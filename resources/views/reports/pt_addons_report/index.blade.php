@extends('layouts.master_table')

@section('title')
    {{ __('reports.pt_addons_report_title') ?? 'تقرير PT Add-ons' }}
@endsection

@section('content')
@php
    $rtl = app()->getLocale() === 'ar';

    $filters = $filters ?? [];
    $kpis = $kpis ?? [];
    $filterOptions = $filterOptions ?? [];

    $groupByOptions = $filterOptions['group_by'] ?? [];
    $sources = $filterOptions['sources'] ?? [];

    // Fix: when translation key is missing, Laravel returns the key itself (not null)
    $tr = function($key, $fallback){
        $v = __($key);
        return ($v === $key || $v === '') ? $fallback : $v;
    };

    // Source value label (for dropdown display)
    $sourceLabel = function($source) use ($tr){
        $s = strtolower(trim((string)$source));
        if ($s === 'reception') return $tr('reports.source_reception', 'الاستقبال');
        return (string)$source;
    };
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
            <h4 class="mb-sm-0">{{ __('reports.pt_addons_report_title') ?? 'تقرير PT Add-ons' }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">{{ __('reports.reports') ?? 'التقارير' }}</a></li>
                    <li class="breadcrumb-item active">{{ __('reports.pt_addons_report_title') ?? 'تقرير PT Add-ons' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- KPIs (Payment removed) --}}
<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-4">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.pt_kpi_total_amount') ?? 'إجمالي قيمة الإضافات' }}</small>
                        <h4 class="mb-0" id="kpi_total_amount">{{ (float)($kpis['total_amount_sum'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-primary text-primary">
                            <i class="ri-hand-coin-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.pt_kpi_addons_count') ?? 'عدد الإضافات' }}:
                    <span id="kpi_addons_count">{{ (int)($kpis['addons_count'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.pt_kpi_sessions_total') ?? 'إجمالي الحصص' }}</small>
                        <h4 class="mb-0" id="kpi_sessions_total">{{ (int)($kpis['sessions_total_sum'] ?? 0) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-success text-success">
                            <i class="ri-timer-2-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.pt_kpi_sessions_used') ?? 'مستخدم' }}:
                    <span id="kpi_sessions_used">{{ (int)($kpis['sessions_used_sum'] ?? 0) }}</span>
                    —
                    {{ __('reports.pt_kpi_sessions_remaining') ?? 'متبقي' }}:
                    <span id="kpi_sessions_remaining">{{ (int)($kpis['sessions_remaining_sum'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ __('reports.pt_kpi_unique') ?? 'إحصائيات' }}</small>
                        <h4 class="mb-0">
                            <span id="kpi_unique_members">{{ (int)($kpis['unique_members'] ?? 0) }}</span>
                            <small class="text-muted">{{ __('reports.pt_kpi_unique_members') ?? 'أعضاء' }}</small>
                        </h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-warning text-warning">
                            <i class="ri-user-3-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('reports.pt_kpi_unique_subs') ?? 'اشتراكات' }}:
                    <span id="kpi_unique_subs">{{ (int)($kpis['unique_subscriptions'] ?? 0) }}</span>
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Filters (Payment removed) --}}
<div class="card border shadow-none mb-3">
    <div class="card-body">
        <form method="get" action="{{ route('pt_addons_report.index') }}" class="row g-2 align-items-end" id="filtersForm">
            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.pt_filter_date_from') ?? 'من تاريخ' }}</label>
                <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.pt_filter_date_to') ?? 'إلى تاريخ' }}</label>
                <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>

            <div class="col-md-6">
                <label class="form-label mb-1">{{ __('reports.pt_filter_branches') ?? 'الفروع' }}</label>
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
                <label class="form-label mb-1">{{ __('reports.pt_filter_trainer') ?? 'المدرب' }}</label>
                <select name="trainer_id" class="form-select select2">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach($trainers as $t)
                        <option value="{{ $t->id }}" {{ (string)($filters['trainer_id'] ?? '') === (string)$t->id ? 'selected' : '' }}>
                            {{ $t->full_name }} {{ $t->code ? '(' . $t->code . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.pt_filter_member') ?? 'رقم العضو' }}</label>
                <input type="number" class="form-control" name="member_id" value="{{ $filters['member_id'] ?? '' }}" min="1" step="1">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.pt_filter_subscription') ?? 'رقم الاشتراك' }}</label>
                <input type="number" class="form-control" name="member_subscription_id" value="{{ $filters['member_subscription_id'] ?? '' }}" min="1" step="1">
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('reports.pt_filter_source') ?? 'المصدر' }}</label>
                <select name="source" class="form-select select2">
                    <option value="">{{ __('reports.sub_all') ?? 'الكل' }}</option>
                    @foreach(($sources ?? []) as $s)
                        <option value="{{ $s }}" {{ (string)($filters['source'] ?? '') === (string)$s ? 'selected' : '' }}>
                            {{ $sourceLabel($s) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.pt_filter_only_remaining') ?? 'المتبقي > 0' }}</label>
                <select name="only_remaining" class="form-select">
                    <option value="0" {{ (string)($filters['only_remaining'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('reports.sub_no') ?? 'لا' }}</option>
                    <option value="1" {{ (string)($filters['only_remaining'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('reports.sub_yes') ?? 'نعم' }}</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.pt_filter_sessions_from') ?? 'الحصص من' }}</label>
                <input type="number" class="form-control" name="sessions_from" value="{{ $filters['sessions_from'] ?? '' }}" min="0" step="1">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.pt_filter_sessions_to') ?? 'الحصص إلى' }}</label>
                <input type="number" class="form-control" name="sessions_to" value="{{ $filters['sessions_to'] ?? '' }}" min="0" step="1">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.pt_filter_amount_from') ?? 'القيمة من' }}</label>
                <input type="number" class="form-control" name="amount_from" value="{{ $filters['amount_from'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('reports.pt_filter_amount_to') ?? 'القيمة إلى' }}</label>
                <input type="number" class="form-control" name="amount_to" value="{{ $filters['amount_to'] ?? '' }}" min="0" step="0.01">
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('reports.pt_filter_group_by') ?? 'تجميع حسب' }}</label>
                <select name="group_by" class="form-select" id="groupBySelect">
                    @foreach(($groupByOptions ?? []) as $k => $lbl)
                        <option value="{{ $k }}" {{ (string)($filters['group_by'] ?? 'trainer') === (string)$k ? 'selected' : '' }}>
                            {{ $lbl }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 d-flex justify-content-end flex-wrap gap-2 mt-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ri-search-line align-bottom me-1"></i> {{ __('reports.search') ?? 'بحث' }}
                </button>

                <a class="btn btn-soft-secondary" href="{{ route('pt_addons_report.index') }}">
                    <i class="ri-refresh-line align-bottom me-1"></i> {{ __('reports.reset') ?? 'إعادة تعيين' }}
                </a>

                <a class="btn btn-soft-success" target="_blank" id="btnPrint"
                   href="{{ route('pt_addons_report.index', array_merge(request()->all(), ['action' => 'print'])) }}">
                    <i class="ri-printer-line align-bottom me-1"></i> {{ __('reports.print') ?? 'طباعة' }}
                </a>

                <a class="btn btn-soft-success" id="btnExport"
                   href="{{ route('pt_addons_report.index', array_merge(request()->all(), ['action' => 'export_excel'])) }}">
                    <i class="ri-file-excel-2-line align-bottom me-1"></i> {{ __('reports.export_excel') ?? 'تصدير Excel' }}
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Group Summary (Payment removed) --}}
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">{{ __('reports.pt_group_title') ?? 'ملخص التجميع' }}</h5>
        <div class="text-muted small">{{ __('reports.pt_group_hint') ?? 'يتغير حسب خيار (تجميع حسب).' }}</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped group-table mb-0" id="groupTable">
                <thead class="table-light">
                <tr>
                    <th style="min-width:180px">{{ __('reports.pt_group_col_name') ?? 'البند' }}</th>
                    <th>{{ __('reports.pt_group_col_count') ?? 'عدد الإضافات' }}</th>
                    <th>{{ __('reports.pt_group_col_total_amount') ?? 'إجمالي القيمة' }}</th>
                    <th>{{ __('reports.pt_group_col_sessions_total') ?? 'حصص' }}</th>
                    <th>{{ __('reports.pt_group_col_sessions_used') ?? 'مستخدم' }}</th>
                    <th>{{ __('reports.pt_group_col_sessions_remaining') ?? 'متبقي' }}</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="6" class="text-center text-muted py-3">{{ __('reports.loading') ?? 'جاري التحميل...' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Detail Table (Payment removed + PTA# removed) --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">{{ __('reports.pt_table_title') ?? 'تفاصيل PT Add-ons' }}</h5>
        <div class="text-muted small">{{ __('reports.pt_table_hint') ?? 'الجدول يدعم البحث والترتيب.' }}</div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="ptAddonsTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                <thead class="table-light">
                <tr>
                    <th>{{ __('reports.pt_col_date') ?? 'التاريخ' }}</th>
                    <th>{{ __('reports.pt_col_branch') ?? 'الفرع' }}</th>
                    <th>{{ __('reports.pt_col_member') ?? 'العضو' }}</th>
                    <th>{{ __('reports.pt_col_subscription') ?? 'الاشتراك' }}</th>
                    <th>{{ __('reports.pt_col_trainer') ?? 'المدرب' }}</th>

                    <th>{{ __('reports.pt_col_sessions_count') ?? 'إجمالي' }}</th>
                    <th>{{ __('reports.pt_col_sessions_used') ?? 'مستخدم' }}</th>
                    <th>{{ __('reports.pt_col_sessions_remaining') ?? 'متبقي' }}</th>

                    <th>{{ __('reports.pt_col_session_price') ?? 'سعر الحصة' }}</th>
                    <th>{{ __('reports.pt_col_total_amount') ?? 'الإجمالي' }}</th>

                    <th>{{ __('reports.pt_col_notes') ?? 'ملاحظات' }}</th>
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

        obj.trainer_id = $form.find('[name="trainer_id"]').val() || '';
        obj.member_id = $form.find('[name="member_id"]').val() || '';
        obj.member_subscription_id = $form.find('[name="member_subscription_id"]').val() || '';
        obj.source = $form.find('[name="source"]').val() || '';

        obj.only_remaining = $form.find('[name="only_remaining"]').val() || '0';

        obj.sessions_from = $form.find('[name="sessions_from"]').val() || '';
        obj.sessions_to = $form.find('[name="sessions_to"]').val() || '';

        obj.amount_from = $form.find('[name="amount_from"]').val() || '';
        obj.amount_to = $form.find('[name="amount_to"]').val() || '';

        obj.group_by = $form.find('[name="group_by"]').val() || 'trainer';

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
        var baseUrl = "{{ route('pt_addons_report.index') }}";
        var qs = buildQueryString(filters);

        var printUrl  = baseUrl + (qs ? ('?' + qs + '&action=print') : '?action=print');
        var exportUrl = baseUrl + (qs ? ('?' + qs + '&action=export_excel') : '?action=export_excel');

        $('#btnPrint').attr('href', printUrl);
        $('#btnExport').attr('href', exportUrl);
    }

    function loadKpis(filters) {
        var baseUrl = "{{ route('pt_addons_report.index') }}";
        var data = $.extend({}, filters || {}, { action: 'metrics' });

        return $.ajax({
            url: baseUrl,
            type: "GET",
            data: data,
            success: function (res) {
                if (!res) return;

                $('#kpi_addons_count').text(parseInt(res.addons_count || 0, 10));
                $('#kpi_total_amount').text(res.total_amount_sum || 0);

                $('#kpi_sessions_total').text(parseInt(res.sessions_total_sum || 0, 10));
                $('#kpi_sessions_used').text(parseInt(res.sessions_used_sum || 0, 10));
                $('#kpi_sessions_remaining').text(parseInt(res.sessions_remaining_sum || 0, 10));

                $('#kpi_unique_members').text(parseInt(res.unique_members || 0, 10));
                $('#kpi_unique_subs').text(parseInt(res.unique_subscriptions || 0, 10));
            }
        });
    }

    function loadGroup(filters) {
        var baseUrl = "{{ route('pt_addons_report.index') }}";
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
                        '<td class="text-center">' + (r.addons_count || 0) + '</td>' +
                        '<td class="text-center">' + (r.total_amount_sum || 0) + '</td>' +
                        '<td class="text-center">' + (r.sessions_total_sum || 0) + '</td>' +
                        '<td class="text-center">' + (r.sessions_used_sum || 0) + '</td>' +
                        '<td class="text-center">' + (r.sessions_remaining_sum || 0) + '</td>' +
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

    var table = $('#ptAddonsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('pt_addons_report.index') }}",
            type: "GET",
            data: function (d) {
                var f = getFiltersObject();

                d.date_from = f.date_from;
                d.date_to = f.date_to;
                d.branch_ids = f.branch_ids;

                d.trainer_id = f.trainer_id;
                d.member_id = f.member_id;
                d.member_subscription_id = f.member_subscription_id;
                d.source = f.source;

                d.only_remaining = f.only_remaining;

                d.sessions_from = f.sessions_from;
                d.sessions_to = f.sessions_to;

                d.amount_from = f.amount_from;
                d.amount_to = f.amount_to;

                d.group_by = f.group_by;
            }
        },
        columnDefs: [
            // member, subscription, notes
            { targets: [2, 3, 10], className: 'dt-cell-wrap' }
        ],
        columns: [
            { data: 'date', name: 'pta.created_at' },
            { data: 'branch', name: 'b.name' },
            { data: 'member', name: 'member_name', orderable: false, searchable: true },
            { data: 'subscription', name: 'pta.member_subscription_id', orderable: false, searchable: true },
            { data: 'trainer', name: 'trainer_name' },

            { data: 'sessions_count', name: 'pta.sessions_count' },
            { data: 'sessions_used', name: 'sessions_used' },
            { data: 'sessions_remaining', name: 'pta.sessions_remaining' },

            { data: 'session_price', name: 'pta.session_price' },
            { data: 'total_amount', name: 'pta.total_amount' },

            { data: 'notes', name: 'pta.notes' },
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
