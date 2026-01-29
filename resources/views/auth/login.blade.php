@extends('layouts.master2')
@section('title')
تسجيل دخول - برنامج ادارة العيادات
@stop


@section('content')
 <!-- auth-page wrapper -->
 <div class="auth-page-wrapper auth-bg-cover py-5 d-flex justify-content-center align-items-center min-vh-100">
    <div class="bg-overlay"></div>
    <!-- auth-page content -->
    <div class="auth-page-content overflow-hidden pt-lg-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card overflow-hidden">
                        <div class="row g-0">
                            <div class="col-lg-6">
                                <div class="p-lg-5 p-4 auth-one-bg h-100">
                                    <div class="bg-overlay" style="background: linear-gradient(to right,#fbfbfb,#e1e0ea);"></div>
                                    <div class="position-relative h-100 d-flex flex-column">
                                        <div class="mb-4">
                                            <a href="index.html" class="d-block">
                                                <img src="assets/images/logo.png" alt="" style="height: 100px;width: auto;">
                                            </a>
                                        </div>
                                        <div class="mt-auto">
                                            <div class="mb-3">
                                                <i class="ri-double-quotes-l display-4 text-info"></i>
                                            </div>

                                            <div id="qoutescarouselIndicators" class="carousel slide" data-bs-ride="carousel">
                                                <div class="carousel-indicators">
                                                    <button type="button" data-bs-target="#qoutescarouselIndicators" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                                    <button type="button" data-bs-target="#qoutescarouselIndicators" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                                    <button type="button" data-bs-target="#qoutescarouselIndicators" data-bs-slide-to="2" aria-label="Slide 3"></button>
                                                </div>
                                                <div class="carousel-inner text-center  pb-5">
                                                    <div class="carousel-item active" style="height: 80px;">
                                                        <p class="fs-15 fst-italic">" A customized software solution to streamline clinic operations and optimize patient care. "</p>
                                                    </div>
                                                    <div class="carousel-item" style="height: 80px;">
                                                        <p class="fs-15 fst-italic">"A centralized platform to manage appointments, medical records, billing, and other administrative tasks for a healthcare clinic."</p>
                                                    </div>
                                                    <div class="carousel-item" style="height: 80px;">
                                                        <p class="fs-15 fst-italic">" An integrated clinic management system designed to improve workflow efficiency, enhance collaboration between staff, and provide a superior patient experience."</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- end carousel -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- end col -->

                            <div class="col-lg-6">
                                <div class="p-lg-5 p-4">
                                    <div>
                                        <h5 class="text-info">Welcome Back !</h5>
                                        <p class="text-muted">Sign in to continue to Clinic management system.</p>
                                    </div>

                                    <div class="mt-4">
                                        <form method="POST" action="{{ route('login') }}">
                                            @csrf
                                            <div class="form-group">
                                                <label>Username</label>
                                                <input  id="email" name="email" type="text" placeholder="Enter username" class="form-control @error('email') is-invalid @enderror"  value="{{ old('email') }}" required autocomplete="email" autofocus>
                                                @error('email')
                                                <span class="invalid-feedback" role="alert">
                                                 <strong>{{ $message }}</strong>
                                                 </span>
                                                @enderror
                                            </div>
                                           

                                            <div class="mb-3">
                                                <div class="float-end">
                                                    <a href="auth-pass-reset-cover.html" class="text-muted">Forgot password?</a>
                                                </div>
                                                <label class="form-label" for="password-input">Password</label>
                                                <div class="position-relative auth-pass-inputgroup mb-3">
                                                    <input placeholder="Enter password" id="password" type="password" class="form-control pe-5 password-input @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                                    @error('password')
                                                    <span class="invalid-feedback" role="alert">
                                                  <strong>{{ $message }}</strong>
                                                  </span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"  name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="remember">Remember me</label>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-info w-100" type="submit">Sign In</button>
                                            </div>


                                        </form>
                                    </div>

                                   
                                </div>
                            </div>
                            <!-- end col -->
                        </div>
                        <!-- end row -->
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->

            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- end auth page content -->

    <!-- footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center">
                        <p class="mb-0" style="color: #fff;font-weight: bold;">&copy;
                            <script>document.write(new Date().getFullYear())</script>  Powered by RIS 
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- end Footer -->
</div>
<!-- end auth-page-wrapper -->
@endsection
