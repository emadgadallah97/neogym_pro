@extends('layouts.master_table')

@section('title')
    {{ trans('hr.payrolls') }} | {{ trans('main_trans.title') }}
@endsection

@section('content')

@php
    $kpis = $kpis ?? [
        'total_rows'=>0,'draft'=>0,'approved'=>0,'paid'=>0,
        'sum_base'=>0,'sum_overtime'=>0,'sum_allowances'=>0,'sum_advances'=>0,'sum_deductions'=>0,'sum_gross'=>0,'sum_net'=>0,
    ];

    // ✅ New (from controller)
    $attendanceSummary = $attendanceSummary ?? [];
    $workDays = (int)($workDays ?? 26);
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
            <h4 class="mb-sm-0 font">
                <i class="ri-money-dollar-circle-line me-1"></i>
                {{ trans('hr.payrolls') }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('hr.index') }}">{{ trans('hr.title') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('hr.payrolls') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div id="page-alerts" class="mb-3"></div>

{{-- KPIs --}}
<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ trans('hr.total_rows') ?? 'عدد السجلات' }}</small>
                        <h4 class="mb-0" id="kpi_total_rows">{{ (int)$kpis['total_rows'] }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-primary text-primary">
                            <i class="ri-file-list-3-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ trans('hr.draft') ?? 'مسودة' }}: <span id="kpi_draft">{{ (int)$kpis['draft'] }}</span>
                    | {{ trans('hr.approved') ?? 'معتمد' }}: <span id="kpi_approved">{{ (int)$kpis['approved'] }}</span>
                    | {{ trans('hr.paid') ?? 'مصروف' }}: <span id="kpi_paid">{{ (int)$kpis['paid'] }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ trans('hr.total_salary_base') ?? 'إجمالي الأساسي' }}</small>
                        <h4 class="mb-0" id="kpi_sum_base">{{ number_format((float)$kpis['sum_base'], 2) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-info text-info">
                            <i class="ri-money-dollar-circle-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ trans('hr.total_gross') ?? 'إجمالي الإجمالي' }}:
                    <span id="kpi_sum_gross">{{ number_format((float)$kpis['sum_gross'], 2) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ trans('hr.total_overtime') ?? 'إجمالي الإضافي' }}</small>
                        <h4 class="mb-0" id="kpi_sum_overtime">{{ number_format((float)$kpis['sum_overtime'], 2) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-warning text-warning">
                            <i class="ri-time-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ trans('hr.total_allowances') ?? 'إجمالي البدلات' }}:
                    <span id="kpi_sum_allowances">{{ number_format((float)$kpis['sum_allowances'], 2) }}</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border mb-0 kpi-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">{{ trans('hr.total_net') ?? 'إجمالي الصافي' }}</small>
                        <h4 class="mb-0" id="kpi_sum_net">{{ number_format((float)$kpis['sum_net'], 2) }}</h4>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title rounded bg-soft-success text-success">
                            <i class="ri-wallet-3-line fs-20"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ trans('hr.total_deductions') ?? 'إجمالي الخصومات' }}:
                    <span id="kpi_sum_deductions">{{ number_format((float)$kpis['sum_deductions'], 2) }}</span>
                    | {{ trans('hr.total_advances') ?? 'إجمالي السلف' }}:
                    <span id="kpi_sum_advances">{{ number_format((float)$kpis['sum_advances'], 2) }}</span>
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('payrolls.index') }}" class="card border-0 shadow-sm mb-3" id="filtersForm">
    <div class="card-body">
        <div class="row g-3 align-items-end">

            <div class="col-md-3">
                <label class="form-label font">{{ trans('hr.branch') }}</label>
                <select name="branch_id" id="filter_branch_id" class="form-select font">
                    <option value="">{{ trans('hr.select_branch') }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ (int)$branchId === (int)$b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label font">{{ trans('hr.employee') }}</label>
                <select name="employee_id" id="filter_employee_id" class="form-select font">
                    <option value="">{{ trans('hr.all_employees') }}</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" {{ (int)$employeeId === (int)$e->id ? 'selected' : '' }}>
                            {{ $e->full_name }} ({{ $e->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label font">{{ trans('hr.month') }}</label>
                <input type="month" name="month" id="filter_month" class="form-control font" value="{{ $monthFilter }}">
            </div>

            <div class="col-md-2">
                <label class="form-label font">{{ trans('hr.status') }}</label>
                <select name="status" id="filter_status" class="form-select font">
                    <option value="">{{ trans('hr.all') ?? 'الكل' }}</option>
                    <option value="draft" {{ $statusFilter === 'draft' ? 'selected' : '' }}>{{ trans('hr.draft') ?? 'مسودة' }}</option>
                    <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>{{ trans('hr.approved') ?? 'معتمد' }}</option>
                    <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>{{ trans('hr.paid') ?? 'مصروف' }}</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary font w-100">
                    <i class="ri-filter-3-line me-1"></i> {{ trans('hr.filter') }}
                </button>
            </div>

            <div class="col-md-12 d-flex justify-content-between flex-wrap gap-2 mt-2">
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-success font" id="btnOpenGenerate">
                        <i class="ri-add-line me-1"></i> {{ trans('hr.generate_payrolls') ?? 'توليد الرواتب' }}
                    </button>

                    <button type="button" class="btn btn-soft-warning font" id="btnOpenCancelDrafts">
                        <i class="ri-close-circle-line me-1"></i> {{ trans('hr.cancel_drafts') ?? 'إلغاء (مسودات)' }}
                    </button>

                    <button type="button" class="btn btn-soft-primary font" id="btnApproveMonth">
                        <i class="ri-check-line me-1"></i> {{ trans('hr.approve_payrolls') ?? 'اعتماد الرواتب' }}
                    </button>

                    <button type="button" class="btn btn-soft-success font" id="btnOpenPay">
                        <i class="ri-wallet-3-line me-1"></i> {{ trans('hr.pay_payrolls') ?? 'صرف الرواتب' }}
                    </button>

                    <a class="btn btn-soft-secondary font" target="_blank" id="btnPrint"
                       href="{{ route('payrolls.index', array_merge(request()->all(), ['action' => 'print'])) }}">
                        <i class="ri-printer-line align-bottom me-1"></i> {{ trans('hr.print') ?? 'طباعة' }}
                    </a>

                    <a class="btn btn-soft-success font" id="btnExport"
                       href="{{ route('payrolls.index', array_merge(request()->all(), ['action' => 'export_excel'])) }}">
                        <i class="ri-file-excel-2-line align-bottom me-1"></i> {{ trans('hr.export_excel') ?? 'تصدير Excel' }}
                    </a>
                </div>

                <div class="text-muted small font">
                    {{ trans('hr.note_generate_snapshot_method') ?? 'ملاحظة: يتم حفظ طريقة الصرف Snapshot من الموظف وقت التوليد.' }}
                </div>
            </div>

        </div>
    </div>
</form>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0 font">{{ trans('hr.payrolls_list') ?? 'قائمة الرواتب' }}</h5>
    </div>

    <div class="card-body">
        <table id="payrollsTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ trans('hr.employee') }}</th>
                    <th>{{ trans('hr.month') }}</th>
                    <th>{{ trans('hr.base_salary') ?? 'الأساسي' }}</th>
                    <th>{{ trans('hr.overtime') ?? 'إضافي' }}</th>
                    <th>{{ trans('hr.allowances') ?? 'بدلات/حوافز' }}</th>
                    <th>{{ trans('hr.advances') ?? 'سلف' }}</th>
                    <th>{{ trans('hr.deductions') ?? 'خصومات/جزاءات' }}</th>
                    <th>{{ trans('hr.net_salary') ?? 'الصافي' }}</th>

                    {{-- ✅ merged --}}
                    <th>{{ trans('hr.payment_info') ?? 'الصرف (الطريقة/البيان)' }}</th>

                    {{-- ✅ new attendance column --}}
                    <th>{{ trans('hr.attendance_days') ?? 'الحضور' }}</th>

                    <th>{{ trans('hr.status') }}</th>
                    <th>{{ trans('hr.payment_date') ?? 'تاريخ الصرف' }}</th>
                    <th>{{ trans('hr.actions') ?? 'إجراءات' }}</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 0; @endphp
                @foreach($rows as $r)
                    @php
                        $i++;

                        $att = $attendanceSummary[(int)$r->employee_id] ?? null;
                        $presentDays = (int)($att['present_days'] ?? 0);
                        $lateDays    = (int)($att['late_days'] ?? 0);
                        $halfDays    = (int)($att['halfday_days'] ?? 0);
                        $absentDays  = (int)($att['absent_days'] ?? 0);
                        $totalHours  = (float)($att['total_hours'] ?? 0);

                        $payMethod  = $r->payment_method ?? '—';
                        $payDetails = $r->salary_transfer_details ?? '';
                    @endphp

                    <tr>
                        <td>{{ $i }}</td>

                        <td class="font">
                            {{ $r->employee?->full_name ?? '' }}
                            <small class="text-muted">({{ $r->employee?->code ?? '' }})</small>
                        </td>

                        <td class="text-center">
                            <code>{{ $r->month ? \Carbon\Carbon::parse($r->month)->format('Y-m') : '' }}</code>
                        </td>

                        <td class="text-end">{{ number_format((float)$r->base_salary, 2) }}</td>
                        <td class="text-end">{{ number_format((float)$r->overtime_amount, 2) }}</td>
                        <td class="text-end">{{ number_format((float)$r->allowances_amount, 2) }}</td>
                        <td class="text-end">{{ number_format((float)$r->advances_deduction, 2) }}</td>
                        <td class="text-end">{{ number_format((float)$r->deductions_amount, 2) }}</td>
                        <td class="text-end fw-bold">{{ number_format((float)$r->net_salary, 2) }}</td>

                        {{-- ✅ payment info merged --}}
                        <td class="font dt-cell-wrap">
                            <span class="badge bg-light text-dark">{{ $payMethod }}</span>
                            <div class="mt-1">
                                <small class="text-muted">{{ trim($payDetails) !== '' ? $payDetails : '—' }}</small>
                            </div>
                        </td>

                        {{-- ✅ attendance --}}
                        <td class="text-center font dt-cell-wrap">
                            <span class="badge bg-soft-primary text-primary">
                                {{ $presentDays }} / {{ $workDays }}
                            </span>
                            <div class="mt-1">
                                <small class="text-muted">
                                    {{ trans('hr.late') ?? 'تأخير' }}: {{ $lateDays }}
                                    | {{ trans('hr.halfday') ?? 'نصف' }}: {{ $halfDays }}
                                    | {{ trans('hr.absent') ?? 'غياب' }}: {{ $absentDays }}
                                    | {{ trans('hr.hours') ?? 'ساعات' }}: {{ number_format($totalHours, 2) }}
                                </small>
                            </div>
                        </td>

                        <td class="text-center">
                            @if($r->status === 'draft')
                                <span class="badge bg-secondary">{{ trans('hr.draft') ?? 'مسودة' }}</span>
                            @elseif($r->status === 'approved')
                                <span class="badge bg-primary">{{ trans('hr.approved') ?? 'معتمد' }}</span>
                            @else
                                <span class="badge bg-success">{{ trans('hr.paid') ?? 'مصروف' }}</span>
                            @endif
                        </td>

                        <td class="text-center">
                            {{ $r->payment_date ? \Carbon\Carbon::parse($r->payment_date)->toDateString() : '—' }}
                        </td>

                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-soft-info btn-sm btn-details"
                                    data-employee-id="{{ $r->employee_id }}"
                                    data-branch-id="{{ $r->branch_id }}"
                                    data-month="{{ $r->month ? \Carbon\Carbon::parse($r->month)->format('Y-m') : '' }}"
                                    data-employee-name="{{ $r->employee?->full_name ?? '' }}"
                                    data-employee-code="{{ $r->employee?->code ?? '' }}">
                                <i class="ri-eye-line me-1"></i> {{ trans('hr.details') ?? 'تفاصيل' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Modals (split to partials) --}}
@include('hr.payrolls.partials.generate_modal')
@include('hr.payrolls.partials.cancel_drafts_modal')
@include('hr.payrolls.partials.pay_modal')
@include('hr.payrolls.partials.breakdown_modal')

{{-- breakdown_modal intentionally NOT included (will be created separately) --}}

<script>
$(document).ready(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

    function toast(type, message) {
        if (typeof Swal !== 'undefined' && Swal.mixin) {
            const Toast = Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:2500, timerProgressBar:true });
            Toast.fire({ icon:type, title:(type==='success'?'Success ! ':'Error ! ') + message });
            return;
        }
        var klass = (type === 'success') ? 'alert-success' : 'alert-danger';
        var title = (type === 'success') ? 'Success !' : 'Error !';
        $('#page-alerts').html(
            '<div class="alert '+klass+' alert-dismissible fade show" role="alert">'+
            '<strong>'+title+'</strong> '+message+
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
        );
    }
    function toastSuccess(msg){ toast('success', msg); }
    function toastError(msg){ toast('error', msg); }

    function setLoading(btn, loading, htmlNormal){
        if (loading) btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
        else btn.prop('disabled', false).html(htmlNormal);
    }

    function money(v){
        var n = parseFloat(v || 0);
        if (isNaN(n) || !isFinite(n)) n = 0;
        return n.toFixed(2);
    }

    function escHtml(s){
        if (s === null || s === undefined) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    var table = $('#payrollsTable').DataTable({
        responsive: true,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' },
        order: [[2, 'desc']],
        pageLength: 25,
        columnDefs: [{ orderable:false, targets:[-1] }],
    });

    function initSelect2(){
        if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) return;

        $('#filter_branch_id').select2({ width:'100%' });
        $('#filter_employee_id').select2({ width:'100%' });
        $('#filter_status').select2({ width:'100%' });

        $('#gen_branch_id').select2({ width:'100%', dropdownParent: $('#generateModal') });
        $('#cancel_branch_id').select2({ width:'100%', dropdownParent: $('#cancelDraftsModal') });
    }
    initSelect2();

    // Reload employees by branch
    $('#filter_branch_id').on('change', function(){
        var branchId = $(this).val();
        $('#filter_employee_id').html('<option value="">{{ trans('hr.all_employees') }}</option>');

        if (!branchId) {
            initSelect2();
            return;
        }

        $.get('{{ route('payrolls.employees.byBranch') }}', { branch_id: branchId }, function(res){
            if (res.success) {
                res.data.forEach(function(e){
                    $('#filter_employee_id').append('<option value="'+e.id+'">'+escHtml(e.name)+' ('+escHtml(e.code)+')</option>');
                });
                initSelect2();
            }
        });
    });

    // Update print/export links
    function getFiltersObject() {
        return {
            branch_id: $('#filter_branch_id').val() || '',
            employee_id: $('#filter_employee_id').val() || '',
            month: $('#filter_month').val() || '',
            status: $('#filter_status').val() || '',
        };
    }
    function buildQueryString(filters) {
        var params = new URLSearchParams();
        Object.keys(filters || {}).forEach(function(k){
            var v = filters[k];
            if (v !== null && v !== undefined && String(v).trim() !== '') params.set(k, v);
        });
        return params.toString();
    }
    function updateActionLinks(filters) {
        var baseUrl = "{{ route('payrolls.index') }}";
        var qs = buildQueryString(filters);

        var printUrl  = baseUrl + (qs ? ('?' + qs + '&action=print') : '?action=print');
        var exportUrl = baseUrl + (qs ? ('?' + qs + '&action=export_excel') : '?action=export_excel');

        $('#btnPrint').attr('href', printUrl);
        $('#btnExport').attr('href', exportUrl);
    }
    updateActionLinks(getFiltersObject());

    // Open Generate (branch selectable)
    $('#btnOpenGenerate').on('click', function(){
        $('#gen_branch_id').val('').trigger('change');
        var fb = $('#filter_branch_id').val() || '';
        if (fb) $('#gen_branch_id').val(fb).trigger('change');

        $('#gen_month').val($('#filter_month').val() || '{{ $monthFilter }}');
        $('#generateModal').modal('show');
    });

    // Submit Generate
    $('#generateForm').on('submit', function(e){
        e.preventDefault();

        var btn = $('#genSubmitBtn');
        setLoading(btn, true, '');

        $.ajax({
            url: '{{ route('payrolls.generate') }}',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res){
                setLoading(btn, false, '<i class="ri-save-line me-1"></i> {{ trans('hr.generate') ?? 'توليد' }}');
                if(!res.success){
                    toastError(res.message || '{{ trans('hr.error_occurred') }}');
                    return;
                }
                toastSuccess(res.message || '{{ trans('hr.done') ?? 'تم' }}');
                $('#generateModal').modal('hide');

                var b = $('#gen_branch_id').val() || '';
                var m = $('#gen_month').val() || '';
                var url = "{{ route('payrolls.index') }}";
                var qs = new URLSearchParams();
                if (b) qs.set('branch_id', b);
                if (m) qs.set('month', m);
                window.location.href = url + '?' + qs.toString();
            },
            error: function(xhr){
                setLoading(btn, false, '<i class="ri-save-line me-1"></i> {{ trans('hr.generate') ?? 'توليد' }}');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') }}';
                toastError(msg);
            }
        });
    });

    // Open Cancel Drafts
    $('#btnOpenCancelDrafts').on('click', function(){
        var fb = $('#filter_branch_id').val() || '';
        var fm = $('#filter_month').val() || '{{ $monthFilter }}';

        $('#cancel_branch_id').val(fb).trigger('change');
        $('#cancel_month').val(fm);
        $('#delete_auto_overtime').prop('checked', false);

        $('#cancelDraftsModal').modal('show');
    });

    // Submit Cancel Drafts
    $('#cancelDraftsForm').on('submit', function(e){
        e.preventDefault();

        var btn = $('#cancelSubmitBtn');
        setLoading(btn, true, '');

        $.ajax({
            url: '{{ route('payrolls.cancelDrafts') }}',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res){
                setLoading(btn, false, '<i class="ri-delete-bin-5-line me-1"></i> {{ trans('hr.confirm') ?? 'تأكيد' }}');
                if(!res.success){
                    toastError(res.message || '{{ trans('hr.error_occurred') }}');
                    return;
                }

                toastSuccess(res.message || '{{ trans('hr.done') ?? 'تم' }}');
                $('#cancelDraftsModal').modal('hide');
                setTimeout(function(){ window.location.reload(); }, 500);
            },
            error: function(xhr){
                setLoading(btn, false, '<i class="ri-delete-bin-5-line me-1"></i> {{ trans('hr.confirm') ?? 'تأكيد' }}');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') }}';
                toastError(msg);
            }
        });
    });

    // Approve
    $('#btnApproveMonth').on('click', function(){
        var branchId = $('#filter_branch_id').val();
        var month = $('#filter_month').val();

        if (!branchId || !month) {
            toastError('{{ trans('hr.select_branch') }} / {{ trans('hr.month') }}');
            return;
        }

        function doApprove(){
            $.ajax({
                url: '{{ route('payrolls.approve') }}',
                method: 'POST',
                data: { branch_id: branchId, month: month },
                dataType: 'json',
                success: function(res){
                    if(!res.success){
                        toastError(res.message || '{{ trans('hr.error_occurred') }}');
                        return;
                    }
                    toastSuccess(res.message);
                    setTimeout(function(){ window.location.reload(); }, 600);
                },
                error: function(xhr){
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') }}';
                    toastError(msg);
                }
            });
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '{{ trans('hr.approve_confirm_title') ?? 'تأكيد الاعتماد' }}',
                html: '{{ trans('hr.payrolls_approve_confirm_msg') ?? 'هل تريد اعتماد الرواتب وتطبيق البنود؟' }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ trans('hr.yes_approve') ?? 'نعم' }}',
                cancelButtonText: '{{ trans('hr.cancel') }}',
            }).then(function(r){ if(r.isConfirmed) doApprove(); });
        } else {
            if(confirm('{{ trans('hr.approve_confirm_title') ?? 'تأكيد الاعتماد' }}')) doApprove();
        }
    });

    // Open Pay
    $('#btnOpenPay').on('click', function () {
        var branchId = $('#filter_branch_id').val();
        var month = $('#filter_month').val();

        if (!branchId || !month) {
            toastError('{{ trans('hr.select_branch') }} / {{ trans('hr.month') }}');
            return;
        }

        $('#pay_branch_id').val(branchId);
        $('#pay_month').val(month);
        $('#payment_reference').val('');

        $('#payModal').modal('show');
    });

    // Pay submit
    $('#payForm').on('submit', function (e) {
        e.preventDefault();

        var btn = $('#paySubmitBtn');
        setLoading(btn, true, '');

        $.ajax({
            url: '{{ route('payrolls.pay') }}',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (res) {
                setLoading(btn, false, '<i class="ri-check-double-line me-1"></i> {{ trans('hr.pay') ?? 'صرف' }}');

                if (!res.success) {
                    toastError(res.message || '{{ trans('hr.error_occurred') }}');
                    return;
                }

                $('#payModal').modal('hide');
                toastSuccess(res.message);
                setTimeout(function(){ window.location.reload(); }, 600);
            },
            error: function (xhr) {
                setLoading(btn, false, '<i class="ri-check-double-line me-1"></i> {{ trans('hr.pay') ?? 'صرف' }}');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') }}';
                toastError(msg);
            }
        });
    });

    // Breakdown modal
    function resetBreakdown(){
        $('#bd_sum_overtime').text('0.00');
        $('#bd_sum_allowances').text('0.00');
        $('#bd_sum_deductions').text('0.00');
        $('#bd_sum_advances').text('0.00');

        $('#bd_overtime_rows').html('');
        $('#bd_allowance_rows').html('');
        $('#bd_deduction_rows').html('');
        $('#bd_advance_rows').html('');

        // attendance tab (if exists)
        if ($('#bd_att_present_days').length) $('#bd_att_present_days').text('0');
        if ($('#bd_att_work_days').length) $('#bd_att_work_days').text('0');
        if ($('#bd_att_late_days').length) $('#bd_att_late_days').text('0');
        if ($('#bd_att_halfday_days').length) $('#bd_att_halfday_days').text('0');
        if ($('#bd_att_absent_days').length) $('#bd_att_absent_days').text('0');
        if ($('#bd_att_total_hours').length) $('#bd_att_total_hours').text('0.00');
        if ($('#bd_attendance_rows').length) $('#bd_attendance_rows').html('');

        // ✅ NEW: attendance money KPIs (if exists)
        if ($('#bd_att_day_rate').length) $('#bd_att_day_rate').text('0.00');
        if ($('#bd_att_present_amount').length) $('#bd_att_present_amount').text('0.00');
        if ($('#bd_att_halfday_amount').length) $('#bd_att_halfday_amount').text('0.00');
        if ($('#bd_att_attendance_amount').length) $('#bd_att_attendance_amount').text('0.00');

        // رجّع أول تبويب (لو موجود)
        var firstTabBtn = document.querySelector('#breakdownModal button[data-bs-target="#tabOvertime"]');
        if (firstTabBtn) firstTabBtn.click();
    }

    function renderAttendance(d){
        if (!d) return;

        var k = d.attendance_kpi || null;
        var rows = d.attendance_rows || [];

        if (k) {
            if ($('#bd_att_present_days').length) $('#bd_att_present_days').text(parseInt(k.present_days || 0));
            if ($('#bd_att_work_days').length) $('#bd_att_work_days').text(parseInt(k.work_days || 0));
            if ($('#bd_att_late_days').length) $('#bd_att_late_days').text(parseInt(k.late_days || 0));
            if ($('#bd_att_halfday_days').length) $('#bd_att_halfday_days').text(parseInt(k.halfday_days || 0));
            if ($('#bd_att_absent_days').length) $('#bd_att_absent_days').text(parseInt(k.absent_days || 0));
            if ($('#bd_att_total_hours').length) $('#bd_att_total_hours').text(money(k.total_hours || 0));

            // ✅ NEW: money fields
            if ($('#bd_att_day_rate').length) $('#bd_att_day_rate').text(money(k.day_rate || 0));
            if ($('#bd_att_present_amount').length) $('#bd_att_present_amount').text(money(k.present_amount || 0));
            if ($('#bd_att_halfday_amount').length) $('#bd_att_halfday_amount').text(money(k.halfday_amount || 0));
            if ($('#bd_att_attendance_amount').length) $('#bd_att_attendance_amount').text(money(k.attendance_amount || 0));
        }

        if (!$('#bd_attendance_rows').length) return;

        if (!rows.length) {
            $('#bd_attendance_rows').html(
                '<tr><td colspan="7" class="text-center text-muted">{{ trans('hr.no_results') ?? 'لا توجد بيانات' }}</td></tr>'
            );
            return;
        }

        var html = '';
        rows.forEach(function(r, idx){
            html += '<tr>' +
                '<td class="text-center">'+(idx+1)+'</td>' +
                '<td class="text-center"><code>'+escHtml(r.date || '-')+'</code></td>' +
                '<td class="text-center">'+escHtml(r.check_in || '-')+'</td>' +
                '<td class="text-center">'+escHtml(r.check_out || '-')+'</td>' +
                '<td class="text-center">'+money(r.total_hours || 0)+'</td>' +
                '<td class="text-center">'+escHtml(r.status || '-')+'</td>' +
                '<td class="dt-cell-wrap">'+escHtml(r.notes || '')+'</td>' +
            '</tr>';
        });
        $('#bd_attendance_rows').html(html);
    }

    $(document).on('click', '.btn-details', function(){
        resetBreakdown();

        var empId = $(this).data('employee-id');
        var branchId = $(this).data('branch-id');
        var month = $(this).data('month');

        var empName = $(this).data('employee-name') || '';
        var empCode = $(this).data('employee-code') || '';

        $('#breakdownTitle').html('<i class="ri-file-search-line me-1"></i> {{ trans('hr.payroll_breakdown') ?? 'تفاصيل كشف الراتب' }} - ' + escHtml(empName) + ' ' + (empCode ? '('+escHtml(empCode)+')' : '') + ' - ' + escHtml(month));

        $('#breakdownLoading').removeClass('d-none');
        $('#breakdownModal').modal('show');

        $.ajax({
            url: '{{ route('payrolls.breakdown') }}',
            method: 'GET',
            data: { employee_id: empId, branch_id: branchId, month: month },
            dataType: 'json',
            success: function(res){
                $('#breakdownLoading').addClass('d-none');

                if(!res.success){
                    toastError(res.message || '{{ trans('hr.error_occurred') }}');
                    return;
                }

                var d = res.data || {};

                $('#bd_sum_overtime').text(money(d.sum_overtime));
                $('#bd_sum_allowances').text(money(d.sum_allowances));
                $('#bd_sum_deductions').text(money(d.sum_deductions));
                $('#bd_sum_advances').text(money(d.sum_advances));

                // overtime rows
                var ot = d.overtimes || [];
                if (!ot.length) {
                    $('#bd_overtime_rows').html('<tr><td colspan="7" class="text-center text-muted">{{ trans('hr.no_results') ?? 'لا توجد بيانات' }}</td></tr>');
                } else {
                    var html = '';
                    ot.forEach(function(r, idx){
                        html += '<tr>' +
                            '<td class="text-center">'+(idx+1)+'</td>' +
                            '<td class="text-center"><code>'+escHtml(r.date || '-')+'</code></td>' +
                            '<td class="text-center">'+escHtml(r.source || '-')+'</td>' +
                            '<td class="text-center">'+money(r.hours)+'</td>' +
                            '<td class="text-center">'+money(r.hour_rate)+'</td>' +
                            '<td class="text-center">'+money(r.total_amount)+'</td>' +
                            '<td class="dt-cell-wrap">'+escHtml(r.notes || '')+'</td>' +
                        '</tr>';
                    });
                    $('#bd_overtime_rows').html(html);
                }

                // allowances
                var al = d.allowances || [];
                if (!al.length) {
                    $('#bd_allowance_rows').html('<tr><td colspan="6" class="text-center text-muted">{{ trans('hr.no_results') ?? 'لا توجد بيانات' }}</td></tr>');
                } else {
                    var html2 = '';
                    al.forEach(function(r, idx){
                        html2 += '<tr>' +
                            '<td class="text-center">'+(idx+1)+'</td>' +
                            '<td class="text-center"><code>'+escHtml(r.date || '-')+'</code></td>' +
                            '<td class="text-center">'+escHtml(r.type || '-')+'</td>' +
                            '<td class="dt-cell-wrap">'+escHtml(r.reason || '-')+'</td>' +
                            '<td class="text-center">'+money(r.amount)+'</td>' +
                            '<td class="dt-cell-wrap">'+escHtml(r.notes || '')+'</td>' +
                        '</tr>';
                    });
                    $('#bd_allowance_rows').html(html2);
                }

                // deductions
                var de = d.deductions || [];
                if (!de.length) {
                    $('#bd_deduction_rows').html('<tr><td colspan="6" class="text-center text-muted">{{ trans('hr.no_results') ?? 'لا توجد بيانات' }}</td></tr>');
                } else {
                    var html3 = '';
                    de.forEach(function(r, idx){
                        html3 += '<tr>' +
                            '<td class="text-center">'+(idx+1)+'</td>' +
                            '<td class="text-center"><code>'+escHtml(r.date || '-')+'</code></td>' +
                            '<td class="text-center">'+escHtml(r.type || '-')+'</td>' +
                            '<td class="dt-cell-wrap">'+escHtml(r.reason || '-')+'</td>' +
                            '<td class="text-center">'+money(r.amount)+'</td>' +
                            '<td class="dt-cell-wrap">'+escHtml(r.notes || '')+'</td>' +
                        '</tr>';
                    });
                    $('#bd_deduction_rows').html(html3);
                }

                // advances
                var ad = d.advances || [];
                if (!ad.length) {
                    $('#bd_advance_rows').html('<tr><td colspan="4" class="text-center text-muted">{{ trans('hr.no_results') ?? 'لا توجد بيانات' }}</td></tr>');
                } else {
                    var html4 = '';
                    ad.forEach(function(r, idx){
                        html4 += '<tr>' +
                            '<td class="text-center">'+(idx+1)+'</td>' +
                            '<td class="text-center">'+escHtml(r.advance_id || '-')+'</td>' +
                            '<td class="text-center">'+money(r.amount)+'</td>' +
                            '<td class="text-center">'+escHtml(r.status_text || '-')+'</td>' +
                        '</tr>';
                    });
                    $('#bd_advance_rows').html(html4);
                }

                // attendance KPI + rows (with money KPIs)
                renderAttendance(d);
            },
            error: function(xhr){
                $('#breakdownLoading').addClass('d-none');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') }}';
                toastError(msg);
            }
        });
    });
});
</script>

@endsection
