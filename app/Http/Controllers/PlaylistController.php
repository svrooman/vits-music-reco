<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistController extends Controller
{
    /**
     * Display the playlist creation form and previously created playlists
     */
    public function index()
    {
        // Check if user is authenticated
        if (Auth::check() || session()->has('spotify_user_data')) {
            // Get user ID from either Auth or Spotify session
            $userId = Auth::id() ?? session('spotify_user_data')->id;

            // Get user's playlists
            $playlists = Playlist::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } else {
            $playlists = collect();
        }

        return view('playlist.index', [
            'playlists' => $playlists,
            'selectedService' => session('selected_service')
        ]);
    }

    /**
     * Show a specific playlist
     */
    public function show($id)
    {
        $playlist = Playlist::findOrFail($id);

        // Check if user is authorized to view this playlist
        if (Auth::id() !== $playlist->user_id && !session()->has('spotify_user_data')) {
            abort(403, 'Unauthorized');
        }

        return view('playlist.show', [
            'playlist' => $playlist
        ]);
    }
}
