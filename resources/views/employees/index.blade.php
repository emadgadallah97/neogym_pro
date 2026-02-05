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
                        <small class="text-muted">{{ trans('employees.compensation_hint') }}</small>
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

                                    // NEW: commission value type
                                    $commissionValueType = $Employee->commission_value_type ?? '';
                                @endphp


                                <tr>
                                    <?php $i++; ?>
                                    <td>{{$i}}</td>
                                    <td><span class="badge bg-dark-subtle text-dark">{{$Employee->code}}</span></td>


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


                                    <td><span class="badge bg-soft-info text-info">{{ $jobName }}</span></td>


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
                                                data-commission-value-type="{{ $commissionValueType }}" {{-- NEW --}}
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
                                                class="btn btn-sm btn-soft-warning btn-icon"
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
                                                data-cvt="{{ $commissionValueType }}" {{-- NEW --}}
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


                                            {{-- More (Delete only) --}}
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ trans('employees.more') ?? 'المزيد' }}">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                                    <li>
                                                        <button
                                                            class="dropdown-item text-danger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteEmployeeModal"
                                                            data-id="{{ $Employee->id }}"
                                                            data-name="{{ $Employee->full_name }}"
                                                            type="button"
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


            {{-- Modals --}}
            @include('employees.show')
            @include('employees.create')
            @include('employees.edit')


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

        // NEW: commission sub-type (percent/fixed) support (safe if elements not present yet)
        toggleCommissionValueType(form);
    }

    // NEW
    function toggleCommissionValueType(form) {
        const compensationSelect = form.querySelector('.compensation-type');
        const commissionTypeSelect = form.querySelector('.commission-value-type');

        const percentBoxes = form.querySelectorAll('.commission-percent-box');
        const fixedBoxes = form.querySelectorAll('.commission-fixed-box');

        if (!compensationSelect) return;

        const comp = compensationSelect.value;
        const showCommission = (comp === 'commission_only' || comp === 'salary_and_commission');

        // If commission not applicable, hide both and clear select (if exists)
        if (!showCommission) {
            percentBoxes.forEach(el => el.style.display = 'none');
            fixedBoxes.forEach(el => el.style.display = 'none');
            if (commissionTypeSelect) commissionTypeSelect.value = '';
            return;
        }

        // Commission applicable but select not added yet => do nothing
        if (!commissionTypeSelect) return;

        const v = commissionTypeSelect.value;

        percentBoxes.forEach(el => el.style.display = (v === 'percent') ? '' : 'none');
        fixedBoxes.forEach(el => el.style.display = (v === 'fixed') ? '' : 'none');
    }


    document.addEventListener('DOMContentLoaded', function () {


        // Dynamic form sections (Add/Edit)
        document.querySelectorAll('.employee-form').forEach(form => {
            toggleEmployeeForm(form);

            const typeSelect = form.querySelector('.compensation-type');
            if (typeSelect) {
                typeSelect.addEventListener('change', () => toggleEmployeeForm(form));
            }

            // NEW: listen commission value type
            const commissionTypeSelect = form.querySelector('.commission-value-type');
            if (commissionTypeSelect) {
                commissionTypeSelect.addEventListener('change', () => toggleEmployeeForm(form));
            }
        });


        // DataTable
        let dt = null;
        if (window.jQuery && $.fn.DataTable) {


            dt = $('#employeesTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[8, 'desc']],
                dom: "<'row align-items-center'<'col-md-6'l><'col-md-6 text-end'>>" +
                     "<'row'<'col-12'tr>>" +
                     "<'row align-items-center'<'col-md-5'i><'col-md-7'p>>"
            });


            // Global search
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
                const coachDiv = document.getElementById('viewiscoach-div');
                if (coachDiv) {
                    coachDiv.textContent = (coachVal === '1' || coachVal === 'true' || coachVal == 1) ? 'مدرب' : 'غير مدرب';
                }


                document.getElementById('view_bio').textContent = btn.getAttribute('data-bio') || '-';


                document.getElementById('view_type').textContent = btn.getAttribute('data-type') || '-';
                document.getElementById('view_base_salary').textContent = btn.getAttribute('data-base-salary') || '-';
                document.getElementById('view_cp').textContent = btn.getAttribute('data-commission-percent') || '-';
                document.getElementById('view_cf').textContent = btn.getAttribute('data-commission-fixed') || '-';

                // NEW (safe): if you add an element later in show.blade.php
                const cvt = btn.getAttribute('data-commission-value-type') || '';
                const viewCvtEl = document.getElementById('view_commission_value_type');
                if (viewCvtEl) {
                    viewCvtEl.textContent = (cvt === 'percent') ? 'Percent' : (cvt === 'fixed' ? 'Fixed' : '-');
                }


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


        // Delete modal fill (FIX: works with DataTables paging/redraw)
        const deleteModal = document.getElementById('deleteEmployeeModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                if (!btn) return;


                document.getElementById('delete_emp_id').value = btn.getAttribute('data-id') || '';
                document.getElementById('delete_emp_name').textContent = btn.getAttribute('data-name') || '';
            });
        }


        // Edit modal fill (FIX: works with DataTables paging/redraw)
        const editModal = document.getElementById('editEmployeeModal');
        if (editModal) {


            editModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                if (!btn) return;


                document.getElementById('edit_id').value = btn.getAttribute('data-id') || '';


                document.getElementById('edit_first_name').value = btn.getAttribute('data-first') || '';
                document.getElementById('edit_last_name').value = btn.getAttribute('data-last') || '';


                document.getElementById('edit_job_id').value = btn.getAttribute('data-job-id') || '';
                document.getElementById('edit_gender').value = btn.getAttribute('data-gender') || '';
                document.getElementById('edit_birth_date').value = btn.getAttribute('data-birth') || '';


                document.getElementById('edit_phone_1').value = btn.getAttribute('data-phone1') || '';
                document.getElementById('edit_phone_2').value = btn.getAttribute('data-phone2') || '';
                document.getElementById('edit_whatsapp').value = btn.getAttribute('data-whatsapp') || '';
                document.getElementById('edit_email').value = btn.getAttribute('data-email') || '';


                document.getElementById('edit_specialization').value = btn.getAttribute('data-specialization') || '';
                document.getElementById('edit_years_experience').value = btn.getAttribute('data-years') || '';


                const coach = btn.getAttribute('data-is-coach');
                const coachCheckbox = document.getElementById('editiscoach');
                if (coachCheckbox) {
                    coachCheckbox.checked = (coach === '1' || coach === 'true' || coach == 1);
                }


                document.getElementById('edit_bio').value = btn.getAttribute('data-bio') || '';


                document.getElementById('edit_compensation_type').value = btn.getAttribute('data-comp') || 'salary_only';
                document.getElementById('edit_base_salary').value = btn.getAttribute('data-base') || '';

                // NEW: commission value type (safe if select not added yet)
                const editCvt = btn.getAttribute('data-cvt') || '';
                const editCvtSelect = document.getElementById('edit_commission_value_type');
                if (editCvtSelect) {
                    editCvtSelect.value = editCvt;
                }

                document.getElementById('edit_commission_percent').value = btn.getAttribute('data-cp') || '';
                document.getElementById('edit_commission_fixed').value = btn.getAttribute('data-cf') || '';


                document.getElementById('edit_salary_transfer_method').value = btn.getAttribute('data-tm') || '';
                document.getElementById('edit_salary_transfer_details').value = btn.getAttribute('data-td') || '';


                const st = btn.getAttribute('data-status');
                document.getElementById('edit_status').checked = (st === '1' || st === 'true' || st == 1);


                const photoUrl = btn.getAttribute('data-photo-url') || '';
                const wrap = document.getElementById('edit_photo_link_wrap');
                const link = document.getElementById('edit_photo_link');


                if (photoUrl) {
                    link.href = photoUrl;
                    wrap.style.display = '';
                } else {
                    link.href = '#';
                    wrap.style.display = 'none';
                }


                // branches multi select + primary branch (IMPORTANT: clear first)
                const multi = document.getElementById('edit_branches');
                if (multi) {
                    Array.from(multi.options).forEach(opt => opt.selected = false);


                    try {
                        const branches = JSON.parse(btn.getAttribute('data-branches') || '[]');
                        Array.from(multi.options).forEach(opt => {
                            opt.selected = branches.includes(parseInt(opt.value));
                        });
                    } catch (e) {}
                }


                document.getElementById('edit_primary_branch_id').value = btn.getAttribute('data-primary') || '';


                toggleEmployeeForm(document.getElementById('editForm'));
            });


            editModal.addEventListener('hidden.bs.modal', function () {
                const form = document.getElementById('editForm');
                if (!form) return;


                form.reset();


                const multi = document.getElementById('edit_branches');
                if (multi) Array.from(multi.options).forEach(opt => opt.selected = false);


                const wrap = document.getElementById('edit_photo_link_wrap');
                const link = document.getElementById('edit_photo_link');
                if (wrap && link) {
                    link.href = '#';
                    wrap.style.display = 'none';
                }


                const coachCheckbox = document.getElementById('editiscoach');
                if (coachCheckbox) coachCheckbox.checked = false;


                toggleEmployeeForm(form);
            });
        }


    });
</script>


@endsection
