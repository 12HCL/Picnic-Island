{{--
    <x-shared.status-badge :status="$booking->status" />

    One colour scheme for every status in the system. BUILD_CONTRACT.md §5 asks for this
    to be agreed early, because five people inventing five schemes for the same states
    looks careless in the screenshots appendix.

    Every value below comes from an ENUM in 04_Design/MASTER_SCHEMA.md. If the schema gains
    a status, add it here in the same session - an unlisted status falls back to grey.
--}}
@props(['status'])

@php
    $map = [
        // in force / good
        'available'      => 'success',
        'active'         => 'success',
        'confirmed'      => 'success',
        'issued'         => 'success',
        'valid'          => 'success',
        'paid'           => 'success',

        // future or informational
        'scheduled'      => 'info',
        'refunded'       => 'info',

        // needs attention
        'pending'        => 'warning',
        'maintenance'    => 'warning',

        // finished, no action left
        'checked_in'     => 'secondary',
        'completed'      => 'secondary',
        'departed'       => 'secondary',
        'boarded'        => 'secondary',
        'used'           => 'secondary',
        'retired'        => 'secondary',

        // stopped / failed
        'cancelled'      => 'danger',
        'failed'         => 'danger',
        'out_of_service' => 'danger',
    ];

    $key   = strtolower((string) $status);
    $color = $map[$key] ?? 'secondary';
@endphp

<span {{ $attributes->merge(['class' => "badge text-bg-$color"]) }}>
    {{ ucfirst(str_replace('_', ' ', $key)) }}
</span>
