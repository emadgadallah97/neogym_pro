@extends('layouts.master_table')

@section('title')
    {{ trans('hr.overtime') ?? 'الوقت الإضافي' }} | {{ trans('main_trans.title') }}
@endsection

@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="ri-time-line me-1"></i>
                {{ trans('hr.overtime') ?? 'الوقت الإضافي' }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('hr.index') }}">{{ trans('hr.title') ?? 'الموارد البشرية' }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('hr.overtime') ?? 'الوقت الإضافي' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div id="page-alerts" class="mb-3"></div>

<form method="GET" action="{{ route('overtime.index') }}" class="card border-0 shadow-sm mb-3">
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

            <div class="col-md-3">
                <label class="form-label font">{{ trans('hr.status') }}</label>
                <select name="status" id="filter_status" class="form-select font">
                    <option value="">{{ trans('hr.all_status') ?? 'كل الحالات' }}</option>
                    <option value="pending"  {{ $statusFilter==='pending'  ? 'selected' : '' }}>{{ trans('hr.status_pending') }}</option>
                    <option value="approved" {{ $statusFilter==='approved' ? 'selected' : '' }}>{{ trans('hr.status_approved') }}</option>
                    <option value="applied"  {{ $statusFilter==='applied'  ? 'selected' : '' }}>{{ trans('hr.status_applied') }}</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label font">{{ trans('hr.applied_month') ?? 'شهر التطبيق' }}</label>
                <input type="month" name="applied_month" id="filter_month" class="form-control font" value="{{ $monthFilter }}">
            </div>

            <div class="col-md-12 d-flex justify-content-between mt-2">
                <button type="submit" class="btn btn-primary font">
                    <i class="ri-filter-3-line me-1"></i> {{ trans('hr.filter') }}
                </button>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-soft-info font" id="btnOpenGenerate">
                        <i class="ri-magic-line me-1"></i> {{ trans('hr.generate_from_attendance') ?? 'توليد من الحضور' }}
                    </button>

                    <button type="button" class="btn btn-success font" id="btnOpenAdd">
                        <i class="ri-add-line me-1"></i> {{ trans('hr.add_overtime') ?? 'إضافة' }}
                    </button>
                </div>
            </div>

        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0 font">{{ trans('hr.overtime_list') ?? 'قائمة الوقت الإضافي' }}</h5>
    </div>

    <div class="card-body">
        <table id="overtimeTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ trans('hr.employee') }}</th>
                    <th>{{ trans('hr.branch') }}</th>
                    <th>{{ trans('hr.date') }}</th>
                    <th>{{ trans('hr.source') ?? 'المصدر' }}</th>
                    <th>{{ trans('hr.hours') ?? 'الساعات' }}</th>
                    <th>{{ trans('hr.hour_rate') ?? 'سعر الساعة' }}</th>
                    <th>{{ trans('hr.total_amount') ?? 'الإجمالي' }}</th>
                    <th>{{ trans('hr.applied_month') ?? 'شهر التطبيق' }}</th>
                    <th>{{ trans('hr.status') }}</th>
                    <th>{{ trans('hr.payroll_id') ?? 'Payroll' }}</th>
                    <th>{{ trans('hr.actions') }}</th>
                </tr>
            </thead>

            <tbody>
                @php $i=0; @endphp
                @foreach($rows as $r)
                    @php $i++; @endphp
                    <tr id="row-{{ $r->id }}" data-id="{{ $r->id }}">
                        <td>{{ $i }}</td>
                        <td class="font">
                            {{ $r->employee?->full_name ?? '-' }}
                            <small class="text-muted">({{ $r->employee?->code ?? '' }})</small>
                        </td>
                        <td class="font">{{ $r->branch?->name ?? '-' }}</td>
                        <td class="text-center"><code>{{ $r->date }}</code></td>

                        <td class="text-center">
                            @php $src = $r->source ?? 'manual'; @endphp
                            @if($src === 'attendance')
                                <span class="badge bg-info">{{ trans('hr.source_attendance') ?? 'من الحضور' }}</span>
                            @else
                                <span class="badge bg-secondary">{{ trans('hr.source_manual') ?? 'يدوي' }}</span>
                            @endif
                        </td>

                        <td class="text-center">{{ number_format((float)$r->hours,2) }}</td>
                        <td class="text-center">{{ number_format((float)$r->hour_rate,2) }}</td>
                        <td class="text-center">{{ number_format((float)$r->total_amount,2) }}</td>
                        <td class="text-center"><code>{{ \Carbon\Carbon::parse($r->applied_month)->format('Y-m') }}</code></td>
                        <td class="text-center">
                            @if($r->status === 'pending')
                                <span class="badge bg-warning text-dark">{{ trans('hr.status_pending') }}</span>
                            @elseif($r->status === 'approved')
                                <span class="badge bg-success">{{ trans('hr.status_approved') }}</span>
                            @else
                                <span class="badge bg-primary">{{ trans('hr.status_applied') }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $r->payroll_id ?? '—' }}</td>

                        <td class="text-center">
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                    <i class="ri-more-fill align-middle"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">

                                    <li>
                                        <button type="button" class="dropdown-item btn-edit" data-id="{{ $r->id }}">
                                            <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                            {{ trans('hr.edit') }}
                                        </button>
                                    </li>

                                    @if($r->status === 'pending')
                                        <li>
                                            <button type="button" class="dropdown-item text-success btn-approve" data-id="{{ $r->id }}">
                                                <i class="ri-check-line align-bottom me-2"></i>
                                                {{ trans('hr.approve') ?? 'اعتماد' }}
                                            </button>
                                        </li>
                                    @endif

                                    @if($r->status !== 'applied' && empty($r->payroll_id))
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger btn-delete"
                                                    data-id="{{ $r->id }}"
                                                    data-name="{{ $r->employee?->full_name ?? '' }}">
                                                <i class="ri-delete-bin-fill align-bottom me-2"></i>
                                                {{ trans('hr.delete') }}
                                            </button>
                                        </li>
                                    @endif

                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>
</div>

{{-- Overtime Modal --}}
<div id="overtimeModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font" id="modalTitle">{{ trans('hr.add_overtime') ?? 'إضافة وقت إضافي' }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div id="appliedLockHint" class="alert alert-warning d-none font">
                    {{ trans('hr.record_applied_locked') ?? 'هذا السجل تم تطبيقه على كشف راتب ولا يمكن تعديله/حذفه.' }}
                </div>

                <form id="overtimeForm" autocomplete="off">
                    @csrf
                    <input type="hidden" id="form_id">

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.branch') }}</label>
                            <select name="branch_id" id="form_branch_id" class="form-select font" required>
                                <option value="">{{ trans('hr.select_branch') }}</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="form_branch_id_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.employee') }}</label>
                            <select name="employee_id" id="form_employee_id" class="form-select font" required>
                                <option value="">{{ trans('hr.select_employee') }}</option>
                            </select>
                            <div class="invalid-feedback" id="form_employee_id_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.date') }}</label>
                            <input type="date" name="date" id="form_date" class="form-control font" required>
                            <div class="invalid-feedback" id="form_date_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.hours') ?? 'الساعات' }}</label>
                            <input type="number" step="0.01" min="0.01" max="24" name="hours" id="form_hours" class="form-control font" required>
                            <div class="invalid-feedback" id="form_hours_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.hour_rate') ?? 'سعر الساعة' }}</label>
                            <input type="number" step="0.01" min="0.01" name="hour_rate" id="form_hour_rate" class="form-control font">
                            <div class="invalid-feedback" id="form_hour_rate_error"></div>
                            <div class="text-muted small mt-1">
                                {{ trans('hr.hour_rate_auto_hint') ?? 'يتم حسابه تلقائيًا من الراتب الأساسي ويمكنك تعديله.' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.total_amount') ?? 'الإجمالي' }}</label>
                            <input type="text" id="form_total_amount_view" class="form-control font" value="0.00" disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">{{ trans('hr.applied_month') ?? 'شهر التطبيق' }}</label>
                            <input type="month" name="applied_month" id="form_applied_month" class="form-control font">
                            <div class="invalid-feedback" id="form_applied_month_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">{{ trans('hr.notes') }}</label>
                            <input type="text" name="notes" id="form_notes" class="form-control font">
                            <div class="invalid-feedback" id="form_notes_error"></div>
                        </div>

                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary font" id="submitBtn">
                            <i class="ri-save-line me-1"></i> {{ trans('hr.save') }}
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

{{-- Generate From Attendance Modal --}}
<div id="generateModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font">
                    {{ trans('hr.generate_from_attendance') ?? 'توليد الوقت الإضافي من الحضور' }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="alert alert-info font">
                    <div class="mb-1"><strong>{{ trans('hr.calc_notes') ?? 'ملاحظات الحساب:' }}</strong></div>
                    <div class="small">
                        - يتم التوليد فقط إذا كان Attendance status = present.<br>
                        - يوم العمل: الإضافي = الوقت بعد نهاية الوردية فقط (إذا كان الانصراف بعد نهاية الوردية).<br>
                        - يوم الإجازة في الوردية: كل ساعات الحضور تُعتبر إضافي.<br>
                        - يتم التقريب لأقرب 0.5 ساعة.<br>
                        - grace_minutes في الوردية خاص بالتأخير فقط (لا يدخل في حساب الإضافي).<br>
                        - في الشفت الليلي: اليوم يُحسب من تاريخ تسجيل الدخول (date) حتى لو كان الانصراف في اليوم التالي.
                    </div>
                </div>

                <form id="generateForm" autocomplete="off">
                    @csrf

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.branch') }}</label>
                            <select name="branch_id" id="gen_branch_id" class="form-select font" required>
                                <option value="">{{ trans('hr.select_branch') }}</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="gen_branch_id_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.date_from') ?? 'من تاريخ' }}</label>
                            <input type="date" name="date_from" id="gen_date_from" class="form-control font" required>
                            <div class="invalid-feedback" id="gen_date_from_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.date_to') ?? 'إلى تاريخ' }}</label>
                            <input type="date" name="date_to" id="gen_date_to" class="form-control font" required>
                            <div class="invalid-feedback" id="gen_date_to_error"></div>
                        </div>

                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-info font" id="genSubmitBtn">
                            <i class="ri-play-line me-1"></i> {{ trans('hr.generate') ?? 'توليد' }}
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</div>

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

    var table = $('#overtimeTable').DataTable({
        responsive: true,
        language:   { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' },
        columnDefs: [{ orderable: false, targets: [-1] }],
        order:      [[0, 'asc']],
        pageLength: 25,
    });

    function renumber(){
        table.column(0, { search: 'applied', order: 'applied' }).nodes().each(function(cell, i){
            cell.innerHTML = i + 1;
        });
    }
    table.on('draw.dt', function(){ renumber(); });

    function initSelect2(){
        if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) return;

        $('#filter_branch_id').select2({ width:'100%' });
        $('#filter_employee_id').select2({ width:'100%' });
        $('#filter_status').select2({ width:'100%' });

        $('#form_branch_id').select2({ width:'100%', dropdownParent: $('#overtimeModal') });
        $('#form_employee_id').select2({ width:'100%', dropdownParent: $('#overtimeModal') });

        $('#gen_branch_id').select2({ width:'100%', dropdownParent: $('#generateModal') });
    }
    initSelect2();

    function clearErrors(){
        ['branch_id','employee_id','date','hours','hour_rate','applied_month','notes'].forEach(function(f){
            $('#form_'+f).removeClass('is-invalid');
            $('#form_'+f+'_error').text('');
        });
        ['branch_id','date_from','date_to'].forEach(function(f){
            $('#gen_'+f).removeClass('is-invalid');
            $('#gen_'+f+'_error').text('');
        });
    }
    function showErrors(errors){
        $.each(errors, function(field, messages){
            if ($('#form_'+field).length){
                $('#form_'+field).addClass('is-invalid');
                $('#form_'+field+'_error').text(messages[0]);
            }
            if ($('#gen_'+field).length){
                $('#gen_'+field).addClass('is-invalid');
                $('#gen_'+field+'_error').text(messages[0]);
            }
        });
    }
    function setLoading(btn, loading, htmlNormal){
        if (loading) btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
        else btn.prop('disabled', false).html(htmlNormal);
    }

    function statusBadge(status){
        if (status === 'pending') return '<span class="badge bg-warning text-dark">{{ trans('hr.status_pending') }}</span>';
        if (status === 'approved') return '<span class="badge bg-success">{{ trans('hr.status_approved') }}</span>';
        return '<span class="badge bg-primary">{{ trans('hr.status_applied') }}</span>';
    }

    function sourceBadge(source){
        if (source === 'attendance') return '<span class="badge bg-info">{{ trans('hr.source_attendance') ?? 'من الحضور' }}</span>';
        return '<span class="badge bg-secondary">{{ trans('hr.source_manual') ?? 'يدوي' }}</span>';
    }

    function actionsHtml(d){
        var html = '' +
            '<div class="dropdown d-inline-block">' +
                '<button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown">' +
                    '<i class="ri-more-fill align-middle"></i>' +
                '</button>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                    '<li><button type="button" class="dropdown-item btn-edit" data-id="'+d.id+'">' +
                        '<i class="ri-pencil-fill align-bottom me-2 text-muted"></i>{{ trans('hr.edit') }}' +
                    '</button></li>';

        if (d.status === 'pending') {
            html += '<li><button type="button" class="dropdown-item text-success btn-approve" data-id="'+d.id+'">' +
                '<i class="ri-check-line align-bottom me-2"></i>{{ trans('hr.approve') ?? 'اعتماد' }}' +
            '</button></li>';
        }

        if (d.status !== 'applied' && !d.payroll_id) {
            html += '<li><hr class="dropdown-divider"></li>' +
                '<li><button type="button" class="dropdown-item text-danger btn-delete" data-id="'+d.id+'" data-name="'+(d.employee_name||'')+'">' +
                    '<i class="ri-delete-bin-fill align-bottom me-2"></i>{{ trans('hr.delete') }}' +
                '</button></li>';
        }

        html += '</ul></div>';
        return html;
    }

    // Filter employees by branch (filter form)
    $('#filter_branch_id').on('change', function(){
        var branchId = $(this).val();
        $('#filter_employee_id').html('<option value="">{{ trans('hr.all_employees') }}</option>');

        if (!branchId) {
            initSelect2();
            return;
        }

        $.get('{{ route('overtime.employees.byBranch') }}', { branch_id: branchId }, function(res){
            if (res.success) {
                res.data.forEach(function(e){
                    $('#filter_employee_id').append('<option value="'+e.id+'">'+e.name+' ('+e.code+')</option>');
                });
                initSelect2();
            }
        });
    });

    // Modal employees loader
    function loadModalEmployees(branchId, selectedEmployeeId){
        selectedEmployeeId = selectedEmployeeId || '';

        $('#form_employee_id').html('<option value="">{{ trans('hr.select_employee') }}</option>');

        if (!branchId) {
            $('#form_employee_id').val('').trigger('change');
            return;
        }

        $.get('{{ route('overtime.employees.byBranch') }}', { branch_id: branchId }, function(res){
            if (res.success) {
                res.data.forEach(function(e){
                    $('#form_employee_id')
                        .append('<option value="'+e.id+'" data-hour-rate="'+e.hour_rate+'">'+e.name+' ('+e.code+')</option>');
                });

                if (selectedEmployeeId) $('#form_employee_id').val(selectedEmployeeId).trigger('change');
                else $('#form_employee_id').val('').trigger('change');
            }
        });
    }

    function recalcTotal(){
        var h = parseFloat($('#form_hours').val() || '0');
        var r = parseFloat($('#form_hour_rate').val() || '0');
        var t = (h * r);
        if (isNaN(t) || !isFinite(t)) t = 0;
        $('#form_total_amount_view').val(t.toFixed(2));
    }

    $('#form_hours, #form_hour_rate').on('input', recalcTotal);

    $('#form_branch_id').on('change', function(){
        loadModalEmployees($(this).val(), '');
    });

    // auto set applied_month from date
    $('#form_date').on('change', function(){
        var d = $(this).val(); // YYYY-MM-DD
        if (!d) return;
        $('#form_applied_month').val(d.substring(0,7));
    });

    // auto set hour_rate on employee select (editable after)
    $('#form_employee_id').on('change', function(){
        var rate = $(this).find('option:selected').data('hour-rate');
        if (rate !== undefined && rate !== null && rate !== '') {
            $('#form_hour_rate').val(parseFloat(rate).toFixed(2));
        }
        recalcTotal();
    });

    // Open Add
    $('#btnOpenAdd').on('click', function(){
        clearErrors();
        $('#modalTitle').text('{{ trans('hr.add_overtime') ?? 'إضافة وقت إضافي' }}');
        $('#form_id').val('');
        $('#overtimeForm')[0].reset();
        $('#appliedLockHint').addClass('d-none');

        var defaultBranch = $('#filter_branch_id').val() || '';
        $('#form_branch_id').val(defaultBranch).trigger('change');
        loadModalEmployees(defaultBranch, '');

        $('#form_total_amount_view').val('0.00');

        $('#overtimeModal').modal('show');
    });

    // Open Generate
    $('#btnOpenGenerate').on('click', function(){
        clearErrors();

        var defaultBranch = $('#filter_branch_id').val() || '';
        if (defaultBranch) $('#gen_branch_id').val(defaultBranch).trigger('change');
        else $('#gen_branch_id').val('').trigger('change');

        // default date range: current month from filter_month if exists
        var m = $('#filter_month').val(); // Y-m
        if (m) {
            var from = m + '-01';
            $('#gen_date_from').val(from);

            var dt = new Date(m + '-01T00:00:00');
            dt.setMonth(dt.getMonth() + 1);
            dt.setDate(0);
            var last = dt.toISOString().substring(0,10);
            $('#gen_date_to').val(last);
        }

        $('#generateModal').modal('show');
    });

    // Submit Generate
    $('#generateForm').on('submit', function(e){
        e.preventDefault();
        clearErrors();

        var btn = $('#genSubmitBtn');
        setLoading(btn, true, '<i class="ri-play-line me-1"></i> {{ trans('hr.generate') ?? 'توليد' }}');

        $.ajax({
            url: '{{ route('overtime.generateFromAttendance') }}',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res){
                setLoading(btn, false, '<i class="ri-play-line me-1"></i> {{ trans('hr.generate') ?? 'توليد' }}');

                if(!res.success){
                    toastError(res.message || '{{ trans('hr.error_occurred') }}');
                    return;
                }

                toastSuccess(res.message || '{{ trans('hr.done') ?? 'تم' }}');
                $('#generateModal').modal('hide');

                // easiest + safe: reload to show generated records
                setTimeout(function(){ window.location.reload(); }, 600);
            },
            error: function(xhr){
                setLoading(btn, false, '<i class="ri-play-line me-1"></i> {{ trans('hr.generate') ?? 'توليد' }}');
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') }}');
                }
            }
        });
    });

    // Open Edit
    $(document).on('click', '.btn-edit', function(){
        clearErrors();
        var id = $(this).data('id');

        $.get('{{ url('overtime') }}/' + id, function(res){
            if(!res.success){
                toastError(res.message || '{{ trans('hr.error_occurred') }}');
                return;
            }

            var d = res.data;

            $('#modalTitle').text('{{ trans('hr.edit_overtime') ?? 'تعديل وقت إضافي' }}');
            $('#form_id').val(d.id);

            $('#form_branch_id').val(d.branch_id).trigger('change');
            loadModalEmployees(d.branch_id, d.employee_id);

            $('#form_date').val(d.date);
            $('#form_hours').val(d.hours);
            $('#form_hour_rate').val(d.hour_rate);
            $('#form_total_amount_view').val(d.total_amount);
            $('#form_applied_month').val(d.applied_month || '');
            $('#form_notes').val(d.notes || '');

            if (d.status === 'applied' || d.payroll_id) $('#appliedLockHint').removeClass('d-none');
            else $('#appliedLockHint').addClass('d-none');

            $('#overtimeModal').modal('show');
        }).fail(function(){
            toastError('{{ trans('hr.error_occurred') }}');
        });
    });

    // Submit Overtime (add/edit)
    $('#overtimeForm').on('submit', function(e){
        e.preventDefault();
        clearErrors();

        var btn = $('#submitBtn');
        setLoading(btn, true, '<i class="ri-save-line me-1"></i> {{ trans('hr.save') }}');

        var id = $('#form_id').val();
        var url = id ? ('{{ url('overtime') }}/' + id) : '{{ route('overtime.store') }}';
        var data = $(this).serialize();
        if (id) data += '&_method=PUT';

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(res){
                setLoading(btn, false, '<i class="ri-save-line me-1"></i> {{ trans('hr.save') }}');

                if(!res.success){
                    toastError(res.message || '{{ trans('hr.error_occurred') }}');
                    return;
                }

                toastSuccess(res.message);
                $('#overtimeModal').modal('hide');

                var d = res.data;

                var rowData = [
                    '',
                    d.employee_name + ' <small class="text-muted">(' + (d.employee_code ?? '') + ')</small>',
                    d.branch_name,
                    '<div class="text-center"><code>'+ (d.date ?? '') +'</code></div>',
                    '<div class="text-center">'+ sourceBadge(d.source) +'</div>',
                    '<div class="text-center">'+ d.hours +'</div>',
                    '<div class="text-center">'+ d.hour_rate +'</div>',
                    '<div class="text-center">'+ d.total_amount +'</div>',
                    '<div class="text-center"><code>'+ (d.applied_month ?? '') +'</code></div>',
                    '<div class="text-center">'+ statusBadge(d.status) +'</div>',
                    '<div class="text-center">'+ (d.payroll_id ?? '—') +'</div>',
                    '<div class="text-center">'+ actionsHtml(d) +'</div>',
                ];

                if (id) {
                    var $tr = $('#row-'+id);
                    var row = table.row($tr.get(0));
                    if (row.any()) row.data(rowData).draw(false);
                } else {
                    var rowApi = table.row.add(rowData);
                    table.draw(false);
                    $(rowApi.node()).attr('id', 'row-'+d.id).attr('data-id', d.id);
                }

                renumber();
            },
            error: function(xhr){
                setLoading(btn, false, '<i class="ri-save-line me-1"></i> {{ trans('hr.save') }}');
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') }}');
                }
            }
        });
    });

    // Approve
    $(document).on('click', '.btn-approve', function(){
        var id = $(this).data('id');

        function doApprove(){
            $.ajax({
                url: '{{ url('overtime') }}/' + id + '/approve',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                dataType: 'json',
                success: function(res){
                    if(!res.success){
                        toastError(res.message || '{{ trans('hr.error_occurred') }}');
                        return;
                    }

                    toastSuccess(res.message);

                    var d = res.data;
                    var $tr = $('#row-'+d.id);
                    var row = table.row($tr.get(0));
                    if(row.any()){
                        var rowData = row.data();
                        rowData[9] = '<div class="text-center">'+ statusBadge(d.status) +'</div>';
                        rowData[11]= '<div class="text-center">'+ actionsHtml(d) +'</div>';
                        row.data(rowData).draw(false);
                    }
                },
                error: function(xhr){
                    toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') }}');
                }
            });
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '{{ trans('hr.approve_confirm_title') ?? 'تأكيد الاعتماد' }}',
                text: '{{ trans('hr.overtime_approve_confirm_msg') ?? 'هل تريد الاعتماد؟' }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0ab39c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ trans('hr.yes_approve') ?? 'نعم اعتماد' }}',
                cancelButtonText: '{{ trans('hr.cancel') }}',
            }).then(function(r){ if(r.isConfirmed) doApprove(); });
        } else {
            if(confirm('{{ trans('hr.approve_confirm_title') ?? 'تأكيد الاعتماد' }}')) doApprove();
        }
    });

    // Delete
    $(document).on('click', '.btn-delete', function(){
        var id = $(this).data('id');
        var name = $(this).data('name');

        function doDelete(){
            $.ajax({
                url: '{{ url('overtime') }}/' + id,
                method: 'POST',
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                dataType: 'json',
                success: function(res){
                    if(!res.success){
                        toastError(res.message || '{{ trans('hr.error_occurred') }}');
                        return;
                    }

                    toastSuccess(res.message);

                    var $tr = $('#row-'+id);
                    var row = table.row($tr.get(0));
                    if(row.any()){
                        row.remove().draw(false);
                        renumber();
                    }
                },
                error: function(xhr){
                    toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') }}');
                }
            });
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '{{ trans('hr.delete_confirm_title') }}',
                html: '{{ trans('hr.delete_confirm_msg') }} <strong>' + name + '</strong>؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ trans('hr.yes_delete') }}',
                cancelButtonText: '{{ trans('hr.cancel') }}',
            }).then(function(r){ if(r.isConfirmed) doDelete(); });
        } else {
            if(confirm('{{ trans('hr.delete_confirm_title') }}')) doDelete();
        }
    });

});
</script>

@endsection
