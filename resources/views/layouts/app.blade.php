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

    <script>
        (() => {
            const savedTheme = localStorage.getItem('picnic-island-theme');
            document.documentElement.setAttribute('data-bs-theme', savedTheme ?? 'light');
        })();
    </script>

    {{-- Bootstrap is vendored locally and pinned at 5.3.3. This project still has no build step. --}}
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    {{-- Site theme: re-colours the vendored Bootstrap and adds the shared page furniture.
         Loaded after Bootstrap so its variables win, and before @stack so a module's own
         stylesheet can still override it. --}}
    <link href="{{ asset('css/site.css') }}" rel="stylesheet">
    @stack('styles')
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}" defer></script>
    <script src="{{ asset('js/theme.js') }}" defer></script>
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
                    <a class="nav-link {{ request()->routeIs('content.map') ? 'active' : '' }}"
                        href="{{ route('content.map') }}">Island map</a>
                </li>


                @auth
                    @if (auth()->user()->hasRole('visitor'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('visitor.bookings.*') ? 'active' : '' }}" href="{{ route('visitor.bookings.index') }}">My bookings</a>
                        </li>
                    @endif

                    @if (auth()->user()->hasRole('admin'))
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                                </li>
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Users</a>
                                </li>
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">Reports</a>
                                </li>
                            </ul>
                        </li>
                    @endif
                @endauth

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

                {{-- Module 3, Ferry (Naayif). Public like Hotels: browsing the timetable runs
                     before booking, and BR-01 is checked on the booking page after login. --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('ferry.schedules.*') || request()->routeIs('ferry.tickets.*') ? 'active' : '' }}" href="{{ route('ferry.schedules.index') }}">Ferry</a>
                </li>
                @auth
                    @if (auth()->user()->hasRole('ferry_operator'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('ferry.dashboard') || request()->routeIs('ferry.staff.*') ? 'active' : '' }}" href="{{ route('ferry.dashboard') }}">Ferry Operations</a>
                        </li>
                    @endif
                @endauth
            </ul>

            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <button type="button" class="btn btn-outline-light btn-sm pi-theme-toggle" data-theme-toggle aria-pressed="false">
                        <span data-theme-icon aria-hidden="true">&#9790;</span>
                        <span data-theme-label>Dark mode</span>
                    </button>
                </li>
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

@stack('scripts')
</body>
</html>
