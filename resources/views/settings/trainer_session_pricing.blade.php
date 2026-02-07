@extends('layouts.master_table')


@section('title')
    {{ trans('settings_trans.trainer_session_pricing') }}
@endsection



@section('content')
    <div class="row">
        <div class="col-12">


            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">{{ trans('settings_trans.trainer_session_pricing') }}</h5>


                        <a href="{{ url()->previous() }}" class="btn btn-light btn-sm">
                            <i class="mdi mdi-arrow-left"></i> {{ trans('settings_trans.back') ?? 'رجوع' }}
                        </a>
                    </div>
                </div>


                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success mb-3">{{ session('success') }}</div>
                    @endif

                    {{-- Search by trainer name --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label mb-1">{{ trans('settings_trans.search_by_trainer_name') ?? 'بحث بالاسم' }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                <input type="text"
                                       class="form-control"
                                       id="trainer_name_search"
                                       placeholder="{{ trans('settings_trans.search') ?? 'بحث...' }}">
                                <button type="button" class="btn btn-light" id="trainer_name_search_clear">
                                    {{ trans('settings_trans.clear') ?? 'مسح' }}
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">
                                {{ trans('settings_trans.search_by_trainer_name_hint') ?? 'ابحث بالاسم الأول أو الأخير (وسيعمل أيضًا على الإيميل).' }}
                            </small>
                        </div>
                    </div>


                    <div class="table-responsive">
                        <table id="trainerPricingTable" class="table table-bordered table-striped align-middle mb-0" style="width:100%;">
                            <thead>
                                <tr>
                                    <th style="width:80px;">#</th>
                                    <th>{{ trans('settings_trans.trainer') }}</th>
                                    <th style="width:160px;">{{ trans('settings_trans.phone') ?? 'رقم الهاتف' }}</th>
                                    <th>{{ trans('settings_trans.email') ?? 'Email' }}</th>
                                    <th style="width:200px;">{{ trans('settings_trans.session_price') }}</th>
                                    <th style="width:180px;">{{ trans('settings_trans.updated_by') }}</th>
                                    <th style="width:170px;">{{ trans('settings_trans.updated_at') }}</th>
                                    <th style="width:110px;">{{ trans('settings_trans.action') ?? 'Action' }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>


                    <small class="text-muted d-block mt-2">
                        {{ trans('settings_trans.trainer_session_pricing_hint') }}
                    </small>
                </div>
            </div>


        </div>
    </div>




    <script>
        (function () {
            // CSRF for Ajax POST
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            });


            var table = $('#trainerPricingTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('trainer_session_pricing.index') }}",
                    type: "GET"
                },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                columns: [
                    { data: 'trainer_id', name: 'trainer_id' },
                    { data: 'name', name: 'name' },
                    { data: 'phone', name: 'phone' },
                    { data: 'email', name: 'email' },
                    {
                        data: 'session_price',
                        name: 'session_price',
                        orderable: false,
                        render: function (data, type, row) {
                            var val = (data === null || data === undefined) ? '' : data;
                            return `
                                <input type="number" step="0.01" min="0"
                                       class="form-control form-control-sm session-price-input"
                                       data-trainer-id="${row.trainer_id}"
                                       value="${val}">
                            `;
                        }
                    },
                    {
                        data: 'updated_by_name',
                        name: 'updated_by_name',
                        orderable: false,
                        render: function (data) {
                            return (data === null || data === undefined || data === '') ? '-' : data;
                        }
                    },
                    { data: 'updated_at', name: 'updated_at', orderable: false },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return `
                                <button type="button"
                                        class="btn btn-primary btn-sm btn-save-price"
                                        data-trainer-id="${row.trainer_id}">
                                    <i class="mdi mdi-content-save-outline"></i> {{ trans('settings_trans.save') ?? 'حفظ' }}
                                </button>
                            `;
                        }
                    }
                ]
            });


            // Search by name (global search - includes phone too because server filters by it)
            var searchTimer = null;

            $('#trainer_name_search').on('keyup change', function () {
                var val = this.value;

                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    table.search(val).draw();
                }, 300);
            });

            $('#trainer_name_search_clear').on('click', function () {
                $('#trainer_name_search').val('');
                table.search('').draw();
            });


            // Save handler
            $(document).on('click', '.btn-save-price', function () {
                var trainerId = $(this).data('trainer-id');
                var input = $('.session-price-input[data-trainer-id="' + trainerId + '"]');
                var price = input.val();


                $.ajax({
                    url: "{{ route('trainer_session_pricing.store') }}",
                    type: "POST",
                    data: {
                        trainer_id: trainerId,
                        session_price: price
                    },
                    success: function (res) {
                        if (res && res.status) {
                            table.ajax.reload(null, false);
                        } else {
                            alert("{{ trans('settings_trans.something_went_wrong') ?? 'حدث خطأ' }}");
                        }
                    },
                    error: function (xhr) {
                        if (xhr && xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                alert(xhr.responseJSON.message);
                                return;
                            }
                            if (xhr.responseJSON.errors) {
                                var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                                if (firstKey) {
                                    alert(xhr.responseJSON.errors[firstKey][0]);
                                    return;
                                }
                            }
                        }
                        alert("{{ trans('settings_trans.validation_error') ?? 'خطأ في البيانات' }}");
                    }
                });
            });
        })();
    </script>
@endsection
