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
                <i class="mdi mdi-cog-outline me-1"></i>
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
            <i class="ri-apps-2-line align-middle me-1"></i>
            {{ trans('settings_trans.system_settings') }}
        </p>
    </div>
</div>

{{-- ══════════════════════════════════════
    Settings Cards
    ══════════════════════════════════════ --}}
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xxl-4 g-3 gy-4">

    {{-- ① الجنسيات --}}
    {{-- <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <i class="ri-flag-line fs-36"></i>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">{{ trans('settings_trans.nationalities_settings') }}</h6>
    <p class="text-muted mb-3 small">
        {{ trans('settings_trans.nationalities_settings_desc') ?? 'إدارة الجنسيات المتاحة في النظام' }}
    </p>
    <div class="mt-auto">
        <a href="{{ url('/nationalities_settings') }}" class="btn btn-soft-info w-100 btn-sm">
            <i class="ri-flag-line align-bottom me-1"></i>
            {{ trans('settings_trans.open') ?? 'فتح' }}
        </a>
    </div>
</div>
</div>
</div> --}}

    {{-- ② الأماكن --}}
    @can('settings_places_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-map-pin-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.Place_settings') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.places_settings_desc') ?? 'إدارة الأماكن والمواقع المرتبطة بالنظام' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ url('/places_settings') }}" class="btn btn-soft-primary w-100 btn-sm">
                        <i class="ri-map-pin-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ③ العملات --}}
    @can('settings_currencies_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-money-dollar-circle-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.currency_settings') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.currency_settings_desc') ?? 'إدارة العملات وأسعار الصرف المستخدمة' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ url('/currencies') }}" class="btn btn-soft-warning w-100 btn-sm">
                        <i class="ri-money-dollar-circle-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ④ الإعدادات العامة --}}
    @can('settings_general_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-secondary text-secondary rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-settings-3-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.general_settings') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.general_settings_desc') ?? 'ضبط الإعدادات العامة للنظام والمنشأة' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ url('/general_settings') }}" class="btn btn-soft-secondary w-100 btn-sm">
                        <i class="ri-settings-3-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑤ الفروع --}}
    @can('settings_branches_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-building-2-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.branches_settings') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.branches_settings_desc') ?? 'إضافة وإدارة فروع المنشأة وبياناتها' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('branches.index') }}" class="btn btn-soft-info w-100 btn-sm">
                        <i class="ri-building-2-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑥ الوظائف --}}
    @can('settings_jobs_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-dark text-dark rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-briefcase-4-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.jobs_settings') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.jobs_settings_desc') ?? 'إدارة المسميات الوظيفية والدرجات الوظيفية' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('jobs.index') }}" class="btn btn-soft-dark w-100 btn-sm">
                        <i class="ri-briefcase-4-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑦ أنواع الاشتراكات --}}
    @can('settings_subscriptions_types_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-vip-crown-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.subscriptions_types') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.subscriptions_types_desc') ?? 'تعريف باقات وأنواع الاشتراكات المتاحة' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('subscriptions_types.index') }}" class="btn btn-soft-warning w-100 btn-sm">
                        <i class="ri-vip-crown-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑧ إعدادات العمولات --}}
    @can('settings_commissions_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-danger text-danger rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-percent-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.commission_settings') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.commission_settings_desc') ?? 'ضبط نسب وقواعد احتساب العمولات' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('commission_settings.index') }}" class="btn btn-soft-danger w-100 btn-sm">
                        <i class="ri-percent-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑨ تسعير حصص المدربين --}}
    @can('settings_trainer_pricing_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-success text-success rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-user-star-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.trainer_session_pricing') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.trainer_session_pricing_desc') ?? 'تحديد أسعار الحصص التدريبية لكل مدرب' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('trainer_session_pricing.index') }}" class="btn btn-soft-success w-100 btn-sm">
                        <i class="ri-user-star-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑩ أنواع المصروفات --}}
    @can('settings_expenses_types_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-danger text-danger rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-arrow-up-circle-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.expenses_types') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.expenses_types_desc') ?? 'تصنيف وإدارة أنواع المصروفات في النظام' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('expenses_types.index') }}" class="btn btn-soft-danger w-100 btn-sm">
                        <i class="ri-arrow-up-circle-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑪ أنواع الإيرادات --}}
    @can('settings_income_types_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-success text-success rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-arrow-down-circle-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.income_types') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.income_types_desc') ?? 'تصنيف وإدارة أنواع الإيرادات في النظام' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('income_types.index') }}" class="btn btn-soft-success w-100 btn-sm">
                        <i class="ri-arrow-down-circle-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑫ طرق التعرف علينا --}}
    @can('settings_referral_sources_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-compass-discover-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('settings_trans.referral_sources_settings') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('settings_trans.referral_sources_settings_desc') }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('referral_sources.index') }}" class="btn btn-soft-primary w-100 btn-sm">
                        <i class="ri-compass-discover-line align-bottom me-1"></i>
                        {{ trans('settings_trans.open') ?? 'فتح' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

</div>

@endsection