@extends('layouts.master_table')

@section('title')
{{ trans('sales.current_subscriptions') ?? 'الاشتراكات الحالية' }}
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('sales.current_subscriptions') ?? 'الاشتراكات الحالية' }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('sales.index') }}">{{ trans('sales.sales') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ trans('sales.current_subscriptions') ?? 'الاشتراكات الحالية' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- زر العودة لصفحة المبيعات --}}
<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('sales.index') }}" class="btn btn-soft-primary">
            <i class="ri-arrow-left-line me-1"></i>
            {{ trans('sales.back_to_sales') ?? 'العودة لصفحة المبيعات' }}
        </a>
    </div>
</div>

@include('sales.partials.current_subscriptions', [
'Branches' => $Branches,
'Plans' => $Plans,
'Types' => $Types,
'Employees' => $Employees,
])
@endsection