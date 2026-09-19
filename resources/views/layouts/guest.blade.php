{{--
    Centred, chrome-free layout for login, register and other public pages.
    Owned by Faain (BUILD_CONTRACT.md §1). Same Bootstrap CDN tags as layouts/app.blade.php -
    if you change the Bootstrap version, change it in both files.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Welcome') &mdash; {{ config('app.name') }}</title>

    <script>
        (() => {
            const savedTheme = localStorage.getItem('picnic-island-theme');
            document.documentElement.setAttribute('data-bs-theme', savedTheme ?? 'light');
        })();
    </script>

    {{-- Vendored locally, like layouts/app.blade.php. The CDN tags that were here meant
         login and register were unstyled on a machine with no internet. --}}
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/site.css') }}" rel="stylesheet">
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}" defer></script>
    <script src="{{ asset('js/theme.js') }}" defer></script>
</head>
<body class="pi-guest">

<button type="button" class="btn btn-outline-light btn-sm pi-theme-toggle pi-theme-toggle--guest" data-theme-toggle aria-pressed="false">
    <span class="pi-theme-icon" data-theme-icon aria-hidden="true">
        {{-- Bootstrap Icons: moon -- https://icons.getbootstrap.com/icons/moon/ --}}
        <svg class="pi-theme-icon-moon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278M4.858 1.311A7.27 7.27 0 0 0 1.025 7.71c0 4.02 3.279 7.276 7.319 7.276a7.32 7.32 0 0 0 5.205-2.162q-.506.063-1.029.063c-4.61 0-8.343-3.714-8.343-8.29 0-1.167.242-2.278.681-3.286"/>
        </svg>
        {{-- Bootstrap Icons: sun -- https://icons.getbootstrap.com/icons/sun/ --}}
        <svg class="pi-theme-icon-sun" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6m0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13M2.343 2.343a.5.5 0 0 1 .707 0l1.414 1.414a.5.5 0 1 1-.707.707L2.343 3.05a.5.5 0 0 1 0-.707m9.193 9.193a.5.5 0 0 1 .707 0l1.414 1.414a.5.5 0 1 1-.707.707l-1.414-1.414a.5.5 0 0 1 0-.707M0 8a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2A.5.5 0 0 1 0 8m13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2A.5.5 0 0 1 13 8m.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.414a.5.5 0 1 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0M4.464 11.536a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0"/>
        </svg>
    </span>
    <span data-theme-label>Dark mode</span>
</button>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-sm-10 col-md-7 col-lg-5">

            <div class="text-center mb-4">
                <a class="h3 text-decoration-none fw-semibold text-white" href="{{ url('/') }}">
                    {{ config('app.name') }}
                </a>
                <p class="pi-eyebrow text-white-50 mt-2 mb-0">Hotel, ferry and theme park bookings</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    @yield('content')
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
