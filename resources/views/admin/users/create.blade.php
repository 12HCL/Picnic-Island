@extends('layouts.app')

@section('title', 'Create user')

@section('content')
    <x-shared.page-header
        title="Create user"
        subtitle="Create an account and assign exactly one of the five system roles."
    />

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                @include('admin.users._form', [
                    'passwordRequired' => true,
                    'submitLabel' => 'Create account',
                ])
            </form>
        </div>
    </div>
@endsection
