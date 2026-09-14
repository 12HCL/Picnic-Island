@extends('layouts.app')

@section('title', 'Edit user')

@section('content')
    <x-shared.page-header
        title="Edit user"
        :subtitle="$user->name"
    />

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')
                @include('admin.users._form', [
                    'passwordRequired' => false,
                    'submitLabel' => 'Save changes',
                ])
            </form>
        </div>
    </div>
@endsection
