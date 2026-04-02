<?php

namespace App\Http\Controllers\accounting;

use App\Http\Controllers\Controller;
use App\Models\accounting\CommissionSettlement;
use App\Models\accounting\CommissionSettlementItem;
use App\Models\accounting\Expense;
use App\Models\accounting\ExpensesType;
use App\Models\employee\employee as Employee;
use App\Models\general\Branch;
use App\Models\general\GeneralSetting;
use App\Models\sales\MemberSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class commissionscontroller extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:commissions_view')->only(['index', 'show']);
        $this->middleware('permission:commissions_create')->only(['store']);
        $this->middleware('permission:commissions_pay')->only(['pay']);
        $this->middleware('permission:commissions_cancel')->only(['cancel']);
        $this->middleware('permission:commissions_delete')->only(['destroy']);
        $this->middleware('permission:commissions_print')->only(['printSettlement']);
    }
    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function accessibleBranchIds(): array
    {
        $user = Auth::user();
        if (!$user->employee_id) return [];

        return DB::table('employee_branch')
            ->where('employee_id', $user->employee_id)
            ->pluck('branch_id')
            ->map(fn($id) => (int)$id)
            ->toArray();
    }

    private function userCanAccessBranch(int $branchId): bool
    {
        $ids = $this->accessibleBranchIds();
        return empty($ids) || in_array($branchId, $ids);
    }

    // ─────────────────────────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $branchIds = $this->accessibleBranchIds();

        // ✅ موظفو المبيعات مقيّدون بفروع المستخدم
        $SalesEmployeesList = Employee::query()
            ->where('status', 1)
            ->when(!empty($branchIds), fn($q) => $q->whereHas('branches', fn($b) => $b->whereIn('branches.id', $branchIds)))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        // ✅ Scope يعمل تلقائياً — فروع المستخدم فقط
        $BranchesList = Branch::select(['id', 'name'])
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        $ExpensesTypes = ExpensesType::where('status', 1)->orderByDesc('id')->get();

        // ✅ تسويات فروع المستخدم فقط
        $settlements = CommissionSettlement::with([
            'salesEmployee',
            'paidByUser',
            'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
        ])
        ->when(!empty($branchIds), fn($q) => $q->whereIn('branch_id', $branchIds))
        ->orderByDesc('id')
        ->paginate(30);

        // ✅ KPIs مقيّدة بفروع المستخدم
        $baseKpi = CommissionSettlement::when(!empty($branchIds), fn($q) => $q->whereIn('branch_id', $branchIds));

        $kpiTotal       = (clone $baseKpi)->count();
        $kpiDraft       = (clone $baseKpi)->where('status', 'draft')->count();
        $kpiPaid        = (clone $baseKpi)->where('status', 'paid')->count();
        $kpiPaidAmount  = (float)(clone $baseKpi)->where('status', 'paid')->sum('total_commission_amount');

        $preview      = null;
        $previewRows  = collect();
        $previewTotals = [
            'all_count'       => 0,
            'included_count'  => 0,
            'excluded_count'  => 0,
            'all_amount'      => 0,
            'included_amount' => 0,
            'excluded_amount' => 0,
        ];

        $dateFrom        = $request->get('date_from');
        $dateTo          = $request->get('date_to');
        $salesEmployeeId = (int)($request->get('sales_employee_id') ?: 0);
        $branchId        = (int)($request->get('branch_id') ?: 0);

        if ($dateFrom && $dateTo) {
            $this->authorize('commissions_extract');
            $from = Carbon::parse($dateFrom)->startOfDay();
            $to   = Carbon::parse($dateTo)->endOfDay();

            $q = MemberSubscription::query()
                ->with(['member', 'branch', 'salesEmployee', 'invoice'])
                ->whereBetween('created_at', [$from, $to])
                ->whereHas('invoice', fn($iq) => $iq->where('status', 'paid'))
                ->whereNotNull('sales_employee_id')
                ->where('commission_amount', '>', 0)
                ->where('commission_is_paid', 0)
                ->orderBy('created_at');

            // ✅ فلتر فروع المستخدم على الاشتراكات
            if (!empty($branchIds)) {
                $q->whereIn('branch_id', $branchIds);
            }

            if ($salesEmployeeId > 0) {
                $q->where('sales_employee_id', $salesEmployeeId);
            }

            // ✅ الفلتر اليدوي مقيّد بفروع المستخدم
            if ($branchId > 0 && $this->userCanAccessBranch($branchId)) {
                $q->where('branch_id', $branchId);
            }

            $previewRows = $q->limit(2000)->get();

            $preview = [
                'date_from'         => $from->toDateString(),
                'date_to'           => $to->toDateString(),
                'sales_employee_id' => $salesEmployeeId ?: null,
                'branch_id'         => $branchId ?: null,
            ];

            $previewTotals['all_count']       = $previewRows->count();
            $previewTotals['all_amount']       = (float)$previewRows->sum('commission_amount');
            $previewTotals['included_count']   = $previewTotals['all_count'];
            $previewTotals['included_amount']  = $previewTotals['all_amount'];
        }

        return view('accounting.programs.commissions.index', compact(
            'SalesEmployeesList',
            'BranchesList',
            'ExpensesTypes',
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

    public function create() { return redirect()->route('commissions.index'); }

    // ─────────────────────────────────────────────────────────────
    // Store
    // ─────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        if ($request->action === 'pay_now') {
            $this->authorize('commissions_pay');
        }
        $data = $request->validate([
            'date_from'                  => 'required|date',
            'date_to'                    => 'required|date|after_or_equal:date_from',
            'sales_employee_id'          => 'nullable|integer|exists:employees,id',
            'branch_id'                  => 'nullable|integer|exists:branches,id',
            'action'                     => 'required|string|in:save_draft,pay_now',
            'exclude_subscription_ids'   => 'nullable|array',
            'exclude_subscription_ids.*' => 'integer',
            'exclude_reasons'            => 'nullable|array',
            'exclude_reasons.*'          => 'nullable|string|max:255',
            'notes'                      => 'nullable|string',
            'expense_type_id'            => 'nullable|required_if:action,pay_now|integer|exists:expenses_types,id',
            'expense_branch_id'          => 'nullable|integer|exists:branches,id',
            'expense_disbursed_by'       => 'nullable|integer|exists:employees,id',
        ]);

        $from            = Carbon::parse($data['date_from'])->startOfDay();
        $to              = Carbon::parse($data['date_to'])->endOfDay();
        $salesEmployeeId = !empty($data['sales_employee_id']) ? (int)$data['sales_employee_id'] : 0;
        $branchIdFilter  = !empty($data['branch_id']) ? (int)$data['branch_id'] : 0;

        // ✅ التحقق أن الفرع المختار ضمن فروع المستخدم
        if ($branchIdFilter > 0 && !$this->userCanAccessBranch($branchIdFilter)) {
            return redirect()->back()->withInput()->with('error', trans('accounting.branch_not_allowed'));
        }

        $branchIds = $this->accessibleBranchIds();

        $excludeIds = collect($data['exclude_subscription_ids'] ?? [])
            ->filter(fn($v) => is_numeric($v))
            ->map(fn($v) => (int)$v)
            ->unique()->values()->all();

        $excludeReasons = $data['exclude_reasons'] ?? [];

        $q = MemberSubscription::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('invoice', fn($iq) => $iq->where('status', 'paid'))
            ->whereNotNull('sales_employee_id')
            ->where('commission_amount', '>', 0)
            ->where('commission_is_paid', 0);

        // ✅ فلتر فروع المستخدم
        if (!empty($branchIds)) {
            $q->whereIn('branch_id', $branchIds);
        }

        if ($salesEmployeeId > 0) $q->where('sales_employee_id', $salesEmployeeId);
        if ($branchIdFilter > 0)  $q->where('branch_id', $branchIdFilter);

        $subs = $q->get();

        if ($subs->isEmpty()) {
            return redirect()->back()->withInput()->with('error', trans('accounting.commissions_no_data'));
        }

        $groupedByBranch = $subs->groupBy('branch_id');
        $action          = $data['action'];
        $notes           = $data['notes'] ?? null;
        $lastSettlementId = null;

        DB::beginTransaction();
        try {
            foreach ($groupedByBranch as $currentBranchId => $branchSubs) {
                $subsById = $branchSubs->keyBy('id');

                $branchExcludeIds = array_values(array_filter(
                    $excludeIds,
                    fn($id) => $subsById->has($id)
                ));

                $included = $branchSubs->reject(fn($s) => in_array((int)$s->id, $branchExcludeIds, true));
                $excluded = $branchSubs->filter(fn($s)  => in_array((int)$s->id, $branchExcludeIds, true));

                $totalAll      = (float)$branchSubs->sum('commission_amount');
                $totalExcluded = (float)$excluded->sum('commission_amount');
                $totalIncluded = (float)$included->sum('commission_amount');

                $settlement = new CommissionSettlement();
                $settlement->date_from                        = $from->toDateString();
                $settlement->date_to                          = $to->toDateString();
                $settlement->sales_employee_id                = $salesEmployeeId > 0 ? $salesEmployeeId : null;
                $settlement->branch_id                        = $currentBranchId ?: null;
                $settlement->status                           = ($action === 'pay_now') ? 'paid' : 'draft';
                $settlement->total_commission_amount          = $totalIncluded;
                $settlement->total_excluded_commission_amount = $totalExcluded;
                $settlement->total_all_commission_amount      = $totalAll;
                $settlement->items_count                      = (int)$included->count();
                $settlement->excluded_items_count             = (int)$excluded->count();
                $settlement->all_items_count                  = (int)$branchSubs->count();
                $settlement->paid_at                          = $action === 'pay_now' ? Carbon::now() : null;
                $settlement->paid_by                          = $action === 'pay_now' ? Auth::id() : null;
                $settlement->notes                            = $notes;
                $settlement->user_add                         = Auth::id();
                $settlement->save();

                $lastSettlementId = $settlement->id;

                foreach ($branchSubs as $s) {
                    $isExcluded = in_array((int)$s->id, $branchExcludeIds, true);

                    CommissionSettlementItem::create([
                        'commission_settlement_id' => $settlement->id,
                        'member_subscription_id'   => $s->id,
                        'member_id'                => $s->member_id        ?? null,
                        'branch_id'                => $s->branch_id        ?? null,
                        'sales_employee_id'        => $s->sales_employee_id ?? null,
                        'commission_base_amount'   => $s->commission_base_amount,
                        'commission_value_type'    => $s->commission_value_type,
                        'commission_value'         => $s->commission_value,
                        'commission_amount'        => $s->commission_amount ?? 0,
                        'subscription_created_at'  => $s->created_at,
                        'is_excluded'              => $isExcluded,
                        'exclude_reason'           => $isExcluded ? ($excludeReasons[$s->id] ?? null) : null,
                    ]);
                }

                if ($action === 'pay_now') {
                    $includedIds = $included->pluck('id')->map(fn($v) => (int)$v)->values()->all();

                    MemberSubscription::query()
                        ->whereIn('id', $includedIds)
                        ->where('commission_is_paid', 0)
                        ->whereHas('invoice', fn($iq) => $iq->where('status', 'paid'))
                        ->update([
                            'commission_is_paid'          => 1,
                            'commission_paid_at'          => $settlement->paid_at,
                            'commission_paid_by'          => $settlement->paid_by,
                            'commission_settlement_id'    => $settlement->id,
                            'user_update'                 => Auth::id(),
                        ]);

                    $this->createExpenseFromSettlement(
                        $settlement,
                        (int)($data['expense_type_id'] ?? 0),
                        (int)($data['expense_branch_id'] ?? 0) ?: ($currentBranchId ?: null),
                        !empty($data['expense_disbursed_by']) ? (int)$data['expense_disbursed_by'] : null
                    );
                }
            }

            DB::commit();
            return redirect()->route('commissions.show', $lastSettlementId)
                ->with('success', trans('accounting.commissions_saved_successfully'));

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('accounting.commissions_something_went_wrong'));
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Show
    // ─────────────────────────────────────────────────────────────

    public function show($id)
    {
        $settlement = CommissionSettlement::with([
            'salesEmployee',
            'paidByUser',
            // ✅ withoutGlobalScope لضمان ظهور اسم الفرع
            'branch'            => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            'items.salesEmployee',
            'items.branch'      => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
        ])->findOrFail($id);

        // ✅ Scope يعمل تلقائياً
        $BranchesList  = Branch::select(['id', 'name'])->where('status', 1)->orderByDesc('id')->get();
        $ExpensesTypes = ExpensesType::where('status', 1)->orderByDesc('id')->get();

        return view('accounting.programs.commissions.show', compact(
            'settlement', 'BranchesList', 'ExpensesTypes'
        ));
    }

    public function edit($id)   { return redirect()->route('commissions.show', $id); }
    public function update(Request $request, $id) { return redirect()->route('commissions.show', $id); }

    // ─────────────────────────────────────────────────────────────
    // Destroy
    // ─────────────────────────────────────────────────────────────

    public function destroy($id)
    {
        $settlement = CommissionSettlement::findOrFail($id);

        if ($settlement->status === 'paid') {
            return redirect()->back()->with('error', trans('accounting.commissions_cannot_delete_paid'));
        }

        $settlement->delete();
        return redirect()->route('commissions.index')->with('success', trans('accounting.deleted_successfully'));
    }

    // ─────────────────────────────────────────────────────────────
    // Pay
    // ─────────────────────────────────────────────────────────────

    public function pay(Request $request, $id)
    {
        $request->validate([
            'expense_type_id'      => 'required|integer|exists:expenses_types,id',
            'expense_branch_id'    => 'nullable|integer|exists:branches,id',
            'expense_disbursed_by' => 'nullable|integer|exists:employees,id',
        ]);

        $settlement = CommissionSettlement::with('items')->findOrFail($id);

        if ($settlement->status !== 'draft') {
            return redirect()->back()->with('error', trans('accounting.commissions_only_draft_payable'));
        }

        // ✅ التحقق أن فرع التسوية ضمن فروع المستخدم
        if ($settlement->branch_id && !$this->userCanAccessBranch((int)$settlement->branch_id)) {
            return redirect()->back()->with('error', trans('accounting.branch_not_allowed'));
        }

        DB::beginTransaction();
        try {
            $settlement->status      = 'paid';
            $settlement->paid_at     = Carbon::now();
            $settlement->paid_by     = Auth::id();
            $settlement->user_update = Auth::id();
            $settlement->save();

            $includedIds = $settlement->items
                ->where('is_excluded', false)
                ->pluck('member_subscription_id')
                ->map(fn($v) => (int)$v)
                ->values()->all();

            MemberSubscription::query()
                ->whereIn('id', $includedIds)
                ->where('commission_is_paid', 0)
                ->whereHas('invoice', fn($iq) => $iq->where('status', 'paid'))
                ->update([
                    'commission_is_paid'       => 1,
                    'commission_paid_at'       => $settlement->paid_at,
                    'commission_paid_by'       => $settlement->paid_by,
                    'commission_settlement_id' => $settlement->id,
                    'user_update'              => Auth::id(),
                ]);

            $expenseBranchId = $request->expense_branch_id
                ? (int)$request->expense_branch_id
                : ($settlement->branch_id ?: null);

            $this->createExpenseFromSettlement(
                $settlement,
                (int)$request->expense_type_id,
                $expenseBranchId,
                $request->expense_disbursed_by ? (int)$request->expense_disbursed_by : null
            );

            DB::commit();
            return redirect()->back()->with('success', trans('accounting.commissions_paid_successfully'));

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', trans('accounting.commissions_something_went_wrong'));
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Cancel
    // ─────────────────────────────────────────────────────────────

    public function cancel(Request $request, $id)
    {
        $settlement = CommissionSettlement::findOrFail($id);

        if ($settlement->status !== 'draft') {
            return redirect()->back()->with('error', trans('accounting.commissions_only_draft_cancellable'));
        }

        $settlement->status      = 'cancelled';
        $settlement->user_update = Auth::id();
        $settlement->save();

        return redirect()->back()->with('success', trans('accounting.updated_successfully'));
    }

    // ─────────────────────────────────────────────────────────────
    // Print
    // ─────────────────────────────────────────────────────────────

    public function printSettlement($id)
    {
        $settlement = CommissionSettlement::with([
            'salesEmployee',
            'paidByUser',
            'branch'       => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            'items.salesEmployee',
            'items.branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
        ])->findOrFail($id);

        $settings = GeneralSetting::query()->where('status', 1)->first();
        $orgName  = '-';

        if ($settings) {
            $orgName = method_exists($settings, 'getTranslation')
                ? ($settings->getTranslation('name', app()->getLocale())
                    ?: ($settings->getTranslation('name', 'ar')
                        ?: $settings->getTranslation('name', 'en')))
                : ($settings->name ?? '-');
        }

        return view('accounting.programs.commissions.print', compact('settlement', 'orgName'));
    }

    // ─────────────────────────────────────────────────────────────
    // Private: Create Expense from Settlement
    // ─────────────────────────────────────────────────────────────

    private function createExpenseFromSettlement(
        CommissionSettlement $settlement,
        int $expenseTypeId,
        ?int $branchId,
        ?int $disbursedByEmployeeId
    ): void {
        if ($expenseTypeId <= 0) return;

        if (!$branchId && $settlement->branch_id) $branchId = $settlement->branch_id;
        if (!$branchId) {
            $firstItem = $settlement->items->first();
            $branchId  = $firstItem ? $firstItem->branch_id : null;
        }
        if (!$branchId) return;

        $empName = '';
        if ($settlement->salesEmployee) {
            $empName = $settlement->salesEmployee->fullname
                ?? trim(($settlement->salesEmployee->first_name ?? '') . ' ' . ($settlement->salesEmployee->last_name ?? ''));
        }

        Expense::create([
            'branchid'                 => $branchId,
            'expensestypeid'           => $expenseTypeId,
            'expensedate'              => Carbon::now()->toDateString(),
            'amount'                   => $settlement->total_commission_amount,
            'recipientname'            => trans('accounting.commissions_expense_recipient'),
            'recipientphone'           => null,
            'recipientnationalid'      => null,
            'disbursedbyemployeeid'    => $disbursedByEmployeeId,
            'description'              => trans('accounting.commissions_expense_description', [
                'id'   => $settlement->id,
                'from' => optional($settlement->date_from)->format('Y-m-d'),
                'to'   => optional($settlement->date_to)->format('Y-m-d'),
            ]),
            'notes'                    => $empName ? ($empName . ' - #' . $settlement->id) : ('#' . $settlement->id),
            'iscancelled'              => false,
            'cancelledat'              => null,
            'cancelledby'              => null,
            'useradd'                  => Auth::id(),
            'userupdate'               => null,
            'commission_settlement_id' => $settlement->id,
        ]);
    }
}
