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
    public $playlistId = null;
    public $generatedTracks = [];
    public $showPreview = false;

    protected $listeners = ['trackReplaced'];

    public function submit()
    {
        // Clear previous success message and playlist ID
        $this->playlistId = null;

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

            $this->generatedTracks = $result['tracks'];
            $this->showPreview = true;

        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function createPlaylist()
    {
        if (empty($this->generatedTracks)) {
            return;
        }

        try {
            // Create playlist on Spotify
            $spotifyService = new SpotifyService();
            $spotifyPlaylist = $spotifyService->createPlaylist($this->name, $this->isPublic);
            $playlistId = $spotifyPlaylist['id'];

            // Add validated tracks with Spotify URIs
            $trackUris = array_filter(array_map(function($track) {
                return $track['spotify_uri'] ?? null;
            }, $this->generatedTracks));

            if (!empty($trackUris)) {
                $spotifyService->addTracksToPlaylist($playlistId, $trackUris);
            }

            // Save to database (using authenticated user)
            \App\Models\Playlist::create([
                'user_id' => auth()->id(),
                'name' => $this->name,
                'description' => $this->description,
                'spotify_playlist_id' => $playlistId,
                'spotify_playlist_uri' => "spotify:playlist:{$playlistId}",
                'tracks' => collect($this->generatedTracks)->map(function ($track) {
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
            $this->reset(['name', 'description', 'numberOfTracks', 'isPublic', 'generatedTracks', 'showPreview']);

        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function trackReplaced($index, $newTrack)
    {
        if (isset($this->generatedTracks[$index])) {
            $this->generatedTracks[$index] = $newTrack;
            session()->flash('success', 'Track replaced successfully!');
        }
    }

    public function updateTrackOrder($orderedIndexes)
    {
        $newOrder = [];
        foreach ($orderedIndexes as $index) {
            if (isset($this->generatedTracks[$index])) {
                $newOrder[] = $this->generatedTracks[$index];
            }
        }
        $this->generatedTracks = $newOrder;
    }

    protected function msToMinutesSeconds($ms)
    {
        $seconds = floor($ms / 1000);
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function render()
    {
        return view('livewire.forms.playlist-generator');
    }
}
