@extends('layouts.master_table')

@section('title')
    {{ trans('hr.employee_shifts') ?? 'ورديات الموظفين' }} | {{ trans('main_trans.title') }}
@endsection

@section('content')


{{-- Page Title --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="ri-user-settings-line me-1"></i>
                {{ trans('hr.employee_shifts') ?? 'ورديات الموظفين' }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('hr.index') }}">{{ trans('hr.title') ?? 'الموارد البشرية' }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('hr.employee_shifts') ?? 'ورديات الموظفين' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div id="page-alerts" class="mb-3"></div>

{{-- Filters --}}
<form method="GET" action="{{ route('employee_shifts.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">

            <div class="col-md-4">
                <label class="form-label font">{{ trans('hr.branch') ?? 'الفرع' }}</label>
                <select name="branch_id" id="filter_branch_id" class="form-select font">
                    <option value="">{{ trans('hr.select_branch') ?? 'اختر الفرع' }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ (int)$branchId === (int)$b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label font">{{ trans('hr.employee') ?? 'الموظف' }}</label>
                <select name="employee_id" id="filter_employee_id" class="form-select font">
                    <option value="">{{ trans('hr.all_employees') ?? 'كل الموظفين' }}</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" {{ (int)$employeeId === (int)$e->id ? 'selected' : '' }}>
                            {{ $e->full_name }} ({{ $e->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 d-flex justify-content-between gap-2">
                <button type="submit" class="btn btn-primary font w-100">
                    <i class="ri-filter-3-line me-1"></i> {{ trans('hr.filter') ?? 'تصفية' }}
                </button>

                <button type="button" class="btn btn-success font w-100" id="btnOpenAdd"
                        {{ $branchId ? '' : 'disabled' }}>
                    <i class="ri-add-line me-1"></i> {{ trans('hr.add_employee_shift') ?? 'إضافة' }}
                </button>
            </div>

            @if(!$branchId)
                <div class="col-12">
                    <div class="alert alert-warning mb-0 font">
                        {{ trans('hr.select_branch_first') ?? 'يرجى اختيار الفرع أولاً' }}
                    </div>
                </div>
            @endif

        </div>
    </div>
</form>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0 font">{{ trans('hr.employee_shifts_list') ?? 'قائمة ورديات الموظفين' }}</h5>
    </div>

    <div class="card-body">
        <table id="employeeShiftsTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ trans('hr.employee') ?? 'الموظف' }}</th>
                    <th>{{ trans('hr.branch') ?? 'الفرع' }}</th>
                    <th>{{ trans('hr.shift') ?? 'الوردية' }}</th>
                    <th>{{ trans('hr.start_date') ?? 'من' }}</th>
                    <th>{{ trans('hr.end_date') ?? 'إلى' }}</th>
                    <th>{{ trans('hr.status') ?? 'الحالة' }}</th>
                    <th>{{ trans('hr.create_date') ?? 'تاريخ الإنشاء' }}</th>
                    <th>{{ trans('hr.actions') ?? 'الإجراءات' }}</th>
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
                        <td class="font fw-medium">{{ $r->shift?->name ?? '-' }}</td>
                        <td class="text-center"><code>{{ $r->start_date }}</code></td>
                        <td class="text-center"><code>{{ $r->end_date }}</code></td>
                        <td class="text-center">
                            @if($r->status)
                                <span class="badge bg-success">{{ trans('hr.active') ?? 'نشط' }}</span>
                            @else
                                <span class="badge bg-danger">{{ trans('hr.inactive') ?? 'غير نشط' }}</span>
                            @endif
                        </td>
                        <td class="text-center"><code>{{ $r->created_at }}</code></td>
                        <td class="text-center">
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                    <i class="ri-more-fill align-middle"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item btn-edit" data-id="{{ $r->id }}">
                                            <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                            {{ trans('hr.edit') ?? 'تعديل' }}
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item text-danger btn-delete"
                                                data-id="{{ $r->id }}"
                                                data-name="{{ $r->employee?->full_name ?? '' }}">
                                            <i class="ri-delete-bin-fill align-bottom me-2"></i>
                                            {{ trans('hr.delete') ?? 'حذف' }}
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>
</div>

{{-- Add/Edit Modal --}}
<div id="employeeShiftModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font" id="modalTitle">
                    {{ trans('hr.add_employee_shift') ?? 'إضافة وردية لموظف' }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="employeeShiftForm" autocomplete="off">
                    @csrf
                    <input type="hidden" id="form_id">

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.branch') ?? 'الفرع' }}</label>
                            <input type="hidden" name="branch_id" id="form_branch_id" value="{{ $branchId }}">
                            <input type="text" class="form-control font"
                                   value="{{ $branchId ? $branches->firstWhere('id',$branchId)?->name : '' }}"
                                   disabled>
                            <div class="invalid-feedback" id="form_branch_id_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.employee') ?? 'الموظف' }}</label>
                            <select name="employee_id" id="form_employee_id" class="form-select font" required>
                                <option value="">{{ trans('hr.select_employee') ?? 'اختر الموظف' }}</option>
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->code }})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="form_employee_id_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.shift') ?? 'الوردية' }}</label>
                            <select name="shift_id" id="form_shift_id" class="form-select font" required>
                                <option value="">{{ trans('hr.select_shift') ?? 'اختر الوردية' }}</option>
                                @foreach($shifts as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="form_shift_id_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.start_date') ?? 'من' }}</label>
                            <input type="date" name="start_date" id="form_start_date" class="form-control font" required>
                            <div class="invalid-feedback" id="form_start_date_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.end_date') ?? 'إلى' }}</label>
                            <input type="date" name="end_date" id="form_end_date" class="form-control font" required>
                            <div class="invalid-feedback" id="form_end_date_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.status') ?? 'الحالة' }}</label>
                            <select name="status" id="form_status" class="form-select font" required>
                                <option value="1">{{ trans('hr.active') ?? 'نشط' }}</option>
                                <option value="0">{{ trans('hr.inactive') ?? 'غير نشط' }}</option>
                            </select>
                            <div class="invalid-feedback" id="form_status_error"></div>
                        </div>

                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') ?? 'إلغاء' }}
                        </button>
                        <button type="submit" class="btn btn-primary font" id="submitBtn">
                            <i class="ri-save-line me-1"></i> {{ trans('hr.save') ?? 'حفظ' }}
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

    var table = $('#employeeShiftsTable').DataTable({
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

        $('#form_employee_id').select2({ width:'100%', dropdownParent: $('#employeeShiftModal') });
        $('#form_shift_id').select2({ width:'100%', dropdownParent: $('#employeeShiftModal') });
        $('#form_status').select2({ width:'100%', dropdownParent: $('#employeeShiftModal') });
    }
    initSelect2();

    function clearErrors(){
        ['branch_id','employee_id','shift_id','start_date','end_date','status'].forEach(function(f){
            $('#form_'+f).removeClass('is-invalid');
            $('#form_'+f+'_error').text('');
        });
    }
    function showErrors(errors){
        $.each(errors, function(field, messages){
            $('#form_'+field).addClass('is-invalid');
            $('#form_'+field+'_error').text(messages[0]);
        });
    }
    function setLoading(btn, loading, htmlNormal){
        if (loading) btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
        else btn.prop('disabled', false).html(htmlNormal);
    }

    function statusBadgeHtml(status){
        if (parseInt(status) === 1) return '<span class="badge bg-success">{{ trans('hr.active') ?? 'نشط' }}</span>';
        return '<span class="badge bg-danger">{{ trans('hr.inactive') ?? 'غير نشط' }}</span>';
    }

    function actionsHtml(id, employeeName){
        return '' +
            '<div class="dropdown d-inline-block">' +
                '<button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown">' +
                    '<i class="ri-more-fill align-middle"></i>' +
                '</button>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                    '<li><button type="button" class="dropdown-item btn-edit" data-id="'+id+'">' +
                        '<i class="ri-pencil-fill align-bottom me-2 text-muted"></i>{{ trans('hr.edit') ?? 'تعديل' }}' +
                    '</button></li>' +
                    '<li><button type="button" class="dropdown-item text-danger btn-delete" data-id="'+id+'" data-name="'+employeeName+'">' +
                        '<i class="ri-delete-bin-fill align-bottom me-2"></i>{{ trans('hr.delete') ?? 'حذف' }}' +
                    '</button></li>' +
                '</ul>' +
            '</div>';
    }

    // Open Add
    $('#btnOpenAdd').on('click', function(){
        clearErrors();
        $('#modalTitle').text('{{ trans('hr.add_employee_shift') ?? 'إضافة وردية لموظف' }}');
        $('#form_id').val('');
        $('#employeeShiftForm')[0].reset();

        $('#form_employee_id').val('').trigger('change');
        $('#form_shift_id').val('').trigger('change');
        $('#form_status').val('1').trigger('change');

        $('#employeeShiftModal').modal('show');
    });

    // Open Edit
    $(document).on('click', '.btn-edit', function(){
        clearErrors();
        var id = $(this).data('id');

        $.get('{{ url('employee_shifts') }}/' + id, function(res){
            if(!res.success){
                toastError(res.message || '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                return;
            }

            var d = res.data;

            $('#modalTitle').text('{{ trans('hr.edit_employee_shift') ?? 'تعديل وردية موظف' }}');
            $('#form_id').val(d.id);

            // branch ثابت حسب الفلتر الحالي
            $('#form_employee_id').val(d.employee_id).trigger('change');
            $('#form_shift_id').val(d.shift_id).trigger('change');

            $('#form_start_date').val(d.start_date);
            $('#form_end_date').val(d.end_date);
            $('#form_status').val(d.status).trigger('change');

            $('#employeeShiftModal').modal('show');
        }).fail(function(){
            toastError('{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
        });
    });

    // Submit (Store/Update)
    $('#employeeShiftForm').on('submit', function(e){
        e.preventDefault();
        clearErrors();

        var btn = $('#submitBtn');
        setLoading(btn, true, '<i class="ri-save-line me-1"></i> {{ trans('hr.save') ?? 'حفظ' }}');

        var id = $('#form_id').val();
        var url = id ? ('{{ url('employee_shifts') }}/' + id) : '{{ route('employee_shifts.store') }}';
        var data = $(this).serialize();
        if (id) data += '&_method=PUT';

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(res){
                setLoading(btn, false, '<i class="ri-save-line me-1"></i> {{ trans('hr.save') ?? 'حفظ' }}');

                if(!res.success){
                    toastError(res.message || '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                    return;
                }

                toastSuccess(res.message);
                $('#employeeShiftModal').modal('hide');

                var d = res.data;

                var rowData = [
                    '',
                    d.employee_name + ' <small class="text-muted">(' + (d.employee_code ?? '') + ')</small>',
                    d.branch_name,
                    '<span class="font fw-medium">'+d.shift_name+'</span>',
                    '<div class="text-center"><code>'+d.start_date+'</code></div>',
                    '<div class="text-center"><code>'+d.end_date+'</code></div>',
                    '<div class="text-center">'+statusBadgeHtml(d.status)+'</div>',
                    '<div class="text-center"><code>'+(d.created_at ?? '')+'</code></div>',
                    '<div class="text-center">'+actionsHtml(d.id, d.employee_name)+'</div>',
                ];

                if (id) {
                    var $tr = $('#row-'+id);
                    var row = table.row($tr.get(0));
                    if (row.any()) {
                        row.data(rowData);
                        table.draw(false);
                    }
                } else {
                    var rowApi = table.row.add(rowData);
                    table.draw(false);

                    var node = rowApi.node();
                    $(node).attr('id', 'row-'+d.id).attr('data-id', d.id);
                }

                renumber();
            },
            error: function(xhr){
                setLoading(btn, false, '<i class="ri-save-line me-1"></i> {{ trans('hr.save') ?? 'حفظ' }}');
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                }
            }
        });
    });

    // Delete
    $(document).on('click', '.btn-delete', function(){
        var id = $(this).data('id');
        var name = $(this).data('name');

        function doDelete(){
            $.ajax({
                url: '{{ url('employee_shifts') }}/' + id,
                method: 'POST',
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                dataType: 'json',
                success: function(res){
                    if(!res.success){
                        toastError(res.message || '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                        return;
                    }

                    toastSuccess(res.message);

                    var $tr = $('#row-'+id);
                    var row = table.row($tr.get(0));
                    if(row.any()){
                        row.remove();
                        table.draw(false);
                        renumber();
                    }
                },
                error: function(xhr){
                    toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                }
            });
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '{{ trans('hr.delete_confirm_title') ?? 'تأكيد الحذف' }}',
                html: '{{ trans('hr.delete_confirm_msg') ?? 'هل تريد حذف' }} <strong>' + name + '</strong> ؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ trans('hr.yes_delete') ?? 'نعم حذف' }}',
                cancelButtonText: '{{ trans('hr.cancel') ?? 'إلغاء' }}',
            }).then(function(r){
                if(r.isConfirmed) doDelete();
            });
        } else {
            if(confirm('{{ trans('hr.delete_confirm_title') ?? 'تأكيد الحذف' }}')) doDelete();
        }
    });

});
</script>

@endsection
