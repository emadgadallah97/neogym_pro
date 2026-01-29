
@extends('layouts.master')
@section('title')
{{ trans('main_trans.title') }}



@stop


@section('content')
         <!-- start page title -->
         <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font">{{ trans('settings_trans.settings') }}</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('settings_trans.settings') }}</a></li>
                            <li class="breadcrumb-item active">{{ trans('settings_trans.system_settings') }}</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="row">
                    <div class="col-xxl-6 col-lg-6">
                        <div class="card card-body text-center">
                            <div class="avatar-md mx-auto mb-3">
                                <div class="avatar-title bg-soft-light  border border-info p-2 text-info  rounded">
                                   <i class="mdi mdi-cog" style="font-size: 35px"></i>
                                </div>
                            </div>
                            <h4 class="card-title font font">{{ trans('settings_trans.countries') }}</h4>
                            <a  href="{{ url('/' . $page='countries') }}" class="btn btn-info">{{ trans('settings_trans.Go_to_settings_now') }}</a>
                        </div>
                    </div><!-- end col -->
                    <div class="col-xxl-6 col-lg-6">
                        <div class="card card-body text-center">
                            <div class="avatar-md mx-auto mb-3">
                                <div class="avatar-title bg-soft-light  border border-info p-2 text-info  rounded">
                                    <i class="mdi mdi-cog" style="font-size: 35px"></i>
                                 </div>
                            </div>
                            <h4 class="card-title font">{{ trans('settings_trans.governorate') }}</h4>
                            <a  href="{{ url('/' . $page='government') }}" class="btn btn-info">{{ trans('settings_trans.Go_to_settings_now') }}</a>
                        </div>
                    </div><!-- end col -->
                    <div class="col-xxl-6 col-lg-6">
                        <div class="card card-body text-center">
                            <div class="avatar-md mx-auto mb-3">
                                <div class="avatar-title bg-soft-light  border border-info p-2 text-info  rounded">
                                    <i class="mdi mdi-cog" style="font-size: 35px"></i>
                                 </div>
                            </div>
                            <h4 class="card-title font">{{ trans('settings_trans.city') }}</h4>
                            <a  href="{{ url('/' . $page='city') }}" class="btn btn-info">{{ trans('settings_trans.Go_to_settings_now') }}</a>
                        </div>
                    </div>
                    <div class="col-xxl-6 col-lg-6">
                        <div class="card card-body text-center">
                            <div class="avatar-md mx-auto mb-3">
                                <div class="avatar-title bg-soft-light  border border-info p-2 text-info  rounded">
                                    <i class="mdi mdi-cog" style="font-size: 35px"></i>
                                 </div>
                            </div>
                            <h4 class="card-title font">{{ trans('settings_trans.area') }}</h4>
                            <a href="{{ url('/' . $page='area') }}"class="btn btn-info">{{ trans('settings_trans.Go_to_settings_now') }}</a>
                        </div>
                    </div>
                    <!-- end col -->
                </div><!-- end row -->
            </div><!-- end col -->
        </div><!-- end row -->
  


@endsection

