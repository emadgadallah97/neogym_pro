@extends('layouts.master_table')
@section('title')
{{ trans('main_trans.title') }}
@stop




@section('content')



<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('subscriptions.add_new_plan') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('subscriptions_plans.index') }}">{{ trans('subscriptions.subscriptions_plans') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('subscriptions.add_new_plan') }}</li>
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
                    <h5 class="card-title mb-0">{{ trans('subscriptions.add_new_plan') }}</h5>

                    <div class="d-flex gap-2">
                        <a href="{{ route('subscriptions_plans.index') }}" class="btn btn-soft-secondary btn-sm">
                            <i class="ri-arrow-go-back-line align-bottom me-1"></i> {{ trans('subscriptions.back') }}
                        </a>
                    </div>
                </div>

                {{-- Message --}}
                @if (Session::has('error'))
                    <div class="alert alert-danger alert-dismissible mt-3 mb-0" role="alert">
                        <button type="button" class="close" data-dismiss="alert">
                            <i class="fa fa-times"></i>
                        </button>
                        <strong>Error !</strong> {{ session('error') }}
                    </div>
                @endif

                {{-- Validation errors summary --}}
                @if ($errors->any())
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong>{{ trans('subscriptions.validation_error') }}</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>



            <div class="card-body">
                <form action="{{ route('subscriptions_plans.store') }}" method="post" id="planForm">
                    {{ csrf_field() }}

                    @include('subscriptions.programs.subscriptions_plans.partials.form')

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('subscriptions_plans.index') }}" class="btn btn-light">
                            {{ trans('subscriptions.cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-3-line align-bottom me-1"></i> {{ trans('subscriptions.submit') }}
                        </button>
                    </div>

                </form>
            </div>



        </div>
    </div>
</div>



@endsection
