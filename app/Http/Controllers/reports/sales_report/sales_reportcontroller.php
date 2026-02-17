<?php

namespace App\Http\Controllers\reports\sales_report;

use App\Http\Controllers\Controller;
use App\Models\general\Branch;
use App\Models\general\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class sales_reportcontroller extends Controller
{
    public function index(Request $request)
    {
        $action = (string)$request->get('action', '');

        if (!$request->ajax() && $action === 'print') {
            return $this->print($request);
        }

        if (!$request->ajax() && $action === 'export_excel') {
            return $this->exportExcel($request);
        }

        if ($request->ajax()) {
            if ($action === 'metrics') {
                return response()->json($this->computeKpis($request));
            }

            if ($action === 'group') {
                return response()->json($this->groupSummary($request));
            }

            return $this->datatable($request);
        }

        $branches = Branch::query()
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        $types = DB::table('subscriptions_types as st')
            ->whereNull('st.deleted_at')
            ->select('st.id', 'st.name', 'st.status')
            ->orderByDesc('st.id')
            ->get();

        $plans = DB::table('subscriptions_plans as sp')
            ->whereNull('sp.deleted_at')
            ->select('sp.id', 'sp.code', 'sp.name', 'sp.status')
            ->orderByDesc('sp.id')
            ->get();

        $salesEmployees = DB::table('employees as e')
            ->whereNull('e.deleted_at')
            ->select([
                'e.id',
                DB::raw("TRIM(CONCAT(COALESCE(e.first_name,''),' ',COALESCE(e.last_name,''))) as full_name"),
                'e.code',
            ])
            ->orderBy('full_name')
            ->get();

        $sources = DB::table('member_subscriptions as ms')
            ->whereNull('ms.deleted_at')
            ->whereNotNull('ms.source')
            ->where('ms.source', '!=', '')
            ->distinct()
            ->orderBy('ms.source')
            ->pluck('ms.source')
            ->toArray();

        $kpis = [
            'subs_count' => 0,
            'total_sales' => 0,
            'avg_sale' => 0,
            'total_discount' => 0,
            'offer_discount' => 0,
            'coupon_discount' => 0,
            'pt_addons_sales' => 0,
            'offers_used_count' => 0,
            'coupons_used_count' => 0,
        ];

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'branch_ids' => (array)$request->get('branch_ids', []),

            'status' => $request->get('status'),
            'type_id' => $request->get('type_id'),
            'plan_id' => $request->get('plan_id'),
            'source' => $request->get('source'),
            'sales_employee_id' => $request->get('sales_employee_id'),

            'has_offer' => $request->get('has_offer'),
            'has_coupon' => $request->get('has_coupon'),

            'amount_from' => $request->get('amount_from'),
            'amount_to' => $request->get('amount_to'),

            'discount_from' => $request->get('discount_from'),
            'discount_to' => $request->get('discount_to'),

            // NEW: member search (name/code)
            'member_q' => $request->get('member_q'),

            'group_by' => $request->get('group_by', 'branch'),
        ];

        $filterOptions = [
            'statuses' => $this->subscriptionStatusOptions(),
            'yes_no' => $this->yesNoOptions(),
            'group_by' => $this->groupByOptions(),
            'sources' => $sources,
        ];

        return view('reports.sales_report.index', [
            'branches' => $branches,
            'types' => $types,
            'plans' => $plans,
            'salesEmployees' => $salesEmployees,

            'kpis' => $kpis,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ]);
    }

    private function datatable(Request $request)
    {
        $draw   = (int)$request->input('draw', 1);
        $start  = (int)$request->input('start', 0);
        $length = (int)$request->input('length', 25);

        $search = trim((string)data_get($request->input('search', []), 'value', ''));

        $baseQuery = $this->buildDetailQuery($request, false);
        $recordsTotal = (clone $baseQuery)->count('ms.id');

        $filteredQuery = $this->buildDetailQuery($request, true, $search);
        $recordsFiltered = (clone $filteredQuery)->count('ms.id');

        $orderColIndex = (int)data_get($request->input('order', []), '0.column', 0);
        $orderDir = strtolower((string)data_get($request->input('order', []), '0.dir', 'desc'));
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';

        $columnsMap = [
            0 => 'ms.created_at',      // dates
            1 => 'b.name',             // branch
            2 => DB::raw("member_name"), // member
            3 => 'ms.plan_name',       // plan
            4 => 'ms.status',          // status
            5 => 'ms.source',          // source
            6 => 'ms.total_discount',  // discounts
            7 => 'ms.total_amount',    // amounts
            8 => DB::raw("offer_name"),// offer/coupon
            9 => DB::raw("sales_employee_name"),
            10 => 'ms.id',
        ];

        $orderBy = $columnsMap[$orderColIndex] ?? 'ms.created_at';

        $rows = $filteredQuery
            ->orderBy($orderBy, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $locale = app()->getLocale();

        $data = [];
        foreach ($rows as $idx => $r) {
            $branchName = $this->nameJsonOrText($r->branch_name ?? null, $locale) ?: '-';
            $planName = $this->nameJsonOrText($r->plan_name ?? null, $locale) ?: '-';
            $typeName = $this->nameJsonOrText($r->type_name ?? null, $locale) ?: '-';

            $memberName = (string)($r->member_name ?? '');
            $memberCode = (string)($r->member_code ?? '');

            $employeeName = $r->sales_employee_name ?: '-';

            $offerName = $this->nameJsonOrText($r->offer_name ?? null, $locale) ?: '';
            $couponName = $this->nameJsonOrText($r->coupon_name ?? null, $locale) ?: '';
            $couponCode = (string)($r->coupon_code ?? '');

            $data[] = [
                'rownum' => $start + $idx + 1,

                'date_block' => $this->buildDateBlock($r->created_at ?? null, $r->start_date ?? null, $r->end_date ?? null),
                'branch' => e($branchName),
                'member' => $this->buildMemberBlock($r->member_id ?? null, $memberCode, $memberName),
                'plan' => $this->buildPlanBlock($r->plan_code ?? null, $planName, $typeName),
                'status' => $this->buildSubscriptionStatusBadge($r->status ?? null),
                'source' => e($r->source ?? '-'),

                'discounts' => $this->buildDiscountBlock(
                    $r->discount_offer_amount ?? 0,
                    $r->discount_coupon_amount ?? 0,
                    $r->total_discount ?? 0
                ),

                'amounts' => $this->buildAmountsBlock(
                    $r->price_plan ?? 0,
                    $r->price_pt_addons ?? 0,
                    $r->total_amount ?? 0
                ),

                'offers_coupons' => $this->buildOfferCouponBlock(
                    $r->offer_id ?? null,
                    $offerName,
                    $r->coupon_id ?? null,
                    $couponName,
                    $couponCode
                ),

                'sales_employee' => e($employeeName),
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => (int)$recordsTotal,
            'recordsFiltered' => (int)$recordsFiltered,
            'data' => $data,
        ]);
    }

    private function buildDetailQuery(Request $request, bool $applySearch = false, string $search = '')
    {
        $salesEmployeeNameExpr = "TRIM(CONCAT(COALESCE(se.first_name,''),' ',COALESCE(se.last_name,'')))";
        $memberNameExpr = "TRIM(CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,'')))";

        $q = DB::table('member_subscriptions as ms')
            ->whereNull('ms.deleted_at')
            ->leftJoin('branches as b', function ($j) {
                $j->on('b.id', '=', 'ms.branch_id')->whereNull('b.deleted_at');
            })
            ->leftJoin('subscriptions_types as st', function ($j) {
                $j->on('st.id', '=', 'ms.subscriptions_type_id')->whereNull('st.deleted_at');
            })
            ->leftJoin('employees as se', function ($j) {
                $j->on('se.id', '=', 'ms.sales_employee_id')->whereNull('se.deleted_at');
            })
            // NEW: join members
            ->leftJoin('members as m', function ($j) {
                $j->on('m.id', '=', 'ms.member_id')->whereNull('m.deleted_at');
            })
            // NEW: join offer/coupon to show names
            ->leftJoin('offers as ofr', function ($j) {
                $j->on('ofr.id', '=', 'ms.offer_id')->whereNull('ofr.deleted_at');
            })
            ->leftJoin('coupons as cp', function ($j) {
                $j->on('cp.id', '=', 'ms.coupon_id')->whereNull('cp.deleted_at');
            })
            ->select([
                'ms.id',
                'ms.member_id',
                'ms.branch_id',
                'ms.subscriptions_plan_id',
                'ms.subscriptions_type_id',

                'ms.plan_code',
                'ms.plan_name',

                'ms.duration_days',
                'ms.sessions_count',
                'ms.sessions_included',
                'ms.sessions_remaining',

                'ms.start_date',
                'ms.end_date',
                'ms.status',
                'ms.allow_all_branches',
                'ms.source',

                'ms.price_plan',
                'ms.price_pt_addons',
                'ms.discount_offer_amount',
                'ms.discount_coupon_amount',
                'ms.total_discount',
                'ms.total_amount',

                'ms.offer_id',
                'ms.coupon_id',

                'ms.sales_employee_id',
                'ms.commission_base_amount',
                'ms.commission_value_type',
                'ms.commission_value',
                'ms.commission_amount',
                'ms.commission_is_paid',

                'ms.created_at',

                'b.name as branch_name',
                'st.name as type_name',

                DB::raw("$salesEmployeeNameExpr as sales_employee_name"),

                // member fields
                'm.member_code as member_code',
                DB::raw("$memberNameExpr as member_name"),

                // offer/coupon names
                'ofr.name as offer_name',
                'cp.name as coupon_name',
                'cp.code as coupon_code',
            ]);

        $this->applyFilters($q, $request, $memberNameExpr);

        if ($applySearch && $search !== '') {
            $this->applySearch($q, $search, $salesEmployeeNameExpr, $memberNameExpr);
        }

        return $q;
    }

    private function applyFilters($q, Request $request, string $memberNameExpr): void
    {
        $branchIds = array_values(array_filter(array_map('intval', (array)$request->get('branch_ids', []))));
        if (!empty($branchIds)) {
            $q->whereIn('ms.branch_id', $branchIds);
        }

        if ($request->filled('date_from')) {
            $q->whereDate('ms.created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('ms.created_at', '<=', $request->get('date_to'));
        }

        if ($request->filled('status')) {
            $q->where('ms.status', (string)$request->get('status'));
        }

        if ($request->filled('type_id')) {
            $q->where('ms.subscriptions_type_id', (int)$request->get('type_id'));
        }

        if ($request->filled('plan_id')) {
            $q->where('ms.subscriptions_plan_id', (int)$request->get('plan_id'));
        }

        if ($request->filled('source')) {
            $q->where('ms.source', (string)$request->get('source'));
        }

        if ($request->filled('sales_employee_id')) {
            $q->where('ms.sales_employee_id', (int)$request->get('sales_employee_id'));
        }

        if ($request->filled('has_offer') && in_array((string)$request->get('has_offer'), ['0', '1'], true)) {
            if ((string)$request->get('has_offer') === '1') {
                $q->whereNotNull('ms.offer_id');
            } else {
                $q->whereNull('ms.offer_id');
            }
        }

        if ($request->filled('has_coupon') && in_array((string)$request->get('has_coupon'), ['0', '1'], true)) {
            if ((string)$request->get('has_coupon') === '1') {
                $q->whereNotNull('ms.coupon_id');
            } else {
                $q->whereNull('ms.coupon_id');
            }
        }

        if ($request->filled('amount_from')) {
            $q->where('ms.total_amount', '>=', (float)$request->get('amount_from'));
        }
        if ($request->filled('amount_to')) {
            $q->where('ms.total_amount', '<=', (float)$request->get('amount_to'));
        }

        if ($request->filled('discount_from')) {
            $q->where('ms.total_discount', '>=', (float)$request->get('discount_from'));
        }
        if ($request->filled('discount_to')) {
            $q->where('ms.total_discount', '<=', (float)$request->get('discount_to'));
        }

        // NEW: member_q filter
        if ($request->filled('member_q')) {
            $mq = trim((string)$request->get('member_q'));
            if ($mq !== '') {
                $like = '%' . $mq . '%';
                $q->where(function ($w) use ($like, $memberNameExpr) {
                    $w->orWhere('m.member_code', 'like', $like)
                      ->orWhere(DB::raw("COALESCE($memberNameExpr,'')"), 'like', $like);
                });
            }
        }
    }

    private function applySearch($q, string $search, string $salesEmployeeNameExpr, string $memberNameExpr): void
    {
        $s = trim($search);
        $like = '%' . $s . '%';

        $q->where(function ($w) use ($s, $like, $salesEmployeeNameExpr, $memberNameExpr) {
            if (is_numeric($s)) {
                $w->orWhere('ms.id', (int)$s)
                  ->orWhere('ms.member_id', (int)$s)
                  ->orWhere('ms.branch_id', (int)$s);
            }

            $w->orWhere('ms.plan_code', 'like', $like)
              ->orWhere('ms.plan_name', 'like', $like)
              ->orWhere('ms.source', 'like', $like)
              ->orWhere('ms.status', 'like', $like)
              ->orWhere(DB::raw("COALESCE(b.name,'')"), 'like', $like)
              ->orWhere(DB::raw("COALESCE(st.name,'')"), 'like', $like)
              ->orWhere(DB::raw("COALESCE($salesEmployeeNameExpr,'')"), 'like', $like)

              // NEW: member search in global search too
              ->orWhere('m.member_code', 'like', $like)
              ->orWhere(DB::raw("COALESCE($memberNameExpr,'')"), 'like', $like);
        });
    }

    private function computeKpis(Request $request): array
    {
        $q = $this->buildDetailQuery($request, false);

        $subsCount = (int)(clone $q)->count('ms.id');
        $totalSales = (float)((clone $q)->sum('ms.total_amount') ?? 0);
        $avgSale = $subsCount > 0 ? round($totalSales / $subsCount, 2) : 0;

        $totalDiscount = (float)((clone $q)->sum('ms.total_discount') ?? 0);
        $offerDiscount = (float)((clone $q)->sum('ms.discount_offer_amount') ?? 0);
        $couponDiscount = (float)((clone $q)->sum('ms.discount_coupon_amount') ?? 0);

        $ptAddonsSales = (float)((clone $q)->sum('ms.price_pt_addons') ?? 0);

        $offersUsedCount = (int)(clone $q)->whereNotNull('ms.offer_id')->count('ms.id');
        $couponsUsedCount = (int)(clone $q)->whereNotNull('ms.coupon_id')->count('ms.id');

        return [
            'subs_count' => $subsCount,
            'total_sales' => round($totalSales, 2),
            'avg_sale' => $avgSale,
            'total_discount' => round($totalDiscount, 2),
            'offer_discount' => round($offerDiscount, 2),
            'coupon_discount' => round($couponDiscount, 2),
            'pt_addons_sales' => round($ptAddonsSales, 2),
            'offers_used_count' => $offersUsedCount,
            'coupons_used_count' => $couponsUsedCount,
        ];
    }

    private function groupSummary(Request $request): array
    {
        $groupBy = (string)$request->get('group_by', 'branch');
        $allowed = array_keys($this->groupByOptions());
        if (!in_array($groupBy, $allowed, true)) {
            $groupBy = 'branch';
        }

        $q = DB::table('member_subscriptions as ms')
            ->whereNull('ms.deleted_at');

        // Note: member_q doesn't affect group by in this query unless we join members.
        // To keep consistent, we will join members only when member_q is used.
        if ($request->filled('member_q')) {
            $q->leftJoin('members as m', function ($j) {
                $j->on('m.id', '=', 'ms.member_id')->whereNull('m.deleted_at');
            });
        }

        $memberNameExpr = "TRIM(CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,'')))";
        $this->applyFilters($q, $request, $memberNameExpr);

        if ($groupBy === 'branch') {
            $q->leftJoin('branches as b', function ($j) {
                $j->on('b.id', '=', 'ms.branch_id')->whereNull('b.deleted_at');
            });

            $q->select([
                'ms.branch_id as group_id',
                'b.name as group_name',
                DB::raw("COUNT(ms.id) as subs_count"),
                DB::raw("SUM(ms.total_amount) as total_sales"),
                DB::raw("SUM(ms.total_discount) as total_discount"),
                DB::raw("SUM(ms.price_pt_addons) as pt_addons_sales"),
            ])->groupBy('ms.branch_id', 'b.name');
        } elseif ($groupBy === 'type') {
            $q->leftJoin('subscriptions_types as st', function ($j) {
                $j->on('st.id', '=', 'ms.subscriptions_type_id')->whereNull('st.deleted_at');
            });

            $q->select([
                'ms.subscriptions_type_id as group_id',
                'st.name as group_name',
                DB::raw("COUNT(ms.id) as subs_count"),
                DB::raw("SUM(ms.total_amount) as total_sales"),
                DB::raw("SUM(ms.total_discount) as total_discount"),
                DB::raw("SUM(ms.price_pt_addons) as pt_addons_sales"),
            ])->groupBy('ms.subscriptions_type_id', 'st.name');
        } elseif ($groupBy === 'plan') {
            $q->select([
                'ms.subscriptions_plan_id as group_id',
                'ms.plan_name as group_name',
                DB::raw("COUNT(ms.id) as subs_count"),
                DB::raw("SUM(ms.total_amount) as total_sales"),
                DB::raw("SUM(ms.total_discount) as total_discount"),
                DB::raw("SUM(ms.price_pt_addons) as pt_addons_sales"),
            ])->groupBy('ms.subscriptions_plan_id', 'ms.plan_name');
        } elseif ($groupBy === 'source') {
            $q->select([
                DB::raw("ms.source as group_id"),
                DB::raw("ms.source as group_name"),
                DB::raw("COUNT(ms.id) as subs_count"),
                DB::raw("SUM(ms.total_amount) as total_sales"),
                DB::raw("SUM(ms.total_discount) as total_discount"),
                DB::raw("SUM(ms.price_pt_addons) as pt_addons_sales"),
            ])->groupBy('ms.source');
        } elseif ($groupBy === 'sales_employee') {
            $q->leftJoin('employees as se', function ($j) {
                $j->on('se.id', '=', 'ms.sales_employee_id')->whereNull('se.deleted_at');
            });

            $nameExpr = "TRIM(CONCAT(COALESCE(se.first_name,''),' ',COALESCE(se.last_name,'')))";

            $q->select([
                'ms.sales_employee_id as group_id',
                DB::raw("$nameExpr as group_name"),
                DB::raw("COUNT(ms.id) as subs_count"),
                DB::raw("SUM(ms.total_amount) as total_sales"),
                DB::raw("SUM(ms.total_discount) as total_discount"),
                DB::raw("SUM(ms.price_pt_addons) as pt_addons_sales"),
            ])->groupBy('ms.sales_employee_id', DB::raw($nameExpr));
        } else { // status
            $q->select([
                DB::raw("ms.status as group_id"),
                DB::raw("ms.status as group_name"),
                DB::raw("COUNT(ms.id) as subs_count"),
                DB::raw("SUM(ms.total_amount) as total_sales"),
                DB::raw("SUM(ms.total_discount) as total_discount"),
                DB::raw("SUM(ms.price_pt_addons) as pt_addons_sales"),
            ])->groupBy('ms.status');
        }

        $rows = $q->orderByDesc(DB::raw('total_sales'))->limit(200)->get();

        $locale = app()->getLocale();
        $out = [];

        foreach ($rows as $r) {
            $name = $r->group_name;

            if (is_string($name) || is_array($name)) {
                $name = $this->nameJsonOrText($name, $locale);
            }

            $out[] = [
                'group_id' => $r->group_id,
                'group_name' => $name ?: '-',
                'subs_count' => (int)($r->subs_count ?? 0),
                'total_sales' => round((float)($r->total_sales ?? 0), 2),
                'total_discount' => round((float)($r->total_discount ?? 0), 2),
                'pt_addons_sales' => round((float)($r->pt_addons_sales ?? 0), 2),
            ];
        }

        return [
            'group_by' => $groupBy,
            'rows' => $out,
        ];
    }

    private function print(Request $request)
    {
        $rows = $this->buildDetailQuery($request, false)
            ->orderBy('ms.created_at', 'desc')
            ->limit(5000)
            ->get();

        $kpis = $this->computeKpis($request);
        $group = $this->groupSummary($request);

        $settings = GeneralSetting::query()->where('status', 1)->first();

        $orgName = '-';
        if ($settings) {
            if (method_exists($settings, 'getTranslation')) {
                $orgName = $settings->getTranslation('name', app()->getLocale())
                    ?: ($settings->getTranslation('name', 'ar') ?: $settings->getTranslation('name', 'en'));
            } else {
                $orgName = $settings->name ?? '-';
            }
        }

        $chips = $this->buildFilterChips($request);

        $meta = [
            'title' => __('reports.sales_report_title'),
            'org_name' => $orgName,
            'generated_at' => now('Africa/Cairo')->format('Y-m-d H:i'),
            'total_count' => $rows->count(),
        ];

        return view('reports.sales_report.print', [
            'meta' => $meta,
            'chips' => $chips,
            'kpis' => $kpis,
            'group' => $group,
            'rows' => $rows,
        ]);
    }

    private function exportExcel(Request $request)
    {
        $rows = $this->buildDetailQuery($request, false)
            ->orderBy('ms.created_at', 'desc')
            ->limit(50000)
            ->get();

        $locale = app()->getLocale();
        $isRtl = ($locale === 'ar');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($isRtl) $sheet->setRightToLeft(true);

        $headers = [
            __('reports.sales_col_sale_date'),
            __('reports.sales_col_branch'),
            __('reports.sales_col_member'),
            __('reports.sales_col_plan'),
            __('reports.sales_col_type'),
            __('reports.sales_col_status'),
            __('reports.sales_col_source'),
            __('reports.sales_col_offer'),
            __('reports.sales_col_coupon'),
            __('reports.sales_col_price_plan'),
            __('reports.sales_col_price_pt_addons'),
            __('reports.sales_col_total_discount'),
            __('reports.sales_col_total_amount'),
            __('reports.sales_col_sales_employee'),
        ];

        $sheet->fromArray($headers, null, 'A1');

        $headerRange = 'A1:' . $sheet->getHighestColumn() . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        $sheet->freezePane('A2');

        $rowNum = 2;
        foreach ($rows as $r) {
            $branchName = $this->nameJsonOrText($r->branch_name ?? null, $locale) ?: '-';
            $planName = $this->nameJsonOrText($r->plan_name ?? null, $locale) ?: '-';
            $typeName = $this->nameJsonOrText($r->type_name ?? null, $locale) ?: '-';

            $memberName = trim((string)($r->member_name ?? ''));
            $memberCode = trim((string)($r->member_code ?? ''));
            $memberCell = ($memberCode ? $memberCode . ' - ' : '') . ($memberName ?: ('#' . (string)($r->member_id ?? '-')));

            $offerName = $this->nameJsonOrText($r->offer_name ?? null, $locale) ?: '';
            $couponName = $this->nameJsonOrText($r->coupon_name ?? null, $locale) ?: '';
            $couponCode = (string)($r->coupon_code ?? '');

            $sheet->fromArray([
                $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i') : '-',
                $branchName,
                $memberCell,
                ($r->plan_code ? ($r->plan_code . ' - ') : '') . $planName,
                $typeName,
                (string)($r->status ?? '-'),
                (string)($r->source ?? '-'),
                $offerName ?: '-',
                ($couponName ? $couponName : '-') . ($couponCode ? (' (' . $couponCode . ')') : ''),
                (float)($r->price_plan ?? 0),
                (float)($r->price_pt_addons ?? 0),
                (float)($r->total_discount ?? 0),
                (float)($r->total_amount ?? 0),
                (string)($r->sales_employee_name ?? '-'),
            ], null, 'A' . $rowNum);

            $rowNum++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $dataRange = 'A1:' . $sheet->getHighestColumn() . ($rowNum - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $fileName = 'sales_report_' . now('Africa/Cairo')->format('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ================= UI blocks =================

    private function buildDateBlock($createdAt, $startDate, $endDate): string
    {
        $created = $createdAt ? Carbon::parse($createdAt)->format('Y-m-d H:i') : '-';
        $start = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : '-';
        $end = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e(__('reports.sales_col_sale_date') . ': ' . $created) . '</span>' .
            '<small class="text-muted">' . e(__('reports.sales_col_start_end') . ': ' . $start . ' ⟶ ' . $end) . '</small>' .
            '</div>';
    }

    private function buildMemberBlock($memberId, string $memberCode = '', string $memberName = ''): string
    {
        $id = $memberId ? (string)$memberId : '-';
        $mc = trim($memberCode);
        $mn = trim($memberName);

        $line1 = ($mn !== '') ? $mn : ('#' . $id);
        $line2 = [];

        if ($mc !== '') $line2[] = (__('reports.member_code') ?? 'كود') . ': ' . $mc;
        if ($memberId) $line2[] = 'ID: #' . $id;

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($line1) . '</span>' .
            '<small class="text-muted">' . e(implode(' | ', $line2) ?: '-') . '</small>' .
            '</div>';
    }

    private function buildPlanBlock($planCode, $planName, $typeName): string
    {
        $pc = $planCode ?: '-';
        $pn = $planName ?: '-';
        $tn = $typeName ?: '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($pn) . '</span>' .
            '<small class="text-muted">' . e(__('reports.sales_col_plan_code') . ': ' . $pc) . '</small>' .
            '<small class="text-muted">' . e(__('reports.sales_col_type') . ': ' . $tn) . '</small>' .
            '</div>';
    }

    private function buildSubscriptionStatusBadge($status): string
    {
        $s = strtolower(trim((string)$status));
        $label = $s !== '' ? $s : '-';

        $map = [
            'active' => ['success', __('reports.sub_status_active')],
            'expired' => ['secondary', __('reports.sub_status_expired')],
            'frozen' => ['warning', __('reports.sub_status_frozen')],
            'cancelled' => ['danger', __('reports.sub_status_cancelled')],
            'pending' => ['info', __('reports.sub_status_pending')],
        ];

        if (isset($map[$s])) {
            return '<span class="badge bg-' . e($map[$s][0]) . '">' . e($map[$s][1]) . '</span>';
        }

        return '<span class="badge bg-dark">' . e($label) . '</span>';
    }

    private function buildDiscountBlock($offer, $coupon, $total): string
    {
        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e(__('reports.sales_col_total_discount') . ': ' . (float)$total) . '</span>' .
            '<small class="text-muted">' . e(__('reports.sales_col_offer_discount') . ': ' . (float)$offer) . '</small>' .
            '<small class="text-muted">' . e(__('reports.sales_col_coupon_discount') . ': ' . (float)$coupon) . '</small>' .
            '</div>';
    }

    private function buildAmountsBlock($pricePlan, $pt, $total): string
    {
        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e(__('reports.sales_col_total_amount') . ': ' . (float)$total) . '</span>' .
            '<small class="text-muted">' . e(__('reports.sales_col_price_plan') . ': ' . (float)$pricePlan) . '</small>' .
            '<small class="text-muted">' . e(__('reports.sales_col_price_pt_addons') . ': ' . (float)$pt) . '</small>' .
            '</div>';
    }

    private function buildOfferCouponBlock($offerId, string $offerName = '', $couponId = null, string $couponName = '', string $couponCode = ''): string
    {
        $offerTxt = '-';
        if ($offerId) {
            $offerTxt = trim($offerName) !== '' ? $offerName : ('#' . (string)$offerId);
        }

        $couponTxt = '-';
        if ($couponId) {
            $couponTxt = trim($couponName) !== '' ? $couponName : ('#' . (string)$couponId);
            if (trim($couponCode) !== '') {
                $couponTxt .= ' (' . $couponCode . ')';
            }
        }

        return '<div class="d-flex flex-column">' .
            '<small class="text-muted">' . e(__('reports.sales_col_offer') . ': ' . $offerTxt) . '</small>' .
            '<small class="text-muted">' . e(__('reports.sales_col_coupon') . ': ' . $couponTxt) . '</small>' .
            '</div>';
    }

    // ================= Filters chips =================

    private function buildFilterChips(Request $request): array
    {
        $chips = [];

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $chips[] = __('reports.sales_filter_date') . ': ' . ($request->get('date_from') ?: '---') . ' ⟶ ' . ($request->get('date_to') ?: '---');
        }

        $branchIds = array_values(array_filter(array_map('intval', (array)$request->get('branch_ids', []))));
        if (!empty($branchIds)) {
            $branchNames = Branch::query()
                ->whereIn('id', $branchIds)
                ->get()
                ->map(function ($b) {
                    return method_exists($b, 'getTranslation') ? $b->getTranslation('name', app()->getLocale()) : ($b->name ?? '');
                })
                ->filter()
                ->values()
                ->implode('، ');
            $chips[] = __('reports.sales_filter_branches') . ': ' . ($branchNames ?: '---');
        }

        foreach ([
            'status' => 'sales_filter_status',
            'source' => 'sales_filter_source',
            'sales_employee_id' => 'sales_filter_sales_employee',
            'member_q' => 'sales_filter_member_q',
        ] as $key => $labelKey) {
            if ($request->filled($key)) {
                $chips[] = __('reports.' . $labelKey) . ': ' . $request->get($key);
            }
        }

        if ($request->filled('type_id')) {
            $chips[] = __('reports.sales_filter_type') . ': ' . $request->get('type_id');
        }

        if ($request->filled('plan_id')) {
            $chips[] = __('reports.sales_filter_plan') . ': ' . $request->get('plan_id');
        }

        if ($request->filled('has_offer')) {
            $chips[] = __('reports.sales_filter_has_offer') . ': ' . $this->translateYesNo($request->get('has_offer'));
        }

        if ($request->filled('has_coupon')) {
            $chips[] = __('reports.sales_filter_has_coupon') . ': ' . $this->translateYesNo($request->get('has_coupon'));
        }

        if ($request->filled('amount_from') || $request->filled('amount_to')) {
            $chips[] = __('reports.sales_filter_amount') . ': ' . ($request->get('amount_from') ?: '---') . ' ⟶ ' . ($request->get('amount_to') ?: '---');
        }

        if ($request->filled('discount_from') || $request->filled('discount_to')) {
            $chips[] = __('reports.sales_filter_discount') . ': ' . ($request->get('discount_from') ?: '---') . ' ⟶ ' . ($request->get('discount_to') ?: '---');
        }

        if ($request->filled('group_by')) {
            $chips[] = __('reports.sales_filter_group_by') . ': ' . $request->get('group_by');
        }

        return $chips;
    }

    // ================= Options / translations =================

    private function translateYesNo($v): string
    {
        $isAr = app()->getLocale() === 'ar';
        if ((string)$v === '1') return $this->trFallback('reports.sub_yes', $isAr ? 'نعم' : 'Yes');
        if ((string)$v === '0') return $this->trFallback('reports.sub_no', $isAr ? 'لا' : 'No');
        return '-';
    }

    private function yesNoOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.sub_all')],
            ['value' => '1', 'label' => $this->translateYesNo(1)],
            ['value' => '0', 'label' => $this->translateYesNo(0)],
        ];
    }

    private function subscriptionStatusOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.sub_all')],
            ['value' => 'active', 'label' => __('reports.sub_status_active')],
            ['value' => 'expired', 'label' => __('reports.sub_status_expired')],
            ['value' => 'frozen', 'label' => __('reports.sub_status_frozen')],
            ['value' => 'cancelled', 'label' => __('reports.sub_status_cancelled')],
            ['value' => 'pending', 'label' => __('reports.sub_status_pending')],
        ];
    }

    private function groupByOptions(): array
    {
        return [
            'branch' => __('reports.sales_group_branch'),
            'type' => __('reports.sales_group_type'),
            'plan' => __('reports.sales_group_plan'),
            'source' => __('reports.sales_group_source'),
            'sales_employee' => __('reports.sales_group_sales_employee'),
            'status' => __('reports.sales_group_status'),
        ];
    }

    private function trFallback(string $key, string $fallback): string
    {
        $t = __($key);
        return ($t === $key) ? $fallback : $t;
    }

    private function nameJsonOrText($nameJsonOrText, string $locale): string
    {
        if ($nameJsonOrText === null) return '';

        if (is_array($nameJsonOrText)) {
            return (string)($nameJsonOrText[$locale] ?? $nameJsonOrText['ar'] ?? $nameJsonOrText['en'] ?? reset($nameJsonOrText) ?? '');
        }

        $v = (string)$nameJsonOrText;

        for ($i = 0; $i < 2; $i++) {
            $decoded = json_decode($v, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }

            if (is_array($decoded)) {
                return (string)($decoded[$locale] ?? $decoded['ar'] ?? $decoded['en'] ?? reset($decoded) ?? '');
            }

            if (is_string($decoded)) {
                $v = $decoded;
                continue;
            }

            break;
        }

        return trim($v);
    }
}
