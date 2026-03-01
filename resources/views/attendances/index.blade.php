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
                    <small class="text-muted">
                        {{ trans('attendances.branch_hint') }}:
                        {{ $branchName ?: ($branchId ?: '-') }}
                    </small>
                </div>
            </div>

            <div class="card-body">

                {{-- ① أضفنا id="filterForm" + id على كل input --}}
                <form id="filterForm" method="GET" action="{{ route('attendances.index') }}" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ trans('attendances.date_from') }}</label>
                        <input type="date" class="form-control" name="date_from" id="date_from" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ trans('attendances.date_to') }}</label>
                        <input type="date" class="form-control" name="date_to" id="date_to" value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ trans('attendances.member') }}</label>
                        <input type="text" class="form-control" name="member" id="member"
                               value="{{ $memberSearch ?? '' }}"
                               placeholder="{{ trans('attendances.member_code') }} / {{ trans('members.name') ?? 'Name' }} / Phone"
                               autocomplete="off">
                    </div>
                    <div class="col-md-2" style="display:flex; align-items:end;">
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

                        {{-- Hidden input to ensure unchecked sends 0 --}}
                        <input type="hidden" name="deduct_pt" value="0">

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="deduct_pt" id="deduct_pt" value="1"
                                   {{ old('deduct_pt', 1) ? 'checked' : '' }}>
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
                    {{-- ② أضفنا عمود الضيوف في الـthead --}}
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
                                <th>{{ trans('attendances.guests') ?? 'Guests' }}</th>
                                <th>{{ trans('attendances.actions') }}</th>
                            </tr>
                        </thead>
                        {{-- ③ tbody فارغ — DataTables تملؤه عبر AJAX --}}
                        <tbody></tbody>
                    </table>
                </div>

                {{-- ④ حذفنا $rows->links() — DataTables تتولى الصفحات --}}

            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!(window.jQuery && $.fn.DataTable)) {
        return;
    }

    var attTable = $('#att_table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('attendances.datatable') }}",
            data: function (d) {
                d.date_from = document.getElementById('date_from') ? document.getElementById('date_from').value : '';
                d.date_to   = document.getElementById('date_to')   ? document.getElementById('date_to').value   : '';
                d.member    = document.getElementById('member')    ? document.getElementById('member').value    : '';
            }
        },
        columns: [
            { data: 'id',               name: 'id' },
            { data: 'attendance_date',  name: 'attendance_date' },
            { data: 'attendance_time',  name: 'attendance_time' },
            { data: 'member',           name: 'member',          orderable: false, searchable: true },
            { data: 'checkin_method',   name: 'checkin_method' },
            { data: 'base',             name: 'base',            orderable: false, searchable: false },
            { data: 'pt',               name: 'pt',              orderable: false, searchable: false },
            { data: 'status',           name: 'status',          orderable: true,  searchable: false },
            { data: 'guests',           name: 'guests',          orderable: false, searchable: false },
            { data: 'actions',          name: 'actions',         orderable: false, searchable: false },
        ],
        columnDefs: [
            { targets: [3, 5, 6, 7, 8, 9], render: function (data) { return data; } }
        ]
    });

    // ⑤ عند الضغط على فلتر يعيد تحميل الـDataTable بدل إرسال الفورم
    document.getElementById('filterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        attTable.ajax.reload();
    });
});
</script>
@endsection
