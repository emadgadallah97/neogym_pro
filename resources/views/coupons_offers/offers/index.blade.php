@extends('layouts.master_table')
@section('title')
{{ trans('coupons_offers.offers') }}
@stop

@section('content')

@php
    $totalOffers = $Offers->count();
    $activeOffers = $Offers->where('status', 'active')->count();
    $disabledOffers = $Offers->where('status', 'disabled')->count();
    $expiredOffers = $Offers->filter(function($o){ return isset($o->is_expired) && $o->is_expired; })->count();
    $lastCreatedAt = $Offers->max('created_at');
@endphp

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('coupons_offers.offers') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('coupons_offers.offers') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('coupons_offers.offers') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body pb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">{{ trans('subscriptions.plans_kpis') }}</h5>
                        <p class="text-muted mb-0">{{ trans('subscriptions.quick_filters') }}</p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('offers.create') }}" class="btn btn-primary">
                            <i class="ri-add-line align-bottom me-1"></i> {{ trans('coupons_offers.add_new_offer') }}
                        </a>
                    </div>
                </div>

                <div class="row mt-3 g-3">
                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-primary text-primary">
                                            <i class="ri-price-tag-3-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('coupons_offers.offers') }}</p>
                                        <h4 class="mb-0">{{ $totalOffers }}</h4>
                                        @if($lastCreatedAt)
                                            <small class="text-muted d-block">{{ trans('subscriptions.last_created') }}: {{ $lastCreatedAt }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-success text-success">
                                            <i class="ri-check-double-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('coupons_offers.active') }}</p>
                                        <h4 class="mb-0">{{ $activeOffers }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-danger text-danger">
                                            <i class="ri-close-circle-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('coupons_offers.disabled') }}</p>
                                        <h4 class="mb-0">{{ $disabledOffers }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-warning text-warning">
                                            <i class="ri-timer-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('coupons_offers.expired') }}</p>
                                        <h4 class="mb-0">{{ $expiredOffers }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="mt-4 mb-0">
            </div>

            <div class="card-body pt-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label mb-1">{{ trans('coupons_offers.search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                            <input type="text" id="offerSearch" class="form-control" placeholder="{{ trans('coupons_offers.search_here') }}">
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label mb-1">{{ trans('coupons_offers.status') }}</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">{{ trans('coupons_offers.all') }}</option>
                            <option value="active">{{ trans('coupons_offers.active') }}</option>
                            <option value="disabled">{{ trans('coupons_offers.disabled') }}</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('coupons_offers.applies_to') }}</label>
                        <select id="filterAppliesTo" class="form-select">
                            <option value="">{{ trans('coupons_offers.all') }}</option>
                            <option value="any">{{ trans('coupons_offers.any') }}</option>
                            <option value="subscription">{{ trans('coupons_offers.subscription') }}</option>
                            <option value="sale">{{ trans('coupons_offers.sale') }}</option>
                            <option value="service">{{ trans('coupons_offers.service') }}</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3 text-end">
                        <button type="button" id="clearFilters" class="btn btn-soft-secondary">
                            <i class="ri-refresh-line align-bottom me-1"></i> {{ trans('coupons_offers.clear_filters') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">

            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">{{ trans('coupons_offers.offers') }}</h5>

                    <div class="ms-3">
                        @if (Session::has('success'))
                            <div class="alert alert-success alert-dismissible mb-0 py-2 px-3" role="alert">
                                <button type="button" class="close" data-dismiss="alert">
                                    <i class="fa fa-times"></i>
                                </button>
                                <strong>Success !</strong> {{ session('success') }}
                            </div>
                        @endif

                        @if (Session::has('error'))
                            <div class="alert alert-danger alert-dismissible mb-0 py-2 px-3" role="alert">
                                <button type="button" class="close" data-dismiss="alert">
                                    <i class="fa fa-times"></i>
                                </button>
                                <strong>Error !</strong> {{ session('error') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body">
                <table id="example" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th data-ordering="false">#</th>
                            <th data-ordering="false">{{ trans('coupons_offers.name_ar') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.name_en') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.applies_to') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.discount_type') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.discount_value') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.status') }}</th>
                            <th>{{ trans('subscriptions.create_date') }}</th>
                            <th>{{ trans('coupons_offers.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $i=0; ?>
                        @foreach($Offers as $Offer)
                            <?php $i++; ?>
                            <tr>
                                <td>{{ $i }}</td>

                                <td class="text-truncate" style="max-width:220px;">
                                    {{ $Offer->getTranslation('name', 'ar') }}
                                </td>

                                <td class="text-truncate" style="max-width:220px;">
                                    {{ $Offer->getTranslation('name', 'en') }}
                                </td>

                                <td>
                                    <span class="d-none">__APPLIES__{{ $Offer->applies_to }}__</span>
                                    <span class="badge bg-soft-info text-info">{{ trans('coupons_offers.' . $Offer->applies_to) }}</span>
                                </td>

                                <td>
                                    <span class="badge bg-soft-primary text-primary">{{ trans('coupons_offers.' . $Offer->discount_type) }}</span>
                                </td>

                                <td>
                                    <span class="badge bg-soft-success text-success">{{ $Offer->discount_value }}</span>
                                </td>

                                <td>
                                    <span class="d-none">__STATUS__{{ $Offer->status }}__</span>

                                    @if($Offer->is_expired)
                                        <span class="badge bg-warning">{{ trans('coupons_offers.expired') }}</span>
                                    @else
                                        @if($Offer->status === 'active')
                                            <span class="badge bg-success">{{ trans('coupons_offers.active') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ trans('coupons_offers.disabled') }}</span>
                                        @endif
                                    @endif
                                </td>

                                <td>{{ $Offer->created_at }}</td>

                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('offers.show', $Offer->id) }}" class="btn btn-soft-primary btn-sm" title="{{ trans('coupons_offers.view') }}">
                                            <i class="ri-eye-fill align-bottom"></i>
                                        </a>
                                        <a href="{{ route('offers.edit', $Offer->id) }}" class="btn btn-soft-secondary btn-sm" title="{{ trans('coupons_offers.edit') }}">
                                            <i class="ri-pencil-fill align-bottom"></i>
                                        </a>
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal{{$Offer->id}}" class="btn btn-soft-danger btn-sm" title="{{ trans('coupons_offers.delete') }}">
                                            <i class="ri-delete-bin-fill align-bottom"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- delete -->
                            <div id="deleteModal{{$Offer->id}}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-body text-center p-5">
                                            <lord-icon src="{{URL::asset('assets/images/icon/tdrtiskw.json')}}" trigger="loop"
                                                colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px">
                                            </lord-icon>

                                            <div class="mt-4 pt-4">
                                                <h4>{{ trans('subscriptions.massagedelete_d') }}!</h4>
                                                <p class="text-muted">{{ trans('subscriptions.massagedelete_p') }} {{ $Offer->getTranslation('name','ar') }}</p>

                                                <form action="{{ route('offers.destroy', $Offer->id) }}" method="post">
                                                    {{ method_field('delete') }}
                                                    {{ csrf_field() }}

                                                    <input class="form-control" id="id" name="id" value="{{ $Offer->id }}" type="hidden">

                                                    <button class="btn btn-warning" data-bs-target="#secondmodal" data-bs-toggle="modal" data-bs-dismiss="modal">
                                                        {{ trans('subscriptions.massagedelete') }}
                                                    </button>
                                                </form>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $ === 'undefined') return;
        if (!$.fn || !$.fn.DataTable) return;

        var table = $('#example').DataTable();

        // columns:
        // 0 #, 1 ar, 2 en, 3 applies_to, 4 type, 5 value, 6 status, 7 created_at, 8 actions
        var appliesColIndex = 3;
        var statusColIndex = 6;

        $('#offerSearch').on('keyup change', function () {
            table.search(this.value).draw();
        });

        $('#filterStatus').on('change', function () {
            var v = $(this).val();
            if (v === '') {
                table.column(statusColIndex).search('').draw();
                return;
            }
            table.column(statusColIndex).search('__STATUS__' + v + '__', false, false).draw();
        });

        $('#filterAppliesTo').on('change', function () {
            var v = $(this).val();
            if (v === '') {
                table.column(appliesColIndex).search('').draw();
                return;
            }
            table.column(appliesColIndex).search('__APPLIES__' + v + '__', false, false).draw();
        });

        $('#clearFilters').on('click', function () {
            $('#offerSearch').val('');
            $('#filterStatus').val('');
            $('#filterAppliesTo').val('');

            table.search('');
            table.column(appliesColIndex).search('');
            table.column(statusColIndex).search('');
            table.draw();
        });
    });
</script>

@endsection
