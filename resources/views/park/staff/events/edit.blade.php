@extends('layouts.app')
@section('title', 'Edit Event')
@section('content')

<x-shared.page-header
    :title="'Edit ' . $event->activity->name"
    :subtitle="$event->event_date->format('l j F Y') . ' at ' . \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i')">
    <a href="{{ route('park.staff.events.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Schedule</a>
</x-shared.page-header>

@if ($event->seats_taken > 0)
    <div class="alert alert-info">
        <strong>{{ $event->seats_taken }} admission(s) already sold.</strong>
        Changing the price will not reprice them — tickets keep the price they were bought at.
        Capacity cannot be lowered below what is sold.
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('park.staff.events.update', $event) }}">
            @csrf
            @method('PUT')
            @include('park.staff.events._form')

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="{{ route('park.staff.events.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
