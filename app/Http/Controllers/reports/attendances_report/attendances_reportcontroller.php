<?php

namespace App\Http\Controllers\reports\attendances_report;

use App\Http\Controllers\Controller;
use App\Models\general\Branch;
use App\Models\general\GeneralSetting;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class attendances_reportcontroller extends Controller
{
    public function index(Request $request)
    {
        // Unified actions (print / export / metrics)
        $action = (string)$request->get('action', '');

        // Backward compatibility: ?print=1
        if (!$request->ajax() && (int)$request->get('print', 0) === 1) {
            return $this->print($request);
        }

        if (!$request->ajax() && $action === 'print') {
            return $this->print($request);
        }

        if (!$request->ajax() && $action === 'export_excel') {
            return $this->exportExcel($request);
        }

        // Defaults (current month)
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        if (empty($dateFrom) && empty($dateTo)) {
            $request->merge([
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to'   => now()->toDateString(),
            ]);
        }

        // Ajax endpoints
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

        $users = User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // We will load KPIs by AJAX after applying filters (like the old report UI)
        $kpis = [
            'total' => 0,
            'unique_members' => 0,
            'cancelled' => 0,
            'not_cancelled' => 0,
            'manual' => 0,
            'barcode' => 0,
            'branches_used' => 0,
            'guests_total' => 0,
        ];

        // خيارات مترجمة للفلاتر (القيم = مفاتيح ثابتة، والـ label مترجم)
        $filterOptions = [
            'member_statuses' => $this->memberStatusOptions(),
            'checkin_methods' => $this->checkinMethodOptions(),
            'day_keys' => $this->dayKeyOptions(),
        ];

        return view('reports.attendances_report.index', [
            'branches' => $branches,
            'users'    => $users,
            'kpis'     => $kpis,
            'filters'  => [
                'date_from'       => $request->get('date_from'),
                'date_to'         => $request->get('date_to'),
                'branch_ids'      => (array)$request->get('branch_ids', []),
                'member_term'     => $request->get('member_term'),
                'member_status'   => $request->get('member_status'),
                'checkin_method'  => $request->get('checkin_method'),
                'is_cancelled'    => $request->get('is_cancelled'),
                'recorded_by'     => (array)$request->get('recorded_by', []),
                'device_id'       => $request->get('device_id'),
                'gate_id'         => $request->get('gate_id'),
                'day_key'         => $request->get('day_key'),
                'subscription_id' => $request->get('subscription_id'),
                'pt_addon_id'     => $request->get('pt_addon_id'),
                'notes'           => $request->get('notes'),
            ],
            'filterOptions' => $filterOptions,
        ]);
    }

    private function datatable(Request $request)
    {
        $draw   = (int)$request->input('draw', 1);
        $start  = (int)$request->input('start', 0);
        $length = (int)$request->input('length', 10);

        // No data until Apply Filters (same behavior as old report)
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
        $recordsTotal = (clone $baseQuery)->count('a.id');

        $filteredQuery = $this->buildQuery($request, true, $search);
        $recordsFiltered = (clone $filteredQuery)->count('a.id');

        // Safe ordering map (DataTables column index -> DB column)
        $orderColIndex = (int)data_get($request->input('order', []), '0.column', 0);
        $orderDir = strtolower((string)data_get($request->input('order', []), '0.dir', 'desc'));
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';

        // New columns (ID removed, merged columns)
        $columnsMap = [
            0  => 'a.attendance_date',     // Date/Time
            1  => 'b.name',                // Branch
            2  => 'm.first_name',          // Member block
            3  => 'm.status',              // Member status
            4  => 'a.checkin_method',      // Method
            5  => 'u.name',                // Recorded by
            6  => 'a.is_cancelled',        // Cancel block
            7  => 's.plan_name',           // Plan block
            8  => 'a.pt_addon_id',         // PT block
            9  => 'a.device_id',           // Device/Gate
            10 => 'a.day_key',             // Day
            11 => 'a.notes',               // Notes
            12 => 'a.id',                  // Guests (subquery)
        ];

        $orderBy = $columnsMap[$orderColIndex] ?? 'a.attendance_date';

        $rows = $filteredQuery
            ->orderBy($orderBy, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $locale = app()->getLocale();

        $data = [];
        foreach ($rows as $idx => $r) {
            $branchName = $this->nameJsonOrText($r->branch_name ?? null, $locale);
            $planName   = $this->nameJsonOrText($r->plan_name ?? null, $locale);

            $memberFullName = trim(($r->member_first_name ?? '') . ' ' . ($r->member_last_name ?? ''));
            $memberPhone = $r->member_phone ?: ($r->member_phone2 ?: ($r->member_whatsapp ?: null));

            $attendanceDate = $r->attendance_date ? Carbon::parse($r->attendance_date)->format('Y-m-d') : '-';
            $attendanceTime = $r->attendance_time ?: '-';

            $memberBlock = $this->buildMemberBlock(
                $r->member_code ?? null,
                $memberFullName ?: null,
                $memberPhone ?: null
            );

            $cancelBlock = $this->buildCancelBlock(
                (int)($r->is_cancelled ?? 0),
                $r->cancelled_at ?? null,
                $r->cancelled_by_name ?? null
            );

            $subscriptionBlock = $this->buildSubscriptionBlock(
                $planName ?: null,
                $r->sub_start_date ?? null,
                $r->sub_end_date ?? null
            );

            $ptAttended = ((int)($r->pt_addon_id ?? 0) > 0) || ((int)($r->is_pt_deducted ?? 0) === 1);
            $ptBlock = $this->buildPtBlock($ptAttended, $r->pt_trainer_name ?? null);

            $deviceGate = $this->buildDeviceGateBlock($r->device_id ?? null, $r->gate_id ?? null);

            $data[] = [
                'rownum' => $start + $idx + 1,

                // merged/new fields
                'attendance_dt' => $this->buildTwoLines($attendanceDate, $attendanceTime),
                'branch'        => $branchName ?: '-',
                'member_block'  => $memberBlock,

                // translated fields
                'member_status'  => $this->translateMemberStatus($r->member_status ?? null),
                'checkin_method' => $this->translateCheckinMethod($r->checkin_method ?? null),
                'recorded_by'    => $r->recorded_by_name ?: '-',

                'cancel_block' => $cancelBlock,
                'plan_block'   => $subscriptionBlock,

                'pt_block'    => $ptBlock,
                'device_gate' => $deviceGate,

                // day translated (and normalized)
                'day_text' => $this->translateDayKey($r->day_key ?? null),

                'notes'        => $r->notes ?? '-',
                'guests_count' => (int)($r->guests_count ?? 0),
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
        $q = DB::table('attendances as a')
            ->whereNull('a.deleted_at')
            ->leftJoin('branches as b', function ($j) {
                $j->on('b.id', '=', 'a.branch_id')->whereNull('b.deleted_at');
            })
            ->leftJoin('members as m', function ($j) {
                $j->on('m.id', '=', 'a.member_id')->whereNull('m.deleted_at');
            })
            ->leftJoin('member_subscriptions as s', function ($j) {
                $j->on('s.id', '=', 'a.member_subscription_id')->whereNull('s.deleted_at');
            })
            ->leftJoin('member_subscription_pt_addons as p', function ($j) {
                $j->on('p.id', '=', 'a.pt_addon_id')->whereNull('p.deleted_at');
            })

            // Recorder: prefer a.recorded_by, fallback a.user_add (solve '-' issue)
            ->leftJoin('users as u', 'u.id', '=', 'a.recorded_by')
            ->leftJoin('users as ua', 'ua.id', '=', 'a.user_add')

            // Cancelled by: prefer a.cancelled_by, fallback a.user_update
            ->leftJoin('users as uc', 'uc.id', '=', 'a.cancelled_by')
            ->leftJoin('users as uu', 'uu.id', '=', 'a.user_update')

            ->leftJoin('employees as pttr', 'pttr.id', '=', 'p.trainer_id')

            ->select([
                'a.id',
                'a.branch_id',
                'a.member_id',
                'a.attendance_date',
                'a.attendance_time',
                'a.day_key',
                'a.member_subscription_id',
                'a.pt_addon_id',
                'a.is_pt_deducted',
                'a.checkin_method',
                'a.recorded_by',
                'a.device_id',
                'a.gate_id',
                'a.is_cancelled',
                'a.cancelled_at',
                'a.cancelled_by',
                'a.notes',

                'b.name as branch_name',

                'm.member_code',
                'm.first_name as member_first_name',
                'm.last_name as member_last_name',
                'm.status as member_status',
                'm.phone as member_phone',
                'm.phone2 as member_phone2',
                'm.whatsapp as member_whatsapp',

                's.plan_name',
                's.start_date as sub_start_date',
                's.end_date as sub_end_date',

                DB::raw("COALESCE(u.name, ua.name) as recorded_by_name"),
                DB::raw("COALESCE(uc.name, uu.name) as cancelled_by_name"),

                DB::raw("TRIM(CONCAT(COALESCE(pttr.first_name,''),' ',COALESCE(pttr.last_name,''))) as pt_trainer_name"),

                DB::raw("(SELECT COUNT(*) FROM attendance_guests g WHERE g.attendance_id = a.id AND g.deleted_at IS NULL) as guests_count"),
            ]);

        $this->applyFilters($q, $request);

        if ($applySearch && $search !== '') {
            $this->applySearch($q, $search);
        }

        return $q;
    }

    private function applyFilters($q, Request $request): void
    {
        if ($request->filled('date_from')) {
            $q->whereDate('a.attendance_date', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('a.attendance_date', '<=', $request->get('date_to'));
        }

        $branchIds = (array)$request->get('branch_ids', []);
        $branchIds = array_values(array_filter(array_map('intval', $branchIds)));
        if (!empty($branchIds)) {
            $q->whereIn('a.branch_id', $branchIds);
        }

        if ($request->filled('member_term')) {
            $term = trim((string)$request->get('member_term'));
            $like = '%' . $term . '%';
            $q->where(function ($w) use ($like) {
                $w->where('m.member_code', 'like', $like)
                    ->orWhere('m.first_name', 'like', $like)
                    ->orWhere('m.last_name', 'like', $like)
                    ->orWhere(DB::raw("CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,''))"), 'like', $like)
                    ->orWhere('m.phone', 'like', $like)
                    ->orWhere('m.phone2', 'like', $like)
                    ->orWhere('m.whatsapp', 'like', $like);
            });
        }

        if ($request->filled('member_status')) {
            $status = $this->normalizeMemberStatus($request->get('member_status'));
            if (!empty($status)) {
                $q->where('m.status', $status);
            }
        }

        if ($request->filled('is_cancelled') && in_array((string)$request->get('is_cancelled'), ['0', '1'], true)) {
            $q->where('a.is_cancelled', (int)$request->get('is_cancelled'));
        }

        if ($request->filled('checkin_method')) {
            $method = $this->normalizeCheckinMethod($request->get('checkin_method'));
            if (!empty($method)) {
                $q->where('a.checkin_method', $method);
            }
        }

        $recordedBy = (array)$request->get('recorded_by', []);
        $recordedBy = array_values(array_filter(array_map('intval', $recordedBy)));
        if (!empty($recordedBy)) {
            // include both recorded_by and user_add for better match with fallback
            $q->where(function ($w) use ($recordedBy) {
                $w->whereIn('a.recorded_by', $recordedBy)
                    ->orWhereIn('a.user_add', $recordedBy);
            });
        }

        if ($request->filled('device_id')) {
            $q->where('a.device_id', (int)$request->get('device_id'));
        }
        if ($request->filled('gate_id')) {
            $q->where('a.gate_id', (int)$request->get('gate_id'));
        }

        if ($request->filled('day_key')) {
            $dayKey = $this->normalizeDayKey($request->get('day_key'));
            if (!empty($dayKey)) {
                $q->where('a.day_key', $dayKey);
            }
        }

        // Keep support (even if removed from UI)
        if ($request->filled('subscription_id')) {
            $q->where('a.member_subscription_id', (int)$request->get('subscription_id'));
        }
        if ($request->filled('pt_addon_id')) {
            $q->where('a.pt_addon_id', (int)$request->get('pt_addon_id'));
        }

        if ($request->filled('notes')) {
            $like = '%' . trim((string)$request->get('notes')) . '%';
            $q->where('a.notes', 'like', $like);
        }
    }

    private function applySearch($q, string $search): void
    {
        $s = trim($search);
        $like = '%' . $s . '%';

        $q->where(function ($w) use ($s, $like) {
            if (is_numeric($s)) {
                $w->orWhere('a.id', (int)$s)
                    ->orWhere('a.member_id', (int)$s)
                    ->orWhere('a.branch_id', (int)$s)
                    ->orWhere('a.member_subscription_id', (int)$s)
                    ->orWhere('a.pt_addon_id', (int)$s)
                    ->orWhere('a.device_id', (int)$s)
                    ->orWhere('a.gate_id', (int)$s);
            }

            $w->orWhere('m.member_code', 'like', $like)
                ->orWhere('m.first_name', 'like', $like)
                ->orWhere('m.last_name', 'like', $like)
                ->orWhere(DB::raw("CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,''))"), 'like', $like)
                ->orWhere('m.phone', 'like', $like)
                ->orWhere('m.phone2', 'like', $like)
                ->orWhere('m.whatsapp', 'like', $like)
                ->orWhere(DB::raw("COALESCE(u.name, ua.name)"), 'like', $like)
                ->orWhere(DB::raw("COALESCE(uc.name, uu.name)"), 'like', $like)
                ->orWhere('a.notes', 'like', $like)
                ->orWhere('s.plan_name', 'like', $like)
                ->orWhere('b.name', 'like', $like);
        });
    }

    private function computeKpis(Request $request): array
    {
        $q = $this->buildQuery($request, false);

        $total = (clone $q)->count('a.id');
        $uniqueMembers = (clone $q)->distinct('a.member_id')->count('a.member_id');

        $cancelled = (clone $q)->where('a.is_cancelled', 1)->count('a.id');
        $notCancelled = max(0, (int)$total - (int)$cancelled);

        $manual = (clone $q)->where('a.checkin_method', 'manual')->count('a.id');
        $barcode = (clone $q)->where('a.checkin_method', 'barcode')->count('a.id');

        $branchesUsed = (clone $q)->distinct('a.branch_id')->count('a.branch_id');

        $guestQ = DB::table('attendance_guests as g')
            ->join('attendances as a', function ($j) {
                $j->on('a.id', '=', 'g.attendance_id')->whereNull('a.deleted_at');
            })
            ->whereNull('g.deleted_at');

        $ids = (clone $q)->limit(50000)->pluck('a.id')->toArray();
        $guestsTotal = 0;
        if (!empty($ids)) {
            $guestsTotal = (int)$guestQ->whereIn('g.attendance_id', $ids)->count('g.id');
        }

        return [
            'total'          => (int)$total,
            'unique_members' => (int)$uniqueMembers,
            'cancelled'      => (int)$cancelled,
            'not_cancelled'  => (int)$notCancelled,
            'manual'         => (int)$manual,
            'barcode'        => (int)$barcode,
            'branches_used'  => (int)$branchesUsed,
            'guests_total'   => (int)$guestsTotal,
        ];
    }

    private function print(Request $request)
    {
        $rows = $this->buildQuery($request, false)
            ->orderBy('a.attendance_date', 'desc')
            ->orderBy('a.attendance_time', 'desc')
            ->get();

        $kpis = $this->computeKpis($request);

        $settings = GeneralSetting::query()
            ->where('status', 1)
            ->first();

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

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $chips[] = __('reports.att_filter_date') . ': ' .
                ($request->get('date_from') ?: '---') . ' ⟶ ' . ($request->get('date_to') ?: '---');
        }

        if (!empty((array)$request->get('branch_ids', []))) {
            $branchNames = Branch::query()
                ->whereIn('id', array_values(array_filter(array_map('intval', (array)$request->get('branch_ids', [])))))
                ->get()
                ->map(function ($b) {
                    return method_exists($b, 'getTranslation') ? $b->getTranslation('name', app()->getLocale()) : ($b->name ?? '');
                })
                ->filter()
                ->values()
                ->implode('، ');
            $chips[] = __('reports.att_filter_branches') . ': ' . ($branchNames ?: '---');
        }

        if ($request->filled('member_term')) {
            $chips[] = __('reports.att_filter_member') . ': ' . $request->get('member_term');
        }

        if ($request->filled('member_status')) {
            $chips[] = __('reports.att_filter_member_status') . ': ' .
                $this->translateMemberStatus($request->get('member_status'));
        }

        if ($request->filled('checkin_method')) {
            $chips[] = __('reports.att_filter_method') . ': ' .
                $this->translateCheckinMethod($request->get('checkin_method'));
        }

        if ($request->filled('is_cancelled')) {
            $chips[] = __('reports.att_filter_cancelled') . ': ' .
                ((string)$request->get('is_cancelled') === '1' ? __('reports.att_cancelled') : __('reports.att_not_cancelled'));
        }

        if (!empty((array)$request->get('recorded_by', []))) {
            $userNames = User::query()
                ->whereIn('id', array_values(array_filter(array_map('intval', (array)$request->get('recorded_by', [])))))
                ->pluck('name')
                ->implode('، ');
            $chips[] = __('reports.att_filter_recorded_by') . ': ' . ($userNames ?: '---');
        }

        if ($request->filled('device_id')) {
            $chips[] = __('reports.att_filter_device') . ': ' . $request->get('device_id');
        }

        if ($request->filled('gate_id')) {
            $chips[] = __('reports.att_filter_gate') . ': ' . $request->get('gate_id');
        }

        if ($request->filled('day_key')) {
            $chips[] = __('reports.att_filter_day_key') . ': ' . $this->translateDayKey($request->get('day_key'));
        }

        if ($request->filled('notes')) {
            $chips[] = __('reports.att_filter_notes') . ': ' . $request->get('notes');
        }

        $meta = [
            'title'        => __('reports.attendances_report_title'),
            'org_name'     => $orgName,
            'generated_at' => now('Africa/Cairo')->format('Y-m-d H:i'),
            'total_count'  => $rows->count(),
        ];

        return view('reports.attendances_report.print', [
            'meta'  => $meta,
            'chips' => $chips,
            'kpis'  => $kpis,
            'rows'  => $rows,
        ]);
    }

    private function exportExcel(Request $request)
    {
        $rows = $this->buildQuery($request, false)
            ->orderBy('a.attendance_date', 'desc')
            ->orderBy('a.attendance_time', 'desc')
            ->get();

        $locale = app()->getLocale();
        $isRtl = ($locale === 'ar');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // RTL for Arabic
        if ($isRtl) {
            $sheet->setRightToLeft(true);
        }

        // Headers
        $headers = [
            __('reports.att_col_date'),
            __('reports.att_col_time'),
            __('reports.att_col_branch'),
            __('reports.att_col_member_code'),
            __('reports.att_col_member_name'),
            __('reports.att_col_member_phone'),
            __('reports.att_col_member_status'),
            __('reports.att_col_method'),
            __('reports.att_col_recorded_by'),
            __('reports.att_col_cancel_status'),
            __('reports.att_col_cancelled_at'),
            __('reports.att_col_cancelled_by'),
            __('reports.att_col_plan'),
            __('reports.att_col_sub_start'),
            __('reports.att_col_sub_end'),
            __('reports.att_col_pt_attended'),
            __('reports.att_col_pt_trainer'),
            __('reports.att_col_device'),
            __('reports.att_col_gate'),
            __('reports.att_col_day'),
            __('reports.att_col_notes'),
            __('reports.att_col_guests'),
        ];

        $sheet->fromArray($headers, null, 'A1');

        // Style header
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

        // Freeze first row
        $sheet->freezePane('A2');

        // Data rows
        $rowNum = 2;
        foreach ($rows as $r) {
            $branchName = $this->nameJsonOrText($r->branch_name ?? null, $locale);
            $planName   = $this->nameJsonOrText($r->plan_name ?? null, $locale);

            $memberFullName = trim(($r->member_first_name ?? '') . ' ' . ($r->member_last_name ?? ''));
            $memberPhone = $r->member_phone ?: ($r->member_phone2 ?: ($r->member_whatsapp ?: ''));

            $cancelStatus = ((int)($r->is_cancelled ?? 0) === 1) ? __('reports.att_cancelled') : __('reports.att_not_cancelled');
            $cancelledAt = $r->cancelled_at ? Carbon::parse($r->cancelled_at)->format('Y-m-d H:i') : '-';
            $cancelledBy = $r->cancelled_by_name ?: '-';

            $subStart = !empty($r->sub_start_date) ? Carbon::parse($r->sub_start_date)->format('Y-m-d') : '-';
            $subEnd = !empty($r->sub_end_date) ? Carbon::parse($r->sub_end_date)->format('Y-m-d') : '-';

            $ptAttended = ((int)($r->pt_addon_id ?? 0) > 0) || ((int)($r->is_pt_deducted ?? 0) === 1);
            $ptText = $ptAttended ? __('reports.att_yes') : __('reports.att_no');

            $sheet->fromArray([
                $r->attendance_date ? Carbon::parse($r->attendance_date)->format('Y-m-d') : '-',
                $r->attendance_time ?: '-',
                $branchName ?: '-',
                $r->member_code ?: '-',
                $memberFullName ?: '-',
                $memberPhone ?: '-',
                $this->translateMemberStatus($r->member_status ?? null),
                $this->translateCheckinMethod($r->checkin_method ?? null),
                $r->recorded_by_name ?: '-',
                $cancelStatus,
                $cancelledAt,
                $cancelledBy,
                $planName ?: '-',
                $subStart,
                $subEnd,
                $ptText,
                $r->pt_trainer_name ?: '-',
                $r->device_id ?? '-',
                $r->gate_id ?? '-',
                $this->translateDayKey($r->day_key ?? null),
                $r->notes ?? '-',
                (int)($r->guests_count ?? 0),
            ], null, 'A' . $rowNum);

            $rowNum++;
        }

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Border all data
        $dataRange = 'A1:' . $sheet->getHighestColumn() . ($rowNum - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $fileName = 'attendances_report_' . now('Africa/Cairo')->format('Ymd_His') . '.xlsx';

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ================= Helpers (UI blocks + translations) =================

    private function trFallback(string $key, string $fallback): string
    {
        $t = __($key);
        return ($t === $key) ? $fallback : $t;
    }

    private function buildTwoLines(?string $line1, ?string $line2): string
    {
        $l1 = $line1 ?: '-';
        $l2 = $line2 ?: '-';
        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($l1) . '</span>' .
            '<small class="text-muted">' . e($l2) . '</small>' .
            '</div>';
    }

    private function buildMemberBlock($code, $name, $phone): string
    {
        $code = $code ?: '-';
        $name = $name ?: '-';
        $phone = $phone ?: '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($name) . '</span>' .
            '<small class="text-muted">' . e($code) . '</small>' .
            '<small class="text-muted">' . e($phone) . '</small>' .
            '</div>';
    }

    private function buildCancelBlock(int $isCancelled, $cancelledAt, $cancelledByName): string
    {
        $yes = __('reports.att_cancelled');
        $no  = __('reports.att_not_cancelled');

        $badge = $isCancelled === 1
            ? '<span class="badge bg-danger">' . e($yes) . '</span>'
            : '<span class="badge bg-success">' . e($no) . '</span>';

        $dt = $cancelledAt ? Carbon::parse($cancelledAt)->format('Y-m-d H:i') : '-';
        $by = $cancelledByName ?: '-';

        return '<div class="d-flex flex-column">' .
            $badge .
            '<small class="text-muted">' . e($dt) . '</small>' .
            '<small class="text-muted">' . e($by) . '</small>' .
            '</div>';
    }

    private function buildSubscriptionBlock($planName, $startDate, $endDate): string
    {
        $planName = $planName ?: '-';
        $sd = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : '---';
        $ed = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : '---';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($planName) . '</span>' .
            '<small class="text-muted">' . e($sd . ' ⟶ ' . $ed) . '</small>' .
            '</div>';
    }

    private function buildPtBlock(bool $attended, $trainerName): string
    {
        $yes = __('reports.att_yes');
        $no  = __('reports.att_no');

        if (!$attended) {
            return '<span class="badge bg-secondary">' . e($no) . '</span>';
        }

        $trainerName = $trainerName ?: '-';
        return '<div class="d-flex flex-column">' .
            '<span class="badge bg-info text-dark">' . e($yes) . '</span>' .
            '<small class="text-muted">' . e($trainerName) . '</small>' .
            '</div>';
    }

    private function buildDeviceGateBlock($deviceId, $gateId): string
    {
        $deviceId = ($deviceId !== null && $deviceId !== '') ? $deviceId : '-';
        $gateId = ($gateId !== null && $gateId !== '') ? $gateId : '-';

        $dLbl = __('reports.att_device_label');
        $gLbl = __('reports.att_gate_label');

        return '<div class="d-flex flex-column">' .
            '<small class="text-muted">' . e($dLbl . ': ' . $deviceId) . '</small>' .
            '<small class="text-muted">' . e($gLbl . ': ' . $gateId) . '</small>' .
            '</div>';
    }

    private function normalizeCheckinMethod($method): ?string
    {
        $m = strtolower(trim((string)$method));
        if ($m === '') return null;

        // accept UI values like "Barcode"
        if ($m === 'barcode') return 'barcode';
        if ($m === 'manual') return 'manual';

        // allow Arabic labels if sent mistakenly as value
        $ar = [
            'يدوي' => 'manual',
            'باركود' => 'barcode',
            'الباركود' => 'barcode',
        ];
        if (isset($ar[$method])) return $ar[$method];

        return null;
    }

    private function translateCheckinMethod($method): string
    {
        $m = $this->normalizeCheckinMethod($method);

        if ($m === 'manual') {
            return $this->trFallback('reports.att_method_manual', app()->getLocale() === 'ar' ? 'يدوي' : 'Manual');
        }

        if ($m === 'barcode') {
            return $this->trFallback('reports.att_method_barcode', app()->getLocale() === 'ar' ? 'باركود' : 'Barcode');
        }

        return ((string)$method !== '') ? (string)$method : '-';
    }

    private function normalizeMemberStatus($status): ?string
    {
        $s = strtolower(trim((string)$status));
        if ($s === '') return null;

        if (in_array($s, ['active', 'inactive', 'frozen'], true)) {
            return $s;
        }

        // Allow translated values if UI mistakenly sends label as value
        $ar = [
            'نشط' => 'active',
            'غير نشط' => 'inactive',
            'موقوف' => 'inactive',
            'مجمد' => 'frozen',
        ];
        if (isset($ar[$status])) return $ar[$status];

        return null;
    }

    private function translateMemberStatus($status): string
    {
        $s = $this->normalizeMemberStatus($status);

        if ($s === 'active') {
            return $this->trFallback('reports.att_status_active', app()->getLocale() === 'ar' ? 'نشط' : 'Active');
        }

        if ($s === 'inactive') {
            return $this->trFallback('reports.att_status_inactive', app()->getLocale() === 'ar' ? 'غير نشط' : 'Inactive');
        }

        if ($s === 'frozen') {
            return $this->trFallback('reports.att_status_frozen', app()->getLocale() === 'ar' ? 'مجمد' : 'Frozen');
        }

        return ((string)$status !== '') ? (string)$status : '-';
    }

    private function normalizeDayKey($dayKey): ?string
    {
        $v = trim((string)$dayKey);
        if ($v === '') return null;

        $k = strtolower($v);

        // DB often stores full day name (monday...) or short (mon...)
        $shortToFull = [
            'mon' => 'monday',
            'tue' => 'tuesday',
            'wed' => 'wednesday',
            'thu' => 'thursday',
            'fri' => 'friday',
            'sat' => 'saturday',
            'sun' => 'sunday',
        ];
        if (isset($shortToFull[$k])) return $shortToFull[$k];

        if (in_array($k, ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'], true)) {
            return $k;
        }

        // Arabic inputs (in case dropdown sends label instead of key)
        $arToFull = [
            'الاثنين' => 'monday',
            'الثلاثاء' => 'tuesday',
            'الأربعاء' => 'wednesday',
            'الاربعاء' => 'wednesday',
            'الخميس' => 'thursday',
            'الجمعة' => 'friday',
            'السبت' => 'saturday',
            'الأحد' => 'sunday',
            'الاحد' => 'sunday',
        ];
        if (isset($arToFull[$v])) return $arToFull[$v];

        return $k; // fallback: keep as-is (won't match if DB differs)
    }

    private function translateDayKey($dayKey): string
    {
        $k = $this->normalizeDayKey($dayKey);
        if (empty($k)) return '-';

        $isAr = app()->getLocale() === 'ar';

        $map = [
            'saturday' => $this->trFallback('reports.att_day_sat', $isAr ? 'السبت' : 'Saturday'),
            'sunday' => $this->trFallback('reports.att_day_sun', $isAr ? 'الأحد' : 'Sunday'),
            'monday' => $this->trFallback('reports.att_day_mon', $isAr ? 'الاثنين' : 'Monday'),
            'tuesday' => $this->trFallback('reports.att_day_tue', $isAr ? 'الثلاثاء' : 'Tuesday'),
            'wednesday' => $this->trFallback('reports.att_day_wed', $isAr ? 'الأربعاء' : 'Wednesday'),
            'thursday' => $this->trFallback('reports.att_day_thu', $isAr ? 'الخميس' : 'Thursday'),
            'friday' => $this->trFallback('reports.att_day_fri', $isAr ? 'الجمعة' : 'Friday'),
        ];

        return $map[$k] ?? (string)$dayKey;
    }

    private function memberStatusOptions(): array
    {
        return [
            ['value' => 'active', 'label' => $this->translateMemberStatus('active')],
            ['value' => 'inactive', 'label' => $this->translateMemberStatus('inactive')],
            ['value' => 'frozen', 'label' => $this->translateMemberStatus('frozen')],
        ];
    }

    private function checkinMethodOptions(): array
    {
        return [
            ['value' => 'manual', 'label' => $this->translateCheckinMethod('manual')],
            ['value' => 'barcode', 'label' => $this->translateCheckinMethod('barcode')],
        ];
    }

    private function dayKeyOptions(): array
    {
        // values are canonical keys to match DB (monday..sunday)
        $days = ['saturday','sunday','monday','tuesday','wednesday','thursday','friday'];

        $out = [];
        foreach ($days as $d) {
            $out[] = ['value' => $d, 'label' => $this->translateDayKey($d)];
        }
        return $out;
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
