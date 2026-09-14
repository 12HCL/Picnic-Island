@extends('layouts.app')
@section('title', 'Schedule Event')
@section('content')

<x-shared.page-header
    title="Schedule an event"
    subtitle="Pick an activity and say when it runs. Capacity and price prefill from the catalogue.">
    <a href="{{ route('park.staff.events.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Schedule</a>
</x-shared.page-header>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('park.staff.events.store') }}">
            @csrf
            @include('park.staff.events._form')

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Schedule it</button>
                <a href="{{ route('park.staff.events.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
