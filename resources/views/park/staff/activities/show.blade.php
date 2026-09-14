@extends('layouts.app')
@section('title', $activity->name)
@section('content')

<x-shared.page-header
    :title="$activity->name"
    :subtitle="ucfirst(str_replace('_', ' ', $activity->type))">
    <a href="{{ route('park.staff.activities.edit', $activity) }}" class="btn btn-primary btn-sm">Edit</a>
    <a href="{{ route('park.staff.activities.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Catalogue</a>
</x-shared.page-header>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-6 text-body-secondary">Status</dt>
                    <dd class="col-6">
                        <span class="badge text-bg-{{ $activity->is_active ? 'success' : 'secondary' }}">
                            {{ $activity->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>

                    <dt class="col-6 text-body-secondary">Default capacity</dt>
                    <dd class="col-6">{{ $activity->default_capacity }}</dd>

                    <dt class="col-6 text-body-secondary">Base price</dt>
                    <dd class="col-6">MVR {{ number_format((float) $activity->base_price, 2) }}</dd>

                    <dt class="col-6 text-body-secondary">Map location</dt>
                    <dd class="col-6">{{ $activity->mapLocation->name ?? '—' }}</dd>
                </dl>

                @if ($activity->description)
                    <hr>
                    <p class="small mb-0">{{ $activity->description }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent">Scheduled events</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Start</th>
                            <th class="text-end">Capacity</th>
                            <th class="text-end">Taken</th>
                            <th class="text-end">Full</th>
                            <th class="text-end">Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td>{{ $event->event_date->format('D j M Y') }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') }}</td>
                                <td class="text-end">{{ $event->capacity }}</td>
                                <td class="text-end">{{ $event->seats_taken }}</td>
                                <td class="text-end">{{ $event->percentFull() }}%</td>
                                <td class="text-end">MVR {{ number_format((float) $event->price, 2) }}</td>
                                <td><x-shared.status-badge :status="$event->status" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <x-shared.empty-state
                                        message="This activity has never been scheduled." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $events->links() }}</div>
    </div>
</div>

@endsection
