{{--
    TEMPORARY holding page for GET / - delete when Safhaan's Content\HomeController@index
    and home.blade.php exist (BUILD_CONTRACT.md §3, Module 5).

    Deliberately not called home.blade.php, so it cannot collide with the real one.
    It exists only so the app does not 404 on its own front page between the scaffold
    commit and the first module. Nothing here is a design decision.
--}}
@extends('layouts.app')

@section('title', 'Scaffold')

@section('content')
    <x-shared.page-header
        title="Scaffold is running"
        subtitle="Laravel + Blade + Bootstrap, connected to MySQL. No module code yet." />

    <div class="alert alert-warning">
        <strong>This page is a placeholder.</strong> The real front page is Safhaan's
        <code>Content\HomeController@index</code> rendering <code>home.blade.php</code>.
        Delete <code>scaffold-placeholder.blade.php</code> and the temporary route in
        <code>routes/web.php</code> when it lands.
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <x-shared.stat-card label="Framework" :value="app()->version()" />
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-shared.stat-card label="PHP" :value="PHP_VERSION" />
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-shared.stat-card label="Database" value="{{ config('database.default') }}" hint="Engine pinned to InnoDB" />
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-shared.stat-card label="Build step" value="None" hint="Bootstrap from CDN" color="success" />
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Shared components are available to every module</h2>
            <p class="text-body-secondary small">
                Defined in <code>resources/views/components/shared/</code> and owned by Faain
                (BUILD_CONTRACT.md §5). Use these rather than inventing your own.
            </p>

            <p class="mb-2"><code>&lt;x-shared.status-badge :status="..." /&gt;</code></p>
            <p class="mb-4 d-flex flex-wrap gap-2">
                @foreach (['confirmed', 'pending', 'scheduled', 'completed', 'cancelled', 'paid', 'refunded', 'failed'] as $example)
                    <x-shared.status-badge :status="$example" />
                @endforeach
            </p>

            <x-shared.empty-state message="This is x-shared.empty-state - use it inside @forelse, never a bare 'No results'." />
        </div>
    </div>
@endsection
