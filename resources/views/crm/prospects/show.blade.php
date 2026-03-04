@extends('layouts.master_table')

@section('title', $prospect->full_name)

@section('content')
{{-- Page Title --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="fas fa-user-tag me-1"></i>
                {{ $prospect->full_name }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('crm.dashboard') }}">{{ trans('crm.dashboard_title') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('crm.prospects.index') }}">{{ trans('crm.seg_prospects') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $prospect->full_name }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-3" dir="rtl">

    <div class="row g-3">

        {{-- ── البيانات الأساسية ──────────────────────────────── --}}
        <div class="col-xl-8">

            {{-- بطاقة المعلومات الشخصية --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-user text-primary me-2"></i>
                        المعلومات الشخصية
                    </h6>
                    <div class="d-flex gap-1">
                        <a href="{{ route('crm.prospects.edit', $prospect->id) }}"
                           class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-edit me-1"></i> تعديل
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">الاسم الكامل</label>
                            <div class="fw-semibold">{{ $prospect->full_name }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted small d-block mb-1">الجنس</label>
                            <div>
                                @if($prospect->gender === 'male')
                                    <span class="badge bg-primary">
                                        <i class="fas fa-mars me-1"></i> ذكر
                                    </span>
                                @elseif($prospect->gender === 'female')
                                    <span class="badge bg-danger">
                                        <i class="fas fa-venus me-1"></i> أنثى
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted small d-block mb-1">الفرع</label>
                            <div>
                                <span class="badge bg-light text-dark">{{ $prospect->branch->name ?? '—' }}</span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted small d-block mb-1">العنوان</label>
                            <div class="fw-semibold">{{ $prospect->address ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- بطاقة معلومات التواصل --}}
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-phone text-success me-2"></i>
                        معلومات التواصل
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">الهاتف الأساسي</label>
                            <div>
                                <a href="tel:{{ $prospect->phone }}" class="text-decoration-none">
                                    <i class="fas fa-phone text-primary me-1"></i>
                                    {{ $prospect->phone }}
                                </a>
                            </div>
                        </div>
                        @if($prospect->phone2)
                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">هاتف إضافي</label>
                            <div>
                                <a href="tel:{{ $prospect->phone2 }}" class="text-decoration-none">
                                    <i class="fas fa-phone text-primary me-1"></i>
                                    {{ $prospect->phone2 }}
                                </a>
                            </div>
                        </div>
                        @endif
                        @if($prospect->whatsapp)
                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">واتساب</label>
                            <div>
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $prospect->whatsapp) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-success">
                                    <i class="fab fa-whatsapp me-1"></i>
                                    {{ $prospect->whatsapp }}
                                </a>
                            </div>
                        </div>
                        @endif
                        @if($prospect->email)
                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">البريد الإلكتروني</label>
                            <div>
                                <a href="mailto:{{ $prospect->email }}" class="text-decoration-none">
                                    <i class="fas fa-envelope text-primary me-1"></i>
                                    {{ $prospect->email }}
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- سجل المتابعات --}}
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-0 pt-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-history text-warning me-2"></i>
                        سجل المتابعات
                        <span class="badge bg-primary ms-1">{{ $prospect->followups->count() }}</span>
                    </h6>
                    <div class="d-flex gap-2 align-items-center">
                        @if($prospect->followups->count() > 0)
                            <a href="{{ route('crm.followups.index', [
                                    'quick'  => 'prospect',
                                    'type'   => 'prospect',
                                    'search' => $prospect->full_name,
                               ]) }}"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-list me-1"></i> كل المتابعات
                            </a>
                        @endif

                        {{-- ✅ يفتح الموديل مباشرة --}}
                        <button type="button"
                                class="btn btn-sm btn-primary"
                                onclick="openProspectFollowupModal()">
                            <i class="fas fa-plus me-1"></i> متابعة جديدة
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($prospect->followups->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-list fa-3x mb-2"></i>
                            <p class="mb-0">لا توجد متابعات حتى الآن</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>النوع</th>
                                        <th>الأولوية</th>
                                        <th>الحالة</th>
                                        <th>الموعد</th>
                                        <th>الملاحظات</th>
                                        <th class="text-center">عرض</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($prospect->followups as $followup)
                                    <tr class="{{ $followup->is_overdue ? 'table-danger' : '' }}">
                                        <td>
                                            <span class="badge bg-{{ $followup->type_badge_class }}">
                                                {{ $followup->type_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $followup->priority_badge_class }}">
                                                {{ $followup->priority_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $followup->status_badge_class }}">
                                                {{ $followup->status_label }}
                                            </span>
                                            @if($followup->is_overdue)
                                                <span class="badge bg-danger ms-1">متأخر</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $followup->next_action_at?->format('Y-m-d h:i A') ?? '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            <small>{{ Str::limit($followup->notes, 50) }}</small>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('crm.followups.index', [
                                                    'quick'       => 'all',
                                                    'followup_id' => $followup->id,
                                                ]) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="عرض هذه المتابعة">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ── الشريط الجانبي ──────────────────────────────────── --}}
        <div class="col-xl-4">

            {{-- إجراءات سريعة --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-bolt text-warning me-2"></i>
                        إجراءات سريعة
                    </h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <form action="{{ route('crm.prospects.convert', $prospect->id) }}"
                          method="POST"
                          onsubmit="return confirm('هل أنت متأكد من تحويل هذا العضو المحتمل إلى عضو فعلي؟')">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-user-check me-1"></i>
                            تحويل لعضو فعلي
                        </button>
                    </form>

                    {{-- ✅ يفتح الموديل مباشرة --}}
                    <button type="button" class="btn btn-primary" onclick="openProspectFollowupModal()">
                        <i class="fas fa-plus me-1"></i>
                        متابعة جديدة
                    </button>

                    @if($prospect->whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $prospect->whatsapp) }}"
                       target="_blank"
                       class="btn btn-outline-success">
                        <i class="fab fa-whatsapp me-1"></i>
                        إرسال واتساب
                    </a>
                    @endif

                    <a href="tel:{{ $prospect->phone }}" class="btn btn-outline-primary">
                        <i class="fas fa-phone me-1"></i>
                        اتصال مباشر
                    </a>

                    <hr class="my-2">

                    <button type="button"
                            class="btn btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#disqualifyModal">
                        <i class="fas fa-user-times me-1"></i>
                        إلغاء / غير مهتم
                    </button>
                </div>
            </div>

            {{-- ملاحظات --}}
            @if($prospect->notes)
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-sticky-note text-warning me-2"></i>
                        ملاحظات
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted small">{{ $prospect->notes }}</p>
                </div>
            </div>
            @endif

            {{-- معلومات النظام --}}
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-info-circle text-secondary me-2"></i>
                        معلومات النظام
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">تاريخ الإضافة:</span>
                            <span class="fw-semibold">{{ $prospect->created_at->format('Y-m-d h:i A') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">آخر تحديث:</span>
                            <span class="fw-semibold">{{ $prospect->updated_at->diffForHumans() }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">الحالة:</span>
                            <span class="badge" style="background:#6f42c1">عضو محتمل</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>{{-- end row --}}

</div>{{-- end container --}}

{{-- ── Modal: Disqualify ───────────────────────────────────── --}}
<div class="modal fade" id="disqualifyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('crm.prospects.disqualify', $prospect->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إلغاء العضو المحتمل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        سيتم إلغاء العضو المحتمل وإغلاق جميع متابعاته
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">سبب الإلغاء</label>
                        <textarea name="reason"
                                  class="form-control"
                                  rows="3"
                                  placeholder="مثال: غير مهتم، انتقل لمكان آخر، لم يرد..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">تأكيد الإلغاء</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal: إضافة متابعة ──────────────────────────────────── --}}
@include('crm.followups._modal_form')

{{-- ── Toast Container ─────────────────────────────────────── --}}
<div id="fu-toast-wrap"
     style="position:fixed;bottom:24px;left:24px;z-index:9999;
            display:flex;flex-direction:column;gap:8px;pointer-events:none;">
</div>

<script>
(function () {
    const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const URL_STORE = '{{ route('crm.followups.store') }}';

    // ── بيانات العضو المحتمل (PHP → JS) ─────────────────────
    const PROSPECT = {
        id:       {{ $prospect->id }},
        name:     '{{ addslashes($prospect->full_name) }}',
        branchId: '{{ $prospect->branch_id ?? '' }}'
    };

    // ── Toast ─────────────────────────────────────────────────
    function toast(msg, type) {
        type = type || 'success';
        const colors = { success: '#28a745', error: '#dc3545', info: '#0d6efd' };
        const wrap = document.getElementById('fu-toast-wrap');
        const el   = document.createElement('div');
        el.style.cssText = `
            background:#323232;color:#fff;padding:10px 18px;border-radius:8px;
            font-size:0.87rem;opacity:0;transform:translateY(10px);
            transition:all .25s ease;max-width:340px;pointer-events:none;
            border-right:4px solid ${colors[type] || colors.info};
        `;
        el.textContent = msg;
        wrap.appendChild(el);
        requestAnimationFrame(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; });
        setTimeout(() => {
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 300);
        }, 2800);
    }

    // ── فتح الموديل مع تثبيت العضو المحتمل ───────────────────
    window.openProspectFollowupModal = function() {
        const formEl = document.getElementById('fuForm');
        delete formEl.dataset.currentId;

        document.getElementById('fuMethod').innerHTML  = '';
        document.getElementById('fuTitle').textContent = 'متابعة جديدة — ' + PROSPECT.name;

        // ضبط الحقول الافتراضية
        document.getElementById('fu_type').value     = 'prospect';
        document.getElementById('fu_status').value   = 'pending';
        document.getElementById('fu_priority').value = 'medium';
        document.getElementById('fu_notes').value    = '';
        document.getElementById('fu_result').value   = '';

        // موعد المتابعة: غداً الساعة 10
        const d  = new Date(); d.setDate(d.getDate() + 1);
        const p  = n => String(n).padStart(2, '0');
        document.getElementById('fu_next_action_at').value =
            `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}T10:00`;

        // ✅ تثبيت العضو المحتمل
        window.fuSetLockedMember(PROSPECT.id, PROSPECT.name, PROSPECT.branchId);

        new bootstrap.Modal(document.getElementById('fuModal')).show();
    };

    // ── إرسال الفورم AJAX ─────────────────────────────────────
    document.getElementById('fuForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const fd      = new FormData(this);
        const method  = (fd.get('_method') === 'PUT') ? 'PUT' : 'POST';
        const id      = this.dataset.currentId || '';
        const url     = (method === 'PUT')
            ? '{{ route('crm.followups.update', '__ID__') }}'.replace('__ID__', id)
            : URL_STORE;

        const body = {
            member_id:      fd.get('member_id'),
            branch_id:      fd.get('branch_id'),
            type:           fd.get('type'),
            status:         fd.get('status'),
            priority:       fd.get('priority'),
            next_action_at: fd.get('next_action_at') || null,
            notes:          fd.get('notes') || null,
            result:         fd.get('result') || null,
        };
        if (method !== 'POST') body._method = method;

        const submitBtn = this.querySelector('[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الحفظ...';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(body)
        })
        .then(r => r.json())
        .then(res => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ';

            if (res && res.success) {
                toast(res.message || 'تم حفظ المتابعة بنجاح', 'success');
                bootstrap.Modal.getInstance(document.getElementById('fuModal'))?.hide();

                // ✅ إعادة تحميل الصفحة لتحديث جدول المتابعات
                setTimeout(() => window.location.reload(), 800);
            } else {
                toast(res.message || 'تعذّر الحفظ', 'error');
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ';
            toast('تعذّر الاتصال بالخادم', 'error');
        });
    });

    // ── إعادة الوضع الطبيعي عند إغلاق الموديل ────────────────
    document.getElementById('fuModal').addEventListener('hidden.bs.modal', function() {
        window.fuSetLockedMember(null);

        const branchSel = document.getElementById('fu_branch_id');
        branchSel.value    = '';
        branchSel.disabled = false;

        document.getElementById('fu_member_id').setAttribute('name', 'member_id');
        document.getElementById('fu_member_id').disabled = true;
        document.getElementById('fu_member_hint').textContent = 'اختر الفرع أولاً لتفعيل البحث';
    });
})();
</script>
@endsection
