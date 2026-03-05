@extends('layouts.master_table')

@section('title', trans('crm.new_prospect') . ' — CRM')

@section('content')
    {{-- Page Title --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font">
                    <i class="fas fa-user-tag me-1"></i>
                    {{ trans('crm.new_prospect') }}
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a
                                href="{{ route('crm.dashboard') }}">{{ trans('crm.dashboard_title') }}</a></li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('crm.prospects.index') }}">{{ trans('crm.seg_prospects') }}</a></li>
                        <li class="breadcrumb-item active">{{ trans('crm.new_prospect') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-3" dir="rtl">

        <form action="{{ route('crm.prospects.store') }}" method="POST" id="prospectForm">
            @csrf

            <div class="row g-3">

                {{-- ── البيانات الأساسية ──────────────────────────────── --}}
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-3">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-user text-primary me-2"></i>
                                {{ trans('crm.basic_info') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                {{-- الفرع --}}
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">
                                        {{ trans('crm.branch') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror"
                                        required>
                                        <option value="">{{ trans('crm.select_branch') }}</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}"
                                                {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('branch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- الاسم الأول --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        {{ trans('crm.first_name') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="first_name" value="{{ old('first_name') }}"
                                        class="form-control @error('first_name') is-invalid @enderror"
                                        placeholder="{{ trans('crm.first_name') }}" required>
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- الاسم الأخير --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        {{ trans('crm.last_name') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="last_name" value="{{ old('last_name') }}"
                                        class="form-control @error('last_name') is-invalid @enderror"
                                        placeholder="{{ trans('crm.last_name') }}" required>
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- الجنس --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ trans('crm.gender') }}</label>
                                    <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                        <option value="">-- {{ trans('crm.gender_unspecified') }} --</option>
                                        <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>{{ trans('crm.gender_male') }}
                                        </option>
                                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ trans('crm.gender_female') }}
                                        </option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- العنوان --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        {{ trans('crm.address') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="address" value="{{ old('address') }}"
                                        class="form-control @error('address') is-invalid @enderror"
                                        placeholder="{{ trans('crm.address') }}" required>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- ── بيانات التواصل ──────────────────────────────── --}}
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header bg-white border-0 pt-3">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-phone text-success me-2"></i>
                                {{ trans('crm.contact_info') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                {{-- الهاتف --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        {{ trans('crm.phone') }} <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="text" name="phone" value="{{ old('phone') }}"
                                            class="form-control @error('phone') is-invalid @enderror"
                                            placeholder="01xxxxxxxxx" required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- هاتف 2 --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ trans('crm.phone_extra') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="text" name="phone2" value="{{ old('phone2') }}"
                                            class="form-control @error('phone2') is-invalid @enderror"
                                            placeholder="01xxxxxxxxx">
                                        @error('phone2')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- واتساب --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ trans('crm.whatsapp') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text text-success">
                                            <i class="fab fa-whatsapp"></i>
                                        </span>
                                        <input type="text" name="whatsapp" value="{{ old('whatsapp') }}"
                                            class="form-control @error('whatsapp') is-invalid @enderror"
                                            placeholder="01xxxxxxxxx">
                                        @error('whatsapp')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- البريد الإلكتروني --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ trans('crm.email') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" name="email" value="{{ old('email') }}"
                                            class="form-control @error('email') is-invalid @enderror"
                                            placeholder="example@email.com">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── الشريط الجانبي ──────────────────────────────────── --}}
                <div class="col-xl-4">

                    {{-- ملاحظات --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-3">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-sticky-note text-warning me-2"></i>
                                {{ trans('crm.notes') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <textarea name="notes" rows="5" class="form-control @error('notes') is-invalid @enderror"
                                placeholder="{{ trans('crm.notes_placeholder') }}">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- متابعة أولى — اختيارية ──────────────────────────────── --}}
                    <div class="card border-0 shadow-sm mt-3" id="followupCard">
                        <div class="card-header bg-white border-0 pt-3">
                            <div class="form-check form-switch d-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" name="create_followup"
                                    id="createFollowupToggle" value="1"
                                    {{ old('create_followup') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold mb-0" for="createFollowupToggle">
                                    <i class="fas fa-calendar-check text-primary me-1"></i>
                                    {{ trans('crm.schedule_first_followup') }}
                                </label>
                            </div>
                        </div>
                        <div class="card-body d-none" id="followupFields">
                            <div class="row g-2">

                                {{-- نوع المتابعة --}}
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">
                                        {{ trans('crm.followup_type') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="followup_type"
                                        class="form-select form-select-sm @error('followup_type') is-invalid @enderror"
                                        id="followupType">
                                        <option value="">{{ trans('crm.select_type') }}</option>
                                        @foreach ($followupTypes as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ old('followup_type') === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('followup_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- تاريخ ووقت المتابعة --}}
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">{{ trans('crm.followup_datetime') }}</label>
                                    <input type="datetime-local" name="followup_datetime"
                                        value="{{ old('followup_datetime', \Carbon\Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d\TH:i')) }}"
                                        min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}"
                                        class="form-control form-control-sm @error('followup_datetime') is-invalid @enderror">
                                    @error('followup_datetime')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ trans('crm.followup_datetime') }}: 2026-03-04 16:20</small>
                                </div>


                                {{-- ملاحظات --}}
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">{{ trans('crm.followup_notes') }}</label>
                                    <textarea name="followup_notes" rows="2" class="form-control form-control-sm"
                                        placeholder="{{ trans('crm.followup_notes_ph') }}">{{ old('followup_notes') }}</textarea>
                                </div>

                            </div>
                        </div>
                    </div>




                    {{-- أزرار الحفظ --}}
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-body d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                {{ trans('crm.save_prospect') }}
                            </button>
                            <a href="{{ route('crm.prospects.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                {{ trans('crm.cancel') }}
                            </a>
                        </div>
                    </div>

                </div>

            </div>{{-- end row --}}

        </form>

    </div>{{-- end container --}}
    {{-- JS: Toggle followup fields --}}
    <script>
        document.getElementById('createFollowupToggle').addEventListener('change', function() {
            const fields = document.getElementById('followupFields');
            const typeField = document.getElementById('followupType');
            if (this.checked) {
                fields.classList.remove('d-none');
                typeField.setAttribute('required', 'required');
            } else {
                fields.classList.add('d-none');
                typeField.removeAttribute('required');
            }
        });

        // حافظ على الحالة عند validation error
        @if (old('create_followup'))
            document.getElementById('createFollowupToggle').dispatchEvent(new Event('change'));
        @endif
    </script>
@endsection
