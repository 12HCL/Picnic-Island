{{--
    ferry.staff.manifest — the passenger list for one sailing. Module 3, Ferry.

    Cancelled passes are listed rather than hidden: a manifest that quietly omits them
    cannot be reconciled against seats_taken, and an operator needs to see that a pass was
    voided rather than simply not find it.
--}}
@extends('layouts.app')
@section('title', 'Passenger manifest')
@section('content')

<x-shared.page-header
    title="Passenger manifest"
    :subtitle="$schedule->route->origin.' to '.$schedule->route->destination.' — '.$schedule->departure_date->format('D j M Y').' at '.\Illuminate\Support\Carbon::parse($schedule->departure_time)->format('H:i').($schedule->departure_date->isToday() ? ' (today)' : ', not today — today is '.now()->format('j F Y'))">
    <a href="{{ route('ferry.staff.schedules.index') }}" class="btn btn-outline-secondary">Timetable</a>
</x-shared.page-header>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Vessel" :value="$schedule->vessel->name" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Issued" :value="$issued" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Boarded" :value="$boarded" color="secondary" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Seats taken"
            :value="$schedule->seats_taken.' of '.$schedule->vessel->capacity"
            hint="BR-02 refuses a ticket once these are equal" />
    </div>
</div>

@if ($cancelled > 0)
    <div class="alert alert-secondary">
        {{ $cancelled }} cancelled {{ Str::plural('pass', $cancelled) }} shown below. Cancelling
        returns the seat, so these are not counted in seats taken.
    </div>
@endif

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Reference</th>
                    <th scope="col">Passenger</th>
                    <th scope="col">Authorising booking</th>
                    <th scope="col">Issued</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr @class(['table-light text-body-secondary' => $ticket->status === 'cancelled'])>
                        <td class="font-monospace">{{ $ticket->reference }}</td>
                        <td>
                            {{ $ticket->user->name }}
                            <div class="small text-body-secondary">{{ $ticket->user->email }}</div>
                        </td>
                        <td>
                            <span class="font-monospace">{{ $ticket->hotelBooking->reference }}</span>
                            <div class="small text-body-secondary">{{ $ticket->hotelBooking->hotel->name }}</div>
                        </td>
                        <td>
                            @if ($ticket->issuedBy)
                                Counter
                                <div class="small text-body-secondary">by {{ $ticket->issuedBy->name }}</div>
                            @else
                                Online
                            @endif
                        </td>
                        <td><x-shared.status-badge :status="$ticket->status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-0">
                            <x-shared.empty-state message="No passes have been issued for this sailing yet." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
