<?php

namespace App\Livewire\Modals;

use LivewireUI\Modal\ModalComponent;
use App\Services\SpotifyService;
use App\Services\PlaylistGeneratorService;

class ReplaceTrack extends ModalComponent
{
    public $track;
    public $trackIndex;
    public $replacementSuggestion = '';
    public $description = '';

    public function mount($track, $trackIndex, $description)
    {
        $this->track = $track;
        $this->trackIndex = $trackIndex;
        $this->description = $description;
    }

    public function replaceTrack()
    {
        try {
            $playlistGeneratorService = new PlaylistGeneratorService(new SpotifyService());

            if (!empty($this->replacementSuggestion)) {
                // Manual replacement
                if (strpos($this->replacementSuggestion, ' - ') !== false) {
                    [$artist, $trackName] = explode(' - ', $this->replacementSuggestion, 2);
                    $searchQuery = trim($artist) . ' ' . trim($trackName);
                } else {
                    $searchQuery = $this->replacementSuggestion;
                }

                $spotifyService = new SpotifyService();
                $searchResults = $spotifyService->getTrackIds('', '', $searchQuery);

                if (!empty($searchResults['tracks']['items'])) {
                    $spotifyTrack = $searchResults['tracks']['items'][0];
                    $newTrack = [
                        'artist' => $spotifyTrack['artists'][0]['name'],
                        'track' => $spotifyTrack['name'],
                        'album' => $spotifyTrack['album']['name'],
                        'year' => isset($spotifyTrack['album']['release_date']) ? substr($spotifyTrack['album']['release_date'], 0, 4) : null,
                        'spotify_id' => $spotifyTrack['id'],
                        'spotify_url' => $spotifyTrack['external_urls']['spotify'],
                        'actual_duration' => $this->msToMinutesSeconds($spotifyTrack['duration_ms']),
                        'spotify_uri' => $spotifyTrack['uri'],
                        'album_image' => $spotifyTrack['album']['images'][0]['url'] ?? null,
                    ];
                } else {
                    session()->flash('error', 'Could not find the suggested track');
                    return;
                }
            } else {
                // AI automatic replacement
                $inspiration = "A track similar to {$this->track['track']} by {$this->track['artist']} that would fit in a playlist about: {$this->description}";

                $result = $playlistGeneratorService->generatePlaylist($inspiration, 1, [
                    'validate_with_spotify' => true
                ]);

                if ($result['success'] && !empty($result['tracks'])) {
                    $newTrack = $result['tracks'][0];
                } else {
                    session()->flash('error', 'Could not find a replacement track');
                    return;
                }
            }

            // Emit event to parent component to update the track
            $this->dispatch('trackReplaced', $this->trackIndex, $newTrack);
            $this->closeModal();

        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
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
        return view('livewire.modals.replace-track');
    }
}
