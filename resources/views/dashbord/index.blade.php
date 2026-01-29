@extends('layouts.master')
<link href="{{ asset('assets/libs/swiper/swiper-bundle.min.css') }}" rel="stylesheet" />

@section('title')
    {{ trans('main_trans.title') }}


@stop


@section('content')


    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">{{ trans('dashbord_trans.dashboard') }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('dashbord_trans.dashboard') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ trans('dashbord_trans.dashboard') }}</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <form action="{{ route('upload.national_id') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="national_id_image" accept="image/*" required>
            <button type="submit">رفع الصورة</button>
        </form>
        <!-- end col -->
    </div>
    <!-- end row -->




@endsection
