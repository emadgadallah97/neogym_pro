<!-- Title -->
<title> @yield('title') </title>
<!-- Favicon -->
<link rel="icon" href="{{ asset('assets/images/logo-sm.png') }}" type="image/x-icon" />
<link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/css/responsive.bootstrap.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('css/prescription-styles.css') }}">
<!-- Layout config Js -->
<script src="{{ asset('assets/js/layout.js') }}"></script>

<!-- Bootstrap Css -->
<!-- Icons Css -->

<link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" />
<!-- App Css-->
@if (App::getlocale() == 'ar')
    <link href="{{ asset('assets/css/bootstrap-rtl.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/app-rtl.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/custom-rtl.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/book-rtl.css') }}" rel="stylesheet" />
@else
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/book.css') }}" rel="stylesheet" />
@endif
<!-- custom Css-->
<link href="{{ asset('assets/plugins/notify/css/notifIt.css') }}" rel="stylesheet" />
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Sweet Alert css-->
<link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<script src="{{ URL::asset('assets/images/icon/lordicon.js') }}"></script>





@yield('css')

<script>
    window.onload = function() {
        @if (session()->has('add'))
            notif({
                msg: "{{ session('add') }}",
                type: "success"
            });
        @endif

        @if (session()->has('error_system'))
            notif({
                msg: "{{ session('error_system') }}",
                type: "error"
            });
        @endif

        @if (session()->has('warning_system'))
            notif({
                msg: "{{ session('warning_system') }}",
                type: "warning"
            });
        @endif
    }
</script>

