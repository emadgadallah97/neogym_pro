{{-- resources/views/crm/followups/index.blade.php --}}
@extends('layouts.master_table')

@section('title', 'المتابعات — CRM')

@section('css')
<style>
    .fu-table { width: 100%; border-collapse: collapse; }
    .fu-table thead th {
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        color: #495057;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 10px 12px;
        white-space: nowrap;
    }
    .fu-table tbody td {
        border-bottom: 1px solid #f0f0f0;
        padding: 10px 12px;
        vertical-align: middle;
        white-space: nowrap;
    }
    .fu-table tbody tr:hover td { background-color: rgba(13,110,253,0.03); }

    .fu-actions { display:flex; gap:6px; justify-content:center; align-items:center; flex-wrap:nowrap; }
    .fu-actions .btn { padding:4px 10px !important; line-height:1.2; font-size:12px; min-width:72px; }

    .fu-badges { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
    .fu-note { white-space:normal; max-width:340px; }
    .fu-muted { color:#6c757d; font-size:0.82rem; }

    .fu-pagination .pagination { margin-bottom:0; }
    .fu-pagination .page-link  { min-width:36px; text-align:center; }

    .nav-pills .nav-link { font-size:0.85rem; }

    .fu-int-box {
        background:#f8f9fa;
        border:1px solid #e9ecef;
        border-radius:10px;
        padding:12px;
        margin-top:8px;
    }

    tr.fu-has-int td { background: rgba(13,110,253,0.05); }
    tr.fu-has-int:hover td { background: rgba(13,110,253,0.08); }

    #fu-toast-wrap {
        position:fixed; bottom:24px; left:24px;
        z-index:9999; display:flex;
        flex-direction:column; gap:8px;
        pointer-events:none;
    }
    .fu-toast {
        background:#323232; color:#fff;
        padding:10px 18px; border-radius:8px;
        font-size:0.87rem; opacity:0;
        transform:translateY(10px);
        transition:all .25s ease;
        pointer-events:none;
        max-width:340px;
    }
    .fu-toast.show { opacity:1; transform:translateY(0); }
    .fu-toast.success { border-right:4px solid #28a745; }
    .fu-toast.error   { border-right:4px solid #dc3545; }

    #fu-loading {
        position: fixed;
        inset: 0;
        background: rgba(255,255,255,0.7);
        z-index: 9998;
        display: none;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(2px);
    }
    #fu-loading .box {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.08);
        color: #495057;
        font-weight: 600;
        font-size: 0.92rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-3" dir="rtl">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item">
                        <a href="{{ route('crm.dashboard') }}" class="text-decoration-none">CRM</a>
                    </li>
                    <li class="breadcrumb-item active">المتابعات</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0">المتابعات</h4>
        </div>
        <button type="button" class="btn btn-warning btn-sm" onclick="fuOpenCreateModal()">
            <i class="fas fa-plus me-1"></i> إضافة متابعة
        </button>
    </div>

    {{-- Tabs --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3" id="fuTabsWrap">
            @include('crm.followups._tabs')
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            {{--
                ✅ حذف hidden quick من هنا — يُقرأ من الـ URL تلقائياً في الـ JS
                حتى لا يتعارض مع تنقل التابات AJAX
            --}}
            <form id="fuFilterForm" method="GET" action="{{ route('crm.followups.index') }}" class="row g-2 align-items-end">

                {{-- بحث --}}
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-0">بحث</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search fa-xs text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="اسم / كود / هاتف..." value="{{ $search }}">
                        @if($search)
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="this.previousElementSibling.value='';document.getElementById('fuFilterForm').dispatchEvent(new Event('submit'));">
                                مسح
                            </button>
                        @endif
                    </div>
                </div>

                {{-- الفرع --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-0">الفرع</label>
                    <select name="branch_id" class="form-select">
                        <option value="">جميع الفروع</option>
                        @foreach($branches as $br)
                            <option value="{{ $br->id }}" {{ (string)$branchId === (string)$br->id ? 'selected' : '' }}>
                                {{ is_array($br->name) ? ($br->name[app()->getLocale()] ?? $br->name['ar'] ?? '') : $br->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- النوع --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-0">النوع</label>
                    <select name="type" class="form-select">
                        <option value="">كل الأنواع</option>
                        @foreach($typeLabels as $k => $v)
                            <option value="{{ $k }}" {{ $type === $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- الأولوية --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-0">الأولوية</label>
                    <select name="priority" class="form-select">
                        <option value="">كل الأولويات</option>
                        @foreach($priorityLabels as $k => $v)
                            <option value="{{ $k }}" {{ $priority === $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- موعد المتابعة (من) --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-0">موعد المتابعة (من)</label>
                    <input type="date" name="next_from" class="form-control"
                           value="{{ $nextFrom ?? '' }}">
                </div>

                {{-- موعد المتابعة (إلى) --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-0">موعد المتابعة (إلى)</label>
                    <input type="date" name="next_to" class="form-control"
                           value="{{ $nextTo ?? '' }}">
                </div>

                {{-- أزرار --}}
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-search me-1"></i> تطبيق
                    </button>
                    @if($search || $branchId || $type || $priority || ($quick && $quick !== 'all') || ($nextFrom ?? null) || ($nextTo ?? null))
                        <a href="{{ route('crm.followups.index') }}"
                           class="btn btn-outline-secondary"
                           data-fu-ajax="1">
                            <i class="fas fa-redo"></i>
                        </a>
                    @endif
                </div>

            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm" id="fu-card">
        <div class="card-body p-0" id="fuTableWrap">
            @include('crm.followups._table')
        </div>
    </div>

</div>

{{-- Loading --}}
<div id="fu-loading">
    <div class="box">
        <i class="fas fa-circle-notch fa-spin me-2"></i>جاري التحميل...
    </div>
</div>

{{-- Toast --}}
<div id="fu-toast-wrap"></div>

{{-- Confirm Modal --}}
@include('crm.followups._confirm_modal')

{{-- Modal Form --}}
@include('crm.followups._modal_form')

<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const URL_PARTIAL = function(url){
        const u = new URL(url, window.location.origin);
        u.searchParams.set('partial', '1');
        return u.toString();
    };

    const URL_STORE       = '{{ route('crm.followups.store') }}';
    const URL_UPDATE      = '{{ route('crm.followups.update', '__ID__') }}';
    const URL_DESTROY     = '{{ route('crm.followups.destroy', '__ID__') }}';
    const URL_DONE        = '{{ route('crm.followups.done', '__ID__') }}';
    const URL_INT_STORE   = '{{ route('crm.interactions.store') }}';
    const URL_INT_DESTROY = '{{ route('crm.interactions.destroy', '__ID__') }}';

    // ── UI helpers ─────────────────────────────────────────
    function showLoading(on){ document.getElementById('fu-loading').style.display = on ? 'flex' : 'none'; }

    function toast(msg, type) {
        type = type || 'success';
        const wrap = document.getElementById('fu-toast-wrap');
        const el   = document.createElement('div');
        el.className = 'fu-toast ' + type;
        el.textContent = msg;
        wrap.appendChild(el);
        requestAnimationFrame(() => el.classList.add('show'));
        setTimeout(() => {
            el.classList.remove('show');
            setTimeout(() => el.remove(), 300);
        }, 2800);
    }

    // ── Confirm modal ──────────────────────────────────────
    let fuConfirmCb = null;
    window.fuConfirm = function(opts){
        opts = opts || {};
        document.getElementById('fuConfirmTitle').textContent = opts.title || 'تأكيد';
        document.getElementById('fuConfirmBody').textContent  = opts.body  || 'هل أنت متأكد؟';

        const ok = document.getElementById('fuConfirmOk');
        ok.textContent = opts.okText || 'تأكيد';
        ok.className = 'btn btn-sm ' + (opts.okClass || 'btn-primary');

        fuConfirmCb = typeof opts.onConfirm === 'function' ? opts.onConfirm : null;
        new bootstrap.Modal(document.getElementById('fuConfirmModal')).show();
    };

    document.getElementById('fuConfirmOk').addEventListener('click', function(){
        const modalEl = document.getElementById('fuConfirmModal');
        bootstrap.Modal.getInstance(modalEl)?.hide();
        if (fuConfirmCb) fuConfirmCb();
        fuConfirmCb = null;
    });

    // ── AJAX helpers ───────────────────────────────────────
    function ajaxJson(url, payload, method) {
        method = method || 'POST';
        const body = Object.assign({}, payload || {});
        if (method !== 'POST') body._method = method;

        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(body)
        }).then(r => r.json());
    }

    async function fuLoad(url, pushState){
        showLoading(true);
        try {
            const res = await fetch(URL_PARTIAL(url), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).then(r => r.json());

            if (!res || !res.tabs_html || !res.table_html) {
                toast('تعذّر تحميل البيانات', 'error');
                showLoading(false);
                return;
            }

            document.getElementById('fuTabsWrap').innerHTML  = res.tabs_html;
            document.getElementById('fuTableWrap').innerHTML = res.table_html;

            if (pushState) window.history.pushState({}, '', url);

        } catch(e) {
            toast('تعذّر الاتصال بالخادم', 'error');
        }
        showLoading(false);
    }

    function fuReloadCurrent(){
        return fuLoad(window.location.href, false);
    }

    // ── Intercept tabs/pagination/ajax links ───────────────
    document.addEventListener('click', function(e){
        const a = e.target.closest('a[data-fu-ajax="1"]');
        if (!a) return;
        e.preventDefault();
        fuLoad(a.href, true);
    });

    // ── Filter form AJAX submit ────────────────────────────
    document.getElementById('fuFilterForm').addEventListener('submit', function(e){
        e.preventDefault();
        const fd = new FormData(this);
        const u  = new URL(this.action, window.location.origin);

        // ✅ قراءة quick من الـ URL الحالي (يتحدث مع التابات AJAX)
        // بدلاً من hidden field الذي لا يتحدث بعد AJAX navigation
        const currentUrl  = new URL(window.location.href);
        const currentQuick = currentUrl.searchParams.get('quick') || 'all';
        u.searchParams.set('quick', currentQuick);

        for (const [k, v] of fd.entries()) {
            if (k === 'quick') continue; // تجاهل أي quick قديم من الـ form
            if (v !== null && String(v).trim() !== '') {
                u.searchParams.set(k, v);
            } else {
                u.searchParams.delete(k);
            }
        }

        // ✅ إذا كان التاب prospect → أضف type=prospect تلقائياً
        if (currentQuick === 'prospect') {
            u.searchParams.set('type', 'prospect');
        }

        fuLoad(u.toString(), true);
    });

    // Back/Forward support
    window.addEventListener('popstate', function(){
        fuLoad(window.location.href, false);
    });

    // ── Followup modal submit via AJAX ─────────────────────
    document.getElementById('fuForm').addEventListener('submit', function(e){
        e.preventDefault();

        const fd     = new FormData(this);
        const method = (fd.get('_method') === 'PUT') ? 'PUT' : 'POST';
        const id     = this.dataset.currentId || '';
        const url    = (method === 'PUT')
            ? URL_UPDATE.replace('__ID__', id)
            : URL_STORE;

        const payload = {
            member_id:      fd.get('member_id'),
            branch_id:      fd.get('branch_id'),
            type:           fd.get('type'),
            status:         fd.get('status'),
            priority:       fd.get('priority'),
            next_action_at: fd.get('next_action_at') || null,
            notes:          fd.get('notes') || null,
            result:         fd.get('result') || null,
        };

        showLoading(true);
        ajaxJson(url, payload, method).then(function(res){
            showLoading(false);
            if (res && res.success){
                toast(res.message || 'تم الحفظ', 'success');
                bootstrap.Modal.getInstance(document.getElementById('fuModal'))?.hide();
                fuReloadCurrent();
            } else {
                toast('تعذّر الحفظ', 'error');
            }
        }).catch(function(){
            showLoading(false);
            toast('تعذّر الاتصال بالخادم', 'error');
        });
    });

    // ── Followup actions ───────────────────────────────────
    window.fuMarkDone = function(id){
        fuConfirm({
            title: 'إنهاء المتابعة',
            body:  'هل تريد إنهاء هذه المتابعة؟',
            okText: 'إنهاء',
            okClass: 'btn-success',
            onConfirm: function(){
                showLoading(true);
                ajaxJson(URL_DONE.replace('__ID__', id), {}, 'POST').then(function(res){
                    showLoading(false);
                    if (res && res.success){ toast(res.message || 'تمت العملية', 'success'); fuReloadCurrent(); }
                    else toast('حدث خطأ', 'error');
                }).catch(()=>{ showLoading(false); toast('تعذّر الاتصال', 'error'); });
            }
        });
    };

    window.fuCancelFollowup = function(id){
        fuConfirm({
            title: 'إلغاء المتابعة',
            body:  'هل تريد إلغاء هذه المتابعة؟',
            okText: 'إلغاء المتابعة',
            okClass: 'btn-outline-secondary',
            onConfirm: function(){
                showLoading(true);
                ajaxJson(URL_UPDATE.replace('__ID__', id), { status: 'cancelled' }, 'PUT').then(function(res){
                    showLoading(false);
                    if (res && res.success){ toast(res.message || 'تم الإلغاء', 'success'); fuReloadCurrent(); }
                    else toast('حدث خطأ', 'error');
                }).catch(()=>{ showLoading(false); toast('تعذّر الاتصال', 'error'); });
            }
        });
    };

    window.fuDeleteFollowup = function(id){
        fuConfirm({
            title: 'حذف المتابعة',
            body:  'سيتم حذف المتابعة نهائياً، هل أنت متأكد؟',
            okText: 'حذف',
            okClass: 'btn-danger',
            onConfirm: function(){
                showLoading(true);
                ajaxJson(URL_DESTROY.replace('__ID__', id), {}, 'DELETE').then(function(res){
                    showLoading(false);
                    if (res && res.success){ toast(res.message || 'تم الحذف', 'success'); fuReloadCurrent(); }
                    else toast('حدث خطأ', 'error');
                }).catch(()=>{ showLoading(false); toast('تعذّر الاتصال', 'error'); });
            }
        });
    };

    // ── Interaction actions ────────────────────────────────
    window.fuSaveInteraction = function(fuId, btn){
        const box = document.getElementById('fuIntForm' + fuId);
        if (!box) return;

        btn.disabled = true;

        const payload = {
            member_id:     box.dataset.member,
            followup_id:   box.dataset.followup,
            channel:       box.querySelector('.fu-int-channel').value,
            direction:     box.querySelector('.fu-int-direction').value,
            result:        box.querySelector('.fu-int-result').value,
            interacted_at: box.querySelector('.fu-int-date').value || null,
            notes:         box.querySelector('.fu-int-notes').value || null,
        };

        ajaxJson(URL_INT_STORE, payload, 'POST').then(function(res){
            btn.disabled = false;
            if (res && res.success){
                toast(res.message || 'تم حفظ التفاعل', 'success');
                fuReloadCurrent();
                box.querySelector('.fu-int-notes').value = '';
                box.querySelector('.fu-int-date').value  = '';
            } else {
                toast('تعذّر حفظ التفاعل', 'error');
            }
        }).catch(function(){
            btn.disabled = false;
            toast('تعذّر الاتصال بالخادم', 'error');
        });
    };

    window.fuDeleteInteraction = function(id){
        fuConfirm({
            title: 'إلغاء/حذف تفاعل',
            body:  'هل تريد حذف هذا التفاعل؟',
            okText: 'حذف التفاعل',
            okClass: 'btn-danger',
            onConfirm: function(){
                showLoading(true);
                ajaxJson(URL_INT_DESTROY.replace('__ID__', id), {}, 'DELETE').then(function(res){
                    showLoading(false);
                    if (res && res.success){ toast(res.message || 'تم حذف التفاعل', 'success'); fuReloadCurrent(); }
                    else toast('حدث خطأ', 'error');
                }).catch(()=>{ showLoading(false); toast('تعذّر الاتصال', 'error'); });
            }
        });
    };

    // ── Open edit modal ────────────────────────────────────
    window.fuOpenEditModal = function(btn){
        const formEl = document.getElementById('fuForm');
        formEl.dataset.currentId = btn.dataset.id;

        document.getElementById('fuMethod').innerHTML =
            '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('fuTitle').textContent = 'تعديل متابعة';

        document.getElementById('fu_branch_id').value      = btn.dataset.branchId  || '';
        document.getElementById('fu_type').value           = btn.dataset.type       || 'general';
        document.getElementById('fu_status').value         = btn.dataset.status     || 'pending';
        document.getElementById('fu_priority').value       = btn.dataset.priority   || 'medium';
        document.getElementById('fu_next_action_at').value = btn.dataset.nextAction || '';
        document.getElementById('fu_notes').value          = btn.dataset.notes      || '';
        document.getElementById('fu_result').value         = btn.dataset.result     || '';

        const memberEl = document.getElementById('fu_member_id');
        memberEl.disabled = false;
        document.getElementById('fu_member_hint').textContent = 'ابحث بالاسم أو الكود أو الهاتف';

        if (btn.dataset.memberId && typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            const opt = new Option(
                btn.dataset.memberText || btn.dataset.memberId,
                btn.dataset.memberId, true, true
            );
            jQuery('#fu_member_id').empty().append(opt).trigger('change');
        }

        new bootstrap.Modal(document.getElementById('fuModal')).show();
    };

    // ── Create modal ───────────────────────────────────────
    window.fuOpenCreateModal = function(){
        const formEl = document.getElementById('fuForm');
        delete formEl.dataset.currentId;

        document.getElementById('fuMethod').innerHTML  = '';
        document.getElementById('fuTitle').textContent = 'إضافة متابعة جديدة';

        document.getElementById('fu_branch_id').value      = '';
        document.getElementById('fu_member_id').disabled   = true;
        document.getElementById('fu_member_hint').textContent = 'اختر الفرع أولاً لتفعيل البحث';
        document.getElementById('fu_type').value           = 'general';
        document.getElementById('fu_status').value         = 'pending';
        document.getElementById('fu_priority').value       = 'medium';
        document.getElementById('fu_next_action_at').value = fuDtTomorrow10();
        document.getElementById('fu_notes').value          = '';
        document.getElementById('fu_result').value         = '';

        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            jQuery('#fu_member_id').val(null).trigger('change');
        }

        new bootstrap.Modal(document.getElementById('fuModal')).show();
    };

    function fuDtTomorrow10() {
        const d = new Date(); d.setDate(d.getDate() + 1);
        const p = n => String(n).padStart(2,'0');
        return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}T10:00`;
    }
})();
</script>
@endsection
