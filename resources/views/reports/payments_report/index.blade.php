@extends('layouts.master_table')



@section('title')
    {{ __('reports.payments_report_title') ?? 'تقرير المدفوعات' }}
@endsection



@section('content')
@php
    $rtl = app()->getLocale() === 'ar';

    $filters = $filters ?? [];
    $kpis = $kpis ?? [];
    $filterOptions = $filterOptions ?? [];

    $statusOptions = [];
    if (isset($filterOptions['statuses']) && is_array($filterOptions['statuses'])) {
        foreach ($filterOptions['statuses'] as $o) $statusOptions[(string)$o['value']] = (string)$o['label'];
    }

    $groupByOptions = $filterOptions['group_by'] ?? [];
    $paymentMethods = $filterOptions['payment_methods'] ?? [];

    // IMPORTANT:
    // You requested to use "source" from invoices/payments with these values:
    // main_subscription&PT, main_subscription_only, PT_only
    $paymentSources = [
        'main_subscription&PT' => __('reports.pay_source_main_and_pt') ?? 'اشتراك + PT',
        'main_subscription_only' => __('reports.pay_source_main_only') ?? 'اشتراك فقط',
        'PT_only' => __('reports.pay_source_pt_only') ?? 'PT فقط',
    ];
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
            <h4 class="mb-sm-0">{{ __('reports.payments_report_title') ?? 'تقرير المدفوعات' }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">{{ __('reports.reports') ?? 'التقارير' }}</a></li>
                    <li class="breadcrumb-item active">{{ __('reports.payments_report_title') ?? 'تقرير المدفوعات' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>



{{-- KPIs --}}
<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-4">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.pay_kpi_net_collected') ?? 'صافي التحصيل' }}</small>
                        <h4 class="mb-0" id="kpi_net_collected">{{ (float)($kpis['net_collected'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-success text-success">
                            <i class="ri-bank-card-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.pay_kpi_payments_count') ?? 'عدد العمليات' }}:
                    <span id="kpi_payments_count">{{ (int)($kpis['payments_count'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>



    <div class="col-md-6 col-xl-4">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.pay_kpi_paid') ?? 'مدفوع' }}</small>
                        <h4 class="mb-0" id="kpi_paid_sum">{{ (float)($kpis['paid_sum'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-primary text-primary">
                            <i class="ri-check-double-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.pay_kpi_refunded') ?? 'مسترد' }}:
                    <span id="kpi_refunded_sum">{{ (float)($kpis['refunded_sum'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>



    <div class="col-md-6 col-xl-4">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.pay_kpi_pending_failed') ?? 'معلّق/فشل' }}</small>
                        <h4 class="mb-0">
                            <span id="kpi_pending_sum">{{ (float)($kpis['pending_sum'] ?? 0) }}</span>
                            /
                            <span id="kpi_failed_sum">{{ (float)($kpis['failed_sum'] ?? 0) }}</span>
                        </h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-warning text-warning">
                            <i class="ri-alert-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.pay_kpi_unique_members') ?? 'عملاء' }}:
                    <span id="kpi_unique_members">{{ (int)($kpis['unique_members'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>
</div>



{{-- Filters --}}
<div class="card border shadow-none mb-3">
    <div class="card-body">
        <form method="get" action="{{ route('payments_report.index') }}" class="row g-2 align-items-end" id="filtersForm">
            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.pay_filter_date_from') ?? 'من تاريخ' }}</label>
                <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>



            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.pay_filter_date_to') ?? 'إلى تاريخ' }}</label>
                <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>



            <div class="col-md-6">
                <label class="form-label mb-1">{{ __('reports.pay_filter_branches') ?? 'الفروع' }}</label>
                <select name="branch_ids[]" id="filterBranches" class="form-select select2" multiple>
                    @foreach($branches as $b)
                        @php $bn = method_exists($b,'getTranslation') ? $b->getTranslation('name', app()->getLocale()) : ($b->name ?? ''); @endphp
                        <option value="{{ $b->id }}"
                            {{ in_array((string)$b->id, array_map('strval', (array)($filters['branch_ids'] ?? [])), true) ? 'selected' : '' }}>
                            {{ $bn }}
                        </option>
                    @endforeach
                </select>
            </div>



            {{-- Member search (name/code) --}}
            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.pay_filter_member_q') ?? 'بحث العضو (اسم/كود)' }}</label>
                <input type="text" class="form-control" name="member_q" value="{{ $filters['member_q'] ?? '' }}"
                       placeholder="{{ __('reports.pay_filter_member_q_ph') ?? 'مثال: M-0001 أو أحمد' }}">
                <small class="text-muted">{{ __('reports.pay_filter_member_q_hint') ?? 'ابحث بالاسم أو كود العضو.' }}</small>
            </div>



            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.pay_filter_status') ?? 'الحالة' }}</label>
                <select name="status" class="form-select">
                    @foreach($statusOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['status'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>



            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.pay_filter_method') ?? 'طريقة الدفع' }}</label>
                <select name="payment_method" class="form-select select2">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach($paymentMethods as $m)
                        <option value="{{ $m }}" {{ (string)($filters['payment_method'] ?? '') === (string)$m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>



            {{-- Payment/Invoice source kinds --}}
            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.pay_filter_source') ?? 'مصدر العملية' }}</label>
                <select name="source" class="form-select select2">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach($paymentSources as $val => $lbl)
                        <option value="{{ $val }}" {{ (string)($filters['source'] ?? '') === (string)$val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
                <small class="text-muted">{{ __('reports.pay_filter_source_hint') ?? 'يميز بين الاشتراك الأساسي و PT (حسب المصدر في payments/invoices).' }}</small>
            </div>



            {{-- Keep exact IDs filters (optional) --}}
            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.pay_filter_member') ?? 'رقم العضو' }}</label>
                <input type="number" class="form-control" name="member_id" value="{{ $filters['member_id'] ?? '' }}" min="1" step="1">
            </div>



            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.pay_filter_subscription') ?? 'رقم الاشتراك' }}</label>
                <input type="number" class="form-control" name="member_subscription_id" value="{{ $filters['member_subscription_id'] ?? '' }}" min="1" step="1">
            </div>



            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.pay_filter_amount_from') ?? 'المبلغ من' }}</label>
                <input type="number" class="form-control" name="amount_from" value="{{ $filters['amount_from'] ?? '' }}" min="0" step="0.01">
            </div>



            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.pay_filter_amount_to') ?? 'المبلغ إلى' }}</label>
                <input type="number" class="form-control" name="amount_to" value="{{ $filters['amount_to'] ?? '' }}" min="0" step="0.01">
            </div>



            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.pay_filter_group_by') ?? 'تجميع حسب' }}</label>
                <select name="group_by" id="groupBySelect" class="form-select">
                    @foreach($groupByOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['group_by'] ?? 'payment_method') === (string)$k ? 'selected' : '' }}>
                            {{ $lbl }}
                        </option>
                    @endforeach
                </select>
            </div>



            <div class="col-12 d-flex justify-content-between flex-wrap gap-2 mt-2">
                <div class="alert alert-info mb-0 py-2 px-3">
                    <i class="mdi mdi-information-outline"></i>
                    <strong>{{ __('reports.pay_tip') ?? 'صافي التحصيل = مدفوع - مسترد.' }}</strong>
                </div>



                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line align-bottom me-1"></i> {{ __('reports.search') ?? 'بحث' }}
                    </button>


                    <a class="btn btn-soft-secondary" href="{{ route('payments_report.index') }}">
                        <i class="ri-refresh-line align-bottom me-1"></i> {{ __('reports.reset') ?? 'إعادة تعيين' }}
                    </a>


                    <a class="btn btn-soft-success" target="_blank" id="btnPrint"
                       href="{{ route('payments_report.index', array_merge(request()->all(), ['action' => 'print'])) }}">
                        <i class="ri-printer-line align-bottom me-1"></i> {{ __('reports.print') ?? 'طباعة' }}
                    </a>


                    <a class="btn btn-soft-success" id="btnExport"
                       href="{{ route('payments_report.index', array_merge(request()->all(), ['action' => 'export_excel'])) }}">
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
        <h5 class="card-title mb-0">{{ __('reports.pay_group_title') ?? 'ملخص التجميع' }}</h5>
        <div class="text-muted small">{{ __('reports.pay_group_hint') ?? 'يتغير حسب خيار (تجميع حسب).' }}</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped group-table mb-0" id="groupTable">
                <thead class="table-light">
                <tr>
                    <th style="min-width:180px">{{ __('reports.pay_group_col_name') ?? 'البند' }}</th>
                    <th>{{ __('reports.pay_group_col_count') ?? 'عدد العمليات' }}</th>
                    <th>{{ __('reports.pay_group_col_paid') ?? 'مدفوع' }}</th>
                    <th>{{ __('reports.pay_group_col_refunded') ?? 'مسترد' }}</th>
                    <th>{{ __('reports.pay_group_col_pending') ?? 'معلّق' }}</th>
                    <th>{{ __('reports.pay_group_col_failed') ?? 'فشل' }}</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="6" class="text-center text-muted py-3">{{ __('reports.loading') ?? 'جاري التحميل...' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>



{{-- Detail Table --}}
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">{{ __('reports.pay_table_title') ?? 'تفاصيل عمليات الدفع' }}</h5>
        <div class="text-muted small">{{ __('reports.pay_table_hint') ?? 'الجدول يدعم البحث والترتيب والتصفح.' }}</div>
    </div>



    <div class="card-body">
        <div class="table-responsive">
            <table id="paymentsTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                <thead class="table-light">
                <tr>
                    <th style="width:55px">{{ __('reports.serial') ?? '#' }}</th>
                    <th>{{ __('reports.pay_col_dates') ?? 'التواريخ' }}</th>
                    <th>{{ __('reports.pay_col_status') ?? 'الحالة' }}</th>
                    <th>{{ __('reports.pay_col_method') ?? 'الطريقة' }}</th>
                    <th>{{ __('reports.pay_col_amount') ?? 'المبلغ' }}</th>
                    <th>{{ __('reports.pay_col_branch') ?? 'الفرع' }}</th>
                    <th>{{ __('reports.pay_col_member') ?? 'العضو' }}</th>
                    <th>{{ __('reports.pay_col_subscription') ?? 'الاشتراك' }}</th>
                    <th>{{ __('reports.pay_col_source') ?? 'المصدر' }}</th>
                    <th>{{ __('reports.pay_col_reference') ?? 'المرجع' }}</th>
                    <th>{{ __('reports.pay_col_added_by') ?? 'أضيف بواسطة' }}</th>
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

        obj.member_q = $form.find('[name="member_q"]').val() || '';

        obj.status = $form.find('[name="status"]').val() || '';
        obj.payment_method = $form.find('[name="payment_method"]').val() || '';
        obj.source = $form.find('[name="source"]').val() || '';

        obj.member_id = $form.find('[name="member_id"]').val() || '';
        obj.member_subscription_id = $form.find('[name="member_subscription_id"]').val() || '';

        obj.amount_from = $form.find('[name="amount_from"]').val() || '';
        obj.amount_to = $form.find('[name="amount_to"]').val() || '';

        obj.group_by = $form.find('[name="group_by"]').val() || 'payment_method';

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
        var baseUrl = "{{ route('payments_report.index') }}";
        var qs = buildQueryString(filters);

        var printUrl  = baseUrl + (qs ? ('?' + qs + '&action=print') : '?action=print');
        var exportUrl = baseUrl + (qs ? ('?' + qs + '&action=export_excel') : '?action=export_excel');

        $('#btnPrint').attr('href', printUrl);
        $('#btnExport').attr('href', exportUrl);
    }

    function loadKpis(filters) {
        var baseUrl = "{{ route('payments_report.index') }}";
        var data = $.extend({}, filters || {}, { action: 'metrics' });

        return $.ajax({
            url: baseUrl,
            type: "GET",
            data: data,
            success: function (res) {
                if (!res) return;

                $('#kpi_payments_count').text(parseInt(res.payments_count || 0, 10));

                $('#kpi_paid_sum').text(res.paid_sum || 0);
                $('#kpi_pending_sum').text(res.pending_sum || 0);
                $('#kpi_failed_sum').text(res.failed_sum || 0);
                $('#kpi_refunded_sum').text(res.refunded_sum || 0);

                $('#kpi_net_collected').text(res.net_collected || 0);

                $('#kpi_unique_members').text(parseInt(res.unique_members || 0, 10));
            }
        });
    }

    function loadGroup(filters) {
        var baseUrl = "{{ route('payments_report.index') }}";
        var data = $.extend({}, filters || {}, { action: 'group' });

        return $.ajax({
            url: baseUrl,
            type: "GET",
            data: data,
            success: function (res) {
                var $tbody = $('#groupTable tbody');
                $tbody.empty();

                if (!res || !res.rows || !res.rows.length) {
                    $tbody.append('<tr><td colspan="6" class="text-center text-muted py-3">{{ __("reports.no_results") ?? "لا توجد نتائج" }}</td></tr>');
                    return;
                }

                res.rows.forEach(function (r) {
                    var tr = '<tr>' +
                        '<td>' + (r.group_name || '-') + '</td>' +
                        '<td class="text-center">' + (r.payments_count || 0) + '</td>' +
                        '<td class="text-center">' + (r.paid_sum || 0) + '</td>' +
                        '<td class="text-center">' + (r.refunded_sum || 0) + '</td>' +
                        '<td class="text-center">' + (r.pending_sum || 0) + '</td>' +
                        '<td class="text-center">' + (r.failed_sum || 0) + '</td>' +
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

    var paymentsTable = $('#paymentsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        order: [[1, 'desc']], // dates column after serial
        ajax: {
            url: "{{ route('payments_report.index') }}",
            type: "GET",
            data: function (d) {
                var f = getFiltersObject();

                d.date_from = f.date_from;
                d.date_to = f.date_to;
                d.branch_ids = f.branch_ids;

                d.member_q = f.member_q;

                d.status = f.status;
                d.payment_method = f.payment_method;
                d.source = f.source;

                d.member_id = f.member_id;
                d.member_subscription_id = f.member_subscription_id;

                d.amount_from = f.amount_from;
                d.amount_to = f.amount_to;
            }
        },
        columnDefs: [
            { targets: [0], orderable: false, searchable: false },
            { targets: [1,7,9], className: 'dt-cell-wrap' }
        ],
        columns: [
            { data: 'rownum', name: 'rownum' },
            { data: 'date_block', name: 'date_block' },
            { data: 'status', name: 'status' },
            { data: 'method', name: 'method' },
            { data: 'amount', name: 'amount' },
            { data: 'branch', name: 'branch' },
            { data: 'member', name: 'member' },
            { data: 'subscription', name: 'subscription' },
            { data: 'source', name: 'source' },
            { data: 'reference', name: 'reference' },
            { data: 'added_by', name: 'added_by' },
        ]
    });

    $('#filtersForm').on('submit', function (e) {
        e.preventDefault();

        var filters = getFiltersObject();
        updateActionLinks(filters);

        paymentsTable.ajax.reload(null, true);

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
