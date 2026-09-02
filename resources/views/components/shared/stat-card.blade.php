{{--
    <x-shared.stat-card label="Bookings today" :value="$count" />
    <x-shared.stat-card label="Revenue" :value="$total" hint="Confirmed payments only" color="success" />

    For the dashboard tiles on the staff and admin screens. Wrap several in a
    <div class="row g-3"> with <div class="col-sm-6 col-lg-3"> around each.
--}}
@props(['label', 'value', 'hint' => null, 'color' => 'primary'])

<div {{ $attributes->merge(['class' => 'card h-100 border-0 shadow-sm']) }}>
    <div class="card-body">
        <p class="text-body-secondary text-uppercase small mb-1">{{ $label }}</p>
        <p class="h3 mb-0 text-{{ $color }}">{{ $value }}</p>
        @if ($hint)
            <p class="text-body-secondary small mb-0 mt-1">{{ $hint }}</p>
        @endif
    </div>
</div>
