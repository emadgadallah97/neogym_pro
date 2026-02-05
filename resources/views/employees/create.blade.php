{{-- Add Modal --}}
<div id="addEmployeeModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3 bg-soft-success">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-account-plus-outline"></i> {{ trans('employees.add_new_employee') }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form action="{{route('employees.store')}}" method="post" enctype="multipart/form-data" class="employee-form">
                    {{ csrf_field() }}

                    @include('employees.form', ['mode' => 'create'])

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
