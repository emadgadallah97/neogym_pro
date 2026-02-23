<?php

namespace App\Http\Controllers\reports\pt_addons_report;

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

class pt_addons_reportcontroller extends Controller
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

        $trainers = DB::table('employees as e')
            ->whereNull('e.deleted_at')
            ->where(function ($w) {
                $w->where('e.is_coach', 1)->orWhereNull('e.is_coach')->orWhere('e.is_coach', 0);
            })
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

        // UPDATED: Removed payment-related KPIs
        $kpis = [
            'addons_count' => 0,
            'total_amount_sum' => 0,
            'sessions_total_sum' => 0,
            'sessions_used_sum' => 0,
            'sessions_remaining_sum' => 0,
            'unique_subscriptions' => 0,
            'unique_members' => 0,
        ];

        // UPDATED: Removed payment filters (payment_state, payment_status)
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),

            'branch_ids' => (array)$request->get('branch_ids', []),

            'trainer_id' => $request->get('trainer_id'),
            'member_id' => $request->get('member_id'),
            'member_subscription_id' => $request->get('member_subscription_id'),

            'source' => $request->get('source'),

            'only_remaining' => $request->get('only_remaining', '0'),

            'sessions_from' => $request->get('sessions_from'),
            'sessions_to' => $request->get('sessions_to'),

            'amount_from' => $request->get('amount_from'),
            'amount_to' => $request->get('amount_to'),

            'group_by' => $request->get('group_by', 'trainer'),
        ];

        // UPDATED: Removed payment_states, payment_statuses
        $filterOptions = [
            'yes_no' => $this->yesNoOptions(),
            'group_by' => $this->groupByOptions(),
            'sources' => $sources,
        ];

        return view('reports.pt_addons_report.index', [
            'branches' => $branches,
            'trainers' => $trainers,
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
        $recordsTotal = (clone $baseQuery)->count(DB::raw('pta.id'));

        $filteredQuery = $this->buildDetailQuery($request, true, $search);
        $recordsFiltered = (clone $filteredQuery)->count(DB::raw('pta.id'));

        $orderColIndex = (int)data_get($request->input('order', []), '0.column', 0);
        $orderDir = strtolower((string)data_get($request->input('order', []), '0.dir', 'desc'));
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';

        // UPDATED: Remove payment + PTA# ordering columns
        // Expected (new) columns order in index:
        // 0 date, 1 branch, 2 member, 3 subscription, 4 trainer,
        // 5 sessions_count, 6 sessions_used, 7 sessions_remaining,
        // 8 session_price, 9 total_amount, 10 notes
        $columnsMap = [
            0  => 'pta.created_at',
            1  => 'b.name',
            2  => DB::raw("member_name"),
            3  => 'pta.member_subscription_id',
            4  => DB::raw("trainer_name"),
            5  => 'pta.sessions_count',
            6  => DB::raw("(pta.sessions_count - pta.sessions_remaining)"),
            7  => 'pta.sessions_remaining',
            8  => 'pta.session_price',
            9  => 'pta.total_amount',
            10 => 'pta.notes',
        ];

        $orderBy = $columnsMap[$orderColIndex] ?? 'pta.created_at';

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

            $sessionsCount = (int)($r->sessions_count ?? 0);
            $sessionsRemaining = (int)($r->sessions_remaining ?? 0);
            $sessionsUsed = max(0, $sessionsCount - $sessionsRemaining);

            $data[] = [
                'rownum' => $start + $idx + 1,
                'date' => $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i') : '-',
                'branch' => e($branchName),

                // UPDATED: Member details
                'member' => $this->buildMemberBlock(
                    $r->member_id ?? null,
                    $r->member_name ?? null,
                    $r->member_code ?? null
                ),

                // UPDATED: Source label fix + translate source value
                'subscription' => $this->buildSubscriptionBlock(
                    $r->member_subscription_id ?? null,
                    $planName,
                    $typeName,
                    $r->source ?? null
                ),

                'trainer' => e($r->trainer_name ?: '-'),

                'sessions_count' => $sessionsCount,
                'sessions_used' => $sessionsUsed,
                'sessions_remaining' => $sessionsRemaining,

                'session_price' => $this->fmtMoney($r->session_price ?? 0),
                'total_amount' => $this->fmtMoney($r->total_amount ?? 0),

                'notes' => e($r->notes ?? ''),
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
        $q = DB::table('member_subscription_pt_addons as pta')
            ->whereNull('pta.deleted_at')
            ->leftJoin('member_subscriptions as ms', function ($j) {
                $j->on('ms.id', '=', 'pta.member_subscription_id')->whereNull('ms.deleted_at');
            })
            ->leftJoin('branches as b', function ($j) {
                $j->on('b.id', '=', 'ms.branch_id')->whereNull('b.deleted_at');
            })
            ->leftJoin('subscriptions_types as st', function ($j) {
                $j->on('st.id', '=', 'ms.subscriptions_type_id')->whereNull('st.deleted_at');
            })
            ->leftJoin('employees as tr', function ($j) {
                $j->on('tr.id', '=', 'pta.trainer_id')->whereNull('tr.deleted_at');
            })
            // NEW: join members to show member details + enable search
            ->leftJoin('members as m', function ($j) {
                $j->on('m.id', '=', 'ms.member_id')->whereNull('m.deleted_at');
            })
            ->select([
                // addon
                'pta.id as pta_id',
                'pta.member_subscription_id',
                'pta.trainer_id',
                'pta.session_price',
                'pta.sessions_count',
                'pta.sessions_remaining',
                'pta.total_amount',
                'pta.notes',
                'pta.created_at',

                // subscription
                'ms.id as subscription_id',
                'ms.member_id',
                'ms.branch_id',
                'ms.plan_code',
                'ms.plan_name',
                'ms.source',

                // lookups
                'b.name as branch_name',
                'st.name as type_name',
                DB::raw("TRIM(CONCAT(COALESCE(tr.first_name,''),' ',COALESCE(tr.last_name,''))) as trainer_name"),

                // member fields
                DB::raw("TRIM(CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,''))) as member_name"),
                'm.member_code as member_code',

                // derived sessions
                DB::raw("(pta.sessions_count - pta.sessions_remaining) as sessions_used"),
            ]);

        $this->applyFilters($q, $request);

        if ($applySearch && $search !== '') {
            $this->applySearch($q, $search);
        }

        return $q;
    }

    private function applyFilters($q, Request $request): void
    {
        // date filter on pta.created_at
        if ($request->filled('date_from')) {
            $q->whereDate('pta.created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('pta.created_at', '<=', $request->get('date_to'));
        }

        $branchIds = array_values(array_filter(array_map('intval', (array)$request->get('branch_ids', []))));
        if (!empty($branchIds)) {
            $q->whereIn('ms.branch_id', $branchIds);
        }

        if ($request->filled('trainer_id')) {
            $q->where('pta.trainer_id', (int)$request->get('trainer_id'));
        }

        if ($request->filled('member_id')) {
            $q->where('ms.member_id', (int)$request->get('member_id'));
        }

        if ($request->filled('member_subscription_id')) {
            $q->where('pta.member_subscription_id', (int)$request->get('member_subscription_id'));
        }

        if ($request->filled('source')) {
            $q->where('ms.source', (string)$request->get('source'));
        }

        if ($request->filled('only_remaining') && in_array((string)$request->get('only_remaining'), ['0', '1'], true)) {
            if ((string)$request->get('only_remaining') === '1') {
                $q->where('pta.sessions_remaining', '>', 0);
            }
        }

        if ($request->filled('sessions_from')) {
            $q->where('pta.sessions_count', '>=', (int)$request->get('sessions_from'));
        }
        if ($request->filled('sessions_to')) {
            $q->where('pta.sessions_count', '<=', (int)$request->get('sessions_to'));
        }

        if ($request->filled('amount_from')) {
            $q->where('pta.total_amount', '>=', (float)$request->get('amount_from'));
        }
        if ($request->filled('amount_to')) {
            $q->where('pta.total_amount', '<=', (float)$request->get('amount_to'));
        }

        // UPDATED: removed payment_state filter
    }

    private function applySearch($q, string $search): void
    {
        $s = trim($search);
        $like = '%' . $s . '%';

        $q->where(function ($w) use ($s, $like) {
            if (is_numeric($s)) {
                $w->orWhere('pta.id', (int)$s)
                    ->orWhere('pta.member_subscription_id', (int)$s)
                    ->orWhere('ms.member_id', (int)$s)
                    ->orWhere('ms.branch_id', (int)$s)
                    ->orWhere('pta.trainer_id', (int)$s);
            }

            $w->orWhere('ms.plan_code', 'like', $like)
                ->orWhere('ms.plan_name', 'like', $like)
                ->orWhere('ms.source', 'like', $like)
                ->orWhere(DB::raw("COALESCE(b.name,'')"), 'like', $like)
                ->orWhere(DB::raw("COALESCE(st.name,'')"), 'like', $like)
                ->orWhere(DB::raw("TRIM(CONCAT(COALESCE(tr.first_name,''),' ',COALESCE(tr.last_name,'')))"), 'like', $like)
                ->orWhere(DB::raw("COALESCE(pta.notes,'')"), 'like', $like)
                // NEW: member search
                ->orWhere('m.member_code', 'like', $like)
                ->orWhere('m.first_name', 'like', $like)
                ->orWhere('m.last_name', 'like', $like)
                ->orWhere(DB::raw("TRIM(CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,'')))"), 'like', $like);
        });
    }

    // ===================== KPIs =====================

    private function computeKpis(Request $request): array
    {
        $q = $this->buildDetailQuery($request, false);

        $addonsCount = (int)(clone $q)->count(DB::raw('pta.id'));

        $totalAmountSum = (float)(clone $q)->sum('pta.total_amount');
        $sessionsTotalSum = (int)(clone $q)->sum('pta.sessions_count');
        $sessionsRemainingSum = (int)(clone $q)->sum('pta.sessions_remaining');
        $sessionsUsedSum = max(0, $sessionsTotalSum - $sessionsRemainingSum);

        $uniqueSubscriptions = (int)(clone $q)->distinct('pta.member_subscription_id')->count('pta.member_subscription_id');
        $uniqueMembers = (int)(clone $q)->distinct('ms.member_id')->count('ms.member_id');

        return [
            'addons_count' => $addonsCount,
            'total_amount_sum' => round($totalAmountSum, 2),

            'sessions_total_sum' => (int)$sessionsTotalSum,
            'sessions_used_sum' => (int)$sessionsUsedSum,
            'sessions_remaining_sum' => (int)$sessionsRemainingSum,

            'unique_subscriptions' => $uniqueSubscriptions,
            'unique_members' => $uniqueMembers,
        ];
    }

    // ===================== Group Summary =====================

    private function groupSummary(Request $request): array
    {
        $groupBy = (string)$request->get('group_by', 'trainer');
        $allowed = array_keys($this->groupByOptions());
        if (!in_array($groupBy, $allowed, true)) {
            $groupBy = 'trainer';
        }

        $q = DB::table('member_subscription_pt_addons as pta')
            ->whereNull('pta.deleted_at')
            ->leftJoin('member_subscriptions as ms', function ($j) {
                $j->on('ms.id', '=', 'pta.member_subscription_id')->whereNull('ms.deleted_at');
            })
            ->leftJoin('branches as b', function ($j) {
                $j->on('b.id', '=', 'ms.branch_id')->whereNull('b.deleted_at');
            })
            ->leftJoin('employees as tr', function ($j) {
                $j->on('tr.id', '=', 'pta.trainer_id')->whereNull('tr.deleted_at');
            })
            // NEW: for member grouping display name
            ->leftJoin('members as m', function ($j) {
                $j->on('m.id', '=', 'ms.member_id')->whereNull('m.deleted_at');
            });

        $this->applyFilters($q, $request);

        $locale = app()->getLocale();

        $sumTotal = "SUM(pta.total_amount)";
        $sumSessions = "SUM(pta.sessions_count)";
        $sumRemaining = "SUM(pta.sessions_remaining)";
        $sumUsed = "SUM(pta.sessions_count - pta.sessions_remaining)";

        if ($groupBy === 'trainer') {
            $q->select([
                'pta.trainer_id as group_id',
                DB::raw("TRIM(CONCAT(COALESCE(tr.first_name,''),' ',COALESCE(tr.last_name,''))) as group_name"),
                DB::raw("COUNT(pta.id) as addons_count"),
                DB::raw("$sumTotal as total_amount_sum"),
                DB::raw("$sumSessions as sessions_total_sum"),
                DB::raw("$sumUsed as sessions_used_sum"),
                DB::raw("$sumRemaining as sessions_remaining_sum"),
            ])->groupBy('pta.trainer_id', DB::raw("TRIM(CONCAT(COALESCE(tr.first_name,''),' ',COALESCE(tr.last_name,'')))"));
        } elseif ($groupBy === 'branch') {
            $q->select([
                'ms.branch_id as group_id',
                'b.name as group_name',
                DB::raw("COUNT(pta.id) as addons_count"),
                DB::raw("$sumTotal as total_amount_sum"),
                DB::raw("$sumSessions as sessions_total_sum"),
                DB::raw("$sumUsed as sessions_used_sum"),
                DB::raw("$sumRemaining as sessions_remaining_sum"),
            ])->groupBy('ms.branch_id', 'b.name');
        } elseif ($groupBy === 'subscription') {
            $q->select([
                'pta.member_subscription_id as group_id',
                DB::raw("CONCAT('#',pta.member_subscription_id) as group_name"),
                DB::raw("COUNT(pta.id) as addons_count"),
                DB::raw("$sumTotal as total_amount_sum"),
                DB::raw("$sumSessions as sessions_total_sum"),
                DB::raw("$sumUsed as sessions_used_sum"),
                DB::raw("$sumRemaining as sessions_remaining_sum"),
            ])->groupBy('pta.member_subscription_id');
        } else { // member
            $q->select([
                'ms.member_id as group_id',
                DB::raw("TRIM(CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,''))) as group_name"),
                DB::raw("COUNT(pta.id) as addons_count"),
                DB::raw("$sumTotal as total_amount_sum"),
                DB::raw("$sumSessions as sessions_total_sum"),
                DB::raw("$sumUsed as sessions_used_sum"),
                DB::raw("$sumRemaining as sessions_remaining_sum"),
            ])->groupBy('ms.member_id', DB::raw("TRIM(CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,'')))"));
        }

        $rows = $q->orderByDesc(DB::raw("total_amount_sum"))->limit(200)->get();

        $out = [];
        foreach ($rows as $r) {
            $name = $r->group_name;

            if (is_string($name) || is_array($name)) {
                $name = $this->nameJsonOrText($name, $locale);
            }

            $out[] = [
                'group_id' => $r->group_id,
                'group_name' => (string)($name ?: '-'),
                'addons_count' => (int)($r->addons_count ?? 0),
                'total_amount_sum' => round((float)($r->total_amount_sum ?? 0), 2),
                'sessions_total_sum' => (int)($r->sessions_total_sum ?? 0),
                'sessions_used_sum' => (int)($r->sessions_used_sum ?? 0),
                'sessions_remaining_sum' => (int)($r->sessions_remaining_sum ?? 0),
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
            ->orderBy('pta.created_at', 'desc')
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
            'title' => __('reports.pt_addons_report_title') ?? 'تقرير PT Add-ons',
            'org_name' => $orgName,
            'generated_at' => now('Africa/Cairo')->format('Y-m-d H:i'),
            'total_count' => $rows->count(),
        ];

        return view('reports.pt_addons_report.print', [
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
            ->orderBy('pta.created_at', 'desc')
            ->limit(50000)
            ->get();

        $locale = app()->getLocale();
        $isRtl = ($locale === 'ar');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($isRtl) $sheet->setRightToLeft(true);

        // UPDATED: remove payment columns + remove PTA#
        // UPDATED: add member_code column
        $headers = [
            __('reports.pt_col_date') ?? 'التاريخ',
            __('reports.pt_col_branch') ?? 'الفرع',
            __('reports.pt_col_member') ?? 'العضو',
            __('reports.member_code') ?? 'كود العضو',
            __('reports.pt_col_subscription') ?? 'الاشتراك',
            __('reports.pt_col_trainer') ?? 'المدرب',
            __('reports.pt_col_sessions_count') ?? 'إجمالي الحصص',
            __('reports.pt_col_sessions_used') ?? 'المستخدم',
            __('reports.pt_col_sessions_remaining') ?? 'المتبقي',
            __('reports.pt_col_session_price') ?? 'سعر الحصة',
            __('reports.pt_col_total_amount') ?? 'الإجمالي',
            __('reports.pt_col_source') ?? 'المصدر',
            __('reports.pt_col_notes') ?? 'ملاحظات',
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

            $date = $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i') : '-';
            $sessionsCount = (int)($r->sessions_count ?? 0);
            $sessionsRemaining = (int)($r->sessions_remaining ?? 0);
            $sessionsUsed = max(0, $sessionsCount - $sessionsRemaining);

            $memberName = trim((string)($r->member_name ?? ''));
            $memberCode = trim((string)($r->member_code ?? ''));
            $srcLabel = $this->sourceValueLabel($r->source ?? null);

            $sheet->fromArray([
                $date,
                $branchName,
                $memberName ?: ('#' . (string)($r->member_id ?? '-')),
                $memberCode ?: '-',
                '#' . (string)($r->member_subscription_id ?? '-') . ' | ' . ($r->plan_code ? ($r->plan_code . ' - ') : '') . $planName . ' | ' . $typeName,
                (string)($r->trainer_name ?? '-'),
                $sessionsCount,
                $sessionsUsed,
                $sessionsRemaining,
                (float)($r->session_price ?? 0),
                (float)($r->total_amount ?? 0),
                $srcLabel ?: '-',
                (string)($r->notes ?? ''),
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

        $fileName = 'pt_addons_report_' . now('Africa/Cairo')->format('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ===================== UI blocks =====================

    private function buildMemberBlock($memberId, $memberName, $memberCode): string
    {
        $id = $memberId ? (string)$memberId : '-';
        $name = trim((string)$memberName);
        $code = trim((string)$memberCode);

        $displayName = $name !== '' ? $name : ('#' . $id);
        $displayCode = $code !== '' ? $code : '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($displayName) . '</span>' .
            '<small class="text-muted">' . e(($this->tr('reports.member_code', 'كود العضو')) . ': ' . $displayCode) . '</small>' .
            '</div>';
    }

    private function buildSubscriptionBlock($subId, $planName, $typeName, $source): string
    {
        $sid = $subId ? (string)$subId : '-';
        $pn = $planName ?: '-';
        $tn = $typeName ?: '-';

        $srcLabel = $this->sourceValueLabel($source);

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">#' . e($sid) . '</span>' .
            '<small class="text-muted">' . e($this->tr('reports.pt_col_plan', 'الخطة')) . ': ' . e($pn) . '</small>' .
            '<small class="text-muted">' . e($this->tr('reports.pt_col_type', 'النوع')) . ': ' . e($tn) . '</small>' .
            '<small class="text-muted">' . e($this->tr('reports.pt_col_source', 'المصدر')) . ': ' . e($srcLabel ?: '-') . '</small>' .
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
            $chips[] = ($this->tr('reports.pt_filter_date', 'الفترة')) . ': ' . ($request->get('date_from') ?: '---') . ' ⟶ ' . ($request->get('date_to') ?: '---');
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
            $chips[] = ($this->tr('reports.pt_filter_branches', 'الفروع')) . ': ' . ($branchNames ?: '---');
        }

        foreach ([
            'trainer_id' => 'pt_filter_trainer',
            'member_id' => 'pt_filter_member',
            'member_subscription_id' => 'pt_filter_subscription',
            'source' => 'pt_filter_source',
        ] as $key => $labelKey) {
            if ($request->filled($key)) {
                $val = $request->get($key);

                if ($key === 'source') {
                    $val = $this->sourceValueLabel((string)$val);
                }

                $chips[] = ($this->tr('reports.' . $labelKey, $key)) . ': ' . $val;
            }
        }

        if ($request->filled('only_remaining')) {
            $chips[] = ($this->tr('reports.pt_filter_only_remaining', 'المتبقي فقط')) . ': ' .
                ((string)$request->get('only_remaining') === '1'
                    ? ($this->tr('reports.sub_yes', 'نعم'))
                    : ($this->tr('reports.sub_no', 'لا')));
        }

        if ($request->filled('sessions_from') || $request->filled('sessions_to')) {
            $chips[] = ($this->tr('reports.pt_filter_sessions', 'الحصص')) . ': ' . ($request->get('sessions_from') ?: '---') . ' ⟶ ' . ($request->get('sessions_to') ?: '---');
        }

        if ($request->filled('amount_from') || $request->filled('amount_to')) {
            $chips[] = ($this->tr('reports.pt_filter_amount', 'القيمة')) . ': ' . ($request->get('amount_from') ?: '---') . ' ⟶ ' . ($request->get('amount_to') ?: '---');
        }

        if ($request->filled('group_by')) {
            $chips[] = ($this->tr('reports.pt_filter_group_by', 'تجميع حسب')) . ': ' . $request->get('group_by');
        }

        return $chips;
    }

    // ===================== Options =====================

    private function yesNoOptions(): array
    {
        return [
            ['value' => '', 'label' => $this->tr('reports.sub_all', 'الكل')],
            ['value' => '1', 'label' => $this->tr('reports.sub_yes', 'نعم')],
            ['value' => '0', 'label' => $this->tr('reports.sub_no', 'لا')],
        ];
    }

    private function groupByOptions(): array
    {
        return [
            'trainer' => $this->tr('reports.pt_group_trainer', 'المدرب'),
            'branch' => $this->tr('reports.pt_group_branch', 'الفرع'),
            'subscription' => $this->tr('reports.pt_group_subscription', 'الاشتراك'),
            'member' => $this->tr('reports.pt_group_member', 'العضو'),
        ];
    }

    // ===================== Helpers =====================

    private function tr(string $key, string $fallback): string
    {
        $v = __($key);
        return ($v === $key || $v === '') ? $fallback : $v;
    }

    private function sourceValueLabel(?string $source): string
    {
        $s = strtolower(trim((string)$source));
        if ($s === '') return '-';

        $locale = app()->getLocale();

        // Fix common values (esp. reception)
        $map = [
            'reception' => [$locale === 'ar' ? 'الاستقبال' : 'Reception'],
            'front_desk' => [$locale === 'ar' ? 'الاستقبال' : 'Front desk'],
            'sales' => [$locale === 'ar' ? 'المبيعات' : 'Sales'],
            'online' => [$locale === 'ar' ? 'أونلاين' : 'Online'],
            'app' => [$locale === 'ar' ? 'التطبيق' : 'App'],
            'system' => [$locale === 'ar' ? 'النظام' : 'System'],
        ];

        if (isset($map[$s])) {
            return (string)$map[$s][0];
        }

        // default: return original (avoid showing translation key)
        return (string)$source;
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
