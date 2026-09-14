@extends('layouts.app')
@section('title', 'Ticket ' . $ticket->reference)
@section('content')

<x-shared.page-header
    :title="'Ticket ' . $ticket->reference"
    :subtitle="$ticket->event->activity->name">
    <a href="{{ route('park.events.index') }}" class="btn btn-outline-secondary btn-sm">What's on</a>
</x-shared.page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h2 class="h5 mb-1">{{ $ticket->event->activity->name }}</h2>
                        <p class="text-body-secondary small mb-0">
                            {{ $ticket->event->event_date->format('l j F Y') }}
                            at {{ \Illuminate\Support\Carbon::parse($ticket->event->start_time)->format('H:i') }}
                        </p>
                    </div>
                    <x-shared.status-badge :status="$ticket->status" />
                </div>

                <dl class="row mb-0 small">
                    <dt class="col-5 text-body-secondary">Reference</dt>
                    <dd class="col-7"><code>{{ $ticket->reference }}</code></dd>

                    <dt class="col-5 text-body-secondary">Admissions</dt>
                    <dd class="col-7">{{ $ticket->quantity }}</dd>

                    <dt class="col-5 text-body-secondary">Price each</dt>
                    <dd class="col-7">MVR {{ number_format((float) $ticket->unit_price, 2) }}</dd>

                    <dt class="col-5 text-body-secondary">Total paid</dt>
                    <dd class="col-7 fw-semibold">MVR {{ number_format((float) $ticket->total(), 2) }}</dd>

                    <dt class="col-5 text-body-secondary">Channel</dt>
                    <dd class="col-7 text-capitalize">{{ $ticket->channel }}</dd>

                    @if ($ticket->channel === 'gate')
                        <dt class="col-5 text-body-secondary">Sold by</dt>
                        <dd class="col-7">{{ $ticket->soldBy->name ?? 'Park staff' }}</dd>
                    @endif

                    @if ($ticket->validated_at)
                        <dt class="col-5 text-body-secondary">Admitted</dt>
                        <dd class="col-7">{{ $ticket->validated_at->format('D j M Y H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent">Payment</div>
            <div class="card-body">
                @if ($ticket->payment)
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-body-secondary">Reference</dt>
                        <dd class="col-7"><code>{{ $ticket->payment->reference }}</code></dd>

                        <dt class="col-5 text-body-secondary">Method</dt>
                        <dd class="col-7 text-capitalize">{{ $ticket->payment->method }}</dd>

                        <dt class="col-5 text-body-secondary">Status</dt>
                        <dd class="col-7"><x-shared.status-badge :status="$ticket->payment->status" /></dd>

                        <dt class="col-5 text-body-secondary">Paid at</dt>
                        <dd class="col-7">{{ $ticket->payment->paid_at?->format('D j M Y H:i') ?? '—' }}</dd>
                    </dl>
                @else
                    <p class="text-body-secondary small mb-0">No payment recorded against this ticket.</p>
                @endif
            </div>
        </div>

        @if ($ticket->status === 'valid')
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <p class="small text-body-secondary">
                        Cancelling returns the admissions to the event so someone else can buy them.
                        The ticket is kept, marked cancelled — it is never deleted.
                    </p>
                    <form method="POST" action="{{ route('park.tickets.cancel', $ticket) }}"
                          onsubmit="return confirm('Cancel ticket {{ $ticket->reference }}?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Cancel this ticket</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
