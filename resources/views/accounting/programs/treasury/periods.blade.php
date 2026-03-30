@extends('layouts.master_table')

@section('title')
    {{ trans('accounting.treasury_periods_title') }}
@endsection
@section('content')

<style>
    .period-row { cursor: pointer; transition: background .15s; }
    .period-row:hover { background:#f0f4ff !important; }
    .badge-open   { background:#d1fae5; color:#065f46; }
    .badge-closed { background:#fee2e2; color:#991b1b; }
    .stat-pill { display:inline-block; font-size:.75rem; font-weight:600;
                 padding:2px 8px; border-radius:20px; }
    .stat-in  { background:#d1fae5; color:#065f46; }
    .stat-out { background:#fee2e2; color:#991b1b; }
</style>


{{-- Page Header --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="ri-history-line me-1 text-primary"></i>
                {{ trans('accounting.treasury_periods_title') }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="{{ url('accounting') }}">{{ trans('accounting.accounting') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('treasury.index') }}">{{ trans('accounting.treasury') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ trans('accounting.treasury_periods_title') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('treasury.periods') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small font fw-semibold">{{ trans('accounting.branch') }}</label>
                <select name="branch_id" class="form-select font">
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $b->id == $selectedBranchId ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small font fw-semibold">{{ trans('accounting.treasury_period_status') }}</label>
                <select name="status" class="form-select font">
                    <option value="">{{ trans('accounting.all') }}</option>
                    <option value="open"   {{ request('status') === 'open'   ? 'selected' : '' }}>{{ trans('accounting.treasury_period_open') }}</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>{{ trans('accounting.treasury_period_closed') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small font fw-semibold">{{ trans('accounting.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small font fw-semibold">{{ trans('accounting.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary font flex-fill">
                    <i class="ri-filter-line me-1"></i>{{ trans('accounting.apply_filters') }}
                </button>
                <a href="{{ route('treasury.periods') }}" class="btn btn-light font">
                    <i class="ri-refresh-line"></i>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Periods Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 font fw-semibold">
            <i class="ri-calendar-line me-1 text-primary"></i>
            {{ trans('accounting.treasury_periods_list') }}
        </h6>
        <small class="text-muted">{{ $periods->total() }} {{ trans('accounting.treasury_period') }}</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ trans('accounting.treasury_period_name') }}</th>
                        <th>{{ trans('accounting.treasury_start_date') }}</th>
                        <th>{{ trans('accounting.treasury_end_date') }}</th>
                        <th>{{ trans('accounting.treasury_opening_balance') }}</th>
                        <th>{{ trans('accounting.treasury_total_in') }}</th>
                        <th>{{ trans('accounting.treasury_total_out') }}</th>
                        <th>{{ trans('accounting.treasury_closing_balance') }}</th>
                        <th>{{ trans('accounting.treasury_handed_over') }}</th>
                        <th>{{ trans('accounting.treasury_carried_forward') }}</th>
                        <th>{{ trans('accounting.status') }}</th>
                        <th>{{ trans('accounting.treasury_opened_by') }}</th>
                        <th>{{ trans('accounting.treasury_closed_by') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $period)
                        <tr class="period-row"
                            onclick="window.location='{{ route('treasury.period.show', $period->id) }}'">
                            <td class="text-muted small">{{ $period->id }}</td>
                            <td class="fw-semibold">{{ $period->name }}</td>
                            <td>{{ optional($period->start_date)->format('Y-m-d') }}</td>
                            <td>{{ optional($period->end_date)->format('Y-m-d') ?? '—' }}</td>
                            <td class="fw-semibold">{{ number_format($period->opening_balance, 2) }}</td>
                            <td><span class="stat-pill stat-in">{{ number_format($period->total_in, 2) }}</span></td>
                            <td><span class="stat-pill stat-out">{{ number_format($period->total_out, 2) }}</span></td>
                            <td class="fw-bold {{ ($period->closing_balance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $period->closing_balance !== null ? number_format($period->closing_balance, 2) : '—' }}
                            </td>
                            <td>{{ $period->handed_over !== null ? number_format($period->handed_over, 2) : '—' }}</td>
                            <td class="text-warning fw-semibold">
                                {{ $period->carried_forward !== null ? number_format($period->carried_forward, 2) : '—' }}
                            </td>
                            <td>
                                @if($period->status === 'open')
                                    <span class="badge badge-open px-2">{{ trans('accounting.treasury_period_open') }}</span>
                                @else
                                    <span class="badge badge-closed px-2">{{ trans('accounting.treasury_period_closed') }}</span>
                                @endif
                            </td>
                            <td class="small">{{ optional($period->openedBy)->name ?? '—' }}</td>
                            <td class="small">{{ optional($period->closedBy)->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-5 text-muted">
                                <i class="ri-inbox-line fs-36 d-block mb-2"></i>
                                {{ trans('accounting.treasury_no_periods') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($periods->hasPages())
    <div class="card-footer bg-white border-top py-2">
        {{ $periods->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@endsection
