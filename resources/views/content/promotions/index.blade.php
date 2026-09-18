@extends('layouts.app')
@section('title', 'Promotions')
@section('content')

<x-shared.page-header
    title="Promotions"
    subtitle="Promotional banners and adverts across the modules. UC-18.">
    <a href="{{ route('content.promotions.create') }}" class="btn btn-primary btn-sm">+ Add promotion</a>
</x-shared.page-header>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Module</th>
                    <th>Runs</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($promotions as $promotion)
                    <tr>
                        <td>
                            <a href="{{ route('content.promotions.show', $promotion) }}"
                               class="text-decoration-none fw-semibold">
                                {{ $promotion->title }}
                            </a>
                        </td>
                        <td class="text-capitalize">{{ $promotion->module }}</td>
                        <td>
                            {{ $promotion->starts_on->format('j M Y') }}
                            &ndash;
                            {{ $promotion->ends_on->format('j M Y') }}
                        </td>
                        <td>
                            {{-- Published is the editor's intent; whether it is on screen
                                 today also depends on the date range. --}}
                            @if (! $promotion->is_published)
                                <span class="badge text-bg-secondary">Draft</span>
                            @elseif ($promotion->ends_on->isPast())
                                <span class="badge text-bg-secondary">Finished</span>
                            @elseif ($promotion->starts_on->isFuture())
                                <span class="badge text-bg-info">Scheduled</span>
                            @else
                                <span class="badge text-bg-success">Live</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('content.promotions.edit', $promotion) }}"
                                   class="btn btn-outline-secondary">Edit</a>
                                <form method="POST"
                                      action="{{ route('content.promotions.destroy', $promotion) }}"
                                      onsubmit="return confirm('Delete {{ $promotion->title }}? Unpublishing it is usually safer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-shared.empty-state message="No promotions have been created yet.">
                                <a href="{{ route('content.promotions.create') }}" class="btn btn-sm btn-primary">
                                    Add the first one
                                </a>
                            </x-shared.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $promotions->links() }}</div>

@endsection
