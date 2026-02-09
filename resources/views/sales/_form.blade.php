@php
    // تمرير المتغيرات من index
    $Branches = $Branches ?? [];
    $Members  = $Members ?? [];
    $Plans    = $Plans ?? [];
    $Types    = $Types ?? [];
    $Coaches  = $Coaches ?? [];
    $Employees= $Employees ?? [];
@endphp

<ul class="nav nav-pills nav-pills-custom mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab_basic" type="button">
            <i class="mdi mdi-card-account-details-outline"></i> {{ trans('sales.tab_basic') ?? 'البيانات الأساسية' }}
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_trainer_pt" type="button">
            <i class="mdi mdi-dumbbell"></i> {{ trans('sales.tab_trainer_pt') ?? 'المدرب والجلسات' }}
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_discounts" type="button">
            <i class="mdi mdi-sale"></i> {{ trans('sales.tab_discounts') ?? 'العروض والكوبونات' }}
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_payment" type="button">
            <i class="mdi mdi-cash-multiple"></i> {{ trans('sales.tab_payment') ?? 'الدفع والعمولة' }}
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab_basic" role="tabpanel">
        @include('sales._form_basic', [
            'Branches' => $Branches,
            'Members' => $Members,
            'Plans' => $Plans,
            'Types' => $Types,
        ])
    </div>

    <div class="tab-pane fade" id="tab_trainer_pt" role="tabpanel">
        @include('sales._form_trainer_pt', [
            'Coaches' => $Coaches,
        ])
    </div>

    <div class="tab-pane fade" id="tab_discounts" role="tabpanel">
        @include('sales._form_discounts')
    </div>

    <div class="tab-pane fade" id="tab_payment" role="tabpanel">
        @include('sales._form_payment', [
            'Employees' => $Employees,
        ])
    </div>
</div>
