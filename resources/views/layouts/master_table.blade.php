<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="light" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="Description" content="نظام ادارة المستشفيات">
    <meta name="Author" content="نظام ادرة المستشفيات">
    <meta name="Keywords" content="المستشفيات" />
    @include('layouts.head')
</head>

<body>
    <div id="layout-wrapper">
        @include('layouts.main-header')
        @include('layouts.main-sidebar')

        @include('layouts.javascripttable')

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>

            {{-- Global barcode scan listener --}}
            @include('layouts.barcode_listener')

            @include('layouts.footer')
            @include('layouts.json_function')
        </div>
    </div>
</body>
</html>
