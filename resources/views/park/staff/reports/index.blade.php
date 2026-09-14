@extends('layouts.app')
@section('title', 'Park Reports')
@section('content')

<x-shared.page-header
    title="Sales &amp; visitor reports"
    subtitle="Reported over the date each event ran, not the date it was paid for.">
    <a href="{{ route('park.dashboard') }}" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
</x-shared.page-header>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('park.staff.reports.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="from" class="form-label small text-muted">From</label>
                <input type="date" name="from" id="from" class="form-control form-control-sm" value="{{ $from }}">
            </div>
            <div class="col-md-3">
                <label for="to" class="form-label small text-muted">To</label>
                <input type="date" name="to" id="to" class="form-control form-control-sm" value="{{ $to }}">
            </div>
            <div class="col-md-3">
                <label for="channel" class="form-label small text-muted">Channel</label>
                <select name="channel" id="channel" class="form-select form-select-sm">
                    <option value="">-- Both channels --</option>
                    <option value="online" {{ $channel === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="gate" {{ $channel === 'gate' ? 'selected' : '' }}>Gate</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Run report</button>
                <a href="{{ route('park.staff.reports.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Revenue" :value="'MVR ' . number_format($revenue, 2)" color="success" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Admissions sold" :value="$admissions" :hint="$tickets . ' tickets'" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Attendance"
            :value="$attendanceRate . '%'"
            :hint="$admitted . ' validated at the gate'"
            color="secondary" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Cancelled"
            :value="'MVR ' . number_format($cancelledValue, 2)"
            :hint="$cancelledTickets . ' tickets — refunds due'"
            color="danger" />
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent">
                By channel
                <span class="small text-body-secondary d-block">
                    Grouped by channel, not by user — a gate sale has no account behind it.
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Channel</th>
                            <th class="text-end">Tickets</th>
                            <th class="text-end">Admissions</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byChannel as $row)
                            <tr>
                                <td class="text-capitalize">{{ $row->channel }}</td>
                                <td class="text-end">{{ $row->tickets }}</td>
                                <td class="text-end">{{ $row->admissions }}</td>
                                <td class="text-end">MVR {{ number_format((float) $row->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-shared.empty-state message="No sales in this window." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent">By activity</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Activity</th>
                            <th>Type</th>
                            <th class="text-end">Tickets</th>
                            <th class="text-end">Admissions</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byActivity as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row->activity }}</td>
                                <td class="text-capitalize small text-body-secondary">
                                    {{ str_replace('_', ' ', $row->type) }}
                                </td>
                                <td class="text-end">{{ $row->tickets }}</td>
                                <td class="text-end">{{ $row->admissions }}</td>
                                <td class="text-end">MVR {{ number_format((float) $row->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-shared.empty-state message="Nothing ran in this window." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent">Day by day</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Admissions</th>
                            <th class="text-end">Revenue</th>
                            <th style="min-width: 200px;">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $peak = $byDay->max('revenue') ?: 1; @endphp
                        @forelse ($byDay as $row)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($row->day)->format('D j M Y') }}</td>
                                <td class="text-end">{{ $row->admissions }}</td>
                                <td class="text-end">MVR {{ number_format((float) $row->revenue, 2) }}</td>
                                <td>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar"
                                             style="width: {{ round((float) $row->revenue / $peak * 100) }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-shared.empty-state message="No sales in this window." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
