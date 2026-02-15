<?php

namespace App\Http\Controllers\reports\members_report;

use App\Http\Controllers\Controller;
use App\Models\general\Branch;
use App\Models\general\GeneralSetting;
use App\Models\members\Member;
use App\models\government;
use App\models\City;
use App\models\area;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class members_reportcontroller extends Controller
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

        // Locations (may be big; still loaded once for now)
        $governments = government::query()
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        $cities = City::query()
            ->select('id', 'name', 'id_government')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        $areas = area::query()
            ->select('id', 'name', 'id_city')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        $kpis = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'frozen' => 0,
            'frozen_now' => 0,
            'male' => 0,
            'female' => 0,
            'branches_used' => 0,
            'avg_height' => 0,
            'avg_weight' => 0,
        ];

        $filterOptions = [
            'statuses' => $this->statusOptions(),
            'genders' => $this->genderOptions(),
            'freeze_now' => $this->freezeNowOptions(),
        ];

        return view('reports.members_report.index', [
            'branches' => $branches,
            'governments' => $governments,
            'cities' => $cities,
            'areas' => $areas,
            'kpis' => $kpis,
            'filters' => [
                'member_term' => $request->get('member_term'),

                'branch_ids' => (array)$request->get('branch_ids', []),

                'status' => $request->get('status'),
                'gender' => $request->get('gender'),

                'join_date_from' => $request->get('join_date_from'),
                'join_date_to' => $request->get('join_date_to'),

                'birth_date_from' => $request->get('birth_date_from'),
                'birth_date_to' => $request->get('birth_date_to'),

                'government_id' => $request->get('government_id'),
                'city_id' => $request->get('city_id'),
                'area_id' => $request->get('area_id'),

                'freeze_now' => $request->get('freeze_now'),
                'freeze_from' => $request->get('freeze_from'),
                'freeze_to' => $request->get('freeze_to'),

                'height_from' => $request->get('height_from'),
                'height_to' => $request->get('height_to'),

                'weight_from' => $request->get('weight_from'),
                'weight_to' => $request->get('weight_to'),
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
        $recordsTotal = (clone $baseQuery)->count('m.id');

        $filteredQuery = $this->buildQuery($request, true, $search);
        $recordsFiltered = (clone $filteredQuery)->count('m.id');

        $orderColIndex = (int)data_get($request->input('order', []), '0.column', 0);
        $orderDir = strtolower((string)data_get($request->input('order', []), '0.dir', 'desc'));
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';

        $columnsMap = [
            0  => 'm.first_name',
            1  => 'b.name',
            2  => 'm.status',
            3  => 'm.gender',
            4  => 'm.join_date',
            5  => 'm.birth_date',
            6  => 'g.name',
            7  => 'c.name',
            8  => 'a.name',
            9  => 'm.height',
            10 => 'm.weight',
            11 => 'ua.name',
            12 => 'm.id',
        ];

        $orderBy = $columnsMap[$orderColIndex] ?? 'm.id';

        $rows = $filteredQuery
            ->orderBy($orderBy, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $locale = app()->getLocale();

        $data = [];
        foreach ($rows as $idx => $r) {
            $branchName = $this->nameJsonOrText($r->branch_name ?? null, $locale) ?: '-';
            $govName = $this->nameJsonOrText($r->gov_name ?? null, $locale) ?: '-';
            $cityName = $this->nameJsonOrText($r->city_name ?? null, $locale) ?: '-';
            $areaName = $this->nameJsonOrText($r->area_name ?? null, $locale) ?: '-';

            $memberName = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));

            $data[] = [
                'rownum' => $start + $idx + 1,

                'member_block' => $this->buildMemberBlock(
                    $r->member_code ?? null,
                    $memberName ?: null,
                    $r->phone ?? null,
                    $r->whatsapp ?? null,
                    $r->email ?? null
                ),

                'branch' => $branchName,

                'status_block' => $this->buildStatusBlock($r->status ?? null, $r->freeze_from ?? null, $r->freeze_to ?? null),

                'gender_text' => $this->translateGender($r->gender ?? null),

                'dates_block' => $this->buildDatesBlock($r->join_date ?? null, $r->birth_date ?? null),

                'location_block' => $this->buildLocationBlock($govName, $cityName, $areaName),

                'body_block' => $this->buildBodyBlock($r->height ?? null, $r->weight ?? null),

                'medical_block' => $this->buildMedicalBlock($r->medical_conditions ?? null, $r->allergies ?? null, $r->notes ?? null),

                'added_by' => $r->added_by_name ?: '-',
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
        $q = DB::table('members as m')
            ->whereNull('m.deleted_at')
            ->leftJoin('branches as b', function ($j) {
                $j->on('b.id', '=', 'm.branch_id')->whereNull('b.deleted_at');
            })
            ->leftJoin('government as g', function ($j) {
                $j->on('g.id', '=', 'm.id_government')->whereNull('g.deleted_at');
            })
            ->leftJoin('city as c', function ($j) {
                $j->on('c.id', '=', 'm.id_city')->whereNull('c.deleted_at');
            })
            ->leftJoin('area as a', function ($j) {
                $j->on('a.id', '=', 'm.id_area')->whereNull('a.deleted_at');
            })
            ->leftJoin('users as ua', 'ua.id', '=', 'm.user_add')
            ->select([
                'm.id',
                'm.member_code',
                'm.branch_id',
                'm.first_name',
                'm.last_name',
                'm.gender',
                'm.birth_date',
                'm.phone',
                'm.phone2',
                'm.whatsapp',
                'm.email',
                'm.address',
                'm.id_government',
                'm.id_city',
                'm.id_area',
                'm.join_date',
                'm.status',
                'm.freeze_from',
                'm.freeze_to',
                'm.height',
                'm.weight',
                'm.medical_conditions',
                'm.allergies',
                'm.notes',
                'm.user_add',

                'b.name as branch_name',
                'g.name as gov_name',
                'c.name as city_name',
                'a.name as area_name',
                'ua.name as added_by_name',
            ]);

        $this->applyFilters($q, $request);

        if ($applySearch && $search !== '') {
            $this->applySearch($q, $search);
        }

        return $q;
    }

    private function applyFilters($q, Request $request): void
    {
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
                    ->orWhere('m.whatsapp', 'like', $like)
                    ->orWhere('m.email', 'like', $like);
            });
        }

        $branchIds = (array)$request->get('branch_ids', []);
        $branchIds = array_values(array_filter(array_map('intval', $branchIds)));
        if (!empty($branchIds)) {
            $q->whereIn('m.branch_id', $branchIds);
        }

        if ($request->filled('status')) {
            $st = $this->normalizeStatus($request->get('status'));
            if (!empty($st)) {
                $q->where('m.status', $st);
            }
        }

        if ($request->filled('gender')) {
            $g = $this->normalizeGender($request->get('gender'));
            if (!empty($g)) {
                $q->where('m.gender', $g);
            }
        }

        if ($request->filled('join_date_from')) {
            $q->whereDate('m.join_date', '>=', $request->get('join_date_from'));
        }
        if ($request->filled('join_date_to')) {
            $q->whereDate('m.join_date', '<=', $request->get('join_date_to'));
        }

        if ($request->filled('birth_date_from')) {
            $q->whereDate('m.birth_date', '>=', $request->get('birth_date_from'));
        }
        if ($request->filled('birth_date_to')) {
            $q->whereDate('m.birth_date', '<=', $request->get('birth_date_to'));
        }

        if ($request->filled('government_id')) {
            $q->where('m.id_government', (int)$request->get('government_id'));
        }
        if ($request->filled('city_id')) {
            $q->where('m.id_city', (int)$request->get('city_id'));
        }
        if ($request->filled('area_id')) {
            $q->where('m.id_area', (int)$request->get('area_id'));
        }

        // Freeze Now (based on original fields)
        if ($request->filled('freeze_now') && in_array((string)$request->get('freeze_now'), ['0', '1'], true)) {
            $want = (int)$request->get('freeze_now');
            if ($want === 1) {
                $today = Carbon::today()->format('Y-m-d');
                $q->where('m.status', 'frozen')
                    ->whereNotNull('m.freeze_from')
                    ->whereNotNull('m.freeze_to')
                    ->whereDate('m.freeze_from', '<=', $today)
                    ->whereDate('m.freeze_to', '>=', $today);
            } else {
                // Not frozen now = either status != frozen or outside range or missing dates
                $today = Carbon::today()->format('Y-m-d');
                $q->where(function ($w) use ($today) {
                    $w->where('m.status', '!=', 'frozen')
                        ->orWhereNull('m.freeze_from')
                        ->orWhereNull('m.freeze_to')
                        ->orWhereDate('m.freeze_from', '>', $today)
                        ->orWhereDate('m.freeze_to', '<', $today);
                });
            }
        }

        if ($request->filled('freeze_from')) {
            $q->whereDate('m.freeze_from', '>=', $request->get('freeze_from'));
        }
        if ($request->filled('freeze_to')) {
            $q->whereDate('m.freeze_to', '<=', $request->get('freeze_to'));
        }

        if ($request->filled('height_from')) {
            $q->where('m.height', '>=', (float)$request->get('height_from'));
        }
        if ($request->filled('height_to')) {
            $q->where('m.height', '<=', (float)$request->get('height_to'));
        }

        if ($request->filled('weight_from')) {
            $q->where('m.weight', '>=', (float)$request->get('weight_from'));
        }
        if ($request->filled('weight_to')) {
            $q->where('m.weight', '<=', (float)$request->get('weight_to'));
        }
    }

    private function applySearch($q, string $search): void
    {
        $s = trim($search);
        $like = '%' . $s . '%';

        $q->where(function ($w) use ($s, $like) {
            if (is_numeric($s)) {
                $w->orWhere('m.id', (int)$s)
                    ->orWhere('m.branch_id', (int)$s);
            }

            $w->orWhere('m.member_code', 'like', $like)
                ->orWhere('m.first_name', 'like', $like)
                ->orWhere('m.last_name', 'like', $like)
                ->orWhere(DB::raw("CONCAT(COALESCE(m.first_name,''),' ',COALESCE(m.last_name,''))"), 'like', $like)
                ->orWhere('m.phone', 'like', $like)
                ->orWhere('m.phone2', 'like', $like)
                ->orWhere('m.whatsapp', 'like', $like)
                ->orWhere('m.email', 'like', $like)
                ->orWhere('m.notes', 'like', $like)
                ->orWhere('b.name', 'like', $like)
                ->orWhere('g.name', 'like', $like)
                ->orWhere('c.name', 'like', $like)
                ->orWhere('a.name', 'like', $like)
                ->orWhere(DB::raw("COALESCE(ua.name,'')"), 'like', $like);
        });
    }

    private function computeKpis(Request $request): array
    {
        $q = $this->buildQuery($request, false);

        $total = (clone $q)->count('m.id');

        $active = (clone $q)->where('m.status', 'active')->count('m.id');
        $inactive = (clone $q)->where('m.status', 'inactive')->count('m.id');
        $frozen = (clone $q)->where('m.status', 'frozen')->count('m.id');

        $today = Carbon::today()->format('Y-m-d');
        $frozenNow = (clone $q)
            ->where('m.status', 'frozen')
            ->whereNotNull('m.freeze_from')
            ->whereNotNull('m.freeze_to')
            ->whereDate('m.freeze_from', '<=', $today)
            ->whereDate('m.freeze_to', '>=', $today)
            ->count('m.id');

        $male = (clone $q)->where('m.gender', 'male')->count('m.id');
        $female = (clone $q)->where('m.gender', 'female')->count('m.id');

        $branchesUsed = (clone $q)->distinct('m.branch_id')->count('m.branch_id');

        $avgHeight = (float)((clone $q)->avg('m.height') ?? 0);
        $avgWeight = (float)((clone $q)->avg('m.weight') ?? 0);

        return [
            'total' => (int)$total,
            'active' => (int)$active,
            'inactive' => (int)$inactive,
            'frozen' => (int)$frozen,
            'frozen_now' => (int)$frozenNow,
            'male' => (int)$male,
            'female' => (int)$female,
            'branches_used' => (int)$branchesUsed,
            'avg_height' => round($avgHeight, 2),
            'avg_weight' => round($avgWeight, 2),
        ];
    }

    private function print(Request $request)
    {
        $rows = $this->buildQuery($request, false)
            ->orderBy('m.id', 'desc')
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

        if ($request->filled('member_term')) {
            $chips[] = __('reports.mem_filter_member') . ': ' . $request->get('member_term');
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
            $chips[] = __('reports.mem_filter_branches') . ': ' . ($branchNames ?: '---');
        }

        if ($request->filled('status')) {
            $chips[] = __('reports.mem_filter_status') . ': ' . $this->translateStatus($request->get('status'));
        }
        if ($request->filled('gender')) {
            $chips[] = __('reports.mem_filter_gender') . ': ' . $this->translateGender($request->get('gender'));
        }

        if ($request->filled('government_id')) {
            $g = government::query()->where('id', (int)$request->get('government_id'))->first();
            $gn = $g ? (method_exists($g, 'getTranslation') ? $g->getTranslation('name', app()->getLocale()) : ($g->name ?? '')) : '';
            $chips[] = __('reports.mem_filter_government') . ': ' . ($gn ?: '---');
        }
        if ($request->filled('city_id')) {
            $c = City::query()->where('id', (int)$request->get('city_id'))->first();
            $cn = $c ? (method_exists($c, 'getTranslation') ? $c->getTranslation('name', app()->getLocale()) : ($c->name ?? '')) : '';
            $chips[] = __('reports.mem_filter_city') . ': ' . ($cn ?: '---');
        }
        if ($request->filled('area_id')) {
            $a = area::query()->where('id', (int)$request->get('area_id'))->first();
            $an = $a ? (method_exists($a, 'getTranslation') ? $a->getTranslation('name', app()->getLocale()) : ($a->name ?? '')) : '';
            $chips[] = __('reports.mem_filter_area') . ': ' . ($an ?: '---');
        }

        if ($request->filled('join_date_from') || $request->filled('join_date_to')) {
            $chips[] = __('reports.mem_filter_join_date') . ': ' .
                ($request->get('join_date_from') ?: '---') . ' ⟶ ' . ($request->get('join_date_to') ?: '---');
        }
        if ($request->filled('birth_date_from') || $request->filled('birth_date_to')) {
            $chips[] = __('reports.mem_filter_birth_date') . ': ' .
                ($request->get('birth_date_from') ?: '---') . ' ⟶ ' . ($request->get('birth_date_to') ?: '---');
        }

        if ($request->filled('freeze_now')) {
            $chips[] = __('reports.mem_filter_freeze_now') . ': ' . $this->translateYesNo($request->get('freeze_now'));
        }
        if ($request->filled('freeze_from') || $request->filled('freeze_to')) {
            $chips[] = __('reports.mem_filter_freeze_range') . ': ' .
                ($request->get('freeze_from') ?: '---') . ' ⟶ ' . ($request->get('freeze_to') ?: '---');
        }

        if ($request->filled('height_from') || $request->filled('height_to')) {
            $chips[] = __('reports.mem_filter_height') . ': ' .
                ($request->get('height_from') ?: '---') . ' ⟶ ' . ($request->get('height_to') ?: '---');
        }
        if ($request->filled('weight_from') || $request->filled('weight_to')) {
            $chips[] = __('reports.mem_filter_weight') . ': ' .
                ($request->get('weight_from') ?: '---') . ' ⟶ ' . ($request->get('weight_to') ?: '---');
        }

        $meta = [
            'title' => __('reports.members_report_title'),
            'org_name' => $orgName,
            'generated_at' => now('Africa/Cairo')->format('Y-m-d H:i'),
            'total_count' => $rows->count(),
        ];

        return view('reports.members_report.print', [
            'meta' => $meta,
            'chips' => $chips,
            'kpis' => $kpis,
            'rows' => $rows,
        ]);
    }

    private function exportExcel(Request $request)
    {
        $rows = $this->buildQuery($request, false)
            ->orderBy('m.id', 'desc')
            ->get();

        $locale = app()->getLocale();
        $isRtl = ($locale === 'ar');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($isRtl) {
            $sheet->setRightToLeft(true);
        }

        $headers = [
            __('reports.mem_col_member_code'),
            __('reports.mem_col_name'),
            __('reports.mem_col_branch'),
            __('reports.mem_col_status'),
            __('reports.mem_col_gender'),
            __('reports.mem_col_join_date'),
            __('reports.mem_col_birth_date'),
            __('reports.mem_col_freeze_range'),
            __('reports.mem_col_government'),
            __('reports.mem_col_city'),
            __('reports.mem_col_area'),
            __('reports.mem_col_phone'),
            __('reports.mem_col_whatsapp'),
            __('reports.mem_col_email'),
            __('reports.mem_col_height'),
            __('reports.mem_col_weight'),
            __('reports.mem_col_medical_conditions'),
            __('reports.mem_col_allergies'),
            __('reports.mem_col_notes'),
            __('reports.mem_col_added_by'),
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
            $memberName = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: '-';

            $branchName = $this->nameJsonOrText($r->branch_name ?? null, $locale) ?: '-';
            $govName = $this->nameJsonOrText($r->gov_name ?? null, $locale) ?: '-';
            $cityName = $this->nameJsonOrText($r->city_name ?? null, $locale) ?: '-';
            $areaName = $this->nameJsonOrText($r->area_name ?? null, $locale) ?: '-';

            $freezeRange = '-';
            if (!empty($r->freeze_from) || !empty($r->freeze_to)) {
                $freezeRange = ($r->freeze_from ?: '---') . ' ⟶ ' . ($r->freeze_to ?: '---');
            }

            $sheet->fromArray([
                $r->member_code ?: '-',
                $memberName,
                $branchName,
                $this->translateStatus($r->status ?? null),
                $this->translateGender($r->gender ?? null),
                !empty($r->join_date) ? Carbon::parse($r->join_date)->format('Y-m-d') : '-',
                !empty($r->birth_date) ? Carbon::parse($r->birth_date)->format('Y-m-d') : '-',
                $freezeRange,
                $govName,
                $cityName,
                $areaName,
                $r->phone ?: '-',
                $r->whatsapp ?: '-',
                $r->email ?: '-',
                $r->height !== null ? (float)$r->height : '-',
                $r->weight !== null ? (float)$r->weight : '-',
                $r->medical_conditions ?: '-',
                $r->allergies ?: '-',
                $r->notes ?: '-',
                $r->added_by_name ?: '-',
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

        $fileName = 'members_report_' . now('Africa/Cairo')->format('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ================= UI blocks =================

    private function buildMemberBlock($code, $name, $phone, $whatsapp, $email): string
    {
        $code = $code ?: '-';
        $name = $name ?: '-';

        $phone = $phone ?: '-';
        $whatsapp = $whatsapp ?: '-';
        $email = $email ?: '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($name) . '</span>' .
            '<small class="text-muted">' . e(__('reports.mem_col_member_code') . ': ' . $code) . '</small>' .
            '<small class="text-muted">' . e(__('reports.mem_col_phone') . ': ' . $phone) . '</small>' .
            '<small class="text-muted">' . e(__('reports.mem_col_whatsapp') . ': ' . $whatsapp) . '</small>' .
            '<small class="text-muted">' . e(__('reports.mem_col_email') . ': ' . $email) . '</small>' .
            '</div>';
    }

    private function buildDatesBlock($join, $birth): string
    {
        $joinTxt = !empty($join) ? Carbon::parse($join)->format('Y-m-d') : '-';
        $birthTxt = !empty($birth) ? Carbon::parse($birth)->format('Y-m-d') : '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e(__('reports.mem_col_join_date') . ': ' . $joinTxt) . '</span>' .
            '<small class="text-muted">' . e(__('reports.mem_col_birth_date') . ': ' . $birthTxt) . '</small>' .
            '</div>';
    }

    private function buildLocationBlock($gov, $city, $area): string
    {
        $gov = $gov ?: '-';
        $city = $city ?: '-';
        $area = $area ?: '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($gov) . '</span>' .
            '<small class="text-muted">' . e($city) . '</small>' .
            '<small class="text-muted">' . e($area) . '</small>' .
            '</div>';
    }

    private function buildBodyBlock($height, $weight): string
    {
        $h = ($height !== null && $height !== '') ? (string)$height : '-';
        $w = ($weight !== null && $weight !== '') ? (string)$weight : '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e(__('reports.mem_col_height') . ': ' . $h) . '</span>' .
            '<small class="text-muted">' . e(__('reports.mem_col_weight') . ': ' . $w) . '</small>' .
            '</div>';
    }

    private function buildMedicalBlock($medical, $allergies, $notes): string
    {
        $medical = $medical ?: '-';
        $allergies = $allergies ?: '-';

        $notes = $notes ?: '-';
        $notesShort = mb_strlen($notes) > 60 ? (mb_substr($notes, 0, 60) . '...') : $notes;

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e(__('reports.mem_col_medical_conditions') . ': ' . $medical) . '</span>' .
            '<small class="text-muted">' . e(__('reports.mem_col_allergies') . ': ' . $allergies) . '</small>' .
            '<small class="text-muted">' . e(__('reports.mem_col_notes') . ': ' . $notesShort) . '</small>' .
            '</div>';
    }

    private function buildStatusBlock($status, $freezeFrom, $freezeTo): string
    {
        $st = $this->normalizeStatus($status);

        $label = $this->translateStatus($st);

        $class = 'bg-secondary';
        if ($st === 'active') $class = 'bg-success';
        if ($st === 'inactive') $class = 'bg-danger';
        if ($st === 'frozen') $class = 'bg-warning text-dark';

        $freezeLine = '';
        if ($st === 'frozen') {
            $from = !empty($freezeFrom) ? Carbon::parse($freezeFrom)->format('Y-m-d') : '---';
            $to = !empty($freezeTo) ? Carbon::parse($freezeTo)->format('Y-m-d') : '---';

            $today = Carbon::today();
            $isFrozenNow = (!empty($freezeFrom) && !empty($freezeTo)) ? $today->between(Carbon::parse($freezeFrom), Carbon::parse($freezeTo)) : false;

            $freezeLine = '<small class="text-muted">' .
                e(__('reports.mem_col_freeze_range') . ': ' . $from . ' ⟶ ' . $to) .
                '</small>';

            if ($isFrozenNow) {
                $freezeLine .= '<small class="text-muted">' . e(__('reports.mem_frozen_now_badge')) . '</small>';
            }
        }

        return '<div class="d-flex flex-column">' .
            '<span class="badge ' . $class . '">' . e($label) . '</span>' .
            $freezeLine .
            '</div>';
    }

    // ================= Enums (canonical) =================

    private function normalizeStatus($v): ?string
    {
        $s = strtolower(trim((string)$v));
        if ($s === '') return null;

        $allowed = ['active', 'inactive', 'frozen'];
        return in_array($s, $allowed, true) ? $s : null;
    }

    private function translateStatus($v): string
    {
        $k = $this->normalizeStatus($v);
        $isAr = app()->getLocale() === 'ar';

        if ($k === 'active') return $this->trFallback('reports.mem_status_active', $isAr ? 'نشط' : 'Active');
        if ($k === 'inactive') return $this->trFallback('reports.mem_status_inactive', $isAr ? 'غير نشط' : 'Inactive');
        if ($k === 'frozen') return $this->trFallback('reports.mem_status_frozen', $isAr ? 'مجمد' : 'Frozen');

        return ((string)$v !== '') ? (string)$v : '-';
    }

    private function normalizeGender($v): ?string
    {
        $s = strtolower(trim((string)$v));
        if ($s === '') return null;

        return in_array($s, ['male', 'female'], true) ? $s : null;
    }

    private function translateGender($v): string
    {
        $k = $this->normalizeGender($v);
        $isAr = app()->getLocale() === 'ar';

        if ($k === 'male') return $this->trFallback('reports.mem_gender_male', $isAr ? 'ذكر' : 'Male');
        if ($k === 'female') return $this->trFallback('reports.mem_gender_female', $isAr ? 'أنثى' : 'Female');

        return ((string)$v !== '') ? (string)$v : '-';
    }

    private function translateYesNo($v): string
    {
        $isAr = app()->getLocale() === 'ar';
        if ((string)$v === '1') return $this->trFallback('reports.mem_yes', $isAr ? 'نعم' : 'Yes');
        if ((string)$v === '0') return $this->trFallback('reports.mem_no', $isAr ? 'لا' : 'No');
        return '-';
    }

    // ================= Options for selects =================

    private function statusOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.mem_all')],
            ['value' => 'active', 'label' => $this->translateStatus('active')],
            ['value' => 'inactive', 'label' => $this->translateStatus('inactive')],
            ['value' => 'frozen', 'label' => $this->translateStatus('frozen')],
        ];
    }

    private function genderOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.mem_all')],
            ['value' => 'male', 'label' => $this->translateGender('male')],
            ['value' => 'female', 'label' => $this->translateGender('female')],
        ];
    }

    private function freezeNowOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.mem_all')],
            ['value' => '1', 'label' => $this->translateYesNo(1)],
            ['value' => '0', 'label' => $this->translateYesNo(0)],
        ];
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
