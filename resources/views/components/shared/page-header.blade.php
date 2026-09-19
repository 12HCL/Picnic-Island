{{--
    <x-shared.page-header title="Ferry schedules" subtitle="All sailings for the next 14 days">
        <a href="{{ route('ferry.schedules.create') }}" class="btn btn-primary">New schedule</a>
    </x-shared.page-header>

    The slot is optional and renders on the right - use it for the primary action button.
--}}
@props(['title', 'subtitle' => null])

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 pi-section-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-body-secondary mb-0">{{ $subtitle }}</p>
        @endif
    </div>

    @if (! $slot->isEmpty())
        <div class="d-flex gap-2">{{ $slot }}</div>
    @endif
</div>
