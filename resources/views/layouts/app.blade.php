{{--
    The one layout every page in the project extends. Owned by Faain (BUILD_CONTRACT.md §1).
    Do not create a second layout for your module - add to this one or ask.

    Every page view starts:
        @extends('layouts.app')
        @section('title', 'Book a ferry crossing')
        @section('content') ... @endsection
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') &mdash; {{ config('app.name') }}</title>

    {{-- Bootstrap 5 from CDN. There is no Vite and no npm build in this project.
         Sprint 3: download both files into public/css and public/js and switch these
         two tags to asset(), so the demo survives a dropped connection. --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ url('/') }}">{{ config('app.name') }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                {{-- Module owners add their own top-level links here, in module order:
                     admin (Faain), hotel (Raafil), ferry (Naayif), park (Malaaz),
                     content (Safhaan). Add yours when your route exists - a link to a
                     route nobody has written yet is a 404 in the screenshots appendix.
                     Use route() names, not url(), so a renamed URL does not break the nav. --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('hotel.index') || request()->routeIs('hotel.show') ? 'active' : '' }}" href="{{ route('hotel.index') }}">Hotels</a>
                </li>
                @auth
                    @if (auth()->user()->hasRole('hotel_staff'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('hotel.dashboard') || request()->routeIs('hotel.staff.*') ? 'active' : '' }}" href="{{ route('hotel.dashboard') }}">Hotel Management</a>
                        </li>
                    @endif
                @endauth
            </ul>

            <ul class="navbar-nav">
                @auth
                    <li class="nav-item">
                        <span class="navbar-text me-3">{{ auth()->user()->name }}</span>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-light btn-sm" type="submit">Log out</button>
                        </form>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Log in</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('register') }}">Register</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 flex-grow-1">

    {{-- Flash area. Controllers redirect with ->with('success', '...') or ->with('error', '...').
         Use these two keys only, so every module's messages look the same. --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Form Request validation failures land here automatically. Individual forms can
         still show per-field errors with @error('field'). --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <p class="mb-1 fw-semibold">Please correct the following:</p>
            <ul class="mb-0">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer class="bg-body-secondary border-top py-3 mt-auto">
    <div class="container text-center text-body-secondary small">
        {{ config('app.name') }} &mdash; UFCF7S-30-2 Systems Development Group Project
    </div>
</footer>

</body>
</html>
