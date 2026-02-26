{{-- Pay Modal --}}
<div id="payModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font">
                    <i class="ri-wallet-3-line me-1 text-success"></i>
                    {{ trans('hr.pay_payrolls') ?? 'صرف الرواتب' }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="payForm" autocomplete="off">
                    @csrf
                    <input type="hidden" name="branch_id" id="pay_branch_id" value="{{ $branchId }}">
                    <input type="hidden" name="month" id="pay_month" value="{{ $monthFilter }}">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label font">{{ trans('hr.payment_date') ?? 'تاريخ الصرف' }}</label>
                            <input type="date" name="payment_date" id="payment_date" class="form-control font" value="{{ date('Y-m-d') }}">
                            <div class="invalid-feedback" id="payment_date_error"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label font">{{ trans('hr.payment_reference') ?? 'مرجع الدفع' }}</label>
                            <input type="text" name="payment_reference" id="payment_reference" class="form-control font" placeholder="{{ trans('hr.optional') ?? 'اختياري' }}">
                        </div>

                        <div class="col-12">
                            <div class="alert alert-warning font mb-0">
                                {{ trans('hr.pay_bulk_note') ?? 'سيتم صرف جميع الرواتب المعتمدة للشهر/الفرع المحدد.' }}
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-success font" id="paySubmitBtn">
                            <i class="ri-check-double-line me-1"></i> {{ trans('hr.pay') ?? 'صرف' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
