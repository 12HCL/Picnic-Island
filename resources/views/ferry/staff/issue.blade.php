{{--
    ferry.staff.issue — counter issuance. UC-14. Module 3, Ferry.

    The same rules as the visitor's own purchase, because both go through
    FerryTicketIssueService. The operator picks a hotel booking first, and the passenger is
    that booking's owner — never a separately chosen person.
--}}
@extends('layouts.app')
@section('title', 'Issue a ferry pass')
@section('content')

<x-shared.page-header
    title="Issue a ferry pass"
    subtitle="Counter issuance — find the visitor's hotel booking, then pick their sailing">
    <a href="{{ route('ferry.dashboard') }}" class="btn btn-outline-secondary">Back to operations</a>
</x-shared.page-header>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('ferry.staff.issue') }}" class="row g-2">
            <div class="col-md-9">
                <label for="search" class="visually-hidden">Booking reference, name or email</label>
                <input type="text" name="search" id="search" class="form-control"
                       placeholder="Booking reference, visitor name or email"
                       value="{{ $search }}" autofocus>
                <div class="form-text">
                    UC-14 A2: a walk-up passenger often does not know their reference, so name
                    and email are searchable too. Only confirmed and checked-in bookings appear.
                </div>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </div>
</div>

@if ($search !== '')
    @forelse ($bookings as $booking)
        @if ($loop->first)
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Matching hotel bookings</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Booking</th>
                                <th scope="col">Visitor</th>
                                <th scope="col">Stay</th>
                                <th scope="col">Status</th>
                                <th scope="col" style="min-width: 22rem;">Issue a pass</th>
                            </tr>
                        </thead>
                        <tbody>
        @endif

                            <tr>
                                <td>
                                    <span class="font-monospace">{{ $booking->reference }}</span>
                                    <div class="small text-body-secondary">{{ $booking->hotel->name }}</div>
                                </td>
                                <td>
                                    {{ $booking->user->name }}
                                    <div class="small text-body-secondary">{{ $booking->user->email }}</div>
                                </td>
                                <td>
                                    {{ $booking->check_in->format('j M') }} to
                                    {{ $booking->check_out->format('j M Y') }}
                                </td>
                                <td><x-shared.status-badge :status="$booking->status" /></td>
                                <td>
                                    <form method="POST" action="{{ route('ferry.staff.issue.store') }}"
                                          class="row g-2">
                                        @csrf
                                        <input type="hidden" name="hotel_booking_id" value="{{ $booking->id }}">

                                        <div class="col-7">
                                            <label class="visually-hidden"
                                                   for="schedule-{{ $booking->id }}">Sailing</label>
                                            <select name="ferry_schedule_id" id="schedule-{{ $booking->id }}"
                                                    class="form-select form-select-sm" required>
                                                @foreach ($schedules as $schedule)
                                                    <option value="{{ $schedule->id }}">
                                                        {{ $schedule->departure_date->format('j M') }}
                                                        {{ \Illuminate\Support\Carbon::parse($schedule->departure_time)->format('H:i') }}
                                                        — {{ $schedule->route->destination }}
                                                        ({{ $schedule->seatsRemaining() }} left)
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-3">
                                            <label class="visually-hidden"
                                                   for="method-{{ $booking->id }}">Payment</label>
                                            <select name="method" id="method-{{ $booking->id }}"
                                                    class="form-select form-select-sm" required>
                                                @foreach ($methods as $method)
                                                    <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-2">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">Issue</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>

        @if ($loop->last)
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @empty
        <x-shared.empty-state
            message="No confirmed hotel booking matches that search. A visitor with no booking cannot be issued a ferry pass — BR-01 applies at the counter exactly as it does online." />
    @endforelse
@endif

@endsection
