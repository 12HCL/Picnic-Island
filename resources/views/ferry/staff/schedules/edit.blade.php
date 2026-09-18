@extends('layouts.app')
@section('title', 'Edit sailing')
@section('content')

<x-shared.page-header
    title="Edit sailing"
    :subtitle="$schedule->route->origin.' to '.$schedule->route->destination.' — '.$schedule->departure_date->format('j M Y')">
    <a href="{{ route('ferry.staff.manifest', $schedule) }}" class="btn btn-outline-secondary btn-sm">Manifest</a>
    <a href="{{ route('ferry.staff.schedules.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Timetable</a>
</x-shared.page-header>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('ferry.staff.schedules.update', $schedule) }}">
                    @csrf
                    @method('PUT')
                    @include('ferry.staff.schedules._form')

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        <a href="{{ route('ferry.staff.schedules.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6">Cancelling this sailing</h2>
                <p class="small text-body-secondary">
                    A sailing is never deleted. Passes already issued reference it, so
                    cancelling sets its status instead. UC-13 E1: a sailing with passengers
                    on it cannot be cancelled until those passes are dealt with.
                </p>

                <form method="POST" action="{{ route('ferry.staff.schedules.cancel', $schedule) }}"
                      onsubmit="return confirm('Cancel this sailing?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm"
                            @disabled($schedule->status === 'cancelled')>
                        Cancel this sailing
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
