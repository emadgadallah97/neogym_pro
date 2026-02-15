@extends('layouts.master_table')

@section('title')
    {{ __('reports.employees_report_title') ?? 'تقرير بيانات الموظفين' }}
@endsection

@section('content')
@php
    $rtl = app()->getLocale() === 'ar';

    $filters = $filters ?? [];
    $kpis = $kpis ?? [];
    $filterOptions = $filterOptions ?? [];

    $optListToMap = function($list){
        $out = ['' => __('reports.emp_all') ?? 'الكل'];
        if (is_array($list)) {
            foreach ($list as $o) {
                $out[(string)($o['value'] ?? '')] = (string)($o['label'] ?? '');
            }
        }
        return $out;
    };

    $statusOptions = $optListToMap($filterOptions['statuses'] ?? []);
    $genderOptions = $optListToMap($filterOptions['genders'] ?? []);
    $coachOptions  = $optListToMap($filterOptions['coach_flags'] ?? []);
    $compOptions   = $optListToMap($filterOptions['compensation_types'] ?? []);
    $commTypeOptions = $optListToMap($filterOptions['commission_value_types'] ?? []);
    $transferOptions = $optListToMap($filterOptions['salary_transfer_methods'] ?? []);
@endphp

<style>
    .select2-container { width: 100% !important; max-width: 100% !important; }
    .select2-dropdown { z-index: 2000; }
    .kpi-card .avatar-sm .avatar-title { width: 2.5rem; height: 2.5rem; }
    .dt-cell-wrap { white-space: normal !important; }
</style>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">
                {{ __('reports.employees_report_title') ?? 'تقرير بيانات الموظفين' }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">{{ __('reports.reports') ?? 'التقارير' }}</a></li>
                    <li class="breadcrumb-item active">{{ __('reports.employees_report_title') ?? 'تقرير بيانات الموظفين' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- KPIs --}}
<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.emp_kpi_total') ?? 'إجمالي الموظفين' }}</small>
                        <h4 class="mb-0" id="kpi_total">{{ (int)($kpis['total'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-primary text-primary">
                            <i class="ri-user-3-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.emp_kpi_jobs_used') ?? 'وظائف مستخدمة' }}:
                    <span id="kpi_jobs_used">{{ (int)($kpis['jobs_used'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.emp_kpi_active') ?? 'نشط' }}</small>
                        <h4 class="mb-0" id="kpi_active">{{ (int)($kpis['active'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-success text-success">
                            <i class="ri-check-double-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.emp_kpi_inactive') ?? 'غير نشط' }}:
                    <span id="kpi_inactive">{{ (int)($kpis['inactive'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.emp_kpi_coaches') ?? 'مدربين' }}</small>
                        <h4 class="mb-0" id="kpi_coaches">{{ (int)($kpis['coaches'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-warning text-warning">
                            <i class="ri-run-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.emp_kpi_branches_used') ?? 'فروع مستخدمة' }}:
                    <span id="kpi_branches_used">{{ (int)($kpis['branches_used'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.emp_kpi_avg_base_salary') ?? 'متوسط الراتب الأساسي' }}</small>
                        <h4 class="mb-0" id="kpi_avg_base_salary">{{ (float)($kpis['avg_base_salary'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-info text-info">
                            <i class="ri-money-dollar-circle-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.emp_kpi_gender') ?? 'النوع' }}:
                    {{ __('reports.emp_gender_male') ?? 'ذكر' }} <span id="kpi_male">{{ (int)($kpis['male'] ?? 0) }}</span>
                    | {{ __('reports.emp_gender_female') ?? 'أنثى' }} <span id="kpi_female">{{ (int)($kpis['female'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Filters Bar --}}
<div class="card border shadow-none mb-3">
    <div class="card-body">
        <form method="get" action="{{ route('employees_report.index') }}" class="row g-2 align-items-end" id="filtersForm">
            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.emp_filter_employee') ?? 'بحث الموظف' }}</label>
                <input type="text" class="form-control" name="employee_term"
                       value="{{ $filters['employee_term'] ?? '' }}"
                       placeholder="{{ __('reports.emp_filter_employee_hint') ?? 'كود/اسم/موبايل/واتساب/Email/تخصص' }}">
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.emp_filter_branches') ?? 'الفروع' }}</label>
                <select name="branch_ids[]" id="filterBranches" class="form-select select2" multiple
                        data-placeholder="{{ __('reports.emp_filter_branches') ?? 'الفروع' }}">
                    @foreach($branches as $b)
                        @php
                            $bn = method_exists($b,'getTranslation') ? $b->getTranslation('name', app()->getLocale()) : ($b->name ?? '');
                        @endphp
                        <option value="{{ $b->id }}"
                            {{ in_array((string)$b->id, array_map('strval', (array)($filters['branch_ids'] ?? [])), true) ? 'selected' : '' }}>
                            {{ $bn }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">{{ __('reports.multiselecthint') ?? 'يمكن اختيار أكثر من فرع' }}</small>
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.emp_filter_job') ?? 'الوظيفة' }}</label>
                <select name="job_id" id="filterJob" class="form-select select2"
                        data-placeholder="{{ __('reports.emp_filter_job') ?? 'الوظيفة' }}">
                    <option value="">{{ __('reports.emp_all') ?? 'الكل' }}</option>
                    @foreach($jobs as $j)
                        @php
                            $jn = method_exists($j,'getTranslation') ? $j->getTranslation('name', app()->getLocale()) : ($j->name ?? '');
                        @endphp
                        <option value="{{ $j->id }}" {{ (string)($filters['job_id'] ?? '') === (string)$j->id ? 'selected' : '' }}>
                            {{ $jn }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.emp_filter_status') ?? 'الحالة' }}</label>
                <select name="status" class="form-select">
                    @foreach($statusOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['status'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.emp_filter_gender') ?? 'النوع' }}</label>
                <select name="gender" class="form-select">
                    @foreach($genderOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['gender'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.emp_filter_is_coach') ?? 'مدرب' }}</label>
                <select name="is_coach" class="form-select">
                    @foreach($coachOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['is_coach'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.emp_filter_compensation_type') ?? 'نوع التعويض' }}</label>
                <select name="compensation_type" class="form-select">
                    @foreach($compOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['compensation_type'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.emp_filter_commission_value_type') ?? 'نوع العمولة' }}</label>
                <select name="commission_value_type" class="form-select">
                    @foreach($commTypeOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['commission_value_type'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.emp_filter_salary_transfer_method') ?? 'طريقة تحويل الراتب' }}</label>
                <select name="salary_transfer_method" class="form-select">
                    @foreach($transferOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['salary_transfer_method'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.emp_filter_birth_date_from') ?? 'تاريخ الميلاد من' }}</label>
                <input type="date" class="form-control" name="birth_date_from" value="{{ $filters['birth_date_from'] ?? '' }}">
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.emp_filter_birth_date_to') ?? 'تاريخ الميلاد إلى' }}</label>
                <input type="date" class="form-control" name="birth_date_to" value="{{ $filters['birth_date_to'] ?? '' }}">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.emp_filter_years_exp_from') ?? 'خبرة من' }}</label>
                <input type="number" class="form-control" name="years_exp_from" value="{{ $filters['years_exp_from'] ?? '' }}" min="0" step="0.5">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.emp_filter_years_exp_to') ?? 'خبرة إلى' }}</label>
                <input type="number" class="form-control" name="years_exp_to" value="{{ $filters['years_exp_to'] ?? '' }}" min="0" step="0.5">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.emp_filter_base_salary_from') ?? 'راتب من' }}</label>
                <input type="number" class="form-control" name="base_salary_from" value="{{ $filters['base_salary_from'] ?? '' }}" min="0" step="1">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.emp_filter_base_salary_to') ?? 'راتب إلى' }}</label>
                <input type="number" class="form-control" name="base_salary_to" value="{{ $filters['base_salary_to'] ?? '' }}" min="0" step="1">
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.emp_filter_specialization') ?? 'التخصص' }}</label>
                <input type="text" class="form-control" name="specialization" value="{{ $filters['specialization'] ?? '' }}"
                       placeholder="{{ __('reports.emp_filter_specialization_hint') ?? 'بحث داخل التخصص' }}">
            </div>

            <div class="col-12 d-flex justify-content-between flex-wrap gap-2 mt-2">
                <div class="alert alert-info mb-0 py-2 px-3">
                    <i class="mdi mdi-information-outline"></i>
                    <strong>{{ __('reports.emp_tip') ?? 'يمكنك استخدام البحث العام داخل الجدول أيضاً.' }}</strong>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line align-bottom me-1"></i> {{ __('reports.search') ?? 'بحث' }}
                    </button>

                    <a class="btn btn-soft-secondary" href="{{ route('employees_report.index') }}">
                        <i class="ri-refresh-line align-bottom me-1"></i> {{ __('reports.reset') ?? 'إعادة تعيين' }}
                    </a>

                    <a class="btn btn-soft-success" target="_blank" id="btnPrint"
                       href="{{ route('employees_report.index', array_merge(request()->all(), ['action' => 'print'])) }}">
                        <i class="ri-printer-line align-bottom me-1"></i> {{ __('reports.print') ?? 'طباعة' }}
                    </a>

                    <a class="btn btn-soft-success" id="btnExport"
                       href="{{ route('employees_report.index', array_merge(request()->all(), ['action' => 'export_excel'])) }}">
                        <i class="ri-file-excel-2-line align-bottom me-1"></i> {{ __('reports.export_excel') ?? 'تصدير Excel' }}
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">{{ __('reports.emp_table_title') ?? 'تفاصيل الموظفين' }}</h5>
        <div class="text-muted small">
            <i class="ri-information-line align-bottom me-1"></i>
            {{ __('reports.emp_table_hint') ?? 'الجدول يدعم البحث، الترتيب، والتصفح.' }}
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="empTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('reports.emp_col_employee') ?? 'بيانات الموظف' }}</th>
                        <th>{{ __('reports.emp_col_job') ?? 'الوظيفة' }}</th>
                        <th>{{ __('reports.emp_col_branches') ?? 'الفروع' }}</th>
                        <th>{{ __('reports.emp_col_gender') ?? 'النوع' }}</th>
                        <th>{{ __('reports.emp_col_status') ?? 'الحالة' }}</th>
                        <th>{{ __('reports.emp_col_is_coach') ?? 'مدرب' }}</th>
                        <th>{{ __('reports.emp_col_compensation') ?? 'التعويض' }}</th>
                        <th>{{ __('reports.emp_col_transfer') ?? 'تحويل الراتب' }}</th>
                        <th>{{ __('reports.emp_col_experience') ?? 'التخصص/الخبرة' }}</th>
                        <th>{{ __('reports.emp_col_added_by') ?? 'مضاف بواسطة' }}</th>
                        <th>{{ __('reports.emp_col_bio') ?? 'نبذة' }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ === 'undefined') return;

    var isRtl = (document.documentElement.getAttribute('dir') || '').toLowerCase() === 'rtl';

    function getFiltersObject() {
        var $form = $('#filtersForm');
        var obj = {};

        obj.employee_term = $form.find('[name="employee_term"]').val() || '';
        obj.job_id = $form.find('[name="job_id"]').val() || '';

        obj.status = $form.find('[name="status"]').val() || '';
        obj.gender = $form.find('[name="gender"]').val() || '';
        obj.is_coach = $form.find('[name="is_coach"]').val() || '';

        obj.compensation_type = $form.find('[name="compensation_type"]').val() || '';
        obj.commission_value_type = $form.find('[name="commission_value_type"]').val() || '';
        obj.salary_transfer_method = $form.find('[name="salary_transfer_method"]').val() || '';

        obj.birth_date_from = $form.find('[name="birth_date_from"]').val() || '';
        obj.birth_date_to = $form.find('[name="birth_date_to"]').val() || '';

        obj.years_exp_from = $form.find('[name="years_exp_from"]').val() || '';
        obj.years_exp_to = $form.find('[name="years_exp_to"]').val() || '';

        obj.base_salary_from = $form.find('[name="base_salary_from"]').val() || '';
        obj.base_salary_to = $form.find('[name="base_salary_to"]').val() || '';

        obj.specialization = $form.find('[name="specialization"]').val() || '';

        obj.branch_ids = $('#filterBranches').val() || [];

        return obj;
    }

    function buildQueryString(filters) {
        var params = new URLSearchParams();

        Object.keys(filters || {}).forEach(function (k) {
            var v = filters[k];

            if (Array.isArray(v)) {
                v.forEach(function (item) {
                    if (item !== null && item !== undefined && String(item).trim() !== '') {
                        if (k === 'branch_ids') params.append('branch_ids[]', item);
                        else params.append(k + '[]', item);
                    }
                });
            } else {
                if (v !== null && v !== undefined && String(v).trim() !== '') {
                    params.set(k, v);
                }
            }
        });

        return params.toString();
    }

    function updateActionLinks(filters) {
        var baseUrl = "{{ route('employees_report.index') }}";
        var qs = buildQueryString(filters);

        var printUrl  = baseUrl + (qs ? ('?' + qs + '&action=print') : '?action=print');
        var exportUrl = baseUrl + (qs ? ('?' + qs + '&action=export_excel') : '?action=export_excel');

        $('#btnPrint').attr('href', printUrl);
        $('#btnExport').attr('href', exportUrl);
    }

    function loadKpis(filters) {
        var baseUrl = "{{ route('employees_report.index') }}";
        var data = $.extend({}, filters || {}, { action: 'metrics' });

        return $.ajax({
            url: baseUrl,
            type: "GET",
            data: data,
            success: function (res) {
                if (!res) return;

                $('#kpi_total').text(parseInt(res.total || 0, 10));
                $('#kpi_active').text(parseInt(res.active || 0, 10));
                $('#kpi_inactive').text(parseInt(res.inactive || 0, 10));
                $('#kpi_coaches').text(parseInt(res.coaches || 0, 10));
                $('#kpi_male').text(parseInt(res.male || 0, 10));
                $('#kpi_female').text(parseInt(res.female || 0, 10));
                $('#kpi_jobs_used').text(parseInt(res.jobs_used || 0, 10));
                $('#kpi_branches_used').text(parseInt(res.branches_used || 0, 10));
                $('#kpi_avg_base_salary').text(res.avg_base_salary || 0);
            }
        });
    }

    // Select2
    if ($.fn && $.fn.select2) {
        var $form = $('#filtersForm');
        $('.select2').select2({
            width: '100%',
            allowClear: true,
            closeOnSelect: false,
            dir: isRtl ? 'rtl' : 'ltr',
            dropdownParent: $form.length ? $form : $(document.body)
        });
    }

    // DataTable
    if (!($.fn && $.fn.DataTable)) return;

    var table = $('#empTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']],

        ajax: {
            url: "{{ route('employees_report.index') }}",
            type: "GET",
            data: function (d) {
                var f = getFiltersObject();

                d.employee_term = f.employee_term;
                d.branch_ids = f.branch_ids;
                d.job_id = f.job_id;

                d.status = f.status;
                d.gender = f.gender;
                d.is_coach = f.is_coach;

                d.compensation_type = f.compensation_type;
                d.commission_value_type = f.commission_value_type;
                d.salary_transfer_method = f.salary_transfer_method;

                d.birth_date_from = f.birth_date_from;
                d.birth_date_to = f.birth_date_to;

                d.years_exp_from = f.years_exp_from;
                d.years_exp_to = f.years_exp_to;

                d.base_salary_from = f.base_salary_from;
                d.base_salary_to = f.base_salary_to;

                d.specialization = f.specialization;
            }
        },

        columnDefs: [
            { targets: [0, 2, 6, 7, 8, 10], className: 'dt-cell-wrap' }
        ],

        columns: [
            { data: 'employee_block', name: 'employee_block' },
            { data: 'job', name: 'job' },
            { data: 'branches_block', name: 'branches_block' },
            { data: 'gender_text', name: 'gender_text' },
            { data: 'status_block', name: 'status_block' },
            { data: 'coach_text', name: 'coach_text' },
            { data: 'comp_block', name: 'comp_block' },
            { data: 'transfer_block', name: 'transfer_block' },
            { data: 'experience_text', name: 'experience_text' },
            { data: 'added_by', name: 'added_by' },
            { data: 'bio', name: 'bio' },
        ]
    });

    $('#filtersForm').on('submit', function (e) {
        e.preventDefault();

        var filters = getFiltersObject();
        updateActionLinks(filters);

        table.ajax.reload(null, true);
        loadKpis(filters);

        try {
            var qs = buildQueryString(filters);
            var newUrl = window.location.pathname + (qs ? ('?' + qs) : '');
            window.history.replaceState({}, document.title, newUrl);
        } catch (err) {}
    });

    // initial
    try {
        var initialFilters = getFiltersObject();
        updateActionLinks(initialFilters);
        loadKpis(initialFilters);
    } catch (e) {}
});
</script>
@endsection
