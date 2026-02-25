@extends('layouts.master_table')

@section('title')
    {{ trans('hr.process_logs') }} | {{ trans('main_trans.title') }}
@endsection

@section('content')


    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font">
                    <i class="ri-cpu-line me-1"></i>
                    {{ trans('hr.process_logs') }}
                </h4>
                <div class="page-title-right">
                    <a href="{{ route('attendance.index') }}" class="btn btn-light btn-sm font">
                        <i class="ri-arrow-left-line me-1"></i> {{ trans('hr.back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div id="page-alerts" class="mb-3"></div>

    {{-- GET: فلاتر فقط --}}
    <form id="processFilterForm" method="GET" action="{{ route('attendance.process.index') }}"
        class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label font">{{ trans('hr.branch') }}</label>
                    <select name="branch_id" id="process_branch_id" class="form-select font">
                        <option value="">{{ trans('hr.select_branch') }}</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (int) $branchId === (int) $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label font">{{ trans('hr.device') }}</label>
                    <select name="device_id" id="process_device_id" class="form-select font">
                        <option value="0">{{ trans('hr.all_devices') }}</option>
                        @foreach($devices as $d)
                            <option value="{{ $d->id }}" {{ (int) $deviceId === (int) $d->id ? 'selected' : '' }}>
                                {{ $d->name }} ({{ $d->serial_number }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label font">{{ trans('hr.date') }}</label>
                    <input type="date" name="date" id="process_date" class="form-control font" value="{{ $date }}">
                </div>
                <div class="text-muted small mt-1">
                    {{ trans('hr.night_shift_logs_hint') ?? 'ملاحظة: يعرض النظام بصمات هذا اليوم + اليوم التالي لدعم الورديات الليلية.' }}
                </div>

                <div class="col-md-12 d-flex justify-content-between mt-2">
                    <button type="submit" class="btn btn-primary font">
                        <i class="ri-filter-3-line me-1"></i> {{ trans('hr.filter') }}
                    </button>

                    <button type="button" class="btn btn-success font" id="btnRunProcess">
                        <i class="ri-play-circle-line me-1"></i> {{ trans('hr.run_processing') }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- POST: تشغيل المعالجة --}}
    <form id="runProcessForm" method="POST" action="{{ route('attendance.process.run') }}" style="display:none;">
        @csrf
        <input type="hidden" name="branch_id" id="run_branch_id" value="{{ $branchId }}">
        <input type="hidden" name="device_id" id="run_device_id" value="{{ $deviceId }}">
        <input type="hidden" name="date" id="run_date" value="{{ $date }}">
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0 font">{{ trans('hr.raw_logs') }}</h5>
        </div>
        <div class="card-body">
            <table id="logsTable" class="table table-bordered dt-responsive nowrap table-striped align-middle"
                style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ trans('hr.employee') }}</th>
                        <th>{{ trans('hr.device') }}</th>
                        <th>{{ trans('hr.punch_time') }}</th>
                        <th>{{ trans('hr.punch_type') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i = 0; @endphp
                    @foreach($logs as $l)
                        @php $i++; @endphp
                        <tr>
                            <td>{{ $i }}</td>
                            <td class="font">{{ $l->employee?->full_name ?? '-' }}</td>
                            <td class="font">{{ $l->device?->name ?? '-' }}</td>
                            <td class="text-center"><code>{{ $l->punch_time }}</code></td>
                            <td class="text-center">{{ $l->punch_type }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>


        $(document).ready(function () {
      if (!$('#process_branch_id').val()) {
    toast('error', '{{ trans('hr.select_branch') }}');
    return;
}
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

            function toast(type, message) {
                if (typeof Swal !== 'undefined' && Swal.mixin) {
                    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
                    Toast.fire({ icon: type, title: (type === 'success' ? 'Success ! ' : 'Error ! ') + message });
                    return;
                }
                var klass = (type === 'success') ? 'alert-success' : 'alert-danger';
                var title = (type === 'success') ? 'Success !' : 'Error !';
                $('#page-alerts').html(
                    '<div class="alert ' + klass + ' alert-dismissible fade show">' +
                    '<strong>' + title + '</strong> ' + message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
                );
            }

            $('#logsTable').DataTable({
                responsive: true,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' },
                pageLength: 25
            });

            if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
                $('#process_branch_id').select2({ width: '100%' });
                $('#process_device_id').select2({ width: '100%' });
            }

            $('#btnRunProcess').on('click', function (e) {
                e.preventDefault();

                $('#run_branch_id').val($('#process_branch_id').val());
                $('#run_device_id').val($('#process_device_id').val());
                $('#run_date').val($('#process_date').val());

                $.ajax({
                    url: $('#runProcessForm').attr('action'),
                    method: 'POST',
                    data: $('#runProcessForm').serialize(),
                    dataType: 'json',
                    success: function (res) {
                        if (res.success) {
                            toast('success', res.message + ' | created: ' + res.data.created + ', updated: ' + res.data.updated + ', logs: ' + res.data.logs);
                            setTimeout(function () { location.reload(); }, 700);
                        } else {
                            toast('error', res.message || '{{ trans('hr.error_occurred') }}');
                        }
                    },
                    error: function (xhr) {
                        // لو app.debug=true هتظهر رسالة أوضح من الكنترولر
                        let msg = '{{ trans('hr.error_occurred') }}';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        toast('error', msg);
                    }
                });

                return false;
            });

        });
    </script>

@endsection
