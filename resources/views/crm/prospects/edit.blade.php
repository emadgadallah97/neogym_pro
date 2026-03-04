@extends('layouts.master_table')

@section('title', 'تعديل: ' . $prospect->full_name)

@section('content')
    {{-- Page Title --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font">
                    <i class="fas fa-edit me-1"></i>
                    تعديل: {{ $prospect->full_name }}
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a
                                href="{{ route('crm.dashboard') }}">{{ trans('crm.dashboard_title') }}</a></li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('crm.prospects.index') }}">{{ trans('crm.seg_prospects') }}</a></li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('crm.prospects.show', $prospect->id) }}">{{ $prospect->full_name }}</a></li>
                        <li class="breadcrumb-item active">تعديل</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-3" dir="rtl">

        <form action="{{ route('crm.prospects.update', $prospect->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">

                {{-- ── البيانات الأساسية ──────────────────────────────── --}}
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-3">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-user text-primary me-2"></i>
                                البيانات الأساسية
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                {{-- الفرع --}}
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">
                                        الفرع <span class="text-danger">*</span>
                                    </label>
                                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror"
                                        required>
                                        <option value="">-- اختر الفرع --</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}"
                                                {{ old('branch_id', $prospect->branch_id) == $branch->id ? 'selected' : '' }}>
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
                                        الاسم الأول <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="first_name"
                                        value="{{ old('first_name', $prospect->first_name) }}"
                                        class="form-control @error('first_name') is-invalid @enderror" required>
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- الاسم الأخير --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        الاسم الأخير <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="last_name"
                                        value="{{ old('last_name', $prospect->last_name) }}"
                                        class="form-control @error('last_name') is-invalid @enderror" required>
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- الجنس --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">الجنس</label>
                                    <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                        <option value="">-- غير محدد --</option>
                                        <option value="male"
                                            {{ old('gender', $prospect->gender) === 'male' ? 'selected' : '' }}>ذكر
                                        </option>
                                        <option value="female"
                                            {{ old('gender', $prospect->gender) === 'female' ? 'selected' : '' }}>أنثى
                                        </option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- العنوان --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        العنوان <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="address" value="{{ old('address', $prospect->address) }}"
                                        class="form-control @error('address') is-invalid @enderror" required>
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
                                بيانات التواصل
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                {{-- الهاتف --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        رقم الهاتف <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="text" name="phone" value="{{ old('phone', $prospect->phone) }}"
                                            class="form-control @error('phone') is-invalid @enderror" required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- هاتف 2 --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">رقم هاتف إضافي</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="text" name="phone2" value="{{ old('phone2', $prospect->phone2) }}"
                                            class="form-control @error('phone2') is-invalid @enderror">
                                        @error('phone2')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- واتساب --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">واتساب</label>
                                    <div class="input-group">
                                        <span class="input-group-text text-success">
                                            <i class="fab fa-whatsapp"></i>
                                        </span>
                                        <input type="text" name="whatsapp"
                                            value="{{ old('whatsapp', $prospect->whatsapp) }}"
                                            class="form-control @error('whatsapp') is-invalid @enderror">
                                        @error('whatsapp')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- البريد الإلكتروني --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">البريد الإلكتروني</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" name="email"
                                            value="{{ old('email', $prospect->email) }}"
                                            class="form-control @error('email') is-invalid @enderror">
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
                                ملاحظات
                            </h6>
                        </div>
                        <div class="card-body">
                            <textarea name="notes" rows="5" class="form-control @error('notes') is-invalid @enderror"
                                placeholder="أي معلومات إضافية...">{{ old('notes', $prospect->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- ✅ جدولة متابعة جديدة — اختيارية ──────────────── --}}
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header bg-white border-0 pt-3">
                            <div class="form-check form-switch d-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" name="create_followup"
                                    id="createFollowupToggle" value="1"
                                    {{ old('create_followup') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold mb-0" for="createFollowupToggle">
                                    <i class="fas fa-calendar-plus text-primary me-1"></i>
                                    جدولة متابعة جديدة
                                </label>
                            </div>
                        </div>
                        <div class="card-body d-none" id="followupFields">
                            <div class="row g-2">

                                {{-- نوع المتابعة --}}
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">
                                        نوع المتابعة <span class="text-danger">*</span>
                                    </label>
                                    <select name="followup_type"
                                        class="form-select form-select-sm @error('followup_type') is-invalid @enderror"
                                        id="followupType">
                                        <option value="">-- اختر --</option>
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
                                    <label class="form-label small fw-semibold">موعد المتابعة (تاريخ + وقت)</label>
                                    <input type="datetime-local" name="followup_datetime"
                                        value="{{ old('followup_datetime', \Carbon\Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d\TH:i')) }}"
                                        min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}"
                                        class="form-control form-control-sm @error('followup_datetime') is-invalid @enderror">
                                    @error('followup_datetime')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">مثال: 2026-03-04 16:20</small>
                                </div>


                                {{-- ملاحظات المتابعة --}}
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">ملاحظات المتابعة</label>
                                    <textarea name="followup_notes" rows="2" class="form-control form-control-sm"
                                        placeholder="تفاصيل المتابعة...">{{ old('followup_notes') }}</textarea>
                                </div>

                                {{-- عدد المتابعات الحالية --}}
                                @if ($prospect->followups->count() > 0)
                                    <div class="col-12">
                                        <div class="alert alert-light border py-2 mb-0 small">
                                            <i class="fas fa-info-circle text-primary me-1"></i>
                                            يوجد حالياً
                                            <strong>{{ $prospect->followups->count() }}</strong>
                                            متابعة مسجلة —
                                            <a href="{{ route('crm.prospects.show', $prospect->id) }}"
                                                class="text-primary">
                                                عرضها
                                            </a>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>

                    {{-- أزرار الحفظ --}}
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-body d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                حفظ التعديلات
                            </button>
                            <a href="{{ route('crm.prospects.show', $prospect->id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
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
