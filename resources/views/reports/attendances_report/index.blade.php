@extends('layouts.master_table')

@section('title')
    {{ __('reports.attendances_report_title') ?? 'تقرير حضور الأعضاء' }}
@endsection

@section('content')
    @php
        $rtl = app()->getLocale() === 'ar';

        // helper similar to other screens
        $nameJsonOrText = function ($nameJsonOrText) {
            $decoded = json_decode($nameJsonOrText ?? '', true);
            if (is_array($decoded)) {
                return $decoded[app()->getLocale()] ?? ($decoded['ar'] ?? ($decoded['en'] ?? reset($decoded)));
            }
            return $nameJsonOrText ?? '';
        };

        $filters = $filters ?? [];
        $kpis = $kpis ?? [];

        // NEW: translated filter options from controller (if provided)
        $filterOptions = $filterOptions ?? [];

        // Methods (translated)
        $methods = [
            '' => __('reports.att_all') ?? 'الكل',
            'manual' => __('reports.att_method_manual') ?? 'Manual',
            'barcode' => __('reports.att_method_barcode') ?? 'Barcode',
        ];
        if (!empty($filterOptions['checkin_methods']) && is_array($filterOptions['checkin_methods'])) {
            $methods = ['' => __('reports.att_all') ?? 'الكل'];
            foreach ($filterOptions['checkin_methods'] as $opt) {
                $methods[(string) ($opt['value'] ?? '')] = (string) ($opt['label'] ?? '');
            }
        }

        // Cancelled options
        $cancelOptions = [
            '' => __('reports.att_all') ?? 'الكل',
            '0' => __('reports.att_not_cancelled') ?? 'غير ملغي',
            '1' => __('reports.att_cancelled') ?? 'ملغي',
        ];

        // Member status (translated) - values fixed: active/inactive/frozen
        $memberStatusOptions = [
            '' => __('reports.att_all') ?? 'الكل',
            'active' => __('reports.att_status_active') ?? 'Active',
            'inactive' => __('reports.att_status_inactive') ?? 'Inactive',
            'frozen' => __('reports.att_status_frozen') ?? 'Frozen',
        ];
        if (!empty($filterOptions['member_statuses']) && is_array($filterOptions['member_statuses'])) {
            $memberStatusOptions = ['' => __('reports.att_all') ?? 'الكل'];
            foreach ($filterOptions['member_statuses'] as $opt) {
                $memberStatusOptions[(string) ($opt['value'] ?? '')] = (string) ($opt['label'] ?? '');
            }
        }

        // Day options (translated) - values fixed: monday..sunday (to match backend normalization)
        $dayOptions = [
            '' => __('reports.att_all') ?? 'الكل',
            'saturday' => __('reports.att_day_sat') ?? 'Saturday',
            'sunday' => __('reports.att_day_sun') ?? 'Sunday',
            'monday' => __('reports.att_day_mon') ?? 'Monday',
            'tuesday' => __('reports.att_day_tue') ?? 'Tuesday',
            'wednesday' => __('reports.att_day_wed') ?? 'Wednesday',
            'thursday' => __('reports.att_day_thu') ?? 'Thursday',
            'friday' => __('reports.att_day_fri') ?? 'Friday',
        ];
        if (!empty($filterOptions['day_keys']) && is_array($filterOptions['day_keys'])) {
            $dayOptions = ['' => __('reports.att_all') ?? 'الكل'];
            foreach ($filterOptions['day_keys'] as $opt) {
                $dayOptions[(string) ($opt['value'] ?? '')] = (string) ($opt['label'] ?? '');
            }
        }
    @endphp

    <style>
        .select2-container {
            width: 100% !important;
            max-width: 100% !important
        }

        .select2-dropdown {
            z-index: 2000
        }

        .kpi-card .avatar-sm .avatar-title {
            width: 2.5rem;
            height: 2.5rem
        }

        .dt-cell-wrap {
            white-space: normal !important
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">
                    {{ __('reports.attendances_report_title') ?? 'تقرير حضور الأعضاء' }}
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a
                                href="javascript:void(0)">{{ __('reports.reports') ?? 'التقارير' }}</a></li>
                        <li class="breadcrumb-item active">
                            {{ __('reports.attendances_report_title') ?? 'تقرير حضور الأعضاء' }}</li>
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
                            <small class="text-muted">{{ __('reports.att_kpi_total') ?? 'إجمالي الحضور' }}</small>
                            <h4 class="mb-0" id="kpi_total">{{ (int) ($kpis['total'] ?? 0) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title rounded bg-soft-primary text-primary">
                                <i class="ri-file-list-3-line fs-20"></i>
                            </span>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1">
                        {{ __('reports.att_kpi_unique_members') ?? 'أعضاء فريدين' }}:
                        <span id="kpi_unique_members">{{ (int) ($kpis['unique_members'] ?? 0) }}</span>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border mb-0 kpi-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted">{{ __('reports.att_kpi_not_cancelled') ?? 'غير ملغي' }}</small>
                            <h4 class="mb-0" id="kpi_not_cancelled">{{ (int) ($kpis['not_cancelled'] ?? 0) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title rounded bg-soft-success text-success">
                                <i class="ri-check-double-line fs-20"></i>
                            </span>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1">
                        {{ __('reports.att_kpi_branches_used') ?? 'فروع مستخدمة' }}:
                        <span id="kpi_branches_used">{{ (int) ($kpis['branches_used'] ?? 0) }}</span>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border mb-0 kpi-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted">{{ __('reports.att_kpi_cancelled') ?? 'ملغي' }}</small>
                            <h4 class="mb-0" id="kpi_cancelled">{{ (int) ($kpis['cancelled'] ?? 0) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title rounded bg-soft-danger text-danger">
                                <i class="ri-close-circle-line fs-20"></i>
                            </span>
                        </div>
                    </div>
                    @php
                        $total = (int) ($kpis['total'] ?? 0);
                        $cancelled = (int) ($kpis['cancelled'] ?? 0);
                        $rate = $total > 0 ? round(($cancelled / $total) * 100, 2) : 0;
                    @endphp
                    <small class="text-muted d-block mt-1">
                        {{ __('reports.att_kpi_cancel_rate') ?? 'نسبة الإلغاء' }}:
                        <span id="kpi_cancel_rate">{{ $rate }}</span>%
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border mb-0 kpi-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted">{{ __('reports.att_kpi_methods') ?? 'طرق الدخول' }}</small>
                            <h4 class="mb-0">

                                {{ __('reports.att_method_barcode') ?? 'Barcode' }}:
                                <span id="kpi_barcode">{{ (int) ($kpis['barcode'] ?? 0) }}</span>
                            </h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title rounded bg-soft-warning text-warning">
                                <i class="ri-barcode-line fs-20"></i>
                            </span>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1">
                        {{ __('reports.att_method_manual') ?? 'Manual' }}:
                        <span id="kpi_manual">{{ (int) ($kpis['manual'] ?? 0) }}</span>
                        | {{ __('reports.att_kpi_guests_total') ?? 'إجمالي الضيوف' }}:
                        <span id="kpi_guests_total">{{ (int) ($kpis['guests_total'] ?? 0) }}</span>
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Bar --}}
    <div class="card border shadow-none mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('attendances_report.index') }}" class="row g-2 align-items-end"
                id="filtersForm">
                <div class="col-md-3">
                    <label class="form-label mb-1">{{ __('reports.att_filter_date_from') ?? 'من تاريخ' }}</label>
                    <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">{{ __('reports.att_filter_date_to') ?? 'إلى تاريخ' }}</label>
                    <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label mb-1">{{ __('reports.att_filter_branches') ?? 'الفروع' }}</label>
                    <select name="branch_ids[]" id="filterBranches" class="form-select select2" multiple
                        data-placeholder="{{ __('reports.att_filter_branches') ?? 'الفروع' }}">
                        @foreach($branches as $b)
                            @php
                                $bn = method_exists($b, 'getTranslation') ? $b->getTranslation('name', app()->getLocale()) : $nameJsonOrText($b->name);
                            @endphp
                            <option value="{{ $b->id }}" {{ in_array((string) $b->id, array_map('strval', (array) ($filters['branch_ids'] ?? [])), true) ? 'selected' : '' }}>
                                {{ $bn }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">{{ __('reports.multiselecthint') ?? 'يمكن اختيار أكثر من فرع' }}</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">{{ __('reports.att_filter_member') ?? 'بحث العضو' }}</label>
                    <input type="text" class="form-control" name="member_term" value="{{ $filters['member_term'] ?? '' }}"
                        placeholder="{{ __('reports.att_filter_member_hint') ?? 'كود/اسم/موبايل/واتساب' }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('reports.att_filter_member_status') ?? 'حالة العضو' }}</label>
                    <select name="member_status" class="form-select">
                        @foreach($memberStatusOptions as $k => $lbl)
                            <option value="{{ $k }}" {{ (string) ($filters['member_status'] ?? '') === (string) $k ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('reports.att_filter_method') ?? 'طريقة الدخول' }}</label>
                    <select name="checkin_method" class="form-select">
                        @foreach($methods as $k => $lbl)
                            <option value="{{ $k }}" {{ (string) ($filters['checkin_method'] ?? '') === (string) $k ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('reports.att_filter_cancelled') ?? 'الإلغاء' }}</label>
                    <select name="is_cancelled" class="form-select">
                        @foreach($cancelOptions as $k => $lbl)
                            <option value="{{ $k }}" {{ (string) ($filters['is_cancelled'] ?? '') === (string) $k ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">{{ __('reports.att_filter_recorded_by') ?? 'مسجل بواسطة' }}</label>
                    <select name="recorded_by[]" id="filterRecordedBy" class="form-select select2" multiple
                        data-placeholder="{{ __('reports.att_filter_recorded_by') ?? 'مسجل بواسطة' }}">
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ in_array((string) $u->id, array_map('strval', (array) ($filters['recorded_by'] ?? [])), true) ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('reports.att_filter_device') ?? 'Device ID' }}</label>
                    <input type="number" class="form-control" name="device_id" value="{{ $filters['device_id'] ?? '' }}"
                        min="0">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('reports.att_filter_gate') ?? 'Gate ID' }}</label>
                    <input type="number" class="form-control" name="gate_id" value="{{ $filters['gate_id'] ?? '' }}"
                        min="0">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('reports.att_filter_day_key') ?? 'Day' }}</label>
                    <select name="day_key" class="form-select">
                        @foreach($dayOptions as $k => $lbl)
                            <option value="{{ $k }}" {{ (string) ($filters['day_key'] ?? '') === (string) $k ? 'selected' : '' }}>
                                {{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('reports.att_filter_subscription') ?? 'Subscription ID' }}</label>
                    <input type="number" class="form-control" name="subscription_id"
                        value="{{ $filters['subscription_id'] ?? '' }}" min="0">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">{{ __('reports.att_filter_pt_addon') ?? 'PT Addon ID' }}</label>
                    <input type="number" class="form-control" name="pt_addon_id" value="{{ $filters['pt_addon_id'] ?? '' }}"
                        min="0">
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">{{ __('reports.att_filter_notes') ?? 'ملاحظات' }}</label>
                    <input type="text" class="form-control" name="notes" value="{{ $filters['notes'] ?? '' }}"
                        placeholder="{{ __('reports.att_filter_notes_hint') ?? 'يبحث داخل Notes' }}">
                </div>

                <div class="col-12 d-flex justify-content-between flex-wrap gap-2 mt-2">
                    <div class="alert alert-info mb-0 py-2 px-3">
                        <i class="mdi mdi-information-outline"></i>
                        <strong>{{ __('reports.att_tip') ?? 'يمكنك استخدام البحث العام داخل الجدول أيضاً.' }}</strong>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-search-line align-bottom me-1"></i> {{ __('reports.search') ?? 'بحث' }}
                        </button>

                        <a class="btn btn-soft-secondary" href="{{ route('attendances_report.index') }}">
                            <i class="ri-refresh-line align-bottom me-1"></i> {{ __('reports.reset') ?? 'إعادة تعيين' }}
                        </a>

                        {{-- Print (same filters) --}}
                        <a class="btn btn-soft-success" target="_blank" id="btnPrint"
                            href="{{ route('attendances_report.index', array_merge(request()->all(), ['action' => 'print'])) }}">
                            <i class="ri-printer-line align-bottom me-1"></i> {{ __('reports.print') ?? 'طباعة' }}
                        </a>

                        {{-- Export XLSX (same filters) --}}
                        <a class="btn btn-soft-success" id="btnExport"
                            href="{{ route('attendances_report.index', array_merge(request()->all(), ['action' => 'export_excel'])) }}">
                            <i class="ri-file-excel-2-line align-bottom me-1"></i>
                            {{ __('reports.export_excel') ?? 'تصدير Excel' }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="card-title mb-0">
                {{ __('reports.att_table_title') ?? 'تفاصيل الحضور' }}
            </h5>
            <div class="text-muted small">
                <i class="ri-information-line align-bottom me-1"></i>
                {{ __('reports.att_table_hint') ?? 'الجدول يدعم البحث، الترتيب، والتصفح.' }}
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                {{-- ملاحظة: لا يوجد عمود ID، وتم دمج الأعمدة (العضو/الإلغاء/الخطة/PT/الجهاز+البوابة) --}}
                <table id="attTable" class="table table-bordered dt-responsive nowrap table-striped align-middle"
                    style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('reports.att_col_date_time') ?? 'التاريخ / الوقت' }}</th>
                            <th>{{ __('reports.att_col_branch') ?? 'الفرع' }}</th>
                            <th>{{ __('reports.att_col_member') ?? 'بيانات العضو' }}</th>
                            <th>{{ __('reports.att_col_member_status') ?? 'حالة العضو' }}</th>
                            <th>{{ __('reports.att_col_method') ?? 'طريقة الدخول' }}</th>
                            <th>{{ __('reports.att_col_recorded_by') ?? 'مسجل بواسطة' }}</th>
                            <th>{{ __('reports.att_col_cancel') ?? 'الإلغاء' }}</th>
                            <th>{{ __('reports.att_col_plan') ?? 'الخطة' }}</th>
                            <th>{{ __('reports.att_col_pt') ?? 'PT' }}</th>
                            <th>{{ __('reports.att_col_device_gate') ?? 'الجهاز / البوابة' }}</th>
                            <th>{{ __('reports.att_col_day') ?? 'اليوم' }}</th>
                            <th>{{ __('reports.att_col_notes') ?? 'ملاحظات' }}</th>
                            <th>{{ __('reports.att_col_guests') ?? 'الضيوف' }}</th>
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
                // basic inputs/selects
                obj.date_from = $form.find('[name="date_from"]').val() || '';
                obj.date_to = $form.find('[name="date_to"]').val() || '';
                obj.member_term = $form.find('[name="member_term"]').val() || '';
                obj.member_status = $form.find('[name="member_status"]').val() || '';
                obj.checkin_method = $form.find('[name="checkin_method"]').val() || '';
                obj.is_cancelled = $form.find('[name="is_cancelled"]').val() || '';
                obj.device_id = $form.find('[name="device_id"]').val() || '';
                obj.gate_id = $form.find('[name="gate_id"]').val() || '';
                obj.day_key = $form.find('[name="day_key"]').val() || '';
                obj.subscription_id = $form.find('[name="subscription_id"]').val() || '';
                obj.pt_addon_id = $form.find('[name="pt_addon_id"]').val() || '';
                obj.notes = $form.find('[name="notes"]').val() || '';

                // multi selects
                obj.branch_ids = $('#filterBranches').val() || [];
                obj.recorded_by = $('#filterRecordedBy').val() || [];

                return obj;
            }

            function buildQueryString(filters) {
                var params = new URLSearchParams();

                Object.keys(filters || {}).forEach(function (k) {
                    var v = filters[k];

                    if (Array.isArray(v)) {
                        // send arrays as repeated keys (compatible with Laravel)
                        v.forEach(function (item) {
                            if (item !== null && item !== undefined && String(item).trim() !== '') {
                                // keep original field names (branch_ids[], recorded_by[])
                                if (k === 'branch_ids') params.append('branch_ids[]', item);
                                else if (k === 'recorded_by') params.append('recorded_by[]', item);
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
                var baseUrl = "{{ route('attendances_report.index') }}";

                var qs = buildQueryString(filters);

                var printUrl = baseUrl + (qs ? ('?' + qs + '&action=print') : '?action=print');
                var exportUrl = baseUrl + (qs ? ('?' + qs + '&action=export_excel') : '?action=export_excel');

                $('#btnPrint').attr('href', printUrl);
                $('#btnExport').attr('href', exportUrl);
            }

            function loadKpis(filters) {
                var baseUrl = "{{ route('attendances_report.index') }}";

                var data = $.extend({}, filters || {}, { action: 'metrics' });

                return $.ajax({
                    url: baseUrl,
                    type: "GET",
                    data: data,
                    success: function (res) {
                        if (!res) return;

                        var total = parseInt(res.total || 0, 10);
                        var cancelled = parseInt(res.cancelled || 0, 10);
                        var rate = total > 0 ? Math.round((cancelled / total) * 10000) / 100 : 0;

                        $('#kpi_total').text(total);
                        $('#kpi_unique_members').text(parseInt(res.unique_members || 0, 10));
                        $('#kpi_cancelled').text(cancelled);
                        $('#kpi_not_cancelled').text(parseInt(res.not_cancelled || 0, 10));
                        $('#kpi_manual').text(parseInt(res.manual || 0, 10));
                        $('#kpi_barcode').text(parseInt(res.barcode || 0, 10));
                        $('#kpi_branches_used').text(parseInt(res.branches_used || 0, 10));
                        $('#kpi_guests_total').text(parseInt(res.guests_total || 0, 10));
                        $('#kpi_cancel_rate').text(rate);
                    }
                });
            }

            // Select2
            if ($.fn && $.fn.select2) {
                // dropdownParent: to avoid clipping inside cards
                var $form = $('#filtersForm');

                $('.select2').select2({
                    width: '100%',
                    allowClear: true,
                    closeOnSelect: false,
                    dir: isRtl ? 'rtl' : 'ltr',
                    dropdownParent: $form.length ? $form : $(document.body)
                });
            }

            // DataTable (server-side)
            if (!($.fn && $.fn.DataTable)) return;

            var table = $('#attTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 25,

                // IMPORTANT: columns order MUST match controller safe ordering map (0..12)
                order: [[0, 'desc']],

                ajax: {
                    url: "{{ route('attendances_report.index') }}",
                    type: "GET",
                    data: function (d) {
                        var f = getFiltersObject();

                        d.date_from = f.date_from;
                        d.date_to = f.date_to;
                        d.checkin_method = f.checkin_method;
                        d.is_cancelled = f.is_cancelled;
                        d.member_term = f.member_term;
                        d.member_status = f.member_status;
                        d.device_id = f.device_id;
                        d.gate_id = f.gate_id;
                        d.day_key = f.day_key;
                        d.subscription_id = f.subscription_id;
                        d.pt_addon_id = f.pt_addon_id;
                        d.notes = f.notes;

                        d.branch_ids = f.branch_ids;
                        d.recorded_by = f.recorded_by;
                    }
                },

                columnDefs: [
                    { targets: [0, 2, 6, 7, 8, 9], className: 'dt-cell-wrap' }
                ],

                columns: [
                    // 0 - Date/Time (HTML block from server). We also add row number prefix as a small enhancement.
                    {
                        data: 'attendance_dt',
                        name: 'attendance_dt',
                        render: function (data, type, row, meta) {
                            var idx = meta.row + meta.settings._iDisplayStart + 1;
                            return '<div class="d-flex flex-column">' +
                                '<small class="text-muted">#' + idx + '</small>' +
                                (data || '-') +
                                '</div>';
                        }
                    },

                    // 1 - Branch
                    { data: 'branch', name: 'branch' },

                    // 2 - Member block (name + code + phone)
                    { data: 'member_block', name: 'member_block', orderable: true, searchable: true },

                    // 3 - Member status (translated)
                    { data: 'member_status', name: 'member_status' },

                    // 4 - Method (translated)
                    { data: 'checkin_method', name: 'checkin_method' },

                    // 5 - Recorded by (already solved in backend with fallback)
                    { data: 'recorded_by', name: 'recorded_by' },

                    // 6 - Cancel block (badge + dt + by)
                    { data: 'cancel_block', name: 'cancel_block', orderable: true, searchable: true },

                    // 7 - Plan block (plan + dates)
                    { data: 'plan_block', name: 'plan_block', orderable: true, searchable: true },

                    // 8 - PT block (yes/no + trainer)
                    { data: 'pt_block', name: 'pt_block', orderable: true, searchable: true },

                    // 9 - Device/Gate block
                    { data: 'device_gate', name: 'device_gate' },

                    // 10 - Day (translated text)
                    { data: 'day_text', name: 'day_text' },

                    // 11 - Notes
                    { data: 'notes', name: 'notes' },

                    // 12 - Guests
                    { data: 'guests_count', name: 'guests_count', searchable: false }
                ]
            });

            // لحظي: تطبيق الفلاتر عند الضغط على "بحث" بدون Reload
            $('#filtersForm').on('submit', function (e) {
                e.preventDefault();

                var filters = getFiltersObject();

                updateActionLinks(filters);

                // reload table & KPIs
                table.ajax.reload(null, true);
                loadKpis(filters);

                // (اختياري) تحديث الـ URL بدون reload (لا يؤثر على التنسيق)
                try {
                    var qs = buildQueryString(filters);
                    var newUrl = window.location.pathname + (qs ? ('?' + qs) : '');
                    window.history.replaceState({}, document.title, newUrl);
                } catch (err) { }
            });

            // تحميل KPIs أول مرة بناءً على القيم الحالية في الفورم (بدون الاعتماد على querystring)
            try {
                var initialFilters = getFiltersObject();
                updateActionLinks(initialFilters);
                loadKpis(initialFilters);
            } catch (e) {
                // ignore
            }
        });
    </script>
@endsection
