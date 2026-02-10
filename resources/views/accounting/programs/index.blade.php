@extends('layouts.master')
@section('title')
{{ trans('main_trans.title') }}
@stop


@section('content')

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font">{{ trans('accounting.programs') }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a
                                href="javascript: void(0);">{{ trans('accounting.programs') }}</a></li>
                        <li class="breadcrumb-item active">{{ trans('accounting.programs_accounting') }}</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <div class="col-12">
            <div class="row">


                {{-- المصروفات --}}
                <div class="col-xxl-4 col-lg-6">
                    <div class="card card-body text-center">
                        <div class="avatar-md mx-auto mb-3">
                            <div class="avatar-title bg-soft-light border border-info p-2 text-success rounded">
                              <lord-icon src="{{URL::asset('assets/images/icon/oaflahpk.json')}}" trigger="loop"
                                    delay="500" colors="primary:#4bb3fd" style="width:250px;height:250px">
                                </lord-icon>
                            </div>
                        </div>
                        <h4 class="card-title font">
                            {{ trans('accounting.expenses') }}
                        </h4>
                        <a href="{{ route('expenses.index') }}" class="btn btn-info">
                            {{ trans('accounting.expenses') }}
                        </a>
                    </div>
                </div>
                {{-- الايرادات --}}
                <div class="col-xxl-4 col-lg-6">
                    <div class="card card-body text-center">
                        <div class="avatar-md mx-auto mb-3">
                            <div class="avatar-title bg-soft-light border border-info p-2 text-success rounded">
                              <lord-icon src="{{URL::asset('assets/images/icon/oaflahpk.json')}}" trigger="loop"
                                    delay="500" colors="primary:#4bb3fd" style="width:250px;height:250px">
                                </lord-icon>
                            </div>
                        </div>
                        <h4 class="card-title font">
                            {{ trans('accounting.income') }}
                        </h4>
                        <a href="{{ route('expenses.index') }}" class="btn btn-info">
                            {{ trans('accounting.income') }}
                        </a>
                    </div>
                </div>
                {{-- العمولات --}}
                <div class="col-xxl-4 col-lg-6">
                    <div class="card card-body text-center">
                        <div class="avatar-md mx-auto mb-3">
                            <div class="avatar-title bg-soft-light border border-info p-2 text-success rounded">
                              <lord-icon src="{{URL::asset('assets/images/icon/oaflahpk.json')}}" trigger="loop"
                                    delay="500" colors="primary:#4bb3fd" style="width:250px;height:250px">
                                </lord-icon>
                            </div>
                        </div>
                        <h4 class="card-title font">
                            {{ trans('accounting.commissions') }}
                        </h4>
                        <a href="{{ route('expenses.index') }}" class="btn btn-info">
                            {{ trans('accounting.commissions') }}
                        </a>
                    </div>
                </div>
                {{-- المرتبات --}}
                <div class="col-xxl-4 col-lg-6">
                    <div class="card card-body text-center">
                        <div class="avatar-md mx-auto mb-3">
                            <div class="avatar-title bg-soft-light border border-info p-2 text-success rounded">
                              <lord-icon src="{{URL::asset('assets/images/icon/oaflahpk.json')}}" trigger="loop"
                                    delay="500" colors="primary:#4bb3fd" style="width:250px;height:250px">
                                </lord-icon>
                            </div>
                        </div>
                        <h4 class="card-title font">
                            {{ trans('accounting.salaries') }}
                        </h4>
                        <a href="{{ route('expenses.index') }}" class="btn btn-info">
                            {{ trans('accounting.salaries') }}
                        </a>
                    </div>
                </div>

                <!-- end col -->
            </div><!-- end row -->
        </div><!-- end col -->
    </div><!-- end row -->


@endsection
