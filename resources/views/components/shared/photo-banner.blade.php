{{--
    <x-shared.photo-banner src="img/photos/ferry-jetty.jpg" alt="The jetty at sunset" />
    <x-shared.photo-banner src="img/photos/palm-reef-room.jpg" alt="A guest room" height="14rem" />

    A full-width photo, cropped to a fixed height so pages line up whatever the photo's shape.
    src is relative to public/.
--}}
@props(['src', 'alt', 'height' => '16rem'])

<img src="{{ asset($src) }}" alt="{{ $alt }}"
     {{ $attributes->merge(['class' => 'w-100 rounded shadow-sm mb-4']) }}
     style="height: {{ $height }}; object-fit: cover;">
