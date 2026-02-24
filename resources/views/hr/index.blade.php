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
            z-index: 2;
        }
        .report-icon-sm {
            width: 70px;
            height: 70px;
        }
        .report-card .card-title {
            font-weight: 600;
        }
        .hr-stat-card {
            border-left: 3px solid transparent;
            transition: all 0.2s ease-in-out;
        }
        .hr-stat-card:hover {
            transform: translateY(-2px);
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
                    <i class="mdi mdi-account-group me-1"></i>
                    {{ trans('hr.title') }}
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="javascript:void(0);">{{ trans('hr.title') }}</a>
                        </li>
                        <li class="breadcrumb-item active">
                            {{ trans('hr.title') }}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════
         Stats Summary
    ══════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- إجمالي الموظفين --}}
        <div class="col-6 col-sm-6 col-md-3">
            <div class="card hr-stat-card border-0 shadow-sm h-100" style="border-left-color:#4bb3fd!important">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="avatar-sm flex-shrink-0">
                        <div class="avatar-title bg-soft-info text-info rounded-circle fs-20">
                            <i class="ri-group-line"></i>
                        </div>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-muted text-truncate mb-0 small">{{ trans('hr.total_employees') ?? 'إجمالي الموظفين' }}</p>
                        <h5 class="mb-0 font">{{ $stats['total_employees'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>

        {{-- حاضرون اليوم --}}
        <div class="col-6 col-sm-6 col-md-3">
            <div class="card hr-stat-card border-0 shadow-sm h-100" style="border-left-color:#0ab39c!important">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="avatar-sm flex-shrink-0">
                        <div class="avatar-title bg-soft-success text-success rounded-circle fs-20">
                            <i class="ri-user-follow-line"></i>
                        </div>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-muted text-truncate mb-0 small">{{ trans('hr.present_today') ?? 'حاضرون اليوم' }}</p>
                        <h5 class="mb-0 font">{{ $stats['present_today'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>

        {{-- سلف معلقة --}}
        <div class="col-6 col-sm-6 col-md-3">
            <div class="card hr-stat-card border-0 shadow-sm h-100" style="border-left-color:#f7b84b!important">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="avatar-sm flex-shrink-0">
                        <div class="avatar-title bg-soft-warning text-warning rounded-circle fs-20">
                            <i class="ri-time-line"></i>
                        </div>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-muted text-truncate mb-0 small">{{ trans('hr.pending_advances') ?? 'سلف معلقة' }}</p>
                        <h5 class="mb-0 font">{{ $stats['pending_advances'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>

        {{-- رواتب هذا الشهر --}}
        <div class="col-6 col-sm-6 col-md-3">
            <div class="card hr-stat-card border-0 shadow-sm h-100" style="border-left-color:#405189!important">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="avatar-sm flex-shrink-0">
                        <div class="avatar-title bg-soft-primary text-primary rounded-circle fs-20">
                            <i class="ri-bank-card-line"></i>
                        </div>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-muted text-truncate mb-0 small">{{ trans('hr.month_payrolls') ?? 'رواتب الشهر' }}</p>
                        <h5 class="mb-0 font">{{ $stats['pending_payrolls'] ?? 0 }}</h5>
                    </div>
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
                {{ trans('hr.programs_label') ?? 'برامج الموارد البشرية' }}
            </p>
        </div>
    </div>

    {{-- ══════════════════════════════════════
         HR Cards
    ══════════════════════════════════════ --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xxl-4 g-3 gy-4">

        {{-- ① الحضور والانصراف --}}
<div class="col">
    <div class="card report-card h-100 shadow-sm border-0">
        <div class="card-body d-flex flex-column text-center p-3">
            <div class="report-icon-wrapper mx-auto mb-2">
                <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                    <i class="ri-login-circle-line fs-36"></i>
                </div>
            </div>
            <h6 class="card-title mb-2 font">{{ trans('hr.attendances') }}</h6>
            <p class="text-muted mb-3 small">{{ trans('hr.attendances_desc') ?? 'تسجيل ومتابعة حضور وانصراف الموظفين' }}</p>
            <div class="mt-auto">
                <a href="{{ route('attendance.index') }}" class="btn btn-soft-info w-100 btn-sm">
                    <i class="ri-calendar-check-line align-bottom me-1"></i>
                    {{ trans('hr.open') ?? 'فتح' }}
                </a>
            </div>
        </div>
    </div>
</div>


        {{-- ② السلف --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <i class="ri-hand-coin-line fs-36"></i>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">{{ trans('hr.advances') ?? 'السلف' }}</h6>
                    <p class="text-muted mb-3 small">{{ trans('hr.advances_desc') ?? 'إدارة طلبات السلف وتتبع الأقساط الشهرية' }}</p>
                    <div class="mt-auto">
                        <a href="{{ route('advances.index') }}" class="btn btn-soft-warning w-100 btn-sm">
                            <i class="ri-hand-coin-line align-bottom me-1"></i>
                            {{ trans('hr.open') ?? 'فتح' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ③ الخصومات والجزاءات --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-danger text-danger rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <i class="ri-file-warning-line fs-36"></i>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">{{ trans('hr.deductions') ?? 'الخصومات والجزاءات' }}</h6>
                    <p class="text-muted mb-3 small">{{ trans('hr.deductions_desc') ?? 'تسجيل الخصومات والجزاءات وتطبيقها على الرواتب' }}</p>
                    <div class="mt-auto">
                        <a href="{{ route('deductions.index') }}" class="btn btn-soft-danger w-100 btn-sm">
                            <i class="ri-file-warning-line align-bottom me-1"></i>
                            {{ trans('hr.open') ?? 'فتح' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ④ الإضافي والمكافآت --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-success text-success rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <i class="ri-gift-2-line fs-36"></i>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">{{ trans('hr.overtime_allowances') ?? 'الإضافي والمكافآت' }}</h6>
                    <p class="text-muted mb-3 small">{{ trans('hr.overtime_allowances_desc') ?? 'إدارة ساعات العمل الإضافي والمكافآت والبدلات' }}</p>
                    <div class="mt-auto">
                        <a href="{{ route('overtime.index') }}" class="btn btn-soft-success w-100 btn-sm">
                            <i class="ri-gift-2-line align-bottom me-1"></i>
                            {{ trans('hr.open') ?? 'فتح' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ⑤ كشف وصرف الرواتب --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <i class="ri-money-dollar-circle-line fs-36"></i>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">{{ trans('hr.payrolls') ?? 'كشف وصرف الرواتب' }}</h6>
                    <p class="text-muted mb-3 small">{{ trans('hr.payrolls_desc') ?? 'إعداد كشوف الرواتب الشهرية واعتمادها وصرفها' }}</p>
                    <div class="mt-auto">
                        <a href="{{ route('payrolls.index') }}" class="btn btn-soft-primary w-100 btn-sm">
                            <i class="ri-money-dollar-circle-line align-bottom me-1"></i>
                            {{ trans('hr.open') ?? 'فتح' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ⑥ أجهزة الحضور --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-secondary text-secondary rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <i class="ri-fingerprint-2-line fs-36"></i>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">{{ trans('hr.devices') ?? 'أجهزة الحضور' }}</h6>
                    <p class="text-muted mb-3 small">{{ trans('hr.devices_desc') ?? 'إدارة أجهزة البصمة ومزامنة بيانات الحضور' }}</p>
                    <div class="mt-auto">
                        <a href="{{ route('devices.index') }}" class="btn btn-soft-secondary w-100 btn-sm">
                            <i class="ri-fingerprint-2-line align-bottom me-1"></i>
                            {{ trans('hr.open') ?? 'فتح' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ⑦ تقارير الموارد البشرية --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <i class="ri-bar-chart-grouped-line fs-36"></i>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">{{ trans('hr.reports') ?? 'تقارير الموارد البشرية' }}</h6>
                    <p class="text-muted mb-3 small">{{ trans('hr.reports_desc') ?? 'تقارير تحليلية شاملة للحضور والرواتب والموظفين' }}</p>
                    <div class="mt-auto">
                        <a href="{{ route('reports.index') }}" class="btn btn-soft-info w-100 btn-sm">
                            <i class="ri-bar-chart-grouped-line align-bottom me-1"></i>
                            {{ trans('hr.open') ?? 'فتح' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ⑧ الموظفون --}}
        <div class="col">
            <div class="card report-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column text-center p-3">
                    <div class="report-icon-wrapper mx-auto mb-2">
                        <div class="avatar-title bg-soft-dark text-dark rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                            <i class="ri-group-line fs-36"></i>
                        </div>
                    </div>
                    <h6 class="card-title mb-2 font">{{ trans('hr.employees') ?? 'الموظفون' }}</h6>
                    <p class="text-muted mb-3 small">{{ trans('hr.employees_desc') ?? 'إدارة بيانات الموظفين والوظائف والفروع' }}</p>
                    <div class="mt-auto">
                        <a href="{{ route('employees.index') }}" class="btn btn-soft-dark w-100 btn-sm">
                            <i class="ri-group-line align-bottom me-1"></i>
                            {{ trans('hr.open') ?? 'فتح' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
