@extends('layouts.master_table')
@section('title')
{{ trans('accounting.income') }}
@stop


@section('content')


@php
    $branchName = function ($nameJsonOrText) {
        $decoded = json_decode($nameJsonOrText, true);
        if (is_array($decoded)) {
            return $decoded[app()->getLocale()] ?? ($decoded['ar'] ?? ($decoded['en'] ?? ''));
        }
        return $nameJsonOrText;
    };

    $total = $Incomes->count();
    $cancelled = $Incomes->where('iscancelled', 1)->count();
    $notCancelled = $Incomes->where('iscancelled', 0)->count();

    $totalAmount = (float) $Incomes->where('iscancelled', 0)->sum('amount');

    // Today & This month amounts (مثل المصروفات)
    $todayStr = \Carbon\Carbon::today()->format('Y-m-d');
    $monthStartStr = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
    $monthEndStr = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');

    $todayAmount = (float) $Incomes->where('iscancelled', 0)->filter(function ($r) use ($todayStr) {
        return optional($r->incomedate)->format('Y-m-d') === $todayStr;
    })->sum('amount');

    $thisMonthAmount = (float) $Incomes->where('iscancelled', 0)->filter(function ($r) use ($monthStartStr, $monthEndStr) {
        $d = optional($r->incomedate)->format('Y-m-d');
        if (!$d) return false;
        return $d >= $monthStartStr && $d <= $monthEndStr;
    })->sum('amount');
@endphp


<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('accounting.income') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('accounting.accounting') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('accounting.income') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-12">
        <div class="card">


            <div class="card-body pb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">{{ trans('accounting.income_kpis') }}</h5>
                        <p class="text-muted mb-0">{{ trans('accounting.quick_filters') }}</p>
                    </div>


                    <div class="d-flex gap-2">
                        <button data-bs-toggle="modal" data-bs-target="#addIncomeModal" class="btn btn-primary">
                            <i class="ri-add-line align-bottom me-1"></i> {{ trans('accounting.add_new_income') }}
                        </button>
                    </div>
                </div>


                {{-- KPI Cards (محسنة بالألوان + اليوم/الشهر) --}}
                <div class="row mt-3 g-3">
                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-primary text-primary">
                                            <i class="ri-file-list-3-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('accounting.total_records') }}</p>
                                        <h4 class="mb-0">{{ $total }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-success text-success">
                                            <i class="ri-check-double-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('accounting.not_cancelled') }}</p>
                                        <h4 class="mb-0">{{ $notCancelled }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-danger text-danger">
                                            <i class="ri-close-circle-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('accounting.cancelled') }}</p>
                                        <h4 class="mb-0">{{ $cancelled }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-warning text-warning">
                                            <i class="ri-money-dollar-circle-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('accounting.total_amount') }}</p>
                                        <h4 class="mb-0">{{ number_format($totalAmount, 2) }}</h4>
                                        <small class="text-muted d-block">
                                            {{ trans('accounting.today_amount') }}: {{ number_format($todayAmount, 2) }}
                                        </small>
                                        <small class="text-muted d-block">
                                            {{ trans('accounting.this_month_amount') }}: {{ number_format($thisMonthAmount, 2) }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <hr class="mt-4 mb-0">
            </div>


            <div class="card-body pt-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label mb-1">{{ trans('accounting.search') }}</label>
                        <input type="text" id="incomeSearch" class="form-control" placeholder="{{ trans('accounting.search_here') }}">
                    </div>


                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('accounting.filter_cancelled') }}</label>
                        <select id="filterCancelled" class="form-select select2" data-placeholder="{{ trans('accounting.all') }}">
                            <option value="">{{ trans('accounting.all') }}</option>
                            <option value="0">{{ trans('accounting.not_cancelled') }}</option>
                            <option value="1">{{ trans('accounting.cancelled') }}</option>
                        </select>
                    </div>


                    <div class="col-md-6 col-lg-5">
                        <label class="form-label mb-1">{{ trans('accounting.filter_branches') }}</label>
                        <select id="filterBranches" class="form-select select2" multiple data-placeholder="{{ trans('accounting.filter_branches') }}">
                            @foreach($BranchesList as $b)
                                <option value="{{ $b->id }}">{{ method_exists($b,'getTranslation') ? $b->getTranslation('name', app()->getLocale()) : $branchName($b->name) }}</option>
                            @endforeach
                        </select>
                    </div>


                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('accounting.date_from') }}</label>
                        <input type="date" id="filterDateFrom" class="form-control">
                    </div>


                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('accounting.date_to') }}</label>
                        <input type="date" id="filterDateTo" class="form-control">
                    </div>


                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('accounting.filter_income_type') }}</label>
                        <select id="filterType" class="form-select select2" data-placeholder="{{ trans('accounting.all') }}">
                            <option value="">{{ trans('accounting.all') }}</option>
                            @foreach($IncomeTypesList as $t)
                                <option value="{{ $t->id }}">{{ $t->getTranslation('name', app()->getLocale()) }}</option>
                            @endforeach
                        </select>
                    </div>


                    <div class="col-12 text-end">
                        <button type="button" id="clearFilters" class="btn btn-soft-secondary">
                            <i class="ri-refresh-line align-bottom me-1"></i> {{ trans('accounting.clear_filters') }}
                        </button>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-12">
        <div class="card">


            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">{{ trans('accounting.income_list') }}</h5>
                </div>
            </div>


            <div class="card-body">


                @if (Session::has('success'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button>
                        <strong>Success !</strong> {{ session('success') }}
                    </div>
                @endif


                @if (Session::has('error'))
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button>
                        <strong>Error !</strong> {{ session('error') }}
                    </div>
                @endif


                <table id="example" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ trans('accounting.id') }}</th>
                            <th>{{ trans('accounting.income_type') }}</th>
                            <th>{{ trans('accounting.branch') }}</th>
                            <th>{{ trans('accounting.income_date') }}</th>
                            <th>{{ trans('accounting.amount') }}</th>
                            <th>{{ trans('accounting.paymentmethod') }}</th>
                            <th>{{ trans('accounting.payer') }}</th>
                            <th>{{ trans('accounting.received_by') }}</th>
                            <th>{{ trans('accounting.description') }}</th>
                            <th>{{ trans('accounting.cancelled_status') }}</th>
                            <th>{{ trans('accounting.create_date') }}</th>
                            <th>{{ trans('accounting.action') }}</th>
                        </tr>
                    </thead>


                    <tbody>
                        @php $i=0; @endphp
                        @foreach($Incomes as $row)
                            @php
                                $i++;

                                $typeId = (int)($row->income_type_id ?? 0);
                                $branchId = (int)($row->branchid ?? 0);
                                $cancelToken = $row->iscancelled ? 1 : 0;
                                $dateToken = optional($row->incomedate)->format('Y-m-d');

                                $typeName = $row->type ? $row->type->getTranslation('name', app()->getLocale()) : '-';

                                $branchText = '-';
                                if ($row->branch) {
                                    $branchText = method_exists($row->branch,'getTranslation')
                                        ? $row->branch->getTranslation('name', app()->getLocale())
                                        : $branchName($row->branch->name ?? '');
                                }

                                $receivedBy = $row->receivedByEmployee
                                    ? ($row->receivedByEmployee->fullname ?? ($row->receivedByEmployee->full_name ?? '-'))
                                    : '-';
                            @endphp


                            <tr>
                                <td>{{ $i }}</td>
                                <td>{{ $row->id }}</td>


                                <td>
                                    <span class="d-none">__TYPE__{{ $typeId }}__</span>
                                    {{ $typeName }}
                                </td>


                                <td>
                                    <span class="d-none">__BRANCH__{{ $branchId }}__</span>
                                    {{ $branchText }}
                                </td>


                                <td>
                                    <span class="d-none">__DATE__{{ $dateToken }}__</span>
                                    {{ $dateToken }}
                                </td>


                                <td>{{ number_format((float)$row->amount, 2) }}</td>
                                <td>{{ trans('accounting.pm_'.$row->paymentmethod) }}</td>


                                <td>
                                    <div class="fw-semibold">{{ $row->payername ?? '-' }}</div>
                                    <small class="text-muted d-block">{{ $row->payerphone ?? '-' }}</small>
                                </td>


                                <td>{{ $receivedBy }}</td>


                                <td class="text-truncate" style="max-width:240px;">
                                    {{ $row->description ?? '-' }}
                                </td>


                                <td>
                                    <span class="d-none">__CANCEL__{{ $cancelToken }}__</span>
                                    @if($row->iscancelled)
                                        <span class="badge bg-danger">{{ trans('accounting.cancelled') }}</span>
                                    @else
                                        <span class="badge bg-success">{{ trans('accounting.not_cancelled') }}</span>
                                    @endif
                                </td>


                                <td>{{ $row->created_at }}</td>


                                <td>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button data-bs-toggle="modal" data-bs-target="#editIncomeModal{{ $row->id }}" class="dropdown-item">
                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                                </button>
                                            </li>
                                            <li>
                                                <button data-bs-toggle="modal" data-bs-target="#deleteIncomeModal{{ $row->id }}" class="dropdown-item">
                                                    <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
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


<!-- Add Income Modal -->
<div id="addIncomeModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0">{{ trans('accounting.add_new_income') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>


            <div class="modal-body">
                <form action="{{ route('income.store') }}" method="post" id="addIncomeForm">
                    @csrf


                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.branch') }}</label>
                            <select name="branchid" class="form-select select2 js-branch" data-placeholder="{{ trans('accounting.choose') }}" required>
                                <option value="">{{ trans('accounting.choose') }}</option>
                                @foreach($BranchesList as $b)
                                    <option value="{{ $b->id }}">{{ method_exists($b,'getTranslation') ? $b->getTranslation('name', app()->getLocale()) : $branchName($b->name) }}</option>
                                @endforeach
                            </select>
                        </div>


                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.income_type') }}</label>
                            <select name="income_type_id" class="form-select select2" data-placeholder="{{ trans('accounting.choose') }}" required>
                                <option value="">{{ trans('accounting.choose') }}</option>
                                @foreach($IncomeTypesList as $t)
                                    <option value="{{ $t->id }}">{{ $t->getTranslation('name', app()->getLocale()) }}</option>
                                @endforeach
                            </select>
                        </div>


                        <div class="col-md-4">
                            <label class="form-label">{{ trans('accounting.income_date') }}</label>
                            <input type="date" name="incomedate" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>


                        <div class="col-md-4">
                            <label class="form-label">{{ trans('accounting.amount') }}</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>


                        <div class="col-md-4">
                            <label class="form-label">{{ trans('accounting.paymentmethod') }}</label>
                            <select name="paymentmethod" class="form-select select2" data-placeholder="{{ trans('accounting.choose') }}" required>
                                <option value="">{{ trans('accounting.choose') }}</option>
                                <option value="cash">{{ trans('accounting.pm_cash') }}</option>
                                <option value="card">{{ trans('accounting.pm_card') }}</option>
                                <option value="transfer">{{ trans('accounting.pm_transfer') }}</option>
                                <option value="instapay">{{ trans('accounting.pm_instapay') }}</option>
                                <option value="ewallet">{{ trans('accounting.pm_ewallet') }}</option>
                                <option value="cheque">{{ trans('accounting.pm_cheque') }}</option>
                                <option value="other">{{ trans('accounting.pm_other') }}</option>
                            </select>
                        </div>


                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.received_by') }}</label>
                            <select name="receivedbyemployeeid" class="form-select select2 js-employee" data-placeholder="{{ trans('accounting.choose') }}">
                                <option value="">{{ trans('accounting.choose_branch_first') }}</option>
                            </select>
                        </div>


                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.payer_name') }}</label>
                            <input type="text" name="payername" class="form-control">
                        </div>


                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.payer_phone') }}</label>
                            <input type="text" name="payerphone" class="form-control">
                        </div>


                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.description') }}</label>
                            <input type="text" name="description" class="form-control">
                        </div>


                        <div class="col-md-12">
                            <label class="form-label">{{ trans('accounting.notes') }}</label>
                            <textarea name="notes" rows="2" class="form-control"></textarea>
                        </div>
                    </div>


                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">{{ trans('accounting.submit') }}</button>
                    </div>
                </form>
            </div>


        </div>
    </div>
</div>


{{-- Edit/Delete Modals OUTSIDE table --}}
@foreach($Incomes as $row)
    <div id="editIncomeModal{{ $row->id }}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 overflow-hidden">
                <div class="modal-header p-3">
                    <h4 class="card-title mb-0">{{ trans('accounting.update_income') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>


                <div class="modal-body">
                    <form action="{{ route('income.update', $row->id) }}" method="post">
                        @csrf
                        {{ method_field('patch') }}


                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.branch') }}</label>
                                <select name="branchid"
                                        class="form-select select2 js-branch"
                                        data-branch-initial="{{ (int)($row->branchid ?? 0) }}"
                                        data-placeholder="{{ trans('accounting.choose') }}"
                                        required>
                                    <option value="">{{ trans('accounting.choose') }}</option>
                                    @foreach($BranchesList as $b)
                                        <option value="{{ $b->id }}" {{ (int)$row->branchid === (int)$b->id ? 'selected' : '' }}>
                                            {{ method_exists($b,'getTranslation') ? $b->getTranslation('name', app()->getLocale()) : $branchName($b->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.income_type') }}</label>
                                <select name="income_type_id" class="form-select select2" data-placeholder="{{ trans('accounting.choose') }}" required>
                                    <option value="">{{ trans('accounting.choose') }}</option>
                                    @foreach($IncomeTypesList as $t)
                                        <option value="{{ $t->id }}" {{ (int)$row->income_type_id === (int)$t->id ? 'selected' : '' }}>
                                            {{ $t->getTranslation('name', app()->getLocale()) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="col-md-4">
                                <label class="form-label">{{ trans('accounting.income_date') }}</label>
                                <input type="date" name="incomedate" class="form-control" value="{{ optional($row->incomedate)->format('Y-m-d') }}" required>
                            </div>


                            <div class="col-md-4">
                                <label class="form-label">{{ trans('accounting.amount') }}</label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ $row->amount }}" required>
                            </div>


                            <div class="col-md-4">
                                <label class="form-label">{{ trans('accounting.paymentmethod') }}</label>
                                <select name="paymentmethod" class="form-select select2" data-placeholder="{{ trans('accounting.choose') }}" required>
                                    <option value="">{{ trans('accounting.choose') }}</option>
                                    <option value="cash" {{ $row->paymentmethod === 'cash' ? 'selected' : '' }}>{{ trans('accounting.pm_cash') }}</option>
                                    <option value="card" {{ $row->paymentmethod === 'card' ? 'selected' : '' }}>{{ trans('accounting.pm_card') }}</option>
                                    <option value="transfer" {{ $row->paymentmethod === 'transfer' ? 'selected' : '' }}>{{ trans('accounting.pm_transfer') }}</option>
                                    <option value="instapay" {{ $row->paymentmethod === 'instapay' ? 'selected' : '' }}>{{ trans('accounting.pm_instapay') }}</option>
                                    <option value="ewallet" {{ $row->paymentmethod === 'ewallet' ? 'selected' : '' }}>{{ trans('accounting.pm_ewallet') }}</option>
                                    <option value="cheque" {{ $row->paymentmethod === 'cheque' ? 'selected' : '' }}>{{ trans('accounting.pm_cheque') }}</option>
                                    <option value="other" {{ $row->paymentmethod === 'other' ? 'selected' : '' }}>{{ trans('accounting.pm_other') }}</option>
                                </select>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.received_by') }}</label>
                                <select name="receivedbyemployeeid"
                                        class="form-select select2 js-employee"
                                        data-placeholder="{{ trans('accounting.choose') }}"
                                        data-selected="{{ (int)($row->receivedbyemployeeid ?? 0) }}">
                                    <option value="">{{ trans('accounting.choose_branch_first') }}</option>
                                </select>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.payer_name') }}</label>
                                <input type="text" name="payername" class="form-control" value="{{ $row->payername }}">
                            </div>


                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.payer_phone') }}</label>
                                <input type="text" name="payerphone" class="form-control" value="{{ $row->payerphone }}">
                            </div>


                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.description') }}</label>
                                <input type="text" name="description" class="form-control" value="{{ $row->description }}">
                            </div>


                            <div class="col-md-12">
                                <label class="form-label">{{ trans('accounting.notes') }}</label>
                                <textarea name="notes" rows="2" class="form-control">{{ $row->notes }}</textarea>
                            </div>


                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="iscancelled" value="1" id="iscancelled_{{ $row->id }}" {{ $row->iscancelled ? 'checked' : '' }}>
                                    <label class="form-check-label" for="iscancelled_{{ $row->id }}">
                                        {{ trans('accounting.mark_as_cancelled') }}
                                    </label>
                                </div>
                            </div>


                            <div class="col-12">
                                <label class="form-label">{{ trans('accounting.cancel_reason') }}</label>
                                <textarea name="cancelreason" rows="2" class="form-control">{{ $row->cancelreason }}</textarea>
                                <small class="text-muted">{{ trans('accounting.cancel_reason_hint') }}</small>
                            </div>
                        </div>


                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">{{ trans('accounting.submit') }}</button>
                        </div>
                    </form>
                </div>


            </div>
        </div>
    </div>


    <div id="deleteIncomeModal{{ $row->id }}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mt-2">
                        <h4>{{ trans('accounting.delete_confirm_title') }}</h4>
                        <p class="text-muted">{{ trans('accounting.delete_confirm_text') }}</p>


                        <form action="{{ route('income.destroy', $row->id) }}" method="post">
                            @csrf
                            {{ method_field('delete') }}


                            <button type="submit" class="btn btn-danger">{{ trans('accounting.delete') }}</button>
                        </form>
                    </div>


                </div>
            </div>
        </div>
    </div>
@endforeach


<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ === 'undefined') return;


    var isRtl = $('html').attr('dir') === 'rtl';
    var locale = ($('html').attr('lang') || '').toLowerCase();


    function initSelect2($el, dropdownParent){
        if (!$.fn || !$.fn.select2) return;
        if (!$el || !$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) return;


        var placeholder = $el.data('placeholder') || '{{ trans('accounting.choose') }}';


        $el.select2({
            width: '100%',
            placeholder: placeholder,
            allowClear: true,
            dir: isRtl ? 'rtl' : 'ltr',
            dropdownParent: dropdownParent ? $(dropdownParent) : $(document.body),
            language: (locale === 'ar' ? 'ar' : undefined)
        });
    }


    function refreshSelect2($el){
        if (!$el || !$el.length) return;
        $el.trigger('change');
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.trigger('change.select2');
        }
    }


    function setSelect2ValueSilent($select, value){
        $select.data('silent-change', 1);
        $select.val(String(value || '')).trigger('change.select2');
        setTimeout(function(){ $select.data('silent-change', 0); }, 0);
    }


    // init select2 خارج المودال
    $('select.select2').each(function(){
        var $el = $(this);
        if (!$el.closest('.modal').length) {
            initSelect2($el, null);
        }
    });


    // Employees AJAX
    var token = '{{ csrf_token() }}';
    var employeesUrl = '{{ route('income.actions.employees_by_branch') }}';


    function nextEmpSeq(formEl){
        var v = parseInt(formEl.dataset.empReqSeq || '0', 10);
        v = isNaN(v) ? 1 : (v + 1);
        formEl.dataset.empReqSeq = String(v);
        return v;
    }


    function getBranchId(branchSel){
        if (!branchSel) return '';
        var v1 = '';
        try { v1 = $(branchSel).val(); } catch(e) { v1 = ''; }
        var v2 = branchSel.value || '';
        var v3 = branchSel.getAttribute('data-branch-initial') || branchSel.dataset.branchInitial || '';
        return String(v1 || v2 || v3 || '').trim();
    }


    async function loadEmployeesForForm(formEl, opts){
        opts = opts || {};
        if (!formEl) return;


        var branchSel = formEl.querySelector('.js-branch');
        var empSel = formEl.querySelector('.js-employee');
        if (!branchSel || !empSel) return;


        var seq = nextEmpSeq(formEl);


        var branchId = getBranchId(branchSel);
        var modalParent = formEl.closest('.modal');
        var $emp = $(empSel);


        $emp.empty();


        if (!branchId) {
            $emp.append(new Option('{{ trans('accounting.choose_branch_first') }}', ''));
            $emp.val('');
            refreshSelect2($emp);
            return;
        }


        $emp.append(new Option('{{ trans('accounting.choose') }}', ''));


        try {
            const res = await fetch(employeesUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ branchid: branchId })
            });


            const json = await res.json();
            if (String(seq) !== String(formEl.dataset.empReqSeq || '')) return;


            if (!json || !json.ok) {
                $emp.val('');
                refreshSelect2($emp);
                return;
            }


            var seen = new Set();
            (json.data || []).forEach(function(row){
                var id = row && (row.id ?? row.value);
                if (id === null || id === undefined) return;


                var sid = String(id);
                if (seen.has(sid)) return;
                seen.add(sid);


                $emp.append(new Option(row.text || row.name || sid, sid));
            });


            var selectedId = '';
            if (opts.keepSelected) {
                selectedId = empSel.getAttribute('data-selected') || '';
            }


            if (selectedId && selectedId !== '0') {
                $emp.val(String(selectedId));
            } else {
                $emp.val('');
            }


            initSelect2($emp, modalParent);
            refreshSelect2($emp);


        } catch (e) {
            if (String(seq) !== String(formEl.dataset.empReqSeq || '')) return;
            $emp.val('');
            refreshSelect2($emp);
        }
    }


    // shown modal: init select2 + (edit) ثبت الفرع من initial + تحميل الموظفين
    $(document).on('shown.bs.modal', '.modal', function () {
        var modalEl = this;


        $(modalEl).find('select.select2').each(function(){
            initSelect2($(this), modalEl);
        });


        var formEl = modalEl.querySelector('form');
        if (!formEl) return;


        var branchSel = formEl.querySelector('.js-branch');
        if (branchSel) {
            var initial = branchSel.getAttribute('data-branch-initial') || '';
            if (initial && String($(branchSel).val() || '') !== String(initial)) {
                setSelect2ValueSilent($(branchSel), initial);
            }
        }


        var hasBranch = branchSel && String($(branchSel).val() || '').trim() !== '';
        if (hasBranch && formEl.querySelector('.js-employee')) {
            loadEmployeesForForm(formEl, { keepSelected: true });
        }
    });


    // change branch: تحميل الموظفين دائمًا (مع تجاهل silent-change)
    $(document).on('change', '.js-branch', function () {
        var $branch = $(this);
        if ($branch.data('silent-change')) return;


        var form = this.closest('form');
        if (!form) return;


        var empSel = form.querySelector('.js-employee');
        if (empSel) empSel.setAttribute('data-selected', '');


        loadEmployeesForForm(form, { keepSelected: false });
    });


    // DataTable + Filters
    if ($.fn && $.fn.DataTable) {
        var table = $('#example').DataTable();


        var typeColIndex = 2;
        var branchColIndex = 3;
        var dateColIndex = 4;
        var cancelColIndex = 10;


        $('#incomeSearch').on('keyup change', function () {
            table.search(this.value).draw();
        });


        $('#filterCancelled').on('change', function () {
            var v = $(this).val();
            if (v === '') {
                table.column(cancelColIndex).search('').draw();
                return;
            }
            table.column(cancelColIndex).search('__CANCEL__' + v + '__', false, false).draw();
        });


        $('#filterType').on('change', function () {
            var v = $(this).val();
            if (v === '') {
                table.column(typeColIndex).search('').draw();
                return;
            }
            table.column(typeColIndex).search('__TYPE__' + v + '__', false, false).draw();
        });


        $('#filterBranches').on('change', function () {
            var selected = $(this).val() || [];
            if (selected.length === 0) {
                table.column(branchColIndex).search('').draw();
                return;
            }
            var tokens = selected.map(function(id){ return '__BRANCH__' + id + '__'; });
            var regex = '(?:' + tokens.join('|') + ')';
            table.column(branchColIndex).search(regex, true, false).draw();
        });


        function parseDate(v){
            if (!v) return null;
            var d = new Date(v);
            if (isNaN(d.getTime())) return null;
            d.setHours(0,0,0,0);
            return d;
        }


        $.fn.dataTable.ext.search.push(function (settings, data) {
            if (settings.nTable.id !== 'example') return true;


            var from = parseDate($('#filterDateFrom').val());
            var to = parseDate($('#filterDateTo').val());


            var cellDateStr = (data[dateColIndex] || '').toString().trim();
            var cellDate = parseDate(cellDateStr);


            if (!from && !to) return true;
            if (!cellDate) return false;


            if (from && cellDate < from) return false;
            if (to && cellDate > to) return false;


            return true;
        });


        $('#filterDateFrom, #filterDateTo').on('change', function () {
            table.draw();
        });


        $('#clearFilters').on('click', function () {
            $('#incomeSearch').val('');
            $('#filterCancelled').val('').trigger('change');
            $('#filterType').val('').trigger('change');
            $('#filterDateFrom').val('');
            $('#filterDateTo').val('');
            $('#filterBranches').val(null).trigger('change');


            table.search('');
            table.column(cancelColIndex).search('');
            table.column(typeColIndex).search('');
            table.column(branchColIndex).search('');
            table.draw();
        });
    }
});
</script>


@endsection
