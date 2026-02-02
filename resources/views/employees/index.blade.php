@extends('layouts.master_table')
@section('title')
{{ trans('main_trans.title') }}
@stop

@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('employees.employees') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('settings_trans.settings') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('employees.employees') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">

            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0">{{ trans('employees.employees_list') }}</h5>
                        <small class="text-muted">
                            {{ trans('employees.compensation_hint') }}
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <button data-bs-toggle="modal" data-bs-target="#addEmployeeModal" class="btn btn-primary waves-effect waves-light" type="button">
                            <i class="mdi mdi-account-plus-outline"></i> {{ trans('employees.add_new_employee') }}
                        </button>
                    </div>
                </div>

                {{-- Message --}}
                @if (Session::has('success'))
                    <div class="alert alert-success alert-dismissible mt-3" role="alert">
                        <button type="button" class="close" data-dismiss="alert">
                            <i class="fa fa-times"></i>
                        </button>
                        <strong>Success !</strong> {{ session('success') }}
                    </div>
                @endif

                @if (Session::has('error'))
                    <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                        <button type="button" class="close" data-dismiss="alert">
                            <i class="fa fa-times"></i>
                        </button>
                        <strong>Error !</strong> {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger mt-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @php
                $total = $Employees->count();

                // KPIs per Job (only jobs having employees)
                $jobCounts = $Employees->whereNotNull('job_id')->groupBy('job_id')->map(function ($items) {
                    return $items->count();
                });

                $kpiColors = ['primary','success','warning','info','secondary','danger','dark'];
                $kpiIconByColor = [
                    'primary' => 'mdi-briefcase-outline',
                    'success' => 'mdi-account-tie-outline',
                    'warning' => 'mdi-account-star-outline',
                    'info' => 'mdi-account-details-outline',
                    'secondary' => 'mdi-account-cog-outline',
                    'danger' => 'mdi-alert-outline',
                    'dark' => 'mdi-account-outline',
                ];
                $kpiIndex = 0;

                $activeText = trans('settings_trans.active');
                $inactiveText = trans('settings_trans.inactive');
            @endphp

            <div class="card-body">

                {{-- KPIs --}}
                <div class="row g-3 mb-3">
                    {{-- Total only --}}
                    <div class="col-md-4 col-xl-2">
                        <div class="card border border-primary bg-soft-primary mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted">{{ trans('employees.employees') }}</small>
                                        <h4 class="mb-0">{{ $total }}</h4>
                                    </div>
                                    <div class="avatar-sm">
                                        <span class="avatar-title rounded bg-primary">
                                            <i class="mdi mdi-account-group-outline"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Per Job (only if count > 0) --}}
                    @foreach($Jobs as $Job)
                        @php
                            $count = (int)($jobCounts[$Job->id] ?? 0);
                            if ($count <= 0) continue;

                            $color = $kpiColors[$kpiIndex % count($kpiColors)];
                            $icon = $kpiIconByColor[$color] ?? 'mdi-briefcase-outline';
                            $kpiIndex++;
                        @endphp

                        <div class="col-md-4 col-xl-2">
                            <div class="card border border-{{ $color }} bg-soft-{{ $color }} mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <small class="text-muted">{{ $Job->getTranslation('name','ar') }}</small>
                                            <h4 class="mb-0">{{ $count }}</h4>
                                        </div>
                                        <div class="avatar-sm">
                                            <span class="avatar-title rounded bg-{{ $color }}">
                                                <i class="mdi {{ $icon }}"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Search + Filters Bar --}}
                <div class="card border shadow-none mb-3">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label mb-1">{{ trans('employees.search') ?? 'بحث' }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="mdi mdi-magnify"></i>
                                    </span>
                                    <input type="text" class="form-control" id="employee_global_search"
                                           placeholder="{{ trans('employees.search_hint') ?? 'ابحث بالاسم / الهاتف / الكود' }}">
                                </div>
                                <small class="text-muted">{{ trans('employees.search_note') ?? 'يبحث داخل الاسم/الكود/الهاتف وباقي الأعمدة.' }}</small>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label mb-1">{{ trans('employees.job') }}</label>
                                <select class="form-select" id="filter_job">
                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                    @foreach($Jobs as $Job)
                                        <option value="{{ $Job->getTranslation('name','ar') }}">{{ $Job->getTranslation('name','ar') }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label mb-1">{{ trans('employees.branches') }}</label>
                                <select class="form-select" id="filter_branch">
                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                    @foreach($Branches as $Branch)
                                        <option value="{{ $Branch->getTranslation('name','ar') }}">{{ $Branch->getTranslation('name','ar') }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label mb-1">{{ trans('employees.compensation_type') }}</label>
                                <select class="form-select" id="filter_compensation">
                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                    <option value="{{ trans('employees.salary_only') }}">{{ trans('employees.salary_only') }}</option>
                                    <option value="{{ trans('employees.commission_only') }}">{{ trans('employees.commission_only') }}</option>
                                    <option value="{{ trans('employees.salary_and_commission') }}">{{ trans('employees.salary_and_commission') }}</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label mb-1">{{ trans('settings_trans.status') }}</label>
                                <select class="form-select" id="filter_status">
                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                    <option value="{{ $activeText }}">{{ $activeText }}</option>
                                    <option value="{{ $inactiveText }}">{{ $inactiveText }}</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between flex-wrap gap-2 mt-2">
                                    <div class="alert alert-info mb-0 py-2 px-3">
                                        <i class="mdi mdi-information-outline"></i>
                                        <strong>*</strong>
                                        {{ trans('employees.required_fields_note') ?? 'الحقول المطلوبة: الاسم الأول، الاسم الأخير، الفروع، الفرع الأساسي، نوع التعويض.' }}
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-soft-secondary" id="btn_reset_filters">
                                            <i class="mdi mdi-filter-remove-outline"></i> {{ trans('employees.reset_filters') ?? 'مسح الفلاتر' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="employeesTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th data-ordering="false">{{ trans('settings_trans.sr_no') }}</th>
                                <th>{{ trans('employees.employee_code') }}</th>
                                <th>{{ trans('employees.full_name') }}</th>
                                <th>{{ trans('employees.job') }}</th>
                                <th>{{ trans('employees.branches') }}</th>
                                <th>{{ trans('employees.phone') }}</th>
                                <th>{{ trans('employees.compensation_type') }}</th>
                                <th>{{ trans('settings_trans.status') }}</th>
                                <th>{{ trans('settings_trans.create_date') }}</th>
                                <th data-ordering="false">{{ trans('settings_trans.action') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $i=0; ?>
                            @foreach($Employees as $Employee)
                                @php
                                    $primary = $Employee->branches->firstWhere('pivot.is_primary', 1);
                                    $branchesText = $primary ? $primary->getTranslation('name','ar') : '-';
                                    $branchesCount = $Employee->branches->count();

                                    $jobName = $Employee->job ? $Employee->job->getTranslation('name','ar') : '-';

                                    $photoUrl = !empty($Employee->photo) ? url($Employee->photo) : '';
                                    $statusText = $Employee->status ? $activeText : $inactiveText;
                                @endphp

                                <tr>
                                    <?php $i++; ?>
                                    <td>{{$i}}</td>
                                    <td>
                                        <span class="badge bg-dark-subtle text-dark">{{$Employee->code}}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-xs">
                                                @if(!empty($Employee->photo))
                                                    <img src="{{ $photoUrl }}" class="rounded-circle" style="width:34px;height:34px;object-fit:cover;">
                                                @else
                                                    <span class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                        <i class="mdi mdi-account-outline"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="lh-1">
                                                <div class="fw-semibold">{{$Employee->full_name}}</div>
                                                <small class="text-muted">{{ $Employee->email ?: '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-info text-info">{{ $jobName }}</span>
                                    </td>
                                    <td>
                                        @if($primary)
                                            <span class="badge bg-primary">{{ $primary->getTranslation('name','ar') }}</span>
                                        @else
                                            <span class="badge bg-soft-secondary text-secondary">-</span>
                                        @endif
                                        <small class="text-muted">({{ $branchesCount }})</small>
                                    </td>
                                    <td>{{$Employee->phone_1 ?: '-'}}</td>
                                    <td>
                                        <span class="badge bg-soft-warning text-warning">
                                            {{ trans('employees.'.$Employee->compensation_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($Employee->status)
                                            <span class="badge bg-success">{{ $activeText }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ $inactiveText }}</span>
                                        @endif
                                    </td>
                                    <td>{{$Employee->created_at}}</td>

                                    {{-- Actions (Improved) --}}
                                    <td>
                                        <div class="d-flex align-items-center gap-1 justify-content-end">

                                            {{-- View --}}
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-soft-info btn-icon"
                                                title="{{ trans('employees.view') ?? 'عرض' }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewEmployeeModal"
                                                data-id="{{ $Employee->id }}"
                                                data-code="{{ $Employee->code }}"
                                                data-name="{{ $Employee->full_name }}"
                                                data-job="{{ $jobName }}"
                                                data-branches="{{ $branchesText }}"
                                                data-branches-count="{{ $branchesCount }}"
                                                data-phone="{{ $Employee->phone_1 }}"
                                                data-email="{{ $Employee->email }}"
                                                data-gender="{{ $Employee->gender }}"
                                                data-birth="{{ $Employee->birth_date }}"
                                                data-type="{{ trans('employees.'.$Employee->compensation_type) }}"
                                                data-base-salary="{{ $Employee->base_salary }}"
                                                data-commission-percent="{{ $Employee->commission_percent }}"
                                                data-commission-fixed="{{ $Employee->commission_fixed }}"
                                                data-transfer-method="{{ $Employee->salary_transfer_method }}"
                                                data-transfer-details="{{ $Employee->salary_transfer_details }}"
                                                data-photo="{{ $photoUrl }}"
                                                data-status="{{ $statusText }}"
                                                data-specialization="{{ $Employee->specialization }}"
                                                data-years="{{ $Employee->years_experience }}"
                                                data-is-coach="{{ $Employee->is_coach }}"
                                                data-whatsapp="{{ $Employee->whatsapp }}"
                                                data-phone2="{{ $Employee->phone_2 }}"
                                                data-bio="{{ $Employee->bio }}"
                                            >
                                                <i class="mdi mdi-eye-outline"></i>
                                            </button>

                                            {{-- Edit --}}
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-soft-warning btn-icon btn-edit-employee"
                                                title="{{ trans('employees.edit') ?? 'تعديل' }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editEmployeeModal"
                                                data-id="{{ $Employee->id }}"
                                                data-first="{{ $Employee->first_name }}"
                                                data-last="{{ $Employee->last_name }}"
                                                data-job-id="{{ $Employee->job_id }}"
                                                data-gender="{{ $Employee->gender }}"
                                                data-birth="{{ $Employee->birth_date }}"
                                                data-phone1="{{ $Employee->phone_1 }}"
                                                data-phone2="{{ $Employee->phone_2 }}"
                                                data-whatsapp="{{ $Employee->whatsapp }}"
                                                data-email="{{ $Employee->email }}"
                                                data-specialization="{{ $Employee->specialization }}"
                                                data-years="{{ $Employee->years_experience }}"
                                                data-is-coach="{{ $Employee->is_coach }}"
                                                data-bio="{{ $Employee->bio }}"
                                                data-comp="{{ $Employee->compensation_type }}"
                                                data-base="{{ $Employee->base_salary }}"
                                                data-cp="{{ $Employee->commission_percent }}"
                                                data-cf="{{ $Employee->commission_fixed }}"
                                                data-tm="{{ $Employee->salary_transfer_method }}"
                                                data-td="{{ $Employee->salary_transfer_details }}"
                                                data-status="{{ $Employee->status }}"
                                                data-photo-url="{{ $photoUrl }}"
                                                data-branches='@json($Employee->branches->pluck("id")->values())'
                                                data-primary='{{ $primary ? $primary->id : "" }}'
                                            >
                                                <i class="mdi mdi-pencil-outline"></i>
                                            </button>

                                            {{-- More (Delete only to reduce mistakes) --}}
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ trans('employees.more') ?? 'المزيد' }}">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                                    <li>
                                                        <button
                                                            class="dropdown-item text-danger btn-delete-employee"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteEmployeeModal"
                                                            data-id="{{ $Employee->id }}"
                                                            data-name="{{ $Employee->full_name }}"
                                                        >
                                                            <i class="ri-delete-bin-6-line me-2"></i> {{ trans('employees.delete') ?? 'حذف' }}
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

            </div>

            {{-- View Modal --}}
            <div id="viewEmployeeModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 overflow-hidden">
                        <div class="modal-header p-3 bg-soft-primary">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-sm">
                                    <span class="avatar-title rounded bg-primary">
                                        <i class="mdi mdi-account-details-outline"></i>
                                    </span>
                                </div>
                                <div>
                                    <h4 class="card-title mb-0" id="view_title">{{ trans('employees.employees') }}</h4>
                                    <small class="text-muted" id="view_subtitle"></small>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card border mb-0">
                                        <div class="card-body text-center">
                                            <div class="mb-2">
                                                <img id="view_photo" src="" class="rounded" style="width:120px;height:120px;object-fit:cover;display:none;">
                                                <div id="view_photo_placeholder" class="avatar-lg mx-auto">
                                                    <span class="avatar-title rounded bg-soft-primary text-primary">
                                                        <i class="mdi mdi-account-outline fs-2"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="fw-semibold" id="view_name">-</div>
                                            <div class="text-muted small" id="view_code">-</div>
                                            <div class="mt-2">
                                                <span class="badge bg-soft-info text-info" id="view_job">-</span>
                                            </div>
                                            <div class="mt-2">
                                                <span class="badge bg-soft-success text-success" id="view_status">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab_contact" type="button" role="tab">
                                                <i class="mdi mdi-phone-outline"></i> {{ trans('employees.phone') }}
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_work" type="button" role="tab">
                                                <i class="mdi mdi-briefcase-outline"></i> {{ trans('employees.specialization') }}
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_finance" type="button" role="tab">
                                                <i class="mdi mdi-cash-multiple"></i> {{ trans('employees.compensation_type') }}
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="tab_contact" role="tabpanel">
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <div class="p-2 border rounded bg-light">
                                                        <small class="text-muted">{{ trans('employees.phone_1') }}</small>
                                                        <div class="fw-semibold" id="view_phone1">-</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="p-2 border rounded bg-light">
                                                        <small class="text-muted">{{ trans('employees.phone_2') }}</small>
                                                        <div class="fw-semibold" id="view_phone2">-</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="p-2 border rounded bg-light">
                                                        <small class="text-muted">{{ trans('employees.whatsapp') }}</small>
                                                        <div class="fw-semibold" id="view_whatsapp">-</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="p-2 border rounded bg-light">
                                                        <small class="text-muted">{{ trans('employees.email') }}</small>
                                                        <div class="fw-semibold" id="view_email">-</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row g-2 mt-2">
                                                <div class="col-md-6">
                                                    <div class="p-2 border rounded">
                                                        <small class="text-muted">{{ trans('employees.gender') }}</small>
                                                        <div class="fw-semibold" id="view_gender">-</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="p-2 border rounded">
                                                        <small class="text-muted">{{ trans('employees.birth_date') }}</small>
                                                        <div class="fw-semibold" id="view_birth">-</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row g-2 mt-2">
                                                <div class="col-md-12">
                                                    <div class="p-2 border rounded">
                                                        <small class="text-muted">{{ trans('employees.branches') }}</small>
                                                        <div class="fw-semibold">
                                                            <span id="view_branches">-</span>
                                                            <small class="text-muted">(<span id="view_branches_count">0</span>)</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="tab_work" role="tabpanel">
                                            <div class="row g-2">
                                                {{-- FIX: 4 + 4 + 4 to avoid wrapping --}}
                                                <div class="col-md-4">
                                                    <div class="p-2 border rounded bg-soft-info">
                                                        <small class="text-muted">{{ trans('employees.specialization') }}</small>
                                                        <div class="fw-semibold" id="view_specialization">-</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="p-2 border rounded bg-soft-info">
                                                        <small class="text-muted">هل الموظف مدرب</small>
                                                        <div class="fw-semibold" id="viewiscoach-div">-</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="p-2 border rounded bg-soft-info">
                                                        <small class="text-muted">{{ trans('employees.years_experience') }}</small>
                                                        <div class="fw-semibold" id="view_years">-</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-12">
                                                    <div class="p-2 border rounded">
                                                        <small class="text-muted">{{ trans('employees.bio') }}</small>
                                                        <div id="view_bio" class="fw-semibold">-</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="tab_finance" role="tabpanel">
                                            <div class="row g-2">
                                                <div class="col-md-12">
                                                    <div class="p-2 border rounded bg-soft-warning">
                                                        <small class="text-muted">{{ trans('employees.compensation_type') }}</small>
                                                        <div class="fw-semibold" id="view_type">-</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="p-2 border rounded">
                                                        <small class="text-muted">{{ trans('employees.base_salary') }}</small>
                                                        <div class="fw-semibold" id="view_base_salary">-</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="p-2 border rounded">
                                                        <small class="text-muted">{{ trans('employees.commission_percent') }}</small>
                                                        <div class="fw-semibold" id="view_cp">-</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="p-2 border rounded">
                                                        <small class="text-muted">{{ trans('employees.commission_fixed') }}</small>
                                                        <div class="fw-semibold" id="view_cf">-</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="p-2 border rounded">
                                                        <small class="text-muted">{{ trans('employees.salary_transfer_method') }}</small>
                                                        <div class="fw-semibold" id="view_tm">-</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="p-2 border rounded">
                                                        <small class="text-muted">{{ trans('employees.salary_transfer_details') }}</small>
                                                        <div class="fw-semibold" id="view_td">-</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Delete Modal --}}
            <div id="deleteEmployeeModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center p-5">
                            <lord-icon src="{{URL::asset('assets/images/icon/tdrtiskw.json')}}" trigger="loop"
                                colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px">
                            </lord-icon>

                            <div class="mt-4 pt-4">
                                <h4>{{ trans('employees.delete_confirm_title') }}!</h4>
                                <p class="text-muted">{{ trans('employees.delete_confirm_text') }} <span id="delete_emp_name"></span></p>

                                <form action="{{ route('employees.destroy','test') }}" method="post">
                                    {{ method_field('delete') }}
                                    {{ csrf_field() }}
                                    <input class="form-control" name="id" id="delete_emp_id" value="" type="hidden">

                                    <button class="btn btn-warning" data-bs-target="#secondmodal" data-bs-toggle="modal" data-bs-dismiss="modal">
                                        {{ trans('employees.confirm_delete') }}
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- Add Modal --}}
            <div id="addEmployeeModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 overflow-hidden">
                        <div class="modal-header p-3 bg-soft-success">
                            <h4 class="card-title mb-0">
                                <i class="mdi mdi-account-plus-outline"></i> {{ trans('employees.add_new_employee') }}
                            </h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <form action="{{route('employees.store')}}" method="post" enctype="multipart/form-data" class="employee-form">
                                {{ csrf_field() }}

                                <ul class="nav nav-pills nav-pills-custom mb-3" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#add_tab_basic" type="button" role="tab">
                                            <i class="mdi mdi-card-account-details-outline"></i> {{ trans('employees.full_name') }}
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#add_tab_work" type="button" role="tab">
                                            <i class="mdi mdi-briefcase-outline"></i> {{ trans('employees.job') }}
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#add_tab_finance" type="button" role="tab">
                                            <i class="mdi mdi-cash-multiple"></i> {{ trans('employees.compensation_type') }}
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#add_tab_branches" type="button" role="tab">
                                            <i class="mdi mdi-source-branch"></i> {{ trans('employees.branches') }}
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content">

                                    <div class="tab-pane fade show active" id="add_tab_basic" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.first_name') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="first_name" value="{{ old('first_name') }}" placeholder="مثال: Ahmed">
                                                <small class="text-muted">Required</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.last_name') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="last_name" value="{{ old('last_name') }}" placeholder="مثال: Ali">
                                                <small class="text-muted">Required</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.gender') }}</label>
                                                <select class="form-select" name="gender">
                                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                                    <option value="male">{{ trans('employees.male') }}</option>
                                                    <option value="female">{{ trans('employees.female') }}</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.birth_date') }}</label>
                                                <input type="date" class="form-control" name="birth_date" value="{{ old('birth_date') }}">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.photo') }}</label>
                                                <input type="file" class="form-control" name="photo" accept="image/*">
                                                <small class="text-muted">jpg / png / webp</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.email') }}</label>
                                                <input type="text" class="form-control" name="email" value="{{ old('email') }}" placeholder="example@email.com">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ trans('employees.phone_1') }}</label>
                                                <input type="text" class="form-control" name="phone_1" value="{{ old('phone_1') }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ trans('employees.phone_2') }}</label>
                                                <input type="text" class="form-control" name="phone_2" value="{{ old('phone_2') }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ trans('employees.whatsapp') }}</label>
                                                <input type="text" class="form-control" name="whatsapp" value="{{ old('whatsapp') }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="add_tab_work" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.job') }}</label>
                                                <select class="form-select" name="job_id">
                                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                                    @foreach($Jobs as $Job)
                                                        <option value="{{$Job->id}}">{{$Job->getTranslation('name','ar')}}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.specialization') }}</label>
                                                <input type="text" class="form-control" name="specialization" value="{{ old('specialization') }}">
                                            </div>

                                            <div class="w-100"></div>

                                            {{-- Ensure boolean posts even when unchecked --}}
                                            <input type="hidden" name="is_coach" value="0">

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">هل الموظف مدرب</label>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="is_coach" value="1" id="iscoachnew"
                                                           {{ old('is_coach') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="iscoachnew">نعم</label>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ trans('employees.years_experience') }}</label>
                                                <input type="number" class="form-control" name="years_experience" min="0" max="80" value="{{ old('years_experience') }}">
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ trans('employees.bio') }}</label>
                                                <textarea class="form-control" name="bio" rows="3">{{ old('bio') }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="add_tab_finance" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.compensation_type') }} <span class="text-danger">*</span></label>
                                                <select class="form-select compensation-type" name="compensation_type">
                                                    <option value="salary_only">{{ trans('employees.salary_only') }}</option>
                                                    <option value="commission_only">{{ trans('employees.commission_only') }}</option>
                                                    <option value="salary_and_commission">{{ trans('employees.salary_and_commission') }}</option>
                                                </select>
                                                <small class="text-muted">{{ trans('employees.compensation_hint') }}</small>
                                            </div>

                                            <div class="col-md-6 mb-3 transfer-box">
                                                <label class="form-label">{{ trans('employees.salary_transfer_method') }} <span class="text-danger">*</span></label>
                                                <select class="form-select" name="salary_transfer_method">
                                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                                    <option value="cash">{{ trans('employees.cash') }}</option>
                                                    <option value="ewallet">{{ trans('employees.ewallet') }}</option>
                                                    <option value="bank_transfer">{{ trans('employees.bank_transfer') }}</option>
                                                    <option value="instapay">{{ trans('employees.instapay') }}</option>
                                                    <option value="credit_card">{{ trans('employees.credit_card') }}</option>
                                                    <option value="cheque">{{ trans('employees.cheque') }}</option>
                                                    <option value="other">{{ trans('employees.other') }}</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 mb-3 salary-box">
                                                <label class="form-label">{{ trans('employees.base_salary') }} <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control" name="base_salary" value="{{ old('base_salary') }}">
                                            </div>

                                            <div class="col-md-4 mb-3 commission-box">
                                                <label class="form-label">{{ trans('employees.commission_percent') }}</label>
                                                <input type="number" step="0.01" class="form-control" name="commission_percent" value="{{ old('commission_percent') }}">
                                                <small class="text-muted">0 - 100</small>
                                            </div>

                                            <div class="col-md-4 mb-3 commission-box">
                                                <label class="form-label">{{ trans('employees.commission_fixed') }}</label>
                                                <input type="number" step="0.01" class="form-control" name="commission_fixed" value="{{ old('commission_fixed') }}">
                                            </div>

                                            <div class="col-md-12 mb-3 transfer-box">
                                                <label class="form-label">{{ trans('employees.salary_transfer_details') }}</label>
                                                <input type="text" class="form-control" name="salary_transfer_details" value="{{ old('salary_transfer_details') }}" placeholder="مثال: رقم المحفظة / رقم الحساب / اسم البنك ...">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="add_tab_branches" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.branches') }} <span class="text-danger">*</span></label>
                                                <select class="form-select" name="branches[]" multiple>
                                                    @foreach($Branches as $Branch)
                                                        <option value="{{$Branch->id}}">{{$Branch->getTranslation('name','ar')}}</option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">{{ trans('employees.branches_hint') }}</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.primary_branch') }} <span class="text-danger">*</span></label>
                                                <select class="form-select" name="primary_branch_id">
                                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                                    @foreach($Branches as $Branch)
                                                        <option value="{{$Branch->id}}">{{$Branch->getTranslation('name','ar')}}</option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">{{ trans('employees.primary_branch_hint') }}</small>
                                            </div>

                                            <div class="col-md-12 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="status" value="1" id="status_new" checked>
                                                    <label class="form-check-label" for="status_new">{{ trans('settings_trans.status_active') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="text-end pt-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-content-save-outline"></i> {{ trans('settings_trans.submit') }}
                                    </button>
                                </div>

                            </form>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Edit Modal (Reusable) --}}
            <div id="editEmployeeModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 overflow-hidden">
                        <div class="modal-header p-3 bg-soft-warning">
                            <h4 class="card-title mb-0">
                                <i class="mdi mdi-account-edit-outline"></i> {{ trans('employees.update_employee') }}
                            </h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <form action="{{ route('employees.update','test') }}" method="post" enctype="multipart/form-data" class="employee-form" id="editForm">
                                {{ method_field('patch') }}
                                @csrf
                                <input class="form-control" name="id" id="edit_id" value="" type="hidden">

                                <ul class="nav nav-pills nav-pills-custom mb-3" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#edit_tab_basic" type="button" role="tab">
                                            <i class="mdi mdi-card-account-details-outline"></i> {{ trans('employees.full_name') }}
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#edit_tab_work" type="button" role="tab">
                                            <i class="mdi mdi-briefcase-outline"></i> {{ trans('employees.job') }}
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#edit_tab_finance" type="button" role="tab">
                                            <i class="mdi mdi-cash-multiple"></i> {{ trans('employees.compensation_type') }}
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#edit_tab_branches" type="button" role="tab">
                                            <i class="mdi mdi-source-branch"></i> {{ trans('employees.branches') }}
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content">

                                    <div class="tab-pane fade show active" id="edit_tab_basic" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.first_name') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="first_name" id="edit_first_name">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.last_name') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="last_name" id="edit_last_name">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.gender') }}</label>
                                                <select class="form-select" name="gender" id="edit_gender">
                                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                                    <option value="male">{{ trans('employees.male') }}</option>
                                                    <option value="female">{{ trans('employees.female') }}</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.birth_date') }}</label>
                                                <input type="date" class="form-control" name="birth_date" id="edit_birth_date">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.photo') }}</label>
                                                <input type="file" class="form-control" name="photo" accept="image/*">
                                                <div class="mt-2" id="edit_photo_link_wrap" style="display:none;">
                                                    <a href="#" target="_blank" id="edit_photo_link">{{ trans('employees.view_photo') }}</a>
                                                </div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.email') }}</label>
                                                <input type="text" class="form-control" name="email" id="edit_email">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ trans('employees.phone_1') }}</label>
                                                <input type="text" class="form-control" name="phone_1" id="edit_phone_1">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ trans('employees.phone_2') }}</label>
                                                <input type="text" class="form-control" name="phone_2" id="edit_phone_2">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ trans('employees.whatsapp') }}</label>
                                                <input type="text" class="form-control" name="whatsapp" id="edit_whatsapp">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="edit_tab_work" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.job') }}</label>
                                                <select class="form-select" name="job_id" id="edit_job_id">
                                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                                    @foreach($Jobs as $Job)
                                                        <option value="{{$Job->id}}">{{$Job->getTranslation('name','ar')}}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.specialization') }}</label>
                                                <input type="text" class="form-control" name="specialization" id="edit_specialization">
                                            </div>

                                            <div class="w-100"></div>

                                            {{-- Ensure boolean posts even when unchecked --}}
                                            <input type="hidden" name="is_coach" value="0">

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">هل الموظف مدرب</label>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="is_coach" value="1" id="editiscoach">
                                                    <label class="form-check-label" for="editiscoach">نعم</label>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ trans('employees.years_experience') }}</label>
                                                <input type="number" class="form-control" name="years_experience" min="0" max="80" id="edit_years_experience">
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ trans('employees.bio') }}</label>
                                                <textarea class="form-control" name="bio" rows="3" id="edit_bio"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="edit_tab_finance" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.compensation_type') }} <span class="text-danger">*</span></label>
                                                <select class="form-select compensation-type" name="compensation_type" id="edit_compensation_type">
                                                    <option value="salary_only">{{ trans('employees.salary_only') }}</option>
                                                    <option value="commission_only">{{ trans('employees.commission_only') }}</option>
                                                    <option value="salary_and_commission">{{ trans('employees.salary_and_commission') }}</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3 transfer-box">
                                                <label class="form-label">{{ trans('employees.salary_transfer_method') }} <span class="text-danger">*</span></label>
                                                <select class="form-select" name="salary_transfer_method" id="edit_salary_transfer_method">
                                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                                    <option value="cash">{{ trans('employees.cash') }}</option>
                                                    <option value="ewallet">{{ trans('employees.ewallet') }}</option>
                                                    <option value="bank_transfer">{{ trans('employees.bank_transfer') }}</option>
                                                    <option value="instapay">{{ trans('employees.instapay') }}</option>
                                                    <option value="credit_card">{{ trans('employees.credit_card') }}</option>
                                                    <option value="cheque">{{ trans('employees.cheque') }}</option>
                                                    <option value="other">{{ trans('employees.other') }}</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 mb-3 salary-box">
                                                <label class="form-label">{{ trans('employees.base_salary') }} <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control" name="base_salary" id="edit_base_salary">
                                            </div>

                                            <div class="col-md-4 mb-3 commission-box">
                                                <label class="form-label">{{ trans('employees.commission_percent') }}</label>
                                                <input type="number" step="0.01" class="form-control" name="commission_percent" id="edit_commission_percent">
                                            </div>

                                            <div class="col-md-4 mb-3 commission-box">
                                                <label class="form-label">{{ trans('employees.commission_fixed') }}</label>
                                                <input type="number" step="0.01" class="form-control" name="commission_fixed" id="edit_commission_fixed">
                                            </div>

                                            <div class="col-md-12 mb-3 transfer-box">
                                                <label class="form-label">{{ trans('employees.salary_transfer_details') }}</label>
                                                <input type="text" class="form-control" name="salary_transfer_details" id="edit_salary_transfer_details">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="edit_tab_branches" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.branches') }} <span class="text-danger">*</span></label>
                                                <select class="form-select" name="branches[]" multiple id="edit_branches">
                                                    @foreach($Branches as $Branch)
                                                        <option value="{{$Branch->id}}">{{$Branch->getTranslation('name','ar')}}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ trans('employees.primary_branch') }} <span class="text-danger">*</span></label>
                                                <select class="form-select" name="primary_branch_id" id="edit_primary_branch_id">
                                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                                    @foreach($Branches as $Branch)
                                                        <option value="{{$Branch->id}}">{{$Branch->getTranslation('name','ar')}}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-12 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="status" value="1" id="edit_status">
                                                    <label class="form-check-label" for="edit_status">{{ trans('settings_trans.status_active') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="text-end pt-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-content-save-outline"></i> {{ trans('settings_trans.submit') }}
                                    </button>
                                </div>

                            </form>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function toggleEmployeeForm(form) {
        const typeSelect = form.querySelector('.compensation-type');
        const salaryBoxes = form.querySelectorAll('.salary-box');
        const commissionBoxes = form.querySelectorAll('.commission-box');
        const transferBoxes = form.querySelectorAll('.transfer-box');

        if (!typeSelect) return;

        const type = typeSelect.value;

        const showSalary = (type === 'salary_only' || type === 'salary_and_commission');
        const showCommission = (type === 'commission_only' || type === 'salary_and_commission');

        salaryBoxes.forEach(el => el.style.display = showSalary ? '' : 'none');
        transferBoxes.forEach(el => el.style.display = showSalary ? '' : 'none');
        commissionBoxes.forEach(el => el.style.display = showCommission ? '' : 'none');
    }

    document.addEventListener('DOMContentLoaded', function () {

        // Dynamic form sections (Add/Edit)
        document.querySelectorAll('.employee-form').forEach(form => {
            toggleEmployeeForm(form);
            const typeSelect = form.querySelector('.compensation-type');
            if (typeSelect) {
                typeSelect.addEventListener('change', () => toggleEmployeeForm(form));
            }
        });

        // DataTable
        let dt = null;
        if (window.jQuery && $.fn.DataTable) {

            dt = $('#employeesTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[8, 'desc']],
                // Remove built-in search box (we use custom)
                dom: "<'row align-items-center'<'col-md-6'l><'col-md-6 text-end'>>" +
                     "<'row'<'col-12'tr>>" +
                     "<'row align-items-center'<'col-md-5'i><'col-md-7'p>>"
            });

            // Global search (name/phone/code...)
            $('#employee_global_search').on('keyup change', function () {
                dt.search(this.value).draw();
            });

            // Column filters
            $('#filter_job').on('change', function () {
                dt.column(3).search(this.value).draw();
            });

            $('#filter_branch').on('change', function () {
                dt.column(4).search(this.value).draw();
            });

            $('#filter_compensation').on('change', function () {
                dt.column(6).search(this.value).draw();
            });

            $('#filter_status').on('change', function () {
                dt.column(7).search(this.value).draw();
            });

            // Reset filters
            $('#btn_reset_filters').on('click', function () {
                $('#employee_global_search').val('');
                $('#filter_job').val('');
                $('#filter_branch').val('');
                $('#filter_compensation').val('');
                $('#filter_status').val('');

                dt.search('');
                dt.columns().search('');
                dt.draw();
            });
        }

        // View modal fill
        const viewModal = document.getElementById('viewEmployeeModal');
        if (viewModal) {
            viewModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                if (!btn) return;

                const photo = btn.getAttribute('data-photo') || '';
                const gender = btn.getAttribute('data-gender') || '-';

                document.getElementById('view_title').textContent = btn.getAttribute('data-name') || '-';
                document.getElementById('view_subtitle').textContent = (btn.getAttribute('data-code') || '') + ' • ' + (btn.getAttribute('data-job') || '-');

                document.getElementById('view_name').textContent = btn.getAttribute('data-name') || '-';
                document.getElementById('view_code').textContent = btn.getAttribute('data-code') || '-';
                document.getElementById('view_job').textContent = btn.getAttribute('data-job') || '-';
                document.getElementById('view_status').textContent = btn.getAttribute('data-status') || '-';

                document.getElementById('view_phone1').textContent = btn.getAttribute('data-phone') || '-';
                document.getElementById('view_phone2').textContent = btn.getAttribute('data-phone2') || '-';
                document.getElementById('view_whatsapp').textContent = btn.getAttribute('data-whatsapp') || '-';
                document.getElementById('view_email').textContent = btn.getAttribute('data-email') || '-';

                document.getElementById('view_gender').textContent = (gender === 'male' ? '{{ trans("employees.male") }}' : (gender === 'female' ? '{{ trans("employees.female") }}' : '-'));
                document.getElementById('view_birth').textContent = btn.getAttribute('data-birth') || '-';

                document.getElementById('view_branches').textContent = btn.getAttribute('data-branches') || '-';
                document.getElementById('view_branches_count').textContent = btn.getAttribute('data-branches-count') || '0';

                document.getElementById('view_specialization').textContent = btn.getAttribute('data-specialization') || '-';
                document.getElementById('view_years').textContent = btn.getAttribute('data-years') || '-';

                const coachVal = btn.getAttribute('data-is-coach');
                document.getElementById('viewiscoach-div').textContent =
                    (coachVal == 1 || coachVal === 'true') ? 'مدرب' : 'غير مدرب';

                document.getElementById('view_bio').textContent = btn.getAttribute('data-bio') || '-';

                document.getElementById('view_type').textContent = btn.getAttribute('data-type') || '-';
                document.getElementById('view_base_salary').textContent = btn.getAttribute('data-base-salary') || '-';
                document.getElementById('view_cp').textContent = btn.getAttribute('data-commission-percent') || '-';
                document.getElementById('view_cf').textContent = btn.getAttribute('data-commission-fixed') || '-';

                document.getElementById('view_tm').textContent = btn.getAttribute('data-transfer-method') || '-';
                document.getElementById('view_td').textContent = btn.getAttribute('data-transfer-details') || '-';

                const img = document.getElementById('view_photo');
                const placeholder = document.getElementById('view_photo_placeholder');

                if (photo) {
                    img.src = photo;
                    img.style.display = '';
                    placeholder.style.display = 'none';
                } else {
                    img.src = '';
                    img.style.display = 'none';
                    placeholder.style.display = '';
                }
            });
        }

        // Delete modal fill
        document.querySelectorAll('.btn-delete-employee').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('delete_emp_id').value = this.getAttribute('data-id') || '';
                document.getElementById('delete_emp_name').textContent = this.getAttribute('data-name') || '';
            });
        });

        // Edit modal fill
        document.querySelectorAll('.btn-edit-employee').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('edit_id').value = this.getAttribute('data-id') || '';

                document.getElementById('edit_first_name').value = this.getAttribute('data-first') || '';
                document.getElementById('edit_last_name').value = this.getAttribute('data-last') || '';

                document.getElementById('edit_job_id').value = this.getAttribute('data-job-id') || '';
                document.getElementById('edit_gender').value = this.getAttribute('data-gender') || '';
                document.getElementById('edit_birth_date').value = this.getAttribute('data-birth') || '';

                document.getElementById('edit_phone_1').value = this.getAttribute('data-phone1') || '';
                document.getElementById('edit_phone_2').value = this.getAttribute('data-phone2') || '';
                document.getElementById('edit_whatsapp').value = this.getAttribute('data-whatsapp') || '';
                document.getElementById('edit_email').value = this.getAttribute('data-email') || '';

                document.getElementById('edit_specialization').value = this.getAttribute('data-specialization') || '';
                document.getElementById('edit_years_experience').value = this.getAttribute('data-years') || '';

                const coach = this.getAttribute('data-is-coach');
                document.getElementById('editiscoach').checked = (coach == 1 || coach === 'true');

                document.getElementById('edit_bio').value = this.getAttribute('data-bio') || '';

                document.getElementById('edit_compensation_type').value = this.getAttribute('data-comp') || 'salary_only';
                document.getElementById('edit_base_salary').value = this.getAttribute('data-base') || '';
                document.getElementById('edit_commission_percent').value = this.getAttribute('data-cp') || '';
                document.getElementById('edit_commission_fixed').value = this.getAttribute('data-cf') || '';

                document.getElementById('edit_salary_transfer_method').value = this.getAttribute('data-tm') || '';
                document.getElementById('edit_salary_transfer_details').value = this.getAttribute('data-td') || '';

                const st = this.getAttribute('data-status');
                document.getElementById('edit_status').checked = (st === '1' || st === 'true');

                const photoUrl = this.getAttribute('data-photo-url') || '';
                const wrap = document.getElementById('edit_photo_link_wrap');
                const link = document.getElementById('edit_photo_link');

                if (photoUrl) {
                    link.href = photoUrl;
                    wrap.style.display = '';
                } else {
                    link.href = '#';
                    wrap.style.display = 'none';
                }

                // branches multi select + primary branch
                try {
                    const branches = JSON.parse(this.getAttribute('data-branches') || '[]');
                    const multi = document.getElementById('edit_branches');
                    Array.from(multi.options).forEach(opt => opt.selected = branches.includes(parseInt(opt.value)));
                } catch (e) {}

                document.getElementById('edit_primary_branch_id').value = this.getAttribute('data-primary') || '';

                toggleEmployeeForm(document.getElementById('editForm'));
            });
        });

    });
</script>

@endsection
