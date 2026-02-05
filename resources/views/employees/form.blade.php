@php
    $mode = $mode ?? 'create';
    $isEdit = ($mode === 'edit');
@endphp


<ul class="nav nav-pills nav-pills-custom mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#{{ $isEdit ? 'edit' : 'add' }}_tab_basic" type="button" role="tab">
            <i class="mdi mdi-card-account-details-outline"></i> {{ trans('employees.full_name') }}
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#{{ $isEdit ? 'edit' : 'add' }}_tab_work" type="button" role="tab">
            <i class="mdi mdi-briefcase-outline"></i> {{ trans('employees.job') }}
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#{{ $isEdit ? 'edit' : 'add' }}_tab_finance" type="button" role="tab">
            <i class="mdi mdi-cash-multiple"></i> {{ trans('employees.compensation_type') }}
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#{{ $isEdit ? 'edit' : 'add' }}_tab_branches" type="button" role="tab">
            <i class="mdi mdi-source-branch"></i> {{ trans('employees.branches') }}
        </button>
    </li>
</ul>



<div class="tab-content">


    {{-- Basic --}}
    <div class="tab-pane fade show active" id="{{ $isEdit ? 'edit' : 'add' }}_tab_basic" role="tabpanel">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ trans('employees.first_name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="first_name"
                       @if($isEdit) id="edit_first_name" @endif
                       value="{{ $isEdit ? '' : old('first_name') }}" placeholder="مثال: Ahmed">
                @if(!$isEdit)<small class="text-muted">Required</small>@endif
            </div>


            <div class="col-md-6 mb-3">
                <label class="form-label">{{ trans('employees.last_name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="last_name"
                       @if($isEdit) id="edit_last_name" @endif
                       value="{{ $isEdit ? '' : old('last_name') }}" placeholder="مثال: Ali">
                @if(!$isEdit)<small class="text-muted">Required</small>@endif
            </div>


            <div class="col-md-6 mb-3">
                <label class="form-label">{{ trans('employees.gender') }}</label>
                <select class="form-select" name="gender" @if($isEdit) id="edit_gender" @endif>
                    <option value="">{{ trans('settings_trans.choose') }}</option>
                    <option value="male">{{ trans('employees.male') }}</option>
                    <option value="female">{{ trans('employees.female') }}</option>
                </select>
            </div>


            <div class="col-md-6 mb-3">
                <label class="form-label">{{ trans('employees.birth_date') }}</label>
                <input type="date" class="form-control" name="birth_date"
                       @if($isEdit) id="edit_birth_date" @endif
                       value="{{ $isEdit ? '' : old('birth_date') }}">
            </div>


            <div class="col-md-6 mb-3">
                <label class="form-label">{{ trans('employees.photo') }}</label>
                <input type="file" class="form-control" name="photo" accept="image/*">
                @if(!$isEdit)<small class="text-muted">jpg / png / webp</small>@endif
                @if($isEdit)
                    <div class="mt-2" id="edit_photo_link_wrap" style="display:none;">
                        <a href="#" target="_blank" id="edit_photo_link">{{ trans('employees.view_photo') }}</a>
                    </div>
                @endif
            </div>


            <div class="col-md-6 mb-3">
                <label class="form-label">{{ trans('employees.email') }}</label>
                <input type="text" class="form-control" name="email"
                       @if($isEdit) id="edit_email" @endif
                       value="{{ $isEdit ? '' : old('email') }}" placeholder="example@email.com">
            </div>


            <div class="col-md-4 mb-3">
                <label class="form-label">{{ trans('employees.phone_1') }}</label>
                <input type="text" class="form-control" name="phone_1"
                       @if($isEdit) id="edit_phone_1" @endif
                       value="{{ $isEdit ? '' : old('phone_1') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ trans('employees.phone_2') }}</label>
                <input type="text" class="form-control" name="phone_2"
                       @if($isEdit) id="edit_phone_2" @endif
                       value="{{ $isEdit ? '' : old('phone_2') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ trans('employees.whatsapp') }}</label>
                <input type="text" class="form-control" name="whatsapp"
                       @if($isEdit) id="edit_whatsapp" @endif
                       value="{{ $isEdit ? '' : old('whatsapp') }}">
            </div>
        </div>
    </div>



    {{-- Work --}}
    <div class="tab-pane fade" id="{{ $isEdit ? 'edit' : 'add' }}_tab_work" role="tabpanel">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ trans('employees.job') }}</label>
                <select class="form-select" name="job_id" @if($isEdit) id="edit_job_id" @endif>
                    <option value="">{{ trans('settings_trans.choose') }}</option>
                    @foreach($Jobs as $Job)
                        <option value="{{$Job->id}}">{{$Job->getTranslation('name','ar')}}</option>
                    @endforeach
                </select>
            </div>


            <div class="col-md-6 mb-3">
                <label class="form-label">{{ trans('employees.specialization') }}</label>
                <input type="text" class="form-control" name="specialization"
                       @if($isEdit) id="edit_specialization" @endif
                       value="{{ $isEdit ? '' : old('specialization') }}">
            </div>


            <div class="w-100"></div>


            {{-- Ensure boolean posts even when unchecked --}}
            <input type="hidden" name="is_coach" value="0">


            <div class="col-md-4 mb-3">
                <label class="form-label">هل الموظف مدرب</label>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_coach" value="1"
                           id="{{ $isEdit ? 'editiscoach' : 'iscoachnew' }}"
                           {{ !$isEdit && old('is_coach') ? 'checked' : '' }}>
                    <label class="form-check-label" for="{{ $isEdit ? 'editiscoach' : 'iscoachnew' }}">نعم</label>
                </div>
            </div>


            <div class="col-md-4 mb-3">
                <label class="form-label">{{ trans('employees.years_experience') }}</label>
                <input type="number" class="form-control" name="years_experience" min="0" max="80"
                       @if($isEdit) id="edit_years_experience" @endif
                       value="{{ $isEdit ? '' : old('years_experience') }}">
            </div>


            <div class="col-md-12 mb-3">
                <label class="form-label">{{ trans('employees.bio') }}</label>
                <textarea class="form-control" name="bio" rows="3" @if($isEdit) id="edit_bio" @endif>{{ $isEdit ? '' : old('bio') }}</textarea>
            </div>
        </div>
    </div>



    {{-- Finance --}}
    <div class="tab-pane fade" id="{{ $isEdit ? 'edit' : 'add' }}_tab_finance" role="tabpanel">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ trans('employees.compensation_type') }} <span class="text-danger">*</span></label>
                <select class="form-select compensation-type" name="compensation_type" @if($isEdit) id="edit_compensation_type" @endif>
                    <option value="salary_only">{{ trans('employees.salary_only') }}</option>
                    <option value="commission_only">{{ trans('employees.commission_only') }}</option>
                    <option value="salary_and_commission">{{ trans('employees.salary_and_commission') }}</option>
                </select>
                @if(!$isEdit)<small class="text-muted">{{ trans('employees.compensation_hint') }}</small>@endif
            </div>


            <div class="col-md-6 mb-3 transfer-box">
                <label class="form-label">{{ trans('employees.salary_transfer_method') }} <span class="text-danger">*</span></label>
                <select class="form-select" name="salary_transfer_method" @if($isEdit) id="edit_salary_transfer_method" @endif>
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
                <input type="number" step="0.01" class="form-control" name="base_salary"
                       @if($isEdit) id="edit_base_salary" @endif
                       value="{{ $isEdit ? '' : old('base_salary') }}">
            </div>

            {{-- NEW: Commission value type (Percent / Fixed) --}}
            <div class="col-md-4 mb-3 commission-box">
                <label class="form-label">{{ trans('employees.commission_value_type') ?? 'نوع العمولة' }}</label>
                <select class="form-select commission-value-type" name="commission_value_type"
                        @if($isEdit) id="edit_commission_value_type" @endif>
                    <option value="">{{ trans('settings_trans.choose') }}</option>
                    <option value="percent">{{ trans('employees.commission_percent') }}</option>
                    <option value="fixed">{{ trans('employees.commission_fixed') }}</option>
                </select>
                <small class="text-muted">{{ trans('employees.commission_value_type_hint') ?? 'اختر هل العمولة نسبة مئوية أم مبلغ ثابت.' }}</small>
            </div>


            {{-- percent --}}
            <div class="col-md-4 mb-3 commission-box commission-percent-box">
                <label class="form-label">{{ trans('employees.commission_percent') }}</label>
                <input type="number" step="0.01" class="form-control" name="commission_percent"
                       @if($isEdit) id="edit_commission_percent" @endif
                       value="{{ $isEdit ? '' : old('commission_percent') }}">
                @if(!$isEdit)<small class="text-muted">0 - 100</small>@endif
            </div>


            {{-- fixed --}}
            <div class="col-md-4 mb-3 commission-box commission-fixed-box">
                <label class="form-label">{{ trans('employees.commission_fixed') }}</label>
                <input type="number" step="0.01" class="form-control" name="commission_fixed"
                       @if($isEdit) id="edit_commission_fixed" @endif
                       value="{{ $isEdit ? '' : old('commission_fixed') }}">
            </div>


            <div class="col-md-12 mb-3 transfer-box">
                <label class="form-label">{{ trans('employees.salary_transfer_details') }}</label>
                <input type="text" class="form-control" name="salary_transfer_details"
                       @if($isEdit) id="edit_salary_transfer_details" @endif
                       value="{{ $isEdit ? '' : old('salary_transfer_details') }}"
                       placeholder="مثال: رقم المحفظة / رقم الحساب / اسم البنك ...">
            </div>
        </div>
    </div>



    {{-- Branches --}}
    <div class="tab-pane fade" id="{{ $isEdit ? 'edit' : 'add' }}_tab_branches" role="tabpanel">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ trans('employees.branches') }} <span class="text-danger">*</span></label>
                <select class="form-select" name="branches[]" multiple @if($isEdit) id="edit_branches" @endif>
                    @foreach($Branches as $Branch)
                        <option value="{{$Branch->id}}">{{$Branch->getTranslation('name','ar')}}</option>
                    @endforeach
                </select>
                @if(!$isEdit)<small class="text-muted">{{ trans('employees.branches_hint') }}</small>@endif
            </div>


            <div class="col-md-6 mb-3">
                <label class="form-label">{{ trans('employees.primary_branch') }} <span class="text-danger">*</span></label>
                <select class="form-select" name="primary_branch_id" @if($isEdit) id="edit_primary_branch_id" @endif>
                    <option value="">{{ trans('settings_trans.choose') }}</option>
                    @foreach($Branches as $Branch)
                        <option value="{{$Branch->id}}">{{$Branch->getTranslation('name','ar')}}</option>
                    @endforeach
                </select>
                @if(!$isEdit)<small class="text-muted">{{ trans('employees.primary_branch_hint') }}</small>@endif
            </div>


            <div class="col-md-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="status" value="1"
                           id="{{ $isEdit ? 'edit_status' : 'status_new' }}" {{ $isEdit ? '' : 'checked' }}>
                    <label class="form-check-label" for="{{ $isEdit ? 'edit_status' : 'status_new' }}">{{ trans('settings_trans.status_active') }}</label>
                </div>
            </div>
        </div>
    </div>


</div>
