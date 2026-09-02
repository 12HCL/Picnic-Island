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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body class="bg-body-tertiary">

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-sm-10 col-md-7 col-lg-5">

            <div class="text-center mb-4">
                <a class="h4 text-decoration-none fw-semibold" href="{{ url('/') }}">
                    {{ config('app.name') }}
                </a>
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
