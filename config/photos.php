<?php

/*
|--------------------------------------------------------------------------
| Hotel photos
|--------------------------------------------------------------------------
| Keyed by hotel name, paths relative to public/. Kept here rather than in a hotels.image
| column because the master schema is locked; a hotel with no entry simply shows no photo.
*/

return [

    'hotels' => [
        'Palm Reef Hotel' => 'img/photos/palm-reef-hotel.webp',
        'Azure Sands Resort' => 'img/photos/azure-sands-resort.webp',
    ],

    // Shown above the room types on a hotel's page, so each hotel's rooms look like its own.
    'rooms' => [
        'Palm Reef Hotel' => 'img/photos/palm-reef-room.webp',
        'Azure Sands Resort' => 'img/photos/azure-sands-room.webp',
    ],

];
