@extends('layouts.master2')

@section('title')
{{ trans('dashboard.dashboard') }} - NeoGym PRO
@stop

@section('content')
<style>
    body {
        font-family: 'Outfit', sans-serif !important;
        margin: 0;
        padding: 0;
    }

    .auth-page-wrapper {
        background: url('{{ asset("assets/images/gym-login-bg.png") }}') no-repeat center center fixed;
        background-size: cover;
        position: relative;
    }

    .bg-overlay {
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(5px);
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.08) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 24px !important;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .auth-one-bg {
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.4) 0%, rgba(13, 110, 253, 0.1) 100%);
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    .text-premium {
        background: linear-gradient(135deg, #fff 0%, #cbd5e0 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 700;
    }

    .btn-premium {
        background: linear-gradient(90deg, #0d6efd 0%, #0b5ed7 100%);
        border: none;
        padding: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        border-radius: 12px;
        color: white;
    }

    .btn-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(13, 110, 253, 0.4);
        background: linear-gradient(90deg, #0b5ed7 0%, #0d6efd 100%);
        color: white;
    }

    .form-control {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        padding: 12px 16px;
        border-radius: 12px;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(13, 110, 253, 0.5) !important;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1) !important;
    }

    .form-label {
        color: #cbd5e0 !important;
        font-weight: 500;
        margin-bottom: 8px;
    }

    .carousel-indicators [data-bs-target] {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin: 0 4px;
    }

    .quote-text {
        font-size: 1.1rem;
        line-height: 1.6;
        color: #e2e8f0;
        font-weight: 300;
        font-style: italic;
    }

    .welcome-title {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .gym-badge {
        background: rgba(13, 110, 253, 0.2);
        color: #60a5fa;
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 20px;
    }
</style>

<div class="auth-page-wrapper py-5 d-flex justify-content-center align-items-center min-vh-100">
    <div class="bg-overlay"></div>
    <div class="auth-page-content overflow-hidden pt-lg-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card glass-card">
                        <div class="row g-0">
                            <div class="col-lg-6 d-none d-lg-block">
                                <div class="p-lg-5 p-4 auth-one-bg h-100 d-flex flex-column">
                                    <div class="mb-5">
                                        <a href="/" class="d-flex align-items-center text-decoration-none">
                                            <img src="{{ asset('assets/images/logo.png') }}" alt="" height="50" class="me-3">
                                            <span class="fs-24 text-premium">NeoGym PRO</span>
                                        </a>
                                    </div>

                                    <div class="mt-auto pb-4">
                                        <div class="mb-4">
                                            <i class="ri-double-quotes-l display-5 text-info opacity-50"></i>
                                        </div>

                                        <div id="qoutescarouselIndicators" class="carousel slide" data-bs-ride="carousel">
                                            <div class="carousel-indicators mb-0" style="bottom: -20px;">
                                                <button type="button" data-bs-target="#qoutescarouselIndicators" data-bs-slide-to="0" class="active"></button>
                                                <button type="button" data-bs-target="#qoutescarouselIndicators" data-bs-slide-to="1"></button>
                                                <button type="button" data-bs-target="#qoutescarouselIndicators" data-bs-slide-to="2"></button>
                                            </div>
                                            <div class="carousel-inner text-start">
                                                <div class="carousel-item active">
                                                    <p class="quote-text">"The only bad workout is the one that didn't happen. Success starts with self-discipline."</p>
                                                </div>
                                                <div class="carousel-item">
                                                    <p class="quote-text">"Fitness is not about being better than someone else. It's about being better than you were yesterday."</p>
                                                </div>
                                                <div class="carousel-item">
                                                    <p class="quote-text">"Take care of your body. It's the only place you have to live. Redefine your limits."</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="p-lg-5 p-4">
                                    <div class="text-center text-lg-start">
                                        <div class="gym-badge">NEOGYM PRO SYSTEM</div>
                                        <h2 class="text-premium welcome-title">Welcome Back</h2>
                                        <p class="text-muted mb-4">Empower your gym management journey.</p>
                                    </div>

                                    <div class="mt-4">
                                        <form method="POST" action="{{ route('login') }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label">Email Address</label>
                                                <input id="email" name="email" type="text" placeholder="Enter your email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                                @error('email')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <!-- <div class="float-end">
                                                    @if (Route::has('password.request'))
                                                    <a href="{{ route('password.request') }}" class="text-muted text-decoration-none fs-13">Forgot password?</a>
                                                    @endif
                                                </div> -->
                                                <label class="form-label" for="password">Password</label>
                                                <div class="position-relative auth-pass-inputgroup">
                                                    <input placeholder="Enter your password" id="password" type="password" class="form-control pe-5 password-input @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                                    @error('password')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-check mb-4">
                                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                                <label class="form-check-label text-muted" for="remember">Remember me</label>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-premium w-100">SIGN IN</button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="mt-5 text-center">
                                        <p class="mb-0 text-muted">&copy; {{ date('year') }} NeoGym. Crafted with ShendyTech<i class="mdi mdi-heart text-danger"></i></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection