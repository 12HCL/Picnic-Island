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
    <span data-theme-icon aria-hidden="true">&#9790;</span>
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
