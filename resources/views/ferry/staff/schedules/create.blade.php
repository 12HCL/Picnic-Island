@extends('layouts.app')
@section('title', 'Schedule a sailing')
@section('content')

<x-shared.page-header
    title="Schedule a sailing"
    subtitle="A crossing on a route, on a boat, at a date and time. UC-13.">
    <a href="{{ route('ferry.staff.schedules.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Timetable</a>
</x-shared.page-header>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('ferry.staff.schedules.store') }}">
                    @csrf
                    @include('ferry.staff.schedules._form')

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Confirm schedule</button>
                        <a href="{{ route('ferry.staff.schedules.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6">What is checked</h2>
                <ul class="small text-body-secondary mb-0 ps-3">
                    <li>A new sailing must depart today or later.</li>
                    <li>The same route cannot be scheduled twice at one date and time.</li>
                    <li>One vessel cannot be on two overlapping crossings that day, on this
                        route or any other.</li>
                    <li>Only active vessels can be assigned.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection
