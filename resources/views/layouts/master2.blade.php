<!DOCTYPE html>
<html lang="en">
	<head>
		<title>C M S</title>
	    <link rel="icon" href="{{URL::asset('assets/images/logo.png')}}" type="image/x-icon" /> 
		<!-- Layout config Js -->
		<script src="{{URL::asset('ssets/js/layout.js')}}"></script>
		<!-- Bootstrap Css -->
		<link href="{{URL::asset('assets/css/bootstrap.min.css')}}" rel="stylesheet"/>
		<!-- Icons Css -->
		<link href="{{URL::asset('assets/css/icons.min.css')}}" rel="stylesheet"/>
		<!-- App Css-->
		<link href="{{URL::asset('assets/css/app.min.css')}}" rel="stylesheet"/>  
		<!-- custom Css-->
		<link href="{{URL::asset('assets/css/custom.min.css')}}" rel="stylesheet" type="text/css" />
	</head>
	
	<body class="main-body bg-primary-transparent">
		@yield('content')		
		@include('layouts.footer-scripts_login')	
	</body>
</html>