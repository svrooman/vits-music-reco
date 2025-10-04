<?php

namespace App\Livewire\Forms;

use Livewire\Component;
use App\Services\SpotifyService;
use App\Services\PlaylistGeneratorService;

class PlaylistGenerator extends Component
{
    public $name = '';
    public $numberOfTracks = 25;
    public $isPublic = false;
    public $description = '';
    public $isLoading = false;
    public $playlistId = null;

    public function submit()
    {
        // Validate
        $this->validate([
            'name' => 'required|string|max:255',
            'numberOfTracks' => 'required|integer|min:1|max:50',
            'description' => 'required|string|max:1000',
        ]);

        // Check for Spotify authentication
        if (!session()->has('spotify_access_token')) {
            session()->flash('error', 'Please authenticate with Spotify first');
            return redirect()->route('spotify.auth');
        }

        $this->isLoading = true;

        try {
            // Initialize services
            $playlistGeneratorService = new PlaylistGeneratorService(new SpotifyService());

            // Generate playlist with AI and create on Spotify
            $result = $playlistGeneratorService->generatePlaylist(
                $this->description,
                $this->numberOfTracks,
                ['validate_with_spotify' => true]
            );

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to generate playlist');
            }

            $tracks = $result['tracks'];

            // Create playlist on Spotify
            $spotifyService = new SpotifyService();
            $spotifyPlaylist = $spotifyService->createPlaylist($this->name, $this->isPublic);
            $playlistId = $spotifyPlaylist['id'];

            // Add validated tracks with Spotify URIs
            $trackUris = array_filter(array_map(function($track) {
                return $track['spotify_uri'] ?? null;
            }, $tracks));

            if (!empty($trackUris)) {
                $spotifyService->addTracksToPlaylist($playlistId, $trackUris);
            }

            // Get user ID
            $userId = null;
            if (session()->has('spotify_user_data')) {
                $spotifyUserData = session('spotify_user_data');
                $userId = is_object($spotifyUserData)
                    ? ($spotifyUserData->id ?? null)
                    : (is_array($spotifyUserData) ? ($spotifyUserData['id'] ?? null) : null);
            }

            if (!$userId) {
                $userId = \Illuminate\Support\Str::uuid()->toString();
            }

            // Save to database
            \App\Models\Playlist::create([
                'user_id' => $userId,
                'name' => $this->name,
                'description' => $this->description,
                'spotify_playlist_id' => $playlistId,
                'spotify_playlist_uri' => "spotify:playlist:{$playlistId}",
                'tracks' => collect($tracks)->map(function ($track) {
                    return [
                        'artist' => $track['artist'],
                        'track' => $track['track'],
                        'album' => $track['album'] ?? '',
                    ];
                })->toArray(),
            ]);

            $this->playlistId = $playlistId;
            session()->flash('success', "Playlist '{$this->name}' created successfully with " . count($trackUris) . " tracks!");

            // Reset form
            $this->reset(['name', 'description', 'numberOfTracks', 'isPublic']);

        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('livewire.forms.playlist-generator');
    }
}
