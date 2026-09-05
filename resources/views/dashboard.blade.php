@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-shared.page-header
        title="{{ $role->label }} dashboard"
        subtitle="You are signed in as {{ $role->label }}."
    />

    <div class="alert alert-info mb-0" role="status">
        Your module dashboard has not been built yet.
    </div>
@endsection
