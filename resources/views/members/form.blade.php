@php
    $mode = $mode ?? 'add';
    $modalId = $modalId ?? 'memberModal';
    $headerClass = $headerClass ?? 'bg-soft-primary';
    $icon = $icon ?? 'mdi-account-plus-outline';
    $title = $title ?? trans('members.members');
    $formId = $formId ?? 'memberForm';
    $action = $action ?? route('members.store');
    $httpMethod = $httpMethod ?? 'post';
@endphp

<div id="{{ $modalId }}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">

            <div class="modal-header p-3 {{ $headerClass }}">
                <h4 class="card-title mb-0">
                    <i class="mdi {{ $icon }}"></i>
                    {{ $title }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form action="{{ $action }}"
                      method="post"
                      enctype="multipart/form-data"
                      class="member-form"
                      id="{{ $formId }}"
                      data-method="{{ strtoupper($httpMethod) }}">
                    @csrf
                    @if(strtolower($httpMethod) !== 'post')
                        @method($httpMethod)
                    @endif

                    @if($mode === 'edit')
                        <input class="form-control" name="id" id="editMemberId" type="hidden">
                    @endif

                    <ul class="nav nav-pills nav-pills-custom mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#{{ $modalId }}_tab_basic" type="button" role="tab">
                                <i class="mdi mdi-card-account-details-outline"></i>
                                {{ trans('members.tab_basic') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#{{ $modalId }}_tab_contact" type="button" role="tab">
                                <i class="mdi mdi-phone-outline"></i>
                                {{ trans('members.tab_contact') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#{{ $modalId }}_tab_address" type="button" role="tab">
                                <i class="mdi mdi-map-marker-outline"></i>
                                {{ trans('members.tab_address') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#{{ $modalId }}_tab_membership" type="button" role="tab">
                                <i class="mdi mdi-account-details-outline"></i>
                                {{ trans('members.tab_membership') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#{{ $modalId }}_tab_medical" type="button" role="tab">
                                <i class="mdi mdi-heart-pulse"></i>
                                {{ trans('members.tab_medical') }}
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        {{-- BASIC --}}
                        <div class="tab-pane fade show active" id="{{ $modalId }}_tab_basic" role="tabpanel">
                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ trans('members.first_name') }} <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control"
                                           name="first_name"
                                           id="{{ $mode === 'edit' ? 'editFirstName' : 'addFirstName' }}"
                                           placeholder="Ahmed">
                                    <small class="text-muted">{{ trans('members.required') }}</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ trans('members.last_name') }} <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control"
                                           name="last_name"
                                           id="{{ $mode === 'edit' ? 'editLastName' : 'addLastName' }}"
                                           placeholder="Ali">
                                    <small class="text-muted">{{ trans('members.required') }}</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ trans('members.branch') }} <span class="text-danger">*</span></label>
                                    <select class="form-select"
                                            name="branch_id"
                                            id="{{ $mode === 'edit' ? 'editBranchId' : 'addBranchId' }}">
                                        <option value="">{{ trans('settings_trans.choose') }}</option>
                                        @foreach($Branches as $Branch)
                                            <option value="{{ $Branch->id }}">{{ $Branch->getTranslation('name','ar') }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ trans('members.gender') }}</label>
                                    <select class="form-select"
                                            name="gender"
                                            id="{{ $mode === 'edit' ? 'editGender' : 'addGender' }}">
                                        <option value="">{{ trans('settings_trans.choose') }}</option>
                                        <option value="male">{{ trans('members.male') }}</option>
                                        <option value="female">{{ trans('members.female') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ trans('members.birth_date') }}</label>
                                    <input type="date"
                                           class="form-control"
                                           name="birth_date"
                                           id="{{ $mode === 'edit' ? 'editBirthDate' : 'addBirthDate' }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ trans('members.photo') }}</label>
                                    <input type="file" class="form-control" name="photo" accept="image/*">
                                    <small class="text-muted">jpg, png, webp</small>

                                    @if($mode === 'edit')
                                        <div class="mt-2" id="editPhotoLinkWrap" style="display:none">
                                            <a href="#" target="_blank" id="editPhotoLink">{{ trans('members.view_photo') }}</a>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ trans('members.join_date') }} <span class="text-danger">*</span></label>
                                    <input type="date"
                                           class="form-control"
                                           name="join_date"
                                           id="{{ $mode === 'edit' ? 'editJoinDate' : 'addJoinDate' }}"
                                           value="{{ $mode === 'add' ? now()->format('Y-m-d') : '' }}">
                                </div>

                            </div>
                        </div>

                        {{-- CONTACT --}}
                        <div class="tab-pane fade" id="{{ $modalId }}_tab_contact" role="tabpanel">
                            <div class="row">

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ trans('members.phone') }} <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control"
                                           name="phone"
                                           id="{{ $mode === 'edit' ? 'editPhone' : 'addPhone' }}">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ trans('members.phone2') }}</label>
                                    <input type="text"
                                           class="form-control"
                                           name="phone2"
                                           id="{{ $mode === 'edit' ? 'editPhone2' : 'addPhone2' }}">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ trans('members.whatsapp') }}</label>
                                    <input type="text"
                                           class="form-control"
                                           name="whatsapp"
                                           id="{{ $mode === 'edit' ? 'editWhatsapp' : 'addWhatsapp' }}">
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ trans('members.email') }}</label>
                                    <input type="text"
                                           class="form-control"
                                           name="email"
                                           id="{{ $mode === 'edit' ? 'editEmail' : 'addEmail' }}"
                                           placeholder="example@email.com">
                                </div>

                            </div>
                        </div>

                        {{-- ADDRESS --}}
                        <div class="tab-pane fade" id="{{ $modalId }}_tab_address" role="tabpanel">
                            <div class="row">

                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ trans('members.address') }} <span class="text-danger">*</span></label>
                                    <textarea class="form-control"
                                              name="address"
                                              rows="2"
                                              id="{{ $mode === 'edit' ? 'editAddress' : 'addAddress' }}"></textarea>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ trans('members.government') }}</label>
                                    <select class="form-select gov-select"
                                            name="id_government"
                                            id="{{ $mode === 'edit' ? 'editGov' : 'addGov' }}">
                                        <option value="">{{ trans('settings_trans.choose') }}</option>
                                        @foreach($Governments as $G)
                                            <option value="{{ $G->id }}">{{ $G->getTranslation('name','ar') }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ trans('members.city') }}</label>
                                    {{-- مهم: نتركها فاضية لتتعبّى من JS حسب الحكومة --}}
                                    <select class="form-select city-select"
                                            name="id_city"
                                            id="{{ $mode === 'edit' ? 'editCity' : 'addCity' }}">
                                        <option value="">{{ trans('settings_trans.choose') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ trans('members.area') }}</label>
                                    {{-- مهم: نتركها فاضية لتتعبّى من JS حسب المدينة --}}
                                    <select class="form-select area-select"
                                            name="id_area"
                                            id="{{ $mode === 'edit' ? 'editArea' : 'addArea' }}">
                                        <option value="">{{ trans('settings_trans.choose') }}</option>
                                    </select>
                                </div>

                            </div>
                        </div>

                        {{-- MEMBERSHIP --}}
                        <div class="tab-pane fade" id="{{ $modalId }}_tab_membership" role="tabpanel">
                            <div class="row">

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ trans('members.status') }} <span class="text-danger">*</span></label>
                                    <select class="form-select member-status"
                                            name="status"
                                            id="{{ $mode === 'edit' ? 'editStatus' : 'addStatus' }}">
                                        <option value="active">{{ trans('members.active') }}</option>
                                        <option value="inactive">{{ trans('members.inactive') }}</option>
                                        <option value="frozen">{{ trans('members.frozen') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-8 mb-3 freeze-box" style="display:none">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ trans('members.freeze_from') }} <span class="text-danger">*</span></label>
                                            <input type="date"
                                                   class="form-control"
                                                   name="freeze_from"
                                                   id="{{ $mode === 'edit' ? 'editFreezeFrom' : 'addFreezeFrom' }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ trans('members.freeze_to') }} <span class="text-danger">*</span></label>
                                            <input type="date"
                                                   class="form-control"
                                                   name="freeze_to"
                                                   id="{{ $mode === 'edit' ? 'editFreezeTo' : 'addFreezeTo' }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ trans('members.height') }}</label>
                                    <input type="number"
                                           step="0.01"
                                           class="form-control"
                                           name="height"
                                           id="{{ $mode === 'edit' ? 'editHeight' : 'addHeight' }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ trans('members.weight') }}</label>
                                    <input type="number"
                                           step="0.01"
                                           class="form-control"
                                           name="weight"
                                           id="{{ $mode === 'edit' ? 'editWeight' : 'addWeight' }}">
                                </div>

                            </div>
                        </div>

                        {{-- MEDICAL --}}
                        <div class="tab-pane fade" id="{{ $modalId }}_tab_medical" role="tabpanel">
                            <div class="row">

                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ trans('members.medical_conditions') }}</label>
                                    <textarea class="form-control"
                                              name="medical_conditions"
                                              rows="3"
                                              id="{{ $mode === 'edit' ? 'editMedical' : 'addMedical' }}"></textarea>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ trans('members.allergies') }}</label>
                                    <textarea class="form-control"
                                              name="allergies"
                                              rows="3"
                                              id="{{ $mode === 'edit' ? 'editAllergies' : 'addAllergies' }}"></textarea>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ trans('members.notes') }}</label>
                                    <textarea class="form-control"
                                              name="notes"
                                              rows="3"
                                              id="{{ $mode === 'edit' ? 'editNotes' : 'addNotes' }}"></textarea>
                                </div>

                            </div>
                        </div>

                    </div>

                    <div class="text-end pt-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save-outline"></i>
                            {{ trans('settings_trans.submit') }}
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>
