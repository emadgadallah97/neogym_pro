<?php

namespace App\Http\Controllers\sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\sales\MemberSubscriptionRequest;

use App\Models\general\Branch;
use App\Models\members\Member;
use App\Models\employee\Employee;

use App\Models\subscriptions\subscriptions_plan;
use App\Models\subscriptions\subscriptions_type;

use App\Models\subscriptions\subscriptions_plan_branch_price;

use App\Models\sales\MemberSubscription;
use App\Models\sales\MemberSubscriptionPtAddon;
use App\Models\sales\Payment;
use App\Models\sales\Invoice;
use App\Models\accounting\Income;

use App\Models\coupons_offers\CouponUsage;
use App\Models\coupons_offers\Offer;

use App\Services\sales\SubscriptionPricingService;
use App\Services\sales\CommissionService;
use App\Services\sales\AvailableOffersService;

use App\Services\coupons_offers\OfferEngine;
use App\Services\coupons_offers\CouponEngine;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class salescontroller extends Controller
{
    protected SubscriptionPricingService $pricingService;
    protected OfferEngine $offerEngine;
    protected CouponEngine $couponEngine;
    protected CommissionService $commissionService;
    protected AvailableOffersService $availableOffersService;

    public function __construct(
        SubscriptionPricingService $pricingService,
        OfferEngine $offerEngine,
        CouponEngine $couponEngine,
        CommissionService $commissionService,
        AvailableOffersService $availableOffersService
    ) {
        $this->pricingService = $pricingService;
        $this->offerEngine = $offerEngine;
        $this->couponEngine = $couponEngine;
        $this->commissionService = $commissionService;
        $this->availableOffersService = $availableOffersService;
        
        $this->middleware('permission:sales');
        $this->middleware('permission:sales_view_subscriptions', ['only' => ['subscriptionsList', 'ajaxCurrentSubscriptionsTable']]);
        $this->middleware('permission:sales_view_subscription_details', ['only' => ['show', 'ajaxSubscriptionShowModal', 'invoicePrint']]);
    }

    private function getBasePriceWithoutTrainer(int $branchId, int $planId): ?float
    {
        $row = subscriptions_plan_branch_price::query()
            ->where('branch_id', $branchId)
            ->where('subscriptions_plan_id', $planId)
            ->first();

        if (!$row) return null;

        return $row->price_without_trainer !== null ? (float)$row->price_without_trainer : null;
    }

    private function coachBelongsToBranch(int $coachId, int $branchId): bool
    {
        return Employee::query()
            ->where('id', $coachId)
            ->where('is_coach', 1)
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })
            ->exists();
    }

    /**
     * ✅ Commission setting: 1 = قبل الخصومات (Gross), 0 = بعد الخصومات (Net)
     */
    private function getCalculateCommissionBeforeDiscounts(): bool
    {
        try {
            $row = DB::table('commission_settings')->where('id', 1)->first();
            return $row ? (bool)$row->calculate_commission_before_discounts : false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * ✅ Compute commission snapshot to store in member_subscriptions
     */
    private function computeCommissionSnapshot(?Employee $employee, float $grossAmount, float $netAmount): array
    {
        if (!$employee) {
            return [
                'base_amount' => 0.0,
                'value_type'  => null,
                'value'       => 0.0,
                'amount'      => 0.0,
            ];
        }

        $beforeDiscounts = $this->getCalculateCommissionBeforeDiscounts();
        $base = $beforeDiscounts ? $grossAmount : $netAmount;
        $base = max(0, (float)$base);

        $valueType = $employee->commission_value_type ?? null; // 'percent' | 'fixed'
        $percent   = (float)($employee->commission_percent ?? 0);
        $fixed     = (float)($employee->commission_fixed ?? 0);

        $amount = 0.0;
        $value  = 0.0;

        if ($valueType === 'percent') {
            $value = max(0, $percent);
            $amount = $base * ($value / 100.0);
        } elseif ($valueType === 'fixed') {
            $value = max(0, $fixed);
            $amount = $value;
        } else {
            $valueType = null;
            $value = 0.0;
            $amount = 0.0;
        }

        return [
            'base_amount' => round($base, 2),
            'value_type'  => $valueType,
            'value'       => round($value, 2),
            'amount'      => round(max(0, $amount), 2),
        ];
    }

    /**
     * ✅ Generate invoice number (حل C) قبل الحفظ (بدون الاعتماد على ID)
     */
    private function generateInvoiceNumber(int $branchId): string
    {
        $invoiceNumber = null;

        for ($i = 0; $i < 10; $i++) {
            $invoiceNumber = 'INV-' . $branchId . '-' . Carbon::now()->format('YmdHisv') . '-' . random_int(1000, 9999);

            if (!Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                return $invoiceNumber;
            }

            usleep(15000);
        }

        throw new \RuntimeException('Failed to generate unique invoice number.');
    }


    public function index(Request $request)
    {
        $Branches  = Branch::orderByDesc('id')->get();
        $Plans     = subscriptions_plan::with('type')->orderByDesc('id')->get();
        $Types     = subscriptions_type::orderByDesc('id')->get();
        $Coaches   = Employee::where('is_coach', 1)->orderByDesc('id')->get();
        $Employees = Employee::orderByDesc('id')->get();

        // لو فورم البيع محتاج Members زي ما هو موجود عندك في index
        $Members = Member::orderByDesc('id')->limit(200)->get();

        // IMPORTANT: لا تجيب الاشتراكات هنا (DataTables هي اللي هتجيبها بالـ AJAX)
        return view('sales.index', compact('Branches', 'Members', 'Plans', 'Types', 'Coaches', 'Employees'));
    }

public function ajaxCurrentSubscriptionsTable(Request $request)
{
    $draw   = (int)$request->get('draw', 1);
    $start  = max(0, (int)$request->get('start', 0));
    $length = (int)$request->get('length', 10);
    $length = $length <= 0 ? 10 : min($length, 200);

    // ✅ جلب فروع المستخدم الحالي
    $user = \Illuminate\Support\Facades\Auth::user();
    $accessibleBranchIds = [];

    if ($user->employee_id) {
        $accessibleBranchIds = \Illuminate\Support\Facades\DB::table('employee_branch')
            ->where('employee_id', $user->employee_id)
            ->pluck('branch_id')
            ->map(fn($id) => (int)$id)
            ->toArray();
    }
    // لو فارغة (admin بدون employee_id) => لا قيد

    // Filters
    $q               = trim((string)$request->get('q', ''));
    $branchId        = $request->filled('branch_id') ? (int)$request->branch_id : null;
    $status          = $request->filled('status') ? (string)$request->status : null;
    $planId          = $request->filled('subscriptions_plan_id') ? (int)$request->subscriptions_plan_id : null;
    $typeId          = $request->filled('subscriptions_type_id') ? (int)$request->subscriptions_type_id : null;
    $source          = $request->filled('source') ? (string)$request->source : null;
    $salesEmployeeId = $request->filled('sales_employee_id') ? (int)$request->sales_employee_id : null;
    $hasPt           = $request->filled('has_pt_addons') ? (int)$request->has_pt_addons : null;

    $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
    $dateTo   = $request->filled('date_to')   ? Carbon::parse($request->date_to)->endOfDay()   : null;

    $baseQuery = MemberSubscription::query()
        ->with([
            'member',
            // ✅ withoutGlobalScope لضمان ظهور اسم الفرع دائماً
            'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            'plan',
            'type',
        ])
        ->withCount('ptAddons')
        ->withSum('ptAddons as pt_sessions_count_sum',     'sessions_count')
        ->withSum('ptAddons as pt_sessions_remaining_sum', 'sessions_remaining')
        // ✅ فلتر فروع المستخدم على مستوى الاشتراكات
        ->when(!empty($accessibleBranchIds), fn($q) => $q->whereIn('branch_id', $accessibleBranchIds));

    $recordsTotal = (clone $baseQuery)->count();

    $filteredQuery = (clone $baseQuery)
        ->when($q !== '', function ($query) use ($q) {
            $query->whereHas('member', function ($m) use ($q) {
                $m->where('member_code', 'like', "%{$q}%")
                  ->orWhere('first_name',   'like', "%{$q}%")
                  ->orWhere('last_name',    'like', "%{$q}%");
            });
        })
        // ✅ الفلتر اليدوي للفرع مقيّد بفروع المستخدم فقط
        ->when($branchId, function ($query) use ($branchId, $accessibleBranchIds) {
            if (empty($accessibleBranchIds) || in_array($branchId, $accessibleBranchIds)) {
                $query->where('branch_id', $branchId);
            }
        })
        ->when($status,          fn($query) => $query->where('status',          $status))
        ->when($planId,          fn($query) => $query->where('subscriptions_plan_id', $planId))
        ->when($typeId,          fn($query) => $query->where('subscriptions_type_id', $typeId))
        ->when($source,          fn($query) => $query->where('source',          $source))
        ->when($salesEmployeeId, fn($query) => $query->where('sales_employee_id', $salesEmployeeId))
        ->when($dateFrom,        fn($query) => $query->where('start_date', '>=', $dateFrom->format('Y-m-d')))
        ->when($dateTo,          fn($query) => $query->where('start_date', '<=', $dateTo->format('Y-m-d')))
        ->when($hasPt !== null, function ($query) use ($hasPt) {
            if ($hasPt === 1) $query->whereHas('ptAddons');
            if ($hasPt === 0) $query->whereDoesntHave('ptAddons');
        });

    $recordsFiltered = (clone $filteredQuery)->count();

    $orderDir = strtolower((string)$request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
    $orderCol = (int)$request->input('order.0.column', 9);

    $orderMap = [
        0 => 'id',
        6 => 'total_amount',
        7 => 'status',
        8 => 'created_at',
        9 => 'created_at',
    ];
    $orderBy = $orderMap[$orderCol] ?? 'created_at';

    $rows = $filteredQuery
        ->orderBy($orderBy, $orderDir)
        ->skip($start)
        ->take($length)
        ->get();

    $data  = [];
    $rowNum = $start + 1;

    foreach ($rows as $row) {
        $baseIncluded  = (int)($row->sessions_included ?? $row->sessions_count ?? 0);
        $baseRemaining = (int)($row->sessions_remaining ?? 0);

        $ptCount         = (int)($row->pt_addons_count ?? 0);
        $ptTotal         = (int)($row->pt_sessions_count_sum ?? 0);
        $ptRemainingRaw  = $row->pt_sessions_remaining_sum;
        $ptRemaining     = (int)($ptRemainingRaw ?? 0);

        $statusKey  = 'sales.status_' . ($row->status ?? '');
        $statusText = trans($statusKey);
        if ($statusText === $statusKey) $statusText = (string)($row->status ?? '-');

        $sourceMap = [
            'reception'  => 'sales.source_reception',
            'website'    => 'sales.source_website',
            'mobile'     => 'sales.source_mobile',
            'callcenter' => 'sales.source_callcenter',
            'partner'    => 'sales.source_partner',
            'other'      => 'sales.source_other',
        ];
        $src        = (string)($row->source ?? '');
        $sourceKey  = $sourceMap[$src] ?? null;
        $sourceText = $sourceKey ? trans($sourceKey) : ($src ?: '-');
        if ($sourceKey && $sourceText === $sourceKey) $sourceText = ($src ?: '-');

        $memberCode = $row->member?->member_code ?? $row->member_id;
        $memberName = $row->member?->full_name
            ?? trim(($row->member?->first_name ?? '') . ' ' . ($row->member?->last_name ?? ''));

        $isNotExpired = !$row->end_date || $row->end_date->startOfDay()->gte(now()->startOfDay());
        $canAddPt = ((string)($row->status ?? '') === 'active')
            && $isNotExpired
            && ($baseIncluded == 0 || $baseRemaining > 0);

        $showBtn = '';
        if (auth()->user()->can('sales_view_subscription_details')) {
            $showBtn  = '<button type="button" class="btn btn-sm btn-outline-primary js-subscription-show" data-id="' . $row->id . '">'
                        . (trans('sales.view') ?? 'عرض') . '</button>';
        }

        $addPtBtn = '';
        if ($canAddPt && auth()->user()->can('sales_add_pt_sessions')) {
            $url      = route('sales.subscriptions.pt_addons.create', $row->id);
            $addPtBtn = ' <a href="' . $url . '" target="_blank" class="btn btn-sm btn-outline-success ms-1">'
                        . (trans('sales.add_pt') ?? 'إضافة PT') . '</a>';
        }

        $renewBtn = '';
        // NOTE: Keeping renewBtn logic as is, or you might want to add a permission for it later if requested.
        if ((string)($row->status ?? '') === 'expired' && is_null($row->renewed_to)) {
            $renewBtn = ' <button type="button" class="btn btn-sm btn-outline-warning ms-1 js-subscription-renew" data-id="' . $row->id . '">'
                        . (trans('sales.renew') ?? 'تجديد') . '</button>';
        }

        $data[] = [
            'rownum'        => $rowNum++,
            'member'        => view('sales.partials._dt_member_cell', compact('memberCode', 'memberName'))->render(),
            'plan'          => $row->plan?->getTranslation('name', 'ar') ?? '-',
            'branch'        => $row->branch?->getTranslation('name', 'ar') ?? '-',  // ✅ يظهر دائماً
            'base_sessions' => view('sales.partials._dt_sessions_cell', compact('baseRemaining', 'baseIncluded'))->render(),
            'pt'            => ($ptCount > 0)
                ? '<span class="badge bg-success">' . (trans('sales.yes') ?? 'نعم') . ' (' . $ptRemaining . '/' . $ptTotal . ')</span>'
                : '<span class="badge bg-light text-dark">' . (trans('sales.no') ?? 'لا') . '</span>',
            'source'        => e($sourceText),
            'total'         => number_format((float)$row->total_amount, 2),
            'status'        => '<span class="badge bg-secondary">' . e($statusText) . '</span>',
            'created_at'    => (string)$row->created_at,
            'actions'       => $showBtn . $addPtBtn . $renewBtn,
        ];
    }

    return response()->json([
        'draw'            => $draw,
        'recordsTotal'    => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data'            => $data,
    ]);
}





    public function ajaxMembersByBranch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        $branchId = (int)$request->branch_id;

        $members = Member::where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->orderBy('member_code')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'text' => ($m->member_code ?? $m->id) . ' - ' . ($m->full_name ?? ''),
                ];
            });

        return response()->json(['ok' => true, 'data' => $members]);
    }

    public function ajaxPlansByBranch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        $branchId = (int)$request->branch_id;

        $plans = subscriptions_plan::query()
            ->withoutTrashed()
            ->where('status', 1)
            ->whereHas('planBranches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->with('type')
            ->orderByDesc('id')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'text' => ($p->code ? '[' . $p->code . '] ' : '') . $p->getTranslation('name', 'ar'),
                    'type_id' => $p->subscriptions_type_id,
                    'duration_days' => (int)$p->duration_days,
                    'sessions_count' => (int)$p->sessions_count,
                ];
            });

        return response()->json(['ok' => true, 'data' => $plans]);
    }

    /**
     * ✅ AJAX: جلب السعر الأساسي للخطة في الفرع (بدون مدرب فقط)
     */
    public function ajaxPlanBasePrice(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
                'subscriptions_plan_id' => 'required|integer|exists:subscriptions_plans,id',
            ]);

            $branchId = (int)$request->branch_id;
            $planId = (int)$request->subscriptions_plan_id;

            $price = $this->getBasePriceWithoutTrainer($branchId, $planId);
            if ($price === null) {
                return response()->json([
                    'ok' => false,
                    'message' => trans('sales.base_price_not_found'),
                ], 422);
            }

            return response()->json([
                'ok' => true,
                'data' => [
                    'price_without_trainer' => round((float)$price, 2),
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => trans('sales.ajax_error_try_again'),
            ], 500);
        }
    }

    /**
     * ✅ AJAX: جلب المدربين المرتبطين بالفرع المختار فقط (employee_branch pivot)
     */
    public function ajaxCoachesByBranch(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
            ]);

            $branchId = (int)$request->branch_id;

            $coaches = Employee::query()
                ->where('is_coach', 1)
                ->whereHas('branches', function ($q) use ($branchId) {
                    $q->where('branches.id', $branchId);
                })
                ->orderByDesc('id')
                ->get()
                ->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'text' => $c->full_name ?? trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
                    ];
                });

            return response()->json([
                'ok' => true,
                'data' => $coaches,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => trans('sales.ajax_error_try_again'),
            ], 500);
        }
    }

    /**
     * ✅ سعر الجلسة للمدرب (PT) + تأمين أنه تابع للفرع
     */
    public function ajaxTrainerSessionPrice(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'trainer_id' => 'required|integer|exists:employees,id',
        ]);

        $branchId = (int)$request->branch_id;
        $trainerId = (int)$request->trainer_id;

        if (!$this->coachBelongsToBranch($trainerId, $branchId)) {
            return response()->json([
                'ok' => false,
                'message' => trans('sales.coach_not_in_branch'),
            ], 422);
        }

        $price = $this->pricingService->getTrainerSessionPrice($trainerId);

        return response()->json([
            'ok' => true,
            'data' => [
                'trainer_id' => $trainerId,
                'session_price' => round((float)$price, 2),
            ]
        ]);
    }

    /**
     * ✅ Preview: السعر الأساسي فقط + PT addons
     */
    public function ajaxPricingPreview(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
                'subscriptions_plan_id' => 'required|integer|exists:subscriptions_plans,id',
                'subscriptions_type_id' => 'nullable|integer|exists:subscriptions_types,id',
                'start_date' => 'nullable|date',

                'pt_addons' => 'nullable|array',
                'pt_addons.*.trainer_id' => 'required_with:pt_addons|integer|exists:employees,id',
                'pt_addons.*.sessions_count' => 'required_with:pt_addons|integer|min:1',

                'offer_id' => 'nullable|integer|exists:offers,id',
            ]);

            $branchId = (int)$request->branch_id;
            $planId = (int)$request->subscriptions_plan_id;
            $typeId = $request->subscriptions_type_id ? (int)$request->subscriptions_type_id : null;

            $plan = subscriptions_plan::with('type')->findOrFail($planId);

            $pricePlan = $this->getBasePriceWithoutTrainer($branchId, $planId);
            if ($pricePlan === null) {
                return response()->json([
                    'ok' => false,
                    'message' => trans('sales.base_price_not_found'),
                ], 422);
            }

            // PT addons
            $ptInput = $request->pt_addons ?? [];
            $ptResult = $this->pricingService->computePtAddons($ptInput);
            $ptTotal = (float)$ptResult['total_amount'];

            $grossAmount = (float)$pricePlan + (float)$ptTotal;

            $durationDays = (int)($plan->duration_days ?? 0);
            $sessionsCount = (int)($plan->sessions_count ?? 0);

            $endDate = null;
            if (!empty($request->start_date) && $durationDays > 0) {
                $sd = Carbon::parse($request->start_date);
                $endDate = $sd->copy()->addDays($durationDays)->format('Y-m-d');
            }

            // Offers
            $offerContext = [
                'applies_to'            => 'subscription',
                'subscriptions_plan_id' => $planId,
                'subscriptions_type_id' => $typeId,
                'branch_id'             => $branchId,
                'duration_value'        => $durationDays,
                'duration_unit'         => 'day',
                'amount'                => $grossAmount,
            ];

            $selectedOfferId = $request->offer_id ? (int)$request->offer_id : null;
            $offerData = null;

            if ($selectedOfferId) {
                $offer = Offer::find($selectedOfferId);
                if ($offer) {
                    $offerData = $this->availableOffersService->computeSingleOfferDiscount($offer, $offerContext);
                }
            }

            // ✅ لم يختار المستخدم عرض = بدون خصم عرض (الاختيار يدوي فقط)
            // if (!$offerData) { ... getBestOffer ... } // تم إلغاء التطبيق التلقائي

            $offerDiscount = $offerData ? (float)$offerData['discount_amount'] : 0.0;
            $amountAfterOffer = max(0, $grossAmount - $offerDiscount);

            return response()->json([
                'ok' => true,
                'data' => [
                    'duration_days' => $durationDays,
                    'sessions_count' => $sessionsCount,
                    'end_date' => $endDate,

                    'price_plan' => round((float)$pricePlan, 2),
                    'pt_total' => round($ptTotal, 2),
                    'gross_amount' => round($grossAmount, 2),

                    'selected_offer' => $offerData,
                    'offer_discount' => round($offerDiscount, 2),
                    'amount_after_offer' => round($amountAfterOffer, 2),
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => trans('sales.ajax_error_try_again'),
            ], 500);
        }
    }

    public function ajaxOffersList(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
                'subscriptions_plan_id' => 'required|integer|exists:subscriptions_plans,id',
                'subscriptions_type_id' => 'nullable|integer|exists:subscriptions_types,id',

                'pt_addons' => 'nullable|array',
                'pt_addons.*.trainer_id' => 'required_with:pt_addons|integer|exists:employees,id',
                'pt_addons.*.sessions_count' => 'required_with:pt_addons|integer|min:1',
            ]);

            $branchId = (int)$request->branch_id;
            $planId = (int)$request->subscriptions_plan_id;
            $typeId = $request->subscriptions_type_id ? (int)$request->subscriptions_type_id : null;

            $plan = subscriptions_plan::with('type')->findOrFail($planId);

            $pricePlan = $this->getBasePriceWithoutTrainer($branchId, $planId);
            if ($pricePlan === null) {
                return response()->json([
                    'ok' => false,
                    'message' => trans('sales.base_price_not_found'),
                ], 422);
            }

            $ptInput = $request->pt_addons ?? [];
            $ptResult = $this->pricingService->computePtAddons($ptInput);
            $ptTotal = (float)$ptResult['total_amount'];

            $grossAmount = (float)$pricePlan + (float)$ptTotal;
            $durationDays = (int)($plan->duration_days ?? 0);

            $context = [
                'applies_to'            => 'subscription',
                'subscriptions_plan_id' => $planId,
                'subscriptions_type_id' => $typeId,
                'branch_id'             => $branchId,
                'duration_value'        => $durationDays,
                'duration_unit'         => 'day',
                'amount'                => $grossAmount,
            ];

            $offers = $this->availableOffersService->listOffersWithDiscount($context);

            return response()->json([
                'ok' => true,
                'data' => [
                    'gross_amount' => round($grossAmount, 2),
                    'offers' => $offers,
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => trans('sales.ajax_error_try_again'),
            ], 500);
        }
    }

    /**
     * ✅ NEW: زر "تحقق وتطبيق" للكوبون (AJAX)
     * يحسب: gross -> يطبق offer (selected/best) -> يتحقق من الكوبون على amount_after_offer
     */
    public function ajaxValidateCoupon(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
                'member_id' => 'nullable|integer|exists:members,id',

                'subscriptions_plan_id' => 'required|integer|exists:subscriptions_plans,id',
                'subscriptions_type_id' => 'nullable|integer|exists:subscriptions_types,id',

                'pt_addons' => 'nullable|array',
                'pt_addons.*.trainer_id' => 'required_with:pt_addons|integer|exists:employees,id',
                'pt_addons.*.sessions_count' => 'required_with:pt_addons|integer|min:1',

                'offer_id' => 'nullable|integer|exists:offers,id',
                'coupon_code' => 'required|string|max:60',
            ]);

            $branchId = (int)$request->branch_id;
            $memberId = $request->member_id ? (int)$request->member_id : null;

            $planId = (int)$request->subscriptions_plan_id;
            $typeId = $request->subscriptions_type_id ? (int)$request->subscriptions_type_id : null;

            $couponCode = trim((string)$request->coupon_code);

            $plan = subscriptions_plan::with('type')->findOrFail($planId);

            // gross = base plan + PT addons
            $pricePlan = $this->getBasePriceWithoutTrainer($branchId, $planId);
            if ($pricePlan === null) {
                return response()->json([
                    'ok' => false,
                    'message' => trans('sales.base_price_not_found'),
                ], 422);
            }

            $ptInput = $request->pt_addons ?? [];
            $ptResult = $this->pricingService->computePtAddons($ptInput);
            $ptTotal = (float)$ptResult['total_amount'];

            $grossAmount = (float)$pricePlan + (float)$ptTotal;

            // apply offer (selected or best)
            $durationDays = (int)($plan->duration_days ?? 0);

            $offerContext = [
                'applies_to'            => 'subscription',
                'subscriptions_plan_id' => $planId,
                'subscriptions_type_id' => $typeId,
                'branch_id'             => $branchId,
                'duration_value'        => $durationDays,
                'duration_unit'         => 'day',
                'amount'                => $grossAmount,
            ];

            $selectedOfferId = $request->offer_id ? (int)$request->offer_id : null;
            $offerDiscount = 0.0;
            $offerChosenId = null;
            $offerName = null;

            if ($selectedOfferId) {
                $offer = Offer::find($selectedOfferId);
                if ($offer) {
                    $single = $this->availableOffersService->computeSingleOfferDiscount($offer, $offerContext);
                    if ($single) {
                        $offerChosenId = (int)$single['offer_id'];
                        $offerDiscount = (float)$single['discount_amount'];
                        $offerName = $single['offer']['name'] ?? null;
                    }
                }
            }

            // ✅ لم يختار المستخدم عرض = بدون خصم عرض (الاختيار يدوي فقط)

            $amountAfterOffer = max(0, $grossAmount - (float)$offerDiscount);

            // validate coupon on amountAfterOffer
            $couponContext = [
                'code'                 => $couponCode,
                'applies_to'           => 'subscription',
                'subscriptions_plan_id' => $planId,
                'subscriptions_type_id' => $typeId,
                'branch_id'            => $branchId,
                'duration_value'       => $durationDays,
                'duration_unit'        => 'day',
                'amount'               => $amountAfterOffer,
                'member_id'            => $memberId,
            ];

            $result = $this->couponEngine->validateAndCompute($couponContext);

            if (empty($result['ok'])) {
                return response()->json([
                    'ok' => false,
                    'message' => $result['message'] ?? trans('sales.coupon_invalid'),
                ], 422);
            }

            $discount = (float)($result['discount_amount'] ?? 0);
            $amountAfterCoupon = max(0, $amountAfterOffer - $discount);

            return response()->json([
                'ok' => true,
                'data' => [
                    'coupon_code' => $couponCode,
                    'coupon_id' => (int)($result['coupon_id'] ?? 0),

                    'gross_amount' => round($grossAmount, 2),

                    'offer_id' => $offerChosenId,
                    'offer_name' => $offerName,
                    'offer_discount' => round((float)$offerDiscount, 2),
                    'amount_after_offer' => round((float)$amountAfterOffer, 2),

                    'coupon_discount' => round($discount, 2),
                    'amount_after_coupon' => round((float)$amountAfterCoupon, 2),

                    'message' => trans('sales.coupon_valid'),
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => trans('sales.ajax_error_try_again'),
            ], 500);
        }
    }

    public function store(MemberSubscriptionRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $branchId = (int)$data['branch_id'];
            $memberId = (int)$data['member_id'];
            $planId   = (int)$data['subscriptions_plan_id'];
            $typeId   = isset($data['subscriptions_type_id']) ? (int)$data['subscriptions_type_id'] : null;

            $plan = subscriptions_plan::with('type')->findOrFail($planId);

            // ✅ السعر الأساسي بدون مدرب
            $pricePlan = $this->getBasePriceWithoutTrainer($branchId, $planId);
            if ($pricePlan === null) {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', trans('sales.base_price_not_found'));
            }

            $durationDays  = (int)($plan->duration_days ?? 0);
            $sessionsCount = (int)($plan->sessions_count ?? 0);

            // ✅ بما أن الخطة تعتمد على حصص
            if ($sessionsCount <= 0) {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', trans('sales.sessions_count_required') ?? 'عدد الحصص بالخطة غير صحيح');
            }

            // PT addons
            $ptInput  = $data['pt_addons'] ?? [];
            $ptResult = $this->pricingService->computePtAddons($ptInput);
            $ptAddons = $ptResult['addons'] ?? [];
            $ptTotal  = (float)($ptResult['total_amount'] ?? 0);

            $grossAmount = (float)$pricePlan + (float)$ptTotal;

            // ✅ تحديد source للفاتورة والمدفوعات (اشتراك فقط vs اشتراك + PT)
            $invoicePaymentSource = ($ptTotal > 0 || !empty($ptAddons))
                ? 'main_subscription&PT'
                : 'main_subscription_only';

            // Offers
            $offerContext = [
                'applies_to'            => 'subscription',
                'subscriptions_plan_id' => $planId,
                'subscriptions_type_id' => $typeId,
                'branch_id'             => $branchId,
                'duration_value'        => $durationDays,
                'duration_unit'         => 'day',
                'amount'                => $grossAmount,
            ];

            $offerDiscount = 0.0;
            $chosenOfferId = null;

            if (!empty($data['offer_id'])) {
                $offer = Offer::find((int)$data['offer_id']);
                if ($offer) {
                    $single = $this->availableOffersService->computeSingleOfferDiscount($offer, $offerContext);
                    if ($single) {
                        $chosenOfferId = (int)$single['offer_id'];
                        $offerDiscount = (float)$single['discount_amount'];
                    }
                }
            }

            $amountAfterOffer = max(0, $grossAmount - $offerDiscount);

            // Coupon
            $couponDiscount = 0.0;
            $couponId = null;

            if (!empty($data['coupon_code'])) {
                $couponContext = [
                    'code'                  => $data['coupon_code'],
                    'applies_to'            => 'subscription',
                    'subscriptions_plan_id' => $planId,
                    'subscriptions_type_id' => $typeId,
                    'branch_id'             => $branchId,
                    'duration_value'        => $durationDays,
                    'duration_unit'         => 'day',
                    'amount'                => $amountAfterOffer,
                    'member_id'             => $memberId,
                ];

                $couponResult = $this->couponEngine->validateAndCompute($couponContext);
                if (empty($couponResult['ok'])) {
                    DB::rollBack();
                    return redirect()->back()->withInput()->with('error', $couponResult['message'] ?? trans('sales.somethingwentwrong'));
                }

                $couponDiscount = (float)($couponResult['discount_amount'] ?? 0);
                $couponId       = (int)($couponResult['coupon_id'] ?? 0);
                if ($couponId <= 0) $couponId = null;
            }

            $totalDiscount = (float)$offerDiscount + (float)$couponDiscount;
            $totalAmount   = max(0, (float)$grossAmount - (float)$totalDiscount);

            // Commission (حسب commission_settings + employee fields)
            $salesEmployee = null;
            if (!empty($data['sales_employee_id'])) {
                $salesEmployee = Employee::find((int)$data['sales_employee_id']);
            }
            $commission = $this->computeCommissionSnapshot($salesEmployee, $grossAmount, $totalAmount);

            // Dates
            $startDate = Carbon::parse($data['start_date']);
            $endDate   = $durationDays > 0 ? $startDate->copy()->addDays($durationDays) : null;

            // Save subscription
            $subscription = new MemberSubscription();
            $subscription->member_id = $memberId;
            $subscription->branch_id = $branchId;
            $subscription->subscriptions_plan_id = $planId;
            $subscription->subscriptions_type_id = $typeId;

            $subscription->plan_code = $plan->code;
            $subscription->plan_name = $plan->name;
            $subscription->duration_days = $durationDays;
            $subscription->sessions_count = $sessionsCount;

            // ✅ بدون مدرب
            $subscription->with_trainer = 0;
            $subscription->main_trainer_id = null;

            $subscription->sessions_included = $sessionsCount;
            $subscription->sessions_remaining = $sessionsCount;

            $subscription->start_date = $startDate->format('Y-m-d');
            $subscription->end_date = $endDate ? $endDate->format('Y-m-d') : null;
            $subscription->status = 'active';

            $subscription->allow_all_branches = (bool)($data['allow_all_branches'] ?? false);
            $subscription->source = $data['source'] ?? 'reception';

            $subscription->price_plan = (float)$pricePlan;
            $subscription->price_pt_addons = (float)$ptTotal;
            $subscription->discount_offer_amount = (float)$offerDiscount;
            $subscription->discount_coupon_amount = (float)$couponDiscount;
            $subscription->total_discount = (float)$totalDiscount;
            $subscription->total_amount = (float)$totalAmount;

            $subscription->offer_id = $chosenOfferId;
            $subscription->coupon_id = $couponId;

            $subscription->sales_employee_id = $salesEmployee?->id;

            $subscription->commission_base_amount = $commission['base_amount'];
            $subscription->commission_value_type  = $commission['value_type'];
            $subscription->commission_value       = $commission['value'];
            $subscription->commission_amount      = $commission['amount'];

            $subscription->user_add = Auth::id();
            $subscription->notes = $data['notes'] ?? null;
            $subscription->save();

            foreach ($ptAddons as $addon) {
                MemberSubscriptionPtAddon::create([
                    'member_subscription_id' => $subscription->id,
                    'trainer_id'              => $addon['trainer_id'],
                    'session_price'           => $addon['session_price'],
                    'sessions_count'          => $addon['sessions_count'],
                    'sessions_remaining'      => $addon['sessions_remaining'],
                    'total_amount'            => $addon['total_amount'],
                ]);
            }

            $invoiceNumber = $this->generateInvoiceNumber($branchId);

            // Payment
            $paymentReference = $data['reference'] ?? $data['payment_reference'] ?? $invoiceNumber;

            Payment::create([
                'member_id'              => $memberId,
                'member_subscription_id' => $subscription->id,
                'amount'                 => $totalAmount,
                'payment_method'         => $data['payment_method'],
                'status'                 => 'paid',
                'paid_at'                => Carbon::now(),
                'reference'              => $paymentReference,
                'notes'                  => null,
                'user_add'               => Auth::id(),
                'source'                 => $invoicePaymentSource,
            ]);

            // Invoice
            $now = Carbon::now();

            $invoice = new Invoice();
            $invoice->invoice_number = $invoiceNumber;
            $invoice->member_id = $memberId;
            $invoice->branch_id = $branchId;
            $invoice->member_subscription_id = $subscription->id;
            $invoice->currency_id = null;
            $invoice->subtotal = $grossAmount;
            $invoice->discount_total = $totalDiscount;
            $invoice->total = $totalAmount;

            $invoice->status = 'paid';
            $invoice->issued_at = $now;
            $invoice->paid_at = $now;

            $invoice->user_add = Auth::id();
            $invoice->source = $invoicePaymentSource;
            $invoice->save();

            // Income
            $member = Member::find($memberId);
            $payerName = $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : null;
            if (!$payerName && $member && $member->full_name) {
                $payerName = $member->full_name;
            }
            $payerPhone = $member ? $member->phone : null;
            $planNameDesc = is_array($plan->name) ? ($plan->name['ar'] ?? $plan->name['en'] ?? '') : $plan->name;

            $incomeType = \App\Models\accounting\incometype::firstOrCreate(
                ['name->en' => 'Subscriptions'],
                ['name' => ['en' => 'Subscriptions', 'ar' => 'الاشتراكات'], 'status' => 1]
            );

            Income::create([
                'branchid'             => $branchId,
                'income_type_id'       => $incomeType->id,
                'incomedate'           => Carbon::now()->format('Y-m-d'),
                'amount'               => $totalAmount,
                'paymentmethod'        => $data['payment_method'],
                'receivedbyemployeeid' => Auth::user()->employee_id ?? null,
                'payername'            => $payerName,
                'payerphone'           => $payerPhone,
                'description'          => trans('sales.new_subscription') . ' - ' . $planNameDesc,
                'notes'                => null,
                'useradd'              => Auth::id(),
            ]);

            if ($couponId) {
                CouponUsage::create([
                    'coupon_id'        => $couponId,
                    'member_id'        => $memberId,
                    'applied_to_type'  => MemberSubscription::class,
                    'applied_to_id'    => $subscription->id,
                    'amount_before'    => $grossAmount,
                    'discount_amount'  => $couponDiscount,
                    'amount_after'     => $totalAmount,
                    'used_at'          => Carbon::now(),
                ]);
            }

            DB::commit();

            // ✅ حفظ وطباعة — يرجع لصفحة المبيعات مع flash لفتح الفاتورة في تاب جديد
            if ($request->input('action') === 'save_and_print') {
                return redirect()->route('sales.index')
                    ->with('success', trans('sales.savedsuccessfully'))
                    ->with('print_invoice_id', $subscription->id);
            }

            return redirect()->route('sales.index')->with('success', trans('sales.savedsuccessfully'));
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Sales store failed', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if (config('app.debug')) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }

            return redirect()->back()->withInput()->with('error', trans('sales.somethingwentwrong'));
        }
    }

public function ajaxSubscriptionShowModal($id)
{
    $subscription = MemberSubscription::with([
        'member',
        // ✅ withoutGlobalScope لضمان ظهور اسم الفرع
        'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
        'plan',
        'type',
        'ptAddons.trainer',
        'payments',
        'invoice',
        'offer',
        'coupon',
        'salesEmployee',
    ])->findOrFail($id);

    $html = view('sales.partials.subscription_details', [
        'subscription' => $subscription,
        'inModal'      => true,
    ])->render();

    return response()->json([
        'ok'   => true,
        'html' => $html,
    ]);
}

public function show($id)
{
    $subscription = MemberSubscription::with([
        'member',
        // ✅ withoutGlobalScope لضمان ظهور اسم الفرع
        'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
        'plan',
        'type',
        'ptAddons.trainer',
        'payments',
        'invoice',
        'offer',
        'coupon',
        'salesEmployee',
    ])->findOrFail($id);

    return view('sales.show', compact('subscription'));
}

    public function renew(Request $request, $expiredSubscriptionId)
    {
        $request->validate([
            'start_date' => 'required|date',
            'payment_method' => 'required|string',
            'offer_id' => 'nullable|integer|exists:offers,id',
            'coupon_code' => 'nullable|string|max:60',
            'sales_employee_id' => 'nullable|integer|exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // 1. Load expired subscription
            $old = MemberSubscription::with('ptAddons')->where('status', 'expired')->findOrFail($expiredSubscriptionId);

            // 2. Load plan
            $plan = subscriptions_plan::with('type')->findOrFail($old->subscriptions_plan_id);

            $branchId = $old->branch_id;
            $memberId = $old->member_id;
            $planId = $old->subscriptions_plan_id;
            $typeId = $old->subscriptions_type_id;

            // 3. Re-calculate prices
            $pricePlan = $this->getBasePriceWithoutTrainer($branchId, $planId);
            if ($pricePlan === null) {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', trans('sales.base_price_not_found'));
            }

            $durationDays = (int)($plan->duration_days ?? 0);
            $sessionsCount = (int)($plan->sessions_count ?? 0);

            if ($sessionsCount <= 0) {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', trans('sales.sessions_count_required') ?? 'عدد الحصص بالخطة غير صحيح');
            }

            // Transfer PT addons with 0 price (already paid)
            $ptAddons = [];
            foreach ($old->ptAddons as $oldAddon) {
                if ($oldAddon->sessions_remaining > 0) {
                    $ptAddons[] = [
                        'trainer_id' => $oldAddon->trainer_id,
                        'sessions_count' => $oldAddon->sessions_remaining,
                        'session_price' => 0,
                        'total_amount' => 0,
                    ];
                }
            }
            $ptTotal  = 0.0;

            $grossAmount = (float)$pricePlan + (float)$ptTotal;

            $invoicePaymentSource = ($ptTotal > 0 || !empty($ptAddons))
                ? 'main_subscription&PT'
                : 'main_subscription_only';

            // Offers
            $offerContext = [
                'applies_to'            => 'subscription',
                'subscriptions_plan_id' => $planId,
                'subscriptions_type_id' => $typeId,
                'branch_id'             => $branchId,
                'duration_value'        => $durationDays,
                'duration_unit'         => 'day',
                'amount'                => $grossAmount,
            ];

            $offerDiscount = 0.0;
            $chosenOfferId = null;

            if (!empty($request->offer_id)) {
                $offer = Offer::find((int)$request->offer_id);
                if ($offer) {
                    $single = $this->availableOffersService->computeSingleOfferDiscount($offer, $offerContext);
                    if ($single) {
                        $chosenOfferId = (int)$single['offer_id'];
                        $offerDiscount = (float)$single['discount_amount'];
                    }
                }
            }

            $amountAfterOffer = max(0, $grossAmount - $offerDiscount);

            // Coupon
            $couponDiscount = 0.0;
            $couponId = null;

            if (!empty($request->coupon_code)) {
                $couponContext = [
                    'code'                  => $request->coupon_code,
                    'applies_to'            => 'subscription',
                    'subscriptions_plan_id' => $planId,
                    'subscriptions_type_id' => $typeId,
                    'branch_id'             => $branchId,
                    'duration_value'        => $durationDays,
                    'duration_unit'         => 'day',
                    'amount'                => $amountAfterOffer,
                    'member_id'             => $memberId,
                ];

                $couponResult = $this->couponEngine->validateAndCompute($couponContext);
                if (empty($couponResult['ok'])) {
                    DB::rollBack();
                    return redirect()->back()->withInput()->with('error', $couponResult['message'] ?? trans('sales.somethingwentwrong'));
                }

                $couponDiscount = (float)($couponResult['discount_amount'] ?? 0);
                $couponId       = (int)($couponResult['coupon_id'] ?? 0);
                if ($couponId <= 0) $couponId = null;
            }

            $totalDiscount = (float)$offerDiscount + (float)$couponDiscount;
            $totalAmount   = max(0, (float)$grossAmount - (float)$totalDiscount);

            $salesEmployee = null;
            if (!empty($request->sales_employee_id)) {
                $salesEmployee = Employee::find((int)$request->sales_employee_id);
            }
            $commission = $this->computeCommissionSnapshot($salesEmployee, $grossAmount, $totalAmount);

            $startDate = Carbon::parse($request->start_date);
            $endDate   = $durationDays > 0 ? $startDate->copy()->addDays($durationDays) : null;

            // 4. Create new subscription
            $newSub = new MemberSubscription();
            $newSub->member_id = $memberId;
            $newSub->branch_id = $branchId;
            $newSub->subscriptions_plan_id = $planId;
            $newSub->subscriptions_type_id = $typeId;

            $newSub->plan_code = $plan->code;
            $newSub->plan_name = $plan->name;
            $newSub->duration_days = $durationDays;
            $newSub->sessions_count = $sessionsCount;
            $newSub->with_trainer = 0;
            $newSub->main_trainer_id = null;
            $newSub->sessions_included = $sessionsCount;
            $newSub->sessions_remaining = $sessionsCount;

            $newSub->start_date = $startDate->format('Y-m-d');
            $newSub->end_date = $endDate ? $endDate->format('Y-m-d') : null;
            $newSub->status = 'active';
            $newSub->allow_all_branches = $old->allow_all_branches;
            $newSub->source = $old->source;

            $newSub->price_plan = (float)$pricePlan;
            $newSub->price_pt_addons = (float)$ptTotal;
            $newSub->discount_offer_amount = (float)$offerDiscount;
            $newSub->discount_coupon_amount = (float)$couponDiscount;
            $newSub->total_discount = (float)$totalDiscount;
            $newSub->total_amount = (float)$totalAmount;

            $newSub->offer_id = $chosenOfferId;
            $newSub->coupon_id = $couponId;
            $newSub->sales_employee_id = $salesEmployee?->id;

            $newSub->commission_base_amount = $commission['base_amount'];
            $newSub->commission_value_type  = $commission['value_type'];
            $newSub->commission_value       = $commission['value'];
            $newSub->commission_amount      = $commission['amount'];

            $newSub->user_add = Auth::id();
            $newSub->notes = $request->notes ?? trans('sales.renewal_notes');

            // Link to old
            $newSub->renewal_of = $old->id;
            $newSub->save();

            // Save new PT addons
            foreach ($ptAddons as $addon) {
                MemberSubscriptionPtAddon::create([
                    'member_subscription_id' => $newSub->id,
                    'trainer_id'              => $addon['trainer_id'],
                    'session_price'           => $addon['session_price'],
                    'sessions_count'          => $addon['sessions_count'],
                    'sessions_remaining'      => $addon['sessions_count'], // Starting fresh based on remaining
                    'total_amount'            => $addon['total_amount'],
                ]);
            }

            // Update old subscription link
            $old->renewed_to = $newSub->id;
            $old->save();

            // Invoice
            $invoiceNumber = $this->generateInvoiceNumber($branchId);
            $now = Carbon::now();

            $invoice = new Invoice();
            $invoice->invoice_number = $invoiceNumber;
            $invoice->member_id = $memberId;
            $invoice->branch_id = $branchId;
            $invoice->member_subscription_id = $newSub->id;
            $invoice->currency_id = null;
            $invoice->subtotal = $grossAmount;
            $invoice->discount_total = $totalDiscount;
            $invoice->total = $totalAmount;
            $invoice->status = 'paid';
            $invoice->issued_at = $now;
            $invoice->paid_at = $now;
            $invoice->user_add = Auth::id();
            $invoice->source = $invoicePaymentSource;
            $invoice->save();

            // Payment
            Payment::create([
                'member_id'              => $memberId,
                'member_subscription_id' => $newSub->id,
                'amount'                 => $totalAmount,
                'payment_method'         => $request->payment_method,
                'status'                 => 'paid',
                'paid_at'                => $now,
                'reference'              => $invoiceNumber,
                'notes'                  => trans('sales.renewal_payment'),
                'user_add'               => Auth::id(),
                'source'                 => $invoicePaymentSource,
            ]);

            // Income
            $member = Member::find($memberId);
            $payerName = $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : null;
            if (!$payerName && $member && $member->full_name) {
                $payerName = $member->full_name;
            }
            $payerPhone = $member ? $member->phone : null;
            $planNameDesc = is_array($plan->name) ? ($plan->name['ar'] ?? $plan->name['en'] ?? '') : $plan->name;

            $incomeType = \App\Models\accounting\incometype::firstOrCreate(
                ['name->en' => 'Subscriptions'],
                ['name' => ['en' => 'Subscriptions', 'ar' => 'الاشتراكات'], 'status' => 1]
            );

            Income::create([
                'branchid'             => $branchId,
                'income_type_id'       => $incomeType->id,
                'incomedate'           => Carbon::now()->format('Y-m-d'),
                'amount'               => $totalAmount,
                'paymentmethod'        => $request->payment_method,
                'receivedbyemployeeid' => Auth::user()->employee_id ?? null,
                'payername'            => $payerName,
                'payerphone'           => $payerPhone,
                'description'          => trans('sales.subscription_renewal') . ' - ' . $planNameDesc,
                'notes'                => trans('sales.renewal_notes'),
                'useradd'              => Auth::id(),
            ]);


            if ($couponId) {
                CouponUsage::create([
                    'coupon_id'        => $couponId,
                    'member_id'        => $memberId,
                    'applied_to_type'  => MemberSubscription::class,
                    'applied_to_id'    => $newSub->id,
                    'amount_before'    => $grossAmount,
                    'discount_amount'  => $couponDiscount,
                    'amount_after'     => $totalAmount,
                    'used_at'          => Carbon::now(),
                ]);
            }

            DB::commit();

            return redirect()->route('sales.subscriptions_list')
                             ->with('success', trans('sales.renewedsuccessfully'))
                             ->with('print_invoice_id', $invoice->member_subscription_id);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Sales renew failed', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if (config('app.debug')) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }

            return redirect()->back()->withInput()->with('error', trans('sales.somethingwentwrong'));
        }
    }

    public function getRenewalDetails($id)
    {
        try {
            $old = MemberSubscription::with(['ptAddons.trainer', 'plan', 'member'])->where('status', 'expired')->findOrFail($id);
            if ($old->renewed_to) {
                return response()->json([
                    'ok' => false,
                    'message' => trans('sales.already_renewed') ?? 'تم تجديد هذا الاشتراك مسبقاً'
                ]);
            }

            $branchId = $old->branch_id;
            $planId = $old->subscriptions_plan_id;

            $pricePlan = $this->getBasePriceWithoutTrainer($branchId, $planId);

            // Fetch PT trainers that have remaining sessions
            $ptDetails = [];
            foreach ($old->ptAddons as $addon) {
                if ($addon->sessions_remaining > 0) {
                    $ptDetails[] = [
                        'trainer_id' => $addon->trainer_id,
                        'trainer_name' => $addon->trainer->full_name ?? trim(($addon->trainer->first_name ?? '') . ' ' . ($addon->trainer->last_name ?? '')),
                        'sessions_remaining' => $addon->sessions_remaining,
                    ];
                }
            }

            // Offers
            $durationDays = (int)($old->plan->duration_days ?? 0);
            $context = [
                'applies_to'            => 'subscription',
                'subscriptions_plan_id' => $planId,
                'subscriptions_type_id' => $old->subscriptions_type_id,
                'branch_id'             => $branchId,
                'duration_value'        => $durationDays,
                'duration_unit'         => 'day',
                'amount'                => $pricePlan, // PT is 0 for renewals
            ];
            $offers = $this->availableOffersService->listOffersWithDiscount($context);

            $memberCode = $old->member->member_code ?? $old->member->id;
            $memberName = $old->member->full_name ?? trim(($old->member->first_name ?? '') . ' ' . ($old->member->last_name ?? ''));

            return response()->json([
                'ok' => true,
                'data' => [
                    'member' => $memberCode . ' - ' . $memberName,
                    'plan_name' => is_array($old->plan_name) ? ($old->plan_name['ar'] ?? ($old->plan_name['en'] ?? '')) : $old->plan_name,
                    'old_end_date' => $old->end_date ? $old->end_date->format('Y-m-d') : null,
                    'base_price' => $pricePlan,
                    'pt_details' => $ptDetails,
                    'branch_id' => $branchId,
                    'subscriptions_plan_id' => $planId,
                    'subscriptions_type_id' => $old->subscriptions_type_id,
                    'offers' => $offers,
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => trans('sales.ajax_error_try_again'),
            ], 500);
        }
    }


    /**
     * ✅ صفحة الاشتراكات الحالية (منفصلة)
     */
public function subscriptionsList(Request $request)
{
    // ✅ Scope يعمل تلقائياً — يرى المستخدم فروعه فقط
    $Branches = Branch::where('status', 1)->orderByDesc('id')->get();

    // هذه لا تتأثر بالـ Scope
    $Plans     = subscriptions_plan::with('type')->orderByDesc('id')->get();
    $Types     = subscriptions_type::orderByDesc('id')->get();
    $Employees = Employee::orderByDesc('id')->get();

    return view('sales.subscriptions_list', compact('Branches', 'Plans', 'Types', 'Employees'));
}


    /**
     * ✅ طباعة الفاتورة
     */
    public function invoicePrint($id)
    {
        $subscription = MemberSubscription::with([
            'member',
            'branch',
            'plan',
            'type',
            'ptAddons.trainer',
            'payments',
            'invoice',
            'offer',
            'coupon',
            'salesEmployee',
        ])->findOrFail($id);

        return view('sales.invoice_print', compact('subscription'));
    }
}
