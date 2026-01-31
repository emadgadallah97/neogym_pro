<div id="viewMemberModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none">
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
                        <h4 class="card-title mb-0" id="viewMemberTitle">{{ trans('members.members') }}</h4>
                        <small class="text-muted" id="viewMemberSubtitle"></small>
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
                                    <img id="viewMemberPhoto" src="" class="rounded" style="width:120px;height:120px;object-fit:cover;display:none">
                                    <div id="viewMemberPhotoPlaceholder" class="avatar-lg mx-auto" style="display:inline-flex">
                                        <span class="avatar-title rounded bg-soft-primary text-primary">
                                            <i class="mdi mdi-account-outline fs-2"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="fw-semibold" id="viewMemberName">-</div>
                                <div class="text-muted small" id="viewMemberCode">-</div>

                                <div class="mt-2">
                                    <span class="badge bg-soft-info text-info" id="viewMemberBranch">-</span>
                                </div>

                                <div class="mt-2">
                                    <span class="badge bg-soft-success text-success" id="viewMemberStatusBadge">-</span>
                                </div>

                                <div class="d-flex justify-content-center gap-2 mt-3">
                                    <a href="#" target="_blank" class="btn btn-sm btn-soft-primary" id="viewMemberCardLink">
                                        <i class="mdi mdi-card-account-details-outline"></i>
                                        {{ trans('members.print_card') }}
                                    </a>
                                    <a href="#" class="btn btn-sm btn-soft-secondary" id="viewMemberQrLink">
                                        <i class="mdi mdi-qrcode"></i>
                                        {{ trans('members.download_qr_png') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#view_tab_contact" type="button" role="tab">
                                    <i class="mdi mdi-phone-outline"></i>
                                    {{ trans('members.tab_contact') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#view_tab_address" type="button" role="tab">
                                    <i class="mdi mdi-map-marker-outline"></i>
                                    {{ trans('members.tab_address') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#view_tab_membership" type="button" role="tab">
                                    <i class="mdi mdi-account-details-outline"></i>
                                    {{ trans('members.tab_membership') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#view_tab_medical" type="button" role="tab">
                                    <i class="mdi mdi-heart-pulse"></i>
                                    {{ trans('members.tab_medical') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#view_tab_subscriptions" type="button" role="tab">
                                    <i class="mdi mdi-cash-multiple"></i>
                                    {{ trans('members.tab_subscriptions') }}
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">

                            {{-- CONTACT --}}
                            <div class="tab-pane fade show active" id="view_tab_contact" role="tabpanel">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="p-2 border rounded bg-light">
                                            <small class="text-muted">{{ trans('members.phone') }}</small>
                                            <div class="fw-semibold" id="viewMemberPhone">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-2 border rounded bg-light">
                                            <small class="text-muted">{{ trans('members.phone2') }}</small>
                                            <div class="fw-semibold" id="viewMemberPhone2">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-2 border rounded bg-light">
                                            <small class="text-muted">{{ trans('members.whatsapp') }}</small>
                                            <div class="fw-semibold" id="viewMemberWhatsapp">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-2 border rounded bg-light">
                                            <small class="text-muted">{{ trans('members.email') }}</small>
                                            <div class="fw-semibold" id="viewMemberEmail">-</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="p-2 border rounded">
                                            <small class="text-muted">{{ trans('members.gender') }}</small>
                                            <div class="fw-semibold" id="viewMemberGender">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-2 border rounded">
                                            <small class="text-muted">{{ trans('members.birth_date') }}</small>
                                            <div class="fw-semibold" id="viewMemberBirth">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ADDRESS --}}
                            <div class="tab-pane fade" id="view_tab_address" role="tabpanel">
                                <div class="row g-2">
                                    <div class="col-md-12">
                                        <div class="p-2 border rounded bg-light">
                                            <small class="text-muted">{{ trans('members.address') }}</small>
                                            <div class="fw-semibold" id="viewMemberAddress">-</div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="p-2 border rounded">
                                            <small class="text-muted">{{ trans('members.government') }}</small>
                                            <div class="fw-semibold" id="viewMemberGovernment">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-2 border rounded">
                                            <small class="text-muted">{{ trans('members.city') }}</small>
                                            <div class="fw-semibold" id="viewMemberCity">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-2 border rounded">
                                            <small class="text-muted">{{ trans('members.area') }}</small>
                                            <div class="fw-semibold" id="viewMemberArea">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- MEMBERSHIP --}}
                            <div class="tab-pane fade" id="view_tab_membership" role="tabpanel">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="p-2 border rounded bg-soft-info">
                                            <small class="text-muted">{{ trans('members.join_date') }}</small>
                                            <div class="fw-semibold" id="viewMemberJoin">-</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="p-2 border rounded bg-soft-info">
                                            <small class="text-muted">{{ trans('members.status') }}</small>
                                            <div class="fw-semibold" id="viewMemberStatusText">-</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="p-2 border rounded">
                                            <small class="text-muted">{{ trans('members.freeze_from') }}</small>
                                            <div class="fw-semibold" id="viewMemberFreezeFrom">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-2 border rounded">
                                            <small class="text-muted">{{ trans('members.freeze_to') }}</small>
                                            <div class="fw-semibold" id="viewMemberFreezeTo">-</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="p-2 border rounded">
                                            <small class="text-muted">{{ trans('members.height') }}</small>
                                            <div class="fw-semibold" id="viewMemberHeight">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-2 border rounded">
                                            <small class="text-muted">{{ trans('members.weight') }}</small>
                                            <div class="fw-semibold" id="viewMemberWeight">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- MEDICAL --}}
                            <div class="tab-pane fade" id="view_tab_medical" role="tabpanel">
                                <div class="row g-2">
                                    <div class="col-md-12">
                                        <div class="p-2 border rounded bg-light">
                                            <small class="text-muted">{{ trans('members.medical_conditions') }}</small>
                                            <div class="fw-semibold" id="viewMemberMedical">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="p-2 border rounded bg-light">
                                            <small class="text-muted">{{ trans('members.allergies') }}</small>
                                            <div class="fw-semibold" id="viewMemberAllergies">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="p-2 border rounded">
                                            <small class="text-muted">{{ trans('members.notes') }}</small>
                                            <div class="fw-semibold" id="viewMemberNotes">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- SUBSCRIPTIONS --}}
                            <div class="tab-pane fade" id="view_tab_subscriptions" role="tabpanel">
                                <div class="alert alert-warning mb-0">
                                    <i class="mdi mdi-information-outline"></i>
                                    {{ trans('members.subscriptions_placeholder') }}
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
