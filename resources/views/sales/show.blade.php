@extends('layouts.master_table')

@section('title')
{{ trans('sales.sales') }}
@stop

@section('content')
@php
    $s = $subscription;
@endphp

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">
                {{ trans('sales.subscription_show_title') ?? 'تفاصيل الاشتراك' }} #{{ $s->id }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('sales.index') }}">{{ trans('sales.sales') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ trans('sales.view') ?? 'عرض' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@include('sales.partials.subscription_details', ['subscription' => $subscription, 'inModal' => false])
@endsection
