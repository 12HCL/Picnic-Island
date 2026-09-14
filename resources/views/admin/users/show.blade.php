@extends('layouts.app')

@section('title', $user->name)

@section('content')
    <x-shared.page-header
        :title="$user->name"
        subtitle="User account details and recorded activity."
    >
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary">Edit user</a>

        @if ($user->is_active)
            <form method="POST" action="{{ route('admin.users.deactivate', $user) }}"
                  onsubmit="return confirm('Deactivate this user account?')">
                @csrf
                <button type="submit" class="btn btn-outline-danger">Deactivate</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                @csrf
                <button type="submit" class="btn btn-outline-success">Activate</button>
            </form>
        @endif
    </x-shared.page-header>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Account details</span>
                    <x-shared.status-badge :status="$user->is_active ? 'active' : 'inactive'" />
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $user->name }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $user->email }}</dd>

                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">{{ $user->phone ?? 'Not provided' }}</dd>

                        <dt class="col-sm-4">Role</dt>
                        <dd class="col-sm-8">{{ $user->role->label }}</dd>

                        <dt class="col-sm-4">Created</dt>
                        <dd class="col-sm-8 mb-0">{{ $user->created_at->format('d M Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-body-tertiary fw-semibold">Recorded activity</div>
                <div class="card-body">
                    @if (array_sum($activityCounts) === 0)
                        <x-shared.empty-state message="This user has no bookings, ferry tickets, or payments yet." />
                    @else
                        <dl class="row mb-0">
                            <dt class="col-8">Hotel bookings</dt>
                            <dd class="col-4 text-end">{{ $activityCounts['hotel_bookings'] }}</dd>

                            <dt class="col-8">Ferry tickets</dt>
                            <dd class="col-4 text-end">{{ $activityCounts['ferry_tickets'] }}</dd>

                            <dt class="col-8">Payments</dt>
                            <dd class="col-4 text-end mb-0">{{ $activityCounts['payments'] }}</dd>
                        </dl>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
