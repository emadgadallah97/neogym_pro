@extends('layouts.master_table')

@section('title')
    {{ trans('accounting.treasury') }}
@endsection
@section('content')

<style>
    /* ── KPI Cards ── */
    .kpi-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .08);
        transition: transform .2s, box-shadow .2s;
    }

    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, .13);
    }

    .kpi-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .kpi-value {
        font-size: 1.45rem;
        font-weight: 700;
        letter-spacing: -.5px;
    }

    .kpi-label {
        font-size: .78rem;
        color: #7b8190;
    }

    /* ── Period badge ── */
    .period-badge-open {
        background: #d1fae5;
        color: #065f46;
    }

    .period-badge-closed {
        background: #fee2e2;
        color: #991b1b;
    }

    /* ── Transactions table ── */
    .tx-type-in {
        background: #d1fae5;
        color: #065f46;
    }

    .tx-type-out {
        background: #fee2e2;
        color: #991b1b;
    }

    .tx-reversal td {
        opacity: .6;
        font-style: italic;
    }

    .tx-reversal-badge {
        background: #fef3c7;
        color: #92400e;
        font-size: .7rem;
    }

    /* ── Modals ── */
    .modal-header-treasury {
        background: linear-gradient(135deg, #1e3a5f, #2563eb);
        color: #fff;
        border-radius: 16px 16px 0 0;
    }

    .confirm-step {
        display: none;
    }

    /* ── Filters bar ── */
    .filters-bar {
        background: #f8fafc;
        border-radius: 12px;
        padding: 14px 18px;
        border: 1px solid #e2e8f0;
    }

    /* ── Pagination ── */
    .page-link {
        border-radius: 8px !important;
        margin: 0 2px;
    }

    .section-divider {
        border: 0;
        height: 2px;
        background: linear-gradient(to right, #e2e8f0, #2563eb22, #e2e8f0);
        margin: 1.5rem 0;
    }
</style>


    {{-- ══════════════════════════════════════
    Page Header
    ══════════════════════════════════════ --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font">
                    <i class="mdi mdi-cash-register me-1 text-primary"></i>
                    {{ trans('accounting.treasury') }}
                    @if (isset($viewPeriod) && !$viewPeriod->isOpen())
                        <small class="text-muted fs-13 ms-2">— عرض فترة: {{ $viewPeriod->name }}</small>
                    @endif
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="{{ url('accounting') }}">{{ trans('accounting.accounting') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ trans('accounting.treasury') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════
    Branch Selector + Period Status
    ══════════════════════════════════════ --}}
    <div class="row mb-4 align-items-center g-3">
        <div class="col-md-4">
            <form method="GET" action="{{ route('treasury.index') }}" id="branchForm">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="ri-building-line text-primary"></i>
                    </span>
                    <select name="branch_id" id="branchSelector" class="form-select border-start-0 font"
                        onchange="document.getElementById('branchForm').submit()">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $branch->id == $selectedBranchId ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
        <div class="col-md-8 d-flex align-items-center gap-2 flex-wrap">
            @php
                $isViewingHistory = isset($viewPeriod) && !$viewPeriod->isOpen();
                $displayPeriod = $isViewingHistory ? $viewPeriod : $balance['period'];
            @endphp
            @if ($displayPeriod)
                @if ($displayPeriod->isOpen())
                    <span class="badge period-badge-open fs-12 px-3 py-2">
                        <i class="ri-lock-unlock-line me-1"></i>
                        {{ trans('accounting.treasury_period_open') }} — {{ $displayPeriod->name }}
                    </span>
                @else
                    <span class="badge period-badge-closed fs-12 px-3 py-2">
                        <i class="ri-lock-line me-1"></i>
                        فترة مغلقة — {{ $displayPeriod->name }}
                    </span>
                @endif
                <small class="text-muted">من: {{ optional($displayPeriod->start_date)->format('Y-m-d') }}</small>
            @else
                <span class="badge period-badge-closed fs-12 px-3 py-2">
                    <i class="ri-lock-line me-1"></i>
                    {{ trans('accounting.treasury_no_open_period_badge') }}
                </span>
            @endif

            {{-- Action Buttons --}}
            <div class="ms-auto d-flex flex-wrap gap-2">
                @if (!$isViewingHistory)
                    @can('treasury.open')
                        @if (!$balance['period'])
                            <button class="btn btn-success btn-sm font" data-bs-toggle="modal" data-bs-target="#modalOpenPeriod">
                                <i class="ri-add-circle-line me-1"></i>{{ trans('accounting.treasury_open_period') }}
                            </button>
                        @endif
                    @endcan

                    @can('treasury.close')
                        @if ($balance['period'])
                            <button class="btn btn-danger btn-sm font" data-bs-toggle="modal" data-bs-target="#modalClosePeriod">
                                <i class="ri-close-circle-line me-1"></i>{{ trans('accounting.treasury_close_period') }}
                            </button>
                        @endif
                    @endcan

                    @can('treasury.manual')
                        @if ($balance['period'])
                            <button class="btn btn-warning btn-sm font text-dark" data-bs-toggle="modal"
                                data-bs-target="#modalManual">
                                <i class="ri-edit-box-line me-1"></i>{{ trans('accounting.treasury_manual_transaction') }}
                            </button>
                        @endif
                    @endcan
                @endif

                @can('treasury.review')
                    <a href="{{ route('treasury.periods', ['branch_id' => $selectedBranchId]) }}"
                        class="btn btn-soft-secondary btn-sm font">
                        <i class="ri-history-line me-1"></i>{{ trans('accounting.treasury_review_periods') }}
                    </a>
                @endcan

                @can('treasury.view')
                    @if ($balance['period'])
                        <a href="{{ route('treasury.export', ['branch_id' => $selectedBranchId]) }}"
                            class="btn btn-soft-info btn-sm font" id="btnExport">
                            <i class="ri-file-excel-2-line me-1"></i>{{ trans('accounting.treasury_export_excel') }}
                        </a>
                        {{-- TODO: PDF export via barryvdh/laravel-dompdf (not yet installed) --}}
                    @endif
                @endcan
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════
    KPI Cards
    ══════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Opening Balance --}}
        <div class="col-6 col-md-3">
            <div class="card kpi-card h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background:#dbeafe">
                        <i class="ri-bank-line text-primary"></i>
                    </div>
                    <div>
                        <div class="kpi-label">{{ trans('accounting.treasury_opening_balance') }}</div>
                        <div class="kpi-value" id="kpiOpening">{{ number_format($balance['opening'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total In --}}
        <div class="col-6 col-md-3">
            <div class="card kpi-card h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background:#d1fae5">
                        <i class="ri-arrow-down-circle-line text-success"></i>
                    </div>
                    <div>
                        <div class="kpi-label">{{ trans('accounting.treasury_total_in') }}</div>
                        <div class="kpi-value text-success" id="kpiIn">{{ number_format($balance['total_in'], 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Out --}}
        <div class="col-6 col-md-3">
            <div class="card kpi-card h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background:#fee2e2">
                        <i class="ri-arrow-up-circle-line text-danger"></i>
                    </div>
                    <div>
                        <div class="kpi-label">{{ trans('accounting.treasury_total_out') }}</div>
                        <div class="kpi-value text-danger" id="kpiOut">{{ number_format($balance['total_out'], 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Current Balance --}}
        <div class="col-6 col-md-3">
            <div class="card kpi-card h-100 p-3"
                style="border-left:4px solid {{ $balance['balance'] >= 0 ? '#22c55e' : '#ef4444' }}">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background:{{ $balance['balance'] >= 0 ? '#d1fae5' : '#fee2e2' }}">
                        <i class="ri-safe-line {{ $balance['balance'] >= 0 ? 'text-success' : 'text-danger' }}"></i>
                    </div>
                    <div>
                        <div class="kpi-label">{{ trans('accounting.treasury_current_balance') }}</div>
                        <div class="kpi-value {{ $balance['balance'] >= 0 ? 'text-success' : 'text-danger' }}"
                            id="kpiBalance">
                            {{ number_format($balance['balance'], 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <hr class="section-divider">

    {{-- ══════════════════════════════════════
    Filters Bar
    ══════════════════════════════════════ --}}
    @if ($balance['period'] || (isset($viewPeriod) && $viewPeriod))
        <div class="filters-bar mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ trans('accounting.treasury_filter_type') }}</label>
                    <select id="filterType" class="form-select form-select-sm font">
                        <option value="">{{ trans('accounting.all') }}</option>
                        <option value="in">{{ trans('accounting.treasury_in') }}</option>
                        <option value="out">{{ trans('accounting.treasury_out') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ trans('accounting.treasury_filter_source') }}</label>
                    <select id="filterSource" class="form-select form-select-sm font">
                        <option value="">{{ trans('accounting.all') }}</option>
                        <option value="income">{{ trans('accounting.treasury_source_income') }}</option>
                        <option value="expense">{{ trans('accounting.treasury_source_expense') }}</option>
                        <option value="manual">{{ trans('accounting.treasury_source_manual') }}</option>
                        <option value="salary">{{ trans('accounting.treasury_source_salary') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ trans('accounting.date_from') }}</label>
                    <input type="date" id="filterDateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ trans('accounting.date_to') }}</label>
                    <input type="date" id="filterDateTo" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ trans('accounting.treasury_filter_user') }}</label>
                    <select id="filterUser" class="form-select form-select-sm font">
                        <option value="">{{ trans('accounting.all') }}</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="btnApplyFilters" class="btn btn-primary btn-sm w-100 font">
                        <i class="ri-filter-line me-1"></i>{{ trans('accounting.apply_filters') }}
                    </button>
                </div>
                <div class="col-12">
                    <button id="btnResetFilters" class="btn btn-link btn-sm text-muted p-0">
                        <i class="ri-refresh-line me-1"></i>{{ trans('accounting.reset_filters') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════
    Dynamic Filter KPIs
    ══════════════════════════════════════ --}}
    <div id="filterKpiSection" class="row g-3 mb-4 d-none">
        <div class="col-12">
            <div class="alert alert-secondary border-0 shadow-sm py-2 mb-0 d-flex flex-wrap align-items-center gap-4 font">
                <strong class="text-dark"><i class="ri-filter-3-line me-1"></i>ملخص البحث المخصص:</strong>
                
                <div class="d-flex align-items-center gap-2">
                    <span class="text-success fw-semibold"><i class="ri-arrow-down-circle-line fs-16 align-middle me-1"></i>إجمالي الوارد: <span id="fKpiIn">0.00</span></span>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <span class="text-danger fw-semibold"><i class="ri-arrow-up-circle-line fs-16 align-middle me-1"></i>إجمالي الصادر: <span id="fKpiOut">0.00</span></span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold"><i class="ri-scales-3-line fs-16 align-middle me-1"></i>الصافي: <span id="fKpiNet" class="fs-15">0.00</span></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════
    Transactions Table
    ══════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
            <h6 class="mb-0 font fw-semibold">
                <i class="ri-list-unordered me-1 text-primary"></i>
                {{ trans('accounting.treasury_transactions_list') }}
                @if (isset($readOnly) && $readOnly)
                    <span
                        class="badge bg-soft-secondary text-secondary ms-2 fs-11">{{ trans('accounting.treasury_read_only') }}</span>
                @endif
            </h6>
            <small class="text-muted" id="txTotalCount">...</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 font" id="txTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>{{ trans('accounting.treasury_tx_date') }}</th>
                            <th>{{ trans('accounting.treasury_tx_type') }}</th>
                            <th>{{ trans('accounting.amount') }}</th>
                            <th>{{ trans('accounting.treasury_tx_source') }}</th>
                            <th>{{ trans('accounting.description') }}</th>
                            <th>{{ trans('accounting.treasury_tx_user') }}</th>
                            <th>{{ trans('accounting.treasury_tx_period') }}</th>
                        </tr>
                    </thead>
                    <tbody id="txTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                جاري التحميل...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top py-2">
            <div id="txPagination" class="d-flex justify-content-center"></div>
        </div>
    </div>

    {{-- ══════════════════════════════════════
    MODALS
    ══════════════════════════════════════ --}}

    {{-- ① Open Period Modal --}}
    @can('treasury.open')
        <div class="modal fade" id="modalOpenPeriod" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('treasury.open') }}" method="POST">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">
                        <div class="modal-header modal-header-treasury">
                            <h5 class="modal-title font">
                                <i class="ri-add-circle-line me-2"></i>{{ trans('accounting.treasury_open_period') }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">

                            <div class="mb-3">
                                <label class="form-label font fw-semibold">{{ trans('accounting.treasury_period_name') }}
                                    <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control font" required
                                    placeholder="{{ trans('accounting.treasury_period_name_hint') }}"
                                    value="{{ trans('accounting.treasury_default_period_name') }} {{ now()->format('Y/m') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label font fw-semibold">{{ trans('accounting.treasury_start_date') }} <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" required
                                    value="{{ now()->toDateString() }}">
                            </div>

                            <div class="mb-3">
                                <label
                                    class="form-label font fw-semibold">{{ trans('accounting.treasury_opening_balance') }}</label>
                                @php
                                    $carriedValue = $lastClosedPeriod ? (float)$lastClosedPeriod->carried_forward : null;
                                @endphp
                                <input type="number" name="opening_balance" id="openingBalanceInput" class="form-control" step="0.01"
                                    min="0" placeholder="0.00" value="{{ $carriedValue }}" data-carried="{{ $carriedValue }}">
                                    
                                <div id="openingBalanceWarning" class="alert alert-danger font mt-2 d-none mb-0">
                                    <h6 class="fw-bold mb-1"><i class="ri-error-warning-fill me-1"></i>تحذير هام جداً:</h6>
                                    لقد قمت بتغيير الرصيد الافتتاحي عن الرقم المُرحّل من الفترة السابقة. 
                                    هذا الإجراء يعني أن الرصيد السابق سيتم تجاهله تماماً، وسيؤدي إلى <u>تغيير الرصيد الفعلي للخزينة</u>. هذه العملية نهائية ولا يمكن التراجع عنها!
                                </div>

                                @if ($lastClosedPeriod && $carriedValue !== null)
                                    <div class="form-text text-muted font" id="openingBalanceHint">
                                        <i class="ri-information-line me-1"></i>
                                        {{ trans('accounting.treasury_carried_forward_hint') }}:
                                        <strong>{{ number_format($carriedValue, 2) }}</strong>
                                        ({{ trans('accounting.treasury_from_last_period') }}: {{ $lastClosedPeriod->name }})
                                    </div>
                                @else
                                    <div class="form-text text-muted" id="openingBalanceHint">{{ trans('accounting.treasury_opening_balance_hint') }}
                                    </div>
                                @endif
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light font"
                                data-bs-dismiss="modal">{{ trans('accounting.treasury_cancel') }}</button>
                            <button type="submit" class="btn btn-success font" id="btnConfirmOpen">
                                <i class="ri-check-line me-1"></i>{{ trans('accounting.treasury_confirm_open') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    {{-- ② Close Period Modal --}}
    @can('treasury.close')
        @if ($balance['period'])
            <div class="modal fade" id="modalClosePeriod" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow">
                        <form action="{{ route('treasury.close') }}" method="POST" id="formClosePeriod">
                            @csrf
                            <input type="hidden" name="period_id" value="{{ $balance['period']->id }}">
                            <div class="modal-header modal-header-treasury">
                                <h5 class="modal-title font">
                                    <i class="ri-close-circle-line me-2"></i>{{ trans('accounting.treasury_close_period') }}
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">

                                {{-- Step 1: Input --}}
                                <div id="closeStep1">
                                    <div class="alert alert-info py-2 font-sm">
                                        <i class="ri-information-line me-1"></i>
                                        {{ trans('accounting.treasury_close_hint') }}
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label
                                                class="form-label font fw-semibold">{{ trans('accounting.treasury_closing_balance') }}</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control fw-bold text-success bg-light"
                                                    id="displayClosingBalance" readonly
                                                    value="{{ number_format($balance['balance'], 2) }}">
                                                <span
                                                    class="input-group-text">{{ trans('accounting.treasury_currency') }}</span>
                                            </div>
                                            <div class="form-text">{{ trans('accounting.treasury_auto_calculated') }}</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label
                                                class="form-label font fw-semibold">{{ trans('accounting.treasury_handed_over') }}
                                                <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" name="handed_over" id="handedOverInput"
                                                    class="form-control" step="0.01" min="0"
                                                    max="{{ $balance['balance'] }}" required
                                                    value="{{ $balance['balance'] }}">
                                                <span
                                                    class="input-group-text">{{ trans('accounting.treasury_currency') }}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label
                                                class="form-label font fw-semibold">{{ trans('accounting.treasury_carried_forward') }}</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light fw-bold text-warning"
                                                    id="displayCarriedForward" readonly value="0.00">
                                                <span
                                                    class="input-group-text">{{ trans('accounting.treasury_currency') }}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label
                                                class="form-label font fw-semibold">{{ trans('accounting.treasury_end_date') }}
                                                <span class="text-danger">*</span></label>
                                            <input type="date" name="end_date" class="form-control" required
                                                value="{{ now()->toDateString() }}">
                                        </div>

                                        <div class="col-12">
                                            <label
                                                class="form-label font fw-semibold">{{ trans('accounting.treasury_close_notes') }}</label>
                                            <textarea name="close_notes" class="form-control font" rows="3"
                                                placeholder="{{ trans('accounting.treasury_close_notes_hint') }}"></textarea>
                                        </div>
                                    </div>

                                    <div class="mt-3 text-end">
                                        <button type="button" class="btn btn-danger font" id="btnCloseConfirmStep">
                                            <i class="ri-eye-line me-1"></i>{{ trans('accounting.treasury_preview_close') }}
                                        </button>
                                    </div>
                                </div>

                                {{-- Step 2: Confirm --}}
                                <div id="closeStep2" class="confirm-step">
                                    <div class="alert alert-warning font">
                                        <h6 class="fw-bold mb-2"><i
                                                class="ri-alert-line me-1"></i>{{ trans('accounting.treasury_confirm_close_title') }}
                                        </h6>
                                        <p class="mb-1">{{ trans('accounting.treasury_confirm_close_message') }}</p>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm font">
                                            <tr>
                                                <th class="bg-light">{{ trans('accounting.treasury_period_name') }}</th>
                                                <td>{{ $balance['period']->name }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">{{ trans('accounting.treasury_closing_balance') }}</th>
                                                <td class="fw-bold text-success" id="confirmClosing">—</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">{{ trans('accounting.treasury_handed_over') }}</th>
                                                <td class="fw-bold text-danger" id="confirmHanded">—</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">{{ trans('accounting.treasury_carried_forward') }}</th>
                                                <td class="fw-bold text-warning" id="confirmCarried">—</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-end mt-3">
                                        <button type="button" class="btn btn-secondary font" id="btnBackToEdit">
                                            <i class="ri-arrow-left-line me-1"></i>{{ trans('accounting.back') }}
                                        </button>
                                        <button type="submit" class="btn btn-danger font">
                                            <i
                                                class="ri-check-double-line me-1"></i>{{ trans('accounting.treasury_confirm_and_close') }}
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    {{-- ③ Manual Transaction Modal --}}
    @can('treasury.manual')
        @if ($balance['period'])
            <div class="modal fade" id="modalManual" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <form action="{{ route('treasury.manual') }}" method="POST">
                            @csrf
                            <input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">
                            <div class="modal-header modal-header-treasury">
                                <h5 class="modal-title font">
                                    <i
                                        class="ri-edit-box-line me-2"></i>{{ trans('accounting.treasury_manual_transaction') }}
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">

                                <div class="mb-3">
                                    <label class="form-label font fw-semibold">{{ trans('accounting.treasury_tx_type') }}
                                        <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type" id="typeIn"
                                                value="in" checked>
                                            <label class="form-check-label text-success fw-semibold font" for="typeIn">
                                                <i
                                                    class="ri-arrow-down-circle-line me-1"></i>{{ trans('accounting.treasury_in') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type" id="typeOut"
                                                value="out">
                                            <label class="form-check-label text-danger fw-semibold font" for="typeOut">
                                                <i
                                                    class="ri-arrow-up-circle-line me-1"></i>{{ trans('accounting.treasury_out') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label font fw-semibold">{{ trans('accounting.amount') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="amount" class="form-control" step="0.01"
                                            min="0.01" required>
                                        <span class="input-group-text">{{ trans('accounting.treasury_currency') }}</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label
                                        class="form-label font fw-semibold">{{ trans('accounting.treasury_tx_category') }}</label>
                                    <select name="category" class="form-select font">
                                        <option value="">{{ trans('accounting.choose') }}</option>
                                        <option value="adjustment">{{ trans('accounting.treasury_cat_adjustment') }}</option>
                                        <option value="transfer">{{ trans('accounting.treasury_cat_transfer') }}</option>
                                        <option value="other">{{ trans('accounting.treasury_cat_other') }}</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label font fw-semibold">{{ trans('accounting.description') }}</label>
                                    <textarea name="description" class="form-control font" rows="3"
                                        placeholder="{{ trans('accounting.treasury_description_hint') }}"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label font fw-semibold">{{ trans('accounting.treasury_tx_date') }}
                                        <span class="text-danger">*</span></label>
                                    <input type="date" name="transaction_date" class="form-control" required
                                        value="{{ now()->toDateString() }}">
                                </div>

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light font"
                                    data-bs-dismiss="modal">{{ trans('accounting.treasury_cancel') }}</button>
                                <button type="submit" class="btn btn-warning text-dark font">
                                    <i class="ri-save-line me-1"></i>{{ trans('accounting.accounting_save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan


<script>
    (function () {
        'use strict';

        const BRANCH_ID  = {{ $selectedBranchId }};
        const PERIOD_ID  = {{ isset($viewPeriod) ? $viewPeriod->id : ($balance['period'] ? $balance['period']->id : 'null') }};
        const CLOSING_BAL = {{ $balance['balance'] }};

        let currentPage    = 1;
        let currentFilters = {};

        // ══════════════════════════════════════
        // Load Transactions
        // ══════════════════════════════════════
        function loadTransactions(page = 1) {
            const params = new URLSearchParams({
                branch_id: BRANCH_ID,
                page     : page,
                ...currentFilters
            });

            if (PERIOD_ID !== null) {
                params.set('period_id', PERIOD_ID);
            }

            $('#txTableBody').html(`
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        جاري التحميل...
                    </td>
                </tr>`);

            $.ajax({
                url     : '{{ route('treasury.transactions') }}',
                type    : 'GET',
                dataType: 'json',
                data    : params.toString(),

                success: function (res) {

                    // ── تحقق من ok + وجود data كـ array ──
                    if (!res.ok || !Array.isArray(res.data)) {
                        showError('حدث خطأ في جلب الحركات');
                        return;
                    }

                    $('#txTotalCount').text('إجمالي: ' + res.total + ' حركة');

                    if (res.data.length === 0) {
                        $('#txTableBody').html(`
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="ri-inbox-line fs-36 d-block mb-2"></i>
                                    {{ trans('accounting.treasury_no_transactions') }}
                                </td>
                            </tr>`);
                        return;
                    }

                    let html = '';
                    res.data.forEach(function (tx) {
                        const isReversal = tx.is_reversal;
                        const rowCls     = isReversal ? 'tx-reversal' : '';

                        const typeBadge = tx.type === 'in'
                            ? '<span class="badge tx-type-in px-2">⬇ '  + tx.type_label + '</span>'
                            : '<span class="badge tx-type-out px-2">⬆ ' + tx.type_label + '</span>';

                        const reversalBadge = isReversal
                            ? ' <span class="badge tx-reversal-badge ms-1"><i class="ri-arrow-go-back-line"></i> عكسي</span>'
                            : '';

                        const sourceLink = tx.source_type && tx.source_type !== 'manual' && tx.source_id
                            ? `<a href="/${tx.source_type}/${tx.source_id}" target="_blank" class="text-decoration-none small">
                                    ${tx.source_type_label} #${tx.source_id}
                               </a>`
                            : `<span class="small text-muted">${tx.source_type_label ?? '—'}</span>`;

                        html += `
                            <tr class="${rowCls}">
                                <td class="text-muted small">${tx.id}${reversalBadge}</td>
                                <td>${tx.transaction_date}</td>
                                <td>${typeBadge}</td>
                                <td class="fw-semibold ${tx.type === 'in' ? 'text-success' : 'text-danger'}">${tx.amount}</td>
                                <td>${sourceLink}</td>
                                <td class="small text-truncate" style="max-width:180px"
                                    title="${tx.description ?? ''}">${tx.description ?? '—'}</td>
                                <td class="small">${tx.user_name ?? '—'}</td>
                                <td class="small text-muted">${tx.period_name ?? '—'}</td>
                            </tr>`;
                    });

                    $('#txTableBody').html(html);
                    renderPagination(res.current_page, res.last_page);

                    // Display filter KPIs if filters are active
                    const hasFilters = currentFilters.type || currentFilters.source_type || currentFilters.user_id || currentFilters.date_from || currentFilters.date_to;
                    
                    if (hasFilters && res.totals) {
                        $('#fKpiIn').text(parseFloat(res.totals.in).toFixed(2));
                        $('#fKpiOut').text(parseFloat(res.totals.out).toFixed(2));
                        
                        let netStr = parseFloat(res.totals.net).toFixed(2);
                        $('#fKpiNet').text(netStr)
                            .removeClass('text-success text-danger')
                            .addClass(res.totals.net >= 0 ? 'text-success' : 'text-danger');
                        
                        $('#filterKpiSection').removeClass('d-none');
                    } else {
                        $('#filterKpiSection').addClass('d-none');
                    }
                },

                error: function (xhr, status, error) {
                    console.error('Treasury AJAX Error ► status:', xhr.status, '| response:', xhr.responseText);
                    showError('{{ trans('accounting.ajax_error_try_again') }}');
                }
            });
        }

        // ══════════════════════════════════════
        // Pagination
        // ══════════════════════════════════════
        function renderPagination(current, last) {
            if (last <= 1) { $('#txPagination').html(''); return; }

            let html = '<nav><ul class="pagination pagination-sm mb-0">';

            html += `<li class="page-item ${current === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${current - 1}">‹</a>
                     </li>`;

            for (let p = Math.max(1, current - 2); p <= Math.min(last, current + 2); p++) {
                html += `<li class="page-item ${p === current ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${p}">${p}</a>
                         </li>`;
            }

            html += `<li class="page-item ${current === last ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${current + 1}">›</a>
                     </li>`;

            html += '</ul></nav>';
            $('#txPagination').html(html);
        }

        // ══════════════════════════════════════
        // Events
        // ══════════════════════════════════════

        // ── Pagination click ──
        $(document).on('click', '#txPagination a.page-link', function (e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (page > 0) {
                currentPage = page;
                loadTransactions(page);
            }
        });

        // ── Apply Filters ──
        $('#btnApplyFilters').on('click', function () {
            currentFilters = {
                type       : $('#filterType').val(),
                source_type: $('#filterSource').val(),
                date_from  : $('#filterDateFrom').val(),
                date_to    : $('#filterDateTo').val(),
                user_id    : $('#filterUser').val(),
            };

            const params = new URLSearchParams({ branch_id: BRANCH_ID, ...currentFilters });
            $('#btnExport').attr('href', '{{ route('treasury.export') }}?' + params.toString());

            currentPage = 1;
            loadTransactions(1);
        });

        // ── Reset Filters ──
        $('#btnResetFilters').on('click', function () {
            $('#filterType, #filterSource, #filterUser').val('');
            $('#filterDateFrom, #filterDateTo').val('');
            currentFilters = {};
            currentPage    = 1;
            loadTransactions(1);
        });

        // ══════════════════════════════════════
        // Open Period Modal — Strict Warning
        // ══════════════════════════════════════
        $('#openingBalanceInput').on('input', function() {
            let original = parseFloat($(this).data('carried'));
            if (!isNaN(original)) {
                let current = parseFloat($(this).val());
                if (isNaN(current)) current = 0;
                
                if (current !== original) {
                    $('#openingBalanceWarning').removeClass('d-none');
                    $('#openingBalanceHint').addClass('d-none');
                    $('#btnConfirmOpen').removeClass('btn-success').addClass('btn-danger')
                        .html('<i class="ri-alert-line me-1"></i>تأكيد الفتح رغم التحذير');
                } else {
                    $('#openingBalanceWarning').addClass('d-none');
                    $('#openingBalanceHint').removeClass('d-none');
                    $('#btnConfirmOpen').removeClass('btn-danger').addClass('btn-success')
                        .html('<i class="ri-check-line me-1"></i>{{ trans('accounting.treasury_confirm_open') }}');
                }
            }
        });

        // ══════════════════════════════════════
        // Close Period Modal — Carried Forward
        // ══════════════════════════════════════
        $('#handedOverInput').on('input', function () {
            const handed  = parseFloat($(this).val()) || 0;
            const carried = Math.max(0, CLOSING_BAL - handed);
            $('#displayCarriedForward').val(carried.toFixed(2));
        }).trigger('input');

        $('#btnCloseConfirmStep').on('click', function () {
            const handed  = parseFloat($('#handedOverInput').val()) || 0;
            const carried = Math.max(0, CLOSING_BAL - handed);

            if (handed > CLOSING_BAL) {
                alert('{{ trans('accounting.treasury_handed_over_exceeds_balance') }}');
                return;
            }

            $('#confirmClosing').text(CLOSING_BAL.toFixed(2));
            $('#confirmHanded').text(handed.toFixed(2));
            $('#confirmCarried').text(carried.toFixed(2));

            $('#closeStep1').hide();
            $('#closeStep2').show();
        });

        $('#btnBackToEdit').on('click', function () {
            $('#closeStep2').hide();
            $('#closeStep1').show();
        });

        // ══════════════════════════════════════
        // Helpers
        // ══════════════════════════════════════
        function showError(msg) {
            $('#txTableBody').html(`
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger">
                        <i class="ri-error-warning-line me-1"></i>${msg}
                    </td>
                </tr>`);
        }

        // ══════════════════════════════════════
        // Init — تشغيل تلقائي عند تحميل الصفحة
        // ══════════════════════════════════════
        $(function () {
            @if ($balance['period'] || (isset($viewPeriod) && $viewPeriod))
                loadTransactions(1);
            @else
                // لا توجد فترة مفتوحة — أظهر رسالة بدلاً من spinner
                $('#txTableBody').html(`
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="ri-lock-line fs-36 d-block mb-2"></i>
                            {{ trans('accounting.treasury_no_open_period_badge') }}
                        </td>
                    </tr>`);
                $('#txTotalCount').text('');
            @endif
        });

    })();
</script>
@endsection
