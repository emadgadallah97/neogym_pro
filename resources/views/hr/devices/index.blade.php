@extends('layouts.master_table')

@section('title')
    {{ trans('hr.devices') }} | {{ trans('main_trans.title') }}
@endsection

@section('content')

<style>
    .device-card {
        transition: all 0.2s ease-in-out;
    }
    .device-card:hover {
        transform: translateY(-2px);
    }
</style>



{{-- ══ Page Title ══ --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="ri-fingerprint-2-line me-1"></i>
                {{ trans('hr.devices') }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('hr.index') }}">{{ trans('hr.title') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ trans('hr.devices') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>
{{-- Alerts fallback (لو لا يوجد toast library) --}}
<div id="page-alerts" class="mb-3"></div>
{{-- ══ Stats ══ --}}
<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="card device-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="avatar-sm flex-shrink-0">
                    <div class="avatar-title bg-soft-primary text-primary rounded-circle fs-22">
                        <i class="ri-router-line"></i>
                    </div>
                </div>
                <div>
                    <p class="text-muted mb-0 small font">{{ trans('hr.total_devices') }}</p>
                    <h4 class="mb-0 font" id="stat-total">{{ $totalDevices }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card device-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="avatar-sm flex-shrink-0">
                    <div class="avatar-title bg-soft-success text-success rounded-circle fs-22">
                        <i class="ri-checkbox-circle-line"></i>
                    </div>
                </div>
                <div>
                    <p class="text-muted mb-0 small font">{{ trans('hr.active_devices') }}</p>
                    <h4 class="mb-0 font" id="stat-active">{{ $activeDevices }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card device-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="avatar-sm flex-shrink-0">
                    <div class="avatar-title bg-soft-danger text-danger rounded-circle fs-22">
                        <i class="ri-close-circle-line"></i>
                    </div>
                </div>
                <div>
                    <p class="text-muted mb-0 small font">{{ trans('hr.inactive_devices') }}</p>
                    <h4 class="mb-0 font" id="stat-inactive">{{ $inactiveDevices }}</h4>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ══ Table Card ══ --}}
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">

            <div class="card-header d-flex align-items-center justify-content-between py-3">
                <h5 class="card-title mb-0 font">
                    <i class="ri-fingerprint-2-line me-1 text-secondary"></i>
                    {{ trans('hr.devices_list') }}
                </h5>
                <button type="button"
                        class="btn btn-primary btn-sm font"
                        data-bs-toggle="modal"
                        data-bs-target="#addDeviceModal">
                    <i class="ri-add-line align-bottom me-1"></i>
                    {{ trans('hr.add_device') }}
                </button>
            </div>

            <div class="card-body">
                <table id="devicesTable"
                       class="table table-bordered dt-responsive nowrap table-striped align-middle"
                       style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ trans('hr.device_name') }}</th>
                            <th>{{ trans('hr.branch') }}</th>
                            <th>{{ trans('hr.serial_number') }}</th>
                            <th>{{ trans('hr.ip_address') }}</th>
                            <th>{{ trans('hr.status') }}</th>
                            <th>{{ trans('hr.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i = 0; @endphp
                        @foreach($devices as $device)
                            @php $i++; @endphp
                            <tr id="row-{{ $device->id }}" data-id="{{ $device->id }}">
                                <td>{{ $i }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs flex-shrink-0">
                                            <div class="avatar-title bg-soft-secondary text-secondary rounded fs-14">
                                                <i class="ri-fingerprint-2-line"></i>
                                            </div>
                                        </div>
                                        <span class="font fw-medium device-name">{{ $device->name }}</span>
                                    </div>
                                </td>
                                <td class="font device-branch">{{ $device->branch?->name ?? '—' }}</td>
                                <td class="text-center">
                                    <code class="fs-12 device-serial">{{ $device->serial_number }}</code>
                                </td>
                                <td class="text-center">
                                    <code class="fs-12 device-ip">{{ $device->ip_address ?? '—' }}</code>
                                </td>
                                <td class="text-center device-status">
                                    @if($device->status === 'active')
                                        <span class="badge bg-success-subtle text-success fs-11">
                                            <i class="ri-checkbox-circle-line me-1"></i>{{ trans('hr.active') }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger fs-11">
                                            <i class="ri-close-circle-line me-1"></i>{{ trans('hr.inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-soft-secondary btn-sm"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button class="dropdown-item btn-edit"
                                                        data-id="{{ $device->id }}">
                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                    {{ trans('hr.edit') }}
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger btn-delete"
                                                        data-id="{{ $device->id }}"
                                                        data-name="{{ $device->name }}">
                                                    <i class="ri-delete-bin-fill align-bottom me-2"></i>
                                                    {{ trans('hr.delete') }}
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

{{-- ══ Add Modal ══ --}}
<div id="addDeviceModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">

            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font">
                    <i class="ri-add-circle-line me-1 text-primary"></i>
                    {{ trans('hr.add_device') }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="addDeviceForm" autocomplete="off">
                    @csrf
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.device_name') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="add_name"
                                   class="form-control font"
                                   placeholder="{{ trans('hr.device_name_placeholder') }}">
                            <div class="invalid-feedback" id="add_name_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.branch') }} <span class="text-danger">*</span>
                            </label>
                            <select name="branch_id" id="add_branch_id" class="form-select font">
                                <option value="">{{ trans('hr.select_branch') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="add_branch_id_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.serial_number') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="serial_number"
                                   id="add_serial_number"
                                   class="form-control"
                                   placeholder="SN-XXXX-XXXX">
                            <div class="invalid-feedback" id="add_serial_number_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.ip_address') }}
                                <small class="text-muted">({{ trans('hr.optional') }})</small>
                            </label>
                            <input type="text"
                                   name="ip_address"
                                   id="add_ip_address"
                                   class="form-control"
                                   placeholder="192.168.1.100">
                            <div class="invalid-feedback" id="add_ip_address_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.status') }} <span class="text-danger">*</span>
                            </label>
                            <select name="status" id="add_status" class="form-select font">
                                <option value="active">{{ trans('hr.active') }}</option>
                                <option value="inactive">{{ trans('hr.inactive') }}</option>
                            </select>
                            <div class="invalid-feedback" id="add_status_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.notes') }}
                                <small class="text-muted">({{ trans('hr.optional') }})</small>
                            </label>
                            <textarea name="notes"
                                      id="add_notes"
                                      class="form-control font"
                                      rows="2"
                                      placeholder="{{ trans('hr.notes_placeholder') }}"></textarea>
                        </div>

                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary font" id="addSubmitBtn">
                            <i class="ri-save-line me-1"></i> {{ trans('hr.save') }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

{{-- ══ Edit Modal ══ --}}
<div id="editDeviceModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">

            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font">
                    <i class="ri-edit-line me-1 text-primary"></i>
                    {{ trans('hr.edit_device') }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="editDeviceForm" autocomplete="off">
                    @csrf
                    <input type="hidden" id="edit_device_id">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.device_name') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" id="edit_name" class="form-control font">
                            <div class="invalid-feedback" id="edit_name_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.branch') }} <span class="text-danger">*</span>
                            </label>
                            <select name="branch_id" id="edit_branch_id" class="form-select font">
                                <option value="">{{ trans('hr.select_branch') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="edit_branch_id_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.serial_number') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="serial_number" id="edit_serial_number" class="form-control">
                            <div class="invalid-feedback" id="edit_serial_number_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.ip_address') }}
                                <small class="text-muted">({{ trans('hr.optional') }})</small>
                            </label>
                            <input type="text" name="ip_address" id="edit_ip_address" class="form-control">
                            <div class="invalid-feedback" id="edit_ip_address_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.status') }} <span class="text-danger">*</span>
                            </label>
                            <select name="status" id="edit_status" class="form-select font">
                                <option value="active">{{ trans('hr.active') }}</option>
                                <option value="inactive">{{ trans('hr.inactive') }}</option>
                            </select>
                            <div class="invalid-feedback" id="edit_status_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font">
                                {{ trans('hr.notes') }}
                                <small class="text-muted">({{ trans('hr.optional') }})</small>
                            </label>
                            <textarea name="notes" id="edit_notes" class="form-control font" rows="2"></textarea>
                        </div>

                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary font" id="editSubmitBtn">
                            <i class="ri-save-line me-1"></i> {{ trans('hr.update') }}
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    // ── System Toast (مطابق لرسائل النظام) ───────────────────
    function alertFallback(type, message) {
        var klass = (type === 'success') ? 'alert-success' : 'alert-danger';
        var title = (type === 'success') ? 'Success !' : 'Error !';

        var html  = '' +
            '<div class="alert ' + klass + ' alert-dismissible fade show" role="alert">' +
                '<strong>' + title + '</strong> ' + message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>';
        $('#page-alerts').html(html);
    }

    function toast(type, message) {
        // 1) SweetAlert2 Toast (أقرب لأسلوب النظام كـ Toast)
        if (typeof Swal !== 'undefined' && Swal.mixin) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });

            Toast.fire({
                icon: type,
                title: (type === 'success' ? 'Success ! ' : 'Error ! ') + message
            });
            return;
        }

        // 2) toastr (لو النظام مستخدمه)
        if (typeof toastr !== 'undefined') {
            if (type === 'success') toastr.success(message);
            else toastr.error(message);
            return;
        }

        // 3) fallback alert
        alertFallback(type, message);
    }

    function toastSuccess(msg){ toast('success', msg); }
    function toastError(msg){ toast('error', msg); }

    // ── DataTable Init ───────────────────────────────────────
    var table = $('#devicesTable').DataTable({
        responsive: true,
        language:   { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' },
        columnDefs: [{ orderable: false, targets: [-1] }],
        order:      [[0, 'asc']],
        pageLength: 15,
    });

    function renumber(){
        table.column(0, { search: 'applied', order: 'applied' }).nodes().each(function(cell, i){
            cell.innerHTML = i + 1;
        });
    }
    table.on('draw.dt', function(){ renumber(); });

    // ── Routes ───────────────────────────────────────────────
    var storeUrl = '{{ route('devices.store') }}';
    var baseUrl  = '{{ url('devices') }}/';

    // ── Helpers ──────────────────────────────────────────────
    var errorFields = ['name', 'branch_id', 'serial_number', 'ip_address', 'status'];

    function clearErrors(prefix) {
        errorFields.forEach(function(f) {
            $('#' + prefix + '_' + f).removeClass('is-invalid');
            $('#' + prefix + '_' + f + '_error').text('');
        });
    }

    function showErrors(prefix, errors) {
        $.each(errors, function(field, messages) {
            $('#' + prefix + '_' + field).addClass('is-invalid');
            $('#' + prefix + '_' + field + '_error').text(messages[0]);
        });
    }

    function setLoading(btn, loading, label, icon) {
        if (loading) {
            btn.prop('disabled', true)
               .html('<span class="spinner-border spinner-border-sm me-1"></span>');
        } else {
            btn.prop('disabled', false)
               .html('<i class="' + icon + ' me-1"></i> ' + label);
        }
    }

    function statusBadgeHtml(status){
        if (status === 'active') {
            return '<span class="badge bg-success-subtle text-success fs-11">' +
                '<i class="ri-checkbox-circle-line me-1"></i>{{ trans('hr.active') }}' +
            '</span>';
        }
        return '<span class="badge bg-danger-subtle text-danger fs-11">' +
            '<i class="ri-close-circle-line me-1"></i>{{ trans('hr.inactive') }}' +
        '</span>';
    }

    function actionsHtml(id, name){
        return '' +
            '<div class="dropdown d-inline-block">' +
                '<button class="btn btn-soft-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
                    '<i class="ri-more-fill align-middle"></i>' +
                '</button>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                    '<li>' +
                        '<button class="dropdown-item btn-edit" data-id="' + id + '">' +
                            '<i class="ri-pencil-fill align-bottom me-2 text-muted"></i>{{ trans('hr.edit') }}' +
                        '</button>' +
                    '</li>' +
                    '<li>' +
                        '<button class="dropdown-item text-danger btn-delete" data-id="' + id + '" data-name="' + name + '">' +
                            '<i class="ri-delete-bin-fill align-bottom me-2"></i>{{ trans('hr.delete') }}' +
                        '</button>' +
                    '</li>' +
                '</ul>' +
            '</div>';
    }

    function deviceNameHtml(name){
        return '' +
            '<div class="d-flex align-items-center gap-2">' +
                '<div class="avatar-xs flex-shrink-0">' +
                    '<div class="avatar-title bg-soft-secondary text-secondary rounded fs-14">' +
                        '<i class="ri-fingerprint-2-line"></i>' +
                    '</div>' +
                '</div>' +
                '<span class="font fw-medium device-name">' + name + '</span>' +
            '</div>';
    }

    function updateStatsFromTable() {
        var total = 0, active = 0, inactive = 0;
        table.rows().every(function(){
            total++;
            var statusCell = $(this.node()).find('.device-status');
            if (statusCell.find('.text-success').length > 0) active++;
            else inactive++;
        });
        $('#stat-total').text(total);
        $('#stat-active').text(active);
        $('#stat-inactive').text(inactive);
    }

    // ── Reset Add Modal ──────────────────────────────────────
    $('#addDeviceModal').on('hidden.bs.modal', function() {
        $('#addDeviceForm')[0].reset();
        clearErrors('add');
        setLoading($('#addSubmitBtn'), false, '{{ trans('hr.save') }}', 'ri-save-line');
    });

    // ─────────────────────────────────────────────────
    //  STORE
    // ─────────────────────────────────────────────────
    $('#addDeviceForm').on('submit', function(e) {
        e.preventDefault();
        clearErrors('add');

        var btn = $('#addSubmitBtn');
        setLoading(btn, true, '{{ trans('hr.save') }}', 'ri-save-line');

        $.ajax({
            url:      storeUrl,
            method:   'POST',
            data:     $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                setLoading(btn, false, '{{ trans('hr.save') }}', 'ri-save-line');

                if (!res || !res.success) {
                    toastError('{{ trans('hr.error_occurred') }}');
                    return;
                }

                $('#addDeviceModal').modal('hide');
                toastSuccess(res.message);

                var d = res.data;

                var rowData = [
                    '',
                    deviceNameHtml(d.name),
                    '<span class="font device-branch">' + (d.branch_name ?? '—') + '</span>',
                    '<div class="text-center"><code class="fs-12 device-serial">' + d.serial_number + '</code></div>',
                    '<div class="text-center"><code class="fs-12 device-ip">' + (d.ip_address ?? '—') + '</code></div>',
                    '<div class="text-center device-status">' + (d.status_label ?? statusBadgeHtml(d.status)) + '</div>',
                    actionsHtml(d.id, d.name)
                ];

                var rowApi = table.row.add(rowData);
                table.draw(false);

                var node = rowApi.node();
                $(node).attr('id', 'row-' + d.id).attr('data-id', d.id);

                renumber();
                updateStatsFromTable();
            },
            error: function(xhr) {
                setLoading(btn, false, '{{ trans('hr.save') }}', 'ri-save-line');

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showErrors('add', xhr.responseJSON.errors);
                } else {
                    toastError('{{ trans('hr.error_occurred') }}');
                }
            }
        });
    });

    // ─────────────────────────────────────────────────
    //  OPEN EDIT MODAL
    // ─────────────────────────────────────────────────
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        clearErrors('edit');

        $.ajax({
            url:      baseUrl + id,
            method:   'GET',
            dataType: 'json',
            success: function(res) {
                if (res && res.success) {
                    var d = res.data;
                    $('#edit_device_id').val(d.id);
                    $('#edit_name').val(d.name);
                    $('#edit_branch_id').val(d.branch_id);
                    $('#edit_serial_number').val(d.serial_number);
                    $('#edit_ip_address').val(d.ip_address || '');
                    $('#edit_status').val(d.status);
                    $('#edit_notes').val(d.notes || '');
                    $('#editDeviceModal').modal('show');
                } else {
                    toastError('{{ trans('hr.error_occurred') }}');
                }
            },
            error: function() {
                toastError('{{ trans('hr.error_occurred') }}');
            }
        });
    });

    // ── Reset Edit Modal ─────────────────────────────
    $('#editDeviceModal').on('hidden.bs.modal', function() {
        clearErrors('edit');
        setLoading($('#editSubmitBtn'), false, '{{ trans('hr.update') }}', 'ri-save-line');
    });

    // ─────────────────────────────────────────────────
    //  UPDATE
    // ─────────────────────────────────────────────────
    $('#editDeviceForm').on('submit', function(e) {
        e.preventDefault();
        clearErrors('edit');

        var id  = $('#edit_device_id').val();
        var btn = $('#editSubmitBtn');
        setLoading(btn, true, '{{ trans('hr.update') }}', 'ri-save-line');

        $.ajax({
            url:      baseUrl + id,
            method:   'POST',
            data:     $(this).serialize() + '&_method=PUT',
            dataType: 'json',
            success: function(res) {
                setLoading(btn, false, '{{ trans('hr.update') }}', 'ri-save-line');

                if (!res || !res.success) {
                    toastError('{{ trans('hr.error_occurred') }}');
                    return;
                }

                $('#editDeviceModal').modal('hide');
                toastSuccess(res.message);

                var d = res.data;

                var row = table.row('#row-' + d.id);
                if (row.any()) {
                    var rowData = row.data();
                    rowData[1] = deviceNameHtml(d.name);
                    rowData[2] = '<span class="font device-branch">' + (d.branch_name ?? '—') + '</span>';
                    rowData[3] = '<div class="text-center"><code class="fs-12 device-serial">' + d.serial_number + '</code></div>';
                    rowData[4] = '<div class="text-center"><code class="fs-12 device-ip">' + (d.ip_address ?? '—') + '</code></div>';
                    rowData[5] = '<div class="text-center device-status">' + (d.status_label ?? statusBadgeHtml(d.status)) + '</div>';
                    rowData[6] = actionsHtml(d.id, d.name);

                    row.data(rowData);
                    table.draw(false);

                    renumber();
                    updateStatsFromTable();
                }
            },
            error: function(xhr) {
                setLoading(btn, false, '{{ trans('hr.update') }}', 'ri-save-line');

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showErrors('edit', xhr.responseJSON.errors);
                } else {
                    toastError('{{ trans('hr.error_occurred') }}');
                }
            }
        });
    });

    // ─────────────────────────────────────────────────
    //  DELETE
    // ─────────────────────────────────────────────────
    $(document).on('click', '.btn-delete', function() {
        var id   = $(this).data('id');
        var name = $(this).data('name');

        function doDelete(){
            $.ajax({
                url:      baseUrl + id,
                method:   'POST',
                data:     { _method: 'DELETE' },
                dataType: 'json',
                success: function(res) {
                    if (res && res.success) {
                        toastSuccess(res.message);

                        var row = table.row('#row-' + id);
                        if (row.any()) {
                            row.remove();
                            table.draw(false);
                            renumber();
                            updateStatsFromTable();
                        }
                    } else {
                        toastError(res?.message ?? '{{ trans('hr.error_occurred') }}');
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : '{{ trans('hr.error_occurred') }}';
                    toastError(msg);
                }
            });
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title:              '{{ trans('hr.delete_confirm_title') }}',
                html:               '{{ trans('hr.delete_confirm_msg') }} <strong>' + name + '</strong>؟',
                icon:               'warning',
                showCancelButton:   true,
                confirmButtonColor: '#d33',
                cancelButtonColor:  '#6c757d',
                confirmButtonText:  '{{ trans('hr.yes_delete') }}',
                cancelButtonText:   '{{ trans('hr.cancel') }}',
            }).then(function(result) {
                if (result.isConfirmed) doDelete();
            });
        } else {
            if (confirm('{{ trans('hr.delete_confirm_title') }}: ' + name + ' ?')) {
                doDelete();
            }
        }
    });

});
</script>

@endsection
