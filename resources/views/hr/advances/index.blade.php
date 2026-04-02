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
                    <li class="breadcrumb-item">
                        <a href="{{ route('hr.index') }}">{{ trans('hr.title') ?? 'الموارد البشرية' }}</a>
                    </li>
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
                @can('hr_advances_create')
                <button type="button" class="btn btn-success font w-100" id="btnOpenAdd">
                    <i class="ri-add-line me-1"></i> {{ trans('hr.add_advance') ?? 'إضافة سلفة' }}
                </button>
                @endcan
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
        <table id="advancesTable" class="table table-bordered table-striped align-middle dt-responsive" style="width:100%">
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
                @php $i = 0; @endphp
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
                    <td class="text-center">{{ number_format((float)$r->total_amount, 2) }}</td>
                    <td class="text-center">{{ (int)$r->installments_count }}</td>
                    <td class="text-center">{{ number_format((float)$r->monthly_installment, 2) }}</td>
                    <td class="text-center">{{ number_format((float)$r->paid_amount, 2) }}</td>
                    <td class="text-center">{{ number_format((float)$r->remaining_amount, 2) }}</td>
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
                                @can('hr_advances_edit')
                                <li>
                                    <button type="button" class="dropdown-item btn-edit" data-id="{{ $r->id }}">
                                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                        {{ trans('hr.edit') ?? 'تعديل' }}
                                    </button>
                                </li>
                                @endcan
                                @if($r->status === 'pending')
                                <li><hr class="dropdown-divider"></li>
                                @can('hr_advances_approve')
                                <li>
                                    <button type="button" class="dropdown-item text-success btn-approve"
                                        data-id="{{ $r->id }}"
                                        data-branch-id="{{ $r->branch_id }}"
                                        data-branch-name="{{ $r->branch?->name ?? '' }}"
                                        data-employee-name="{{ $r->employee?->full_name ?? '' }}"
                                        data-amount="{{ $r->total_amount }}">
                                        <i class="ri-check-line align-bottom me-2"></i>
                                        {{ trans('hr.approve_advance') ?? 'اعتماد' }}
                                    </button>
                                </li>
                                @endcan
                                @can('hr_advances_reject')
                                <li>
                                    <button type="button" class="dropdown-item text-danger btn-reject" data-id="{{ $r->id }}">
                                        <i class="ri-close-line align-bottom me-2"></i>
                                        {{ trans('hr.reject_advance') ?? 'رفض' }}
                                    </button>
                                </li>
                                @endcan
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                @can('hr_advances_delete')
                                <li>
                                    <button type="button" class="dropdown-item text-danger btn-delete"
                                        data-id="{{ $r->id }}"
                                        data-name="{{ $r->employee?->full_name ?? '' }}">
                                        <i class="ri-delete-bin-fill align-bottom me-2"></i>
                                        {{ trans('hr.delete') ?? 'حذف' }}
                                    </button>
                                </li>
                                @endcan
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

{{-- Approve + Expense Modal --}}
<div class="modal fade" id="approveExpenseModal" tabindex="-1" aria-labelledby="approveExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-success-subtle">
                <h5 class="modal-title font" id="approveExpenseModalLabel">
                    <i class="ri-check-double-line align-bottom me-1"></i>
                    {{ trans('hr.approve_and_pay_title') ?? 'اعتماد السلفة وتسجيل الصرف' }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 rounded mb-3" style="background: rgba(10,179,156,.06); border: 1px solid rgba(10,179,156,.2);">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted font">{{ trans('hr.employee') ?? 'الموظف' }}</span>
                        <strong id="aeModal_empName">—</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted font">{{ trans('hr.total_amount') ?? 'إجمالي السلفة' }}</span>
                        <strong id="aeModal_amount">—</strong>
                    </div>
                </div>
                <div class="alert alert-info font">
                    <i class="ri-information-line align-bottom me-1"></i>
                    {{ trans('hr.approve_expense_modal_info') ?? 'سيتم اعتماد السلفة وتوليد الأقساط وتسجيل سجل تلقائي في المصروفات.' }}
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold font">
                        <i class="ri-price-tag-3-line align-bottom me-1"></i>
                        {{ trans('hr.expense_type') ?? 'نوع المصروف' }} <span class="text-danger">*</span>
                    </label>
                    <select id="aeModal_expense_type_id" class="form-select font">
                        <option value="">{{ trans('hr.select_expense_type') ?? 'اختر نوع المصروف' }}</option>
                        @foreach($ExpensesTypes as $et)
                        @php
                            $etName = method_exists($et, 'getTranslation')
                                ? $et->getTranslation('name', app()->getLocale())
                                : (is_array($et->name) ? ($et->name[app()->getLocale()] ?? ($et->name['ar'] ?? '')) : $et->name);
                        @endphp
                        <option value="{{ $et->id }}">{{ $etName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold font">
                        <i class="ri-user-line align-bottom me-1"></i>
                        {{ trans('hr.disbursed_by') ?? 'القائم بالصرف' }}
                        <small class="text-muted fw-normal">({{ trans('hr.optional') ?? 'اختياري' }})</small>
                    </label>
                    <select id="aeModal_expense_disbursed_by" class="form-select font">
                        <option value="">{{ trans('hr.optional') ?? 'اختياري' }}</option>
                    </select>
                    <div class="form-text text-muted font">{{ trans('hr.disbursed_by_hint') ?? 'يتم تحميله تلقائياً بناءً على الفرع' }}</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                    <i class="ri-close-line align-bottom me-1"></i> {{ trans('hr.cancel') ?? 'إلغاء' }}
                </button>
                <button type="button" class="btn btn-success font" id="confirmApproveWithExpense">
                    <i class="ri-check-double-line align-bottom me-1"></i>
                    {{ trans('hr.confirm_approve_and_disburse') ?? 'تأكيد الاعتماد والصرف' }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
    
    // permissions
    var canEdit    = @json(auth()->user()->can('hr_advances_edit'));
    var canApprove = @json(auth()->user()->can('hr_advances_approve'));
    var canReject  = @json(auth()->user()->can('hr_advances_reject'));
    var canDelete  = @json(auth()->user()->can('hr_advances_delete'));


    // ============================================================
    // Toast
    // ============================================================
    function toast(type, message) {
        if (typeof Swal !== 'undefined' && Swal.mixin) {
            Swal.mixin({
                toast: true, position: 'top-end',
                showConfirmButton: false, timer: 2500, timerProgressBar: true
            }).fire({ icon: type, title: (type === 'success' ? 'Success ! ' : 'Error ! ') + message });
            return;
        }
        var klass = (type === 'success') ? 'alert-success' : 'alert-danger';
        var title = (type === 'success') ? 'Success !' : 'Error !';
        $('#page-alerts').html(
            '<div class="alert ' + klass + ' alert-dismissible fade show" role="alert">' +
            '<strong>' + title + '</strong> ' + message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
        );
    }
    function toastSuccess(msg) { toast('success', msg); }
    function toastError(msg)   { toast('error', msg); }

    // ============================================================
    // DataTable
    // ============================================================
    var table = $('#advancesTable').DataTable({
        responsive: true,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' },
        columnDefs: [{ orderable: false, targets: [-1] }],
        order: [[0, 'asc']],
        pageLength: 25,
    });

    function renumber() {
        table.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
            cell.innerHTML = i + 1;
        });
    }
    table.on('draw.dt', function () { renumber(); });

    // ============================================================
    // Select2 — منفصل: فلتر / مودال
    // ============================================================
    function destroySelect2(selector) {
        if ($(selector).hasClass('select2-hidden-accessible')) {
            $(selector).select2('destroy');
        }
    }

    function initFilterSelect2() {
        if (!$.fn || !$.fn.select2) return;
        destroySelect2('#filter_branch_id');
        destroySelect2('#filter_employee_id');
        $('#filter_branch_id').select2({ width: '100%' });
        $('#filter_employee_id').select2({ width: '100%' });
    }

    function initModalSelect2() {
        if (!$.fn || !$.fn.select2) return;
        destroySelect2('#form_branch_id');
        destroySelect2('#form_employee_id');
        $('#form_branch_id').select2({ width: '100%', dropdownParent: $('#advanceModal') });
        $('#form_employee_id').select2({ width: '100%', dropdownParent: $('#advanceModal') });
    }

    // تهيئة أولية
    initFilterSelect2();
    // المودال Select2 تُهيّأ عند فتح المودال فقط
    $('#advanceModal').on('shown.bs.modal', function () {
        initModalSelect2();
    });

    // ============================================================
    // فلترة موظفين الفلتر
    // ============================================================
    $('#filter_branch_id').on('change', function () {
        var branchId = $(this).val();
        destroySelect2('#filter_employee_id');
        $('#filter_employee_id').html('<option value="">{{ trans("hr.all_employees") ?? "كل الموظفين" }}</option>');
        initFilterSelect2();

        if (!branchId) return;

        $.get('{{ route("advances.employees.byBranch") }}', { branch_id: branchId }, function (res) {
            if (res.success) {
                res.data.forEach(function (e) {
                    $('#filter_employee_id').append('<option value="' + e.id + '">' + e.name + ' (' + e.code + ')</option>');
                });
                destroySelect2('#filter_employee_id');
                $('#filter_employee_id').select2({ width: '100%' });
            }
        });
    });

    // ============================================================
    // Helpers
    // ============================================================
    function clearErrors() {
        ['branch_id','employee_id','request_date','start_month','total_amount','installments_count','notes'].forEach(function (f) {
            $('#form_' + f).removeClass('is-invalid');
            $('#form_' + f + '_error').text('');
        });
    }

    function showErrors(errors) {
        $.each(errors, function (field, messages) {
            $('#form_' + field).addClass('is-invalid');
            $('#form_' + field + '_error').text(messages[0]);
        });
    }

    function setLoading(btn, loading, htmlNormal) {
        if (loading) btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
        else btn.prop('disabled', false).html(htmlNormal);
    }

    var ts = {
        status_pending:   '{{ trans("hr.status_pending")   ?? "معلق" }}',
        status_approved:  '{{ trans("hr.status_approved")  ?? "معتمد" }}',
        status_rejected:  '{{ trans("hr.status_rejected")  ?? "مرفوض" }}',
        status_completed: '{{ trans("hr.status_completed") ?? "مكتمل" }}',
        view_installments:'{{ trans("hr.view_installments") ?? "عرض الأقساط" }}',
        edit:             '{{ trans("hr.edit")             ?? "تعديل" }}',
        approve:          '{{ trans("hr.approve_advance")  ?? "اعتماد" }}',
        reject:           '{{ trans("hr.reject_advance")   ?? "رفض" }}',
        delete:           '{{ trans("hr.delete")           ?? "حذف" }}',
        select_employee:  '{{ trans("hr.select_employee")  ?? "اختر الموظف" }}',
        add_advance:      '{{ trans("hr.add_advance")      ?? "إضافة سلفة" }}',
        edit_advance:     '{{ trans("hr.edit_advance")     ?? "تعديل سلفة" }}',
        error_occurred:   '{{ trans("hr.error_occurred")   ?? "حدث خطأ" }}',
        save:             '{{ trans("hr.save")             ?? "حفظ" }}',
        paid:             '{{ trans("hr.paid")             ?? "مدفوع" }}',
        not_paid:         '{{ trans("hr.not_paid")         ?? "غير مدفوع" }}'
    };

    function statusBadge(status) {
        if (status === 'pending')   return '<span class="badge bg-warning text-dark">' + ts.status_pending   + '</span>';
        if (status === 'approved')  return '<span class="badge bg-success">'            + ts.status_approved  + '</span>';
        if (status === 'rejected')  return '<span class="badge bg-danger">'             + ts.status_rejected  + '</span>';
        return '<span class="badge bg-primary">' + ts.status_completed + '</span>';
    }

    function actionsHtml(d) {
        var html =
            '<div class="dropdown d-inline-block">' +
            '<button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown">' +
            '<i class="ri-more-fill align-middle"></i></button>' +
            '<ul class="dropdown-menu dropdown-menu-end">' +
            '<li><button type="button" class="dropdown-item btn-view-installments" data-id="' + d.id + '">' +
            '<i class="ri-calendar-event-line align-bottom me-2 text-muted"></i>' + ts.view_installments + '</button></li>';

        if (canEdit) {
            html += '<li><button type="button" class="dropdown-item btn-edit" data-id="' + d.id + '">' +
                    '<i class="ri-pencil-fill align-bottom me-2 text-muted"></i>' + ts.edit + '</button></li>';
        }

        if (d.status === 'pending') {
            if (canApprove || canReject) {
                html += '<li><hr class="dropdown-divider"></li>';
            }
            if (canApprove) {
                html += '<li><button type="button" class="dropdown-item text-success btn-approve" data-id="' + d.id + '" ' +
                        'data-branch-id="' + (d.branch_id || '') + '" data-branch-name="' + (d.branch_name || '') + '" ' +
                        'data-employee-name="' + (d.employee_name || '') + '" data-amount="' + (d.total_amount || '') + '">' +
                        '<i class="ri-check-line align-bottom me-2"></i>' + ts.approve + '</button></li>';
            }
            if (canReject) {
                html += '<li><button type="button" class="dropdown-item text-danger btn-reject" data-id="' + d.id + '">' +
                        '<i class="ri-close-line align-bottom me-2"></i>' + ts.reject + '</button></li>';
            }
        }

        if (canDelete) {
            html += '<li><hr class="dropdown-divider"></li>' +
                    '<li><button type="button" class="dropdown-item text-danger btn-delete" data-id="' + d.id + '" data-name="' + (d.employee_name || '') + '">' +
                    '<i class="ri-delete-bin-fill align-bottom me-2"></i>' + ts.delete + '</button></li>';
        }

        html += '</ul></div>';
        return html;
    }

    // ============================================================
    // تحميل موظفين المودال
    // ============================================================
    function loadModalEmployees(branchId, selectedEmployeeId) {
        selectedEmployeeId = selectedEmployeeId || '';
        destroySelect2('#form_employee_id');
        $('#form_employee_id').html('<option value="">' + ts.select_employee + '</option>');

        if (!branchId) {
            $('#form_employee_id').select2({ width: '100%', dropdownParent: $('#advanceModal') });
            return;
        }

        $.get('{{ route("advances.employees.byBranch") }}', { branch_id: branchId }, function (res) {
            if (res.success) {
                res.data.forEach(function (e) {
                    $('#form_employee_id').append('<option value="' + e.id + '">' + e.name + ' (' + e.code + ')</option>');
                });
                if (selectedEmployeeId) $('#form_employee_id').val(selectedEmployeeId);
            }
            destroySelect2('#form_employee_id');
            $('#form_employee_id').select2({ width: '100%', dropdownParent: $('#advanceModal') });
        });
    }

    $('#form_branch_id').on('change', function () {
        loadModalEmployees($(this).val(), '');
    });

    // ============================================================
    // Open Add
    // ============================================================
    $('#btnOpenAdd').on('click', function () {
        clearErrors();
        $('#modalTitle').text(ts.add_advance);
        $('#form_id').val('');
        $('#advanceForm')[0].reset();
        $('#editApprovedHint').addClass('d-none');

        var defaultBranch = $('#filter_branch_id').val() || '';
        $('#form_branch_id').val(defaultBranch);
        loadModalEmployees(defaultBranch, '');

        $('#advanceModal').modal('show');
    });

    // ============================================================
    // Open Edit
    // ============================================================
    $(document).on('click', '.btn-edit', function () {
        clearErrors();
        var id = $(this).data('id');

        $.get('{{ url("advances") }}/' + id, function (res) {
            if (!res.success) { toastError(res.message || ts.error_occurred); return; }

            var d = res.data;
            $('#modalTitle').text(ts.edit_advance);
            $('#form_id').val(d.id);
            $('#form_branch_id').val(d.branch_id);
            $('#form_request_date').val(d.request_date);
            $('#form_start_month').val(d.start_month);
            $('#form_total_amount').val(d.total_amount);
            $('#form_installments_count').val(d.installments_count);
            $('#form_notes').val(d.notes || '');

            if (d.status === 'approved') $('#editApprovedHint').removeClass('d-none');
            else $('#editApprovedHint').addClass('d-none');

            loadModalEmployees(d.branch_id, d.employee_id);
            $('#advanceModal').modal('show');
        }).fail(function () { toastError(ts.error_occurred); });
    });

    // ============================================================
    // Submit (Store / Update)
    // ============================================================
    $('#advanceForm').on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        var btn     = $('#submitBtn');
        var saveHtml = '<i class="ri-save-line me-1"></i> ' + ts.save;
        setLoading(btn, true, saveHtml);

        var id   = $('#form_id').val();
        var url  = id ? ('{{ url("advances") }}/' + id) : '{{ route("advances.store") }}';
        var data = $(this).serialize();
        if (id) data += '&_method=PUT';

        $.ajax({
            url: url, method: 'POST', data: data, dataType: 'json',
            success: function (res) {
                setLoading(btn, false, saveHtml);
                if (!res.success) { toastError(res.message || ts.error_occurred); return; }

                toastSuccess(res.message);
                $('#advanceModal').modal('hide');

                var d = res.data;
                var rowData = [
                    '',
                    d.employee_name + ' <small class="text-muted">(' + (d.employee_code ?? '') + ')</small>',
                    '<div class="text-center"><code>' + (d.request_date ?? '') + '</code></div>',
                    '<div class="text-center"><code>' + (d.start_month   ?? '') + '</code></div>',
                    '<div class="text-center">' + d.total_amount        + '</div>',
                    '<div class="text-center">' + d.installments_count  + '</div>',
                    '<div class="text-center">' + d.monthly_installment + '</div>',
                    '<div class="text-center">' + d.paid_amount         + '</div>',
                    '<div class="text-center">' + d.remaining_amount    + '</div>',
                    '<div class="text-center">' + statusBadge(d.status) + '</div>',
                    '<div class="text-center">' + actionsHtml(d)        + '</div>',
                ];

                if (id) {
                    var row = table.row($('#row-' + id).get(0));
                    if (row.any()) { row.data(rowData); table.draw(false); }
                } else {
                    var newRow = table.row.add(rowData);
                    table.draw(false);
                    $(newRow.node()).attr('id', 'row-' + d.id).attr('data-id', d.id).attr('data-status', d.status);
                }
                renumber();
            },
            error: function (xhr) {
                setLoading(btn, false, saveHtml);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : ts.error_occurred);
                }
            }
        });
    });

    // ============================================================
    // View Installments
    // ============================================================
    $(document).on('click', '.btn-view-installments', function () {
        var id = $(this).data('id');
        $('#installmentsBody').html('<tr><td colspan="6" class="text-center text-muted">...</td></tr>');
        $('#inst_employee').text('—');
        $('#inst_total').text('—');

        $.get('{{ url("advances") }}/' + id, function (res) {
            if (!res.success) { toastError(res.message || ts.error_occurred); return; }

            var d = res.data;
            $('#inst_employee').text((d.employee_name || '-') + ' (' + (d.employee_code || '') + ')');
            $('#inst_total').text(d.total_amount);

            var html = '';
            if (d.installments && d.installments.length) {
                d.installments.forEach(function (it, idx) {
                    var paidBadge = parseInt(it.is_paid || 0) === 1
                        ? '<span class="badge bg-success">' + ts.paid + '</span>'
                        : '<span class="badge bg-light text-muted">' + ts.not_paid + '</span>';
                    html += '<tr>' +
                        '<td>' + (idx + 1) + '</td>' +
                        '<td class="text-center"><code>' + (it.month      ?? '')  + '</code></td>' +
                        '<td class="text-center">'       + (it.amount     ?? '')  + '</td>' +
                        '<td class="text-center">'       + paidBadge              + '</td>' +
                        '<td class="text-center"><code>' + (it.paid_date  ?? '—') + '</code></td>' +
                        '<td class="text-center">'       + (it.payroll_id ?? '—') + '</td>' +
                        '</tr>';
                });
            } else {
                html = '<tr><td colspan="6" class="text-center text-muted">—</td></tr>';
            }
            $('#installmentsBody').html(html);
            $('#installmentsModal').modal('show');
        }).fail(function () { toastError(ts.error_occurred); });
    });

    // ============================================================
    // Approve
    // ============================================================
    $(document).on('click', '.btn-approve', function () {
        var id       = $(this).data('id');
        var branchId = $(this).data('branch-id')      || '';
        var empName  = $(this).data('employee-name')  || '';
        var amount   = $(this).data('amount')         || '';

        $('#approveExpenseModal').data('advance-id', id);
        $('#aeModal_empName').text(empName || '-');
        $('#aeModal_amount').text(amount   || '-');
        $('#aeModal_expense_type_id').val('');
        $('#aeModal_expense_disbursed_by').html('<option value="">{{ trans("hr.optional") ?? "اختياري" }}</option>');

        if (branchId) {
            $.get('{{ route("advances.employees.byBranch") }}', { branch_id: branchId }, function (res) {
                if (res.success) {
                    res.data.forEach(function (e) {
                        $('#aeModal_expense_disbursed_by').append('<option value="' + e.id + '">' + e.name + ' (' + e.code + ')</option>');
                    });
                }
            });
        }

        // ✅ استخدام getOrCreateInstance بدل new Modal في كل مرة
        bootstrap.Modal.getOrCreateInstance(document.getElementById('approveExpenseModal')).show();
    });

    // ============================================================
    // Confirm Approve with Expense
    // ============================================================
    $('#confirmApproveWithExpense').on('click', function () {
        var id = $('#approveExpenseModal').data('advance-id');
        if (!id) return;

        var expTypeId = $('#aeModal_expense_type_id').val();
        if (!expTypeId) {
            alert('{{ trans("hr.select_expense_type_required") ?? "يرجى اختيار نوع المصروف" }}');
            return;
        }

        var btn      = $(this);
        var origHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');

        $.ajax({
            url: '{{ url("advances") }}/' + id + '/approve-with-expense',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                expense_type_id:       expTypeId,
                expense_disbursed_by:  $('#aeModal_expense_disbursed_by').val() || ''
            },
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false).html(origHtml);
                bootstrap.Modal.getInstance(document.getElementById('approveExpenseModal')).hide();

                if (!res.success) { toastError(res.message || ts.error_occurred); return; }
                toastSuccess(res.message);

                var d   = res.data;
                var row = table.row($('#row-' + d.id).get(0));
                if (row.any()) {
                    var rowData = row.data();
                    rowData[6]  = '<div class="text-center">' + d.monthly_installment + '</div>';
                    rowData[7]  = '<div class="text-center">' + d.paid_amount         + '</div>';
                    rowData[8]  = '<div class="text-center">' + d.remaining_amount    + '</div>';
                    rowData[9]  = '<div class="text-center">' + statusBadge(d.status) + '</div>';
                    rowData[10] = '<div class="text-center">' + actionsHtml(d)        + '</div>';
                    row.data(rowData).draw(false);
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html(origHtml);
                toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : ts.error_occurred);
            }
        });
    });

    // ============================================================
    // Reject
    // ============================================================
    $(document).on('click', '.btn-reject', function () {
        var id = $(this).data('id');

        function doReject() {
            $.ajax({
                url: '{{ url("advances") }}/' + id + '/reject',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                dataType: 'json',
                success: function (res) {
                    if (!res.success) { toastError(res.message || ts.error_occurred); return; }
                    toastSuccess(res.message);
                    var d   = res.data;
                    var row = table.row($('#row-' + d.id).get(0));
                    if (row.any()) {
                        var rowData = row.data();
                        rowData[9]  = '<div class="text-center">' + statusBadge(d.status) + '</div>';
                        rowData[10] = '<div class="text-center">' + actionsHtml(d)        + '</div>';
                        row.data(rowData).draw(false);
                    }
                },
                error: function (xhr) {
                    toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : ts.error_occurred);
                }
            });
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '{{ trans("hr.reject_confirm_title") ?? "تأكيد الرفض" }}',
                text:  '{{ trans("hr.reject_confirm_msg")   ?? "هل تريد رفض هذه السلفة؟" }}',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ trans("hr.yes_reject") ?? "نعم رفض" }}',
                cancelButtonText:  '{{ trans("hr.cancel")    ?? "إلغاء" }}',
            }).then(function (r) { if (r.isConfirmed) doReject(); });
        } else {
            if (confirm('{{ trans("hr.reject_confirm_title") ?? "تأكيد الرفض" }}')) doReject();
        }
    });

    // ============================================================
    // Delete
    // ============================================================
    $(document).on('click', '.btn-delete', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');

        function doDelete() {
            $.ajax({
                url: '{{ url("advances") }}/' + id,
                method: 'POST',
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                dataType: 'json',
                success: function (res) {
                    if (!res.success) { toastError(res.message || ts.error_occurred); return; }
                    toastSuccess(res.message);
                    var row = table.row($('#row-' + id).get(0));
                    if (row.any()) { row.remove().draw(false); renumber(); }
                },
                error: function (xhr) {
                    toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : ts.error_occurred);
                }
            });
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '{{ trans("hr.delete_confirm_title") ?? "تأكيد الحذف" }}',
                html:  '{{ trans("hr.delete_confirm_msg")   ?? "هل تريد حذف" }} <strong>' + name + '</strong> ؟',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ trans("hr.yes_delete") ?? "نعم حذف" }}',
                cancelButtonText:  '{{ trans("hr.cancel")    ?? "إلغاء" }}',
            }).then(function (r) { if (r.isConfirmed) doDelete(); });
        } else {
            if (confirm('{{ trans("hr.delete_confirm_title") ?? "تأكيد الحذف" }}')) doDelete();
        }
    });

});
</script>

@endsection
