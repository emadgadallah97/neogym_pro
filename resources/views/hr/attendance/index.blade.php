@extends('layouts.master_table')

@section('title')
    {{ trans('hr.attendance') }} | {{ trans('main_trans.title') }}
@endsection

@section('content')

<style>
    .device-card { transition: all 0.2s ease-in-out; }
    .device-card:hover { transform: translateY(-2px); }
</style>


{{-- Page Title --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="ri-time-line me-1"></i>
                {{ trans('hr.attendance') }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('hr.index') }}">{{ trans('hr.title') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('hr.attendance') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div id="page-alerts" class="mb-3"></div>

{{-- Filters --}}
<form method="GET" action="{{ route('attendance.index') }}" class="card border-0 shadow-sm mb-3">
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
                <label class="form-label font">{{ trans('hr.view_mode') }}</label>
                <select name="mode" id="filter_mode" class="form-select font">
                    <option value="daily" {{ $mode === 'daily' ? 'selected' : '' }}>{{ trans('hr.daily') }}</option>
                    <option value="monthly" {{ $mode === 'monthly' ? 'selected' : '' }}>{{ trans('hr.monthly') }}</option>
                </select>
            </div>

            <div class="col-md-2" id="wrap_month" style="{{ $mode === 'monthly' ? '' : 'display:none;' }}">
                <label class="form-label font">{{ trans('hr.month') }}</label>
                <input type="month" name="month" class="form-control font" value="{{ $month }}">
            </div>

            <div class="col-md-2" id="wrap_date" style="{{ $mode === 'daily' ? '' : 'display:none;' }}">
                <label class="form-label font">{{ trans('hr.date') }}</label>
                <input type="date" name="date" class="form-control font" value="{{ $date }}">
            </div>

            <div class="col-md-12 d-flex justify-content-between mt-2">
                <button type="submit" class="btn btn-primary font">
                    <i class="ri-filter-3-line me-1"></i> {{ trans('hr.filter') }}
                </button>

                <div class="d-flex gap-2">
                    <a href="{{ route('attendance.process.index') }}" class="btn btn-soft-primary font">
                        <i class="ri-cpu-line me-1"></i> {{ trans('hr.process_logs') }}
                    </a>

                    <button type="button" class="btn btn-success font" id="btnOpenManualAdd">
                        <i class="ri-add-line me-1"></i> {{ trans('hr.manual_entry') }}
                    </button>
                </div>
            </div>

        </div>
    </div>
</form>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0 font">{{ trans('hr.attendance_list') }}</h5>
    </div>

    <div class="card-body">
        <table id="attendanceTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ trans('hr.employee') }}</th>
                    <th>{{ trans('hr.date') }}</th>
                    <th>{{ trans('hr.shift') }}</th>
                    <th>{{ trans('hr.check_in') }}</th>
                    <th>{{ trans('hr.check_out') }}</th>
                    <th>{{ trans('hr.total_hours') }}</th>
                    <th>{{ trans('hr.status') }}</th>
                    <th>{{ trans('hr.source') }}</th>
                    <th>{{ trans('hr.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $i=0; @endphp
                @foreach($rows as $r)
                    @php $i++; @endphp
                    <tr id="{{ $r['row_dom_id'] }}"
                        data-virtual="{{ $r['is_virtual'] }}"
                        data-employee="{{ $r['employee_id'] }}"
                        data-date="{{ $r['date'] }}"
                        data-attendance="{{ $r['attendance_id'] ?? '' }}">
                        <td>{{ $i }}</td>
                        <td class="font">
                            {{ $r['employee_name'] }} <small class="text-muted">({{ $r['employee_code'] }})</small>
                        </td>
                        <td class="text-center"><code>{{ $r['date'] }}</code></td>
                        <td class="font">{{ $r['shift_name'] }}</td>
                        <td class="text-center"><code>{{ $r['check_in'] }}</code></td>
                        <td class="text-center"><code>{{ $r['check_out'] }}</code></td>
                        <td class="text-center">{{ $r['total_hours'] }}</td>
                        <td class="text-center">
                            @if($r['status'] === 'present')
                                <span class="badge bg-success">{{ trans('hr.present') }}</span>
                            @elseif($r['status'] === 'late')
                                <span class="badge bg-warning text-dark">{{ trans('hr.late') }}</span>
                            @elseif($r['status'] === 'halfday' || $r['status'] === 'half_day')
                                <span class="badge bg-info text-dark">{{ trans('hr.halfday') }}</span>
                            @else
                                <span class="badge bg-danger">{{ trans('hr.absent') }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $r['source'] }}</td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-soft-success btn-sm btn-manual"
                                        data-employee="{{ $r['employee_id'] }}"
                                        data-date="{{ $r['date'] }}">
                                    <i class="ri-edit-2-line"></i>
                                </button>

                                @if(!$r['is_virtual'] && $r['attendance_id'])
                                    <button type="button" class="btn btn-soft-danger btn-sm btn-delete"
                                            data-id="{{ $r['attendance_id'] }}"
                                            data-employee="{{ $r['employee_id'] }}"
                                            data-date="{{ $r['date'] }}"
                                            data-name="{{ $r['employee_name'] }}">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Manual Modal --}}
<div id="manualModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font">
                    <i class="ri-edit-line me-1 text-primary"></i>
                    {{ trans('hr.manual_entry') }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="manualForm" autocomplete="off">
                    @csrf
                    <input type="hidden" id="manual_attendance_id">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.branch') }}</label>
                            <input type="hidden" name="branch_id" id="manual_branch_id" value="{{ $branchId }}">
                            <input type="text" class="form-control font" value="{{ $branchId ? $branches->firstWhere('id',$branchId)?->name : '' }}" disabled>
                            <div class="invalid-feedback" id="manual_branch_id_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.employee') }}</label>
                            <select name="employee_id" id="manual_employee_id" class="form-select font">
                                <option value="">{{ trans('hr.select_employee') }}</option>
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->code }})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="manual_employee_id_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.date') }}</label>
                            <input type="date" name="date" id="manual_date" class="form-control font">
                            <div class="invalid-feedback" id="manual_date_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">{{ trans('hr.check_in') }}</label>
                            <input type="time" name="check_in" id="manual_check_in" class="form-control font">
                            <div class="invalid-feedback" id="manual_check_in_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">{{ trans('hr.check_out') }}</label>
                            <input type="time" name="check_out" id="manual_check_out" class="form-control font">
                            <div class="invalid-feedback" id="manual_check_out_error"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label font">{{ trans('hr.notes') }}</label>
                            <input type="text" name="notes" id="manual_notes" class="form-control font">
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary font" id="manualSubmitBtn">
                            <i class="ri-save-line me-1"></i> {{ trans('hr.save') }}
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

    // Toast مطابق لرسائل النظام
    function toast(type, message) {
        if (typeof Swal !== 'undefined' && Swal.mixin) {
            const Toast = Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:2500, timerProgressBar:true });
            Toast.fire({ icon:type, title:(type==='success'?'Success ! ':'Error ! ')+message });
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

    // DataTable
    var table = $('#attendanceTable').DataTable({
        responsive: true,
        language:   { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' },
        columnDefs: [{ orderable: false, targets: [-1] }],
        order:      [[2, 'asc']],
        pageLength: 25,
    });

    function renumber(){
        table.column(0, { search: 'applied', order: 'applied' }).nodes().each(function(cell, i){
            cell.innerHTML = i + 1;
        });
    }
    table.on('draw.dt', function(){ renumber(); });

    // ✅ Select2 لكل القوائم (لو موجودة)
    function initSelect2(){
        if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) return;

        $('#filter_branch_id').select2({ width:'100%' });
        $('#filter_employee_id').select2({ width:'100%' });

        $('#manual_employee_id').select2({
            width:'100%',
            dropdownParent: $('#manualModal')
        });
    }
    initSelect2();

    // Toggle mode UI
    function toggleMode(){
        var mode = $('#filter_mode').val();
        if (mode === 'daily') {
            $('#wrap_month').hide();
            $('#wrap_date').show();
        } else {
            $('#wrap_date').hide();
            $('#wrap_month').show();
        }
    }
    toggleMode();
    $('#filter_mode').on('change', toggleMode);

    // Reload employees by branch (primary) + re-init select2
    $('#filter_branch_id').on('change', function(){
        var branchId = $(this).val();
        $('#filter_employee_id').html('<option value="">{{ trans('hr.all_employees') }}</option>');

        if (!branchId) {
            initSelect2();
            return;
        }

        $.get('{{ route('attendance.employees.byBranch') }}', { branch_id: branchId }, function(res){
            if (res.success) {
                res.data.forEach(function(e){
                    $('#filter_employee_id').append('<option value="'+e.id+'">'+e.name+' ('+e.code+')</option>');
                });
                initSelect2();
            }
        });
    });

    // Manual modal helpers
    function clearErrors(){
        ['employee_id','branch_id','date','check_in','check_out'].forEach(function(f){
            $('#manual_'+f).removeClass('is-invalid');
            $('#manual_'+f+'_error').text('');
        });
    }
    function showErrors(errors){
        $.each(errors, function(field, messages){
            $('#manual_'+field).addClass('is-invalid');
            $('#manual_'+field+'_error').text(messages[0]);
        });
    }
    function setLoading(btn, loading){
        if (loading) btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
        else btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> {{ trans('hr.save') }}');
    }

    function statusBadge(status){
        if (status === 'present') return '<span class="badge bg-success">{{ trans('hr.present') }}</span>';
        if (status === 'late') return '<span class="badge bg-warning text-dark">{{ trans('hr.late') }}</span>';
        if (status === 'halfday' || status === 'half_day') return '<span class="badge bg-info text-dark">{{ trans('hr.halfday') }}</span>';
        return '<span class="badge bg-danger">{{ trans('hr.absent') }}</span>';
    }

    function actionButtons(isVirtual, attendanceId, employeeId, date, employeeName){
        var html = '<div class="d-flex justify-content-center gap-1">' +
            '<button type="button" class="btn btn-soft-success btn-sm btn-manual" data-employee="'+employeeId+'" data-date="'+date+'">' +
                '<i class="ri-edit-2-line"></i>' +
            '</button>';

        if (!isVirtual && attendanceId) {
            html += '<button type="button" class="btn btn-soft-danger btn-sm btn-delete" data-id="'+attendanceId+'" data-employee="'+employeeId+'" data-date="'+date+'" data-name="'+employeeName+'">' +
                '<i class="ri-delete-bin-line"></i>' +
            '</button>';
        }
        html += '</div>';
        return html;
    }

    // Open manual add
    $('#btnOpenManualAdd').on('click', function(){
        $('#manual_attendance_id').val('');
        $('#manual_employee_id').val('').trigger('change');
        $('#manual_date').val('');
        $('#manual_check_in').val('');
        $('#manual_check_out').val('');
        $('#manual_notes').val('');
        clearErrors();
        $('#manualModal').modal('show');
    });

    // Open manual on row
    $(document).on('click', '.btn-manual', function(){
        var empId = $(this).data('employee');
        var date  = $(this).data('date');

        $('#manual_attendance_id').val('');
        $('#manual_employee_id').val(empId).trigger('change');
        $('#manual_date').val(date);
        $('#manual_check_in').val('');
        $('#manual_check_out').val('');
        $('#manual_notes').val('');
        clearErrors();

        var rowByData = $('tr[data-employee="'+empId+'"][data-date="'+date+'"]');
        var $tr = rowByData.length ? $(rowByData[0]) : null;

        var attendanceId = $tr ? $tr.data('attendance') : null;
        var isVirtual    = $tr ? parseInt($tr.data('virtual') || 0) : 1;

        if (!isVirtual && attendanceId) {
            $.get('{{ url('attendance') }}/' + attendanceId, function(res){
                if (res.success) {
                    $('#manual_attendance_id').val(res.data.id);
                    $('#manual_employee_id').val(res.data.employee_id).trigger('change');
                    $('#manual_date').val(res.data.date);
                    $('#manual_check_in').val(res.data.check_in || '');
                    $('#manual_check_out').val(res.data.check_out || '');
                    $('#manual_notes').val(res.data.notes || '');
                    $('#manualModal').modal('show');
                } else {
                    toastError('{{ trans('hr.error_occurred') }}');
                }
            }).fail(function(){
                toastError('{{ trans('hr.error_occurred') }}');
            });
        } else {
            $('#manualModal').modal('show');
        }
    });

    // Submit manual
    $('#manualForm').on('submit', function(e){
        e.preventDefault();
        clearErrors();

        var btn = $('#manualSubmitBtn');
        setLoading(btn, true);

        var id = $('#manual_attendance_id').val();
        var url = id ? ('{{ url('attendance') }}/' + id) : '{{ route('attendance.store') }}';

        var data = $(this).serialize();
        if (id) data += '&_method=PUT';

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(res){
                setLoading(btn, false);

                if (!res.success) {
                    toastError(res.message || '{{ trans('hr.error_occurred') }}');
                    return;
                }

                $('#manualModal').modal('hide');
                toastSuccess(res.message);

                var d = res.data;

                // find row (virtual first)
                var $tr = $('#row-emp-' + d.employee_id + '-' + d.date);
                if ($tr.length === 0) $tr = $('#row-att-' + d.attendance_id);

                var row = table.row($tr.get(0));
                if (row.any()) {
                    var rowData = row.data();

                    rowData[1] = d.employee_name + ' <small class="text-muted">(' + d.employee_code + ')</small>';
                    rowData[2] = '<code>' + d.date + '</code>';
                    rowData[3] = d.shift_name;
                    rowData[4] = '<code>' + (d.check_in ?? '—') + '</code>';
                    rowData[5] = '<code>' + (d.check_out ?? '—') + '</code>';
                    rowData[6] = d.total_hours;
                    rowData[7] = statusBadge(d.status);
                    rowData[8] = d.source;
                    rowData[9] = actionButtons(0, d.attendance_id, d.employee_id, d.date, d.employee_name);

                    row.data(rowData);
                    table.draw(false);

                    var node = row.node();
                    $(node).attr('id', 'row-att-' + d.attendance_id)
                          .attr('data-virtual', 0)
                          .attr('data-attendance', d.attendance_id);
                }

                renumber();
            },
            error: function(xhr){
                setLoading(btn, false);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    toastError('{{ trans('hr.error_occurred') }}');
                }
            }
        });
    });

    // Delete
    $(document).on('click', '.btn-delete', function(){
        var id   = $(this).data('id');
        var name = $(this).data('name');

        function doDelete(){
            $.ajax({
                url: '{{ url('attendance') }}/' + id,
                method: 'POST',
                data: { _method: 'DELETE' },
                dataType: 'json',
                success: function(res){
                    if (!res.success) {
                        toastError(res.message || '{{ trans('hr.error_occurred') }}');
                        return;
                    }

                    toastSuccess(res.message);

                    var $tr = $('#row-att-' + id);
                    var row = table.row($tr.get(0));
                    if (row.any()) {
                        row.remove();
                        table.draw(false);
                    }

                    if (res.data && res.data.absent_row) {
                        var a = res.data.absent_row;

                        var rowData = [
                            '',
                            a.employee_name + ' <small class="text-muted">(' + a.employee_code + ')</small>',
                            '<code>' + a.date + '</code>',
                            a.shift_name,
                            '<code>—</code>',
                            '<code>—</code>',
                            '0.00',
                            statusBadge('absent'),
                            'system',
                            actionButtons(1, null, a.employee_id, a.date, a.employee_name),
                        ];

                        var rowApi = table.row.add(rowData);
                        table.draw(false);

                        var node = rowApi.node();
                        $(node).attr('id', 'row-emp-' + a.employee_id + '-' + a.date)
                              .attr('data-virtual', 1)
                              .attr('data-employee', a.employee_id)
                              .attr('data-date', a.date)
                              .attr('data-attendance', '');
                    }

                    renumber();
                },
                error: function(xhr){
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') }}';
                    toastError(msg);
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
            }).then(function(r){ if (r.isConfirmed) doDelete(); });
        } else {
            if (confirm('{{ trans('hr.delete_confirm_title') }}')) doDelete();
        }
    });

});
</script>

@endsection
