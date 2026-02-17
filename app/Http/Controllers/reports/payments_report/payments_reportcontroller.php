<?php

namespace App\Http\Controllers\reports\payments_report;

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

class payments_reportcontroller extends Controller
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

            if ($action === 'subs_matching') {
                return $this->subscriptionsMatchingDatatable($request);
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

        $paymentMethods = DB::table('payments as p')
            ->whereNull('p.deleted_at')
            ->whereNotNull('p.payment_method')
            ->where('p.payment_method', '!=', '')
            ->distinct()
            ->orderBy('p.payment_method')
            ->pluck('p.payment_method')
            ->toArray();

        // sources are fixed kinds (payments/invoices.source)
        $sources = array_keys($this->paymentSourceKinds());

        $kpis = [
            'payments_count' => 0,
            'paid_sum' => 0,
            'pending_sum' => 0,
            'failed_sum' => 0,
            'refunded_sum' => 0,
            'net_collected' => 0,
            'unique_members' => 0,
            'unique_subscriptions' => 0,
            'outstanding_sum' => 0,
        ];

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'branch_ids' => (array)$request->get('branch_ids', []),

            'status' => $request->get('status'),
            'payment_method' => $request->get('payment_method'),

            'member_q' => $request->get('member_q'),

            'member_id' => $request->get('member_id'),
            'member_subscription_id' => $request->get('member_subscription_id'),

            'type_id' => $request->get('type_id'),
            'plan_id' => $request->get('plan_id'),
            'source' => $request->get('source'),

            'amount_from' => $request->get('amount_from'),
            'amount_to' => $request->get('amount_to'),

            'group_by' => $request->get('group_by', 'payment_method'),

            'show_only_outstanding' => $request->get('show_only_outstanding', '1'),
        ];

        $filterOptions = [
            'statuses' => $this->paymentStatusOptions(),
            'group_by' => $this->groupByOptions(),
            'payment_methods' => $paymentMethods,
            'sources' => $sources,
            'yes_no' => $this->yesNoOptions(),
        ];

        return view('reports.payments_report.index', [
            'branches' => $branches,
            'types' => $types,
            'plans' => $plans,

            'kpis' => $kpis,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ]);
    }

    // ===================== Main transactions datatable =====================

    private function datatable(Request $request)
    {
        $draw   = (int)$request->input('draw', 1);
        $start  = (int)$request->input('start', 0);
        $length = (int)$request->input('length', 25);

        $search = trim((string)data_get($request->input('search', []), 'value', ''));

        $baseQuery = $this->buildPaymentsQuery($request, false);
        $recordsTotal = (clone $baseQuery)->count('p.id');

        $filteredQuery = $this->buildPaymentsQuery($request, true, $search);
        $recordsFiltered = (clone $filteredQuery)->count('p.id');

        $orderColIndex = (int)data_get($request->input('order', []), '0.column', 0);
        $orderDir = strtolower((string)data_get($request->input('order', []), '0.dir', 'desc'));
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';

        $columnsMap = [
            0  => DB::raw("COALESCE(p.paid_at, p.created_at)"),
            1  => 'p.status',
            2  => 'p.payment_method',
            3  => 'p.amount',
            4  => 'b.name',
            5  => 'm.member_code',
            6  => DB::raw("member_name"),
            7  => 'p.member_subscription_id',
            8  => 'ms.plan_name',
            9  => DB::raw("source_kind"),
            10 => 'p.reference',
            11 => 'ua.name',
            12 => 'p.id',
        ];

        $orderBy = $columnsMap[$orderColIndex] ?? DB::raw("COALESCE(p.paid_at, p.created_at)");

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

            $memberName = trim((string)($r->member_name ?? ''));
            $memberCode = trim((string)($r->member_code ?? ''));

            $sourceKind = (string)($r->source_kind ?? '');
            $sourceLabel = $this->paymentSourceLabel($sourceKind);

            $data[] = [
                'rownum' => $start + $idx + 1,

                'date_block' => $this->buildDateBlock($r->paid_at ?? null, $r->created_at ?? null),
                'status' => $this->buildPaymentStatusBadge($r->status ?? null),
                'method' => e($r->payment_method ?: '-'),
                'amount' => $this->buildAmountBlock($r->amount ?? 0, $r->status ?? null),

                'branch' => e($branchName),
                'member' => $this->buildMemberBlock($r->member_id ?? null, $memberCode, $memberName),

                'subscription' => $this->buildSubscriptionBlock(
                    $r->member_subscription_id ?? null,
                    $r->plan_code ?? null,
                    $planName,
                    $typeName,
                    $sourceLabel
                ),

                'source' => e($sourceKind ?: '-'),
                'reference' => e($r->reference ?? '-'),
                'added_by' => e($r->user_add_name ?? '-'),
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => (int)$recordsTotal,
            'recordsFiltered' => (int)$recordsFiltered,
            'data' => $data,
        ]);
    }

    private function buildPaymentsQuery(Request $request, bool $applySearch = false, string $search = '')
    {
        $memberNameExpr = "TRIM(CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,'')))";

        // Latest invoice per subscription to avoid duplicates
        $invLatest = DB::table('invoices as i')
            ->whereNull('i.deleted_at')
            ->selectRaw('i.member_subscription_id, MAX(i.id) as last_invoice_id')
            ->groupBy('i.member_subscription_id');

        $q = DB::table('payments as p')
            ->whereNull('p.deleted_at')
            ->leftJoin('member_subscriptions as ms', function ($j) {
                $j->on('ms.id', '=', 'p.member_subscription_id')->whereNull('ms.deleted_at');
            })
            ->leftJoin('branches as b', function ($j) {
                $j->on('b.id', '=', 'ms.branch_id')->whereNull('b.deleted_at');
            })
            ->leftJoin('subscriptions_types as st', function ($j) {
                $j->on('st.id', '=', 'ms.subscriptions_type_id')->whereNull('st.deleted_at');
            })
            ->leftJoin('users as ua', 'ua.id', '=', 'p.user_add')
            ->leftJoin('members as m', function ($j) {
                $j->on('m.id', '=', 'p.member_id')->whereNull('m.deleted_at');
            })
            ->leftJoinSub($invLatest, 'invx', function ($j) {
                $j->on('invx.member_subscription_id', '=', 'p.member_subscription_id');
            })
            ->leftJoin('invoices as inv', function ($j) {
                $j->on('inv.id', '=', 'invx.last_invoice_id')->whereNull('inv.deleted_at');
            });

        $sourceExpr = $this->sourceKindExpr();

        $q->select([
            'p.id',
            'p.member_id',
            'p.member_subscription_id',
            'p.amount',
            'p.payment_method',
            'p.status',
            'p.paid_at',
            'p.reference',
            'p.notes',
            'p.user_add',
            'p.created_at',

            'p.source as payment_source',

            'ms.branch_id',
            'ms.subscriptions_plan_id',
            'ms.subscriptions_type_id',
            'ms.plan_code',
            'ms.plan_name',
            'ms.total_amount as subscription_total_amount',

            'inv.invoice_number as invoice_number',
            'inv.source as invoice_source',

            'b.name as branch_name',
            'st.name as type_name',
            'ua.name as user_add_name',

            'm.member_code as member_code',
            DB::raw("$memberNameExpr as member_name"),

            DB::raw("$sourceExpr as source_kind"),
        ]);

        $this->applyFilters($q, $request, $memberNameExpr);

        if ($applySearch && $search !== '') {
            $this->applySearch($q, $search, $memberNameExpr);
        }

        return $q;
    }

    private function applyFilters($q, Request $request, string $memberNameExpr = ''): void
    {
        $branchIds = array_values(array_filter(array_map('intval', (array)$request->get('branch_ids', []))));
        if (!empty($branchIds)) {
            $q->whereIn('ms.branch_id', $branchIds);
        }

        if ($request->filled('date_from')) {
            $q->whereDate(DB::raw("DATE(COALESCE(p.paid_at, p.created_at))"), '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate(DB::raw("DATE(COALESCE(p.paid_at, p.created_at))"), '<=', $request->get('date_to'));
        }

        if ($request->filled('status')) {
            $q->where('p.status', (string)$request->get('status'));
        }

        if ($request->filled('payment_method')) {
            $q->where('p.payment_method', (string)$request->get('payment_method'));
        }

        if ($request->filled('source')) {
            $src = (string)$request->get('source');
            if (array_key_exists($src, $this->paymentSourceKinds())) {
                $q->where(DB::raw($this->sourceKindExpr()), '=', $src);
            }
        }

        if ($request->filled('member_q')) {
            $mq = trim((string)$request->get('member_q'));
            if ($mq !== '') {
                $like = '%' . $mq . '%';
                $q->where(function ($w) use ($like, $memberNameExpr) {
                    $w->orWhere('m.member_code', 'like', $like);
                    if ($memberNameExpr) {
                        $w->orWhere(DB::raw("COALESCE($memberNameExpr,'')"), 'like', $like);
                    }
                });
            }
        }

        if ($request->filled('member_id')) {
            $q->where('p.member_id', (int)$request->get('member_id'));
        }

        if ($request->filled('member_subscription_id')) {
            $q->where('p.member_subscription_id', (int)$request->get('member_subscription_id'));
        }

        if ($request->filled('type_id')) {
            $q->where('ms.subscriptions_type_id', (int)$request->get('type_id'));
        }

        if ($request->filled('plan_id')) {
            $q->where('ms.subscriptions_plan_id', (int)$request->get('plan_id'));
        }

        if ($request->filled('amount_from')) {
            $q->where('p.amount', '>=', (float)$request->get('amount_from'));
        }
        if ($request->filled('amount_to')) {
            $q->where('p.amount', '<=', (float)$request->get('amount_to'));
        }
    }

    private function applySearch($q, string $search, string $memberNameExpr = ''): void
    {
        $s = trim($search);
        $like = '%' . $s . '%';

        $q->where(function ($w) use ($s, $like, $memberNameExpr) {
            if (is_numeric($s)) {
                $w->orWhere('p.id', (int)$s)
                  ->orWhere('p.member_id', (int)$s)
                  ->orWhere('p.member_subscription_id', (int)$s)
                  ->orWhere('ms.branch_id', (int)$s);
            }

            $w->orWhere('p.payment_method', 'like', $like)
              ->orWhere('p.status', 'like', $like)
              ->orWhere('p.reference', 'like', $like)
              ->orWhere('ms.plan_code', 'like', $like)
              ->orWhere('ms.plan_name', 'like', $like)
              ->orWhere(DB::raw("COALESCE(b.name,'')"), 'like', $like)
              ->orWhere(DB::raw("COALESCE(st.name,'')"), 'like', $like)
              ->orWhere(DB::raw("COALESCE(ua.name,'')"), 'like', $like)
              ->orWhere('m.member_code', 'like', $like);

            if ($memberNameExpr) {
                $w->orWhere(DB::raw("COALESCE($memberNameExpr,'')"), 'like', $like);
            }
        });
    }

    // ===================== KPIs =====================

    private function computeKpis(Request $request): array
    {
        $q = $this->buildPaymentsQuery($request, false);

        $paymentsCount = (int)(clone $q)->count('p.id');

        $paidSum = (float)(clone $q)->where('p.status', 'paid')->sum('p.amount');
        $pendingSum = (float)(clone $q)->where('p.status', 'pending')->sum('p.amount');
        $failedSum = (float)(clone $q)->where('p.status', 'failed')->sum('p.amount');
        $refundedSum = (float)(clone $q)->where('p.status', 'refunded')->sum('p.amount');

        $netCollected = round($paidSum - $refundedSum, 2);

        $uniqueMembers = (int)(clone $q)->distinct('p.member_id')->count('p.member_id');
        $uniqueSubscriptions = (int)(clone $q)->distinct('p.member_subscription_id')->count('p.member_subscription_id');

        $matching = $this->buildMatchingQuery($request, false)->get();
        $outstandingSum = 0;
        foreach ($matching as $m) {
            $outstandingSum += (float)($m->outstanding ?? 0);
        }

        return [
            'payments_count' => $paymentsCount,
            'paid_sum' => round($paidSum, 2),
            'pending_sum' => round($pendingSum, 2),
            'failed_sum' => round($failedSum, 2),
            'refunded_sum' => round($refundedSum, 2),
            'net_collected' => $netCollected,
            'unique_members' => $uniqueMembers,
            'unique_subscriptions' => $uniqueSubscriptions,
            'outstanding_sum' => round($outstandingSum, 2),
        ];
    }

    // ===================== Group summary =====================

    private function groupSummary(Request $request): array
    {
        $groupBy = (string)$request->get('group_by', 'payment_method');
        $allowed = array_keys($this->groupByOptions());
        if (!in_array($groupBy, $allowed, true)) {
            $groupBy = 'payment_method';
        }

        $invLatest = DB::table('invoices as i')
            ->whereNull('i.deleted_at')
            ->selectRaw('i.member_subscription_id, MAX(i.id) as last_invoice_id')
            ->groupBy('i.member_subscription_id');

        $q = DB::table('payments as p')
            ->whereNull('p.deleted_at')
            ->leftJoin('member_subscriptions as ms', function ($j) {
                $j->on('ms.id', '=', 'p.member_subscription_id')->whereNull('ms.deleted_at');
            })
            ->leftJoin('members as m', function ($j) {
                $j->on('m.id', '=', 'p.member_id')->whereNull('m.deleted_at');
            })
            ->leftJoinSub($invLatest, 'invx', function ($j) {
                $j->on('invx.member_subscription_id', '=', 'p.member_subscription_id');
            })
            ->leftJoin('invoices as inv', function ($j) {
                $j->on('inv.id', '=', 'invx.last_invoice_id')->whereNull('inv.deleted_at');
            });

        $memberNameExpr = "TRIM(CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,'')))";
        $this->applyFilters($q, $request, $memberNameExpr);

        $locale = app()->getLocale();

        if ($groupBy === 'payment_method') {
            $q->select([
                DB::raw("p.payment_method as group_id"),
                DB::raw("p.payment_method as group_name"),
                DB::raw("COUNT(p.id) as payments_count"),
                DB::raw("SUM(CASE WHEN p.status='paid' THEN p.amount ELSE 0 END) as paid_sum"),
                DB::raw("SUM(CASE WHEN p.status='refunded' THEN p.amount ELSE 0 END) as refunded_sum"),
                DB::raw("SUM(CASE WHEN p.status='pending' THEN p.amount ELSE 0 END) as pending_sum"),
                DB::raw("SUM(CASE WHEN p.status='failed' THEN p.amount ELSE 0 END) as failed_sum"),
            ])->groupBy('p.payment_method');

            $rows = $q->orderByDesc('paid_sum')->limit(200)->get();
        } elseif ($groupBy === 'status') {
            $q->select([
                DB::raw("p.status as group_id"),
                DB::raw("p.status as group_name"),
                DB::raw("COUNT(p.id) as payments_count"),
                DB::raw("SUM(p.amount) as total_amount"),
            ])->groupBy('p.status');

            $rows = $q->orderByDesc('total_amount')->limit(200)->get();
        } elseif ($groupBy === 'branch') {
            $q->leftJoin('branches as b', function ($j) {
                $j->on('b.id', '=', 'ms.branch_id')->whereNull('b.deleted_at');
            });

            $q->select([
                DB::raw("ms.branch_id as group_id"),
                DB::raw("b.name as group_name"),
                DB::raw("COUNT(p.id) as payments_count"),
                DB::raw("SUM(CASE WHEN p.status='paid' THEN p.amount ELSE 0 END) as paid_sum"),
                DB::raw("SUM(CASE WHEN p.status='refunded' THEN p.amount ELSE 0 END) as refunded_sum"),
                DB::raw("SUM(CASE WHEN p.status='pending' THEN p.amount ELSE 0 END) as pending_sum"),
                DB::raw("SUM(CASE WHEN p.status='failed' THEN p.amount ELSE 0 END) as failed_sum"),
            ])->groupBy('ms.branch_id', 'b.name');

            $rows = $q->orderByDesc('paid_sum')->limit(200)->get();
        } elseif ($groupBy === 'type') {
            $q->leftJoin('subscriptions_types as st', function ($j) {
                $j->on('st.id', '=', 'ms.subscriptions_type_id')->whereNull('st.deleted_at');
            });

            $q->select([
                DB::raw("ms.subscriptions_type_id as group_id"),
                DB::raw("st.name as group_name"),
                DB::raw("COUNT(p.id) as payments_count"),
                DB::raw("SUM(CASE WHEN p.status='paid' THEN p.amount ELSE 0 END) as paid_sum"),
                DB::raw("SUM(CASE WHEN p.status='refunded' THEN p.amount ELSE 0 END) as refunded_sum"),
            ])->groupBy('ms.subscriptions_type_id', 'st.name');

            $rows = $q->orderByDesc('paid_sum')->limit(200)->get();
        } elseif ($groupBy === 'plan') {
            $q->select([
                DB::raw("ms.subscriptions_plan_id as group_id"),
                DB::raw("ms.plan_name as group_name"),
                DB::raw("COUNT(p.id) as payments_count"),
                DB::raw("SUM(CASE WHEN p.status='paid' THEN p.amount ELSE 0 END) as paid_sum"),
                DB::raw("SUM(CASE WHEN p.status='refunded' THEN p.amount ELSE 0 END) as refunded_sum"),
            ])->groupBy('ms.subscriptions_plan_id', 'ms.plan_name');

            $rows = $q->orderByDesc('paid_sum')->limit(200)->get();
        } else { // source kind
            $q->select([
                DB::raw($this->sourceKindExpr() . " as group_id"),
                DB::raw($this->sourceKindExpr() . " as group_name"),
                DB::raw("COUNT(p.id) as payments_count"),
                DB::raw("SUM(CASE WHEN p.status='paid' THEN p.amount ELSE 0 END) as paid_sum"),
                DB::raw("SUM(CASE WHEN p.status='refunded' THEN p.amount ELSE 0 END) as refunded_sum"),
            ])->groupBy(DB::raw($this->sourceKindExpr()));

            $rows = $q->orderByDesc('paid_sum')->limit(200)->get();
        }

        $out = [];
        foreach ($rows as $r) {
            $name = $r->group_name;

            if (is_string($name) || is_array($name)) {
                $name = $this->nameJsonOrText($name, $locale);
            }

            if ($groupBy === 'source') {
                $friendly = $this->paymentSourceLabel((string)$r->group_name);
                $name = $friendly ?: ($name ?: '-');
            }

            $out[] = [
                'group_id' => $r->group_id,
                'group_name' => $name ?: '-',
                'payments_count' => (int)($r->payments_count ?? 0),
                'paid_sum' => round((float)($r->paid_sum ?? 0), 2),
                'refunded_sum' => round((float)($r->refunded_sum ?? 0), 2),
                'pending_sum' => round((float)($r->pending_sum ?? 0), 2),
                'failed_sum' => round((float)($r->failed_sum ?? 0), 2),
                'total_amount' => round((float)($r->total_amount ?? 0), 2),
            ];
        }

        return [
            'group_by' => $groupBy,
            'rows' => $out,
        ];
    }

    // ===================== Subscriptions matching =====================

    private function subscriptionsMatchingDatatable(Request $request)
    {
        $draw   = (int)$request->input('draw', 1);
        $start  = (int)$request->input('start', 0);
        $length = (int)$request->input('length', 25);

        $search = trim((string)data_get($request->input('search', []), 'value', ''));

        $baseQuery = $this->buildMatchingQuery($request, false);
        $recordsTotal = (clone $baseQuery)->count();

        $filteredQuery = $this->buildMatchingQuery($request, true, $search);
        $recordsFiltered = (clone $filteredQuery)->count();

        $orderColIndex = (int)data_get($request->input('order', []), '0.column', 0);
        $orderDir = strtolower((string)data_get($request->input('order', []), '0.dir', 'desc'));
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';

        $columnsMap = [
            0 => 'ms.created_at',
            1 => 'b.name',
            2 => DB::raw("member_name"),
            3 => 'ms.id',
            4 => 'ms.plan_name',
            5 => 'sale_total',
            6 => 'paid_sum',
            7 => 'refunded_sum',
            8 => 'net_collected',
            9 => 'outstanding',
        ];

        $orderBy = $columnsMap[$orderColIndex] ?? 'outstanding';

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

            $memberName = trim((string)($r->member_name ?? ''));
            $memberCode = trim((string)($r->member_code ?? ''));

            $sourceKind = (string)($r->source_kind ?? '');
            $sourceLabel = $this->paymentSourceLabel($sourceKind);

            $data[] = [
                'rownum' => $start + $idx + 1,
                'date' => $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i') : '-',
                'branch' => e($branchName),
                'member' => $this->buildMemberBlock($r->member_id ?? null, $memberCode, $memberName),
                'subscription' => $this->buildSubscriptionBlock(
                    $r->id ?? null,
                    $r->plan_code ?? null,
                    $planName,
                    $typeName,
                    $sourceLabel
                ),
                'sale_total' => round((float)($r->sale_total ?? 0), 2),
                'paid_sum' => round((float)($r->paid_sum ?? 0), 2),
                'refunded_sum' => round((float)($r->refunded_sum ?? 0), 2),
                'net_collected' => round((float)($r->net_collected ?? 0), 2),
                'outstanding' => round((float)($r->outstanding ?? 0), 2),
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => (int)$recordsTotal,
            'recordsFiltered' => (int)$recordsFiltered,
            'data' => $data,
        ]);
    }

    private function buildMatchingQuery(Request $request, bool $applySearch = false, string $search = '')
    {
        $memberNameExpr = "TRIM(CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,'')))";

        $invLatest = DB::table('invoices as i')
            ->whereNull('i.deleted_at')
            ->selectRaw('i.member_subscription_id, MAX(i.id) as last_invoice_id')
            ->groupBy('i.member_subscription_id');

        $saleTotalExpr = "
            CASE
                WHEN inv.source = 'PT_only' THEN COALESCE(ms.price_pt_addons, 0)
                WHEN inv.source = 'main_subscription_only' THEN COALESCE(ms.price_plan, 0)
                WHEN inv.source = 'main_subscription&PT' THEN COALESCE(ms.total_amount, 0)
                ELSE COALESCE(ms.total_amount, 0)
            END
        ";

        $paidSumExpr = "SUM(CASE WHEN p.status='paid' THEN p.amount ELSE 0 END)";
        $refSumExpr  = "SUM(CASE WHEN p.status='refunded' THEN p.amount ELSE 0 END)";

        $q = DB::table('member_subscriptions as ms')
            ->whereNull('ms.deleted_at')
            ->leftJoin('branches as b', function ($j) {
                $j->on('b.id', '=', 'ms.branch_id')->whereNull('b.deleted_at');
            })
            ->leftJoin('subscriptions_types as st', function ($j) {
                $j->on('st.id', '=', 'ms.subscriptions_type_id')->whereNull('st.deleted_at');
            })
            ->leftJoin('members as m', function ($j) {
                $j->on('m.id', '=', 'ms.member_id')->whereNull('m.deleted_at');
            })
            ->leftJoin('payments as p', function ($j) {
                $j->on('p.member_subscription_id', '=', 'ms.id')->whereNull('p.deleted_at');
            })
            ->leftJoinSub($invLatest, 'invx', function ($j) {
                $j->on('invx.member_subscription_id', '=', 'ms.id');
            })
            ->leftJoin('invoices as inv', function ($j) {
                $j->on('inv.id', '=', 'invx.last_invoice_id')->whereNull('inv.deleted_at');
            })
            ->select([
                'ms.id',
                'ms.member_id',
                'ms.branch_id',
                'ms.subscriptions_type_id',
                'ms.subscriptions_plan_id',
                'ms.plan_name',
                'ms.plan_code',
                'ms.created_at',

                'b.name as branch_name',
                'st.name as type_name',

                'm.member_code as member_code',
                DB::raw("$memberNameExpr as member_name"),

                'inv.source as source_kind',

                DB::raw("$saleTotalExpr as sale_total"),
                DB::raw("$paidSumExpr as paid_sum"),
                DB::raw("$refSumExpr as refunded_sum"),
                DB::raw("(($paidSumExpr) - ($refSumExpr)) as net_collected"),
                DB::raw("(($saleTotalExpr) - (($paidSumExpr) - ($refSumExpr))) as outstanding"),
            ])
            // FIX: add columns used inside CASE to GROUP BY to satisfy ONLY_FULL_GROUP_BY
            ->groupBy(
                'ms.id',
                'ms.member_id',
                'ms.branch_id',
                'ms.subscriptions_type_id',
                'ms.subscriptions_plan_id',
                'ms.plan_name',
                'ms.plan_code',
                'ms.created_at',

                // added:
                'ms.total_amount',
                'ms.price_pt_addons',
                'ms.price_plan',

                'b.name',
                'st.name',
                'm.member_code',
                'm.first_name',
                'm.last_name',
                'inv.source'
            );

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

        if ($request->filled('member_id')) {
            $q->where('ms.member_id', (int)$request->get('member_id'));
        }
        if ($request->filled('member_subscription_id')) {
            $q->where('ms.id', (int)$request->get('member_subscription_id'));
        }

        if ($request->filled('type_id')) {
            $q->where('ms.subscriptions_type_id', (int)$request->get('type_id'));
        }
        if ($request->filled('plan_id')) {
            $q->where('ms.subscriptions_plan_id', (int)$request->get('plan_id'));
        }

        if ($request->filled('source')) {
            $src = (string)$request->get('source');
            if (array_key_exists($src, $this->paymentSourceKinds())) {
                $q->where('inv.source', $src);
            }
        }

        if ($request->filled('show_only_outstanding') && in_array((string)$request->get('show_only_outstanding'), ['0', '1'], true)) {
            if ((string)$request->get('show_only_outstanding') === '1') {
                $q->havingRaw("outstanding > 0.0001");
            }
        }

        if ($request->filled('member_q')) {
            $mq = trim((string)$request->get('member_q'));
            if ($mq !== '') {
                $like = '%' . $mq . '%';
                $q->having(function ($w) use ($like, $memberNameExpr) {
                    $w->orHaving(DB::raw("COALESCE(m.member_code,'')"), 'like', $like)
                      ->orHaving(DB::raw("COALESCE($memberNameExpr,'')"), 'like', $like);
                });
            }
        }

        if ($applySearch && $search !== '') {
            $s = trim($search);
            $like = '%' . $s . '%';

            $q->having(function ($w) use ($s, $like, $memberNameExpr) {
                if (is_numeric($s)) {
                    $w->orHaving('ms.id', '=', (int)$s)
                      ->orHaving('ms.member_id', '=', (int)$s);
                }

                $w->orHaving(DB::raw("COALESCE(ms.plan_code,'')"), 'like', $like)
                  ->orHaving(DB::raw("COALESCE(ms.plan_name,'')"), 'like', $like)
                  ->orHaving(DB::raw("COALESCE(b.name,'')"), 'like', $like)
                  ->orHaving(DB::raw("COALESCE(st.name,'')"), 'like', $like)
                  ->orHaving(DB::raw("COALESCE(m.member_code,'')"), 'like', $like)
                  ->orHaving(DB::raw("COALESCE($memberNameExpr,'')"), 'like', $like);
            });
        }

        return $q;
    }

    // ===================== Print / Excel =====================

    private function print(Request $request)
    {
        $rows = $this->buildPaymentsQuery($request, false)
            ->orderBy(DB::raw("COALESCE(p.paid_at, p.created_at)"), 'desc')
            ->limit(5000)
            ->get();

        $kpis = $this->computeKpis($request);
        $group = $this->groupSummary($request);

        $matching = $this->buildMatchingQuery($request, false)
            ->orderBy('outstanding', 'desc')
            ->limit(50)
            ->get();

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
            'title' => __('reports.payments_report_title'),
            'org_name' => $orgName,
            'generated_at' => now('Africa/Cairo')->format('Y-m-d H:i'),
            'total_count' => $rows->count(),
        ];

        return view('reports.payments_report.print', [
            'meta' => $meta,
            'chips' => $chips,
            'kpis' => $kpis,
            'group' => $group,
            'rows' => $rows,
            'matching' => $matching,
        ]);
    }

    private function exportExcel(Request $request)
    {
        $rows = $this->buildPaymentsQuery($request, false)
            ->orderBy(DB::raw("COALESCE(p.paid_at, p.created_at)"), 'desc')
            ->limit(50000)
            ->get();

        $locale = app()->getLocale();
        $isRtl = ($locale === 'ar');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($isRtl) $sheet->setRightToLeft(true);

        $headers = [
            __('reports.pay_col_date'),
            __('reports.pay_col_status'),
            __('reports.pay_col_method'),
            __('reports.pay_col_amount'),
            __('reports.pay_col_branch'),
            __('reports.pay_col_member'),
            __('reports.pay_col_subscription'),
            __('reports.pay_col_plan'),
            __('reports.pay_col_type'),
            __('reports.pay_col_source'),
            __('reports.pay_col_reference'),
            __('reports.pay_col_added_by'),
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
            $date = $r->paid_at ? Carbon::parse($r->paid_at)->format('Y-m-d H:i') : ($r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i') : '-');

            $memberName = trim((string)($r->member_name ?? ''));
            $memberCode = trim((string)($r->member_code ?? ''));
            $memberCell = ($memberCode ? $memberCode . ' - ' : '') . ($memberName ?: ('#' . (string)($r->member_id ?? '-')));

            $sourceKind = (string)($r->source_kind ?? '');
            $sourceLabel = $this->paymentSourceLabel($sourceKind);
            $sourceCell = $sourceKind ?: '-';
            if ($sourceLabel) $sourceCell .= ' - ' . $sourceLabel;

            $sheet->fromArray([
                $date,
                (string)($r->status ?? '-'),
                (string)($r->payment_method ?? '-'),
                (float)($r->amount ?? 0),
                $branchName,
                $memberCell,
                (string)($r->member_subscription_id ?? '-'),
                ($r->plan_code ? ($r->plan_code . ' - ') : '') . $planName,
                $typeName,
                $sourceCell,
                (string)($r->reference ?? '-'),
                (string)($r->user_add_name ?? '-'),
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

        $fileName = 'payments_report_' . now('Africa/Cairo')->format('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ===================== UI blocks =====================

    private function buildDateBlock($paidAt, $createdAt): string
    {
        $p = $paidAt ? Carbon::parse($paidAt)->format('Y-m-d H:i') : '-';
        $c = $createdAt ? Carbon::parse($createdAt)->format('Y-m-d H:i') : '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e(__('reports.pay_col_paid_at') . ': ' . $p) . '</span>' .
            '<small class="text-muted">' . e(__('reports.pay_col_created_at') . ': ' . $c) . '</small>' .
            '</div>';
    }

    private function buildPaymentStatusBadge($status): string
    {
        $s = strtolower(trim((string)$status));
        $map = [
            'paid' => ['success', __('reports.pay_status_paid')],
            'pending' => ['warning', __('reports.pay_status_pending')],
            'failed' => ['danger', __('reports.pay_status_failed')],
            'refunded' => ['info', __('reports.pay_status_refunded')],
        ];

        if (isset($map[$s])) {
            return '<span class="badge bg-' . e($map[$s][0]) . '">' . e($map[$s][1]) . '</span>';
        }

        return '<span class="badge bg-dark">' . e($s ?: '-') . '</span>';
    }

    private function buildAmountBlock($amount, $status): string
    {
        $a = round((float)$amount, 2);
        $s = strtolower(trim((string)$status));

        $cls = 'text-dark';
        if ($s === 'paid') $cls = 'text-success';
        if ($s === 'failed') $cls = 'text-danger';
        if ($s === 'refunded') $cls = 'text-info';

        return '<span class="fw-semibold ' . e($cls) . '">' . e($a) . '</span>';
    }

    private function buildMemberBlock($memberId, string $memberCode = '', string $memberName = ''): string
    {
        $id = $memberId ? (string)$memberId : '-';
        $mc = trim($memberCode);
        $mn = trim($memberName);

        if ($mn === '' && $id !== '-') $mn = '#' . $id;

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($mn ?: '-') . '</span>' .
            '<small class="text-muted">' . e((__('reports.member_code') ?? 'كود العضو') . ': ' . ($mc ?: '-')) . '</small>' .
            '</div>';
    }

    private function buildSubscriptionBlock($subId, $planCode, $planName, $typeName, string $sourceLabel = ''): string
    {
        $sid = $subId ? (string)$subId : '-';
        $pc = $planCode ?: '-';
        $pn = $planName ?: '-';
        $tn = $typeName ?: '-';

        $kind = trim($sourceLabel);

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">#' . e($sid) . '</span>' .
            '<small class="text-muted">' . e(__('reports.pay_col_plan') . ': ' . ($pc !== '-' ? ($pc . ' - ') : '') . $pn) . '</small>' .
            '<small class="text-muted">' . e(__('reports.pay_col_type') . ': ' . $tn) . '</small>' .
            ($kind !== '' ? '<small class="text-muted">' . e((__('reports.pay_col_source') ?? 'المصدر') . ': ' . $kind) . '</small>' : '') .
            '</div>';
    }

    // ===================== Chips =====================

    private function buildFilterChips(Request $request): array
    {
        $chips = [];

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $chips[] = __('reports.pay_filter_date') . ': ' . ($request->get('date_from') ?: '---') . ' ⟶ ' . ($request->get('date_to') ?: '---');
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
            $chips[] = __('reports.pay_filter_branches') . ': ' . ($branchNames ?: '---');
        }

        foreach ([
            'status' => 'pay_filter_status',
            'payment_method' => 'pay_filter_method',
            'source' => 'pay_filter_source',
            'member_q' => 'pay_filter_member_q',
            'member_id' => 'pay_filter_member',
            'member_subscription_id' => 'pay_filter_subscription',
        ] as $key => $labelKey) {
            if ($request->filled($key)) {
                $chips[] = __('reports.' . $labelKey) . ': ' . $request->get($key);
            }
        }

        if ($request->filled('type_id')) {
            $chips[] = __('reports.pay_filter_type') . ': ' . $request->get('type_id');
        }

        if ($request->filled('plan_id')) {
            $chips[] = __('reports.pay_filter_plan') . ': ' . $request->get('plan_id');
        }

        if ($request->filled('amount_from') || $request->filled('amount_to')) {
            $chips[] = __('reports.pay_filter_amount') . ': ' . ($request->get('amount_from') ?: '---') . ' ⟶ ' . ($request->get('amount_to') ?: '---');
        }

        if ($request->filled('group_by')) {
            $chips[] = __('reports.pay_filter_group_by') . ': ' . $request->get('group_by');
        }

        return $chips;
    }

    // ===================== Options / translations =====================

    private function paymentStatusOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.sub_all')],
            ['value' => 'paid', 'label' => __('reports.pay_status_paid')],
            ['value' => 'pending', 'label' => __('reports.pay_status_pending')],
            ['value' => 'failed', 'label' => __('reports.pay_status_failed')],
            ['value' => 'refunded', 'label' => __('reports.pay_status_refunded')],
        ];
    }

    private function groupByOptions(): array
    {
        return [
            'payment_method' => __('reports.pay_group_method'),
            'status' => __('reports.pay_group_status'),
            'branch' => __('reports.pay_group_branch'),
            'type' => __('reports.pay_group_type'),
            'plan' => __('reports.pay_group_plan'),
            'source' => __('reports.pay_group_source'),
        ];
    }

    private function yesNoOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.sub_all')],
            ['value' => '1', 'label' => __('reports.sub_yes')],
            ['value' => '0', 'label' => __('reports.sub_no')],
        ];
    }

    private function paymentSourceKinds(): array
    {
        return [
            'main_subscription&PT' => 'main_subscription&PT',
            'main_subscription_only' => 'main_subscription_only',
            'PT_only' => 'PT_only',
        ];
    }

    private function paymentSourceLabel(string $kind): string
    {
        $map = [
            'main_subscription&PT' => __('reports.pay_source_main_and_pt') ?? 'اشتراك + PT',
            'main_subscription_only' => __('reports.pay_source_main_only') ?? 'اشتراك فقط',
            'PT_only' => __('reports.pay_source_pt_only') ?? 'PT فقط',
        ];

        return (string)($map[$kind] ?? '');
    }

    private function sourceKindExpr(): string
    {
        return "COALESCE(NULLIF(p.source,''), NULLIF(inv.source,''))";
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
