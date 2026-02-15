@extends('layouts.master_table')

@section('title')
    {{ __('reports.members_report_title') ?? 'تقرير الأعضاء' }}
@endsection

@section('content')
@php
    $rtl = app()->getLocale() === 'ar';

    $filters = $filters ?? [];
    $kpis = $kpis ?? [];
    $filterOptions = $filterOptions ?? [];

    $optListToMap = function($list){
        $out = ['' => __('reports.mem_all') ?? 'الكل'];
        if (is_array($list)) {
            foreach ($list as $o) {
                $out[(string)($o['value'] ?? '')] = (string)($o['label'] ?? '');
            }
        }
        return $out;
    };

    $statusOptions = $optListToMap($filterOptions['statuses'] ?? []);
    $genderOptions = $optListToMap($filterOptions['genders'] ?? []);
    $freezeNowOptions = $optListToMap($filterOptions['freeze_now'] ?? []);
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
                {{ __('reports.members_report_title') ?? 'تقرير الأعضاء' }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">{{ __('reports.reports') ?? 'التقارير' }}</a></li>
                    <li class="breadcrumb-item active">{{ __('reports.members_report_title') ?? 'تقرير الأعضاء' }}</li>
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
                        <small class="text-muted">{{ __('reports.mem_kpi_total') ?? 'إجمالي الأعضاء' }}</small>
                        <h4 class="mb-0" id="kpi_total">{{ (int)($kpis['total'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-primary text-primary">
                            <i class="ri-group-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.mem_kpi_branches_used') ?? 'فروع مستخدمة' }}:
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
                        <small class="text-muted">{{ __('reports.mem_kpi_active') ?? 'نشط' }}</small>
                        <h4 class="mb-0" id="kpi_active">{{ (int)($kpis['active'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-success text-success">
                            <i class="ri-check-double-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.mem_kpi_inactive') ?? 'غير نشط' }}:
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
                        <small class="text-muted">{{ __('reports.mem_kpi_frozen') ?? 'مجمد' }}</small>
                        <h4 class="mb-0" id="kpi_frozen">{{ (int)($kpis['frozen'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-warning text-warning">
                            <i class="ri-snowy-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.mem_kpi_frozen_now') ?? 'مجمد الآن' }}:
                    <span id="kpi_frozen_now">{{ (int)($kpis['frozen_now'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.mem_kpi_avg_body') ?? 'متوسط القياسات' }}</small>
                        <h4 class="mb-0">
                            <span id="kpi_avg_height">{{ (float)($kpis['avg_height'] ?? 0) }}</span>
                            /
                            <span id="kpi_avg_weight">{{ (float)($kpis['avg_weight'] ?? 0) }}</span>
                        </h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-info text-info">
                            <i class="ri-scales-3-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.mem_kpi_gender') ?? 'النوع' }}:
                    {{ __('reports.mem_gender_male') ?? 'ذكر' }} <span id="kpi_male">{{ (int)($kpis['male'] ?? 0) }}</span>
                    | {{ __('reports.mem_gender_female') ?? 'أنثى' }} <span id="kpi_female">{{ (int)($kpis['female'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Filters Bar --}}
<div class="card border shadow-none mb-3">
    <div class="card-body">
        <form method="get" action="{{ route('members_report.index') }}" class="row g-2 align-items-end" id="filtersForm">
            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.mem_filter_member') ?? 'بحث العضو' }}</label>
                <input type="text" class="form-control" name="member_term"
                       value="{{ $filters['member_term'] ?? '' }}"
                       placeholder="{{ __('reports.mem_filter_member_hint') ?? 'كود/اسم/موبايل/واتساب/Email' }}">
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.mem_filter_branches') ?? 'الفروع' }}</label>
                <select name="branch_ids[]" id="filterBranches" class="form-select select2" multiple
                        data-placeholder="{{ __('reports.mem_filter_branches') ?? 'الفروع' }}">
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

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.mem_filter_status') ?? 'الحالة' }}</label>
                <select name="status" class="form-select">
                    @foreach($statusOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['status'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.mem_filter_gender') ?? 'النوع' }}</label>
                <select name="gender" class="form-select">
                    @foreach($genderOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['gender'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.mem_filter_join_date_from') ?? 'تاريخ الاشتراك من' }}</label>
                <input type="date" class="form-control" name="join_date_from" value="{{ $filters['join_date_from'] ?? '' }}">
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.mem_filter_join_date_to') ?? 'تاريخ الاشتراك إلى' }}</label>
                <input type="date" class="form-control" name="join_date_to" value="{{ $filters['join_date_to'] ?? '' }}">
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.mem_filter_birth_date_from') ?? 'تاريخ الميلاد من' }}</label>
                <input type="date" class="form-control" name="birth_date_from" value="{{ $filters['birth_date_from'] ?? '' }}">
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.mem_filter_birth_date_to') ?? 'تاريخ الميلاد إلى' }}</label>
                <input type="date" class="form-control" name="birth_date_to" value="{{ $filters['birth_date_to'] ?? '' }}">
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.mem_filter_government') ?? 'المحافظة' }}</label>
                <select name="government_id" class="form-select select2" data-placeholder="{{ __('reports.mem_filter_government') ?? 'المحافظة' }}">
                    <option value="">{{ __('reports.mem_all') ?? 'الكل' }}</option>
                    @foreach($governments as $g)
                        @php
                            $gn = method_exists($g,'getTranslation') ? $g->getTranslation('name', app()->getLocale()) : ($g->name ?? '');
                        @endphp
                        <option value="{{ $g->id }}" {{ (string)($filters['government_id'] ?? '') === (string)$g->id ? 'selected' : '' }}>{{ $gn }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.mem_filter_city') ?? 'المدينة' }}</label>
                <select name="city_id" class="form-select select2" data-placeholder="{{ __('reports.mem_filter_city') ?? 'المدينة' }}">
                    <option value="">{{ __('reports.mem_all') ?? 'الكل' }}</option>
                    @foreach($cities as $c)
                        @php
                            $cn = method_exists($c,'getTranslation') ? $c->getTranslation('name', app()->getLocale()) : ($c->name ?? '');
                        @endphp
                        <option value="{{ $c->id }}" {{ (string)($filters['city_id'] ?? '') === (string)$c->id ? 'selected' : '' }}>{{ $cn }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.mem_filter_area') ?? 'المنطقة' }}</label>
                <select name="area_id" class="form-select select2" data-placeholder="{{ __('reports.mem_filter_area') ?? 'المنطقة' }}">
                    <option value="">{{ __('reports.mem_all') ?? 'الكل' }}</option>
                    @foreach($areas as $a)
                        @php
                            $an = method_exists($a,'getTranslation') ? $a->getTranslation('name', app()->getLocale()) : ($a->name ?? '');
                        @endphp
                        <option value="{{ $a->id }}" {{ (string)($filters['area_id'] ?? '') === (string)$a->id ? 'selected' : '' }}>{{ $an }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.mem_filter_freeze_now') ?? 'مجمد الآن' }}</label>
                <select name="freeze_now" class="form-select">
                    @foreach($freezeNowOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['freeze_now'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.mem_filter_freeze_from') ?? 'تجميد من' }}</label>
                <input type="date" class="form-control" name="freeze_from" value="{{ $filters['freeze_from'] ?? '' }}">
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.mem_filter_freeze_to') ?? 'تجميد إلى' }}</label>
                <input type="date" class="form-control" name="freeze_to" value="{{ $filters['freeze_to'] ?? '' }}">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.mem_filter_height_from') ?? 'طول من' }}</label>
                <input type="number" class="form-control" name="height_from" value="{{ $filters['height_from'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.mem_filter_height_to') ?? 'طول إلى' }}</label>
                <input type="number" class="form-control" name="height_to" value="{{ $filters['height_to'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.mem_filter_weight_from') ?? 'وزن من' }}</label>
                <input type="number" class="form-control" name="weight_from" value="{{ $filters['weight_from'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.mem_filter_weight_to') ?? 'وزن إلى' }}</label>
                <input type="number" class="form-control" name="weight_to" value="{{ $filters['weight_to'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-12 d-flex justify-content-between flex-wrap gap-2 mt-2">
                <div class="alert alert-info mb-0 py-2 px-3">
                    <i class="mdi mdi-information-outline"></i>
                    <strong>{{ __('reports.mem_tip') ?? 'يمكنك استخدام البحث العام داخل الجدول أيضاً.' }}</strong>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line align-bottom me-1"></i> {{ __('reports.search') ?? 'بحث' }}
                    </button>

                    <a class="btn btn-soft-secondary" href="{{ route('members_report.index') }}">
                        <i class="ri-refresh-line align-bottom me-1"></i> {{ __('reports.reset') ?? 'إعادة تعيين' }}
                    </a>

                    <a class="btn btn-soft-success" target="_blank" id="btnPrint"
                       href="{{ route('members_report.index', array_merge(request()->all(), ['action' => 'print'])) }}">
                        <i class="ri-printer-line align-bottom me-1"></i> {{ __('reports.print') ?? 'طباعة' }}
                    </a>

                    <a class="btn btn-soft-success" id="btnExport"
                       href="{{ route('members_report.index', array_merge(request()->all(), ['action' => 'export_excel'])) }}">
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
        <h5 class="card-title mb-0">{{ __('reports.mem_table_title') ?? 'تفاصيل الأعضاء' }}</h5>
        <div class="text-muted small">
            <i class="ri-information-line align-bottom me-1"></i>
            {{ __('reports.mem_table_hint') ?? 'الجدول يدعم البحث، الترتيب، والتصفح.' }}
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="memTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                <thead class="table-light">
                <tr>
                    <th>{{ __('reports.mem_col_member') ?? 'بيانات العضو' }}</th>
                    <th>{{ __('reports.mem_col_branch') ?? 'الفرع' }}</th>
                    <th>{{ __('reports.mem_col_status') ?? 'الحالة' }}</th>
                    <th>{{ __('reports.mem_col_gender') ?? 'النوع' }}</th>
                    <th>{{ __('reports.mem_col_dates') ?? 'تواريخ' }}</th>
                    <th>{{ __('reports.mem_col_location') ?? 'الموقع' }}</th>
                    <th>{{ __('reports.mem_col_body') ?? 'القياسات' }}</th>
                    <th>{{ __('reports.mem_col_medical') ?? 'طبي/ملاحظات' }}</th>
                    <th>{{ __('reports.mem_col_added_by') ?? 'مضاف بواسطة' }}</th>
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

        obj.member_term = $form.find('[name="member_term"]').val() || '';
        obj.branch_ids = $('#filterBranches').val() || [];

        obj.status = $form.find('[name="status"]').val() || '';
        obj.gender = $form.find('[name="gender"]').val() || '';

        obj.join_date_from = $form.find('[name="join_date_from"]').val() || '';
        obj.join_date_to = $form.find('[name="join_date_to"]').val() || '';

        obj.birth_date_from = $form.find('[name="birth_date_from"]').val() || '';
        obj.birth_date_to = $form.find('[name="birth_date_to"]').val() || '';

        obj.government_id = $form.find('[name="government_id"]').val() || '';
        obj.city_id = $form.find('[name="city_id"]').val() || '';
        obj.area_id = $form.find('[name="area_id"]').val() || '';

        obj.freeze_now = $form.find('[name="freeze_now"]').val() || '';
        obj.freeze_from = $form.find('[name="freeze_from"]').val() || '';
        obj.freeze_to = $form.find('[name="freeze_to"]').val() || '';

        obj.height_from = $form.find('[name="height_from"]').val() || '';
        obj.height_to = $form.find('[name="height_to"]').val() || '';

        obj.weight_from = $form.find('[name="weight_from"]').val() || '';
        obj.weight_to = $form.find('[name="weight_to"]').val() || '';

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
        var baseUrl = "{{ route('members_report.index') }}";
        var qs = buildQueryString(filters);

        var printUrl  = baseUrl + (qs ? ('?' + qs + '&action=print') : '?action=print');
        var exportUrl = baseUrl + (qs ? ('?' + qs + '&action=export_excel') : '?action=export_excel');

        $('#btnPrint').attr('href', printUrl);
        $('#btnExport').attr('href', exportUrl);
    }

    function loadKpis(filters) {
        var baseUrl = "{{ route('members_report.index') }}";
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
                $('#kpi_frozen').text(parseInt(res.frozen || 0, 10));
                $('#kpi_frozen_now').text(parseInt(res.frozen_now || 0, 10));
                $('#kpi_male').text(parseInt(res.male || 0, 10));
                $('#kpi_female').text(parseInt(res.female || 0, 10));
                $('#kpi_branches_used').text(parseInt(res.branches_used || 0, 10));
                $('#kpi_avg_height').text(res.avg_height || 0);
                $('#kpi_avg_weight').text(res.avg_weight || 0);
            }
        });
    }

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

    if (!($.fn && $.fn.DataTable)) return;

    var table = $('#memTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']],
        ajax: {
            url: "{{ route('members_report.index') }}",
            type: "GET",
            data: function (d) {
                var f = getFiltersObject();

                d.member_term = f.member_term;
                d.branch_ids = f.branch_ids;

                d.status = f.status;
                d.gender = f.gender;

                d.join_date_from = f.join_date_from;
                d.join_date_to = f.join_date_to;

                d.birth_date_from = f.birth_date_from;
                d.birth_date_to = f.birth_date_to;

                d.government_id = f.government_id;
                d.city_id = f.city_id;
                d.area_id = f.area_id;

                d.freeze_now = f.freeze_now;
                d.freeze_from = f.freeze_from;
                d.freeze_to = f.freeze_to;

                d.height_from = f.height_from;
                d.height_to = f.height_to;

                d.weight_from = f.weight_from;
                d.weight_to = f.weight_to;
            }
        },
        columnDefs: [
            { targets: [0, 2, 4, 5, 6, 7], className: 'dt-cell-wrap' }
        ],
        columns: [
            { data: 'member_block', name: 'member_block' },
            { data: 'branch', name: 'branch' },
            { data: 'status_block', name: 'status_block' },
            { data: 'gender_text', name: 'gender_text' },
            { data: 'dates_block', name: 'dates_block' },
            { data: 'location_block', name: 'location_block' },
            { data: 'body_block', name: 'body_block' },
            { data: 'medical_block', name: 'medical_block' },
            { data: 'added_by', name: 'added_by' },
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

    try {
        var initialFilters = getFiltersObject();
        updateActionLinks(initialFilters);
        loadKpis(initialFilters);
    } catch (e) {}
});
</script>
@endsection
