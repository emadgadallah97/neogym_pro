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
                <i class="ri-bar-chart-box-line me-1"></i>
                {{ trans('reports.reports') }}
            </h4>
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

{{-- ══════════════════════════════════════
    Section Label
    ══════════════════════════════════════ --}}
<div class="row mb-3">
    <div class="col-12">
        <p class="text-uppercase text-muted section-label mb-0">
            <i class="ri-apps-2-line align-middle me-1"></i>
            {{ trans('reports.reports') }}
        </p>
    </div>
</div>

{{-- ══════════════════════════════════════
    Reports Cards
    ══════════════════════════════════════ --}}
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xxl-4 g-3 gy-4">

    {{-- ① تقرير الحضور --}}
    @can('reports_attendances_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-login-circle-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('reports.attendances_report') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('reports.attendances_report_desc') ?? 'عرض وتحليل سجلات حضور وانصراف الموظفين' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('attendances_report.index') }}" class="btn btn-soft-info w-100 btn-sm">
                        <i class="ri-login-circle-line align-bottom me-1"></i>
                        {{ trans('reports.open_report') ?? 'فتح التقرير' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ② تقرير الموظفين --}}
    @can('reports_employees_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-dark text-dark rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-group-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('reports.employees_report') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('reports.employees_report_desc') ?? 'بيانات شاملة عن الموظفين والوظائف والفروع' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('employees_report.index') }}" class="btn btn-soft-dark w-100 btn-sm">
                        <i class="ri-group-line align-bottom me-1"></i>
                        {{ trans('reports.open_report') ?? 'فتح التقرير' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ③ تقرير الأعضاء --}}
    @can('reports_members_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-user-heart-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('reports.members_report_title') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('reports.members_report_desc') ?? 'إحصائيات وبيانات الأعضاء المسجلين في النادي' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('members_report.index') }}" class="btn btn-soft-primary w-100 btn-sm">
                        <i class="ri-user-heart-line align-bottom me-1"></i>
                        {{ trans('reports.open_report') ?? 'فتح التقرير' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ④ تقرير الخطط والاشتراكات --}}
    @can('reports_subscriptions_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-vip-crown-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('reports.subscriptions_report_title') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('reports.subscriptions_report_desc') ?? 'متابعة الاشتراكات النشطة والمنتهية والباقات' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('subscriptions_report.index') }}" class="btn btn-soft-warning w-100 btn-sm">
                        <i class="ri-vip-crown-line align-bottom me-1"></i>
                        {{ trans('reports.open_report') ?? 'فتح التقرير' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑤ تقرير المبيعات --}}
    @can('reports_sales_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-success text-success rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-line-chart-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('reports.sales_report_title') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('reports.sales_report_desc') ?? 'تحليل المبيعات والإيرادات خلال فترات زمنية محددة' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('sales_report.index') }}" class="btn btn-soft-success w-100 btn-sm">
                        <i class="ri-line-chart-line align-bottom me-1"></i>
                        {{ trans('reports.open_report') ?? 'فتح التقرير' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑥ تقرير المدفوعات --}}
    @can('reports_payments_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-secure-payment-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('reports.payments_report_title') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('reports.payments_report_desc') ?? 'سجل المدفوعات والمقبوضات وطرق الدفع' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('payments_report.index') }}" class="btn btn-soft-primary w-100 btn-sm">
                        <i class="ri-secure-payment-line align-bottom me-1"></i>
                        {{ trans('reports.open_report') ?? 'فتح التقرير' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑦ تقرير العمولات --}}
    @can('reports_commissions_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-danger text-danger rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-percent-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('reports.commissions_report_title') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('reports.commissions_report_desc') ?? 'تفاصيل العمولات المحتسبة لكل موظف ومندوب' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('commissions_report.index') }}" class="btn btn-soft-danger w-100 btn-sm">
                        <i class="ri-percent-line align-bottom me-1"></i>
                        {{ trans('reports.open_report') ?? 'فتح التقرير' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ⑧ تقرير الاشتراكات الخاصة (PT) --}}
    @can('reports_pt_addons_view')
    <div class="col">
        <div class="card report-card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column text-center p-3">
                <div class="report-icon-wrapper mx-auto mb-2">
                    <div class="avatar-title bg-soft-secondary text-secondary rounded-circle d-flex align-items-center justify-content-center report-icon-sm">
                        <i class="ri-user-star-line fs-36"></i>
                    </div>
                </div>
                <h6 class="card-title mb-2 font">{{ trans('reports.pt_addons_report_title') }}</h6>
                <p class="text-muted mb-3 small">
                    {{ trans('reports.pt_addons_report_desc') ?? 'تقرير الحصص الخاصة مع المدربين الشخصيين' }}
                </p>
                <div class="mt-auto">
                    <a href="{{ route('pt_addons_report.index') }}" class="btn btn-soft-secondary w-100 btn-sm">
                        <i class="ri-user-star-line align-bottom me-1"></i>
                        {{ trans('reports.open_report') ?? 'فتح التقرير' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

</div>

@endsection