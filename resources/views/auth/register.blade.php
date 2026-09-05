@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <h1 class="h4 mb-4">Create an account</h1>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Full name</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                class="form-control @error('name') is-invalid @enderror"
                autocomplete="name"
                required
                autofocus
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

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
            >
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">
                Phone number <span class="text-muted">(optional)</span>
            </label>
            <input
                id="phone"
                name="phone"
                type="tel"
                value="{{ old('phone') }}"
                class="form-control @error('phone') is-invalid @enderror"
                autocomplete="tel"
            >
            @error('phone')
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
                autocomplete="new-password"
                required
            >
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                class="form-control"
                autocomplete="new-password"
                required
            >
        </div>

        <button type="submit" class="btn btn-primary w-100">Create account</button>
    </form>
@endsection
