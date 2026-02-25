@extends('layouts.master_table')

@section('title')
    {{ trans('hr.shifts') ?? 'الورديات' }} | {{ trans('main_trans.title') }}
@endsection

@section('content')

<style>
    .day-badge { font-size: 11px; }
</style>


<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="ri-timer-flash-line me-1"></i>
                {{ trans('hr.shifts') ?? 'الورديات' }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('hr.index') }}">{{ trans('hr.title') ?? 'الموارد البشرية' }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('hr.shifts') ?? 'الورديات' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">

            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0 font">
                        <i class="ri-timer-flash-line me-1 text-secondary"></i>
                        {{ trans('hr.shifts_list') ?? 'قائمة الورديات' }}
                    </h5>

                    <button type="button" class="btn btn-primary btn-sm font" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                        <i class="ri-add-line align-bottom me-1"></i>
                        {{ trans('hr.add_shift') ?? 'إضافة وردية' }}
                    </button>
                </div>
            </div>
<div id="page-alerts" class="mb-3"></div>

            <div class="card-body">
                <table id="shiftsTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ trans('hr.shift_name') ?? 'اسم الوردية' }}</th>
                            <th>{{ trans('hr.work_time') ?? 'وقت العمل' }}</th>
                            <th>{{ trans('hr.grace_minutes') ?? 'سماح (دقيقة)' }}</th>
                            <th>{{ trans('hr.min_hours') ?? 'الحد الأدنى (ساعات)' }}</th>
                            <th>{{ trans('hr.working_days') ?? 'أيام العمل' }}</th>
                            <th>{{ trans('hr.status') ?? 'الحالة' }}</th>
                            <th>{{ trans('hr.create_date') ?? 'تاريخ الإنشاء' }}</th>
                            <th>{{ trans('hr.actions') ?? 'الإجراءات' }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php $i=0; @endphp
                        @foreach($shifts as $s)
                            @php
                                $i++;
                                $st = \Carbon\Carbon::parse($s->start_time)->format('H:i');
                                $en = \Carbon\Carbon::parse($s->end_time)->format('H:i');
                                $isOvernight = \Carbon\Carbon::createFromFormat('H:i', $en)->lessThanOrEqualTo(\Carbon\Carbon::createFromFormat('H:i', $st));
                                $durationMin = $isOvernight
                                    ? \Carbon\Carbon::createFromFormat('H:i', $en)->addDay()->diffInMinutes(\Carbon\Carbon::createFromFormat('H:i', $st))
                                    : \Carbon\Carbon::createFromFormat('H:i', $en)->diffInMinutes(\Carbon\Carbon::createFromFormat('H:i', $st));
                                $durationH = round($durationMin/60, 2);
                            @endphp

                            <tr id="row-{{ $s->id }}" data-id="{{ $s->id }}">
                                <td>{{ $i }}</td>
                                <td class="font fw-medium">{{ $s->name }}</td>
                                <td class="text-center">
                                    <div>
                                        <code>{{ $st }}</code>
                                        <span class="mx-1">→</span>
                                        <code>{{ $en }}</code>

                                        @if($isOvernight)
                                            <span class="badge bg-dark ms-2">{{ trans('hr.overnight') ?? 'ليلي' }}</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small mt-1">
                                        {{ trans('hr.shift_duration') ?? 'المدة' }}: <strong>{{ $durationH }}</strong> {{ trans('hr.hours') ?? 'ساعة' }}
                                    </div>
                                </td>
                                <td class="text-center">{{ (int)$s->grace_minutes }}</td>
                                <td class="text-center">
                                    <span class="badge bg-soft-info text-info">
                                        {{ trans('hr.halfday') ?? 'نصف يوم' }}: {{ (float)$s->min_half_hours }}
                                    </span>
                                    <span class="badge bg-soft-primary text-primary">
                                        {{ trans('hr.fullday') ?? 'يوم كامل' }}: {{ (float)$s->min_full_hours }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $days = [
                                            'sun' => trans('hr.sun') ?? 'أحد',
                                            'mon' => trans('hr.mon') ?? 'إثنين',
                                            'tue' => trans('hr.tue') ?? 'ثلاثاء',
                                            'wed' => trans('hr.wed') ?? 'أربعاء',
                                            'thu' => trans('hr.thu') ?? 'خميس',
                                            'fri' => trans('hr.fri') ?? 'جمعة',
                                            'sat' => trans('hr.sat') ?? 'سبت',
                                        ];
                                    @endphp

                                    @foreach($days as $k => $label)
                                        @if($s->$k)
                                            <span class="badge bg-success-subtle text-success day-badge">{{ $label }}</span>
                                        @else
                                            <span class="badge bg-light text-muted day-badge">{{ $label }}</span>
                                        @endif
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    @if($s->status)
                                        <span class="badge bg-success">{{ trans('hr.active') ?? 'نشط' }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ trans('hr.inactive') ?? 'غير نشط' }}</span>
                                    @endif
                                </td>
                                <td class="text-center"><code>{{ $s->created_at }}</code></td>
                                <td class="text-center">
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit" data-id="{{ $s->id }}">
                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                    {{ trans('hr.edit') ?? 'تعديل' }}
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger btn-delete" data-id="{{ $s->id }}" data-name="{{ $s->name }}">
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
    </div>
</div>

{{-- Add Modal --}}
<div id="addShiftModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font">{{ trans('hr.add_shift') ?? 'إضافة وردية' }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="addShiftForm" autocomplete="off">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font">{{ trans('hr.shift_name') ?? 'اسم الوردية' }}</label>
                            <input type="text" name="name" id="add_name" class="form-control font" required>
                            <div class="invalid-feedback" id="add_name_error"></div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label font">{{ trans('hr.start_time') ?? 'بداية الدوام' }}</label>
                            <input type="time" name="start_time" id="add_start_time" class="form-control font" required>
                            <div class="invalid-feedback" id="add_start_time_error"></div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label font">{{ trans('hr.end_time') ?? 'نهاية الدوام' }}</label>
                            <input type="time" name="end_time" id="add_end_time" class="form-control font" required>
                            <div class="invalid-feedback" id="add_end_time_error"></div>
                            <div class="text-muted small mt-1">
                                {{ trans('hr.night_shift_hint') ?? 'للوردية الليلية: اختر نهاية أقل من البداية (سيتم اعتبارها اليوم التالي).' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.grace_minutes') ?? 'سماح التأخير (دقيقة)' }}</label>
                            <input type="number" min="0" name="grace_minutes" id="add_grace_minutes" class="form-control font" value="0">
                            <div class="invalid-feedback" id="add_grace_minutes_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.min_half_hours') ?? 'حد نصف يوم (ساعات)' }}</label>
                            <input type="number" step="0.25" min="0" name="min_half_hours" id="add_min_half_hours" class="form-control font" value="4">
                            <div class="invalid-feedback" id="add_min_half_hours_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.min_full_hours') ?? 'حد يوم كامل (ساعات)' }}</label>
                            <input type="number" step="0.25" min="0" name="min_full_hours" id="add_min_full_hours" class="form-control font" value="8">
                            <div class="invalid-feedback" id="add_min_full_hours_error"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label font">{{ trans('hr.working_days') ?? 'أيام العمل' }}</label>
                            <div class="d-flex flex-wrap gap-3">
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input" type="checkbox" name="sun" value="1" checked> {{ trans('hr.sun') ?? 'أحد' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input" type="checkbox" name="mon" value="1" checked> {{ trans('hr.mon') ?? 'إثنين' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input" type="checkbox" name="tue" value="1" checked> {{ trans('hr.tue') ?? 'ثلاثاء' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input" type="checkbox" name="wed" value="1" checked> {{ trans('hr.wed') ?? 'أربعاء' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input" type="checkbox" name="thu" value="1" checked> {{ trans('hr.thu') ?? 'خميس' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input" type="checkbox" name="fri" value="1"> {{ trans('hr.fri') ?? 'جمعة' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input" type="checkbox" name="sat" value="1"> {{ trans('hr.sat') ?? 'سبت' }}</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.status') ?? 'الحالة' }}</label>
                            <select name="status" id="add_status" class="form-select font" required>
                                <option value="1">{{ trans('hr.active') ?? 'نشط' }}</option>
                                <option value="0">{{ trans('hr.inactive') ?? 'غير نشط' }}</option>
                            </select>
                            <div class="invalid-feedback" id="add_status_error"></div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') ?? 'إلغاء' }}
                        </button>
                        <button type="submit" class="btn btn-primary font" id="addSubmitBtn">
                            <i class="ri-save-line me-1"></i> {{ trans('hr.save') ?? 'حفظ' }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editShiftModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font">{{ trans('hr.edit_shift') ?? 'تعديل وردية' }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="editShiftForm" autocomplete="off">
                    @csrf
                    <input type="hidden" id="edit_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font">{{ trans('hr.shift_name') ?? 'اسم الوردية' }}</label>
                            <input type="text" name="name" id="edit_name" class="form-control font" required>
                            <div class="invalid-feedback" id="edit_name_error"></div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label font">{{ trans('hr.start_time') ?? 'بداية الدوام' }}</label>
                            <input type="time" name="start_time" id="edit_start_time" class="form-control font" required>
                            <div class="invalid-feedback" id="edit_start_time_error"></div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label font">{{ trans('hr.end_time') ?? 'نهاية الدوام' }}</label>
                            <input type="time" name="end_time" id="edit_end_time" class="form-control font" required>
                            <div class="invalid-feedback" id="edit_end_time_error"></div>
                            <div class="text-muted small mt-1">
                                {{ trans('hr.night_shift_hint') ?? 'للوردية الليلية: اختر نهاية أقل من البداية (سيتم اعتبارها اليوم التالي).' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.grace_minutes') ?? 'سماح التأخير (دقيقة)' }}</label>
                            <input type="number" min="0" name="grace_minutes" id="edit_grace_minutes" class="form-control font">
                            <div class="invalid-feedback" id="edit_grace_minutes_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.min_half_hours') ?? 'حد نصف يوم (ساعات)' }}</label>
                            <input type="number" step="0.25" min="0" name="min_half_hours" id="edit_min_half_hours" class="form-control font">
                            <div class="invalid-feedback" id="edit_min_half_hours_error"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.min_full_hours') ?? 'حد يوم كامل (ساعات)' }}</label>
                            <input type="number" step="0.25" min="0" name="min_full_hours" id="edit_min_full_hours" class="form-control font">
                            <div class="invalid-feedback" id="edit_min_full_hours_error"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label font">{{ trans('hr.working_days') ?? 'أيام العمل' }}</label>
                            <div class="d-flex flex-wrap gap-3">
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input edit-day" data-day="sun" type="checkbox" name="sun" value="1"> {{ trans('hr.sun') ?? 'أحد' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input edit-day" data-day="mon" type="checkbox" name="mon" value="1"> {{ trans('hr.mon') ?? 'إثنين' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input edit-day" data-day="tue" type="checkbox" name="tue" value="1"> {{ trans('hr.tue') ?? 'ثلاثاء' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input edit-day" data-day="wed" type="checkbox" name="wed" value="1"> {{ trans('hr.wed') ?? 'أربعاء' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input edit-day" data-day="thu" type="checkbox" name="thu" value="1"> {{ trans('hr.thu') ?? 'خميس' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input edit-day" data-day="fri" type="checkbox" name="fri" value="1"> {{ trans('hr.fri') ?? 'جمعة' }}</label>
                                <label class="form-check form-check-inline font mb-0"><input class="form-check-input edit-day" data-day="sat" type="checkbox" name="sat" value="1"> {{ trans('hr.sat') ?? 'سبت' }}</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font">{{ trans('hr.status') ?? 'الحالة' }}</label>
                            <select name="status" id="edit_status" class="form-select font" required>
                                <option value="1">{{ trans('hr.active') ?? 'نشط' }}</option>
                                <option value="0">{{ trans('hr.inactive') ?? 'غير نشط' }}</option>
                            </select>
                            <div class="invalid-feedback" id="edit_status_error"></div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') ?? 'إلغاء' }}
                        </button>
                        <button type="submit" class="btn btn-primary font" id="editSubmitBtn">
                            <i class="ri-save-line me-1"></i> {{ trans('hr.update') ?? 'تحديث' }}
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

    var table = $('#shiftsTable').DataTable({
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
        $('#add_status').select2({ width:'100%', dropdownParent: $('#addShiftModal') });
        $('#edit_status').select2({ width:'100%', dropdownParent: $('#editShiftModal') });
    }
    initSelect2();

    function clearErrors(prefix){
        ['name','start_time','end_time','grace_minutes','min_half_hours','min_full_hours','status'].forEach(function(f){
            $('#'+prefix+'_'+f).removeClass('is-invalid');
            $('#'+prefix+'_'+f+'_error').text('');
        });
    }
    function showErrors(prefix, errors){
        $.each(errors, function(field, messages){
            $('#'+prefix+'_'+field).addClass('is-invalid');
            $('#'+prefix+'_'+field+'_error').text(messages[0]);
        });
    }
    function setLoading(btn, loading, htmlNormal){
        if (loading) btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
        else btn.prop('disabled', false).html(htmlNormal);
    }

    function daysBadgesHtml(d){
        const labels = {
            sun: '{{ trans('hr.sun') ?? 'أحد' }}',
            mon: '{{ trans('hr.mon') ?? 'إثنين' }}',
            tue: '{{ trans('hr.tue') ?? 'ثلاثاء' }}',
            wed: '{{ trans('hr.wed') ?? 'أربعاء' }}',
            thu: '{{ trans('hr.thu') ?? 'خميس' }}',
            fri: '{{ trans('hr.fri') ?? 'جمعة' }}',
            sat: '{{ trans('hr.sat') ?? 'سبت' }}',
        };

        let html = '';
        Object.keys(labels).forEach(function(k){
            if (parseInt(d[k] || 0) === 1) html += '<span class="badge bg-success-subtle text-success day-badge me-1">'+labels[k]+'</span>';
            else html += '<span class="badge bg-light text-muted day-badge me-1">'+labels[k]+'</span>';
        });
        return html;
    }

    function statusBadgeHtml(status){
        if (parseInt(status) === 1) return '<span class="badge bg-success">{{ trans('hr.active') ?? 'نشط' }}</span>';
        return '<span class="badge bg-danger">{{ trans('hr.inactive') ?? 'غير نشط' }}</span>';
    }

    function actionsHtml(id, name){
        return '' +
            '<div class="dropdown d-inline-block">' +
                '<button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
                    '<i class="ri-more-fill align-middle"></i>' +
                '</button>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                    '<li><button type="button" class="dropdown-item btn-edit" data-id="'+id+'"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i>{{ trans('hr.edit') ?? 'تعديل' }}</button></li>' +
                    '<li><button type="button" class="dropdown-item text-danger btn-delete" data-id="'+id+'" data-name="'+name+'"><i class="ri-delete-bin-fill align-bottom me-2"></i>{{ trans('hr.delete') ?? 'حذف' }}</button></li>' +
                '</ul>' +
            '</div>';
    }

    function workTimeHtml(d){
        let overnightBadge = parseInt(d.is_overnight || 0) === 1 ? '<span class="badge bg-dark ms-2">{{ trans('hr.overnight') ?? 'ليلي' }}</span>' : '';
        let duration = (d.duration_hours ?? '');
        return '' +
            '<div class="text-center">' +
                '<div><code>'+d.start_time+'</code> <span class="mx-1">→</span> <code>'+d.end_time+'</code> '+overnightBadge+'</div>' +
                '<div class="text-muted small mt-1">{{ trans('hr.shift_duration') ?? 'المدة' }}: <strong>'+duration+'</strong> {{ trans('hr.hours') ?? 'ساعة' }}</div>' +
            '</div>';
    }

    // STORE
    $('#addShiftForm').on('submit', function(e){
        e.preventDefault();
        clearErrors('add');

        var btn = $('#addSubmitBtn');
        setLoading(btn, true, '<i class="ri-save-line me-1"></i> {{ trans('hr.save') ?? 'حفظ' }}');

        $.ajax({
            url: '{{ route('shifts.store') }}',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res){
                setLoading(btn, false, '<i class="ri-save-line me-1"></i> {{ trans('hr.save') ?? 'حفظ' }}');

                if(!res.success){
                    toastError(res.message || '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                    return;
                }

                $('#addShiftModal').modal('hide');
                toastSuccess(res.message);

                var d = res.data;

                var rowData = [
                    '',
                    '<span class="font fw-medium">'+d.name+'</span>',
                    workTimeHtml(d),
                    '<div class="text-center">'+d.grace_minutes+'</div>',
                    '<div class="text-center">' +
                        '<span class="badge bg-soft-info text-info me-1">{{ trans('hr.halfday') ?? 'نصف يوم' }}: '+d.min_half_hours+'</span>' +
                        '<span class="badge bg-soft-primary text-primary">{{ trans('hr.fullday') ?? 'يوم كامل' }}: '+d.min_full_hours+'</span>' +
                    '</div>',
                    '<div class="text-center">'+daysBadgesHtml(d)+'</div>',
                    '<div class="text-center">'+statusBadgeHtml(d.status)+'</div>',
                    '<div class="text-center"><code>'+(d.created_at ?? '')+'</code></div>',
                    '<div class="text-center">'+actionsHtml(d.id, d.name)+'</div>'
                ];

                var rowApi = table.row.add(rowData);
                table.draw(false);

                var node = rowApi.node();
                $(node).attr('id', 'row-'+d.id).attr('data-id', d.id);

                renumber();
                $('#addShiftForm')[0].reset();
                initSelect2();
            },
            error: function(xhr){
                setLoading(btn, false, '<i class="ri-save-line me-1"></i> {{ trans('hr.save') ?? 'حفظ' }}');
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) showErrors('add', xhr.responseJSON.errors);
                else toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
            }
        });
    });

    // OPEN EDIT
    $(document).on('click', '.btn-edit', function(){
        clearErrors('edit');
        var id = $(this).data('id');

        $.get('{{ url('shifts') }}/' + id, function(res){
            if(!res.success){
                toastError(res.message || '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                return;
            }

            var d = res.data;

            $('#edit_id').val(d.id);
            $('#edit_name').val(d.name);
            $('#edit_start_time').val(d.start_time);
            $('#edit_end_time').val(d.end_time);
            $('#edit_grace_minutes').val(d.grace_minutes);
            $('#edit_min_half_hours').val(d.min_half_hours);
            $('#edit_min_full_hours').val(d.min_full_hours);
            $('#edit_status').val(d.status).trigger('change');

            $('.edit-day').each(function(){
                var day = $(this).data('day');
                $(this).prop('checked', parseInt(d[day] || 0) === 1);
            });

            $('#editShiftModal').modal('show');
        }).fail(function(){
            toastError('{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
        });
    });

    // UPDATE
    $('#editShiftForm').on('submit', function(e){
        e.preventDefault();
        clearErrors('edit');

        var id = $('#edit_id').val();
        var btn = $('#editSubmitBtn');
        setLoading(btn, true, '<i class="ri-save-line me-1"></i> {{ trans('hr.update') ?? 'تحديث' }}');

        $.ajax({
            url: '{{ url('shifts') }}/' + id,
            method: 'POST',
            data: $(this).serialize() + '&_method=PUT',
            dataType: 'json',
            success: function(res){
                setLoading(btn, false, '<i class="ri-save-line me-1"></i> {{ trans('hr.update') ?? 'تحديث' }}');

                if(!res.success){
                    toastError(res.message || '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
                    return;
                }

                $('#editShiftModal').modal('hide');
                toastSuccess(res.message);

                var d = res.data;

                var $tr = $('#row-'+d.id);
                var row = table.row($tr.get(0));

                if(row.any()){
                    var rowData = row.data();
                    rowData[1] = '<span class="font fw-medium">'+d.name+'</span>';
                    rowData[2] = workTimeHtml(d);
                    rowData[3] = '<div class="text-center">'+d.grace_minutes+'</div>';
                    rowData[4] = '<div class="text-center">' +
                        '<span class="badge bg-soft-info text-info me-1">{{ trans('hr.halfday') ?? 'نصف يوم' }}: '+d.min_half_hours+'</span>' +
                        '<span class="badge bg-soft-primary text-primary">{{ trans('hr.fullday') ?? 'يوم كامل' }}: '+d.min_full_hours+'</span>' +
                    '</div>';
                    rowData[5] = '<div class="text-center">'+daysBadgesHtml(d)+'</div>';
                    rowData[6] = '<div class="text-center">'+statusBadgeHtml(d.status)+'</div>';
                    rowData[8] = '<div class="text-center">'+actionsHtml(d.id, d.name)+'</div>';

                    row.data(rowData);
                    table.draw(false);
                    renumber();
                }
            },
            error: function(xhr){
                setLoading(btn, false, '<i class="ri-save-line me-1"></i> {{ trans('hr.update') ?? 'تحديث' }}');
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) showErrors('edit', xhr.responseJSON.errors);
                else toastError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ trans('hr.error_occurred') ?? 'حدث خطأ' }}');
            }
        });
    });

    // DELETE (كما هو)
    $(document).on('click', '.btn-delete', function(){
        var id = $(this).data('id');
        var name = $(this).data('name');

        function doDelete(){
            $.ajax({
                url: '{{ url('shifts') }}/' + id,
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
