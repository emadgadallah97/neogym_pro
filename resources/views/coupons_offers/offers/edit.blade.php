@extends('layouts.master_table')
@section('title')
{{ trans('coupons_offers.edit_offer') }}
@stop

@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('coupons_offers.edit') }} - {{ $Offer->getTranslation('name', app()->getLocale()) }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('offers.index') }}">{{ trans('coupons_offers.offers') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('coupons_offers.edit') }}</li>
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
                    <h5 class="card-title mb-0">{{ trans('coupons_offers.edit') }}</h5>

                    <div class="ms-3">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible mb-0 py-2 px-3" role="alert">
                                <button type="button" class="close" data-dismiss="alert">
                                    <i class="fa fa-times"></i>
                                </button>
                                <strong>Error !</strong>
                                {{ implode(' | ', $errors->all()) }}
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

                    <div>
                        <a href="{{ route('offers.index') }}" class="btn btn-soft-secondary">
                            <i class="ri-arrow-go-back-line align-bottom me-1"></i> {{ trans('coupons_offers.back') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form action="{{ route('offers.update', $Offer->id) }}" method="post">
                    @csrf
                    {{ method_field('put') }}

                    @include('coupons_offers.offers._form', ['Offer' => $Offer, 'Plans' => $Plans, 'Types' => $Types])

                    <hr class="mt-4">

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-3-line align-bottom me-1"></i> {{ trans('coupons_offers.update') }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection
