@extends('layouts.master_table')
@section('title')
{{ trans('accounting.income_types') }}
@stop

@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('accounting.income_types') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('accounting.accounting') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('accounting.income_types') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">

            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">{{ trans('accounting.income_types_list') }}</h5>

                    <div class="d-flex gap-2">
                        <button data-bs-toggle="modal" data-bs-target="#addIncomeTypeModal" class="btn btn-primary">
                            <i class="ri-add-line align-bottom me-1"></i> {{ trans('accounting.add_new_income_type') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">

                @if (Session::has('success'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button>
                        <strong>Success !</strong> {{ session('success') }}
                    </div>
                @endif

                @if (Session::has('error'))
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button>
                        <strong>Error !</strong> {{ session('error') }}
                    </div>
                @endif

                <table id="example" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ trans('accounting.id') }}</th>
                            <th>{{ trans('accounting.name_ar') }}</th>
                            <th>{{ trans('accounting.name_en') }}</th>
                            <th>{{ trans('accounting.status') }}</th>
                            <th>{{ trans('accounting.notes') }}</th>
                            <th>{{ trans('accounting.create_date') }}</th>
                            <th>{{ trans('accounting.action') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php $i=0; @endphp
                        @foreach($IncomeTypes as $t)
                            @php
                                $i++;
                                $nameAr = $t->getTranslation('name', 'ar');
                                $nameEn = $t->getTranslation('name', 'en');
                            @endphp
                            <tr>
                                <td>{{ $i }}</td>
                                <td>{{ $t->id }}</td>
                                <td>{{ $nameAr }}</td>
                                <td>{{ $nameEn }}</td>
                                <td>
                                    @if($t->status)
                                        <span class="badge bg-success">{{ trans('accounting.active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ trans('accounting.inactive') }}</span>
                                    @endif
                                </td>
                                <td>{{ $t->notes ?? '-' }}</td>
                                <td>{{ $t->created_at }}</td>
                                <td>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button data-bs-toggle="modal" data-bs-target="#editIncomeTypeModal{{ $t->id }}" class="dropdown-item">
                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                                </button>
                                            </li>
                                            <li>
                                                <button data-bs-toggle="modal" data-bs-target="#deleteIncomeTypeModal{{ $t->id }}" class="dropdown-item">
                                                    <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="addIncomeTypeModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0">{{ trans('accounting.add_new_income_type') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form action="{{ route('income_types.store') }}" method="post">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.name_ar') }}</label>
                            <input type="text" name="name_ar" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.name_en') }}</label>
                            <input type="text" name="name_en" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.status') }}</label>
                            <select name="status" class="form-select" required>
                                <option value="1">{{ trans('accounting.active') }}</option>
                                <option value="0">{{ trans('accounting.inactive') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ trans('accounting.notes') }}</label>
                            <input type="text" name="notes" class="form-control">
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">{{ trans('accounting.submit') }}</button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

{{-- Edit/Delete modals OUTSIDE table --}}
@foreach($IncomeTypes as $t)
    @php
        $nameAr = $t->getTranslation('name', 'ar');
        $nameEn = $t->getTranslation('name', 'en');
    @endphp

    <!-- Edit Modal -->
    <div id="editIncomeTypeModal{{ $t->id }}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 overflow-hidden">
                <div class="modal-header p-3">
                    <h4 class="card-title mb-0">{{ trans('accounting.update_income_type') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form action="{{ route('income_types.update', $t->id) }}" method="post">
                        @csrf
                        {{ method_field('patch') }}

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.name_ar') }}</label>
                                <input type="text" name="name_ar" class="form-control" value="{{ $nameAr }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.name_en') }}</label>
                                <input type="text" name="name_en" class="form-control" value="{{ $nameEn }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.status') }}</label>
                                <select name="status" class="form-select" required>
                                    <option value="1" {{ $t->status ? 'selected' : '' }}>{{ trans('accounting.active') }}</option>
                                    <option value="0" {{ !$t->status ? 'selected' : '' }}>{{ trans('accounting.inactive') }}</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ trans('accounting.notes') }}</label>
                                <input type="text" name="notes" class="form-control" value="{{ $t->notes }}">
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">{{ trans('accounting.submit') }}</button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteIncomeTypeModal{{ $t->id }}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mt-2">
                        <h4>{{ trans('accounting.delete_confirm_title') }}</h4>
                        <p class="text-muted">{{ trans('accounting.delete_confirm_text') }}</p>

                        <form action="{{ route('income_types.destroy', $t->id) }}" method="post">
                            @csrf
                            {{ method_field('delete') }}

                            <button type="submit" class="btn btn-danger">{{ trans('accounting.delete') }}</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
        $('#example').DataTable();
    }
});
</script>

@endsection
