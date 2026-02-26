<div id="breakdownModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font" id="breakdownTitle">
                    <i class="ri-file-search-line me-1"></i>
                    {{ trans('hr.payroll_breakdown') ?? 'تفاصيل كشف الراتب' }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div id="breakdownLoading" class="alert alert-info font d-none">
                    {{ trans('hr.loading') ?? 'جاري التحميل...' }}
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-md-3">
                        <div class="card border mb-0">
                            <div class="card-body py-2">
                                <small class="text-muted">{{ trans('hr.overtime') ?? 'إضافي' }}</small>
                                <div class="fw-bold" id="bd_sum_overtime">0.00</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border mb-0">
                            <div class="card-body py-2">
                                <small class="text-muted">{{ trans('hr.allowances') ?? 'بدلات/حوافز' }}</small>
                                <div class="fw-bold" id="bd_sum_allowances">0.00</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border mb-0">
                            <div class="card-body py-2">
                                <small class="text-muted">{{ trans('hr.deductions') ?? 'خصومات/جزاءات' }}</small>
                                <div class="fw-bold" id="bd_sum_deductions">0.00</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border mb-0">
                            <div class="card-body py-2">
                                <small class="text-muted">{{ trans('hr.advances') ?? 'سلف' }}</small>
                                <div class="fw-bold" id="bd_sum_advances">0.00</div>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-2" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabOvertime" type="button" role="tab">
                            {{ trans('hr.breakdown_overtime') ?? 'تفاصيل الإضافي' }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAllowances" type="button" role="tab">
                            {{ trans('hr.breakdown_allowances') ?? 'تفاصيل البدلات' }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDeductions" type="button" role="tab">
                            {{ trans('hr.breakdown_deductions') ?? 'تفاصيل الخصومات' }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAdvances" type="button" role="tab">
                            {{ trans('hr.breakdown_advances') ?? 'تفاصيل السلف' }}
                        </button>
                    </li>

                    {{-- Attendance tab --}}
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAttendance" type="button" role="tab">
                            {{ trans('hr.attendance') ?? 'الحضور' }}
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    <div class="tab-pane fade show active" id="tabOvertime" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ trans('hr.date') ?? 'التاريخ' }}</th>
                                        <th>{{ trans('hr.source') ?? 'المصدر' }}</th>
                                        <th>{{ trans('hr.hours') ?? 'الساعات' }}</th>
                                        <th>{{ trans('hr.hour_rate') ?? 'سعر الساعة' }}</th>
                                        <th>{{ trans('hr.total_amount') ?? 'الإجمالي' }}</th>
                                        <th>{{ trans('hr.notes') ?? 'ملاحظات' }}</th>
                                    </tr>
                                </thead>
                                <tbody id="bd_overtime_rows"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabAllowances" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ trans('hr.date') ?? 'التاريخ' }}</th>
                                        <th>{{ trans('hr.type') ?? 'النوع' }}</th>
                                        <th>{{ trans('hr.reason') ?? 'البيان' }}</th>
                                        <th>{{ trans('hr.amount') ?? 'المبلغ' }}</th>
                                        <th>{{ trans('hr.notes') ?? 'ملاحظات' }}</th>
                                    </tr>
                                </thead>
                                <tbody id="bd_allowance_rows"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabDeductions" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ trans('hr.date') ?? 'التاريخ' }}</th>
                                        <th>{{ trans('hr.type') ?? 'النوع' }}</th>
                                        <th>{{ trans('hr.reason') ?? 'البيان' }}</th>
                                        <th>{{ trans('hr.amount') ?? 'المبلغ' }}</th>
                                        <th>{{ trans('hr.notes') ?? 'ملاحظات' }}</th>
                                    </tr>
                                </thead>
                                <tbody id="bd_deduction_rows"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabAdvances" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ trans('hr.advance_id') ?? 'Advance' }}</th>
                                        <th>{{ trans('hr.amount') ?? 'المبلغ' }}</th>
                                        <th>{{ trans('hr.status') ?? 'الحالة' }}</th>
                                    </tr>
                                </thead>
                                <tbody id="bd_advance_rows"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Attendance --}}
                    <div class="tab-pane fade" id="tabAttendance" role="tabpanel">

                        {{-- Counts/Hours --}}
                        <div class="row g-3 mb-2">
                            <div class="col-md-2">
                                <div class="card border mb-0">
                                    <div class="card-body py-2">
                                        <small class="text-muted">{{ trans('hr.present_days') ?? 'أيام حضور' }}</small>
                                        <div class="fw-bold" id="bd_att_present_days">0</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card border mb-0">
                                    <div class="card-body py-2">
                                        <small class="text-muted">{{ trans('hr.work_days') ?? 'أيام عمل' }}</small>
                                        <div class="fw-bold" id="bd_att_work_days">0</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card border mb-0">
                                    <div class="card-body py-2">
                                        <small class="text-muted">{{ trans('hr.late_days') ?? 'أيام تأخير' }}</small>
                                        <div class="fw-bold" id="bd_att_late_days">0</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card border mb-0">
                                    <div class="card-body py-2">
                                        <small class="text-muted">{{ trans('hr.halfday_days') ?? 'نصف يوم' }}</small>
                                        <div class="fw-bold" id="bd_att_halfday_days">0</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card border mb-0">
                                    <div class="card-body py-2">
                                        <small class="text-muted">{{ trans('hr.absent_days') ?? 'غياب' }}</small>
                                        <div class="fw-bold" id="bd_att_absent_days">0</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card border mb-0">
                                    <div class="card-body py-2">
                                        <small class="text-muted">{{ trans('hr.total_hours') ?? 'إجمالي ساعات' }}</small>
                                        <div class="fw-bold" id="bd_att_total_hours">0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ✅ NEW: Money KPIs --}}
                        <div class="row g-3 mb-2">
                            <div class="col-md-3">
                                <div class="card border mb-0">
                                    <div class="card-body py-2">
                                        <small class="text-muted">{{ trans('hr.day_rate') ?? 'سعر اليوم' }}</small>
                                        <div class="fw-bold" id="bd_att_day_rate">0.00</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card border mb-0">
                                    <div class="card-body py-2">
                                        <small class="text-muted">{{ trans('hr.present_amount') ?? 'قيمة أيام الحضور' }}</small>
                                        <div class="fw-bold" id="bd_att_present_amount">0.00</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card border mb-0">
                                    <div class="card-body py-2">
                                        <small class="text-muted">{{ trans('hr.halfday_amount') ?? 'قيمة أنصاف الأيام' }}</small>
                                        <div class="fw-bold" id="bd_att_halfday_amount">0.00</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card border mb-0">
                                    <div class="card-body py-2">
                                        <small class="text-muted">{{ trans('hr.attendance_total_amount') ?? 'إجمالي قيمة الحضور' }}</small>
                                        <div class="fw-bold" id="bd_att_attendance_amount">0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ trans('hr.date') ?? 'التاريخ' }}</th>
                                        <th>{{ trans('hr.check_in') ?? 'دخول' }}</th>
                                        <th>{{ trans('hr.check_out') ?? 'خروج' }}</th>
                                        <th>{{ trans('hr.total_hours') ?? 'الساعات' }}</th>
                                        <th>{{ trans('hr.status') ?? 'الحالة' }}</th>
                                        <th>{{ trans('hr.notes') ?? 'ملاحظات' }}</th>
                                    </tr>
                                </thead>
                                <tbody id="bd_attendance_rows"></tbody>
                            </table>
                        </div>

                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> {{ trans('hr.close') ?? 'إغلاق' }}
                </button>
            </div>

        </div>
    </div>
</div>
