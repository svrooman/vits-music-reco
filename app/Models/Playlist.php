<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'spotify_playlist_id',
        'spotify_playlist_uri',
        'tidal_playlist_id',
        'tidal_playlist_uri',
        'apple_playlist_id',
        'apple_playlist_uri',
        'yogitunes_playlist_id',
        'yogitunes_playlist_uri',
        'tracks',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'tracks' => 'array',
    ];
}
