@extends('layouts.app')
@section('title', 'Add Promotion')
@section('content')

<x-shared.page-header
    title="Add a promotion"
    subtitle="A promotional banner shown across the site. UC-18.">
    <a href="{{ route('content.promotions.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Promotions</a>
</x-shared.page-header>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                {{-- enctype is required: this form carries a file, unlike the map location
                     form it is otherwise modelled on. Without it the upload arrives empty
                     and the image is silently dropped. --}}
                <form method="POST" action="{{ route('content.promotions.store') }}"
                      enctype="multipart/form-data">
                    @csrf
                    @include('content.promotions._form')

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Save promotion</button>
                        <a href="{{ route('content.promotions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
