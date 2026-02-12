@extends('layouts.master_table')
@section('title')
{{ trans('accounting.commissions') }}
@stop

@section('content')

<style>
    .kpi-card{
        border: 0;
        box-shadow: 0 1px 2px rgba(16,24,40,.06), 0 1px 3px rgba(16,24,40,.1);
        overflow: hidden;
        height: 100%;
    }
    .kpi-card .kpi-icon{
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        font-size: 22px;
    }
    .kpi-card .kpi-value{
        font-weight: 700;
        letter-spacing: .2px;
    }
    .kpi-card .kpi-label{
        font-size: 12px;
        letter-spacing: .6px;
    }
    .kpi-card .kpi-foot{
        border-top: 1px dashed rgba(0,0,0,.08);
        padding: .6rem 1rem;
        background: rgba(0,0,0,.015);
    }

    .section-title-row{
        gap: .75rem;
    }
    .section-title-row .section-subtitle{
        margin: 0;
        color: #6c757d;
        font-size: 13px;
    }

    .preview-summary-card{
        border: 0;
        box-shadow: 0 1px 2px rgba(16,24,40,.06), 0 1px 3px rgba(16,24,40,.08);
        height: 100%;
    }
    .preview-summary-card .icon{
        width: 40px;
        height: 40px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .preview-actions-bar{
        position: sticky;
        top: 0;
        z-index: 2;
        background: #fff;
        border: 1px solid rgba(0,0,0,.08);
        border-radius: .5rem;
        padding: .75rem;
    }

    #previewTable tbody tr.is-excluded{
        background: rgba(255,193,7,.15) !important;
    }

    .table td, .table th{
        vertical-align: middle;
    }
    .badge-status{
        font-weight: 600;
        letter-spacing: .2px;
    }

    /* Settlements filters */
    .settlements-filters{
        border: 1px solid rgba(0,0,0,.08);
        border-radius: .5rem;
        padding: .75rem;
        background: rgba(0,0,0,.015);
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('accounting.commissions') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('accounting.accounting') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('accounting.commissions') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- KPIs --}}
<div class="row">
    <div class="col-12">
        <div class="card border-0">
            <div class="card-body pb-0">
                <div class="d-flex align-items-start justify-content-between flex-wrap section-title-row">
                    <div>
                        <h5 class="card-title mb-1">
                            <i class="ri-pie-chart-2-line align-bottom me-1"></i>
                            {{ trans('accounting.commissions_kpis') }}
                        </h5>
                        <p class="section-subtitle">{{ trans('accounting.quick_filters') }}</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a class="btn btn-sm btn-soft-warning" href="{{ route('commissions.index') }}">
                            <i class="ri-refresh-line align-bottom me-1"></i> {{ trans('accounting.all') }}
                        </a>
                        <a class="btn btn-sm btn-soft-secondary" href="{{ route('commissions.index', array_merge(request()->all(), ['status' => 'draft'])) }}">
                            <i class="ri-draft-line align-bottom me-1"></i> {{ trans('accounting.draft') }}
                        </a>
                        <a class="btn btn-sm btn-soft-success" href="{{ route('commissions.index', array_merge(request()->all(), ['status' => 'paid'])) }}">
                            <i class="ri-checkbox-circle-line align-bottom me-1"></i> {{ trans('accounting.paid') }}
                        </a>
                    </div>
                </div>

                <div class="row mt-3 g-3">
                    <div class="col-md-6 col-xl-3">
                        <div class="card kpi-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <div class="text-muted text-uppercase kpi-label mb-1">{{ trans('accounting.total_records') }}</div>
                                        <div class="h4 mb-0 kpi-value">{{ $kpiTotal }}</div>
                                        <div class="text-muted small mt-1">{{ trans('accounting.commissions_settlements_list') }}</div>
                                    </div>
                                    <div class="kpi-icon bg-primary-subtle text-primary">
                                        <i class="ri-file-list-3-line"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="kpi-foot">
                                <span class="text-muted small">
                                    <i class="ri-information-line align-bottom me-1"></i> {{ trans('accounting.quick_filters') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card kpi-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <div class="text-muted text-uppercase kpi-label mb-1">{{ trans('accounting.commissions_draft') }}</div>
                                        <div class="h4 mb-0 kpi-value">{{ $kpiDraft }}</div>
                                        <div class="text-muted small mt-1">{{ trans('accounting.draft') }}</div>
                                    </div>
                                    <div class="kpi-icon bg-warning-subtle text-warning">
                                        <i class="ri-draft-line"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="kpi-foot">
                                <span class="text-muted small">
                                    <i class="ri-time-line align-bottom me-1"></i> {{ trans('accounting.save_draft') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card kpi-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <div class="text-muted text-uppercase kpi-label mb-1">{{ trans('accounting.commissions_paid') }}</div>
                                        <div class="h4 mb-0 kpi-value">{{ $kpiPaid }}</div>
                                        <div class="text-muted small mt-1">{{ trans('accounting.paid') }}</div>
                                    </div>
                                    <div class="kpi-icon bg-success-subtle text-success">
                                        <i class="ri-checkbox-circle-line"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="kpi-foot">
                                <span class="text-muted small">
                                    <i class="ri-shield-check-line align-bottom me-1"></i> {{ trans('accounting.commissions_paid') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card kpi-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <div class="text-muted text-uppercase kpi-label mb-1">{{ trans('accounting.commissions_paid_amount') }}</div>
                                        <div class="h4 mb-0 kpi-value">{{ number_format((float)$kpiPaidAmount, 2) }}</div>
                                        <div class="text-muted small mt-1">{{ trans('accounting.total_amount') }}</div>
                                    </div>
                                    <div class="kpi-icon bg-info-subtle text-info">
                                        <i class="ri-money-dollar-circle-line"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="kpi-foot">
                                <span class="text-muted small">
                                    <i class="ri-bar-chart-2-line align-bottom me-1"></i> {{ trans('accounting.commissions_kpis') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="mt-4 mb-0">
            </div>

            <div class="card-body pt-3">
                {{-- Preview form (GET) --}}
                <form method="get" action="{{ route('commissions.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label mb-1">
                                <i class="ri-calendar-2-line align-bottom me-1"></i> {{ trans('accounting.date_from') }}
                            </label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label mb-1">
                                <i class="ri-calendar-check-line align-bottom me-1"></i> {{ trans('accounting.date_to') }}
                            </label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label mb-1">
                                <i class="ri-user-star-line align-bottom me-1"></i> {{ trans('accounting.sales_employee') }}
                            </label>
                            <select name="sales_employee_id" class="form-select select2" data-placeholder="{{ trans('accounting.all') }}">
                                <option value="">{{ trans('accounting.all') }}</option>
                                @foreach($SalesEmployeesList as $e)
                                    @php
                                        $ename = $e->fullname ?? trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? ''));
                                        $ename = $ename ?: ('#' . $e->id);
                                    @endphp
                                    <option value="{{ $e->id }}" {{ (string)request('sales_employee_id') === (string)$e->id ? 'selected' : '' }}>
                                        {{ ($e->code ? ($e->code.' - ') : '') . $ename }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 text-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line align-bottom me-1"></i> {{ trans('accounting.commissions_preview') }}
                            </button>
                        </div>
                    </div>
                </form>

                @if($preview)
                    <hr>

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1">
                                <i class="ri-eye-line align-bottom me-1"></i>
                                {{ trans('accounting.commissions_preview_result') }}
                            </h5>
                            <p class="text-muted mb-0">
                                <span class="me-2"><i class="ri-calendar-2-line align-bottom me-1"></i>{{ trans('accounting.date_from') }}: <span class="fw-semibold">{{ $preview['date_from'] }}</span></span>
                                <span><i class="ri-calendar-check-line align-bottom me-1"></i>{{ trans('accounting.date_to') }}: <span class="fw-semibold">{{ $preview['date_to'] }}</span></span>
                            </p>
                        </div>
                    </div>

                    @php
                        $previewAllCount = (int)($previewTotals['all_count'] ?? 0);
                        $previewAllAmount = (float)($previewTotals['all_amount'] ?? 0);
                    @endphp

                    <div class="row mt-3 g-3">
                        <div class="col-md-4">
                            <div class="card preview-summary-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <div class="text-muted">{{ trans('accounting.total_records') }}</div>
                                            <div class="h4 mb-0 fw-bold" id="pv_all_count">{{ $previewAllCount }}</div>
                                            <div class="text-muted small mt-1">{{ trans('accounting.total_records') }}</div>
                                        </div>
                                        <div class="icon bg-primary-subtle text-primary">
                                            <i class="ri-file-list-3-line"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3 d-flex gap-3 small">
                                        <span class="text-muted"><i class="ri-check-line align-bottom me-1"></i>{{ trans('accounting.items_count') }}: <b id="pv_included_count">{{ $previewAllCount }}</b></span>
                                        <span class="text-muted"><i class="ri-forbid-2-line align-bottom me-1"></i>{{ trans('accounting.exclude') }}: <b id="pv_excluded_count">0</b></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card preview-summary-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <div class="text-muted">{{ trans('accounting.total_amount') }}</div>
                                            <div class="h4 mb-0 fw-bold" id="pv_all_amount">{{ number_format($previewAllAmount, 2) }}</div>
                                            <div class="text-muted small mt-1">{{ trans('accounting.commission_amount') }}</div>
                                        </div>
                                        <div class="icon bg-info-subtle text-info">
                                            <i class="ri-money-dollar-circle-line"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3 d-flex gap-3 small">
                                        <span class="text-muted"><i class="ri-check-double-line align-bottom me-1"></i>{{ trans('accounting.total_amount') }}: <b id="pv_included_amount">{{ number_format($previewAllAmount, 2) }}</b></span>
                                        <span class="text-muted"><i class="ri-subtract-line align-bottom me-1"></i>{{ trans('accounting.exclude') }}: <b id="pv_excluded_amount">0.00</b></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card preview-summary-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <div class="text-muted">{{ trans('accounting.note') }}</div>
                                            <div class="fw-semibold">{{ trans('accounting.commissions_exclude_note') }}</div>
                                            <div class="text-muted small mt-1">{{ trans('accounting.optional') }}</div>
                                        </div>
                                        <div class="icon bg-warning-subtle text-warning">
                                            <i class="ri-information-line"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Save settlement (POST) --}}
                    <form method="post" action="{{ route('commissions.store') }}" class="mt-3">
                        @csrf

                        <input type="hidden" name="date_from" value="{{ $preview['date_from'] }}">
                        <input type="hidden" name="date_to" value="{{ $preview['date_to'] }}">
                        <input type="hidden" name="sales_employee_id" value="{{ $preview['sales_employee_id'] }}">

                        <div class="preview-actions-bar mb-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label mb-1">
                                        <i class="ri-sticky-note-line align-bottom me-1"></i> {{ trans('accounting.notes') }}
                                    </label>
                                    <input type="text" name="notes" class="form-control" placeholder="{{ trans('accounting.commissions_notes_hint') }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="action" value="save_draft" class="btn btn-soft-secondary w-100">
                                        <i class="ri-save-3-line align-bottom me-1"></i> {{ trans('accounting.save_draft') }}
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="action" value="pay_now" class="btn btn-success w-100">
                                        <i class="ri-check-double-line align-bottom me-1"></i> {{ trans('accounting.pay_now') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="previewTable" class="table table-bordered dt-responsive nowrap table-striped table-hover align-middle" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:110px;">{{ trans('accounting.exclude') }}</th>
                                        <th>{{ trans('accounting.subscription_id') }}</th>
                                        <th>{{ trans('accounting.member') }}</th>
                                        <th>{{ trans('accounting.branch') }}</th>
                                        <th>{{ trans('accounting.sales_employee') }}</th>
                                        <th>{{ trans('accounting.create_date') }}</th>
                                        <th>{{ trans('accounting.commission_amount') }}</th>
                                        <th>{{ trans('accounting.exclude_reason') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previewRows as $s)
                                        @php
                                            $memberName = $s->member->fullname ?? ($s->member->full_name ?? ($s->member->name ?? '-'));
                                            $branchName = method_exists($s->branch, 'getTranslation') ? $s->branch->getTranslation('name', app()->getLocale()) : ($s->branch->name ?? '-');
                                            $empName = $s->salesEmployee->fullname ?? trim(($s->salesEmployee->first_name ?? '').' '.($s->salesEmployee->last_name ?? ''));
                                            $empName = $empName ?: '-';
                                            $commission = (float)($s->commission_amount ?? 0);
                                        @endphp
                                        <tr data-subscription-id="{{ (int)$s->id }}" data-commission="{{ $commission }}">
                                            <td class="text-center">
                                                <div class="form-check form-switch d-inline-flex align-items-center gap-2 m-0">
                                                    <input class="form-check-input js-exclude" type="checkbox" name="exclude_subscription_ids[]" value="{{ $s->id }}" id="ex_{{ $s->id }}">
                                                    <label class="form-check-label small text-muted" for="ex_{{ $s->id }}">{{ trans('accounting.exclude') }}</label>
                                                </div>
                                            </td>
                                            <td class="fw-semibold">{{ $s->id }}</td>
                                            <td>{{ $memberName }}</td>
                                            <td>{{ $branchName }}</td>
                                            <td>{{ $empName }}</td>
                                            <td class="text-muted">{{ $s->created_at }}</td>
                                            <td class="fw-semibold js-commission-cell">{{ number_format($commission, 2) }}</td>
                                            <td style="min-width: 240px;">
                                                <input type="text"
                                                       name="exclude_reasons[{{ $s->id }}]"
                                                       class="form-control form-control-sm js-reason"
                                                       placeholder="{{ trans('accounting.optional') }}"
                                                       disabled>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Settlements list --}}
<div class="row">
    <div class="col-12">
        <div class="card border-0">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0">
                    <i class="ri-list-check-2 align-bottom me-1"></i>
                    {{ trans('accounting.commissions_settlements_list') }}
                </h5>
                <div class="text-muted small">
                    <i class="ri-information-line align-bottom me-1"></i>
                    {{ trans('accounting.total_records') }}: <b>{{ $settlements->total() ?? $settlements->count() }}</b>
                </div>
            </div>

            <div class="card-body">

                {{-- Filters (GET) --}}
                <form method="get" action="{{ route('commissions.index') }}" class="settlements-filters mb-3">
                    {{-- Preserve preview query (optional) --}}
                    @if(request()->filled('date_from')) <input type="hidden" name="date_from" value="{{ request('date_from') }}"> @endif
                    @if(request()->filled('date_to')) <input type="hidden" name="date_to" value="{{ request('date_to') }}"> @endif
                    @if(request()->filled('sales_employee_id')) <input type="hidden" name="sales_employee_id" value="{{ request('sales_employee_id') }}"> @endif
                    @if(request()->filled('preview')) <input type="hidden" name="preview" value="{{ request('preview') }}"> @endif

                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label mb-1">
                                <i class="ri-search-line align-bottom me-1"></i> {{ trans('accounting.settlement_search') }}
                            </label>
                            <input type="text"
                                   id="st_q"
                                   name="st_q"
                                   class="form-control"
                                   value="{{ request('st_q') }}"
                                   placeholder="#ID / {{ trans('accounting.sales_employee') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1">
                                <i class="ri-filter-3-line align-bottom me-1"></i> {{ trans('accounting.status') }}
                            </label>
                            <select id="st_status" name="st_status" class="form-select">
                                <option value="">{{ trans('accounting.all') }}</option>
                                <option value="draft" {{ request('st_status') === 'draft' ? 'selected' : '' }}>{{ trans('accounting.draft') }}</option>
                                <option value="paid" {{ request('st_status') === 'paid' ? 'selected' : '' }}>{{ trans('accounting.paid') }}</option>
                                <option value="cancelled" {{ request('st_status') === 'cancelled' ? 'selected' : '' }}>{{ trans('accounting.cancelled') }}</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1">
                                <i class="ri-calendar-2-line align-bottom me-1"></i> {{ trans('accounting.date_from') }}
                            </label>
                            <input type="date" id="st_date_from" name="st_date_from" class="form-control" value="{{ request('st_date_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1">
                                <i class="ri-calendar-check-line align-bottom me-1"></i> {{ trans('accounting.date_to') }}
                            </label>
                            <input type="date" id="st_date_to" name="st_date_to" class="form-control" value="{{ request('st_date_to') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label mb-1">
                                <i class="ri-user-star-line align-bottom me-1"></i> {{ trans('accounting.sales_employee') }}
                            </label>
                            <select id="st_sales_employee_id" name="st_sales_employee_id" class="form-select select2" data-placeholder="{{ trans('accounting.all') }}">
                                <option value="">{{ trans('accounting.all') }}</option>
                                @foreach($SalesEmployeesList as $e)
                                    @php
                                        $ename = $e->fullname ?? trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? ''));
                                        $ename = $ename ?: ('#' . $e->id);
                                    @endphp
                                    <option value="{{ $e->id }}" {{ (string)request('st_sales_employee_id') === (string)$e->id ? 'selected' : '' }}>
                                        {{ ($e->code ? ($e->code.' - ') : '') . $ename }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1">
                                <i class="ri-money-dollar-circle-line align-bottom me-1"></i> {{ trans('accounting.amount_from') }}
                            </label>
                            <input type="number" step="0.01" min="0" id="st_amount_from" name="st_amount_from" class="form-control" value="{{ request('st_amount_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1">
                                <i class="ri-money-dollar-circle-line align-bottom me-1"></i> {{ trans('accounting.amount_to') }}
                            </label>
                            <input type="number" step="0.01" min="0" id="st_amount_to" name="st_amount_to" class="form-control" value="{{ request('st_amount_to') }}">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-2-line align-bottom me-1"></i> {{ trans('accounting.apply_filters') }}
                            </button>
                        </div>

                        <div class="col-md-2">
                            <a href="{{ route('commissions.index') }}" class="btn btn-soft-secondary w-100">
                                <i class="ri-refresh-line align-bottom me-1"></i> {{ trans('accounting.reset_filters') }}
                            </a>
                        </div>

                        <div class="col-12">
                            <div class="text-muted small">
                                <i class="ri-information-line align-bottom me-1"></i>
                                {{ trans('accounting.filters_note_datatable_page') }}
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table id="settlementsTable" class="table table-bordered dt-responsive nowrap table-striped table-hover align-middle" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;">#</th>
                                <th style="width:90px;">{{ trans('accounting.id') }}</th>
                                <th>{{ trans('accounting.date_from') }}</th>
                                <th>{{ trans('accounting.date_to') }}</th>
                                <th>{{ trans('accounting.sales_employee') }}</th>
                                <th style="width:120px;">{{ trans('accounting.status') }}</th>
                                <th style="width:140px;">{{ trans('accounting.total_amount') }}</th>
                                <th style="width:140px;">{{ trans('accounting.items_count') }}</th>
                                <th style="width:120px;">{{ trans('accounting.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i=0; @endphp
                            @foreach($settlements as $st)
                                @php
                                    $i++;
                                    $emp = $st->salesEmployee ? ($st->salesEmployee->fullname ?? trim(($st->salesEmployee->first_name ?? '').' '.($st->salesEmployee->last_name ?? ''))) : null;
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $i }}</td>
                                    <td class="fw-semibold">{{ $st->id }}</td>
                                    <td>
                                        <span class="d-none js-date-from">{{ optional($st->date_from)->format('Y-m-d') }}</span>
                                        {{ optional($st->date_from)->format('Y-m-d') }}
                                    </td>
                                    <td>
                                        <span class="d-none js-date-to">{{ optional($st->date_to)->format('Y-m-d') }}</span>
                                        {{ optional($st->date_to)->format('Y-m-d') }}
                                    </td>
                                    <td>
                                        <span class="d-none js-emp-id">{{ (int)$st->sales_employee_id }}</span>
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold">{{ $emp ?: trans('accounting.all_employees') }}</span>
                                            @if($st->sales_employee_id)
                                                <span class="text-muted small">#{{ $st->sales_employee_id }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-none js-status">{{ (string)$st->status }}</span>
                                        @if($st->status === 'paid')
                                            <span class="badge bg-success badge-status">
                                                <i class="ri-checkbox-circle-line align-bottom me-1"></i>{{ trans('accounting.paid') }}
                                            </span>
                                        @elseif($st->status === 'draft')
                                            <span class="badge bg-warning text-dark badge-status">
                                                <i class="ri-draft-line align-bottom me-1"></i>{{ trans('accounting.draft') }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary badge-status">
                                                <i class="ri-close-circle-line align-bottom me-1"></i>{{ trans('accounting.cancelled') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">
                                        <span class="d-none js-total">{{ (float)$st->total_commission_amount }}</span>
                                        {{ number_format((float)$st->total_commission_amount, 2) }}
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ (int)$st->items_count }}</span>
                                        <span class="text-muted">/ {{ (int)$st->all_items_count }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('commissions.show', $st->id) }}" class="btn btn-sm btn-soft-primary">
                                            <i class="ri-eye-line align-bottom me-1"></i> {{ trans('accounting.view') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $settlements->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ === 'undefined') return;

    var isRtl = $('html').attr('dir') === 'rtl';
    var locale = ($('html').attr('lang') || '').toLowerCase();

    function initSelect2($el){
        if (!$.fn || !$.fn.select2) return;
        if (!$el || !$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) return;

        var placeholder = $el.data('placeholder') || '{{ trans('accounting.choose') }}';

        $el.select2({
            width: '100%',
            placeholder: placeholder,
            allowClear: true,
            dir: isRtl ? 'rtl' : 'ltr',
            language: (locale === 'ar' ? 'ar' : undefined)
        });
    }

    $('select.select2').each(function(){ initSelect2($(this)); });

    // DataTables
    var settlementsDt = null;
    if ($.fn && $.fn.DataTable) {

        // Custom range filters (amount/date) for settlements
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
            if (!settings || settings.nTable?.id !== 'settlementsTable') return true;

            var tr = settings.aoData[dataIndex]?.nTr;
            if (!tr) return true;

            var qFrom = (document.getElementById('st_date_from')?.value || '').trim();
            var qTo = (document.getElementById('st_date_to')?.value || '').trim();
            var aFrom = (document.getElementById('st_amount_from')?.value || '').trim();
            var aTo = (document.getElementById('st_amount_to')?.value || '').trim();

            var rowDateFrom = (tr.querySelector('.js-date-from')?.textContent || '').trim();
            var rowDateTo = (tr.querySelector('.js-date-to')?.textContent || '').trim();

            var rowTotalRaw = (tr.querySelector('.js-total')?.textContent || '').toString().trim();
            var rowTotal = parseFloat(rowTotalRaw || '0') || 0;

            // Date filter (Y-m-d string compare works)
            if (qFrom && rowDateFrom && rowDateFrom < qFrom) return false;
            if (qTo && rowDateTo && rowDateTo > qTo) return false;

            // Amount filter
            if (aFrom !== '' && rowTotal < (parseFloat(aFrom) || 0)) return false;
            if (aTo !== '' && rowTotal > (parseFloat(aTo) || 0)) return false;

            return true;
        });

        settlementsDt = $('#settlementsTable').DataTable({
            pageLength: 25,
            order: [[1, 'desc']]
        });

        if (document.getElementById('previewTable')) {
            $('#previewTable').DataTable({
                pageLength: 25,
                order: [[5, 'asc']]
            });
        }
    }

    // Bind settlements filters to DataTables (current page)
    function applySettlementsFilters(){
        if (!settlementsDt) return;

        var q = (document.getElementById('st_q')?.value || '').trim();
        var status = (document.getElementById('st_status')?.value || '').trim();
        var empId = (document.getElementById('st_sales_employee_id')?.value || '').trim();

        // Global search
        settlementsDt.search(q);

        // Status: use hidden raw value inside cell
        // Column index: 5 (Status)
        if (status) {
            settlementsDt.column(5).search(status, true, false);
        } else {
            settlementsDt.column(5).search('', true, false);
        }

        // Employee: use hidden employee id inside cell
        // Column index: 4 (Sales employee)
        if (empId) {
            settlementsDt.column(4).search('\\b' + empId + '\\b', true, false);
        } else {
            settlementsDt.column(4).search('', true, false);
        }

        settlementsDt.draw();
    }

    // Live bind
    ['st_q','st_status','st_date_from','st_date_to','st_amount_from','st_amount_to'].forEach(function(id){
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', applySettlementsFilters);
        el.addEventListener('change', applySettlementsFilters);
    });

    $('#st_sales_employee_id').on('change', applySettlementsFilters);

    // Initial apply (if query params exist)
    applySettlementsFilters();

    // Preview exclude UX + live totals (unchanged)
    function toFloat(v){
        v = (v ?? '').toString();
        v = v.replace(/,/g, '').trim();
        var n = parseFloat(v);
        return isNaN(n) ? 0 : n;
    }

    function formatMoney(n){
        try{
            return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }catch(e){
            return (Number(n) || 0).toFixed(2);
        }
    }

    function refreshPreviewTotals(){
        var rows = document.querySelectorAll('#previewTable tbody tr');
        var allCount = 0, excludedCount = 0, includedCount = 0;
        var allAmount = 0, excludedAmount = 0, includedAmount = 0;

        rows.forEach(function(tr){
            var amount = toFloat(tr.getAttribute('data-commission'));
            var isExcluded = tr.classList.contains('is-excluded');

            allCount += 1;
            allAmount += amount;

            if (isExcluded) {
                excludedCount += 1;
                excludedAmount += amount;
            } else {
                includedCount += 1;
                includedAmount += amount;
            }
        });

        var el;
        el = document.getElementById('pv_all_count'); if (el) el.textContent = allCount;
        el = document.getElementById('pv_all_amount'); if (el) el.textContent = formatMoney(allAmount);

        el = document.getElementById('pv_excluded_count'); if (el) el.textContent = excludedCount;
        el = document.getElementById('pv_excluded_amount'); if (el) el.textContent = formatMoney(excludedAmount);

        el = document.getElementById('pv_included_count'); if (el) el.textContent = includedCount;
        el = document.getElementById('pv_included_amount'); if (el) el.textContent = formatMoney(includedAmount);
    }

    function bindExcludeToggles(){
        var table = document.getElementById('previewTable');
        if (!table) return;

        table.addEventListener('change', function(e){
            var chk = e.target.closest('.js-exclude');
            if (!chk) return;

            var tr = chk.closest('tr');
            if (!tr) return;

            var reason = tr.querySelector('.js-reason');
            var isOn = chk.checked;

            tr.classList.toggle('is-excluded', isOn);
            if (reason) {
                reason.disabled = !isOn;
                if (!isOn) reason.value = '';
                if (isOn) reason.focus();
            }

            refreshPreviewTotals();
        });

        refreshPreviewTotals();
    }

    bindExcludeToggles();
});
</script>

@endsection
