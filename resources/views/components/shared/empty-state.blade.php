{{--
    <x-shared.empty-state message="No sailings scheduled for this date.">
        <a href="{{ route('ferry.schedules.create') }}" class="btn btn-sm btn-primary">Add one</a>
    </x-shared.empty-state>

    Use this inside @forelse ... @empty, never a bare "No results" string. An empty table
    with no explanation reads as a broken page in the screenshots appendix.
--}}
@props(['message' => 'Nothing to show yet.'])

<div class="text-center text-body-secondary py-5">
    <p class="mb-2">{{ $message }}</p>

    @if (! $slot->isEmpty())
        <div>{{ $slot }}</div>
    @endif
</div>
