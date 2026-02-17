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
    return view('sales.index', compact('Branches','Members','Plans','Types','Coaches','Employees'));
}

public function ajaxCurrentSubscriptionsTable(Request $request)
{
    $draw   = (int)$request->get('draw', 1);
    $start  = max(0, (int)$request->get('start', 0));
    $length = (int)$request->get('length', 10);
    $length = $length <= 0 ? 10 : min($length, 200);

    // Filters
    $q = trim((string)$request->get('q', ''));
    $branchId = $request->filled('branch_id') ? (int)$request->branch_id : null;
    $status   = $request->filled('status') ? (string)$request->status : null;
    $planId   = $request->filled('subscriptions_plan_id') ? (int)$request->subscriptions_plan_id : null;
    $typeId   = $request->filled('subscriptions_type_id') ? (int)$request->subscriptions_type_id : null;
    $source   = $request->filled('source') ? (string)$request->source : null;
    $salesEmployeeId = $request->filled('sales_employee_id') ? (int)$request->sales_employee_id : null;
    $hasPt = $request->filled('has_pt_addons') ? (int)$request->has_pt_addons : null; // 1/0

    $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
    $dateTo   = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : null;

    $baseQuery = MemberSubscription::query()
        ->with(['member','branch','plan','type'])
        ->withCount('ptAddons')
        ->withSum('ptAddons as pt_sessions_count_sum', 'sessions_count')
        ->withSum('ptAddons as pt_sessions_remaining_sum', 'sessions_remaining');

    $recordsTotal = (clone $baseQuery)->count();

    $filteredQuery = (clone $baseQuery)
        ->when($q !== '', function ($query) use ($q) {
            $query->whereHas('member', function ($m) use ($q) {
                $m->where('member_code', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%");
            });
        })
        ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
        ->when($status, fn($query) => $query->where('status', $status))
        ->when($planId, fn($query) => $query->where('subscriptions_plan_id', $planId))
        ->when($typeId, fn($query) => $query->where('subscriptions_type_id', $typeId))
        ->when($source, fn($query) => $query->where('source', $source))
        ->when($salesEmployeeId, fn($query) => $query->where('sales_employee_id', $salesEmployeeId))
        ->when($dateFrom, fn($query) => $query->where('start_date', '>=', $dateFrom->format('Y-m-d')))
        ->when($dateTo, fn($query) => $query->where('start_date', '<=', $dateTo->format('Y-m-d')))
        ->when($hasPt !== null, function ($query) use ($hasPt) {
            if ($hasPt === 1) $query->whereHas('ptAddons');
            if ($hasPt === 0) $query->whereDoesntHave('ptAddons');
        });

    $recordsFiltered = (clone $filteredQuery)->count();

    // Ordering (اختياري)
    $orderDir = strtolower((string)$request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
    $orderCol = (int)$request->input('order.0.column', 9);

    // خريطة أعمدة الداتاتيبل -> أعمدة DB (اللي نقدر نعمل عليها orderBy)
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

    $data = [];
    $rowNum = $start + 1;

    foreach ($rows as $row) {
        $baseIncluded  = (int)($row->sessions_included ?? $row->sessions_count ?? 0);
        $baseRemaining = (int)($row->sessions_remaining ?? 0);

        $ptCount = (int)($row->pt_addons_count ?? 0);
        $ptTotal = (int)($row->pt_sessions_count_sum ?? 0);

        // withSum قد يرجع null أو string، لذلك نطبّعها لرقم
        $ptRemainingRaw = $row->pt_sessions_remaining_sum;
        $ptRemaining = (int)($ptRemainingRaw ?? 0);

        $statusKey = 'sales.status_' . ($row->status ?? '');
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
        $src = (string)($row->source ?? '');
        $sourceKey = $sourceMap[$src] ?? null;
        $sourceText = $sourceKey ? trans($sourceKey) : ($src ?: '-');
        if ($sourceKey && $sourceText === $sourceKey) $sourceText = ($src ?: '-');

        $memberCode = $row->member?->member_code ?? $row->member_id;
        $memberName = $row->member?->full_name ?? trim(($row->member?->first_name ?? '').' '.($row->member?->last_name ?? ''));

        // ====== زر إضافة PT بالشرط المطلوب ======
        // الشرط:
        // 1) الاشتراك Active
        // 2) لا يوجد PT Add-ons OR يوجد PT Add-ons لكن المتبقي = 0 في PT Add-ons (وليس الاشتراك الأساسي)
        $canAddPt = ((string)($row->status ?? '') === 'active')
            && (
                $ptCount === 0
                || $ptRemaining === 0
            );

        $showBtn = '<button type="button" class="btn btn-sm btn-outline-primary js-subscription-show" data-id="'.$row->id.'">'.(trans('sales.view') ?? 'عرض').'</button>';

        $addPtBtn = '';
        if ($canAddPt) {
            $url = route('sales.subscriptions.pt_addons.create', $row->id);
            $addPtBtn = ' <a href="'.$url.'" target="_blank" class="btn btn-sm btn-outline-success">'.(trans('sales.add_pt') ?? 'إضافة PT').'</a>';
        }

        $data[] = [
            'rownum' => $rowNum++,
            'member' => view('sales.partials._dt_member_cell', compact('memberCode','memberName'))->render(),
            'plan'   => $row->plan?->getTranslation('name','ar') ?? '-',
            'branch' => $row->branch?->getTranslation('name','ar') ?? '-',
            'base_sessions' => view('sales.partials._dt_sessions_cell', compact('baseRemaining','baseIncluded'))->render(),
            'pt' => ($ptCount > 0)
                ? '<span class="badge bg-success">'.(trans('sales.yes') ?? 'نعم').' ('.$ptRemaining.'/'.$ptTotal.')</span>'
                : '<span class="badge bg-light text-dark">'.(trans('sales.no') ?? 'لا').'</span>',
            'source' => e($sourceText),
            'total'  => number_format((float)$row->total_amount, 2),
            'status' => '<span class="badge bg-secondary">'.e($statusText).'</span>',
            'created_at' => (string)$row->created_at,
            'actions' => $showBtn . $addPtBtn,
        ];
    }

    return response()->json([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
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

            if (!$offerData) {
                $bestOfferData = $this->offerEngine->getBestOffer($offerContext);
                if ($bestOfferData) {
                    $offerData = [
                        'offer_id' => (int)$bestOfferData['offer_id'],
                        'offer' => $bestOfferData['offer'],
                        'discount_amount' => (float)$bestOfferData['discount_amount'],
                        'amount_before' => (float)$bestOfferData['amount_before'],
                        'amount_after' => (float)$bestOfferData['amount_after'],
                    ];
                }
            }

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

            if (!$offerChosenId) {
                $best = $this->offerEngine->getBestOffer($offerContext);
                if ($best) {
                    $offerChosenId = (int)$best['offer_id'];
                    $offerDiscount = (float)$best['discount_amount'];
                    $offerName = $best['offer']['name'] ?? null;
                }
            }

            $amountAfterOffer = max(0, $grossAmount - (float)$offerDiscount);

            // validate coupon on amountAfterOffer
            $couponContext = [
                'code'                 => $couponCode,
                'applies_to'           => 'subscription',
                'subscriptions_plan_id'=> $planId,
                'subscriptions_type_id'=> $typeId,
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

        if (!$chosenOfferId) {
            $bestOfferData = $this->offerEngine->getBestOffer($offerContext);
            if ($bestOfferData) {
                $chosenOfferId = (int)$bestOfferData['offer_id'];
                $offerDiscount = (float)$bestOfferData['discount_amount'];
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
        'member','branch','plan','type',
        'ptAddons.trainer','payments','invoice','offer','coupon','salesEmployee',
    ])->findOrFail($id);

    $html = view('sales.partials.subscription_details', [
        'subscription' => $subscription,
        'inModal' => true,
    ])->render();

    return response()->json([
        'ok' => true,
        'html' => $html,
    ]);
}


    public function show($id)
    {
        $subscription = MemberSubscription::with([
            'member','branch','plan','type',
            'ptAddons.trainer','payments','invoice','offer','coupon','salesEmployee',
        ])->findOrFail($id);

        return view('sales.show', compact('subscription'));
    }
}
