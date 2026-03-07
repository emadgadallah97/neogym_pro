@php
// تمرير المتغيرات من index
$Branches = $Branches ?? [];
$Members = $Members ?? [];
$Plans = $Plans ?? [];
$Types = $Types ?? [];
$Coaches = $Coaches ?? [];
$Employees= $Employees ?? [];
@endphp

<div class="row">
    {{-- ======= العمود الأيسر: البيانات والنموذج ======= --}}
    <div class="col-lg-8">

        {{-- Card 1: البيانات الأساسية --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-primary bg-opacity-10 border-0">
                <h6 class="card-title mb-0 text-primary">
                    <i class="mdi mdi-card-account-details-outline me-1"></i>
                    {{ trans('sales.tab_basic') ?? 'البيانات الأساسية' }}
                </h6>
            </div>
            <div class="card-body">
                @include('sales._form_basic', [
                'Branches' => $Branches,
                'Members' => $Members,
                'Plans' => $Plans,
                'Types' => $Types,
                ])
            </div>
        </div>

        {{-- Card 2: المدرب والجلسات --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-success bg-opacity-10 border-0">
                <h6 class="card-title mb-0 text-success">
                    <i class="mdi mdi-dumbbell me-1"></i>
                    {{ trans('sales.tab_trainer_pt') ?? 'المدرب والجلسات الخاصة' }}
                </h6>
            </div>
            <div class="card-body">
                @include('sales._form_trainer_pt', [
                'Coaches' => $Coaches,
                ])
            </div>
        </div>

        {{-- Card 3: العروض والكوبونات --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-warning bg-opacity-10 border-0">
                <h6 class="card-title mb-0 text-warning">
                    <i class="mdi mdi-sale me-1"></i>
                    {{ trans('sales.tab_discounts') ?? 'العروض والكوبونات' }}
                </h6>
            </div>
            <div class="card-body">
                @include('sales._form_discounts')
            </div>
        </div>

    </div>

    {{-- ======= العمود الأيمن: ملخص الدفع والعمولة (Sticky) ======= --}}
    <div class="col-lg-4">
        <div style="position: sticky; top: 80px;">
            @include('sales._form_payment', [
            'Employees' => $Employees,
            ])
        </div>
    </div>
</div>