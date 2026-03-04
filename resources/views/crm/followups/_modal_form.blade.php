{{-- resources/views/crm/followups/_modal_form.blade.php --}}
@php
    $typeLabels     = \App\Models\crm\CrmFollowup::typeLabels();
    $priorityLabels = \App\Models\crm\CrmFollowup::priorityLabels();
    $statusLabels   = \App\Models\crm\CrmFollowup::statusLabels();
@endphp

<div class="modal fade" id="fuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST"
              id="fuForm"
              data-store-url="{{ route('crm.followups.store') }}"
              data-update-url="{{ route('crm.followups.update', '__ID__') }}"
              action="{{ route('crm.followups.store') }}">
            @csrf
            <span id="fuMethod"></span>

            {{-- ✅ hidden احتياطي لـ branch_id يُرسل دائماً حتى لو الـ select disabled --}}
            <input type="hidden" id="fu_branch_id_hidden" name="">

            <div class="modal-content" dir="rtl">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold" id="fuTitle">إضافة متابعة جديدة</h6>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        {{-- الفرع --}}
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">
                                الفرع <span class="text-danger">*</span>
                            </label>
                            <select name="branch_id"
                                    id="fu_branch_id"
                                    class="form-select form-select-sm"
                                    required>
                                <option value="">— اختر الفرع أولاً —</option>
                                @foreach($branches as $br)
                                    <option value="{{ $br->id }}">
                                        {{ is_array($br->name)
                                            ? ($br->name[app()->getLocale()] ?? $br->name['ar'] ?? $br->name['en'] ?? '')
                                            : $br->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- العضو: وضع عادي (Select2) --}}
                        <div class="col-md-7" id="fu_member_select_wrap">
                            <label class="form-label fw-semibold small">
                                العضو <span class="text-danger">*</span>
                            </label>
                            <select name="member_id"
                                    id="fu_member_id"
                                    class="form-select form-select-sm"
                                    required
                                    disabled
                                    style="width:100%">
                            </select>
                            <small id="fu_member_hint" class="text-muted">اختر الفرع أولاً لتفعيل البحث</small>
                        </div>

                        {{-- العضو: وضع مقفل (من صفحة show) --}}
                        <div class="col-md-7 d-none" id="fu_member_locked_wrap">
                            <label class="form-label fw-semibold small">العضو</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-user-tag text-success"></i>
                                </span>
                                <div class="form-control bg-light d-flex align-items-center gap-2">
                                    <span id="fu_member_locked_name" class="fw-semibold"></span>
                                    <span class="badge bg-success-subtle text-success border">محتمل</span>
                                </div>
                                <input type="hidden" id="fu_member_locked_id" name="member_id">
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-lock me-1"></i>
                                العضو محدد تلقائياً ولا يمكن تغييره
                            </small>
                        </div>

                        {{-- نوع المتابعة --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">نوع المتابعة</label>
                            <select name="type" id="fu_type" class="form-select form-select-sm" required>
                                @foreach($typeLabels as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- الحالة --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">الحالة</label>
                            <select name="status" id="fu_status" class="form-select form-select-sm" required>
                                @foreach($statusLabels as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- الأولوية --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">الأولوية</label>
                            <select name="priority" id="fu_priority" class="form-select form-select-sm" required>
                                @foreach($priorityLabels as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- موعد المتابعة --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">موعد المتابعة</label>
                            <input type="datetime-local"
                                   name="next_action_at"
                                   id="fu_next_action_at"
                                   class="form-control form-control-sm">
                        </div>

                        {{-- النتيجة --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">النتيجة</label>
                            <input type="text"
                                   name="result"
                                   id="fu_result"
                                   class="form-control form-control-sm"
                                   placeholder="مثال: تم الاتفاق على تجديد">
                        </div>

                        {{-- ملاحظات --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small">ملاحظات</label>
                            <textarea name="notes"
                                      id="fu_notes"
                                      class="form-control form-control-sm"
                                      rows="3"
                                      placeholder="تفاصيل المتابعة..."></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-save me-1"></i> حفظ
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const SEARCH_URL = '{{ route('crm.members.search-ajax') }}';

    document.getElementById('fuModal').addEventListener('shown.bs.modal', function(){
        initMemberSelect2();
    });

    function initMemberSelect2(){
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
        const $el = jQuery('#fu_member_id');
        if ($el.hasClass('select2-hidden-accessible')) return;

        $el.select2({
            dropdownParent: jQuery('#fuModal'),
            dir: 'rtl',
            minimumInputLength: 2,
            placeholder: 'ابحث بالاسم أو الكود أو الهاتف...',
            allowClear: true,
            ajax: {
                url: SEARCH_URL,
                dataType: 'json',
                delay: 300,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: function(params){
                    return {
                        q:              params.term || '',
                        branch_id:      document.getElementById('fu_branch_id').value || '',
                        with_prospects: '1'
                    };
                },
                processResults: data => ({ results: data }),
                cache: false
            },
            templateResult: function (item) {
                if (item.loading) return item.text;
                return jQuery(
                    '<div class="d-flex justify-content-between gap-2">' +
                        '<span class="fw-semibold">' + item.text + '</span>' +
                        '<small class="text-muted">' + (item.phone || '') + '</small>' +
                    '</div>'
                );
            },
            templateSelection: item => item.text || item.id
        });
    }

    // ── branch_id change ──────────────────────────────────
    document.getElementById('fu_branch_id').addEventListener('change', function(){
        // تجاهل إذا كان العضو مقفلاً
        if (!document.getElementById('fu_member_locked_wrap').classList.contains('d-none')) return;

        const hasBranch = this.value !== '';
        const memberEl  = document.getElementById('fu_member_id');
        const hintEl    = document.getElementById('fu_member_hint');

        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            jQuery('#fu_member_id').val(null).trigger('change');
        }

        memberEl.disabled = !hasBranch;
        hintEl.textContent = hasBranch
            ? 'ابحث بالاسم أو الكود أو الهاتف'
            : 'اختر الفرع أولاً لتفعيل البحث';
    });

    // ── fuSetLockedMember ────────────────────────────────
    window.fuSetLockedMember = function(memberId, memberName, branchId) {
        const selectWrap  = document.getElementById('fu_member_select_wrap');
        const lockedWrap  = document.getElementById('fu_member_locked_wrap');
        const branchSel   = document.getElementById('fu_branch_id');
        const branchHidden = document.getElementById('fu_branch_id_hidden');

        if (memberId) {
            // ── وضع مقفل ────────────────────────────────
            selectWrap.classList.add('d-none');
            lockedWrap.classList.remove('d-none');

            document.getElementById('fu_member_locked_name').textContent = memberName || '';
            document.getElementById('fu_member_locked_id').value = memberId;

            // إزالة name من select2 لمنع إرسال قيمة فارغة
            document.getElementById('fu_member_id').removeAttribute('name');

            if (branchId) {
                branchSel.value    = branchId;
                branchSel.disabled = true;

                // ✅ hidden يحمل branch_id ويُرسل حتى لو الـ select disabled
                branchHidden.value = branchId;
                branchHidden.setAttribute('name', 'branch_id');

                // ✅ إزالة name من الـ select لمنع إرسال قيمة فارغة
                branchSel.removeAttribute('name');
            }

        } else {
            // ── وضع عادي ────────────────────────────────
            selectWrap.classList.remove('d-none');
            lockedWrap.classList.add('d-none');

            document.getElementById('fu_member_id').setAttribute('name', 'member_id');
            document.getElementById('fu_member_locked_id').value = '';

            // ✅ إعادة name للـ select وتصفير الـ hidden
            branchSel.setAttribute('name', 'branch_id');
            branchSel.disabled = false;
            branchHidden.removeAttribute('name');
            branchHidden.value = '';
        }
    };

    // ── إعادة الوضع الطبيعي عند إغلاق الموديل ───────────
    document.getElementById('fuModal').addEventListener('hidden.bs.modal', function() {
        window.fuSetLockedMember(null);

        const branchSel = document.getElementById('fu_branch_id');
        branchSel.value = '';

        document.getElementById('fu_member_id').setAttribute('name', 'member_id');
        document.getElementById('fu_member_id').disabled = true;
        document.getElementById('fu_member_hint').textContent = 'اختر الفرع أولاً لتفعيل البحث';

        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            jQuery('#fu_member_id').val(null).trigger('change');
        }
    });
})();
</script>
