@extends('layouts.master_table')

@section('title')
{{ trans('main_trans.title') }}
@endsection

@push('css')
<style>
    .report-card {
        transition: all 0.2s ease-in-out;
    }

    .report-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.4rem 0.8rem rgba(15, 23, 42, 0.12);
        position: relative;
        z-index: 2;
    }

    .report-icon-sm {
        width: 70px;
        height: 70px;
    }

    .report-card .card-title {
        font-weight: 600;
    }

    .section-label {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
    }
</style>
@endpush

@section('content')

{{-- ══════════════════════════════════════
    Page Title
    ══════════════════════════════════════ --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="mdi mdi-calculator-variant me-1"></i>
                {{ trans('accounting.programs') }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0);">{{ trans('accounting.programs') }}</a>
                    </li>
                    <li class="breadcrumb-item active">
                        {{ trans('accounting.programs_accounting') }}
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════
    Section Label
    ══════════════════════════════════════ --}}
<div class="row mb-3">
    <div class="col-12">
        <p class="text-uppercase text-muted section-label mb-0">
            <i class="ri-apps-2-line align-middle me-1"></i>
            {{ trans('accounting.programs_accounting') ?? 'برامج المحاسبة' }}
        </p>
    </div>
</div>

{{-- ══════════════════════════════════════
    Accounting Cards
    ══════════════════════════════════════ --}}
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xxl-4 g-3 gy-4">

    {{-- ① المصروفات --}}
    @can('expenses_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div
                        class="avatar-title bg-soft-danger text-danger rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-arrow-up-circle-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('accounting.expenses') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('accounting.expenses_desc') ?? 'تسجيل ومتابعة مصروفات المنشأة وتصنيفها' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('expenses.index') }}" class="btn btn-soft-danger w-100 btn-sm">
                        <i class="ri-arrow-up-circle-line align-bottom me-1"></i>
                        {{ trans('hr.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ② الإيرادات --}}
    @can('income_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div
                        class="avatar-title bg-soft-success text-success rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-arrow-down-circle-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('accounting.income') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('accounting.income_desc') ?? 'متابعة إيرادات المنشأة من مختلف المصادر' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('income.index') }}" class="btn btn-soft-success w-100 btn-sm">
                        <i class="ri-arrow-down-circle-line align-bottom me-1"></i>
                        {{ trans('hr.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ③ العمولات --}}
    @can('commissions_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div
                        class="avatar-title bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-percent-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('accounting.commissions') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('accounting.commissions_desc') ?? 'إدارة العمولات وتوزيعها على الموظفين والمندوبين' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('commissions.index') }}" class="btn btn-soft-warning w-100 btn-sm">
                        <i class="ri-percent-line align-bottom me-1"></i>
                        {{ trans('hr.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ④ الخزينة --}}
    @can('treasury.view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div
                        class="avatar-title bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-safe-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('accounting.treasury') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('accounting.treasury_desc') }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('treasury.index') }}" class="btn btn-soft-primary w-100 btn-sm">
                        <i class="ri-safe-line align-bottom me-1"></i>
                        {{ trans('hr.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

</div>

@endsection