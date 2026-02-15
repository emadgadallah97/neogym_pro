<?php

namespace App\Http\Controllers\reports\subscriptions_report;

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

class subscriptions_reportcontroller extends Controller
{
    public function index(Request $request)
    {
        $action = (string)$request->get('action', '');

        if (!$request->ajax() && (int)$request->get('print', 0) === 1) {
            return $this->print($request);
        }

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

        $periodTypes = [
            'daily',
            'weekly',
            'monthly',
            'quarterly',
            'semi_yearly',
            'yearly',
            'other',
        ];

        $kpis = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'types_used' => 0,
            'allow_freeze' => 0,
            'allow_guest' => 0,
            'notify_before_end' => 0,
            'avg_duration_days' => 0,
            'avg_sessions_count' => 0,
            'avg_price' => 0,
            'branches_used' => 0,
        ];

        $filterOptions = [
            'statuses' => $this->statusOptions(),
            'yes_no' => $this->yesNoOptions(),
            'period_types' => $this->periodTypeOptions($periodTypes),
        ];

        return view('reports.subscriptions_report.index', [
            'branches' => $branches,
            'types' => $types,
            'periodTypes' => $periodTypes,
            'kpis' => $kpis,
            'filters' => [
                'plan_term' => $request->get('plan_term'),
                'type_id' => $request->get('type_id'),
                'branch_ids' => (array)$request->get('branch_ids', []),

                'status' => $request->get('status'),
                'sessions_period_type' => $request->get('sessions_period_type'),

                'allow_guest' => $request->get('allow_guest'),
                'allow_freeze' => $request->get('allow_freeze'),
                'notify_before_end' => $request->get('notify_before_end'),

                'duration_from' => $request->get('duration_from'),
                'duration_to' => $request->get('duration_to'),

                'sessions_from' => $request->get('sessions_from'),
                'sessions_to' => $request->get('sessions_to'),

                'price_from' => $request->get('price_from'),
                'price_to' => $request->get('price_to'),
            ],
            'filterOptions' => $filterOptions,
        ]);
    }

    private function datatable(Request $request)
    {
        $draw   = (int)$request->input('draw', 1);
        $start  = (int)$request->input('start', 0);
        $length = (int)$request->input('length', 10);

        if ((int)$request->get('no_data', 0) === 1) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $search = trim((string)data_get($request->input('search', []), 'value', ''));

        $baseQuery = $this->buildQuery($request, false);
        $recordsTotal = (clone $baseQuery)->count('sp.id');

        $filteredQuery = $this->buildQuery($request, true, $search);
        $recordsFiltered = (clone $filteredQuery)->count('sp.id');

        $orderColIndex = (int)data_get($request->input('order', []), '0.column', 0);
        $orderDir = strtolower((string)data_get($request->input('order', []), '0.dir', 'desc'));
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';

        $columnsMap = [
            0  => 'sp.name',
            1  => 'st.name',
            2  => 'sp.status',
            3  => 'sp.sessions_period_type',
            4  => 'sp.sessions_count',
            5  => 'sp.duration_days',
            6  => 'sp.allow_guest',
            7  => 'sp.allow_freeze',
            8  => 'sp.notify_before_end',
            9  => 'uc.name',
            10 => 'sp.id',
        ];

        $orderBy = $columnsMap[$orderColIndex] ?? 'sp.id';

        $rows = $filteredQuery
            ->orderBy($orderBy, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $locale = app()->getLocale();

        $data = [];
        foreach ($rows as $idx => $r) {
            $planName = $this->nameJsonOrText($r->plan_name ?? null, $locale) ?: '-';
            $typeName = $this->nameJsonOrText($r->type_name ?? null, $locale) ?: '-';

            $branchItems = $this->parseBranchPriceActiveConcat($r->branches_price_active_concat ?? '', $locale);

            $data[] = [
                'rownum' => $start + $idx + 1,

                'plan_block' => $this->buildPlanBlock(
                    $r->code ?? null,
                    $planName,
                    $typeName,
                    $r->description ?? null
                ),

                'status_block' => $this->buildStatusBlock($r->status ?? null),

                'period_block' => $this->buildPeriodBlock(
                    $r->sessions_period_type ?? null,
                    $r->sessions_period_other_label ?? null,
                    $r->allowed_training_days ?? null
                ),

                'limits_block' => $this->buildLimitsBlock(
                    $r->sessions_count ?? null,
                    $r->duration_days ?? null
                ),

                'guest_block' => $this->buildGuestBlock(
                    $r->allow_guest ?? null,
                    $r->guest_people_count ?? null,
                    $r->guest_times_count ?? null,
                    $r->guest_allowed_days ?? null
                ),

                'freeze_block' => $this->buildFreezeBlock(
                    $r->allow_freeze ?? null,
                    $r->max_freeze_days ?? null
                ),

                'notify_block' => $this->buildNotifyBlock(
                    $r->notify_before_end ?? null,
                    $r->notify_days_before_end ?? null
                ),

                'branches_price_block' => $this->buildBranchesPriceBlock(
                    (int)($r->branches_count ?? 0),
                    $branchItems
                ),

                'added_by' => $r->created_by_name ?: '-',
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => (int)$recordsTotal,
            'recordsFiltered' => (int)$recordsFiltered,
            'data' => $data,
        ]);
    }

    private function buildQuery(Request $request, bool $applySearch = false, string $search = '')
    {
        $branchIds = array_values(array_filter(array_map('intval', (array)$request->get('branch_ids', []))));
        $branchFilterSql = '';
        if (!empty($branchIds)) {
            $branchFilterSql = ' AND spb.branch_id IN (' . implode(',', $branchIds) . ') ';
        }

        $q = DB::table('subscriptions_plans as sp')
            ->whereNull('sp.deleted_at')
            ->leftJoin('subscriptions_types as st', function ($j) {
                $j->on('st.id', '=', 'sp.subscriptions_type_id')->whereNull('st.deleted_at');
            })
            ->leftJoin('users as uc', 'uc.id', '=', 'sp.created_by')
            ->leftJoin('users as uu', 'uu.id', '=', 'sp.updated_by')
            ->select([
                'sp.id',
                'sp.code',
                'sp.subscriptions_type_id',
                'sp.name as plan_name',
                'sp.sessions_period_type',
                'sp.sessions_period_other_label',
                'sp.sessions_count',
                'sp.duration_days',
                'sp.allowed_training_days',
                'sp.allow_guest',
                'sp.guest_people_count',
                'sp.guest_times_count',
                'sp.guest_allowed_days',
                'sp.notify_before_end',
                'sp.notify_days_before_end',
                'sp.allow_freeze',
                'sp.max_freeze_days',
                'sp.description',
                'sp.notes',
                'sp.status',
                'sp.created_by',
                'sp.updated_by',

                'st.name as type_name',
                'uc.name as created_by_name',
                'uu.name as updated_by_name',

                DB::raw("(SELECT COUNT(DISTINCT spb.branch_id)
                          FROM subscriptions_plan_branches spb
                          WHERE spb.subscriptions_plan_id = sp.id {$branchFilterSql}) as branches_count"),

                DB::raw("(SELECT GROUP_CONCAT(CONCAT(
                                COALESCE(b.name,''),'::',
                                COALESCE(spp.price_without_trainer,''),'::',
                                (SELECT COUNT(*)
                                 FROM member_subscriptions ms
                                 WHERE ms.deleted_at IS NULL
                                   AND ms.subscriptions_plan_id = sp.id
                                   AND ms.branch_id = spb.branch_id
                                   AND ms.status = 'active')
                            ) SEPARATOR '||')
                          FROM subscriptions_plan_branches spb
                          JOIN branches b ON b.id = spb.branch_id AND b.deleted_at IS NULL
                          LEFT JOIN subscriptions_plan_branch_prices spp
                            ON spp.subscriptions_plan_id = sp.id
                           AND spp.branch_id = spb.branch_id
                           AND spp.deleted_at IS NULL
                          WHERE spb.subscriptions_plan_id = sp.id {$branchFilterSql}
                        ) as branches_price_active_concat"),
            ]);

        $this->applyFilters($q, $request, $branchIds);

        if ($applySearch && $search !== '') {
            $this->applySearch($q, $search);
        }

        return $q;
    }

    private function applyFilters($q, Request $request, array $branchIds): void
    {
        if ($request->filled('plan_term')) {
            $term = trim((string)$request->get('plan_term'));
            $like = '%' . $term . '%';

            $q->where(function ($w) use ($like) {
                $w->where('sp.code', 'like', $like)
                    ->orWhere('sp.name', 'like', $like)
                    ->orWhere('st.name', 'like', $like)
                    ->orWhere('sp.description', 'like', $like)
                    ->orWhere('sp.notes', 'like', $like);
            });
        }

        if ($request->filled('type_id')) {
            $q->where('sp.subscriptions_type_id', (int)$request->get('type_id'));
        }

        if (!empty($branchIds)) {
            $q->whereExists(function ($sub) use ($branchIds) {
                $sub->select(DB::raw(1))
                    ->from('subscriptions_plan_branches as spb_f')
                    ->whereColumn('spb_f.subscriptions_plan_id', 'sp.id')
                    ->whereIn('spb_f.branch_id', $branchIds);
            });
        }

        if ($request->filled('status')) {
            $st = $this->normalizeStatus($request->get('status'));
            if ($st !== null) {
                $q->where('sp.status', $st);
            }
        }

        if ($request->filled('sessions_period_type')) {
            $q->where('sp.sessions_period_type', (string)$request->get('sessions_period_type'));
        }

        if ($request->filled('allow_guest') && in_array((string)$request->get('allow_guest'), ['0', '1'], true)) {
            $q->where('sp.allow_guest', (int)$request->get('allow_guest'));
        }

        if ($request->filled('allow_freeze') && in_array((string)$request->get('allow_freeze'), ['0', '1'], true)) {
            $q->where('sp.allow_freeze', (int)$request->get('allow_freeze'));
        }

        if ($request->filled('notify_before_end') && in_array((string)$request->get('notify_before_end'), ['0', '1'], true)) {
            $q->where('sp.notify_before_end', (int)$request->get('notify_before_end'));
        }

        if ($request->filled('duration_from')) {
            $q->where('sp.duration_days', '>=', (int)$request->get('duration_from'));
        }
        if ($request->filled('duration_to')) {
            $q->where('sp.duration_days', '<=', (int)$request->get('duration_to'));
        }

        if ($request->filled('sessions_from')) {
            $q->where('sp.sessions_count', '>=', (int)$request->get('sessions_from'));
        }
        if ($request->filled('sessions_to')) {
            $q->where('sp.sessions_count', '<=', (int)$request->get('sessions_to'));
        }

        $priceFrom = $request->filled('price_from') ? (float)$request->get('price_from') : null;
        $priceTo = $request->filled('price_to') ? (float)$request->get('price_to') : null;

        if ($priceFrom !== null || $priceTo !== null) {
            $q->whereExists(function ($sub) use ($priceFrom, $priceTo, $branchIds) {
                $sub->select(DB::raw(1))
                    ->from('subscriptions_plan_branch_prices as sppf')
                    ->whereColumn('sppf.subscriptions_plan_id', 'sp.id')
                    ->whereNull('sppf.deleted_at');

                if (!empty($branchIds)) {
                    $sub->whereIn('sppf.branch_id', $branchIds);
                }

                if ($priceFrom !== null) {
                    $sub->where('sppf.price_without_trainer', '>=', $priceFrom);
                }
                if ($priceTo !== null) {
                    $sub->where('sppf.price_without_trainer', '<=', $priceTo);
                }
            });
        }
    }

    private function applySearch($q, string $search): void
    {
        $s = trim($search);
        $like = '%' . $s . '%';

        $q->where(function ($w) use ($s, $like) {
            if (is_numeric($s)) {
                $w->orWhere('sp.id', (int)$s)
                    ->orWhere('sp.subscriptions_type_id', (int)$s);
            }

            $w->orWhere('sp.code', 'like', $like)
                ->orWhere('sp.name', 'like', $like)
                ->orWhere('st.name', 'like', $like)
                ->orWhere('sp.description', 'like', $like)
                ->orWhere('sp.notes', 'like', $like)
                ->orWhere(DB::raw("COALESCE(uc.name,'')"), 'like', $like);
        });
    }

    private function computeKpis(Request $request): array
    {
        $branchIds = array_values(array_filter(array_map('intval', (array)$request->get('branch_ids', []))));

        $q = $this->buildQuery($request, false);

        $total = (clone $q)->count('sp.id');
        $active = (clone $q)->where('sp.status', 1)->count('sp.id');
        $inactive = (clone $q)->where('sp.status', 0)->count('sp.id');

        $typesUsed = (clone $q)->distinct('sp.subscriptions_type_id')->count('sp.subscriptions_type_id');

        $allowFreeze = (clone $q)->where('sp.allow_freeze', 1)->count('sp.id');
        $allowGuest = (clone $q)->where('sp.allow_guest', 1)->count('sp.id');
        $notifyBeforeEnd = (clone $q)->where('sp.notify_before_end', 1)->count('sp.id');

        $avgDuration = (float)((clone $q)->avg('sp.duration_days') ?? 0);
        $avgSessions = (float)((clone $q)->avg('sp.sessions_count') ?? 0);

        $ids = (clone $q)->limit(50000)->pluck('sp.id')->toArray();

        $avgPrice = 0;
        $branchesUsed = 0;

        if (!empty($ids)) {
            $avgPriceQuery = DB::table('subscriptions_plan_branch_prices as spp')
                ->whereIn('spp.subscriptions_plan_id', $ids)
                ->whereNull('spp.deleted_at');

            if (!empty($branchIds)) {
                $avgPriceQuery->whereIn('spp.branch_id', $branchIds);
            }

            $avgPrice = (float)($avgPriceQuery->avg('spp.price_without_trainer') ?? 0);

            $branchesUsedQuery = DB::table('subscriptions_plan_branches as spb')
                ->whereIn('spb.subscriptions_plan_id', $ids)
                ->distinct('spb.branch_id');

            if (!empty($branchIds)) {
                $branchesUsedQuery->whereIn('spb.branch_id', $branchIds);
            }

            $branchesUsed = (int)$branchesUsedQuery->count('spb.branch_id');
        }

        return [
            'total' => (int)$total,
            'active' => (int)$active,
            'inactive' => (int)$inactive,
            'types_used' => (int)$typesUsed,
            'allow_freeze' => (int)$allowFreeze,
            'allow_guest' => (int)$allowGuest,
            'notify_before_end' => (int)$notifyBeforeEnd,
            'avg_duration_days' => round($avgDuration, 2),
            'avg_sessions_count' => round($avgSessions, 2),
            'avg_price' => round($avgPrice, 2),
            'branches_used' => (int)$branchesUsed,
        ];
    }

    private function print(Request $request)
    {
        $rows = $this->buildQuery($request, false)
            ->orderBy('sp.id', 'desc')
            ->get();

        $kpis = $this->computeKpis($request);

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

        $chips = [];

        if ($request->filled('plan_term')) {
            $chips[] = __('reports.sub_filter_plan') . ': ' . $request->get('plan_term');
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
            $chips[] = __('reports.sub_filter_branches') . ': ' . ($branchNames ?: '---');
        }

        if ($request->filled('type_id')) {
            $t = DB::table('subscriptions_types')->whereNull('deleted_at')->where('id', (int)$request->get('type_id'))->first();
            $tn = $t ? $this->nameJsonOrText($t->name ?? null, app()->getLocale()) : '';
            $chips[] = __('reports.sub_filter_type') . ': ' . ($tn ?: '---');
        }

        if ($request->filled('status')) {
            $chips[] = __('reports.sub_filter_status') . ': ' . $this->translateStatus($request->get('status'));
        }

        if ($request->filled('sessions_period_type')) {
            $chips[] = __('reports.sub_filter_period_type') . ': ' . $this->translatePeriodType($request->get('sessions_period_type'));
        }

        if ($request->filled('allow_guest')) {
            $chips[] = __('reports.sub_filter_allow_guest') . ': ' . $this->translateYesNo($request->get('allow_guest'));
        }

        if ($request->filled('allow_freeze')) {
            $chips[] = __('reports.sub_filter_allow_freeze') . ': ' . $this->translateYesNo($request->get('allow_freeze'));
        }

        if ($request->filled('notify_before_end')) {
            $chips[] = __('reports.sub_filter_notify_before_end') . ': ' . $this->translateYesNo($request->get('notify_before_end'));
        }

        if ($request->filled('duration_from') || $request->filled('duration_to')) {
            $chips[] = __('reports.sub_filter_duration_days') . ': ' .
                ($request->get('duration_from') ?: '---') . ' ⟶ ' . ($request->get('duration_to') ?: '---');
        }

        if ($request->filled('sessions_from') || $request->filled('sessions_to')) {
            $chips[] = __('reports.sub_filter_sessions_count') . ': ' .
                ($request->get('sessions_from') ?: '---') . ' ⟶ ' . ($request->get('sessions_to') ?: '---');
        }

        if ($request->filled('price_from') || $request->filled('price_to')) {
            $chips[] = __('reports.sub_filter_price') . ': ' .
                ($request->get('price_from') ?: '---') . ' ⟶ ' . ($request->get('price_to') ?: '---');
        }

        $meta = [
            'title' => __('reports.subscriptions_report_title'),
            'org_name' => $orgName,
            'generated_at' => now('Africa/Cairo')->format('Y-m-d H:i'),
            'total_count' => $rows->count(),
        ];

        return view('reports.subscriptions_report.print', [
            'meta' => $meta,
            'chips' => $chips,
            'kpis' => $kpis,
            'rows' => $rows,
        ]);
    }

    private function exportExcel(Request $request)
    {
        $rows = $this->buildQuery($request, false)
            ->orderBy('sp.id', 'desc')
            ->get();

        $locale = app()->getLocale();
        $isRtl = ($locale === 'ar');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($isRtl) $sheet->setRightToLeft(true);

        $headers = [
            __('reports.sub_col_code'),
            __('reports.sub_col_name'),
            __('reports.sub_col_type'),
            __('reports.sub_col_status'),
            __('reports.sub_col_period_type'),
            __('reports.sub_col_allowed_training_days'),
            __('reports.sub_col_sessions_count'),
            __('reports.sub_col_duration_days'),
            __('reports.sub_col_allow_guest'),
            __('reports.sub_col_guest_people_count'),
            __('reports.sub_col_guest_times_count'),
            __('reports.sub_col_guest_allowed_days'),
            __('reports.sub_col_allow_freeze'),
            __('reports.sub_col_max_freeze_days'),
            __('reports.sub_col_notify_before_end'),
            __('reports.sub_col_notify_days_before_end'),
            __('reports.sub_col_branches_prices'),
            __('reports.sub_col_description'),
            __('reports.sub_col_notes'),
            __('reports.sub_col_created_by'),
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
            $planName = $this->nameJsonOrText($r->plan_name ?? null, $locale) ?: '-';
            $typeName = $this->nameJsonOrText($r->type_name ?? null, $locale) ?: '-';

            $allowedDays = $this->formatDaysList($r->allowed_training_days ?? null);
            $guestDays = $this->formatDaysList($r->guest_allowed_days ?? null);
            $periodType = $this->translatePeriodType($r->sessions_period_type ?? null, $r->sessions_period_other_label ?? null);

            $branchItems = $this->parseBranchPriceActiveConcat($r->branches_price_active_concat ?? '', $locale);
            $branchesText = $this->formatBranchItemsForExcel($branchItems);

            $sheet->fromArray([
                $r->code ?: '-',
                $planName,
                $typeName,
                $this->translateStatus($r->status ?? null),
                $periodType,
                $allowedDays ?: '-',
                $r->sessions_count !== null ? (int)$r->sessions_count : '-',
                $r->duration_days !== null ? (int)$r->duration_days : '-',
                $this->translateYesNo($r->allow_guest ?? null),
                $r->guest_people_count !== null ? (int)$r->guest_people_count : '-',
                $r->guest_times_count !== null ? (int)$r->guest_times_count : '-',
                $guestDays ?: '-',
                $this->translateYesNo($r->allow_freeze ?? null),
                $r->max_freeze_days !== null ? (int)$r->max_freeze_days : '-',
                $this->translateYesNo($r->notify_before_end ?? null),
                $r->notify_days_before_end !== null ? (int)$r->notify_days_before_end : '-',
                $branchesText ?: '-',
                $r->description ?: '-',
                $r->notes ?: '-',
                $r->created_by_name ?: '-',
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

        $fileName = 'subscriptions_report_' . now('Africa/Cairo')->format('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ================= Blocks =================

    private function buildPlanBlock($code, $name, $typeName, $desc): string
    {
        $code = $code ?: '-';
        $name = $name ?: '-';
        $typeName = $typeName ?: '-';

        $desc = $desc ?: '-';
        $descShort = mb_strlen($desc) > 60 ? (mb_substr($desc, 0, 60) . '...') : $desc;

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($name) . '</span>' .
            '<small class="text-muted">' . e(__('reports.sub_col_code') . ': ' . $code) . '</small>' .
            '<small class="text-muted">' . e(__('reports.sub_col_type') . ': ' . $typeName) . '</small>' .
            '<small class="text-muted">' . e(__('reports.sub_col_description') . ': ' . $descShort) . '</small>' .
            '</div>';
    }

    private function buildStatusBlock($status): string
    {
        $st = $this->normalizeStatus($status);
        if ($st === null) {
            return '<span class="badge bg-secondary">-</span>';
        }

        if ((int)$st === 1) {
            return '<span class="badge bg-success">' . e(__('reports.sub_status_active')) . '</span>';
        }
        return '<span class="badge bg-danger">' . e(__('reports.sub_status_inactive')) . '</span>';
    }

    private function buildPeriodBlock($type, $otherLabel, $allowedDays): string
    {
        $period = $this->translatePeriodType($type, $otherLabel);
        $days = $this->formatDaysList($allowedDays);

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($period ?: '-') . '</span>' .
            '<small class="text-muted">' . e(__('reports.sub_col_allowed_training_days') . ': ' . ($days ?: '-')) . '</small>' .
            '</div>';
    }

    private function buildLimitsBlock($sessionsCount, $durationDays): string
    {
        $sc = ($sessionsCount !== null && $sessionsCount !== '') ? (string)$sessionsCount : '-';
        $dd = ($durationDays !== null && $durationDays !== '') ? (string)$durationDays : '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e(__('reports.sub_col_sessions_count') . ': ' . $sc) . '</span>' .
            '<small class="text-muted">' . e(__('reports.sub_col_duration_days') . ': ' . $dd) . '</small>' .
            '</div>';
    }

    private function buildGuestBlock($allow, $people, $times, $days): string
    {
        $allowTxt = $this->translateYesNo($allow);
        $p = ($people !== null && $people !== '') ? (string)$people : '-';
        $t = ($times !== null && $times !== '') ? (string)$times : '-';
        $d = $this->formatDaysList($days);

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e(__('reports.sub_col_allow_guest') . ': ' . $allowTxt) . '</span>' .
            '<small class="text-muted">' . e(__('reports.sub_col_guest_people_count') . ': ' . $p . ' | ' . __('reports.sub_col_guest_times_count') . ': ' . $t) . '</small>' .
            '<small class="text-muted">' . e(__('reports.sub_col_guest_allowed_days') . ': ' . ($d ?: '-')) . '</small>' .
            '</div>';
    }

    private function buildFreezeBlock($allow, $maxDays): string
    {
        $allowTxt = $this->translateYesNo($allow);
        $m = ($maxDays !== null && $maxDays !== '') ? (string)$maxDays : '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e(__('reports.sub_col_allow_freeze') . ': ' . $allowTxt) . '</span>' .
            '<small class="text-muted">' . e(__('reports.sub_col_max_freeze_days') . ': ' . $m) . '</small>' .
            '</div>';
    }

    private function buildNotifyBlock($notify, $daysBefore): string
    {
        $nTxt = $this->translateYesNo($notify);
        $d = ($daysBefore !== null && $daysBefore !== '') ? (string)$daysBefore : '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e(__('reports.sub_col_notify_before_end') . ': ' . $nTxt) . '</span>' .
            '<small class="text-muted">' . e(__('reports.sub_col_notify_days_before_end') . ': ' . $d) . '</small>' .
            '</div>';
    }

    private function buildBranchesPriceBlock(int $branchesCount, array $branchItems): string
    {
        if (empty($branchItems)) {
            return '<div class="d-flex flex-column">' .
                '<span class="fw-semibold">' . e(__('reports.sub_col_branches_count') . ': ' . $branchesCount) . '</span>' .
                '<small class="text-muted">-</small>' .
                '</div>';
        }

        $lines = [];
        foreach ($branchItems as $it) {
            $bn = $it['branch_name'] ?? '-';
            $price = $it['price'] ?? '-';
            $active = (string)((int)($it['active_count'] ?? 0));

            $lines[] = e($bn) .
                ' — ' . e(__('reports.sub_branch_price') . ': ' . $price) .
                ' — ' . e(__('reports.sub_active_subs') . ': ' . $active);
        }

        $html = '<div class="d-flex flex-column">';
        $html .= '<span class="fw-semibold">' . e(__('reports.sub_col_branches_count') . ': ' . $branchesCount) . '</span>';
        $html .= '<small class="text-muted">' . implode('<br>', $lines) . '</small>';
        $html .= '</div>';

        return $html;
    }

    // ================= Parsing helpers =================

    private function parseBranchPriceActiveConcat(string $concat, string $locale): array
    {
        $concat = trim($concat);
        if ($concat === '') return [];

        $items = array_values(array_filter(array_map('trim', explode('||', $concat))));
        $out = [];

        foreach ($items as $it) {
            $parts = explode('::', $it);

            $branchRaw = $parts[0] ?? '';
            $priceRaw = $parts[1] ?? '';
            $activeRaw = $parts[2] ?? '0';

            $branchName = $this->nameJsonOrText($branchRaw, $locale) ?: '-';

            $price = trim((string)$priceRaw);
            $price = ($price === '' ? '-' : $price);

            $activeCount = is_numeric($activeRaw) ? (int)$activeRaw : 0;

            $out[] = [
                'branch_name' => $branchName,
                'price' => $price,
                'active_count' => $activeCount,
            ];
        }

        return $out;
    }

    private function formatBranchItemsForExcel(array $branchItems): string
    {
        if (empty($branchItems)) return '';

        $lines = [];
        foreach ($branchItems as $it) {
            $bn = $it['branch_name'] ?? '-';
            $price = $it['price'] ?? '-';
            $active = (string)((int)($it['active_count'] ?? 0));

            $lines[] = $bn . ' | ' . __('reports.sub_branch_price') . ': ' . $price . ' | ' . __('reports.sub_active_subs') . ': ' . $active;
        }

        return implode("\n", $lines);
    }

    // ================= Enums / translations =================

    private function normalizeStatus($status): ?int
    {
        $v = trim((string)$status);
        if ($v === '') return null;

        if (in_array($v, ['1', '0'], true)) return (int)$v;

        $s = strtolower($v);
        if (in_array($s, ['active', 'enabled'], true)) return 1;
        if (in_array($s, ['inactive', 'disabled'], true)) return 0;

        return null;
    }

    private function translateStatus($status): string
    {
        $st = $this->normalizeStatus($status);
        $isAr = app()->getLocale() === 'ar';

        if ($st === 1) return $this->trFallback('reports.sub_status_active', $isAr ? 'نشط' : 'Active');
        if ($st === 0) return $this->trFallback('reports.sub_status_inactive', $isAr ? 'غير نشط' : 'Inactive');

        return ((string)$status !== '') ? (string)$status : '-';
    }

    private function translateYesNo($v): string
    {
        $isAr = app()->getLocale() === 'ar';
        if ((string)$v === '1') return $this->trFallback('reports.sub_yes', $isAr ? 'نعم' : 'Yes');
        if ((string)$v === '0') return $this->trFallback('reports.sub_no', $isAr ? 'لا' : 'No');
        if (is_bool($v)) return $v ? $this->trFallback('reports.sub_yes', $isAr ? 'نعم' : 'Yes') : $this->trFallback('reports.sub_no', $isAr ? 'لا' : 'No');
        return '-';
    }

    private function translatePeriodType($type, $otherLabel = null): string
    {
        $t = strtolower(trim((string)$type));
        $isAr = app()->getLocale() === 'ar';

        if ($t === '') return '-';

        $map = [
            'daily' => $this->trFallback('reports.sub_period_daily', $isAr ? 'يومي' : 'Daily'),
            'weekly' => $this->trFallback('reports.sub_period_weekly', $isAr ? 'أسبوعي' : 'Weekly'),
            'monthly' => $this->trFallback('reports.sub_period_monthly', $isAr ? 'شهري' : 'Monthly'),
            'quarterly' => $this->trFallback('reports.sub_period_quarterly', $isAr ? 'ربع سنوي' : 'Quarterly'),
            'semi_yearly' => $this->trFallback('reports.sub_period_semi_yearly', $isAr ? 'نصف سنوي' : 'Semi-yearly'),
            'yearly' => $this->trFallback('reports.sub_period_yearly', $isAr ? 'سنوي' : 'Yearly'),
            'other' => $this->trFallback('reports.sub_period_other', $isAr ? 'أخرى' : 'Other'),
        ];

        $label = $map[$t] ?? $type;

        if ($t === 'other' && !empty($otherLabel)) {
            return $label . ' - ' . $otherLabel;
        }

        return (string)$label;
    }

    // ✅ FIX: support sat/sun + saturday/sunday + comma-separated strings
    private function normalizeDayKey(string $k): string
    {
        $k = strtolower(trim($k));

        $aliases = [
            'saturday' => 'sat',
            'sat' => 'sat',
            'sa' => 'sat',

            'sunday' => 'sun',
            'sun' => 'sun',
            'su' => 'sun',

            'monday' => 'mon',
            'mon' => 'mon',
            'mo' => 'mon',

            'tuesday' => 'tue',
            'tue' => 'tue',
            'tu' => 'tue',

            'wednesday' => 'wed',
            'wed' => 'wed',
            'we' => 'wed',

            'thursday' => 'thu',
            'thu' => 'thu',
            'th' => 'thu',

            'friday' => 'fri',
            'fri' => 'fri',
            'fr' => 'fri',
        ];

        return $aliases[$k] ?? $k;
    }

    private function translateDay($d): string
    {
        $k = $this->normalizeDayKey((string)$d);
        $isAr = app()->getLocale() === 'ar';

        $map = [
            'sat' => $this->trFallback('reports.sub_day_sat', $isAr ? 'السبت' : 'Sat'),
            'sun' => $this->trFallback('reports.sub_day_sun', $isAr ? 'الأحد' : 'Sun'),
            'mon' => $this->trFallback('reports.sub_day_mon', $isAr ? 'الإثنين' : 'Mon'),
            'tue' => $this->trFallback('reports.sub_day_tue', $isAr ? 'الثلاثاء' : 'Tue'),
            'wed' => $this->trFallback('reports.sub_day_wed', $isAr ? 'الأربعاء' : 'Wed'),
            'thu' => $this->trFallback('reports.sub_day_thu', $isAr ? 'الخميس' : 'Thu'),
            'fri' => $this->trFallback('reports.sub_day_fri', $isAr ? 'الجمعة' : 'Fri'),
        ];

        return $map[$k] ?? (string)$d;
    }

    // ✅ FIX: if string is not JSON array, split by comma and translate
    private function formatDaysList($jsonOrArray): string
    {
        if ($jsonOrArray === null || $jsonOrArray === '') return '';

        $arr = $jsonOrArray;

        if (is_string($arr)) {
            $decoded = json_decode($arr, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $arr = $decoded;
            } else {
                // comma separated like: "saturday, sunday, monday"
                if (strpos($arr, ',') !== false) {
                    $arr = array_map('trim', explode(',', $arr));
                }
            }
        }

        if (!is_array($arr)) {
            // if still a single word like "saturday"
            $single = trim((string)$arr);
            return $single !== '' ? $this->translateDay($single) : '';
        }

        $out = [];
        foreach ($arr as $d) {
            if ($d === null || $d === '') continue;
            $out[] = $this->translateDay((string)$d);
        }

        return implode('، ', $out);
    }

    // ================= Options =================

    private function statusOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.sub_all')],
            ['value' => '1', 'label' => $this->translateStatus(1)],
            ['value' => '0', 'label' => $this->translateStatus(0)],
        ];
    }

    private function yesNoOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.sub_all')],
            ['value' => '1', 'label' => $this->translateYesNo(1)],
            ['value' => '0', 'label' => $this->translateYesNo(0)],
        ];
    }

    private function periodTypeOptions(array $periodTypes): array
    {
        $out = [
            ['value' => '', 'label' => __('reports.sub_all')],
        ];

        foreach ($periodTypes as $pt) {
            $out[] = [
                'value' => (string)$pt,
                'label' => $this->translatePeriodType($pt),
            ];
        }

        return $out;
    }

    // ================= Shared helpers =================

    private function trFallback(string $key, string $fallback): string
    {
        $t = __($key);
        return ($t === $key) ? $fallback : $t;
    }

    private function nameJsonOrText($nameJsonOrText, string $locale): string
    {
        if ($nameJsonOrText === null) {
            return '';
        }

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
