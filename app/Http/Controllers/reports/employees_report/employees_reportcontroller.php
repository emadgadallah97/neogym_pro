<?php

namespace App\Http\Controllers\reports\employees_report;

use App\Http\Controllers\Controller;
use App\Models\employee\Job;
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

class employees_reportcontroller extends Controller
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

        $jobs = Job::query()
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        // We will load KPIs by AJAX after applying filters
        $kpis = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'coaches' => 0,
            'male' => 0,
            'female' => 0,
            'jobs_used' => 0,
            'branches_used' => 0,
            'avg_base_salary' => 0,
        ];

        $filterOptions = [
            'statuses' => $this->statusOptions(),
            'genders' => $this->genderOptions(),
            'coach_flags' => $this->coachOptions(),

            // IMPORTANT: values are canonical DB values, labels are translated
            'compensation_types' => $this->compensationTypeOptions(),
            'commission_value_types' => $this->commissionValueTypeOptions(),
            'salary_transfer_methods' => $this->salaryTransferMethodOptions(),
        ];

        return view('reports.employees_report.index', [
            'branches' => $branches,
            'jobs' => $jobs,
            'kpis' => $kpis,
            'filters' => [
                'employee_term' => $request->get('employee_term'),
                'branch_ids' => (array)$request->get('branch_ids', []),
                'job_id' => $request->get('job_id'),

                'status' => $request->get('status'),
                'gender' => $request->get('gender'),
                'is_coach' => $request->get('is_coach'),

                'compensation_type' => $request->get('compensation_type'),
                'commission_value_type' => $request->get('commission_value_type'),
                'salary_transfer_method' => $request->get('salary_transfer_method'),

                'birth_date_from' => $request->get('birth_date_from'),
                'birth_date_to' => $request->get('birth_date_to'),

                'years_exp_from' => $request->get('years_exp_from'),
                'years_exp_to' => $request->get('years_exp_to'),

                'base_salary_from' => $request->get('base_salary_from'),
                'base_salary_to' => $request->get('base_salary_to'),

                'specialization' => $request->get('specialization'),
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
        $recordsTotal = (clone $baseQuery)->count('e.id');

        $filteredQuery = $this->buildQuery($request, true, $search);
        $recordsFiltered = (clone $filteredQuery)->count('e.id');

        $orderColIndex = (int)data_get($request->input('order', []), '0.column', 0);
        $orderDir = strtolower((string)data_get($request->input('order', []), '0.dir', 'desc'));
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';

        $columnsMap = [
            0  => 'e.first_name',
            1  => 'j.name',
            2  => 'pb.name',
            3  => 'e.gender',
            4  => 'e.status',
            5  => 'e.is_coach',
            6  => 'e.compensation_type',
            7  => 'e.base_salary',
            8  => 'e.years_experience',
            9  => 'ua.name',
            10 => 'e.id',
        ];

        $orderBy = $columnsMap[$orderColIndex] ?? 'e.id';

        $rows = $filteredQuery
            ->orderBy($orderBy, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $locale = app()->getLocale();

        $data = [];
        foreach ($rows as $idx => $r) {
            $jobName = $this->nameJsonOrText($r->job_name ?? null, $locale);
            $primaryBranchName = $this->nameJsonOrText($r->primary_branch_name ?? null, $locale);

            $employeeName = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
            $phone = $r->phone_1 ?: ($r->phone_2 ?: ($r->whatsapp ?: null));

            $branchesList = $this->concatNamesJsonToTextList($r->branches_names_concat ?? '', $locale);
            $branchesCount = (int)($r->branches_count ?? 0);

            $data[] = [
                'rownum' => $start + $idx + 1,

                'employee_block' => $this->buildEmployeeBlock(
                    $r->code ?? null,
                    $employeeName ?: null,
                    $phone ?: null,
                    $r->email ?? null
                ),

                'job' => $jobName ?: '-',

                'branches_block' => $this->buildBranchesBlock(
                    $primaryBranchName ?: null,
                    $branchesCount,
                    $branchesList
                ),

                'gender_text' => $this->translateGender($r->gender ?? null),
                'status_block' => $this->buildStatusBlock($r->status ?? null),
                'coach_text' => $this->translateCoachFlag($r->is_coach ?? null),

                'comp_block' => $this->buildCompensationBlock(
                    $r->compensation_type ?? null,
                    $r->base_salary ?? null,
                    $r->commission_value_type ?? null,
                    $r->commission_percent ?? null,
                    $r->commission_fixed ?? null
                ),

                'transfer_block' => $this->buildTransferBlock(
                    $r->salary_transfer_method ?? null,
                    $r->salary_transfer_details ?? null
                ),

                'experience_text' => $this->buildExperienceText(
                    $r->specialization ?? null,
                    $r->years_experience ?? null
                ),

                'added_by' => $r->added_by_name ?: '-',

                'bio' => $r->bio ?: '-',
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
        $q = DB::table('employees as e')
            ->whereNull('e.deleted_at')
            ->leftJoin('jobs as j', function ($j) {
                $j->on('j.id', '=', 'e.job_id')->whereNull('j.deleted_at');
            })
            ->leftJoin('users as ua', 'ua.id', '=', 'e.user_add')
            ->leftJoin('employee_branch as ebp', function ($j) {
                $j->on('ebp.employee_id', '=', 'e.id')->where('ebp.is_primary', '=', 1);
            })
            ->leftJoin('branches as pb', function ($j) {
                $j->on('pb.id', '=', 'ebp.branch_id')->whereNull('pb.deleted_at');
            })
            ->select([
                'e.id',
                'e.code',
                'e.first_name',
                'e.last_name',
                'e.job_id',
                'e.gender',
                'e.birth_date',
                'e.phone_1',
                'e.phone_2',
                'e.whatsapp',
                'e.email',
                'e.specialization',
                'e.years_experience',
                'e.bio',
                'e.compensation_type',
                'e.base_salary',
                'e.commission_value_type',
                'e.commission_percent',
                'e.commission_fixed',
                'e.salary_transfer_method',
                'e.salary_transfer_details',
                'e.status',
                'e.user_add',
                'e.is_coach',

                'j.name as job_name',
                'ua.name as added_by_name',
                'pb.name as primary_branch_name',

                DB::raw("(SELECT COUNT(*) FROM employee_branch eb2 WHERE eb2.employee_id = e.id) as branches_count"),
                DB::raw("(SELECT GROUP_CONCAT(b2.name SEPARATOR '||')
                          FROM employee_branch eb3
                          JOIN branches b2 ON b2.id = eb3.branch_id AND b2.deleted_at IS NULL
                          WHERE eb3.employee_id = e.id) as branches_names_concat"),
            ]);

        $this->applyFilters($q, $request);

        if ($applySearch && $search !== '') {
            $this->applySearch($q, $search);
        }

        return $q;
    }

    private function applyFilters($q, Request $request): void
    {
        if ($request->filled('employee_term')) {
            $term = trim((string)$request->get('employee_term'));
            $like = '%' . $term . '%';

            $q->where(function ($w) use ($like) {
                $w->where('e.code', 'like', $like)
                    ->orWhere('e.first_name', 'like', $like)
                    ->orWhere('e.last_name', 'like', $like)
                    ->orWhere(DB::raw("CONCAT(COALESCE(e.first_name,''),' ',COALESCE(e.last_name,''))"), 'like', $like)
                    ->orWhere('e.phone_1', 'like', $like)
                    ->orWhere('e.phone_2', 'like', $like)
                    ->orWhere('e.whatsapp', 'like', $like)
                    ->orWhere('e.email', 'like', $like)
                    ->orWhere('e.specialization', 'like', $like);
            });
        }

        $branchIds = (array)$request->get('branch_ids', []);
        $branchIds = array_values(array_filter(array_map('intval', $branchIds)));
        if (!empty($branchIds)) {
            $q->whereExists(function ($sub) use ($branchIds) {
                $sub->select(DB::raw(1))
                    ->from('employee_branch as ebf')
                    ->whereColumn('ebf.employee_id', 'e.id')
                    ->whereIn('ebf.branch_id', $branchIds);
            });
        }

        if ($request->filled('job_id')) {
            $q->where('e.job_id', (int)$request->get('job_id'));
        }

        if ($request->filled('status')) {
            $st = $this->normalizeStatus($request->get('status'));
            if ($st !== null) {
                $q->where('e.status', $st);
            }
        }

        if ($request->filled('gender')) {
            $g = $this->normalizeGender($request->get('gender'));
            if (!empty($g)) {
                $q->where('e.gender', $g);
            }
        }

        if ($request->filled('is_coach') && in_array((string)$request->get('is_coach'), ['0', '1'], true)) {
            $q->where('e.is_coach', (int)$request->get('is_coach'));
        }

        // ===== FIXED: filter by original canonical DB values =====
        if ($request->filled('compensation_type')) {
            $ct = $this->normalizeCompensationType($request->get('compensation_type'));
            if (!empty($ct)) {
                $q->where('e.compensation_type', $ct);
            }
        }

        if ($request->filled('commission_value_type')) {
            $cv = $this->normalizeCommissionValueType($request->get('commission_value_type'));
            if (!empty($cv)) {
                $q->where('e.commission_value_type', $cv);
            }
        }

        if ($request->filled('salary_transfer_method')) {
            $stm = $this->normalizeSalaryTransferMethod($request->get('salary_transfer_method'));
            if (!empty($stm)) {
                $q->where('e.salary_transfer_method', $stm);
            }
        }
        // ========================================================

        if ($request->filled('birth_date_from')) {
            $q->whereDate('e.birth_date', '>=', $request->get('birth_date_from'));
        }
        if ($request->filled('birth_date_to')) {
            $q->whereDate('e.birth_date', '<=', $request->get('birth_date_to'));
        }

        if ($request->filled('years_exp_from')) {
            $q->where('e.years_experience', '>=', (float)$request->get('years_exp_from'));
        }
        if ($request->filled('years_exp_to')) {
            $q->where('e.years_experience', '<=', (float)$request->get('years_exp_to'));
        }

        if ($request->filled('base_salary_from')) {
            $q->where('e.base_salary', '>=', (float)$request->get('base_salary_from'));
        }
        if ($request->filled('base_salary_to')) {
            $q->where('e.base_salary', '<=', (float)$request->get('base_salary_to'));
        }

        if ($request->filled('specialization')) {
            $like = '%' . trim((string)$request->get('specialization')) . '%';
            $q->where('e.specialization', 'like', $like);
        }
    }

    private function applySearch($q, string $search): void
    {
        $s = trim($search);
        $like = '%' . $s . '%';

        $q->where(function ($w) use ($s, $like) {
            if (is_numeric($s)) {
                $w->orWhere('e.id', (int)$s)
                    ->orWhere('e.job_id', (int)$s)
                    ->orWhere('e.base_salary', (float)$s)
                    ->orWhere('e.years_experience', (float)$s);
            }

            $w->orWhere('e.code', 'like', $like)
                ->orWhere('e.first_name', 'like', $like)
                ->orWhere('e.last_name', 'like', $like)
                ->orWhere(DB::raw("CONCAT(COALESCE(e.first_name,''),' ',COALESCE(e.last_name,''))"), 'like', $like)
                ->orWhere('e.phone_1', 'like', $like)
                ->orWhere('e.phone_2', 'like', $like)
                ->orWhere('e.whatsapp', 'like', $like)
                ->orWhere('e.email', 'like', $like)
                ->orWhere('e.specialization', 'like', $like)
                ->orWhere('e.bio', 'like', $like)
                ->orWhere(DB::raw("COALESCE(ua.name,'')"), 'like', $like)
                ->orWhere('j.name', 'like', $like)
                ->orWhere('pb.name', 'like', $like);
        });
    }

    private function computeKpis(Request $request): array
    {
        $q = $this->buildQuery($request, false);

        $total = (clone $q)->count('e.id');

        $active = (clone $q)->where('e.status', 1)->count('e.id');
        $inactive = max(0, (int)$total - (int)$active);

        $coaches = (clone $q)->where('e.is_coach', 1)->count('e.id');

        $male = (clone $q)->where('e.gender', 'male')->count('e.id');
        $female = (clone $q)->where('e.gender', 'female')->count('e.id');

        $jobsUsed = (clone $q)->distinct('e.job_id')->count('e.job_id');

        $avgBaseSalary = (float)((clone $q)->avg('e.base_salary') ?? 0);

        $ids = (clone $q)->limit(50000)->pluck('e.id')->toArray();
        $branchesUsed = 0;
        if (!empty($ids)) {
            $branchesUsed = (int)DB::table('employee_branch as eb')
                ->whereIn('eb.employee_id', $ids)
                ->distinct('eb.branch_id')
                ->count('eb.branch_id');
        }

        return [
            'total' => (int)$total,
            'active' => (int)$active,
            'inactive' => (int)$inactive,
            'coaches' => (int)$coaches,
            'male' => (int)$male,
            'female' => (int)$female,
            'jobs_used' => (int)$jobsUsed,
            'branches_used' => (int)$branchesUsed,
            'avg_base_salary' => round($avgBaseSalary, 2),
        ];
    }

    private function print(Request $request)
    {
        $rows = $this->buildQuery($request, false)
            ->orderBy('e.id', 'desc')
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

        if ($request->filled('employee_term')) {
            $chips[] = __('reports.emp_filter_employee') . ': ' . $request->get('employee_term');
        }

        if (!empty((array)$request->get('branch_ids', []))) {
            $ids = array_values(array_filter(array_map('intval', (array)$request->get('branch_ids', []))));
            $branchNames = Branch::query()
                ->whereIn('id', $ids)
                ->get()
                ->map(function ($b) {
                    return method_exists($b, 'getTranslation') ? $b->getTranslation('name', app()->getLocale()) : ($b->name ?? '');
                })
                ->filter()
                ->values()
                ->implode('، ');
            $chips[] = __('reports.emp_filter_branches') . ': ' . ($branchNames ?: '---');
        }

        if ($request->filled('job_id')) {
            $job = Job::query()->where('id', (int)$request->get('job_id'))->first();
            $jn = '-';
            if ($job) {
                $jn = method_exists($job, 'getTranslation') ? $job->getTranslation('name', app()->getLocale()) : ($job->name ?? '-');
            }
            $chips[] = __('reports.emp_filter_job') . ': ' . ($jn ?: '---');
        }

        if ($request->filled('status')) {
            $chips[] = __('reports.emp_filter_status') . ': ' . $this->translateStatus($request->get('status'));
        }
        if ($request->filled('gender')) {
            $chips[] = __('reports.emp_filter_gender') . ': ' . $this->translateGender($request->get('gender'));
        }
        if ($request->filled('is_coach')) {
            $chips[] = __('reports.emp_filter_is_coach') . ': ' . $this->translateCoachFlag($request->get('is_coach'));
        }

        // show translated labels, but filters are on original values
        if ($request->filled('compensation_type')) {
            $chips[] = __('reports.emp_filter_compensation_type') . ': ' . $this->translateCompensationType($request->get('compensation_type'));
        }
        if ($request->filled('commission_value_type')) {
            $chips[] = __('reports.emp_filter_commission_value_type') . ': ' . $this->translateCommissionValueType($request->get('commission_value_type'));
        }
        if ($request->filled('salary_transfer_method')) {
            $chips[] = __('reports.emp_filter_salary_transfer_method') . ': ' . $this->translateSalaryTransferMethod($request->get('salary_transfer_method'));
        }

        if ($request->filled('birth_date_from') || $request->filled('birth_date_to')) {
            $chips[] = __('reports.emp_filter_birth_date') . ': ' .
                ($request->get('birth_date_from') ?: '---') . ' ⟶ ' . ($request->get('birth_date_to') ?: '---');
        }
        if ($request->filled('years_exp_from') || $request->filled('years_exp_to')) {
            $chips[] = __('reports.emp_filter_years_experience') . ': ' .
                ($request->get('years_exp_from') ?: '---') . ' ⟶ ' . ($request->get('years_exp_to') ?: '---');
        }
        if ($request->filled('base_salary_from') || $request->filled('base_salary_to')) {
            $chips[] = __('reports.emp_filter_base_salary') . ': ' .
                ($request->get('base_salary_from') ?: '---') . ' ⟶ ' . ($request->get('base_salary_to') ?: '---');
        }

        if ($request->filled('specialization')) {
            $chips[] = __('reports.emp_filter_specialization') . ': ' . $request->get('specialization');
        }

        $meta = [
            'title' => __('reports.employees_report_title'),
            'org_name' => $orgName,
            'generated_at' => now('Africa/Cairo')->format('Y-m-d H:i'),
            'total_count' => $rows->count(),
        ];

        return view('reports.employees_report.print', [
            'meta' => $meta,
            'chips' => $chips,
            'kpis' => $kpis,
            'rows' => $rows,
        ]);
    }

    private function exportExcel(Request $request)
    {
        $rows = $this->buildQuery($request, false)
            ->orderBy('e.id', 'desc')
            ->get();

        $locale = app()->getLocale();
        $isRtl = ($locale === 'ar');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($isRtl) {
            $sheet->setRightToLeft(true);
        }

        $headers = [
            __('reports.emp_col_code'),
            __('reports.emp_col_name'),
            __('reports.emp_col_job'),
            __('reports.emp_col_primary_branch'),
            __('reports.emp_col_branches'),
            __('reports.emp_col_gender'),
            __('reports.emp_col_status'),
            __('reports.emp_col_is_coach'),
            __('reports.emp_col_compensation_type'),
            __('reports.emp_col_base_salary'),
            __('reports.emp_col_commission_value_type'),
            __('reports.emp_col_commission_percent'),
            __('reports.emp_col_commission_fixed'),
            __('reports.emp_col_salary_transfer_method'),
            __('reports.emp_col_salary_transfer_details'),
            __('reports.emp_col_specialization'),
            __('reports.emp_col_years_experience'),
            __('reports.emp_col_birth_date'),
            __('reports.emp_col_phone'),
            __('reports.emp_col_email'),
            __('reports.emp_col_added_by'),
            __('reports.emp_col_bio'),
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
            $employeeName = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: '-';

            $jobName = $this->nameJsonOrText($r->job_name ?? null, $locale) ?: '-';
            $primaryBranchName = $this->nameJsonOrText($r->primary_branch_name ?? null, $locale) ?: '-';

            $branchesList = $this->concatNamesJsonToTextList($r->branches_names_concat ?? '', $locale);
            $branchesText = !empty($branchesList) ? implode('، ', $branchesList) : '-';

            $phone = $r->phone_1 ?: ($r->phone_2 ?: ($r->whatsapp ?: '-'));

            $sheet->fromArray([
                $r->code ?: '-',
                $employeeName,
                $jobName,
                $primaryBranchName,
                $branchesText,
                $this->translateGender($r->gender ?? null),
                $this->translateStatus($r->status ?? null),
                $this->translateCoachFlag($r->is_coach ?? null),

                $this->translateCompensationType($r->compensation_type ?? null),
                $r->base_salary !== null ? (float)$r->base_salary : '-',
                $this->translateCommissionValueType($r->commission_value_type ?? null),
                $r->commission_percent !== null ? (float)$r->commission_percent : '-',
                $r->commission_fixed !== null ? (float)$r->commission_fixed : '-',
                $this->translateSalaryTransferMethod($r->salary_transfer_method ?? null),
                $r->salary_transfer_details ?: '-',

                $r->specialization ?: '-',
                $r->years_experience !== null ? (float)$r->years_experience : '-',
                !empty($r->birth_date) ? Carbon::parse($r->birth_date)->format('Y-m-d') : '-',
                $phone,
                $r->email ?: '-',
                $r->added_by_name ?: '-',
                $r->bio ?: '-',
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

        $fileName = 'employees_report_' . now('Africa/Cairo')->format('Ymd_His') . '.xlsx';

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

    private function buildEmployeeBlock($code, $name, $phone, $email): string
    {
        $code = $code ?: '-';
        $name = $name ?: '-';
        $phone = $phone ?: '-';
        $email = $email ?: '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($name) . '</span>' .
            '<small class="text-muted">' . e(__('reports.emp_col_code') . ': ' . $code) . '</small>' .
            '<small class="text-muted">' . e(__('reports.emp_col_phone') . ': ' . $phone) . '</small>' .
            '<small class="text-muted">' . e(__('reports.emp_col_email') . ': ' . $email) . '</small>' .
            '</div>';
    }

    private function buildBranchesBlock(?string $primaryName, int $count, array $allNames): string
    {
        $primaryName = $primaryName ?: '-';
        $countText = __('reports.emp_branches_count') . ': ' . $count;

        $listText = '-';
        if (!empty($allNames)) {
            $listText = implode('، ', array_slice($allNames, 0, 3));
            if (count($allNames) > 3) {
                $listText .= '...';
            }
        }

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($primaryName) . '</span>' .
            '<small class="text-muted">' . e($countText) . '</small>' .
            '<small class="text-muted">' . e($listText) . '</small>' .
            '</div>';
    }

    private function buildStatusBlock($status): string
    {
        $st = $this->normalizeStatus($status);
        $activeText = __('reports.emp_status_active');
        $inactiveText = __('reports.emp_status_inactive');

        if ((int)$st === 1) {
            return '<span class="badge bg-success">' . e($activeText) . '</span>';
        }
        if ((string)$status === '') {
            return '<span class="badge bg-secondary">-</span>';
        }
        return '<span class="badge bg-danger">' . e($inactiveText) . '</span>';
    }

    private function buildCompensationBlock($type, $baseSalary, $commissionType, $commissionPercent, $commissionFixed): string
    {
        $typeText = $this->translateCompensationType($type);

        $base = ($baseSalary !== null && $baseSalary !== '') ? (string)$baseSalary : '-';
        $cvType = $this->translateCommissionValueType($commissionType);

        $pct = ($commissionPercent !== null && $commissionPercent !== '') ? ((float)$commissionPercent . '%') : '-';
        $fix = ($commissionFixed !== null && $commissionFixed !== '') ? (string)$commissionFixed : '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($typeText) . '</span>' .
            '<small class="text-muted">' . e(__('reports.emp_col_base_salary') . ': ' . $base) . '</small>' .
            '<small class="text-muted">' . e(__('reports.emp_col_commission_value_type') . ': ' . $cvType) . '</small>' .
            '<small class="text-muted">' . e(__('reports.emp_col_commission_percent') . ': ' . $pct . ' | ' . __('reports.emp_col_commission_fixed') . ': ' . $fix) . '</small>' .
            '</div>';
    }

    private function buildTransferBlock($method, $details): string
    {
        $m = $this->translateSalaryTransferMethod($method);
        $d = $details ?: '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($m) . '</span>' .
            '<small class="text-muted">' . e($d) . '</small>' .
            '</div>';
    }

    private function buildExperienceText($specialization, $years): string
    {
        $sp = $specialization ?: '-';
        $yrs = ($years !== null && $years !== '') ? (string)$years : '-';

        return '<div class="d-flex flex-column">' .
            '<span class="fw-semibold">' . e($sp) . '</span>' .
            '<small class="text-muted">' . e(__('reports.emp_col_years_experience') . ': ' . $yrs) . '</small>' .
            '</div>';
    }

    private function normalizeStatus($status): ?int
    {
        $v = trim((string)$status);
        if ($v === '') return null;

        if (in_array($v, ['1', '0'], true)) return (int)$v;

        $s = strtolower($v);
        if (in_array($s, ['active', 'enabled'], true)) return 1;
        if (in_array($s, ['inactive', 'disabled'], true)) return 0;

        $ar = [
            'نشط' => 1,
            'غير نشط' => 0,
            'غير فعال' => 0,
            'موقوف' => 0,
        ];
        if (isset($ar[$v])) return $ar[$v];

        return null;
    }

    private function translateStatus($status): string
    {
        $st = $this->normalizeStatus($status);
        $isAr = app()->getLocale() === 'ar';

        if ($st === 1) return $this->trFallback('reports.emp_status_active', $isAr ? 'نشط' : 'Active');
        if ($st === 0) return $this->trFallback('reports.emp_status_inactive', $isAr ? 'غير نشط' : 'Inactive');

        return ((string)$status !== '') ? (string)$status : '-';
    }

    private function normalizeGender($gender): ?string
    {
        $v = strtolower(trim((string)$gender));
        if ($v === '') return null;

        if (in_array($v, ['male', 'female'], true)) return $v;
        if (in_array($v, ['1', 'm'], true)) return 'male';
        if (in_array($v, ['2', 'f'], true)) return 'female';

        $ar = [
            'ذكر' => 'male',
            'انثى' => 'female',
            'أنثى' => 'female',
        ];
        if (isset($ar[$gender])) return $ar[$gender];

        return $v;
    }

    private function translateGender($gender): string
    {
        $g = $this->normalizeGender($gender);
        $isAr = app()->getLocale() === 'ar';

        if ($g === 'male') return $this->trFallback('reports.emp_gender_male', $isAr ? 'ذكر' : 'Male');
        if ($g === 'female') return $this->trFallback('reports.emp_gender_female', $isAr ? 'أنثى' : 'Female');

        return ((string)$gender !== '') ? (string)$gender : '-';
    }

    private function translateCoachFlag($v): string
    {
        $isAr = app()->getLocale() === 'ar';
        if ((string)$v === '1') return $this->trFallback('reports.emp_yes', $isAr ? 'نعم' : 'Yes');
        if ((string)$v === '0') return $this->trFallback('reports.emp_no', $isAr ? 'لا' : 'No');
        return '-';
    }

    // ================= FIXED NORMALIZERS + TRANSLATORS =================

    private function normalizeCompensationType($v): ?string
    {
        $s = strtolower(trim((string)$v));
        if ($s === '') return null;

        $allowed = ['salary_and_commission', 'salary_only', 'commission_only'];
        if (in_array($s, $allowed, true)) return $s;

        return null;
    }

    private function translateCompensationType($v): string
    {
        $k = $this->normalizeCompensationType($v);
        $isAr = app()->getLocale() === 'ar';

        if ($k === 'salary_only') {
            return $this->trFallback('reports.emp_comp_salary_only', $isAr ? 'راتب فقط' : 'Salary only');
        }
        if ($k === 'commission_only') {
            return $this->trFallback('reports.emp_comp_commission_only', $isAr ? 'عمولة فقط' : 'Commission only');
        }
        if ($k === 'salary_and_commission') {
            return $this->trFallback('reports.emp_comp_salary_and_commission', $isAr ? 'راتب + عمولة' : 'Salary & commission');
        }

        return ((string)$v !== '') ? (string)$v : '-';
    }

    private function normalizeCommissionValueType($v): ?string
    {
        $s = strtolower(trim((string)$v));
        if ($s === '') return null;

        if (in_array($s, ['fixed', 'percent'], true)) return $s;

        return null;
    }

    private function translateCommissionValueType($v): string
    {
        $k = $this->normalizeCommissionValueType($v);
        $isAr = app()->getLocale() === 'ar';

        if ($k === 'fixed') return $this->trFallback('reports.emp_comm_fixed', $isAr ? 'قيمة ثابتة' : 'Fixed');
        if ($k === 'percent') return $this->trFallback('reports.emp_comm_percent', $isAr ? 'نسبة' : 'Percent');

        return ((string)$v !== '') ? (string)$v : '-';
    }

    private function normalizeSalaryTransferMethod($v): ?string
    {
        $s = strtolower(trim((string)$v));
        if ($s === '') return null;

        $allowed = ['ewallet', 'cash', 'bank_transfer', 'instapay', 'credit_card', 'cheque', 'other'];
        if (in_array($s, $allowed, true)) return $s;

        return null;
    }

    private function translateSalaryTransferMethod($v): string
    {
        $k = $this->normalizeSalaryTransferMethod($v);
        $isAr = app()->getLocale() === 'ar';

        if ($k === 'ewallet') return $this->trFallback('reports.emp_transfer_ewallet', $isAr ? 'محفظة إلكترونية' : 'E-wallet');
        if ($k === 'cash') return $this->trFallback('reports.emp_transfer_cash', $isAr ? 'نقدًا' : 'Cash');
        if ($k === 'bank_transfer') return $this->trFallback('reports.emp_transfer_bank_transfer', $isAr ? 'تحويل بنكي' : 'Bank transfer');
        if ($k === 'instapay') return $this->trFallback('reports.emp_transfer_instapay', $isAr ? 'InstaPay' : 'InstaPay');
        if ($k === 'credit_card') return $this->trFallback('reports.emp_transfer_credit_card', $isAr ? 'بطاقة' : 'Credit card');
        if ($k === 'cheque') return $this->trFallback('reports.emp_transfer_cheque', $isAr ? 'شيك' : 'Cheque');
        if ($k === 'other') return $this->trFallback('reports.emp_transfer_other', $isAr ? 'أخرى' : 'Other');

        return ((string)$v !== '') ? (string)$v : '-';
    }

    // ================= OPTIONS =================

    private function statusOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.emp_all')],
            ['value' => '1', 'label' => $this->translateStatus(1)],
            ['value' => '0', 'label' => $this->translateStatus(0)],
        ];
    }

    private function genderOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.emp_all')],
            ['value' => 'male', 'label' => $this->translateGender('male')],
            ['value' => 'female', 'label' => $this->translateGender('female')],
        ];
    }

    private function coachOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.emp_all')],
            ['value' => '1', 'label' => $this->translateCoachFlag(1)],
            ['value' => '0', 'label' => $this->translateCoachFlag(0)],
        ];
    }

    private function compensationTypeOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.emp_all')],
            ['value' => 'salary_and_commission', 'label' => $this->translateCompensationType('salary_and_commission')],
            ['value' => 'salary_only', 'label' => $this->translateCompensationType('salary_only')],
            ['value' => 'commission_only', 'label' => $this->translateCompensationType('commission_only')],
        ];
    }

    private function commissionValueTypeOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.emp_all')],
            ['value' => 'fixed', 'label' => $this->translateCommissionValueType('fixed')],
            ['value' => 'percent', 'label' => $this->translateCommissionValueType('percent')],
        ];
    }

    private function salaryTransferMethodOptions(): array
    {
        return [
            ['value' => '', 'label' => __('reports.emp_all')],
            ['value' => 'ewallet', 'label' => $this->translateSalaryTransferMethod('ewallet')],
            ['value' => 'cash', 'label' => $this->translateSalaryTransferMethod('cash')],
            ['value' => 'bank_transfer', 'label' => $this->translateSalaryTransferMethod('bank_transfer')],
            ['value' => 'instapay', 'label' => $this->translateSalaryTransferMethod('instapay')],
            ['value' => 'credit_card', 'label' => $this->translateSalaryTransferMethod('credit_card')],
            ['value' => 'cheque', 'label' => $this->translateSalaryTransferMethod('cheque')],
            ['value' => 'other', 'label' => $this->translateSalaryTransferMethod('other')],
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
            if (json_last_error() !== JSON_ERROR_NONE) break;

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

    private function concatNamesJsonToTextList(string $concat, string $locale): array
    {
        $concat = trim($concat);
        if ($concat === '') return [];

        $parts = array_values(array_filter(array_map('trim', explode('||', $concat))));
        $out = [];
        foreach ($parts as $p) {
            $t = $this->nameJsonOrText($p, $locale);
            if ($t !== '') $out[] = $t;
        }
        return $out;
    }
}
