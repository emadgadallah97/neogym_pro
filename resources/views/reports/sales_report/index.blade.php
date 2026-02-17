@extends('layouts.master_table')


@section('title')
    {{ __('reports.sales_report_title') ?? 'تقرير المبيعات' }}
@endsection


@section('content')
@php
    $rtl = app()->getLocale() === 'ar';

    $filters = $filters ?? [];
    $kpis = $kpis ?? [];
    $filterOptions = $filterOptions ?? [];

    $yesNoOptions = [];
    if (isset($filterOptions['yes_no']) && is_array($filterOptions['yes_no'])) {
        foreach ($filterOptions['yes_no'] as $o) $yesNoOptions[(string)$o['value']] = (string)$o['label'];
    }

    $statusOptions = [];
    if (isset($filterOptions['statuses']) && is_array($filterOptions['statuses'])) {
        foreach ($filterOptions['statuses'] as $o) $statusOptions[(string)$o['value']] = (string)$o['label'];
    }

    $groupByOptions = $filterOptions['group_by'] ?? [];
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
            <h4 class="mb-sm-0">{{ __('reports.sales_report_title') ?? 'تقرير المبيعات' }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">{{ __('reports.reports') ?? 'التقارير' }}</a></li>
                    <li class="breadcrumb-item active">{{ __('reports.sales_report_title') ?? 'تقرير المبيعات' }}</li>
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
                        <small class="text-muted">{{ __('reports.sales_kpi_total_sales') ?? 'إجمالي المبيعات' }}</small>
                        <h4 class="mb-0" id="kpi_total_sales">{{ (float)($kpis['total_sales'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-primary text-primary">
                            <i class="ri-shopping-bag-3-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.sales_kpi_subs_count') ?? 'عدد الاشتراكات' }}:
                    <span id="kpi_subs_count">{{ (int)($kpis['subs_count'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>


    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.sales_kpi_avg_sale') ?? 'متوسط قيمة البيع' }}</small>
                        <h4 class="mb-0" id="kpi_avg_sale">{{ (float)($kpis['avg_sale'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-info text-info">
                            <i class="ri-bar-chart-2-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.sales_kpi_pt_addons') ?? 'مبيعات PT' }}:
                    <span id="kpi_pt_addons_sales">{{ (float)($kpis['pt_addons_sales'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>


    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.sales_kpi_total_discount') ?? 'إجمالي الخصم' }}</small>
                        <h4 class="mb-0" id="kpi_total_discount">{{ (float)($kpis['total_discount'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-warning text-warning">
                            <i class="ri-price-tag-3-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.sales_kpi_offer_coupon') ?? 'عرض/كوبون' }}:
                    <span id="kpi_offer_discount">{{ (float)($kpis['offer_discount'] ?? 0) }}</span>
                    /
                    <span id="kpi_coupon_discount">{{ (float)($kpis['coupon_discount'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>


    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.sales_kpi_usage') ?? 'الاستخدام' }}</small>
                        <h4 class="mb-0">
                            <span id="kpi_offers_used_count">{{ (int)($kpis['offers_used_count'] ?? 0) }}</span>
                            /
                            <span id="kpi_coupons_used_count">{{ (int)($kpis['coupons_used_count'] ?? 0) }}</span>
                        </h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-success text-success">
                            <i class="ri-coupon-3-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.sales_kpi_usage_hint') ?? 'عدد الاشتراكات التي استخدمت عرض/كوبون' }}
                </small>
            </div>
        </div>
    </div>
</div>


{{-- Filters --}}
<div class="card border shadow-none mb-3">
    <div class="card-body">
        <form method="get" action="{{ route('sales_report.index') }}" class="row g-2 align-items-end" id="filtersForm">
            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.sales_filter_date_from') ?? 'من تاريخ' }}</label>
                <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>


            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.sales_filter_date_to') ?? 'إلى تاريخ' }}</label>
                <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>


            <div class="col-md-6">
                <label class="form-label mb-1">{{ __('reports.sales_filter_branches') ?? 'الفروع' }}</label>
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


            {{-- NEW: member search --}}
            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.sales_filter_member_q') ?? 'بحث العضو (اسم/كود)' }}</label>
                <input type="text" class="form-control" name="member_q" value="{{ $filters['member_q'] ?? '' }}"
                       placeholder="{{ __('reports.sales_filter_member_q_ph') ?? 'مثال: M-0001 أو أحمد' }}">
            </div>


            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.sales_filter_status') ?? 'حالة الاشتراك' }}</label>
                <select name="status" class="form-select">
                    @foreach($statusOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['status'] ?? '') === (string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>


            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.sales_filter_type') ?? 'نوع الاشتراك' }}</label>
                <select name="type_id" class="form-select select2">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach($types as $t)
                        @php
                            $tn = is_array($t->name) ? ($t->name[app()->getLocale()] ?? ($t->name['ar'] ?? ($t->name['en'] ?? ''))) : $t->name;
                            if (is_string($tn)) {
                                $decoded = json_decode($tn, true);
                                if (json_last_error()===JSON_ERROR_NONE && is_array($decoded)) {
                                    $tn = $decoded[app()->getLocale()] ?? ($decoded['ar'] ?? ($decoded['en'] ?? reset($decoded)));
                                }
                            }
                        @endphp
                        <option value="{{ $t->id }}" {{ (string)($filters['type_id'] ?? '') === (string)$t->id ? 'selected' : '' }}>
                            {{ $tn }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.sales_filter_plan') ?? 'الخطة' }}</label>
                <select name="plan_id" class="form-select select2">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach($plans as $p)
                        @php
                            $pn = $p->name;
                            if (is_string($pn)) {
                                $decoded = json_decode($pn, true);
                                if (json_last_error()===JSON_ERROR_NONE && is_array($decoded)) {
                                    $pn = $decoded[app()->getLocale()] ?? ($decoded['ar'] ?? ($decoded['en'] ?? reset($decoded)));
                                }
                            }
                        @endphp
                        <option value="{{ $p->id }}" {{ (string)($filters['plan_id'] ?? '') === (string)$p->id ? 'selected' : '' }}>
                            {{ ($p->code ? $p->code.' - ' : '') . $pn }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.sales_filter_source') ?? 'المصدر' }}</label>
                <select name="source" class="form-select select2">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach($sources as $s)
                        <option value="{{ $s }}" {{ (string)($filters['source'] ?? '') === (string)$s ? 'selected' : '' }}>
                            {{ $s }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.sales_filter_sales_employee') ?? 'موظف المبيعات' }}</label>
                <select name="sales_employee_id" class="form-select select2">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach($salesEmployees as $se)
                        @php
                            $sen = $se->full_name ?? '';
                            $sen = trim((string)$sen);
                            if ($sen === '') $sen = ('#' . (string)($se->id ?? ''));
                            $sec = $se->code ?? null;
                        @endphp
                        <option value="{{ $se->id }}" {{ (string)($filters['sales_employee_id'] ?? '') === (string)$se->id ? 'selected' : '' }}>
                            {{ $sen }} {{ $sec ? '(' . $sec . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sales_filter_has_offer') ?? 'استخدم عرض' }}</label>
                <select name="has_offer" class="form-select">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    <option value="1" {{ (string)($filters['has_offer'] ?? '') === '1' ? 'selected' : '' }}>{{ __('reports.sub_yes') ?? 'نعم' }}</option>
                    <option value="0" {{ (string)($filters['has_offer'] ?? '') === '0' ? 'selected' : '' }}>{{ __('reports.sub_no') ?? 'لا' }}</option>
                </select>
            </div>


            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sales_filter_has_coupon') ?? 'استخدم كوبون' }}</label>
                <select name="has_coupon" class="form-select">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    <option value="1" {{ (string)($filters['has_coupon'] ?? '') === '1' ? 'selected' : '' }}>{{ __('reports.sub_yes') ?? 'نعم' }}</option>
                    <option value="0" {{ (string)($filters['has_coupon'] ?? '') === '0' ? 'selected' : '' }}>{{ __('reports.sub_no') ?? 'لا' }}</option>
                </select>
            </div>


            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sales_filter_amount_from') ?? 'المبلغ من' }}</label>
                <input type="number" class="form-control" name="amount_from" value="{{ $filters['amount_from'] ?? '' }}" min="0" step="0.01">
            </div>


            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sales_filter_amount_to') ?? 'المبلغ إلى' }}</label>
                <input type="number" class="form-control" name="amount_to" value="{{ $filters['amount_to'] ?? '' }}" min="0" step="0.01">
            </div>


            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sales_filter_discount_from') ?? 'الخصم من' }}</label>
                <input type="number" class="form-control" name="discount_from" value="{{ $filters['discount_from'] ?? '' }}" min="0" step="0.01">
            </div>


            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.sales_filter_discount_to') ?? 'الخصم إلى' }}</label>
                <input type="number" class="form-control" name="discount_to" value="{{ $filters['discount_to'] ?? '' }}" min="0" step="0.01">
            </div>


            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.sales_filter_group_by') ?? 'تجميع حسب' }}</label>
                <select name="group_by" id="groupBySelect" class="form-select">
                    @foreach($groupByOptions as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['group_by'] ?? 'branch') === (string)$k ? 'selected' : '' }}>
                            {{ $lbl }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div class="col-12 d-flex justify-content-between flex-wrap gap-2 mt-2">
                <div class="alert alert-info mb-0 py-2 px-3">
                    <i class="mdi mdi-information-outline"></i>
                    <strong>{{ __('reports.sales_tip') ?? 'قيمة البيع تعتمد على إجمالي الاشتراك (total_amount).' }}</strong>
                </div>


                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line align-bottom me-1"></i> {{ __('reports.search') ?? 'بحث' }}
                    </button>


                    <a class="btn btn-soft-secondary" href="{{ route('sales_report.index') }}">
                        <i class="ri-refresh-line align-bottom me-1"></i> {{ __('reports.reset') ?? 'إعادة تعيين' }}
                    </a>


                    <a class="btn btn-soft-success" target="_blank" id="btnPrint"
                       href="{{ route('sales_report.index', array_merge(request()->all(), ['action' => 'print'])) }}">
                        <i class="ri-printer-line align-bottom me-1"></i> {{ __('reports.print') ?? 'طباعة' }}
                    </a>


                    <a class="btn btn-soft-success" id="btnExport"
                       href="{{ route('sales_report.index', array_merge(request()->all(), ['action' => 'export_excel'])) }}">
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
        <h5 class="card-title mb-0">{{ __('reports.sales_group_title') ?? 'ملخص التجميع' }}</h5>
        <div class="text-muted small">
            {{ __('reports.sales_group_hint') ?? 'يتغير حسب خيار (تجميع حسب).' }}
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped group-table mb-0" id="groupTable">
                <thead class="table-light">
                <tr>
                    <th style="min-width:180px">{{ __('reports.sales_group_col_name') ?? 'البند' }}</th>
                    <th>{{ __('reports.sales_group_col_subs') ?? 'عدد الاشتراكات' }}</th>
                    <th>{{ __('reports.sales_group_col_sales') ?? 'إجمالي المبيعات' }}</th>
                    <th>{{ __('reports.sales_group_col_discount') ?? 'إجمالي الخصم' }}</th>
                    <th>{{ __('reports.sales_group_col_pt') ?? 'مبيعات PT' }}</th>
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
        <h5 class="card-title mb-0">{{ __('reports.sales_table_title') ?? 'تفاصيل المبيعات (الاشتراكات)' }}</h5>
        <div class="text-muted small">
            {{ __('reports.sales_table_hint') ?? 'الجدول يدعم البحث والترتيب والتصفح.' }}
        </div>
    </div>


    <div class="card-body">
        <div class="table-responsive">
            <table id="salesTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                <thead class="table-light">
                <tr>
                    <th style="width:55px">{{ __('reports.serial') ?? '#' }}</th>
                    <th>{{ __('reports.sales_col_dates') ?? 'التواريخ' }}</th>
                    <th>{{ __('reports.sales_col_branch') ?? 'الفرع' }}</th>
                    <th>{{ __('reports.sales_col_member') ?? 'العضو' }}</th>
                    <th>{{ __('reports.sales_col_plan') ?? 'الخطة' }}</th>
                    <th>{{ __('reports.sales_col_status') ?? 'الحالة' }}</th>
                    <th>{{ __('reports.sales_col_source') ?? 'المصدر' }}</th>
                    <th>{{ __('reports.sales_col_discounts') ?? 'الخصومات' }}</th>
                    <th>{{ __('reports.sales_col_amounts') ?? 'المبالغ' }}</th>
                    <th>{{ __('reports.sales_col_offer_coupon') ?? 'عرض/كوبون' }}</th>
                    <th>{{ __('reports.sales_col_sales_employee') ?? 'موظف المبيعات' }}</th>
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
        obj.type_id = $form.find('[name="type_id"]').val() || '';
        obj.plan_id = $form.find('[name="plan_id"]').val() || '';
        obj.source = $form.find('[name="source"]').val() || '';
        obj.sales_employee_id = $form.find('[name="sales_employee_id"]').val() || '';

        obj.has_offer = $form.find('[name="has_offer"]').val() || '';
        obj.has_coupon = $form.find('[name="has_coupon"]').val() || '';

        obj.amount_from = $form.find('[name="amount_from"]').val() || '';
        obj.amount_to = $form.find('[name="amount_to"]').val() || '';

        obj.discount_from = $form.find('[name="discount_from"]').val() || '';
        obj.discount_to = $form.find('[name="discount_to"]').val() || '';

        obj.group_by = $form.find('[name="group_by"]').val() || 'branch';

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
        var baseUrl = "{{ route('sales_report.index') }}";
        var qs = buildQueryString(filters);

        var printUrl  = baseUrl + (qs ? ('?' + qs + '&action=print') : '?action=print');
        var exportUrl = baseUrl + (qs ? ('?' + qs + '&action=export_excel') : '?action=export_excel');

        $('#btnPrint').attr('href', printUrl);
        $('#btnExport').attr('href', exportUrl);
    }

    function loadKpis(filters) {
        var baseUrl = "{{ route('sales_report.index') }}";
        var data = $.extend({}, filters || {}, { action: 'metrics' });

        return $.ajax({
            url: baseUrl,
            type: "GET",
            data: data,
            success: function (res) {
                if (!res) return;

                $('#kpi_subs_count').text(parseInt(res.subs_count || 0, 10));
                $('#kpi_total_sales').text(res.total_sales || 0);
                $('#kpi_avg_sale').text(res.avg_sale || 0);

                $('#kpi_total_discount').text(res.total_discount || 0);
                $('#kpi_offer_discount').text(res.offer_discount || 0);
                $('#kpi_coupon_discount').text(res.coupon_discount || 0);

                $('#kpi_pt_addons_sales').text(res.pt_addons_sales || 0);

                $('#kpi_offers_used_count').text(parseInt(res.offers_used_count || 0, 10));
                $('#kpi_coupons_used_count').text(parseInt(res.coupons_used_count || 0, 10));
            }
        });
    }

    function loadGroup(filters) {
        var baseUrl = "{{ route('sales_report.index') }}";
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
                        '<td class="text-center">' + (r.subs_count || 0) + '</td>' +
                        '<td class="text-center">' + (r.total_sales || 0) + '</td>' +
                        '<td class="text-center">' + (r.total_discount || 0) + '</td>' +
                        '<td class="text-center">' + (r.pt_addons_sales || 0) + '</td>' +
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

    var table = $('#salesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        order: [[1, 'desc']], // dates column after serial
        ajax: {
            url: "{{ route('sales_report.index') }}",
            type: "GET",
            data: function (d) {
                var f = getFiltersObject();

                d.date_from = f.date_from;
                d.date_to = f.date_to;
                d.branch_ids = f.branch_ids;

                d.member_q = f.member_q;

                d.status = f.status;
                d.type_id = f.type_id;
                d.plan_id = f.plan_id;
                d.source = f.source;
                d.sales_employee_id = f.sales_employee_id;

                d.has_offer = f.has_offer;
                d.has_coupon = f.has_coupon;

                d.amount_from = f.amount_from;
                d.amount_to = f.amount_to;

                d.discount_from = f.discount_from;
                d.discount_to = f.discount_to;
            }
        },
        columnDefs: [
            { targets: [0,1,4,8,9], className: 'dt-cell-wrap' },
            { targets: [0], orderable: false, searchable: false }
        ],
        columns: [
            { data: 'rownum', name: 'rownum' },
            { data: 'date_block', name: 'ms.created_at' },
            { data: 'branch', name: 'b.name' },
            { data: 'member', name: 'member_name' },
            { data: 'plan', name: 'ms.plan_name' },
            { data: 'status', name: 'ms.status' },
            { data: 'source', name: 'ms.source' },
            { data: 'discounts', name: 'ms.total_discount' },
            { data: 'amounts', name: 'ms.total_amount' },
            { data: 'offers_coupons', name: 'offer_coupon' },
            { data: 'sales_employee', name: 'sales_employee_name' },
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
