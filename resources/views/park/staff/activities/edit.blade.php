@extends('layouts.app')
@section('title', 'Edit Activity')
@section('content')

<x-shared.page-header
    :title="'Edit ' . $activity->name"
    subtitle="Changes here do not reprice or resize anything already scheduled.">
    <a href="{{ route('park.staff.activities.show', $activity) }}" class="btn btn-outline-secondary btn-sm">
        View activity
    </a>
</x-shared.page-header>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('park.staff.activities.update', $activity) }}">
            @csrf
            @method('PUT')
            @include('park.staff.activities._form')

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="{{ route('park.staff.activities.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
