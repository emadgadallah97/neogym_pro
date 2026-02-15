@extends('layouts.master_table')

@section('title')
    {{ __('reports.subscriptions_report_title') ?? 'تقرير خطط الاشتراكات' }}
@endsection

@section('content')
@php
    $rtl = app()->getLocale() === 'ar';

    $filters = $filters ?? [];
    $kpis = $kpis ?? [];
    $filterOptions = $filterOptions ?? [];
    $types = $types ?? collect();

    $optListToMap = function($list){
        $out = ['' => __('reports.sub_all') ?? 'الكل'];
        if (is_array($list)) {
            foreach ($list as $o) {
                $out[(string)($o['value'] ?? '')] = (string)($o['label'] ?? '');
            }
        }
        return $out;
    };

    $statusOptions = $optListToMap($filterOptions['statuses'] ?? []);
    $yesNoOptions = $optListToMap($filterOptions['yes_no'] ?? []);
    $periodTypeOptions = $optListToMap($filterOptions['period_types'] ?? []);

    $nameJsonOrText = function ($nameJsonOrText) {
        $locale = app()->getLocale();
        if ($nameJsonOrText === null) return '';
        if (is_array($nameJsonOrText)) {
            return $nameJsonOrText[$locale] ?? ($nameJsonOrText['ar'] ?? ($nameJsonOrText['en'] ?? reset($nameJsonOrText)));
        }
        $v = (string)$nameJsonOrText;
        $decoded = json_decode($v, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded)) return $decoded[$locale] ?? ($decoded['ar'] ?? ($decoded['en'] ?? reset($decoded)));
            if (is_string($decoded)) return $decoded;
        }
        return $v;
    };
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
                {{ __('reports.subscriptions_report_title') ?? 'تقرير خطط الاشتراكات' }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">{{ __('reports.reports') ?? 'التقارير' }}</a></li>
                    <li class="breadcrumb-item active">{{ __('reports.subscriptions_report_title') ?? 'تقرير خطط الاشتراكات' }}</li>
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
                        <small class="text-muted">{{ __('reports.sub_kpi_total') ?? 'إجمالي الخطط' }}</small>
                        <h4 class="mb-0" id="kpi_total">{{ (int)($kpis['total'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-primary text-primary">
                            <i class="ri-coupon-3-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.sub_kpi_types_used') ?? 'أنواع مستخدمة' }}:
                    <span id="kpi_types_used">{{ (int)($kpis['types_used'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.sub_kpi_active') ?? 'نشط' }}</small>
                        <h4 class="mb-0" id="kpi_active">{{ (int)($kpis['active'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-success text-success">
                            <i class="ri-check-double-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.sub_kpi_inactive') ?? 'غير نشط' }}:
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
                        <small class="text-muted">{{ __('reports.sub_kpi_features') ?? 'خصائص' }}</small>
                        <h4 class="mb-0">
                            <span id="kpi_allow_guest">{{ (int)($kpis['allow_guest'] ?? 0) }}</span>
                            /
                            <span id="kpi_allow_freeze">{{ (int)($kpis['allow_freeze'] ?? 0) }}</span>
                            /
                            <span id="kpi_notify_before_end">{{ (int)($kpis['notify_before_end'] ?? 0) }}</span>
                        </h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-warning text-warning">
                            <i class="ri-settings-3-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.sub_kpi_branches_used') ?? 'فروع مستخدمة' }}:
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
                        <small class="text-muted">{{ __('reports.sub_kpi_avgs') ?? 'متوسطات' }}</small>
                        <h4 class="mb-0">
                            <span id="kpi_avg_duration_days">{{ (float)($kpis['avg_duration_days'] ?? 0) }}</span>
                            /
                            <span id="kpi_avg_sessions_count">{{ (float)($kpis['avg_sessions_count'] ?? 0) }}</span>
                        </h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-info text-info">
                            <i class="ri-bar-chart-2-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.sub_kpi_avg_price') ?? 'متوسط السعر الأساسي' }}:
                    <span id="kpi_avg_price">{{ (float)($kpis['avg_price'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border shadow-none mb-3">
    <div class="card-body">
        <form method="get" action="{{ route('subscriptions_report.index') }}" class="row g-2 align-items-end" id="filtersForm">
            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.sub_filter_plan') ?? 'بحث الخطة' }}</label>
                <input type="text" class="form-control" name="plan_term"
                       value="{{ $filters['plan_term'] ?? '' }}"
                       placeholder="{{ __('reports.sub_filter_plan_hint') ?? 'كود/اسم/نوع/وصف/ملاحظات' }}">
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.sub_filter_branches') ?? 'الفروع' }}</label>
                <select name="branch_ids[]" id="filterBranches" class="form-select select2" multiple
                        data-placeholder="{{ __('reports.sub_filter_branches') ?? 'الفروع' }}">
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
                <label class="form-label mb-1">{{ __('reports.sub_filter_type') ?? 'نوع الاشتراك' }}</label>
                <select name="type_id" class="form-select select2" data-placeholder="{{ __('reports.sub_filter_type') ?? 'نوع الاشتراك' }}">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach($types as $t)
                        @php $tn = $nameJsonOrText($t->name ?? null); @endphp
                        <option value="{{ $t->id }}" {{ (string)($filters['type_id'] ?? '') === (string)$t->id ? 'selected' : '' }}>
                            {{ $tn }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sub_filter_status') ?? 'الحالة' }}</label>
                <select name="status" class="form-select">
                    @foreach($statusOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['status'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.sub_filter_period_type') ?? 'نوع الفترة' }}</label>
                <select name="sessions_period_type" class="form-select">
                    @foreach($periodTypeOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['sessions_period_type'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sub_filter_allow_guest') ?? 'السماح بالضيف' }}</label>
                <select name="allow_guest" class="form-select">
                    @foreach($yesNoOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['allow_guest'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sub_filter_allow_freeze') ?? 'السماح بالتجميد' }}</label>
                <select name="allow_freeze" class="form-select">
                    @foreach($yesNoOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['allow_freeze'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.sub_filter_notify_before_end') ?? 'تنبيه قبل الانتهاء' }}</label>
                <select name="notify_before_end" class="form-select">
                    @foreach($yesNoOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['notify_before_end'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sub_filter_duration_from') ?? 'المدة من (يوم)' }}</label>
                <input type="number" class="form-control" name="duration_from" value="{{ $filters['duration_from'] ?? '' }}" min="0" step="1">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sub_filter_duration_to') ?? 'المدة إلى (يوم)' }}</label>
                <input type="number" class="form-control" name="duration_to" value="{{ $filters['duration_to'] ?? '' }}" min="0" step="1">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sub_filter_sessions_from') ?? 'الجلسات من' }}</label>
                <input type="number" class="form-control" name="sessions_from" value="{{ $filters['sessions_from'] ?? '' }}" min="0" step="1">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sub_filter_sessions_to') ?? 'الجلسات إلى' }}</label>
                <input type="number" class="form-control" name="sessions_to" value="{{ $filters['sessions_to'] ?? '' }}" min="0" step="1">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sub_filter_price_from') ?? 'السعر من' }}</label>
                <input type="number" class="form-control" name="price_from" value="{{ $filters['price_from'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sub_filter_price_to') ?? 'السعر إلى' }}</label>
                <input type="number" class="form-control" name="price_to" value="{{ $filters['price_to'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-12 d-flex justify-content-between flex-wrap gap-2 mt-2">
                <div class="alert alert-info mb-0 py-2 px-3">
                    <i class="mdi mdi-information-outline"></i>
                    <strong>{{ __('reports.sub_tip') ?? 'السعر المعتمد هو السعر الأساسي فقط (بدون مدرب) لكل فرع، مع عرض عدد الاشتراكات الفعالة.' }}</strong>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line align-bottom me-1"></i> {{ __('reports.search') ?? 'بحث' }}
                    </button>

                    <a class="btn btn-soft-secondary" href="{{ route('subscriptions_report.index') }}">
                        <i class="ri-refresh-line align-bottom me-1"></i> {{ __('reports.reset') ?? 'إعادة تعيين' }}
                    </a>

                    <a class="btn btn-soft-success" target="_blank" id="btnPrint"
                       href="{{ route('subscriptions_report.index', array_merge(request()->all(), ['action' => 'print'])) }}">
                        <i class="ri-printer-line align-bottom me-1"></i> {{ __('reports.print') ?? 'طباعة' }}
                    </a>

                    <a class="btn btn-soft-success" id="btnExport"
                       href="{{ route('subscriptions_report.index', array_merge(request()->all(), ['action' => 'export_excel'])) }}">
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
        <h5 class="card-title mb-0">{{ __('reports.sub_table_title') ?? 'تفاصيل الخطط' }}</h5>
        <div class="text-muted small">
            <i class="ri-information-line align-bottom me-1"></i>
            {{ __('reports.sub_table_hint') ?? 'الجدول يدعم البحث، الترتيب، والتصفح.' }}
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="subTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                <thead class="table-light">
                <tr>
                    <th>{{ __('reports.sub_col_plan') ?? 'الخطة' }}</th>
                    <th>{{ __('reports.sub_col_status') ?? 'الحالة' }}</th>
                    <th>{{ __('reports.sub_col_period') ?? 'الفترة/الأيام' }}</th>
                    <th>{{ __('reports.sub_col_limits') ?? 'الحدود' }}</th>
                    <th>{{ __('reports.sub_col_guest') ?? 'الضيف' }}</th>
                    <th>{{ __('reports.sub_col_freeze') ?? 'التجميد' }}</th>
                    <th>{{ __('reports.sub_col_notify') ?? 'التنبيه' }}</th>
                    <th>{{ __('reports.sub_col_branches_price') ?? 'الفروع/السعر/الاشتراكات' }}</th>
                    <th>{{ __('reports.sub_col_created_by') ?? 'مضاف بواسطة' }}</th>
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

        obj.plan_term = $form.find('[name="plan_term"]').val() || '';
        obj.type_id = $form.find('[name="type_id"]').val() || '';
        obj.branch_ids = $('#filterBranches').val() || [];

        obj.status = $form.find('[name="status"]').val() || '';
        obj.sessions_period_type = $form.find('[name="sessions_period_type"]').val() || '';

        obj.allow_guest = $form.find('[name="allow_guest"]').val() || '';
        obj.allow_freeze = $form.find('[name="allow_freeze"]').val() || '';
        obj.notify_before_end = $form.find('[name="notify_before_end"]').val() || '';

        obj.duration_from = $form.find('[name="duration_from"]').val() || '';
        obj.duration_to = $form.find('[name="duration_to"]').val() || '';

        obj.sessions_from = $form.find('[name="sessions_from"]').val() || '';
        obj.sessions_to = $form.find('[name="sessions_to"]').val() || '';

        obj.price_from = $form.find('[name="price_from"]').val() || '';
        obj.price_to = $form.find('[name="price_to"]').val() || '';

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
        var baseUrl = "{{ route('subscriptions_report.index') }}";
        var qs = buildQueryString(filters);

        var printUrl  = baseUrl + (qs ? ('?' + qs + '&action=print') : '?action=print');
        var exportUrl = baseUrl + (qs ? ('?' + qs + '&action=export_excel') : '?action=export_excel');

        $('#btnPrint').attr('href', printUrl);
        $('#btnExport').attr('href', exportUrl);
    }

    function loadKpis(filters) {
        var baseUrl = "{{ route('subscriptions_report.index') }}";
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
                $('#kpi_types_used').text(parseInt(res.types_used || 0, 10));

                $('#kpi_allow_freeze').text(parseInt(res.allow_freeze || 0, 10));
                $('#kpi_allow_guest').text(parseInt(res.allow_guest || 0, 10));
                $('#kpi_notify_before_end').text(parseInt(res.notify_before_end || 0, 10));

                $('#kpi_avg_duration_days').text(res.avg_duration_days || 0);
                $('#kpi_avg_sessions_count').text(res.avg_sessions_count || 0);

                $('#kpi_avg_price').text(res.avg_price || 0);
                $('#kpi_branches_used').text(parseInt(res.branches_used || 0, 10));
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

    var table = $('#subTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']],
        ajax: {
            url: "{{ route('subscriptions_report.index') }}",
            type: "GET",
            data: function (d) {
                var f = getFiltersObject();

                d.plan_term = f.plan_term;
                d.type_id = f.type_id;
                d.branch_ids = f.branch_ids;

                d.status = f.status;
                d.sessions_period_type = f.sessions_period_type;

                d.allow_guest = f.allow_guest;
                d.allow_freeze = f.allow_freeze;
                d.notify_before_end = f.notify_before_end;

                d.duration_from = f.duration_from;
                d.duration_to = f.duration_to;

                d.sessions_from = f.sessions_from;
                d.sessions_to = f.sessions_to;

                d.price_from = f.price_from;
                d.price_to = f.price_to;
            }
        },
        columnDefs: [
            { targets: [0, 2, 3, 4, 5, 6, 7], className: 'dt-cell-wrap' }
        ],
        columns: [
            { data: 'plan_block', name: 'plan_block' },
            { data: 'status_block', name: 'status_block' },
            { data: 'period_block', name: 'period_block' },
            { data: 'limits_block', name: 'limits_block' },
            { data: 'guest_block', name: 'guest_block' },
            { data: 'freeze_block', name: 'freeze_block' },
            { data: 'notify_block', name: 'notify_block' },
            { data: 'branches_price_block', name: 'branches_price_block' },
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
