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
                <i class="ri-map-pin-line me-1"></i>
                {{ trans('settings_trans.settings') }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0);">{{ trans('settings_trans.settings') }}</a>
                    </li>
                    <li class="breadcrumb-item active">
                        {{ trans('settings_trans.system_settings') }}
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
            <i class="ri-earth-line align-middle me-1"></i>
            {{ trans('settings_trans.Place_settings') ?? 'إعدادات الأماكن' }}
        </p>
    </div>
</div>

{{-- ══════════════════════════════════════
    Places Cards
    ══════════════════════════════════════ --}}
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-2 row-cols-lg-4 g-3 gy-4">

    {{-- ① الدول --}}
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-earth-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.countries') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.countries_desc') ?? 'إدارة قائمة الدول المتاحة في النظام' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ url('/countries') }}" class="btn btn-soft-primary w-100 btn-sm">
                        <i class="ri-earth-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ② المحافظات --}}
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-map-2-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.governorate') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.governorate_desc') ?? 'إدارة المحافظات التابعة لكل دولة' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ url('/government') }}" class="btn btn-soft-info w-100 btn-sm">
                        <i class="ri-map-2-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ③ المدن --}}
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-building-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.city') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.city_desc') ?? 'إدارة المدن التابعة لكل محافظة' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ url('/city') }}" class="btn btn-soft-warning w-100 btn-sm">
                        <i class="ri-building-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ④ المناطق --}}
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-success text-success rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-map-pin-2-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.area') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.area_desc') ?? 'إدارة المناطق والأحياء التابعة لكل مدينة' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ url('/area') }}" class="btn btn-soft-success w-100 btn-sm">
                        <i class="ri-map-pin-2-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection