@extends('layouts.master_table')

@section('title')
    {{ trans('hr.advances') ?? 'السلف' }} | {{ trans('main_trans.title') }}
@endsection

@section('content')


{{-- Page Title --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="ri-hand-coin-line me-1"></i>
                {{ trans('hr.advances') ?? 'السلف' }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('hr.index') }}">{{ trans('hr.title') ?? 'الموارد البشرية' }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('hr.advances') ?? 'السلف' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div id="page-alerts" class="mb-3"></div>

{{-- Filters --}}
<form method="GET" action="{{ route('advances.index') }}" class="card border-0 shadow-sm mb-3">
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

                {{-- ✅ لم يعد يعتمد على اختيار الفرع من الفلتر --}}
                <button type="button" class="btn btn-success font w-100" id="btnOpenAdd">
                    <i class="ri-add-line me-1"></i> {{ trans('hr.add_advance') ?? 'إضافة سلفة' }}
                </button>
            </div>

        </div>
    </div>
</form>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0 font">{{ trans('hr.advances_list') ?? 'قائمة السلف' }}</h5>
    </div>

    <div class="card-body">
        <table id="advancesTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ trans('hr.employee') ?? 'الموظف' }}</th>
                    <th>{{ trans('hr.request_date') ?? 'تاريخ الطلب' }}</th>
                    <th>{{ trans('hr.start_month') ?? 'بداية الخصم' }}</th>
                    <th>{{ trans('hr.total_amount') ?? 'إجمالي' }}</th>
                    <th>{{ trans('hr.installments_count') ?? 'عدد الأقساط' }}</th>
                    <th>{{ trans('hr.monthly_installment') ?? 'القسط الشهري' }}</th>
                    <th>{{ trans('hr.paid_amount') ?? 'المدفوع' }}</th>
                    <th>{{ trans('hr.remaining_amount') ?? 'المتبقي' }}</th>
                    <th>{{ trans('hr.status') ?? 'الحالة' }}</th>
                    <th>{{ trans('hr.actions') ?? 'الإجراءات' }}</th>
                </tr>
            </thead>

            <tbody>
                @php $i=0; @endphp
                @foreach($rows as $r)
                    @php $i++; @endphp
                    <tr id="row-{{ $r->id }}" data-id="{{ $r->id }}" data-status="{{ $r->status }}">
                        <td>{{ $i }}</td>
                        <td class="font">
                            {{ $r->employee?->full_name ?? '-' }}
                            <small class="text-muted">({{ $r->employee?->code ?? '' }})</small>
                        </td>
                        <td class="text-center"><code>{{ $r->request_date }}</code></td>
                        <td class="text-center"><code>{{ \Carbon\Carbon::parse($r->start_month)->format('Y-m') }}</code></td>
                        <td class="text-center">{{ number_format((float)$r->total_amount,2) }}</td>
                        <td class="text-center">{{ (int)$r->installments_count }}</td>
                        <td class="text-center">{{ number_format((float)$r->monthly_installment,2) }}</td>
                        <td class="text-center">{{ number_format((float)$r->paid_amount,2) }}</td>
                        <td class="text-center">{{ number_format((float)$r->remaining_amount,2) }}</td>
                        <td class="text-center">
                            @if($r->status === 'pending')
                                <span class="badge bg-warning text-dark">{{ trans('hr.status_pending') ?? 'معلق' }}</span>
                            @elseif($r->status === 'approved')
                                <span class="badge bg-success">{{ trans('hr.status_approved') ?? 'معتمد' }}</span>
                            @elseif($r->status === 'rejected')
                                <span class="badge bg-danger">{{ trans('hr.status_rejected') ?? 'مرفوض' }}</span>
                            @else
                                <span class="badge bg-primary">{{ trans('hr.status_completed') ?? 'مكتمل' }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                    <i class="ri-more-fill align-middle"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">

                                    <li>
                                        <button type="button" class="dropdown-item btn-view-installments" data-id="{{ $r->id }}">
                                            <i class="ri-calendar-event-line align-bottom me-2 text-muted"></i>
                                            {{ trans('hr.view_installments') ?? 'عرض الأقساط' }}
                                        </button>
                                    </li>

                                    <li>
                                        <button type="button" class="dropdown-item btn-edit" data-id="{{ $r->id }}">
                                            <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                            {{ trans('hr.edit') ?? 'تعديل' }}
                                        </button>
                                    </li>

                                    @if($r->status === 'pending')
                                        <li><hr class="dropdown-divider"></li>

                                        <li>
                                            <button type="button" class="dropdown-item text-success btn-approve" data-id="{{ $r->id }}">
                                                <i class="ri-check-line align-bottom me-2"></i>
                                                {{ trans('hr.approve_advance') ?? 'اعتماد' }}
                                            </button>
                                        </li>

                                        <li>
                                            <button type="button" class="dropdown-item text-danger btn-reject" data-id="{{ $r->id }}">
                                                <i class="ri-close-line align-bottom me-2"></i>
                                                {{ trans('hr.reject_advance') ?? 'رفض' }}
                                            </button>
                                        </li>
                                    @endif

                                    <li><hr class="dropdown-divider"></li>

                                    <li>
                                        <button type="button" class="dropdown-item text-danger btn-delete" data-id="{{ $r->id }}"
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
<div id="advanceModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font" id="modalTitle">{{ trans('hr.add_advance') ?? 'إضافة سلفة' }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div id="editApprovedHint" class="alert alert-info d-none font">
                    {{ trans('hr.advance_reschedule_hint') ?? 'عند تعديل سلفة معتمدة سيتم إعادة جدولة الأقساط غير المدفوعة فقط.' }}
                </div>

                <form id="advanceForm" autocomplete="off">
                    @csrf
                    <input type="hidden" id="form_id">

                    <div class="row g-3">

                        {{-- ✅ branch select داخل المودال --}}
                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.branch') ?? 'الفرع' }}</label>
                            <select name="branch_id" id="form_branch_id" class="form-select font" required>
                                <option value="">{{ trans('hr.select_branch') ?? 'اختر الفرع' }}</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="form_branch_id_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.employee') ?? 'الموظف' }}</label>
                            <select name="employee_id" id="form_employee_id" class="form-select font" required>
                                <option value="">{{ trans('hr.select_employee') ?? 'اختر الموظف' }}</option>
                            </select>
                            <div class="invalid-feedback" id="form_employee_id_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.request_date') ?? 'تاريخ الطلب' }}</label>
                            <input type="date" name="request_date" id="form_request_date" class="form-control font" required>
                            <div class="invalid-feedback" id="form_request_date_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.start_month') ?? 'بداية الخصم' }}</label>
                            <input type="month" name="start_month" id="form_start_month" class="form-control font" required>
                            <div class="invalid-feedback" id="form_start_month_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.total_amount') ?? 'إجمالي السلفة' }}</label>
                            <input type="number" step="0.01" min="1" name="total_amount" id="form_total_amount" class="form-control font" required>
                            <div class="invalid-feedback" id="form_total_amount_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.installments_count') ?? 'عدد الأقساط' }}</label>
                            <input type="number" min="1" name="installments_count" id="form_installments_count" class="form-control font" required>
                            <div class="invalid-feedback" id="form_installments_count_error"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label font">{{ trans('hr.notes') ?? 'ملاحظات' }}</label>
                            <input type="text" name="notes" id="form_notes" class="form-control font">
                            <div class="invalid-feedback" id="form_notes_error"></div>
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

{{-- Installments Modal --}}
<div id="installmentsModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font">
                    <i class="ri-calendar-event-line me-1 text-primary"></i>
                    {{ trans('hr.installments') ?? 'الأقساط' }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6 font">
                        <strong>{{ trans('hr.employee') ?? 'الموظف' }}:</strong>
                        <span id="inst_employee">—</span>
                    </div>
                    <div class="col-md-6 font">
                        <strong>{{ trans('hr.total_amount') ?? 'إجمالي' }}:</strong>
                        <span id="inst_total">—</span>
                    </div>
                </div>

                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ trans('hr.installment_month') ?? 'الشهر' }}</th>
                            <th>{{ trans('hr.installment_amount') ?? 'القيمة' }}</th>
                            <th>{{ trans('hr.is_paid') ?? 'مدفوع؟' }}</th>
                            <th>{{ trans('hr.paid_date') ?? 'تاريخ الدفع' }}</th>
                            <th>{{ trans('hr.payroll_id') ?? 'Payroll' }}</th>
                        </tr>
                    </thead>
                    <tbody id="installmentsBody">
                        <tr><td colspan="6" class="text-center text-muted">—</td></tr>
                    </tbody>
                </table>

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

    var table = $('#advancesTable').DataTable({
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

        // ✅ داخل المودال لازم dropdownParent
        $('#form_branch_id').select2({ width:'100%', dropdownParent: $('#advanceModal') }); // [web:249]
        $('#form_employee_id').select2({ width:'100%', dropdownParent: $('#advanceModal') }); // [web:249]
    }
    initSelect2();

    // فلترة موظفين الفلتر (اختياري)
    $('#filter_branch_id').on('change', function(){
        var branchId = $(this).val();
        $('#filter_employee_id').html('<option value="">{{ trans('hr.all_employees') ?? 'كل الموظفين' }}</option>');

        if (!branchId) {
            initSelect2();
            return;
        }

        $.get('{{ route('advances.employees.byBranch') }}', { branch_id: branchId }, function(res){
            if (res.success) {
                res.data.forEach(function(e){
                    $('#filter_employee_id').append('<option value="'+e.id+'">'+e.name+' ('+e.code+')</option>');
                });
                initSelect2();
            }
        });
    });

    function clearErrors(){
        ['branch_id','employee_id','request_date','start_month','total_amount','installments_count','notes'].forEach(function(f){
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

    function statusBadge(status){
        if (status === 'pending') return '<span class="badge bg-warning text-dark">{{ trans('hr.status_pending') ?? 'معلق' }}</span>';
        if (status === 'approved') return '<span class="badge bg-success">{{ trans('hr.status_approved') ?? 'معتمد' }}</span>';
        if (status === 'rejected') return '<span class="badge bg-danger">{{ trans('hr.status_rejected') ?? 'مرفوض' }}</span>';
        return '<span class="badge bg-primary">{{ trans('hr.status_completed') ?? 'مكتمل' }}</span>';
    }

    function actionsHtml(d){
        var html = '' +
        '<div class="dropdown d-inline-block">' +
            '<button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown">' +
                '<i class="ri-more-fill align-middle"></i>' +
            '</button>' +
            '<ul class="dropdown-menu dropdown-menu-end">' +

                '<li><button type="button" class="dropdown-item btn-view-installments" data-id="'+d.id+'">' +
                    '<i class="ri-calendar-event-line align-bottom me-2 text-muted"></i>{{ trans('hr.view_installments') ?? 'عرض الأقساط' }}' +
                '</button></li>' +

                '<li><button type="button" class="dropdown-item btn-edit" data-id="'+d.id+'">' +
                    '<i class="ri-pencil-fill align-bottom me-2 text-muted"></i>{{ trans('hr.edit') ?? 'تعديل' }}' +
                '</button></li>';

        if (d.status === 'pending') {
            html += '<li><hr class="dropdown-divider"></li>' +
                '<li><button type="button" class="dropdown-item text-success btn-approve" data-id="'+d.id+'">' +
                    '<i class="ri-check-line align-bottom me-2"></i>{{ trans('hr.approve_advance') ?? 'اعتماد' }}' +
                '</button></li>' +
                '<li><button type="button" class="dropdown-item text-danger btn-reject" data-id="'+d.id+'">' +
                    '<i class="ri-close-line align-bottom me-2"></i>{{ trans('hr.reject_advance') ?? 'رفض' }}' +
                '</button></li>';
        }

        html += '<li><hr class="dropdown-divider"></li>' +
                '<li><button type="button" class="dropdown-item text-danger btn-delete" data-id="'+d.id+'" data-name="'+(d.employee_name||'')+'">' +
                    '<i class="ri-delete-bin-fill align-bottom me-2"></i>{{ trans('hr.delete') ?? 'حذف' }}' +
                '</button></li>' +
            '</ul>' +
        '</div>';

        return html;
    }

    // ✅ تحميل موظفين المودال حسب الفرع
    function loadModalEmployees(branchId, selectedEmployeeId){
        selectedEmployeeId = selectedEmployeeId || '';

        $('#form_employee_id').html('<option value="">{{ trans('hr.select_employee') ?? 'اختر الموظف' }}</option>');

        if (!branchId) {
            $('#form_employee_id').val('').trigger('change');
            return;
        }

        $.get('{{ route('advances.employees.byBranch') }}', { branch_id: branchId }, function(res){
            if (res.success) {
                res.data.forEach(function(e){
                    $('#form_employee_id').append('<option value="'+e.id+'">'+e.name+' ('+e.code+')</option>');
                });

                if (selectedEmployeeId) {
                    $('#form_employee_id').val(selectedEmployeeId).trigger('change');
                } else {
                    $('#form_employee_id').val('').trigger('change');
                }
            }
        });
    }

    // تغيير فرع داخل المودال => فلترة الموظفين
    $('#form_branch_id').on('change', function(){
        loadModalEmployees($(this).val(), '');
    });

    // Open Add
    $('#btnOpenAdd').on('click', function(){
        clearErrors();
        $('#modalTitle').text('{{ trans('hr.add_advance') ?? 'إضافة سلفة' }}');
        $('#form_id').val('');
        $('#advanceForm')[0].reset();

        $('#editApprovedHint').addClass('d-none');

        // الافتراضي: استخدم فرع الفلتر إن وجد، وإلا فاضي
        var defaultBranch = $('#filter_branch_id').val() || '';
        $('#form_branch_id').val(defaultBranch).trigger('change');

        // تحميل موظفين بناءً على الفرع
        loadModalEmployees(defaultBranch, '');

        $('#advanceModal').modal('show');
    });

    // Open Edit
    $(document).on('click', '.btn-edit', function(){
        clearErrors();
        var id = $(this).data('id');

        $.get('{{ url('advances') }}/' + id, function(res){
            if(!res.success){
                toastError(res.message || '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                return;
            }

            var d = res.data;

            $('#modalTitle').text('{{ trans('hr.edit_advance') ?? 'تعديل سلفة' }}');
            $('#form_id').val(d.id);

            // ✅ تعبئة الفرع أولاً ثم تحميل موظفينه ثم اختيار الموظف
            $('#form_branch_id').val(d.branch_id).trigger('change');
            loadModalEmployees(d.branch_id, d.employee_id);

            $('#form_request_date').val(d.request_date);
            $('#form_start_month').val(d.start_month); // Y-m
            $('#form_total_amount').val(d.total_amount);
            $('#form_installments_count').val(d.installments_count);
            $('#form_notes').val(d.notes || '');

            if (d.status === 'approved') $('#editApprovedHint').removeClass('d-none');
            else $('#editApprovedHint').addClass('d-none');

            $('#advanceModal').modal('show');
        }).fail(function(){
            toastError('{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
        });
    });

    // Submit (Store/Update)
    $('#advanceForm').on('submit', function(e){
        e.preventDefault();
        clearErrors();

        var btn = $('#submitBtn');
        setLoading(btn, true, '<i class="ri-save-line me-1"></i> {{ trans('hr.save') ?? 'حفظ' }}');

        var id = $('#form_id').val();
        var url = id ? ('{{ url('advances') }}/' + id) : '{{ route('advances.store') }}';
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
                $('#advanceModal').modal('hide');

                var d = res.data;

                var rowData = [
                    '',
                    d.employee_name + ' <small class="text-muted">(' + (d.employee_code ?? '') + ')</small>',
                    '<div class="text-center"><code>' + (d.request_date ?? '') + '</code></div>',
                    '<div class="text-center"><code>' + (d.start_month ?? '') + '</code></div>',
                    '<div class="text-center">' + d.total_amount + '</div>',
                    '<div class="text-center">' + d.installments_count + '</div>',
                    '<div class="text-center">' + d.monthly_installment + '</div>',
                    '<div class="text-center">' + d.paid_amount + '</div>',
                    '<div class="text-center">' + d.remaining_amount + '</div>',
                    '<div class="text-center">' + statusBadge(d.status) + '</div>',
                    '<div class="text-center">' + actionsHtml(d) + '</div>',
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
                    $(node).attr('id', 'row-'+d.id).attr('data-id', d.id).attr('data-status', d.status);
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

    // View installments
    $(document).on('click', '.btn-view-installments', function(){
        var id = $(this).data('id');

        $('#installmentsBody').html('<tr><td colspan="6" class="text-center text-muted">...</td></tr>');
        $('#inst_employee').text('—');
        $('#inst_total').text('—');

        $.get('{{ url('advances') }}/' + id, function(res){
            if(!res.success){
                toastError(res.message || '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                return;
            }

            var d = res.data;

            $('#inst_employee').text((d.employee_name || '-') + ' (' + (d.employee_code || '') + ')');
            $('#inst_total').text(d.total_amount);

            var html = '';
            if (d.installments && d.installments.length) {
                d.installments.forEach(function(it, idx){
                    html += '<tr>' +
                        '<td>' + (idx+1) + '</td>' +
                        '<td class="text-center"><code>' + (it.month ?? '') + '</code></td>' +
                        '<td class="text-center">' + (it.amount ?? '') + '</td>' +
                        '<td class="text-center">' + (parseInt(it.is_paid||0)===1 ? '<span class="badge bg-success">{{ trans('hr.paid') ?? 'مدفوع' }}</span>' : '<span class="badge bg-light text-muted">{{ trans('hr.not_paid') ?? 'غير مدفوع' }}</span>') + '</td>' +
                        '<td class="text-center"><code>' + (it.paid_date ?? '—') + '</code></td>' +
                        '<td class="text-center">' + (it.payroll_id ?? '—') + '</td>' +
                    '</tr>';
                });
            } else {
                html = '<tr><td colspan="6" class="text-center text-muted">—</td></tr>';
            }

            $('#installmentsBody').html(html);
            $('#installmentsModal').modal('show');
        }).fail(function(){
            toastError('{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
        });
    });

    // Approve
    $(document).on('click', '.btn-approve', function(){
        var id = $(this).data('id');

        function doApprove(){
            $.ajax({
                url: '{{ url('advances') }}/' + id + '/approve',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                dataType: 'json',
                success: function(res){
                    if(!res.success){
                        toastError(res.message || '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                        return;
                    }
                    toastSuccess(res.message);

                    var d = res.data;

                    var $tr = $('#row-'+d.id);
                    var row = table.row($tr.get(0));
                    if (row.any()) {
                        var rowData = row.data();
                        rowData[6] = '<div class="text-center">' + d.monthly_installment + '</div>';
                        rowData[7] = '<div class="text-center">' + d.paid_amount + '</div>';
                        rowData[8] = '<div class="text-center">' + d.remaining_amount + '</div>';
                        rowData[9] = '<div class="text-center">' + statusBadge(d.status) + '</div>';
                        rowData[10]= '<div class="text-center">' + actionsHtml(d) + '</div>';
                        row.data(rowData).draw(false);
                    }
                },
                error: function(xhr){
                    toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                }
            });
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '{{ trans('hr.approve_confirm_title') ?? 'تأكيد الاعتماد' }}',
                text: '{{ trans('hr.approve_confirm_msg') ?? 'سيتم اعتماد السلفة وتوليد الأقساط، هل تريد المتابعة؟' }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0ab39c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ trans('hr.yes_approve') ?? 'نعم اعتماد' }}',
                cancelButtonText: '{{ trans('hr.cancel') ?? 'إلغاء' }}',
            }).then(function(r){
                if(r.isConfirmed) doApprove();
            });
        } else {
            if(confirm('{{ trans('hr.approve_confirm_title') ?? 'تأكيد الاعتماد' }}')) doApprove();
        }
    });

    // Reject
    $(document).on('click', '.btn-reject', function(){
        var id = $(this).data('id');

        function doReject(){
            $.ajax({
                url: '{{ url('advances') }}/' + id + '/reject',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                dataType: 'json',
                success: function(res){
                    if(!res.success){
                        toastError(res.message || '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                        return;
                    }
                    toastSuccess(res.message);

                    var d = res.data;

                    var $tr = $('#row-'+d.id);
                    var row = table.row($tr.get(0));
                    if (row.any()) {
                        var rowData = row.data();
                        rowData[9] = '<div class="text-center">' + statusBadge(d.status) + '</div>';
                        rowData[10]= '<div class="text-center">' + actionsHtml(d) + '</div>';
                        row.data(rowData).draw(false);
                    }
                },
                error: function(xhr){
                    toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                }
            });
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '{{ trans('hr.reject_confirm_title') ?? 'تأكيد الرفض' }}',
                text: '{{ trans('hr.reject_confirm_msg') ?? 'هل تريد رفض هذه السلفة؟' }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ trans('hr.yes_reject') ?? 'نعم رفض' }}',
                cancelButtonText: '{{ trans('hr.cancel') ?? 'إلغاء' }}',
            }).then(function(r){
                if(r.isConfirmed) doReject();
            });
        } else {
            if(confirm('{{ trans('hr.reject_confirm_title') ?? 'تأكيد الرفض' }}')) doReject();
        }
    });

    // Delete
    $(document).on('click', '.btn-delete', function(){
        var id = $(this).data('id');
        var name = $(this).data('name');

        function doDelete(){
            $.ajax({
                url: '{{ url('advances') }}/' + id,
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
                        row.remove().draw(false);
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
