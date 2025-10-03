<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlaylistGeneratorService;
use Illuminate\Http\Request;


class PlaylistController extends Controller
{
    protected $playlistService;

    public function __construct(PlaylistGeneratorService $playlistService)
    {
        $this->playlistService = $playlistService;
    }

    /**
     * Generate playlist from ANY inspiration
     * Works with: artist-song, genre, mood, era, general descriptions
     */
    public function generate(Request $request)
    {
        $request->validate([
            'inspiration' => 'required|string|max:1000',
            'number_of_tracks' => 'integer|min:5|max:50',
            'provider' => 'in:claude,openai',
            'max_duration_minutes' => 'integer|min:1|max:30',
            'banned_artists' => 'array',
            'banned_artists.*' => 'string',
            'additional_constraints' => 'nullable|string|max:500'
        ]);

        $options = [
            'provider' => $request->get('provider', 'claude'),
            'max_duration_minutes' => $request->get('max_duration_minutes', 12),
            'temperature' => $request->get('temperature', 0.7),
            'banned_artists' => $request->get('banned_artists', []),
            'additional_constraints' => $request->get('additional_constraints', ''),
            'validate_with_spotify' => $request->get('validate_with_spotify', true),
            'include_local' => $request->get('include_local', false),
            'prefer_local' => $request->get('prefer_local', false),
            'no_repeats' => $request->get('no_repeats', true),
        ];

        if ($options['include_local']) {
            $result = $this->playlistService->generatePlaylistWithLocal(
                $request->inspiration,
                $request->get('number_of_tracks', 25),
                $options
            );
        } else {
            $result = $this->playlistService->generatePlaylist(
                $request->inspiration,
                $request->get('number_of_tracks', 25),
                $options
            );
        }

        return response()->json($result);
    }

    /**
     * Generate playlist AND create it on Spotify
     */
    public function generateAndCreate(Request $request)
    {
        $request->validate([
            'inspiration' => 'required|string|max:1000',
            'playlist_name' => 'required|string|max:100',
            'number_of_tracks' => 'integer|min:5|max:50',
            'provider' => 'in:claude,openai',
            'max_duration_minutes' => 'integer|min:1|max:30',
            'banned_artists' => 'array',
            'banned_artists.*' => 'string',
            'additional_constraints' => 'nullable|string|max:500',
            'public' => 'boolean',
            'spotify_access_token' => 'string',
        ]);

        if (request()->header('X-Spotify-Token')) {
            session(['spotify_access_token' => request()->header('X-Spotify-Token')]);
        }

        // Generate the playlist
        $options = [
            'provider' => $request->get('provider', 'claude'),
            'max_duration_minutes' => $request->get('max_duration_minutes', 12),
            'temperature' => $request->get('temperature', 0.7),
            'banned_artists' => $request->get('banned_artists', []),
            'additional_constraints' => $request->get('additional_constraints', ''),
            'validate_with_spotify' => true // Always validate if creating Spotify playlist
        ];

        $generationResult = $this->playlistService->generatePlaylist(
            $request->inspiration,
            $request->get('number_of_tracks', 25),
            $options
        );

        if (!$generationResult['success']) {
            return response()->json($generationResult);
        }

        // Create the Spotify playlist
        $spotifyResult = $this->playlistService->createSpotifyPlaylist(
            $generationResult['tracks'],
            $request->playlist_name,
            $request->get('public', false)
        );

        return response()->json([
            'generation' => $generationResult,
            'spotify' => $spotifyResult
        ]);
    }

    public function ragRecommendations(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500'
        ]);

        $query = $request->query('query', '');

        $options = [];

        $result = $this->playlistService->ragRecommendations(
            $query,
            $options
        );

        return response()->json($result);
    }
}
