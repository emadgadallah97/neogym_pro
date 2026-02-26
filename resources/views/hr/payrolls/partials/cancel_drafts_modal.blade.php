{{-- Cancel Drafts Modal --}}
<div id="cancelDraftsModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font">
                    <i class="ri-close-circle-line me-1 text-danger"></i>
                    {{ trans('hr.cancel_drafts') ?? 'إلغاء (مسودات)' }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-warning font">
                    {{ trans('hr.cancel_drafts_note') ?? 'سيتم حذف الرواتب المسودة فقط لنفس الشهر/الفرع، ولن يتم لمس المعتمد/المصروف.' }}
                </div>

                <form id="cancelDraftsForm" autocomplete="off">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label font">{{ trans('hr.branch') }}</label>
                            <select name="branch_id" id="cancel_branch_id" class="form-select font" required>
                                <option value="">{{ trans('hr.select_branch') }}</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="cancel_branch_id_error"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label font">{{ trans('hr.month') }}</label>
                            <input type="month" name="month" id="cancel_month" class="form-control font" value="{{ $monthFilter }}" required>
                            <div class="invalid-feedback" id="cancel_month_error"></div>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="delete_auto_overtime" id="delete_auto_overtime" value="1">
                                <label class="form-check-label font" for="delete_auto_overtime">
                                    {{ trans('hr.delete_auto_overtime') ?? 'حذف الإضافي المتولد تلقائيًا' }}
                                </label>
                            </div>
                            <small class="text-muted">
                                {{ trans('hr.delete_auto_overtime_hint') ?? 'يُحذف فقط السجلات التي تم توليدها بعلامة AUTO_OT_FROM_PAYROLL.' }}
                            </small>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger font" id="cancelSubmitBtn">
                            <i class="ri-delete-bin-5-line me-1"></i> {{ trans('hr.confirm') ?? 'تأكيد' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
