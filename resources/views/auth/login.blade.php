@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
    <h1 class="h4 mb-4">Log in to your account</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                class="form-control @error('email') is-invalid @enderror"
                autocomplete="email"
                required
                autofocus
            >
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                class="form-control @error('password') is-invalid @enderror"
                autocomplete="current-password"
                required
            >
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-check mb-4">
            <input
                id="remember"
                name="remember"
                type="checkbox"
                value="1"
                class="form-check-input"
                @checked(old('remember'))
            >
            <label for="remember" class="form-check-label">Remember me</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Log in</button>
    </form>

    <p class="text-center text-body-secondary small mt-4 mb-0">
        Do not have an account? <a href="{{ route('register') }}">Register</a>
    </p>
@endsection
