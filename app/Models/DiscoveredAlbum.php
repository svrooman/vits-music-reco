<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscoveredAlbum extends Model
{
    protected $fillable = [
        'source',
        'artist',
        'album',
        'url',
        'image_url',
        'description',
        'discovered_at',
        'tidal_added',
        'tidal_added_at',
        'tags',
    ];

    protected $casts = [
        'discovered_at' => 'datetime',
        'tidal_added_at' => 'datetime',
        'tidal_added' => 'boolean',
        'tags' => 'array',
    ];
}
