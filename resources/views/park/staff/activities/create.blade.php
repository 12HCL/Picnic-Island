@extends('layouts.app')
@section('title', 'Add Activity')
@section('content')

<x-shared.page-header
    title="Add an activity"
    subtitle="A ride, show or beach event. Scheduling the dates it runs comes after.">
    <a href="{{ route('park.staff.activities.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Catalogue</a>
</x-shared.page-header>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('park.staff.activities.store') }}">
            @csrf
            @include('park.staff.activities._form')

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Add to catalogue</button>
                <a href="{{ route('park.staff.activities.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
