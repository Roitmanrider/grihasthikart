@extends('layouts.app')

@section('title', 'Admin Login - GrihasthiKart')

@section('content')
    <main class="min-vh-100 d-flex align-items-center bg-light py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <img src="{{ asset('assets/images/logos/logo.png') }}" alt="GrihasthiKart" style="height: 52px;">
                                <h1 class="h4 mt-4 mb-1">Admin Login</h1>
                                <p class="text-muted mb-0">Access is restricted to authorized admin users.</p>
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger">{{ $errors->first() }}</div>
                            @endif

                            <form method="POST" action="{{ route('admin.login.submit') }}">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label" for="email">Email</label>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="password">Password</label>
                                    <input type="password" id="password" name="password" class="form-control" required>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                                    <label class="form-check-label" for="remember">Remember me</label>
                                </div>

                                <button class="btn btn-success w-100" type="submit">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
