<?php

namespace App\Http\Controllers\reports\commissions_report;

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

class commissions_reportcontroller extends Controller
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

        $salesEmployees = DB::table('employees as e')
            ->whereNull('e.deleted_at')
            ->select([
                'e.id',
                DB::raw("TRIM(CONCAT(COALESCE(e.first_name,''),' ',COALESCE(e.last_name,''))) as full_name"),
                'e.code',
            ])
            ->orderBy('full_name')
            ->get();

        $settlementStatuses = DB::table('commission_settlements as cs')
            ->whereNull('cs.deleted_at')
            ->whereNotNull('cs.status')
            ->where('cs.status', '!=', '')
            ->distinct()
            ->orderBy('cs.status')
            ->pluck('cs.status')
            ->toArray();

        $sources = DB::table('member_subscriptions as ms')
            ->whereNull('ms.deleted_at')
            ->whereNotNull('ms.source')
            ->where('ms.source', '!=', '')
            ->distinct()
            ->orderBy('ms.source')
            ->pluck('ms.source')
            ->toArray();

        $kpis = [
            'items_count' => 0,
            'total_commission_all' => 0,
            'total_commission_included' => 0,
            'total_commission_excluded' => 0,
            'paid_commission' => 0,
            'unpaid_commission' => 0,
            'settled_items_count' => 0,
            'unsettled_items_count' => 0,
        ];

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'branch_ids' => (array)$request->get('branch_ids', []),

            'sales_employee_id' => $request->get('sales_employee_id'),
            'commission_is_paid' => $request->get('commission_is_paid'), // 1/0
            'has_settlement' => $request->get('has_settlement'), // 1/0
            'settlement_status' => $request->get('settlement_status'),
            'is_excluded' => $request->get('is_excluded'), // 1/0

            'source' => $request->get('source'),

            'amount_from' => $request->get('amount_from'),
            'amount_to' => $request->get('amount_to'),

            'commission_from' => $request->get('commission_from'),
            'commission_to' => $request->get('commission_to'),

            'only_with_commission' => $request->get('only_with_commission', '1'),
            'group_by' => $request->get('group_by', 'sales_employee'),
        ];

        $filterOptions = [
            'yes_no' => $this->yesNoOptions(),
            'group_by' => $this->groupByOptions(),
            'settlement_statuses' => $settlementStatuses,
            'sources' => $sources,
        ];

        return view('reports.commissions_report.index', [
            'branches' => $branches,
            'salesEmployees' => $salesEmployees,
            'kpis' => $kpis,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ]);
    }

    // ===================== Datatable =====================

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
            0 => DB::raw("COALESCE(csi.subscription_created_at, ms.created_at)"),
            1 => 'b.name',
            2 => 'ms.member_id',
            3 => 'ms.id',
            4 => 'ms.total_amount',
            5 => DB::raw("COALESCE(csi.commission_base_amount, ms.commission_base_amount)"),
            6 => DB::raw("COALESCE(csi.commission_value_type, ms.commission_value_type)"),
            7 => DB::raw("COALESCE(csi.commission_value, ms.commission_value)"),
            8 => DB::raw("COALESCE(csi.commission_amount, ms.commission_amount)"),
            9 => DB::raw("COALESCE(csi.is_excluded, 0)"),
            10 => 'ms.commission_is_paid',
            11 => 'cs.status',
            12 => DB::raw("sales_employee_name"),
            13 => 'ms.id',
        ];

        $orderBy = $columnsMap[$orderColIndex] ?? DB::raw("COALESCE(csi.subscription_created_at, ms.created_at)");

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

            $saleDate = $r->sale_date ? Carbon::parse($r->sale_date)->format('Y-m-d H:i') : '-';

            $commissionAmount = (float)($r->commission_amount ?? 0);
            $commissionBase = (float)($r->commission_base_amount ?? 0);
            $commissionValue = (float)($r->commission_value ?? 0);
            $commissionValueType = (string)($r->commission_value_type ?? '');

            $data[] = [
                'rownum' => $start + $idx + 1,

                'sale_date' => e($saleDate),
                'branch' => e($branchName),

                'member' => $this->buildMemberBlock($r->member_id ?? null),
                'subscription' => $this->buildSubscriptionBlock(
                    $r->subscription_id ?? null,
                    $planName,
                    $typeName,
                    $r->source ?? null
                ),

                'sale_total' => $this->fmtMoney($r->sale_total ?? 0),
                'commission_base' => $this->fmtMoney($commissionBase),
                'commission_rule' => $this->buildCommissionRuleBlock($commissionValueType, $commissionValue),
                'commission_amount' => $this->buildCommissionAmountBlock($commissionAmount, (int)($r->is_excluded ?? 0)),

                'excluded' => $this->buildExcludedBlock((int)($r->is_excluded ?? 0), $r->exclude_reason ?? null),
                'paid' => $this->buildPaidBlock((int)($r->commission_is_paid ?? 0), $r->commission_paid_at ?? null, $r->commission_paid_by_name ?? null),

                'settlement' => $this->buildSettlementBlock(
                    $r->commission_settlement_id ?? null,
                    $r->settlement_status ?? null,
                    $r->settlement_paid_at ?? null,
                    $r->settlement_paid_by_name ?? null
                ),

                'sales_employee' => e($r->sales_employee_name ?: '-'),
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
        $q = DB::table('member_subscriptions as ms')
            ->whereNull('ms.deleted_at')
            ->leftJoin('commission_settlements as cs', function ($j) {
                $j->on('cs.id', '=', 'ms.commission_settlement_id')->whereNull('cs.deleted_at');
            })
            ->leftJoin('commission_settlement_items as csi', function ($j) {
                $j->on('csi.member_subscription_id', '=', 'ms.id')
                  ->on('csi.commission_settlement_id', '=', 'ms.commission_settlement_id');
            })
            ->leftJoin('branches as b', function ($j) {
                $j->on('b.id', '=', 'ms.branch_id')->whereNull('b.deleted_at');
            })
            ->leftJoin('subscriptions_types as st', function ($j) {
                $j->on('st.id', '=', 'ms.subscriptions_type_id')->whereNull('st.deleted_at');
            })
            ->leftJoin('users as u_paid', 'u_paid.id', '=', 'ms.commission_paid_by')
            ->leftJoin('users as u_cs_paid', 'u_cs_paid.id', '=', 'cs.paid_by')
            ->leftJoin('employees as se', function ($j) {
                $j->on('se.id', '=', 'ms.sales_employee_id')->whereNull('se.deleted_at');
            })
            ->select([
                DB::raw("COALESCE(csi.subscription_created_at, ms.created_at) as sale_date"),

                'ms.id as subscription_id',
                'ms.member_id',
                'ms.branch_id',
                'ms.subscriptions_plan_id',
                'ms.subscriptions_type_id',
                'ms.plan_code',
                'ms.plan_name',
                'ms.source',

                'ms.total_amount as sale_total',

                DB::raw("COALESCE(csi.commission_base_amount, ms.commission_base_amount) as commission_base_amount"),
                DB::raw("COALESCE(csi.commission_value_type, ms.commission_value_type) as commission_value_type"),
                DB::raw("COALESCE(csi.commission_value, ms.commission_value) as commission_value"),
                DB::raw("COALESCE(csi.commission_amount, ms.commission_amount) as commission_amount"),

                DB::raw("COALESCE(csi.is_excluded, 0) as is_excluded"),
                DB::raw("COALESCE(csi.exclude_reason, '') as exclude_reason"),

                'ms.commission_is_paid',
                'ms.commission_paid_at',

                'ms.commission_paid_by',
                'u_paid.name as commission_paid_by_name',

                'ms.commission_settlement_id',
                'cs.status as settlement_status',
                'cs.paid_at as settlement_paid_at',
                'u_cs_paid.name as settlement_paid_by_name',

                'b.name as branch_name',
                'st.name as type_name',

                DB::raw("TRIM(CONCAT(COALESCE(se.first_name,''),' ',COALESCE(se.last_name,''))) as sales_employee_name"),
            ]);

        $this->applyFilters($q, $request);

        if ($applySearch && $search !== '') {
            $this->applySearch($q, $search);
        }

        return $q;
    }

    private function applyFilters($q, Request $request): void
    {
        $branchIds = array_values(array_filter(array_map('intval', (array)$request->get('branch_ids', []))));
        if (!empty($branchIds)) {
            $q->whereIn('ms.branch_id', $branchIds);
        }

        // Date filter uses sale_date = COALESCE(csi.subscription_created_at, ms.created_at)
        if ($request->filled('date_from')) {
            $q->whereDate(DB::raw("DATE(COALESCE(csi.subscription_created_at, ms.created_at))"), '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate(DB::raw("DATE(COALESCE(csi.subscription_created_at, ms.created_at))"), '<=', $request->get('date_to'));
        }

        if ($request->filled('sales_employee_id')) {
            $q->where('ms.sales_employee_id', (int)$request->get('sales_employee_id'));
        }

        if ($request->filled('commission_is_paid') && in_array((string)$request->get('commission_is_paid'), ['0', '1'], true)) {
            $q->where('ms.commission_is_paid', (int)$request->get('commission_is_paid'));
        }

        if ($request->filled('has_settlement') && in_array((string)$request->get('has_settlement'), ['0', '1'], true)) {
            if ((string)$request->get('has_settlement') === '1') {
                $q->whereNotNull('ms.commission_settlement_id');
            } else {
                $q->whereNull('ms.commission_settlement_id');
            }
        }

        if ($request->filled('settlement_status')) {
            $q->where('cs.status', (string)$request->get('settlement_status'));
        }

        if ($request->filled('is_excluded') && in_array((string)$request->get('is_excluded'), ['0', '1'], true)) {
            $q->where(DB::raw("COALESCE(csi.is_excluded, 0)"), (int)$request->get('is_excluded'));
        }

        if ($request->filled('source')) {
            $q->where('ms.source', (string)$request->get('source'));
        }

        if ($request->filled('amount_from')) {
            $q->where('ms.total_amount', '>=', (float)$request->get('amount_from'));
        }
        if ($request->filled('amount_to')) {
            $q->where('ms.total_amount', '<=', (float)$request->get('amount_to'));
        }

        if ($request->filled('commission_from')) {
            $q->where(DB::raw("COALESCE(csi.commission_amount, ms.commission_amount)"), '>=', (float)$request->get('commission_from'));
        }
        if ($request->filled('commission_to')) {
            $q->where(DB::raw("COALESCE(csi.commission_amount, ms.commission_amount)"), '<=', (float)$request->get('commission_to'));
        }

        if ($request->filled('only_with_commission') && in_array((string)$request->get('only_with_commission'), ['0', '1'], true)) {
            if ((string)$request->get('only_with_commission') === '1') {
                $q->where(DB::raw("COALESCE(csi.commission_amount, ms.commission_amount)"), '>', 0);
            }
        }
    }

    private function applySearch($q, string $search): void
    {
        $s = trim($search);
        $like = '%' . $s . '%';

        $q->where(function ($w) use ($s, $like) {
            if (is_numeric($s)) {
                $w->orWhere('ms.id', (int)$s)
                    ->orWhere('ms.member_id', (int)$s)
                    ->orWhere('ms.branch_id', (int)$s)
                    ->orWhere('ms.sales_employee_id', (int)$s)
                    ->orWhere('ms.commission_settlement_id', (int)$s);
            }

            $w->orWhere('ms.plan_code', 'like', $like)
                ->orWhere('ms.plan_name', 'like', $like)
                ->orWhere('ms.source', 'like', $like)
                ->orWhere(DB::raw("COALESCE(b.name,'')"), 'like', $like)
                ->orWhere(DB::raw("COALESCE(st.name,'')"), 'like', $like)
                ->orWhere(DB::raw("COALESCE(cs.status,'')"), 'like', $like)
                ->orWhere(DB::raw("COALESCE(csi.exclude_reason,'')"), 'like', $like)
                ->orWhere(DB::raw("TRIM(CONCAT(COALESCE(se.first_name,''),' ',COALESCE(se.last_name,'')))"), 'like', $like)
                ->orWhere(DB::raw("COALESCE(u_paid.name,'')"), 'like', $like)
                ->orWhere(DB::raw("COALESCE(u_cs_paid.name,'')"), 'like', $like);
        });
    }

    // ===================== KPIs =====================

    private function computeKpis(Request $request): array
    {
        $q = $this->buildDetailQuery($request, false);

        $itemsCount = (int)(clone $q)->count('ms.id');

        $totalAll = (float)(clone $q)->sum(DB::raw("COALESCE(csi.commission_amount, ms.commission_amount)"));
        $totalExcluded = (float)(clone $q)
            ->where(DB::raw("COALESCE(csi.is_excluded,0)"), 1)
            ->sum(DB::raw("COALESCE(csi.commission_amount, ms.commission_amount)"));

        $totalIncluded = $totalAll - $totalExcluded;

        $paidCommission = (float)(clone $q)
            ->where('ms.commission_is_paid', 1)
            ->sum(DB::raw("COALESCE(csi.commission_amount, ms.commission_amount)"));

        $unpaidCommission = (float)(clone $q)
            ->where(function ($w) {
                $w->whereNull('ms.commission_is_paid')->orWhere('ms.commission_is_paid', 0);
            })
            ->sum(DB::raw("COALESCE(csi.commission_amount, ms.commission_amount)"));

        $settledCount = (int)(clone $q)->whereNotNull('ms.commission_settlement_id')->count('ms.id');
        $unsettledCount = $itemsCount - $settledCount;

        return [
            'items_count' => $itemsCount,
            'total_commission_all' => round($totalAll, 2),
            'total_commission_included' => round($totalIncluded, 2),
            'total_commission_excluded' => round($totalExcluded, 2),
            'paid_commission' => round($paidCommission, 2),
            'unpaid_commission' => round($unpaidCommission, 2),
            'settled_items_count' => $settledCount,
            'unsettled_items_count' => $unsettledCount,
        ];
    }

    // ===================== Group Summary =====================

    private function groupSummary(Request $request): array
    {
        $groupBy = (string)$request->get('group_by', 'sales_employee');
        $allowed = array_keys($this->groupByOptions());
        if (!in_array($groupBy, $allowed, true)) {
            $groupBy = 'sales_employee';
        }

        $q = DB::table('member_subscriptions as ms')
            ->whereNull('ms.deleted_at')
            ->leftJoin('commission_settlements as cs', function ($j) {
                $j->on('cs.id', '=', 'ms.commission_settlement_id')->whereNull('cs.deleted_at');
            })
            ->leftJoin('commission_settlement_items as csi', function ($j) {
                $j->on('csi.member_subscription_id', '=', 'ms.id')
                  ->on('csi.commission_settlement_id', '=', 'ms.commission_settlement_id');
            });

        $this->applyFilters($q, $request);

        $locale = app()->getLocale();

        $sumExpr = "SUM(COALESCE(csi.commission_amount, ms.commission_amount))";
        $sumExcludedExpr = "SUM(CASE WHEN COALESCE(csi.is_excluded,0)=1 THEN COALESCE(csi.commission_amount, ms.commission_amount) ELSE 0 END)";
        $sumPaidExpr = "SUM(CASE WHEN ms.commission_is_paid=1 THEN COALESCE(csi.commission_amount, ms.commission_amount) ELSE 0 END)";

        if ($groupBy === 'sales_employee') {
            $q->leftJoin('employees as se', function ($j) {
                $j->on('se.id', '=', 'ms.sales_employee_id')->whereNull('se.deleted_at');
            });

            $q->select([
                'ms.sales_employee_id as group_id',
                DB::raw("TRIM(CONCAT(COALESCE(se.first_name,''),' ',COALESCE(se.last_name,''))) as group_name"),
                DB::raw("COUNT(ms.id) as items_count"),
                DB::raw("$sumExpr as total_commission"),
                DB::raw("$sumExcludedExpr as excluded_commission"),
                DB::raw("$sumPaidExpr as paid_commission"),
            ])->groupBy('ms.sales_employee_id', DB::raw("TRIM(CONCAT(COALESCE(se.first_name,''),' ',COALESCE(se.last_name,'')))"));
        } elseif ($groupBy === 'branch') {
            $q->leftJoin('branches as b', function ($j) {
                $j->on('b.id', '=', 'ms.branch_id')->whereNull('b.deleted_at');
            });

            $q->select([
                'ms.branch_id as group_id',
                'b.name as group_name',
                DB::raw("COUNT(ms.id) as items_count"),
                DB::raw("$sumExpr as total_commission"),
                DB::raw("$sumExcludedExpr as excluded_commission"),
                DB::raw("$sumPaidExpr as paid_commission"),
            ])->groupBy('ms.branch_id', 'b.name');
        } elseif ($groupBy === 'paid_status') {
            $q->select([
                DB::raw("COALESCE(ms.commission_is_paid,0) as group_id"),
                DB::raw("CASE WHEN COALESCE(ms.commission_is_paid,0)=1 THEN 'paid' ELSE 'unpaid' END as group_name"),
                DB::raw("COUNT(ms.id) as items_count"),
                DB::raw("$sumExpr as total_commission"),
                DB::raw("$sumExcludedExpr as excluded_commission"),
                DB::raw("$sumPaidExpr as paid_commission"),
            ])->groupBy(DB::raw("COALESCE(ms.commission_is_paid,0)"));
        } elseif ($groupBy === 'settlement_status') {
            $q->select([
                DB::raw("COALESCE(cs.status,'-') as group_id"),
                DB::raw("COALESCE(cs.status,'-') as group_name"),
                DB::raw("COUNT(ms.id) as items_count"),
                DB::raw("$sumExpr as total_commission"),
                DB::raw("$sumExcludedExpr as excluded_commission"),
                DB::raw("$sumPaidExpr as paid_commission"),
            ])->groupBy(DB::raw("COALESCE(cs.status,'-')"));
        } elseif ($groupBy === 'excluded') {
            $q->select([
                DB::raw("COALESCE(csi.is_excluded,0) as group_id"),
                DB::raw("CASE WHEN COALESCE(csi.is_excluded,0)=1 THEN 'excluded' ELSE 'included' END as group_name"),
                DB::raw("COUNT(ms.id) as items_count"),
                DB::raw("$sumExpr as total_commission"),
                DB::raw("$sumExcludedExpr as excluded_commission"),
                DB::raw("$sumPaidExpr as paid_commission"),
            ])->groupBy(DB::raw("COALESCE(csi.is_excluded,0)"));
        } else { // source
            $q->select([
                DB::raw("COALESCE(ms.source,'-') as group_id"),
                DB::raw("COALESCE(ms.source,'-') as group_name"),
                DB::raw("COUNT(ms.id) as items_count"),
                DB::raw("$sumExpr as total_commission"),
                DB::raw("$sumExcludedExpr as excluded_commission"),
                DB::raw("$sumPaidExpr as paid_commission"),
            ])->groupBy(DB::raw("COALESCE(ms.source,'-')"));
        }

        $rows = $q->orderByDesc(DB::raw("total_commission"))->limit(200)->get();

        $out = [];
        foreach ($rows as $r) {
            $name = $r->group_name;

            if (is_string($name) || is_array($name)) {
                $name = $this->nameJsonOrText($name, $locale);
            }

            $displayName = (string)$name;

            if (($groupBy === 'paid_status') && $displayName === 'paid') $displayName = __('reports.com_paid');
            if (($groupBy === 'paid_status') && $displayName === 'unpaid') $displayName = __('reports.com_unpaid');
            if (($groupBy === 'excluded') && $displayName === 'excluded') $displayName = __('reports.com_excluded');
            if (($groupBy === 'excluded') && $displayName === 'included') $displayName = __('reports.com_included');

            $out[] = [
                'group_id' => $r->group_id,
                'group_name' => $displayName ?: '-',
                'items_count' => (int)($r->items_count ?? 0),
                'total_commission' => round((float)($r->total_commission ?? 0), 2),
                'excluded_commission' => round((float)($r->excluded_commission ?? 0), 2),
                'paid_commission' => round((float)($r->paid_commission ?? 0), 2),
            ];
        }

        return [
            'group_by' => $groupBy,
            'rows' => $out,
        ];
    }

    // ===================== Print / Excel =====================

    private function print(Request $request)
    {
        $rows = $this->buildDetailQuery($request, false)
            ->orderBy(DB::raw("COALESCE(csi.subscription_created_at, ms.created_at)"), 'desc')
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
            'title' => __('reports.commissions_report_title'),
            'org_name' => $orgName,
            'generated_at' => now('Africa/Cairo')->format('Y-m-d H:i'),
            'total_count' => $rows->count(),
        ];

        return view('reports.commissions_report.print', [
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
            ->orderBy(DB::raw("COALESCE(csi.subscription_created_at, ms.created_at)"), 'desc')
            ->limit(50000)
            ->get();

        $locale = app()->getLocale();
        $isRtl = ($locale === 'ar');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($isRtl) $sheet->setRightToLeft(true);

        $headers = [
            __('reports.com_col_sale_date'),
            __('reports.com_col_branch'),
            __('reports.com_col_member'),
            __('reports.com_col_subscription'),
            __('reports.com_col_sale_total'),
            __('reports.com_col_commission_base'),
            __('reports.com_col_commission_rule'),
            __('reports.com_col_commission_amount'),
            __('reports.com_col_excluded'),
            __('reports.com_col_paid'),
            __('reports.com_col_settlement'),
            __('reports.com_col_sales_employee'),
            __('reports.com_col_source'),
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
            $saleDate = $r->sale_date ? Carbon::parse($r->sale_date)->format('Y-m-d H:i') : '-';

            $commissionValueType = (string)($r->commission_value_type ?? '');
            $commissionValue = (float)($r->commission_value ?? 0);

            $rule = $commissionValueType;
            if ($commissionValueType === 'percent') $rule = $commissionValue . '%';
            elseif ($commissionValueType === 'fixed') $rule = (string)$commissionValue;

            $excludedText = ((int)($r->is_excluded ?? 0) === 1)
                ? (__('reports.sub_yes') . ' - ' . ($r->exclude_reason ?: ''))
                : __('reports.sub_no');

            $paidText = ((int)($r->commission_is_paid ?? 0) === 1) ? __('reports.sub_yes') : __('reports.sub_no');

            $settlementText = $r->commission_settlement_id
                ? ('#' . $r->commission_settlement_id . ' / ' . ($r->settlement_status ?? '-'))
                : '-';

            $sheet->fromArray([
                $saleDate,
                $branchName,
                (string)($r->member_id ?? '-'),
                (string)($r->subscription_id ?? '-'),
                (float)($r->sale_total ?? 0),
                (float)($r->commission_base_amount ?? 0),
                $rule,
                (float)($r->commission_amount ?? 0),
                $excludedText,
                $paidText,
                $settlementText,
                (string)($r->sales_employee_name ?? '-'),
                (string)($r->source ?? '-'),
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

        $fileName = 'commissions_report_' . now('Africa/Cairo')->format('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ===================== UI blocks =====================

    private function buildMemberBlock($memberId): string
    {
        $id = $memberId ? (string)$memberId : '-';
        return '<span class="fw-semibold">#' . e($id) . '</span>';
    }

    private function buildSubscriptionBlock($subId, $planName, $typeName, $source): string
    {
        $sid = $subId ? (string)$subId : '-';
        $pn = $planName ?: '-';
        $tn = $typeName ?: '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">#' . e($sid) . '</span>' .
            '<small class="text-muted">' . e(__('reports.com_col_plan') . ': ' . $pn) . '</small>' .
            '<small class="text-muted">' . e(__('reports.com_col_type') . ': ' . $tn) . '</small>' .
            '<small class="text-muted">' . e(__('reports.com_col_source') . ': ' . ($source ?: '-')) . '</small>' .
            '</div>';
    }

    private function buildCommissionRuleBlock(string $type, float $value): string
    {
        $t = strtolower(trim($type));
        if ($t === 'percent') {
            return '<span class="badge bg-soft-primary text-primary">' . e($value) . '%</span>';
        }
        if ($t === 'fixed') {
            return '<span class="badge bg-soft-info text-info">' . e(__('reports.com_rule_fixed')) . ': ' . e($value) . '</span>';
        }

        return '<span class="badge bg-dark">' . e($type ?: '-') . '</span>';
    }

    private function buildCommissionAmountBlock(float $amount, int $isExcluded): string
    {
        $cls = $isExcluded === 1 ? 'text-muted' : 'text-success';
        return '<span class="fw-semibold ' . e($cls) . '">' . e($this->fmtMoney($amount)) . '</span>';
    }

    private function buildExcludedBlock(int $isExcluded, ?string $reason): string
    {
        if ($isExcluded === 1) {
            $r = trim((string)$reason);
            return '<div class="d-flex flex-column">' .
                '<span class="badge bg-soft-danger text-danger">' . e(__('reports.com_excluded')) . '</span>' .
                '<small class="text-muted">' . e($r !== '' ? $r : '-') . '</small>' .
                '</div>';
        }

        return '<span class="badge bg-soft-success text-success">' . e(__('reports.com_included')) . '</span>';
    }

    private function buildPaidBlock(int $isPaid, $paidAt, $paidByName): string
    {
        if ($isPaid === 1) {
            $dt = $paidAt ? Carbon::parse($paidAt)->format('Y-m-d H:i') : '-';
            $by = $paidByName ?: '-';

            return '<div class="d-flex flex-column">' .
                '<span class="badge bg-success">' . e(__('reports.com_paid')) . '</span>' .
                '<small class="text-muted">' . e(__('reports.com_paid_at') . ': ' . $dt) . '</small>' .
                '<small class="text-muted">' . e(__('reports.com_paid_by') . ': ' . $by) . '</small>' .
                '</div>';
        }

        return '<span class="badge bg-warning">' . e(__('reports.com_unpaid')) . '</span>';
    }

    private function buildSettlementStatusBadge(?string $status): string
    {
        $s = strtolower(trim((string)$status));

        // paid/draft/cancelled
        $map = [
            'paid' => ['success', 'paid'],
            'draft' => ['warning', 'draft'],
            'cancelled' => ['danger', 'cancelled'],
        ];

        if (isset($map[$s])) {
            return '<span class="badge bg-' . e($map[$s][0]) . '">' . e($map[$s][1]) . '</span>';
        }

        return '<span class="badge bg-dark">' . e($s ?: '-') . '</span>';
    }

    private function buildSettlementBlock($settlementId, $status, $paidAt, $paidByName): string
    {
        if (!$settlementId) {
            return '<span class="badge bg-secondary">' . e(__('reports.com_unsettled')) . '</span>';
        }

        $st = (string)($status ?: '-');
        $dt = $paidAt ? Carbon::parse($paidAt)->format('Y-m-d H:i') : '-';
        $by = $paidByName ?: '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">#' . e((string)$settlementId) . '</span>' .
            '<div class="mt-1">' . $this->buildSettlementStatusBadge($st) . '</div>' .
            '<small class="text-muted">' . e(__('reports.com_settlement_paid_at') . ': ' . $dt) . '</small>' .
            '<small class="text-muted">' . e(__('reports.com_settlement_paid_by') . ': ' . $by) . '</small>' .
            '</div>';
    }

    private function fmtMoney($v): string
    {
        return number_format((float)$v, 2, '.', '');
    }

    // ===================== Chips =====================

    private function buildFilterChips(Request $request): array
    {
        $chips = [];

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $chips[] = __('reports.com_filter_date') . ': ' . ($request->get('date_from') ?: '---') . ' ⟶ ' . ($request->get('date_to') ?: '---');
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
            $chips[] = __('reports.com_filter_branches') . ': ' . ($branchNames ?: '---');
        }

        foreach ([
            'sales_employee_id' => 'com_filter_sales_employee',
            'source' => 'com_filter_source',
            'settlement_status' => 'com_filter_settlement_status',
        ] as $key => $labelKey) {
            if ($request->filled($key)) {
                $chips[] = __('reports.' . $labelKey) . ': ' . $request->get($key);
            }
        }

        if ($request->filled('commission_is_paid')) {
            $chips[] = __('reports.com_filter_paid') . ': ' . ((string)$request->get('commission_is_paid') === '1' ? __('reports.com_paid') : __('reports.com_unpaid'));
        }

        if ($request->filled('has_settlement')) {
            $chips[] = __('reports.com_filter_has_settlement') . ': ' . ((string)$request->get('has_settlement') === '1' ? __('reports.sub_yes') : __('reports.sub_no'));
        }

        if ($request->filled('is_excluded')) {
            $chips[] = __('reports.com_filter_excluded') . ': ' . ((string)$request->get('is_excluded') === '1' ? __('reports.sub_yes') : __('reports.sub_no'));
        }

        if ($request->filled('amount_from') || $request->filled('amount_to')) {
            $chips[] = __('reports.com_filter_sale_amount') . ': ' . ($request->get('amount_from') ?: '---') . ' ⟶ ' . ($request->get('amount_to') ?: '---');
        }

        if ($request->filled('commission_from') || $request->filled('commission_to')) {
            $chips[] = __('reports.com_filter_commission_amount') . ': ' . ($request->get('commission_from') ?: '---') . ' ⟶ ' . ($request->get('commission_to') ?: '---');
        }

        if ($request->filled('only_with_commission')) {
            $chips[] = __('reports.com_filter_only_with_commission') . ': ' . ((string)$request->get('only_with_commission') === '1' ? __('reports.sub_yes') : __('reports.sub_no'));
        }

        if ($request->filled('group_by')) {
            $chips[] = __('reports.com_filter_group_by') . ': ' . $request->get('group_by');
        }

        return $chips;
    }

    // ===================== Options =====================

    private function yesNoOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.sub_all')],
            ['value' => '1', 'label' => __('reports.sub_yes')],
            ['value' => '0', 'label' => __('reports.sub_no')],
        ];
    }

    private function groupByOptions(): array
    {
        return [
            'sales_employee' => __('reports.com_group_sales_employee'),
            'branch' => __('reports.com_group_branch'),
            'paid_status' => __('reports.com_group_paid_status'),
            'settlement_status' => __('reports.com_group_settlement_status'),
            'excluded' => __('reports.com_group_excluded'),
            'source' => __('reports.com_group_source'),
        ];
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
