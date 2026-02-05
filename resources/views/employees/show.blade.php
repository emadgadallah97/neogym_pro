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

                                    {{-- NEW: commission value type --}}
                                    <div class="col-md-4">
                                        <div class="p-2 border rounded">
                                            <small class="text-muted">{{ trans('employees.commission_value_type') ?? 'نوع العمولة' }}</small>
                                            <div class="fw-semibold" id="view_commission_value_type">-</div>
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
