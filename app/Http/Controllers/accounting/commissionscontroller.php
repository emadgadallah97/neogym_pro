<?php

namespace App\Http\Controllers\accounting;

use App\Http\Controllers\Controller;
use App\Models\accounting\CommissionSettlement;
use App\Models\accounting\CommissionSettlementItem;
use App\Models\employee\employee as Employee;
use App\Models\sales\MemberSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class commissionscontroller extends Controller
{
    public function index(Request $request)
    {
        $SalesEmployeesList = Employee::query()
            ->where('status', 1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        $settlements = CommissionSettlement::with(['salesEmployee', 'paidByUser'])
            ->orderByDesc('id')
            ->paginate(30);

        $kpiTotal = CommissionSettlement::count();
        $kpiDraft = CommissionSettlement::where('status', 'draft')->count();
        $kpiPaid = CommissionSettlement::where('status', 'paid')->count();
        $kpiPaidAmount = (float) CommissionSettlement::where('status', 'paid')->sum('total_commission_amount');

        $preview = null;
        $previewRows = collect();
        $previewTotals = [
            'all_count' => 0,
            'included_count' => 0,
            'excluded_count' => 0,
            'all_amount' => 0,
            'included_amount' => 0,
            'excluded_amount' => 0,
        ];

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $salesEmployeeId = (int)($request->get('sales_employee_id') ?: 0);

        if ($dateFrom && $dateTo) {
            $from = Carbon::parse($dateFrom)->startOfDay();
            $to = Carbon::parse($dateTo)->endOfDay();

            $q = MemberSubscription::query()
                ->with(['member', 'branch', 'salesEmployee', 'invoice'])
                ->whereBetween('created_at', [$from, $to])
                // بدل status=active: نعتمد على الفاتورة المدفوعة
                ->whereHas('invoice', function ($iq) {
                    $iq->where('status', 'paid');
                })
                ->whereNotNull('sales_employee_id')
                ->where('commission_amount', '>', 0)
                ->where('commission_is_paid', 0)
                ->orderBy('created_at');

            if ($salesEmployeeId > 0) {
                $q->where('sales_employee_id', $salesEmployeeId);
            }

            $previewRows = $q->limit(2000)->get();

            $preview = [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'sales_employee_id' => $salesEmployeeId ?: null,
            ];

            $previewTotals['all_count'] = $previewRows->count();
            $previewTotals['all_amount'] = (float) $previewRows->sum('commission_amount');

            // في مرحلة الـ preview: included = all (الاستثناءات بتتحدد عند الحفظ)
            $previewTotals['included_count'] = $previewTotals['all_count'];
            $previewTotals['included_amount'] = $previewTotals['all_amount'];
        }

        return view('accounting.programs.commissions.index', compact(
            'SalesEmployeesList',
            'settlements',
            'kpiTotal',
            'kpiDraft',
            'kpiPaid',
            'kpiPaidAmount',
            'preview',
            'previewRows',
            'previewTotals'
        ));
    }

    public function create()
    {
        return redirect()->route('commissions.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'sales_employee_id' => 'nullable|integer|exists:employees,id',
            'action' => 'required|string|in:save_draft,pay_now',
            'exclude_subscription_ids' => 'nullable|array',
            'exclude_subscription_ids.*' => 'integer',
            'exclude_reasons' => 'nullable|array',
            'exclude_reasons.*' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $from = Carbon::parse($data['date_from'])->startOfDay();
        $to = Carbon::parse($data['date_to'])->endOfDay();
        $salesEmployeeId = !empty($data['sales_employee_id']) ? (int)$data['sales_employee_id'] : 0;

        $excludeIds = collect($data['exclude_subscription_ids'] ?? [])
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int)$v)
            ->unique()
            ->values()
            ->all();

        $excludeReasons = $data['exclude_reasons'] ?? [];

        $q = MemberSubscription::query()
            ->whereBetween('created_at', [$from, $to])
            // بدل status=active: نعتمد على الفاتورة المدفوعة
            ->whereHas('invoice', function ($iq) {
                $iq->where('status', 'paid');
            })
            ->whereNotNull('sales_employee_id')
            ->where('commission_amount', '>', 0)
            ->where('commission_is_paid', 0);

        if ($salesEmployeeId > 0) {
            $q->where('sales_employee_id', $salesEmployeeId);
        }

        $subs = $q->get();

        if ($subs->isEmpty()) {
            return redirect()->back()->withInput()->with('error', trans('accounting.commissions_no_data'));
        }

        $subsById = $subs->keyBy('id');

        // تجاهل أي exclude id مش موجود ضمن البيانات المستخرجة
        $excludeIds = array_values(array_filter($excludeIds, function ($id) use ($subsById) {
            return $subsById->has($id);
        }));

        $included = $subs->reject(fn ($s) => in_array((int)$s->id, $excludeIds, true));
        $excluded = $subs->filter(fn ($s) => in_array((int)$s->id, $excludeIds, true));

        $totalAll = (float) $subs->sum('commission_amount');
        $totalExcluded = (float) $excluded->sum('commission_amount');
        $totalIncluded = (float) $included->sum('commission_amount');

        $action = $data['action'];
        $notes = $data['notes'] ?? null;

        DB::beginTransaction();
        try {
            $settlement = new CommissionSettlement();
            $settlement->date_from = $from->toDateString();
            $settlement->date_to = $to->toDateString();
            $settlement->sales_employee_id = $salesEmployeeId > 0 ? $salesEmployeeId : null;

            $settlement->status = ($action === 'pay_now') ? 'paid' : 'draft';

            $settlement->total_commission_amount = $totalIncluded;
            $settlement->total_excluded_commission_amount = $totalExcluded;
            $settlement->total_all_commission_amount = $totalAll;

            $settlement->items_count = (int) $included->count();
            $settlement->excluded_items_count = (int) $excluded->count();
            $settlement->all_items_count = (int) $subs->count();

            if ($action === 'pay_now') {
                $settlement->paid_at = Carbon::now();
                $settlement->paid_by = Auth::id();
            } else {
                $settlement->paid_at = null;
                $settlement->paid_by = null;
            }

            $settlement->notes = $notes;
            $settlement->user_add = Auth::id();
            $settlement->save();

            // Items snapshot
            foreach ($subs as $s) {
                $isExcluded = in_array((int)$s->id, $excludeIds, true);
                $reason = null;
                if ($isExcluded) {
                    $reason = $excludeReasons[$s->id] ?? null;
                }

                CommissionSettlementItem::create([
                    'commission_settlement_id' => $settlement->id,
                    'member_subscription_id' => $s->id,
                    'member_id' => $s->member_id ?? null,
                    'branch_id' => $s->branch_id ?? null,
                    'sales_employee_id' => $s->sales_employee_id ?? null,

                    'commission_base_amount' => $s->commission_base_amount,
                    'commission_value_type' => $s->commission_value_type,
                    'commission_value' => $s->commission_value,
                    'commission_amount' => $s->commission_amount ?? 0,

                    'subscription_created_at' => $s->created_at,

                    'is_excluded' => $isExcluded,
                    'exclude_reason' => $reason,
                ]);
            }

            // لو pay_now: علّم الاشتراكات included فقط كـ paid (مع إعادة تحقق invoice=paid وقت التحديث)
            if ($action === 'pay_now') {
                $includedIds = $included->pluck('id')->map(fn ($v) => (int)$v)->values()->all();

                MemberSubscription::query()
                    ->whereIn('id', $includedIds)
                    ->where('commission_is_paid', 0)
                    ->whereHas('invoice', function ($iq) {
                        $iq->where('status', 'paid');
                    })
                    ->update([
                        'commission_is_paid' => 1,
                        'commission_paid_at' => $settlement->paid_at,
                        'commission_paid_by' => $settlement->paid_by,
                        'commission_settlement_id' => $settlement->id,
                        'user_update' => Auth::id(),
                    ]);
            }

            DB::commit();

            return redirect()->route('commissions.show', $settlement->id)
                ->with('success', trans('accounting.commissions_saved_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('accounting.commissions_something_went_wrong'));
        }
    }

    public function show($id)
    {
        $settlement = CommissionSettlement::with(['salesEmployee', 'paidByUser', 'items'])
            ->findOrFail($id);

        return view('accounting.programs.commissions.show', compact('settlement'));
    }

    public function edit($id)
    {
        return redirect()->route('commissions.show', $id);
    }

    public function update(Request $request, $id)
    {
        // مش مستخدم حالياً
        return redirect()->route('commissions.show', $id);
    }

    public function destroy($id)
    {
        $settlement = CommissionSettlement::findOrFail($id);

        if ($settlement->status === 'paid') {
            return redirect()->back()->with('error', trans('accounting.commissions_cannot_delete_paid'));
        }

        $settlement->delete();
        return redirect()->route('commissions.index')->with('success', trans('accounting.deleted_successfully'));
    }

    // إضافي: اعتماد/صرف Settlement محفوظ Draft
    public function pay(Request $request, $id)
    {
        $settlement = CommissionSettlement::with('items')->findOrFail($id);

        if ($settlement->status !== 'draft') {
            return redirect()->back()->with('error', trans('accounting.commissions_only_draft_payable'));
        }

        DB::beginTransaction();
        try {
            $settlement->status = 'paid';
            $settlement->paid_at = Carbon::now();
            $settlement->paid_by = Auth::id();
            $settlement->user_update = Auth::id();
            $settlement->save();

            $includedIds = $settlement->items
                ->where('is_excluded', false)
                ->pluck('member_subscription_id')
                ->map(fn ($v) => (int)$v)
                ->values()
                ->all();

            MemberSubscription::query()
                ->whereIn('id', $includedIds)
                ->where('commission_is_paid', 0)
                ->whereHas('invoice', function ($iq) {
                    $iq->where('status', 'paid');
                })
                ->update([
                    'commission_is_paid' => 1,
                    'commission_paid_at' => $settlement->paid_at,
                    'commission_paid_by' => $settlement->paid_by,
                    'commission_settlement_id' => $settlement->id,
                    'user_update' => Auth::id(),
                ]);

            DB::commit();
            return redirect()->back()->with('success', trans('accounting.commissions_paid_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', trans('accounting.commissions_something_went_wrong'));
        }
    }

    public function cancel(Request $request, $id)
    {
        $settlement = CommissionSettlement::findOrFail($id);

        if ($settlement->status !== 'draft') {
            return redirect()->back()->with('error', trans('accounting.commissions_only_draft_cancellable'));
        }

        $settlement->status = 'cancelled';
        $settlement->user_update = Auth::id();
        $settlement->save();

        return redirect()->back()->with('success', trans('accounting.updated_successfully'));
    }
}
