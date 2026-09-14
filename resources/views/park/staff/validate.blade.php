@extends('layouts.app')
@section('title', 'Validate Tickets')
@section('content')

<x-shared.page-header
    title="Validate at the gate"
    subtitle="Enter a reference to admit or refuse. UC-16.">
    <a href="{{ route('park.dashboard') }}" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
</x-shared.page-header>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger py-2 small">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('park.staff.validate.check') }}">
                    @csrf
                    <label for="reference" class="form-label small text-muted">Ticket reference</label>
                    <input type="text" name="reference" id="reference" maxlength="20" required autofocus
                           autocomplete="off" placeholder="PIB-TK-000123"
                           class="form-control form-control-lg text-uppercase mb-3">

                    <button type="submit" class="btn btn-primary btn-lg w-100">Check</button>
                </form>

                <p class="text-body-secondary small mt-3 mb-0">
                    The field keeps focus after each check, so a group can be validated one
                    reference after another without leaving this screen.
                </p>
            </div>
        </div>

        {{-- A1: the visitor cannot produce a reference. --}}
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-transparent">No reference? Search by name</div>
            <div class="card-body">
                <form method="GET" action="{{ route('park.staff.validate') }}" class="d-flex gap-2">
                    <input type="text" name="name" class="form-control form-control-sm"
                           value="{{ $search }}" placeholder="Visitor name">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Search</button>
                </form>

                @if ($search !== '')
                    <hr>
                    @forelse ($matches as $match)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div class="small">
                                <div class="fw-semibold">{{ $match->user->name ?? 'Gate sale' }}</div>
                                <div class="text-body-secondary">
                                    <code>{{ $match->reference }}</code>
                                    &middot; {{ $match->event->activity->name }}
                                    &middot; {{ $match->quantity }} adm.
                                </div>
                            </div>
                            <x-shared.status-badge :status="$match->status" />
                        </div>
                    @empty
                        <p class="text-body-secondary small mb-0 pt-2">
                            No tickets for today matching that name.
                        </p>
                    @endforelse
                    <p class="text-body-secondary small mb-0 mt-2">
                        Today's events only — searching all time would return last month's
                        tickets and invite admitting one.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        @if ($result)
            @php $admitted = $result['verdict'] === 'admitted'; @endphp

            <div class="card shadow-sm border-{{ $admitted ? 'success' : 'danger' }}">
                <div class="card-body text-center py-5">
                    <p class="display-6 mb-2 text-{{ $admitted ? 'success' : 'danger' }}">
                        {{ $admitted ? 'ADMIT' : 'DO NOT ADMIT' }}
                    </p>
                    <p class="lead mb-0">{{ $result['reason'] }}</p>
                </div>

                @if ($result['ticket'])
                    @php $t = $result['ticket']; @endphp
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-body-secondary small">Reference</span>
                            <code>{{ $t->reference }}</code>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-body-secondary small">Event</span>
                            <span>{{ $t->event->activity->name }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-body-secondary small">Event date</span>
                            <span>{{ $t->event->event_date->format('D j M Y') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-body-secondary small">Admissions</span>
                            <span>{{ $t->quantity }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-body-secondary small">Status</span>
                            <x-shared.status-badge :status="$t->status" />
                        </li>
                    </ul>
                @endif
            </div>
        @else
            <div class="card shadow-sm">
                <div class="card-body">
                    <x-shared.empty-state
                        message="Enter a ticket reference on the left. The verdict appears here." />
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
