@extends('layouts.app')

@section('title', 'Manage users')

@section('content')
    <x-shared.page-header
        title="Manage users"
        subtitle="Search accounts, filter by role, and review whether each account is active."
    />

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input
                        type="search"
                        class="form-control"
                        id="search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Name or email address"
                    >
                </div>

                <div class="col-md-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected($filters['role'] === $role->name)>
                                {{ $role->label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    @if ($filters['search'] !== null || $filters['role'] !== null)
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if ($users->isEmpty())
        <div class="card border-0 shadow-sm">
            <x-shared.empty-state message="No users match the selected filters." />
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Role</th>
                            <th scope="col">Account status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="fw-semibold">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone ?? 'Not provided' }}</td>
                                <td>{{ $user->role->label }}</td>
                                <td>
                                    <x-shared.status-badge :status="$user->is_active ? 'active' : 'inactive'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="card-footer bg-transparent py-3">
                    {{ $users->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    @endif
@endsection
