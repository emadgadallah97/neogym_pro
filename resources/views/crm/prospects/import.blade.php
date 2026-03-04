@extends('layouts.master_table')

@section('title', 'رفع بيانات أعضاء محتملين')

@section('content')
{{-- Page Title --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="fas fa-file-excel me-1"></i>
                رفع بيانات أعضاء محتملين
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('crm.dashboard') }}">{{ trans('crm.dashboard_title') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('crm.prospects.index') }}">{{ trans('crm.seg_prospects') }}</a></li>
                    <li class="breadcrumb-item active">رفع Excel</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-3" dir="rtl">

    <div class="row justify-content-center">
        <div class="col-xl-8">

            {{-- تعليمات --}}
            <div class="card border-0 shadow-sm border-start border-info border-4 mb-3">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle fa-2x text-info"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-2">تعليمات هامة قبل الرفع</h6>
                            <ul class="mb-0 small text-muted">
                                <li>يجب أن يكون الملف بصيغة <code>.xlsx</code> أو <code>.xls</code></li>
                                <li>الصف الأول يحتوي على العناوين — البيانات تبدأ من الصف الثاني</li>
                                <li>الحقول المطلوبة: <strong>branch_id, first_name, last_name, phone, address</strong></li>
                                <li>سيتم تجاهل الصفوف الفارغة أو غير الصحيحة</li>
                                <li>الحد الأقصى: 500 صف في المرة الواحدة</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- نموذج الرفع --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-upload text-success me-2"></i>
                        رفع ملف Excel
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('crm.prospects.import.store') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          id="importForm">
                        @csrf

                        {{-- رفع الملف --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">اختر ملف Excel</label>
                            <input type="file"
                                   name="file"
                                   class="form-control @error('file') is-invalid @enderror"
                                   accept=".xlsx,.xls"
                                   required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">الحد الأقصى: 5 ميجابايت</small>
                        </div>

                        {{-- ✅ متابعة أولى اختيارية أثناء الاستيراد --}}
                        <div class="card border-0 shadow-sm mb-3 border-start border-primary border-4">
                            <div class="card-body">
                                <div class="form-check form-switch d-flex align-items-center gap-2 mb-2">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="create_followup"
                                           id="createFollowupToggle"
                                           value="1"
                                           {{ old('create_followup') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold mb-0" for="createFollowupToggle">
                                        جدولة متابعة أولى للمستوردين
                                    </label>
                                </div>

                                <div class="row g-2 d-none" id="followupFields">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">
                                            نوع المتابعة <span class="text-danger">*</span>
                                        </label>
                                        <select name="followup_type"
                                                class="form-select form-select-sm @error('followup_type') is-invalid @enderror"
                                                id="followupType">
                                            <option value="">-- اختر --</option>
                                            @foreach(($followupTypes ?? []) as $value => $label)
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

                                   <div class="col-md-6">
    <label class="form-label small fw-semibold">موعد المتابعة (تاريخ + وقت)</label>
    <input type="datetime-local"
           name="followup_datetime"
           value="{{ old('followup_datetime', \Carbon\Carbon::tomorrow()->setTime(10,0)->format('Y-m-d\TH:i')) }}"
           min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}"
           class="form-control form-control-sm @error('followup_datetime') is-invalid @enderror">
    @error('followup_datetime')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

                                </div>
                            </div>
                        </div>

                        {{-- أزرار --}}
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success" id="submitBtn">
                                <i class="fas fa-cloud-upload-alt me-1"></i>
                                بدء الرفع والمعالجة
                            </button>
                            <a href="{{ route('crm.prospects.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- تحميل القالب --}}
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-download text-primary me-2"></i>
                        تحميل قالب Excel
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        قم بتحميل القالب الجاهز، املأ البيانات حسب التنسيق المطلوب، ثم ارفعه مرة أخرى
                    </p>
                    <a href="{{ route('crm.prospects.download-template') }}"
                       class="btn btn-outline-primary">
                        <i class="fas fa-file-download me-1"></i>
                        تحميل قالب Excel فارغ
                    </a>
                </div>
            </div>

            {{-- شرح الأعمدة --}}
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-table text-warning me-2"></i>
                        شرح الأعمدة المطلوبة
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>اسم العمود</th>
                                    <th>مطلوب؟</th>
                                    <th>النوع</th>
                                    <th>مثال</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>branch_id</code></td>
                                    <td><span class="badge bg-danger">مطلوب</span></td>
                                    <td>رقم</td>
                                    <td>1</td>
                                    <td>رقم الفرع من جدول الفروع</td>
                                </tr>
                                <tr>
                                    <td><code>first_name</code></td>
                                    <td><span class="badge bg-danger">مطلوب</span></td>
                                    <td>نص</td>
                                    <td>أحمد</td>
                                    <td>الاسم الأول (حد أقصى 100 حرف)</td>
                                </tr>
                                <tr>
                                    <td><code>last_name</code></td>
                                    <td><span class="badge bg-danger">مطلوب</span></td>
                                    <td>نص</td>
                                    <td>محمد</td>
                                    <td>الاسم الأخير (حد أقصى 100 حرف)</td>
                                </tr>
                                <tr>
                                    <td><code>phone</code></td>
                                    <td><span class="badge bg-danger">مطلوب</span></td>
                                    <td>نص</td>
                                    <td>01012345678</td>
                                    <td>رقم الهاتف الأساسي</td>
                                </tr>
                                <tr>
                                    <td><code>address</code></td>
                                    <td><span class="badge bg-danger">مطلوب</span></td>
                                    <td>نص</td>
                                    <td>المعادي، القاهرة</td>
                                    <td>العنوان الكامل</td>
                                </tr>
                                <tr>
                                    <td><code>phone2</code></td>
                                    <td><span class="badge bg-secondary">اختياري</span></td>
                                    <td>نص</td>
                                    <td>01098765432</td>
                                    <td>رقم هاتف إضافي</td>
                                </tr>
                                <tr>
                                    <td><code>whatsapp</code></td>
                                    <td><span class="badge bg-secondary">اختياري</span></td>
                                    <td>نص</td>
                                    <td>01012345678</td>
                                    <td>رقم واتساب</td>
                                </tr>
                                <tr>
                                    <td><code>email</code></td>
                                    <td><span class="badge bg-secondary">اختياري</span></td>
                                    <td>بريد</td>
                                    <td>ahmed@example.com</td>
                                    <td>بريد إلكتروني صحيح</td>
                                </tr>
                                <tr>
                                    <td><code>gender</code></td>
                                    <td><span class="badge bg-secondary">اختياري</span></td>
                                    <td>نص</td>
                                    <td>male أو female</td>
                                    <td>الجنس (male/female فقط)</td>
                                </tr>
                                <tr>
                                    <td><code>notes</code></td>
                                    <td><span class="badge bg-secondary">اختياري</span></td>
                                    <td>نص</td>
                                    <td>عرف عنا من الفيسبوك</td>
                                    <td>ملاحظات إضافية</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
document.getElementById('importForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري الرفع والمعالجة...';
});

document.getElementById('createFollowupToggle').addEventListener('change', function () {
    const fields    = document.getElementById('followupFields');
    const typeField = document.getElementById('followupType');

    if (this.checked) {
        fields.classList.remove('d-none');
        typeField.setAttribute('required', 'required');
    } else {
        fields.classList.add('d-none');
        typeField.removeAttribute('required');
    }
});

@if(old('create_followup'))
document.getElementById('createFollowupToggle').dispatchEvent(new Event('change'));
@endif
</script>
@endsection
