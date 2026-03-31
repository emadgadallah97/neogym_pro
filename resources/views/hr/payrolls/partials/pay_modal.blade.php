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

            <div class="modal-body bg-light">
                <form id="payForm" autocomplete="off">
                    @csrf
                    <input type="hidden" name="branch_id" id="pay_branch_id" value="{{ $branchId }}">
                    <input type="hidden" name="month" id="pay_month" value="{{ $monthFilter }}">

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="bg-white p-3 border rounded shadow-sm d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1 font">إجمالي الرواتب المعتمدة:</h6>
                                    <h4 class="mb-0 text-dark fw-bold" id="pay_amount_display" dir="ltr">0.00</h4>
                                </div>
                                <div class="text-end" style="min-width: 150px;">
                                    <h6 class="text-muted mb-1 font">رصيد خزينة الفرع:</h6>
                                    <h4 class="mb-0 text-dark fw-bold" id="treasury_balance_display" dir="ltr" data-balance="0">0.00</h4>
                                </div>
                            </div>
                        </div>

                        <div class="col-12" id="treasury_warning_box" style="display: none;">
                            <div class="alert alert-danger font mb-0 border-danger d-flex align-items-center">
                                <i class="ri-error-warning-line fs-18 py-0 pe-2 me-2 border-end border-danger"></i>
                                <div>
                                    <h6 class="alert-heading fw-bold mb-1">تحذير: رصيد الخزينة غير كافٍ!</h6>
                                    <p class="mb-0">سحب هذا المبلغ سيؤدي إلى جعل رصيد الخزينة بالسالب. يرجى التأكد أو إيداع مبلغ أولاً.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <div class="form-check form-switch form-switch-lg mb-0 font">
                                    <input class="form-check-input fs-20" type="checkbox" role="switch" name="record_in_treasury" id="record_in_treasury" value="1" checked style="margin-left: 0; float: right;">
                                    <label class="form-check-label fw-bold w-100" for="record_in_treasury" style="margin-right: 50px; display: block;">
                                        تسجيل المبلغ وسحبه من الخزينة تلقائياً
                                        <small class="d-block text-muted fw-normal mt-1">إذا تم إيقاف هذا الخيار، سيتم تسجيل المصروف فقط دون التأثير على رصيد الخزينة.</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font text-dark fw-bold">{{ trans('hr.payment_date') ?? 'تاريخ الصرف' }} <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" id="payment_date" class="form-control font" value="{{ date('Y-m-d') }}">
                            <div class="invalid-feedback" id="payment_date_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font text-dark fw-bold text-danger">نوع المصروف <span class="text-danger">*</span></label>
                            <select name="expense_type_id" id="expense_type_id" class="form-select font">
                                <option value="" selected disabled>-- اختر بناءً على الدليل المحاسبي --</option>
                                @foreach($ExpensesTypes ?? [] as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="expense_type_id_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font text-dark fw-bold">المنصرف بواسطة (اختياري)</label>
                            <select name="expense_disbursed_by" id="expense_disbursed_by" class="form-select font select2-no-search">
                                <option value="">-- تفويض المنصرف --</option>
                                @foreach($employees ?? [] as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name ?? $emp->full_name ?? $emp->first_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="expense_disbursed_by_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font text-dark fw-bold">{{ trans('hr.payment_reference') ?? 'مرجع الدفع' }}</label>
                            <input type="text" name="payment_reference" id="payment_reference" class="form-control font" placeholder="{{ trans('hr.optional') ?? 'اختياري' }}">
                        </div>
                    </div>

                    <div class="text-end mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-success font px-4 fw-bold" id="paySubmitBtn">
                            <i class="ri-check-double-line me-1"></i> اعتماد والصرف
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
