@extends('layouts.master_table')

@section('title')
    {{ __('reports.commissions_report_title') ?? 'تقرير العمولات' }}
@endsection

@section('content')
@php
    $rtl = app()->getLocale() === 'ar';

    $filters = $filters ?? [];
    $kpis = $kpis ?? [];
    $filterOptions = $filterOptions ?? [];

    $groupByOptions = $filterOptions['group_by'] ?? [];
    $settlementStatuses = $filterOptions['settlement_statuses'] ?? [];
    $sources = $filterOptions['sources'] ?? [];
@endphp

<style>
    .select2-container { width: 100% !important; max-width: 100% !important; }
    .select2-dropdown { z-index: 2000; }
    .kpi-card .avatar-sm .avatar-title { width: 2.5rem; height: 2.5rem; }
    .dt-cell-wrap { white-space: normal !important; }
    .group-table td, .group-table th { vertical-align: middle; }
</style>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ __('reports.commissions_report_title') ?? 'تقرير العمولات' }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">{{ __('reports.reports') ?? 'التقارير' }}</a></li>
                    <li class="breadcrumb-item active">{{ __('reports.commissions_report_title') ?? 'تقرير العمولات' }}</li>
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
                        <small class="text-muted">{{ __('reports.com_kpi_total_all') ?? 'إجمالي العمولات (الكل)' }}</small>
                        <h4 class="mb-0" id="kpi_total_all">{{ (float)($kpis['total_commission_all'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-primary text-primary">
                            <i class="ri-hand-coin-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.com_kpi_items_count') ?? 'عدد البنود' }}:
                    <span id="kpi_items_count">{{ (int)($kpis['items_count'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.com_kpi_included') ?? 'عمولات مستحقة (غير مستبعدة)' }}</small>
                        <h4 class="mb-0" id="kpi_included">{{ (float)($kpis['total_commission_included'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-success text-success">
                            <i class="ri-shield-check-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.com_kpi_excluded') ?? 'مستبعدة' }}:
                    <span id="kpi_excluded">{{ (float)($kpis['total_commission_excluded'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.com_kpi_paid') ?? 'مدفوعة' }}</small>
                        <h4 class="mb-0" id="kpi_paid">{{ (float)($kpis['paid_commission'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-info text-info">
                            <i class="ri-check-double-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.com_kpi_unpaid') ?? 'غير مدفوعة' }}:
                    <span id="kpi_unpaid">{{ (float)($kpis['unpaid_commission'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.com_kpi_settled') ?? 'ضمن تسوية' }}</small>
                        <h4 class="mb-0">
                            <span id="kpi_settled">{{ (int)($kpis['settled_items_count'] ?? 0) }}</span>
                            /
                            <span id="kpi_unsettled">{{ (int)($kpis['unsettled_items_count'] ?? 0) }}</span>
                        </h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-warning text-warning">
                            <i class="ri-file-list-3-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">{{ __('reports.com_kpi_settled_hint') ?? 'ضمن تسوية / بدون تسوية' }}</small>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border shadow-none mb-3">
    <div class="card-body">
        <form method="get" action="{{ route('commissions_report.index') }}" class="row g-2 align-items-end" id="filtersForm">
            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.com_filter_date_from') ?? 'من تاريخ' }}</label>
                <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.com_filter_date_to') ?? 'إلى تاريخ' }}</label>
                <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>

            <div class="col-md-6">
                <label class="form-label mb-1">{{ __('reports.com_filter_branches') ?? 'الفروع' }}</label>
                <select name="branch_ids[]" id="filterBranches" class="form-select select2" multiple>
                    @foreach($branches as $b)
                        @php $bn = method_exists($b,'getTranslation') ? $b->getTranslation('name', app()->getLocale()) : ($b->name ?? ''); @endphp
                        <option value="{{ $b->id }}"
                            {{ in_array((string)$b->id, array_map('strval', (array)($filters['branch_ids'] ?? [])), true) ? 'selected' : '' }}>
                            {{ $bn }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">{{ __('reports.multiselecthint') ?? 'يمكن اختيار أكثر من فرع' }}</small>
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.com_filter_sales_employee') ?? 'موظف المبيعات' }}</label>
                <select name="sales_employee_id" class="form-select select2">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach($salesEmployees as $se)
                        <option value="{{ $se->id }}" {{ (string)($filters['sales_employee_id'] ?? '') === (string)$se->id ? 'selected' : '' }}>
                            {{ $se->full_name }} {{ $se->code ? '(' . $se->code . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.com_filter_paid') ?? 'حالة الدفع' }}</label>
                <select name="commission_is_paid" class="form-select">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    <option value="1" {{ (string)($filters['commission_is_paid'] ?? '') === '1' ? 'selected' : '' }}>{{ __('reports.com_paid') ?? 'مدفوعة' }}</option>
                    <option value="0" {{ (string)($filters['commission_is_paid'] ?? '') === '0' ? 'selected' : '' }}>{{ __('reports.com_unpaid') ?? 'غير مدفوعة' }}</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.com_filter_has_settlement') ?? 'ضمن تسوية' }}</label>
                <select name="has_settlement" class="form-select">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    <option value="1" {{ (string)($filters['has_settlement'] ?? '') === '1' ? 'selected' : '' }}>{{ __('reports.sub_yes') ?? 'نعم' }}</option>
                    <option value="0" {{ (string)($filters['has_settlement'] ?? '') === '0' ? 'selected' : '' }}>{{ __('reports.sub_no') ?? 'لا' }}</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.com_filter_settlement_status') ?? 'حالة التسوية' }}</label>
                <select name="settlement_status" class="form-select select2">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach($settlementStatuses as $st)
                        <option value="{{ $st }}" {{ (string)($filters['settlement_status'] ?? '') === (string)$st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.com_filter_excluded') ?? 'مستبعد' }}</label>
                <select name="is_excluded" class="form-select">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    <option value="1" {{ (string)($filters['is_excluded'] ?? '') === '1' ? 'selected' : '' }}>{{ __('reports.sub_yes') ?? 'نعم' }}</option>
                    <option value="0" {{ (string)($filters['is_excluded'] ?? '') === '0' ? 'selected' : '' }}>{{ __('reports.sub_no') ?? 'لا' }}</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.com_filter_source') ?? 'المصدر' }}</label>
                <select name="source" class="form-select select2">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach($sources as $s)
                        <option value="{{ $s }}" {{ (string)($filters['source'] ?? '') === (string)$s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.com_filter_sale_from') ?? 'قيمة البيع من' }}</label>
                <input type="number" class="form-control" name="amount_from" value="{{ $filters['amount_from'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.com_filter_sale_to') ?? 'قيمة البيع إلى' }}</label>
                <input type="number" class="form-control" name="amount_to" value="{{ $filters['amount_to'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.com_filter_commission_from') ?? 'العمولة من' }}</label>
                <input type="number" class="form-control" name="commission_from" value="{{ $filters['commission_from'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.com_filter_commission_to') ?? 'العمولة إلى' }}</label>
                <input type="number" class="form-control" name="commission_to" value="{{ $filters['commission_to'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.com_filter_only_with_commission') ?? 'عمولات فقط' }}</label>
                <select name="only_with_commission" class="form-select">
                    <option value="1" {{ (string)($filters['only_with_commission'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('reports.sub_yes') ?? 'نعم' }}</option>
                    <option value="0" {{ (string)($filters['only_with_commission'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('reports.sub_no') ?? 'لا' }}</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.com_filter_group_by') ?? 'تجميع حسب' }}</label>
                <select name="group_by" id="groupBySelect" class="form-select">
                    @foreach($groupByOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['group_by'] ?? 'sales_employee') === (string)$k ? 'selected' : '' }}>
                            {{ $lbl }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 d-flex justify-content-between flex-wrap gap-2 mt-2">
                <div class="alert alert-info mb-0 py-2 px-3">
                    <i class="mdi mdi-information-outline"></i>
                    <strong>{{ __('reports.com_tip') ?? 'إذا كانت هناك تسوية، يتم أخذ الاستبعاد/السبب من عناصر التسوية، وإلا تُحسب من بيانات الاشتراك.' }}</strong>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line align-bottom me-1"></i> {{ __('reports.search') ?? 'بحث' }}
                    </button>

                    <a class="btn btn-soft-secondary" href="{{ route('commissions_report.index') }}">
                        <i class="ri-refresh-line align-bottom me-1"></i> {{ __('reports.reset') ?? 'إعادة تعيين' }}
                    </a>

                    <a class="btn btn-soft-success" target="_blank" id="btnPrint"
                       href="{{ route('commissions_report.index', array_merge(request()->all(), ['action' => 'print'])) }}">
                        <i class="ri-printer-line align-bottom me-1"></i> {{ __('reports.print') ?? 'طباعة' }}
                    </a>

                    <a class="btn btn-soft-success" id="btnExport"
                       href="{{ route('commissions_report.index', array_merge(request()->all(), ['action' => 'export_excel'])) }}">
                        <i class="ri-file-excel-2-line align-bottom me-1"></i> {{ __('reports.export_excel') ?? 'تصدير Excel' }}
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Group Summary --}}
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">{{ __('reports.com_group_title') ?? 'ملخص التجميع' }}</h5>
        <div class="text-muted small">{{ __('reports.com_group_hint') ?? 'يتغير حسب خيار (تجميع حسب).' }}</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped group-table mb-0" id="groupTable">
                <thead class="table-light">
                <tr>
                    <th style="min-width:180px">{{ __('reports.com_group_col_name') ?? 'البند' }}</th>
                    <th>{{ __('reports.com_group_col_count') ?? 'عدد البنود' }}</th>
                    <th>{{ __('reports.com_group_col_total') ?? 'إجمالي العمولة' }}</th>
                    <th>{{ __('reports.com_group_col_excluded') ?? 'مستبعدة' }}</th>
                    <th>{{ __('reports.com_group_col_paid') ?? 'مدفوعة' }}</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="5" class="text-center text-muted py-3">{{ __('reports.loading') ?? 'جاري التحميل...' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Detail Table --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">{{ __('reports.com_table_title') ?? 'تفاصيل العمولات' }}</h5>
        <div class="text-muted small">{{ __('reports.com_table_hint') ?? 'الجدول يدعم البحث والترتيب.' }}</div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="commissionsTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                <thead class="table-light">
                <tr>
                    <th>{{ __('reports.com_col_sale_date') ?? 'تاريخ البيع' }}</th>
                    <th>{{ __('reports.com_col_branch') ?? 'الفرع' }}</th>
                    <th>{{ __('reports.com_col_member') ?? 'العضو' }}</th>
                    <th>{{ __('reports.com_col_subscription') ?? 'الاشتراك' }}</th>

                    <th>{{ __('reports.com_col_sale_total') ?? 'قيمة البيع' }}</th>
                    <th>{{ __('reports.com_col_commission_base') ?? 'أساس العمولة' }}</th>
                    <th>{{ __('reports.com_col_commission_rule') ?? 'قيمة/نوع العمولة' }}</th>
                    <th>{{ __('reports.com_col_commission_amount') ?? 'قيمة العمولة' }}</th>

                    <th>{{ __('reports.com_col_excluded') ?? 'الاستبعاد' }}</th>
                    <th>{{ __('reports.com_col_paid') ?? 'الدفع' }}</th>
                    <th>{{ __('reports.com_col_settlement') ?? 'التسوية' }}</th>
                    <th>{{ __('reports.com_col_sales_employee') ?? 'موظف المبيعات' }}</th>
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

        obj.date_from = $form.find('[name="date_from"]').val() || '';
        obj.date_to = $form.find('[name="date_to"]').val() || '';
        obj.branch_ids = $('#filterBranches').val() || [];

        obj.sales_employee_id = $form.find('[name="sales_employee_id"]').val() || '';
        obj.commission_is_paid = $form.find('[name="commission_is_paid"]').val() || '';
        obj.has_settlement = $form.find('[name="has_settlement"]').val() || '';
        obj.settlement_status = $form.find('[name="settlement_status"]').val() || '';
        obj.is_excluded = $form.find('[name="is_excluded"]').val() || '';

        obj.source = $form.find('[name="source"]').val() || '';

        obj.amount_from = $form.find('[name="amount_from"]').val() || '';
        obj.amount_to = $form.find('[name="amount_to"]').val() || '';

        obj.commission_from = $form.find('[name="commission_from"]').val() || '';
        obj.commission_to = $form.find('[name="commission_to"]').val() || '';

        obj.only_with_commission = $form.find('[name="only_with_commission"]').val() || '1';

        obj.group_by = $form.find('[name="group_by"]').val() || 'sales_employee';

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
        var baseUrl = "{{ route('commissions_report.index') }}";
        var qs = buildQueryString(filters);

        var printUrl  = baseUrl + (qs ? ('?' + qs + '&action=print') : '?action=print');
        var exportUrl = baseUrl + (qs ? ('?' + qs + '&action=export_excel') : '?action=export_excel');

        $('#btnPrint').attr('href', printUrl);
        $('#btnExport').attr('href', exportUrl);
    }

    function loadKpis(filters) {
        var baseUrl = "{{ route('commissions_report.index') }}";
        var data = $.extend({}, filters || {}, { action: 'metrics' });

        return $.ajax({
            url: baseUrl,
            type: "GET",
            data: data,
            success: function (res) {
                if (!res) return;

                $('#kpi_items_count').text(parseInt(res.items_count || 0, 10));
                $('#kpi_total_all').text(res.total_commission_all || 0);

                $('#kpi_included').text(res.total_commission_included || 0);
                $('#kpi_excluded').text(res.total_commission_excluded || 0);

                $('#kpi_paid').text(res.paid_commission || 0);
                $('#kpi_unpaid').text(res.unpaid_commission || 0);

                $('#kpi_settled').text(parseInt(res.settled_items_count || 0, 10));
                $('#kpi_unsettled').text(parseInt(res.unsettled_items_count || 0, 10));
            }
        });
    }

    function loadGroup(filters) {
        var baseUrl = "{{ route('commissions_report.index') }}";
        var data = $.extend({}, filters || {}, { action: 'group' });

        return $.ajax({
            url: baseUrl,
            type: "GET",
            data: data,
            success: function (res) {
                var $tbody = $('#groupTable tbody');
                $tbody.empty();

                if (!res || !res.rows || !res.rows.length) {
                    $tbody.append('<tr><td colspan="5" class="text-center text-muted py-3">{{ __("reports.no_results") ?? "لا توجد نتائج" }}</td></tr>');
                    return;
                }

                res.rows.forEach(function (r) {
                    var tr = '<tr>' +
                        '<td>' + (r.group_name || '-') + '</td>' +
                        '<td class="text-center">' + (r.items_count || 0) + '</td>' +
                        '<td class="text-center">' + (r.total_commission || 0) + '</td>' +
                        '<td class="text-center">' + (r.excluded_commission || 0) + '</td>' +
                        '<td class="text-center">' + (r.paid_commission || 0) + '</td>' +
                    '</tr>';
                    $tbody.append(tr);
                });
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

    var table = $('#commissionsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('commissions_report.index') }}",
            type: "GET",
            data: function (d) {
                var f = getFiltersObject();

                d.date_from = f.date_from;
                d.date_to = f.date_to;
                d.branch_ids = f.branch_ids;

                d.sales_employee_id = f.sales_employee_id;
                d.commission_is_paid = f.commission_is_paid;
                d.has_settlement = f.has_settlement;
                d.settlement_status = f.settlement_status;
                d.is_excluded = f.is_excluded;

                d.source = f.source;

                d.amount_from = f.amount_from;
                d.amount_to = f.amount_to;

                d.commission_from = f.commission_from;
                d.commission_to = f.commission_to;

                d.only_with_commission = f.only_with_commission;
            }
        },
        columnDefs: [
            { targets: [3,8,9,10], className: 'dt-cell-wrap' }
        ],
        columns: [
            { data: 'sale_date', name: 'sale_date' },
            { data: 'branch', name: 'branch' },
            { data: 'member', name: 'member' },
            { data: 'subscription', name: 'subscription' },

            { data: 'sale_total', name: 'sale_total' },
            { data: 'commission_base', name: 'commission_base' },
            { data: 'commission_rule', name: 'commission_rule' },
            { data: 'commission_amount', name: 'commission_amount' },

            { data: 'excluded', name: 'excluded' },
            { data: 'paid', name: 'paid' },
            { data: 'settlement', name: 'settlement' },
            { data: 'sales_employee', name: 'sales_employee' },
        ]
    });

    $('#filtersForm').on('submit', function (e) {
        e.preventDefault();

        var filters = getFiltersObject();
        updateActionLinks(filters);

        table.ajax.reload(null, true);
        loadKpis(filters);
        loadGroup(filters);

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
        loadGroup(initialFilters);
    } catch (e) {}
});
</script>
@endsection
