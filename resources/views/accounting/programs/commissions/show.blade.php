@extends('layouts.master_table')
@section('title')
{{ trans('accounting.commissions_settlement_details') }}
@stop

@section('content')

<style>
    .info-card {
        border: 0;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .06), 0 1px 3px rgba(16, 24, 40, .1);
        overflow: hidden;
    }

    .mini-kpi {
        border: 0;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .06), 0 1px 3px rgba(16, 24, 40, .08);
        height: 100%;
    }

    .mini-kpi .icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .mini-kpi .label {
        font-size: 12px;
        letter-spacing: .6px;
    }

    .mini-kpi .value {
        font-weight: 800;
        letter-spacing: .2px;
    }

    .meta-row .text-muted {
        font-size: 13px;
    }

    .actions-bar {
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: .5rem;
        padding: .75rem;
        background: rgba(0, 0, 0, .015);
    }

    .badge-status {
        font-weight: 600;
        letter-spacing: .2px;
    }

    #itemsTable tbody tr.is-excluded {
        background: rgba(220, 53, 69, .08) !important;
    }

    #itemsTable tbody tr.is-included {
        background: rgba(25, 135, 84, .06) !important;
    }

    .table td,
    .table th {
        vertical-align: middle;
    }

    .modal-summary-item {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        border-bottom: 1px dashed rgba(0, 0, 0, .08);
    }

    .modal-summary-item:last-child {
        border-bottom: 0;
    }
</style>

@php
$emp = $settlement->salesEmployee
? ($settlement->salesEmployee->fullname ?? trim(($settlement->salesEmployee->first_name ?? '').' '.($settlement->salesEmployee->last_name ?? '')))
: null;

$branchName = null;
if ($settlement->branch) {
$branchName = method_exists($settlement->branch, 'getTranslation')
? $settlement->branch->getTranslation('name', app()->getLocale())
: ($settlement->branch->name ?? null);
}

$status = (string)($settlement->status ?? '');
$locale = app()->getLocale();
@endphp

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="mb-sm-0">
                    {{ trans('accounting.commissions_settlement_details') }}
                    <span class="text-muted">#{{ $settlement->id }}</span>
                </h4>

                @if($status === 'paid')
                <span class="badge bg-success badge-status">
                    <i class="ri-checkbox-circle-line align-bottom me-1"></i>{{ trans('accounting.paid') }}
                </span>
                @elseif($status === 'draft')
                <span class="badge bg-warning text-dark badge-status">
                    <i class="ri-draft-line align-bottom me-1"></i>{{ trans('accounting.draft') }}
                </span>
                @else
                <span class="badge bg-secondary badge-status">
                    <i class="ri-close-circle-line align-bottom me-1"></i>{{ trans('accounting.cancelled') }}
                </span>
                @endif
            </div>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('commissions.index') }}">
                            <i class="ri-arrow-left-line align-bottom me-1"></i>{{ trans('accounting.commissions') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active">{{ trans('accounting.view') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Header / Meta --}}
<div class="row g-3">
    <div class="col-12">
        <div class="card info-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1">
                            <i class="ri-file-shield-2-line align-bottom me-1"></i>
                            {{ trans('accounting.commissions_settlement_details') }}
                        </h5>
                        <div class="text-muted meta-row">
                            <span class="me-3">
                                <i class="ri-calendar-2-line align-bottom me-1"></i>{{ trans('accounting.date_from') }}:
                                <span class="fw-semibold">{{ optional($settlement->date_from)->format('Y-m-d') }}</span>
                            </span>
                            <span class="me-3">
                                <i class="ri-calendar-check-line align-bottom me-1"></i>{{ trans('accounting.date_to') }}:
                                <span class="fw-semibold">{{ optional($settlement->date_to)->format('Y-m-d') }}</span>
                            </span>
                            <span class="me-3">
                                <i class="ri-user-star-line align-bottom me-1"></i>{{ trans('accounting.sales_employee') }}:
                                <span class="fw-semibold">{{ $emp ?: trans('accounting.all_employees') }}</span>
                            </span>
                            @if($branchName)
                            <span>
                                <i class="ri-building-line align-bottom me-1"></i>{{ trans('accounting.branch') }}:
                                <span class="fw-semibold">{{ $branchName }}</span>
                            </span>
                            @endif
                        </div>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('commissions.index') }}" class="btn btn-soft-secondary">
                            <i class="ri-arrow-go-back-line align-bottom me-1"></i>{{ trans('accounting.back') ?? trans('accounting.view') }}
                        </a>
                        <a href="{{ route('commissions.print', $settlement->id) }}" target="_blank" class="btn btn-soft-primary">
                            <i class="ri-printer-line align-bottom me-1"></i>{{ trans('accounting.print') ?? 'Print' }}
                        </a>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Totals KPIs --}}
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card mini-kpi">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <div class="text-muted text-uppercase label mb-1">{{ trans('accounting.total_amount') }}</div>
                                        <div class="h4 mb-0 value">{{ number_format((float)$settlement->total_commission_amount, 2) }}</div>
                                        <div class="text-muted small mt-1">{{ trans('accounting.included') }}</div>
                                    </div>
                                    <div class="icon bg-success-subtle text-success">
                                        <i class="ri-check-double-line"></i>
                                    </div>
                                </div>
                                <div class="mt-3 text-muted small">
                                    <i class="ri-list-check-2 align-bottom me-1"></i>
                                    {{ trans('accounting.items_count') }}:
                                    <b>{{ (int)$settlement->items_count }}</b>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mini-kpi">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <div class="text-muted text-uppercase label mb-1">{{ trans('accounting.excluded_amount') }}</div>
                                        <div class="h4 mb-0 value">{{ number_format((float)$settlement->total_excluded_commission_amount, 2) }}</div>
                                        <div class="text-muted small mt-1">{{ trans('accounting.excluded') }}</div>
                                    </div>
                                    <div class="icon bg-danger-subtle text-danger">
                                        <i class="ri-forbid-2-line"></i>
                                    </div>
                                </div>
                                <div class="mt-3 text-muted small">
                                    <i class="ri-close-circle-line align-bottom me-1"></i>
                                    {{ trans('accounting.items_count') }}:
                                    <b>{{ (int)$settlement->excluded_items_count }}</b>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mini-kpi">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <div class="text-muted text-uppercase label mb-1">{{ trans('accounting.total_amount_all') }}</div>
                                        <div class="h4 mb-0 value">{{ number_format((float)$settlement->total_all_commission_amount, 2) }}</div>
                                        <div class="text-muted small mt-1">{{ trans('accounting.total_records') }}</div>
                                    </div>
                                    <div class="icon bg-info-subtle text-info">
                                        <i class="ri-money-dollar-circle-line"></i>
                                    </div>
                                </div>
                                <div class="mt-3 text-muted small">
                                    <i class="ri-hashtag align-bottom me-1"></i>
                                    {{ trans('accounting.items_count') }}:
                                    <b>{{ (int)$settlement->all_items_count }}</b>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                @if($status === 'draft')
                <div class="actions-bar mt-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="text-muted small">
                            <i class="ri-information-line align-bottom me-1"></i>
                            {{ trans('accounting.draft') }}: {{ trans('accounting.commissions_only_draft_payable') ?? '' }}
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payConfirmModal">
                                <i class="ri-check-double-line align-bottom me-1"></i> {{ trans('accounting.mark_as_paid') }}
                            </button>

                            <form method="post" action="{{ route('commissions.cancel', $settlement->id) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-soft-secondary">
                                    <i class="ri-close-circle-line align-bottom me-1"></i> {{ trans('accounting.cancel_settlement') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                @if(!empty($settlement->notes))
                <div class="mt-3">
                    <div class="text-muted mb-1">
                        <i class="ri-sticky-note-line align-bottom me-1"></i>{{ trans('accounting.notes') }}
                    </div>
                    <div class="fw-semibold">{{ $settlement->notes }}</div>
                </div>
                @endif

                @if($status === 'paid')
                <div class="row mt-3 g-3">
                    <div class="col-md-6">
                        <div class="text-muted">
                            <i class="ri-time-line align-bottom me-1"></i>{{ trans('accounting.paid_at') ?? 'Paid at' }}
                        </div>
                        <div class="fw-semibold">{{ $settlement->paid_at ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">
                            <i class="ri-user-3-line align-bottom me-1"></i>{{ trans('accounting.paid_by') ?? 'Paid by' }}
                        </div>
                        <div class="fw-semibold">
                            {{ $settlement->paidByUser->name ?? ($settlement->paid_by ?? '-') }}
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>

{{-- Items --}}
<div class="row">
    <div class="col-12">
        <div class="card info-card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0">
                    <i class="ri-list-check-2 align-bottom me-1"></i>
                    {{ trans('accounting.commissions_items') }}
                </h5>
                <div class="text-muted small">
                    <i class="ri-hashtag align-bottom me-1"></i>
                    {{ trans('accounting.total_records') }}: <b>{{ $settlement->items->count() }}</b>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="itemsTable" class="table table-bordered dt-responsive nowrap table-striped table-hover align-middle" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;">#</th>
                                <th style="width:120px;">{{ trans('accounting.subscription_id') }}</th>
                                <th style="width:140px;">{{ trans('accounting.sales_employee') }}</th>
                                <th style="width:120px;">{{ trans('accounting.branch') }}</th>
                                <th style="width:170px;">{{ trans('accounting.create_date') }}</th>
                                <th style="width:140px;">{{ trans('accounting.commission_amount') }}</th>
                                <th style="width:120px;">{{ trans('accounting.status') }}</th>
                                <th>{{ trans('accounting.exclude_reason') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i=0; @endphp
                            @foreach($settlement->items as $it)
                            @php
                            $i++;
                            $rowClass = $it->is_excluded ? 'is-excluded' : 'is-included';

                            // Resolve employee name
                            $itemEmpName = '-';
                            if ($it->salesEmployee) {
                            $itemEmpName = $it->salesEmployee->fullname
                            ?? trim(($it->salesEmployee->first_name ?? '') . ' ' . ($it->salesEmployee->last_name ?? ''));
                            $itemEmpName = $itemEmpName ?: ('#' . $it->sales_employee_id);
                            }

                            // Resolve branch name
                            $itemBranchName = '-';
                            if ($it->branch) {
                            $itemBranchName = method_exists($it->branch, 'getTranslation')
                            ? $it->branch->getTranslation('name', $locale)
                            : ($it->branch->name ?? '-');
                            }
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="text-muted">{{ $i }}</td>
                                <td class="fw-semibold">{{ $it->member_subscription_id }}</td>
                                <td>
                                    <span>{{ $itemEmpName }}</span>
                                </td>
                                <td>
                                    <span>{{ $itemBranchName }}</span>
                                </td>
                                <td class="text-muted">{{ $it->subscription_created_at }}</td>
                                <td class="fw-semibold">{{ number_format((float)$it->commission_amount, 2) }}</td>
                                <td>
                                    @if($it->is_excluded)
                                    <span class="badge bg-danger badge-status">
                                        <i class="ri-forbid-2-line align-bottom me-1"></i>{{ trans('accounting.excluded') }}
                                    </span>
                                    @else
                                    <span class="badge bg-success badge-status">
                                        <i class="ri-check-line align-bottom me-1"></i>{{ trans('accounting.included') }}
                                    </span>
                                    @endif
                                </td>
                                <td>{{ $it->exclude_reason ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Payment Confirmation Modal --}}
@if($status === 'draft')
<div class="modal fade" id="payConfirmModal" tabindex="-1" aria-labelledby="payConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="{{ route('commissions.pay', $settlement->id) }}">
                @csrf
                <div class="modal-header bg-success-subtle">
                    <h5 class="modal-title" id="payConfirmModalLabel">
                        <i class="ri-check-double-line align-bottom me-1"></i>
                        {{ trans('accounting.commissions_confirm_pay_title') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Summary --}}
                    <div class="mb-3 p-3 rounded" style="background: rgba(25,135,84,.06); border: 1px solid rgba(25,135,84,.15);">
                        <h6 class="fw-bold mb-2">
                            <i class="ri-file-list-3-line align-bottom me-1"></i>
                            {{ trans('accounting.commissions_confirm_pay_summary') }}
                        </h6>
                        <div class="modal-summary-item">
                            <span class="text-muted">{{ trans('accounting.commissions_settlement_id') }}</span>
                            <span class="fw-bold">#{{ $settlement->id }}</span>
                        </div>
                        <div class="modal-summary-item">
                            <span class="text-muted">{{ trans('accounting.commissions_settlement_amount') }}</span>
                            <span class="fw-bold text-success">{{ number_format((float)$settlement->total_commission_amount, 2) }}</span>
                        </div>
                        <div class="modal-summary-item">
                            <span class="text-muted">{{ trans('accounting.commissions_settlement_items') }}</span>
                            <span class="fw-bold">{{ (int)$settlement->items_count }}</span>
                        </div>
                        <div class="modal-summary-item">
                            <span class="text-muted">{{ trans('accounting.commissions_settlement_period') }}</span>
                            <span class="fw-semibold">{{ optional($settlement->date_from)->format('Y-m-d') }} — {{ optional($settlement->date_to)->format('Y-m-d') }}</span>
                        </div>
                        @if($emp)
                        <div class="modal-summary-item">
                            <span class="text-muted">{{ trans('accounting.sales_employee') }}</span>
                            <span class="fw-semibold">{{ $emp }}</span>
                        </div>
                        @endif
                    </div>

                    <div class="alert alert-info mb-3">
                        <i class="ri-information-line align-bottom me-1"></i>
                        {{ trans('accounting.commissions_confirm_pay_message') }}
                    </div>

                    {{-- Expense Type --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="ri-price-tag-3-line align-bottom me-1"></i>
                            {{ trans('accounting.expense_type') }} <span class="text-danger">*</span>
                        </label>
                        <select name="expense_type_id" class="form-select" required>
                            <option value="">{{ trans('accounting.commissions_select_expense_type') }}</option>
                            @foreach($ExpensesTypes as $et)
                            @php
                            $etName = method_exists($et, 'getTranslation')
                            ? $et->getTranslation('name', $locale)
                            : (is_array($et->name) ? ($et->name[$locale] ?? ($et->name['ar'] ?? '')) : $et->name);
                            @endphp
                            <option value="{{ $et->id }}">{{ $etName }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Expense Branch (Auto-set, readonly) --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="ri-building-line align-bottom me-1"></i>
                            {{ trans('accounting.branch') }}
                        </label>
                        <input type="hidden" name="expense_branch_id" value="{{ $settlement->branch_id }}">
                        <select class="form-select" disabled>
                            @foreach($BranchesList as $b)
                            @php
                            $bName = method_exists($b, 'getTranslation')
                            ? $b->getTranslation('name', $locale)
                            : (is_array($b->name) ? ($b->name[$locale] ?? ($b->name['ar'] ?? '')) : $b->name);
                            @endphp
                            <option value="{{ $b->id }}" {{ $settlement->branch_id == $b->id ? 'selected' : '' }}>
                                {{ $bName }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Disbursed By Employee --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="ri-user-line align-bottom me-1"></i>
                            {{ trans('accounting.commissions_select_disbursed_by') }}
                        </label>
                        <select name="expense_disbursed_by" class="form-select" id="modalDisbursedBy">
                            <option value="">{{ trans('accounting.optional') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="ri-close-line align-bottom me-1"></i>{{ trans('accounting.commissions_close') }}
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="ri-check-double-line align-bottom me-1"></i>{{ trans('accounting.commissions_confirm_and_pay') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ === 'undefined') return;

        if ($.fn && $.fn.DataTable) {
            $('#itemsTable').DataTable({
                pageLength: 25,
                order: [
                    [1, 'asc']
                ]
            });
        }

        // Auto-load employees on modal open based on settlement branch
        var payModal = document.getElementById('payConfirmModal');
        if (payModal) {
            payModal.addEventListener('shown.bs.modal', function() {
                var branchId = '{{ $settlement->branch_id ?? "" }}';
                var empSelect = document.getElementById('modalDisbursedBy');
                if (!branchId || !empSelect) return;

                // Only load if not already populated
                if (empSelect.options.length > 1) return;

                $.ajax({
                    url: '{{ route("expenses.actions.employees_by_branch") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        branchid: branchId
                    },
                    success: function(res) {
                        if (res.ok && res.data) {
                            res.data.forEach(function(e) {
                                var opt = document.createElement('option');
                                opt.value = e.id;
                                opt.textContent = e.text;
                                empSelect.appendChild(opt);
                            });
                        }
                    }
                });
            });
        }
    });
</script>

@endsection