@extends('layouts.master_table')

@section('title')
    {{ trans('attendances.attendances') }}
@stop

@section('content')
<div class="row">
    <div class="col-12">

        @if (Session::has('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (Session::has('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul style="margin:0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                    <h5 style="margin:0;">{{ trans('attendances.attendances') }}</h5>
                    <a href="{{ route('attendances.kiosk') }}" class="btn btn-sm btn-primary">
                        {{ trans('attendances.kiosk_page') }}
                    </a>
                </div>
                <div class="mt-2">
                    <small class="text-muted">{{ trans('attendances.branch_hint') }}: {{ $branchId ?: '-' }}</small>
                </div>
            </div>

            <div class="card-body">

                <form method="GET" action="{{ route('attendances.index') }}" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ trans('attendances.date_from') }}</label>
                        <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ trans('attendances.date_to') }}</label>
                        <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-3" style="display:flex; align-items:end;">
                        <button class="btn btn-secondary w-100" type="submit">{{ trans('attendances.filter') }}</button>
                    </div>
                </form>

                <hr>

                <form method="POST" action="{{ route('attendances.store') }}" class="row g-2 mb-4">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">{{ trans('attendances.member_code') }}</label>
                        <input type="text" name="member_code" class="form-control" required
                               value="{{ old('member_code') }}" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ trans('attendances.deduct_pt') }}</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="deduct_pt" id="deduct_pt"
                                   value="1" {{ old('deduct_pt', 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="deduct_pt">
                                {{ trans('attendances.deduct_pt_hint') }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-5" style="display:flex; align-items:end; gap:10px;">
                        <button class="btn btn-success" type="submit">{{ trans('attendances.manual_checkin') }}</button>
                        <small class="text-muted">{{ trans('attendances.global_scan_hint') }}</small>
                    </div>
                </form>

                <div class="table-responsive">
                    <table id="att_table" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ trans('attendances.date') }}</th>
                                <th>{{ trans('attendances.time') }}</th>
                                <th>{{ trans('attendances.member') }}</th>
                                <th>{{ trans('attendances.method') }}</th>
                                <th>{{ trans('attendances.base') }}</th>
                                <th>{{ trans('attendances.pt') }}</th>
                                <th>{{ trans('attendances.status') }}</th>
                                <th>{{ trans('attendances.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $row->attendance_date?->format('Y-m-d') }}</td>
                                    <td>{{ $row->attendance_time }}</td>
                                    <td>
                                        <div>{{ $row->member?->member_code ?? $row->member_id }}</div>
                                        <small class="text-muted">{{ $row->member?->full_name ?? '' }}</small>
                                    </td>
                                    <td>{{ $row->checkin_method }}</td>
                                    <td>
                                        {{ $row->base_sessions_before }} → {{ $row->base_sessions_after }}
                                    </td>
                                    <td>
                                        @if($row->pt_sessions_before !== null)
                                            {{ $row->pt_sessions_before }} → {{ $row->pt_sessions_after }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->is_cancelled)
                                            <span class="badge bg-danger">{{ trans('attendances.cancelled') }}</span>
                                        @else
                                            <span class="badge bg-success">{{ trans('attendances.active') }}</span>
                                        @endif
                                    </td>
                                    <td style="white-space:nowrap;">
                                        @if(!$row->is_cancelled)
                                            <form method="POST" action="{{ route('attendances.actions.cancel_pt', $row->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    {{ trans('attendances.cancel_pt') }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('attendances.actions.cancel', $row->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    {{ trans('attendances.cancel_attendance') }}
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-primary"
                                                    onclick="document.getElementById('guest_form_{{ $row->id }}').style.display='block'">
                                                {{ trans('attendances.add_guest') }}
                                            </button>

                                            <div id="guest_form_{{ $row->id }}" style="display:none; margin-top:6px;">
                                                <form method="POST" action="{{ route('attendances.actions.guests.store', $row->id) }}">
                                                    @csrf
                                                    <input type="text" name="guest_name" class="form-control form-control-sm mb-1"
                                                           placeholder="{{ trans('attendances.guest_name') }}">
                                                    <input type="text" name="guest_phone" class="form-control form-control-sm mb-1"
                                                           placeholder="{{ trans('attendances.guest_phone') }}">
                                                    <button class="btn btn-sm btn-success" type="submit">
                                                        {{ trans('attendances.save_guest') }}
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && $.fn.DataTable) {
        $('#att_table').DataTable({
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
});
</script>
@endsection
