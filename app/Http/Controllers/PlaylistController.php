<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

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

            // Fetch cover images from Spotify for each playlist
            $accessToken = session('spotify_access_token');
            if ($accessToken) {
                foreach ($playlists as $playlist) {
                    if ($playlist->spotify_playlist_id && !$playlist->cover_image_url) {
                        try {
                            $response = Http::withToken($accessToken)
                                ->get("https://api.spotify.com/v1/playlists/{$playlist->spotify_playlist_id}", [
                                    'fields' => 'images'
                                ])
                                ->json();

                            if (!empty($response['images'])) {
                                // Spotify returns images in descending size order, get the smallest (last)
                                $coverUrl = $response['images'][count($response['images']) - 1]['url'] ?? $response['images'][0]['url'];
                                $playlist->cover_image_url = $coverUrl;
                                $playlist->save();
                            }
                        } catch (\Exception $e) {
                            // Silently fail - playlist will just not have cover art
                            logger()->warning("Failed to fetch cover for playlist {$playlist->id}: " . $e->getMessage());
                        }
                    }
                }
            }
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
