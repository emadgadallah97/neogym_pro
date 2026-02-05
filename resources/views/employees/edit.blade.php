{{-- Edit Modal (Reusable) --}}
<div id="editEmployeeModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3 bg-soft-warning">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-account-edit-outline"></i> {{ trans('employees.update_employee') }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form action="{{ route('employees.update','test') }}" method="post" enctype="multipart/form-data" class="employee-form" id="editForm">
                    {{ method_field('patch') }}
                    @csrf
                    <input class="form-control" name="id" id="edit_id" value="" type="hidden">

                    @include('employees.form', ['mode' => 'edit'])

                    <div class="text-end pt-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save-outline"></i> {{ trans('settings_trans.submit') }}
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>
