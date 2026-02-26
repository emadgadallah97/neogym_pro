{{-- Generate Modal --}}
<div id="generateModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0 font" id="genModalTitle">
                    <i class="ri-add-line me-1 text-success"></i>
                    {{ trans('hr.generate_payrolls') ?? 'توليد الرواتب' }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info font">
                    {{ trans('hr.generate_note_auto_ot') ?? 'سيتم توليد الوقت الإضافي تلقائيًا من الحضور (عند الحاجة) بدون تكرار.' }}
                </div>

                <form id="generateForm" autocomplete="off">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label font">{{ trans('hr.branch') }}</label>
                            <select name="branch_id" id="gen_branch_id" class="form-select font" required>
                                <option value="">{{ trans('hr.select_branch') }}</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="gen_branch_id_error"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label font">{{ trans('hr.month') }}</label>
                            <input type="month" name="month" id="gen_month" class="form-control font" value="{{ $monthFilter }}" required>
                            <div class="invalid-feedback" id="gen_month_error"></div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light font" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i> {{ trans('hr.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-success font" id="genSubmitBtn">
                            <i class="ri-save-line me-1"></i> {{ trans('hr.generate') ?? 'توليد' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
