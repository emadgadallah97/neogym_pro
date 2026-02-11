@extends('layouts.master_table')
@section('title')
{{ trans('main_trans.title') }}
@stop

@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('accounting.expenses') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('accounting.accounting') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('accounting.expenses') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@php
    $branchName = function ($nameJsonOrText) {
        $decoded = json_decode($nameJsonOrText, true);
        if (is_array($decoded)) {
            return $decoded[app()->getLocale()] ?? ($decoded['ar'] ?? ($decoded['en'] ?? ''));
        }
        return $nameJsonOrText;
    };

    $totalExpenses = $Expenses->count();
    $cancelledExpenses = $Expenses->where('iscancelled', 1)->count();
    $notCancelledExpenses = $Expenses->where('iscancelled', 0)->count();

    $totalAmount = (float) $Expenses->where('iscancelled', 0)->sum('amount');

    $today = \Carbon\Carbon::today()->format('Y-m-d');
    $todayAmount = (float) $Expenses
        ->where('iscancelled', 0)
        ->filter(function($x) use ($today){
            return optional($x->expensedate)->format('Y-m-d') === $today;
        })
        ->sum('amount');

    $monthStart = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
    $monthEnd = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');
    $thisMonthAmount = (float) $Expenses
        ->where('iscancelled', 0)
        ->filter(function($x) use ($monthStart, $monthEnd) {
            $d = optional($x->expensedate)->format('Y-m-d');
            return $d && $d >= $monthStart && $d <= $monthEnd;
        })
        ->sum('amount');
@endphp

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body pb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">{{ trans('accounting.expenses_kpis') }}</h5>
                        <p class="text-muted mb-0">{{ trans('accounting.quick_filters') }}</p>
                    </div>

                    <div class="d-flex gap-2">
                        <button data-bs-toggle="modal" data-bs-target="#addExpenseModal" class="btn btn-primary">
                            <i class="ri-add-line align-bottom me-1"></i> {{ trans('accounting.add_new_expense') }}
                        </button>
                    </div>
                </div>

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
                                        <p class="text-muted mb-1">{{ trans('accounting.total_expenses') }}</p>
                                        <h4 class="mb-0">{{ $totalExpenses }}</h4>
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
                                        <h4 class="mb-0">{{ $notCancelledExpenses }}</h4>
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
                                        <h4 class="mb-0">{{ $cancelledExpenses }}</h4>
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
                                            — {{ trans('accounting.this_month_amount') }}: {{ number_format($thisMonthAmount, 2) }}
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
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                            <input type="text" id="expenseSearch" class="form-control" placeholder="{{ trans('accounting.search_here') }}">
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label mb-1">{{ trans('accounting.filter_cancelled') }}</label>
                        <select id="filterCancelled" class="form-select select2" data-placeholder="{{ trans('accounting.all') }}">
                            <option value="">{{ trans('accounting.all') }}</option>
                            <option value="0">{{ trans('accounting.not_cancelled') }}</option>
                            <option value="1">{{ trans('accounting.cancelled') }}</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('accounting.filter_expense_type') }}</label>
                        <select id="filterType" class="form-select select2" data-placeholder="{{ trans('accounting.all') }}">
                            <option value="">{{ trans('accounting.all') }}</option>
                            @foreach($ExpensesTypes as $t)
                                <option value="{{ $t->id }}">{{ $t->getTranslation('name', app()->getLocale()) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('accounting.filter_branches') }}</label>
                        <select id="filterBranches" class="form-select select2" multiple data-placeholder="{{ trans('accounting.filter_branches') }}">
                            @foreach($BranchesList as $b)
                                <option value="{{ $b->id }}">{{ $branchName($b->name) }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ trans('accounting.multi_select_hint') }}</small>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('accounting.date_from') }}</label>
                        <input type="date" id="filterDateFrom" class="form-control">
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('accounting.date_to') }}</label>
                        <input type="date" id="filterDateTo" class="form-control">
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
                    <h5 class="card-title mb-0">{{ trans('accounting.expenses_list') }}</h5>

                    <div class="ms-3">
                        @if (Session::has('success'))
                            <div class="alert alert-success alert-dismissible mb-0 py-2 px-3" role="alert">
                                <button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button>
                                <strong>Success !</strong> {{ session('success') }}
                            </div>
                        @endif

                        @if (Session::has('error'))
                            <div class="alert alert-danger alert-dismissible mb-0 py-2 px-3" role="alert">
                                <button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button>
                                <strong>Error !</strong> {{ session('error') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body">
                <table id="example" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th data-ordering="false">{{ trans('accounting.sr_no') }}</th>
                            <th data-ordering="false">{{ trans('accounting.id') }}</th>
                            <th data-ordering="false">{{ trans('accounting.expense_type') }}</th>
                            <th data-ordering="false">{{ trans('accounting.branch') }}</th>
                            <th data-ordering="false">{{ trans('accounting.expense_date') }}</th>
                            <th data-ordering="false">{{ trans('accounting.amount') }}</th>
                            <th data-ordering="false">{{ trans('accounting.recipient') }}</th>
                            <th data-ordering="false">{{ trans('accounting.disbursed_by') }}</th>
                            <th data-ordering="false">{{ trans('accounting.description') }}</th>
                            <th data-ordering="false">{{ trans('accounting.cancelled_status') }}</th>
                            <th>{{ trans('accounting.create_date') }}</th>
                            <th>{{ trans('accounting.action') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $i=0; ?>
                        @foreach($Expenses as $Expense)
                            <?php $i++; ?>
                            @php
                                $typeId = (int) ($Expense->expensestypeid ?? 0);
                                $branchId = (int) ($Expense->branchid ?? 0);
                                $cancelToken = $Expense->iscancelled ? 1 : 0;
                                $dateToken = optional($Expense->expensedate)->format('Y-m-d');

                                $typeName = $Expense->type ? $Expense->type->getTranslation('name', app()->getLocale()) : '-';

                                $branchText = '-';
                                if ($Expense->branch) {
                                    if (method_exists($Expense->branch, 'getTranslation')) {
                                        $branchText = $Expense->branch->getTranslation('name', app()->getLocale());
                                    } else {
                                        $branchText = $branchName($Expense->branch->name ?? '');
                                    }
                                }

                                $disburserName = $Expense->disburserEmployee ? ($Expense->disburserEmployee->full_name ?? '-') : '-';
                            @endphp

                            <tr>
                                <td>{{ $i }}</td>
                                <td>{{ $Expense->id }}</td>

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

                                <td>{{ number_format((float)$Expense->amount, 2) }}</td>

                                <td>
                                    <div class="fw-semibold">{{ $Expense->recipientname }}</div>
                                    <small class="text-muted d-block">{{ $Expense->recipientphone ?? '-' }}</small>
                                </td>

                                <td>{{ $disburserName }}</td>

                                <td class="text-truncate" style="max-width:240px;">
                                    {{ $Expense->description ?? '-' }}
                                </td>

                                <td>
                                    <span class="d-none">__CANCEL__{{ $cancelToken }}__</span>
                                    @if($Expense->iscancelled)
                                        <span class="badge bg-danger">{{ trans('accounting.cancelled') }}</span>
                                    @else
                                        <span class="badge bg-success">{{ trans('accounting.not_cancelled') }}</span>
                                    @endif
                                </td>

                                <td>{{ $Expense->created_at }}</td>

                                <td>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button
                                                    data-expense-id="{{ $Expense->id }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editExpenseModal{{ $Expense->id }}"
                                                    class="dropdown-item edit-item-btn js-open-edit-expense">
                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                                </button>
                                            </li>
                                            <li>
                                                <button data-bs-toggle="modal" data-bs-target="#deleteExpenseModal{{ $Expense->id }}" class="dropdown-item edit-item-btn">
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

{{-- Modals OUTSIDE table --}}
@foreach($Expenses as $Expense)
    <div id="editExpenseModal{{ $Expense->id }}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 overflow-hidden">
                <div class="modal-header p-3">
                    <h4 class="card-title mb-0">{{ trans('accounting.update_expense') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form action="{{ route('expenses.update','test') }}" method="post" class="expense-edit-form">
                        {{ method_field('patch') }}
                        @csrf

                        <input class="form-control" name="id" value="{{ $Expense->id }}" type="hidden">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.branch') }}</label>
                                <select name="branchid"
                                        class="form-select select2 js-branch"
                                        data-branch-initial="{{ (int)($Expense->branchid ?? 0) }}"
                                        data-placeholder="{{ trans('accounting.choose') }}"
                                        required>
                                    <option value="">{{ trans('accounting.choose') }}</option>
                                    @foreach($BranchesList as $b)
                                        <option value="{{ $b->id }}" {{ (int)$Expense->branchid === (int)$b->id ? 'selected' : '' }}>
                                            {{ $branchName($b->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.expense_type') }}</label>
                                <select name="expensestypeid" class="form-select select2" data-placeholder="{{ trans('accounting.choose') }}" required>
                                    <option value="">{{ trans('accounting.choose') }}</option>
                                    @foreach($ExpensesTypes as $t)
                                        <option value="{{ $t->id }}" {{ (int)$Expense->expensestypeid === (int)$t->id ? 'selected' : '' }}>
                                            {{ $t->getTranslation('name', app()->getLocale()) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ trans('accounting.expense_date') }}</label>
                                <input type="date" name="expensedate" class="form-control" value="{{ optional($Expense->expensedate)->format('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ trans('accounting.amount') }}</label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ $Expense->amount }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ trans('accounting.disbursed_by_employee') }}</label>
                                <select name="disbursedbyemployeeid"
                                        class="form-select select2 js-employee"
                                        data-placeholder="{{ trans('accounting.choose') }}"
                                        data-selected="{{ (int)($Expense->disbursedbyemployeeid ?? 0) }}">
                                    <option value="">{{ trans('accounting.choose_branch_first') }}</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.recipient_name') }}</label>
                                <input type="text" name="recipientname" class="form-control" value="{{ $Expense->recipientname }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.recipient_phone') }}</label>
                                <input type="text" name="recipientphone" class="form-control" value="{{ $Expense->recipientphone }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.recipient_national_id') }}</label>
                                <input type="text" name="recipientnationalid" class="form-control" value="{{ $Expense->recipientnationalid }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.description') }}</label>
                                <textarea name="description" rows="2" class="form-control">{{ $Expense->description }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">{{ trans('accounting.notes') }}</label>
                                <textarea name="notes" rows="2" class="form-control">{{ $Expense->notes }}</textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="iscancelled" value="1" id="iscancelled_edit_{{ $Expense->id }}" {{ $Expense->iscancelled ? 'checked' : '' }}>
                                    <label class="form-check-label" for="iscancelled_edit_{{ $Expense->id }}">
                                        {{ trans('accounting.mark_as_cancelled') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer px-0 pb-0">
                            <button type="submit" class="btn btn-primary">{{ trans('accounting.submit') }}</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <div id="deleteExpenseModal{{ $Expense->id }}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <lord-icon src="{{URL::asset('assets/images/icon/tdrtiskw.json')}}" trigger="loop"
                        colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px">
                    </lord-icon>

                    <div class="mt-4 pt-4">
                        <h4>{{ trans('accounting.massagedelete_d') }}!</h4>
                        <p class="text-muted">
                            {{ trans('accounting.massagedelete_p') }}
                            {{ $Expense->recipientname }} - {{ number_format((float)$Expense->amount, 2) }}
                        </p>

                        <form action="{{ route('expenses.destroy','test') }}" method="post">
                            {{ method_field('delete') }}
                            {{ csrf_field() }}
                            <input class="form-control" name="id" value="{{ $Expense->id }}" type="hidden">

                            <button class="btn btn-warning" data-bs-target="#secondmodal" data-bs-toggle="modal" data-bs-dismiss="modal">
                                {{ trans('accounting.massagedelete') }}
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endforeach

<div id="addExpenseModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0">{{ trans('accounting.add_new_expense') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form action="{{ route('expenses.store') }}" method="post" id="addExpenseForm">
                    {{ csrf_field() }}

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.branch') }}</label>
                            <select name="branchid" class="form-select select2 js-branch" data-placeholder="{{ trans('accounting.choose') }}" required>
                                <option value="">{{ trans('accounting.choose') }}</option>
                                @foreach($BranchesList as $b)
                                    <option value="{{ $b->id }}">{{ $branchName($b->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.expense_type') }}</label>
                            <select name="expensestypeid" class="form-select select2" data-placeholder="{{ trans('accounting.choose') }}" required>
                                <option value="">{{ trans('accounting.choose') }}</option>
                                @foreach($ExpensesTypes as $t)
                                    <option value="{{ $t->id }}">{{ $t->getTranslation('name', app()->getLocale()) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ trans('accounting.expense_date') }}</label>
                            <input type="date" name="expensedate" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ trans('accounting.amount') }}</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ trans('accounting.disbursed_by_employee') }}</label>
                            <select name="disbursedbyemployeeid" class="form-select select2 js-employee" data-placeholder="{{ trans('accounting.choose') }}">
                                <option value="">{{ trans('accounting.choose_branch_first') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.recipient_name') }}</label>
                            <input type="text" name="recipientname" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.recipient_phone') }}</label>
                            <input type="text" name="recipientphone" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.recipient_national_id') }}</label>
                            <input type="text" name="recipientnationalid" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.description') }}</label>
                            <textarea name="description" rows="2" class="form-control"></textarea>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ === 'undefined') return;

    var isRtl = $('html').attr('dir') === 'rtl';
    var locale = ($('html').attr('lang') || '').toLowerCase();

    var TXT_CHOOSE = @json(trans('accounting.choose'));
    var TXT_CHOOSE_BRANCH_FIRST = @json(trans('accounting.choose_branch_first'));

    function initSelect2($el, dropdownParent){
        if (!$.fn || !$.fn.select2) return;
        if (!$el || !$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) return;

        var placeholder = $el.data('placeholder') || TXT_CHOOSE;

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
        // يمنع handler change من الاشتغال أثناء التعيين البرمجي
        $select.data('silent-change', 1);
        $select.val(String(value || '')).trigger('change.select2');
        setTimeout(function(){ $select.data('silent-change', 0); }, 0);
    }

    // init select2 outside modals
    $('select.select2').each(function(){
        var $el = $(this);
        if (!$el.closest('.modal').length) {
            initSelect2($el, null);
        }
    });

    var token = '{{ csrf_token() }}';
    var employeesUrl = '{{ route('expenses.actions.employees_by_branch') }}';

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
            $emp.append(new Option(TXT_CHOOSE_BRANCH_FIRST, ''));
            $emp.val('');
            refreshSelect2($emp);
            return;
        }

        $emp.append(new Option(TXT_CHOOSE, ''));

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

    // عند فتح أي مودال: init select2 + (لو تعديل) ثبّت قيمة الفرع ثم حمّل الموظفين
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
            // التثبيت هذا مفيد للتعديل فقط، الإنشاء لا يملك initial
            if (initial && String($(branchSel).val() || '') !== String(initial)) {
                setSelect2ValueSilent($(branchSel), initial);
            }
        }

        // لو يوجد فرع فعلي (مثلاً edit أو old values) حمّل الموظفين
        var hasBranch = branchSel && String($(branchSel).val() || '').trim() !== '';
        if (hasBranch && formEl.querySelector('.js-employee')) {
            loadEmployeesForForm(formEl, { keepSelected: true });
        }
    });

    // ✅ إصلاح الإنشاء: تحميل الموظفين عند تغيير الفرع بدون الاعتماد على originalEvent
    $(document).on('change', '.js-branch', function () {
        var $branch = $(this);

        if ($branch.data('silent-change')) return;

        var form = this.closest('form');
        if (!form) return;

        var empSel = form.querySelector('.js-employee');
        if (empSel) empSel.setAttribute('data-selected', '');

        loadEmployeesForForm(form, { keepSelected: false });
    });

    // DataTable init (اختياري)
    if ($.fn && $.fn.DataTable) {
        $('#example').DataTable();
    }
});
</script>

@endsection
