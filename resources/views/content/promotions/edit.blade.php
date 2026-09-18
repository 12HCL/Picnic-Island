@extends('layouts.app')
@section('title', 'Edit Promotion')
@section('content')

<x-shared.page-header
    title="Edit {{ $promotion->title }}"
    subtitle="Changes take effect immediately, within the dates set below.">
    <a href="{{ route('content.promotions.show', $promotion) }}" class="btn btn-outline-secondary btn-sm">View</a>
    <a href="{{ route('content.promotions.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Promotions</a>
</x-shared.page-header>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('content.promotions.update', $promotion) }}"
                      enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('content.promotions._form')

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        <a href="{{ route('content.promotions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
