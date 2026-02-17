@extends('layouts.master')

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
            z-index: 2; /* يمنع تداخل الـ hover بصريًا */
        }
        .report-icon-sm {
            width: 70px;
            height: 70px;
        }
        .report-card .card-title {
            font-weight: 600;
        }
    </style>
@endpush

@section('content')

    {{-- start page title --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font">{{ trans('reports.reports') }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="javascript:void(0);">{{ trans('reports.reports') }}</a>
                        </li>
                        <li class="breadcrumb-item active">
                            {{ trans('reports.reports') }}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    {{-- end page title --}}

    {{-- cards --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xxl-4 g-3 gy-4">

        {{-- تقرير الحضور --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <lord-icon
                                src="{{ URL::asset('assets/images/icon/oaflahpk.json') }}"
                                trigger="loop"
                                delay="500"
                                colors="primary:#4bb3fd"
                                style="width:60px;height:60px">
                            </lord-icon>
                        </div>
                    </div>

                    <h6 class="card-title mb-2 font">
                        {{ trans('reports.attendances_report') }}
                    </h6>

                    <div class="mt-auto">
                        <a href="{{ route('attendances_report.index') }}" class="btn btn-soft-info w-100 btn-sm">
                            <i class="ri-bar-chart-2-line align-bottom me-1"></i>
                            {{ trans('reports.open_report') ?? trans('reports.report') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- تقرير الموظفين --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <lord-icon
                                src="{{ URL::asset('assets/images/icon/oaflahpk.json') }}"
                                trigger="loop"
                                delay="500"
                                colors="primary:#4bb3fd"
                                style="width:60px;height:60px">
                            </lord-icon>
                        </div>
                    </div>

                    <h6 class="card-title mb-2 font">
                        {{ trans('reports.employees_report') }}
                    </h6>

                    <div class="mt-auto">
                        <a href="{{ route('employees_report.index') }}" class="btn btn-soft-info w-100 btn-sm">
                            <i class="ri-bar-chart-2-line align-bottom me-1"></i>
                            {{ trans('reports.open_report') ?? trans('reports.report') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- تقرير الاعضاء --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <lord-icon
                                src="{{ URL::asset('assets/images/icon/oaflahpk.json') }}"
                                trigger="loop"
                                delay="500"
                                colors="primary:#4bb3fd"
                                style="width:60px;height:60px">
                            </lord-icon>
                        </div>
                    </div>

                    <h6 class="card-title mb-2 font">
                        {{ trans('reports.members_report_title') }}
                    </h6>

                    <div class="mt-auto">
                        <a href="{{ route('members_report.index') }}" class="btn btn-soft-info w-100 btn-sm">
                            <i class="ri-bar-chart-2-line align-bottom me-1"></i>
                            {{ trans('reports.open_report') ?? trans('reports.report') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- تقرير الخطط والاشتراكات --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <lord-icon
                                src="{{ URL::asset('assets/images/icon/oaflahpk.json') }}"
                                trigger="loop"
                                delay="500"
                                colors="primary:#4bb3fd"
                                style="width:60px;height:60px">
                            </lord-icon>
                        </div>
                    </div>

                    <h6 class="card-title mb-2 font">
                        {{ trans('reports.subscriptions_report_title') }}
                    </h6>

                    <div class="mt-auto">
                        <a href="{{ route('subscriptions_report.index') }}" class="btn btn-soft-info w-100 btn-sm">
                            <i class="ri-bar-chart-2-line align-bottom me-1"></i>
                            {{ trans('reports.open_report') ?? trans('reports.report') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- تقرير المبيعات --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <lord-icon
                                src="{{ URL::asset('assets/images/icon/oaflahpk.json') }}"
                                trigger="loop"
                                delay="500"
                                colors="primary:#4bb3fd"
                                style="width:60px;height:60px">
                            </lord-icon>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">
                        {{ trans('reports.sales_report_title') }}
                    </h6>
                    <div class="mt-auto">
                        <a href="{{ route('sales_report.index') }}" class="btn btn-soft-info w-100 btn-sm">
                            <i class="ri-bar-chart-2-line align-bottom me-1"></i>
                            {{ trans('reports.open_report') ?? trans('reports.report') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        {{-- تقرير المفوعات --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <lord-icon
                                src="{{ URL::asset('assets/images/icon/oaflahpk.json') }}"
                                trigger="loop"
                                delay="500"
                                colors="primary:#4bb3fd"
                                style="width:60px;height:60px">
                            </lord-icon>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">
                        {{ trans('reports.payments_report_title') }}
                    </h6>
                    <div class="mt-auto">
                        <a href="{{ route('payments_report.index') }}" class="btn btn-soft-info w-100 btn-sm">
                            <i class="ri-bar-chart-2-line align-bottom me-1"></i>
                            {{ trans('reports.open_report') ?? trans('reports.report') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
                {{-- تقرير العمولات --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <lord-icon
                                src="{{ URL::asset('assets/images/icon/oaflahpk.json') }}"
                                trigger="loop"
                                delay="500"
                                colors="primary:#4bb3fd"
                                style="width:60px;height:60px">
                            </lord-icon>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">
                        {{ trans('reports.commissions_report_title') }}
                    </h6>
                    <div class="mt-auto">
                        <a href="{{ route('commissions_report.index') }}" class="btn btn-soft-info w-100 btn-sm">
                            <i class="ri-bar-chart-2-line align-bottom me-1"></i>
                            {{ trans('reports.open_report') ?? trans('reports.report') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
                {{-- تقرير الاشتراكات الخاصه --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <lord-icon
                                src="{{ URL::asset('assets/images/icon/oaflahpk.json') }}"
                                trigger="loop"
                                delay="500"
                                colors="primary:#4bb3fd"
                                style="width:60px;height:60px">
                            </lord-icon>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">
                        {{ trans('reports.pt_addons_report_title') }}
                    </h6>
                    <div class="mt-auto">
                        <a href="{{ route('pt_addons_report.index') }}" class="btn btn-soft-info w-100 btn-sm">
                            <i class="ri-bar-chart-2-line align-bottom me-1"></i>
                            {{ trans('reports.open_report') ?? trans('reports.report') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        {{-- Coming soon --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-success text-success rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <i class="ri-line-chart-line fs-24"></i>
                        </div>
                    </div>

                    <h6 class="card-title mb-2 font">
                        {{ trans('reports.placeholder_title_3') ?? 'Report 4' }}
                    </h6>

                    <p class="text-muted mb-3 small">
                        {{ trans('reports.placeholder_desc_3') ?? '' }}
                    </p>

                    <div class="mt-auto">
                        <button type="button" class="btn btn-soft-success w-100 btn-sm" disabled>
                            <i class="ri-lock-line align-bottom me-1"></i>
                            {{ trans('reports.coming_soon') ?? 'Coming soon' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
