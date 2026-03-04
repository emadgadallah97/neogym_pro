{{-- resources/views/crm/followups/_confirm_modal.blade.php --}}
<div class="modal fade" id="fuConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" dir="rtl">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="fuConfirmTitle">تأكيد</h6>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="fuConfirmBody">هل أنت متأكد؟</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary btn-sm" id="fuConfirmOk">تأكيد</button>
            </div>
        </div>
    </div>
</div>
