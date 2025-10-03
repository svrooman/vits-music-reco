<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class SpotifyService
{
    protected $clientId;
    protected $clientSecret;
    protected $accessToken;
    protected $user;

    public function __construct()
    {
        $this->clientId = config('services.spotify.client_id');

        $this->clientSecret = config('services.spotify.client_secret');

        $this->accessToken = null;

        $this->user = null;

        if (session()->has('spotify_access_token')) {
            $this->accessToken = session('spotify_access_token');
        } elseif (request()->header('X-Spotify-Token')) {  // Use header instead of parameter
            $this->accessToken = request()->header('X-Spotify-Token');
        }

        if (session()->has('spotify_user_data')) {
            $this->user = session('spotify_user_data');
        } elseif (request()->header('X-Spotify-User')) {
            $this->user = json_decode(request()->header('X-Spotify-User'));
        }
    }

    private function refreshTokenIfNeeded()
    {
        if ($this->isTokenExpired() && session()->has('spotify_refresh_token')) {
            $this->refreshToken();
        }
    }

    private function refreshToken()
    {
        try {
            $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => session('spotify_refresh_token'),
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $tokenData = $response->json();

                // Update session with new token
                session([
                    'spotify_access_token' => $tokenData['access_token'],
                    'spotify_token_expires_at' => now()->addSeconds($tokenData['expires_in'])
                ]);

                // Update refresh token if provided (Spotify sometimes sends a new one)
                if (isset($tokenData['refresh_token'])) {
                    session(['spotify_refresh_token' => $tokenData['refresh_token']]);
                }

                logger()->info('Spotify token refreshed successfully');
            } else {
                logger()->error('Failed to refresh Spotify token', $response->json());
                // Clear invalid tokens
                session()->forget(['spotify_access_token', 'spotify_refresh_token', 'spotify_token_expires_at']);
            }
        } catch (\Exception $e) {
            logger()->error('Exception refreshing Spotify token: ' . $e->getMessage());
        }
    }

    // Rest of your existing methods stay the same...
    public function createPlaylist($playlistName, $isPublic = false)
    {
        $user = $this->user;

        if (!$user) {
            throw new Exception('User needs to authenticate with Spotify.');
        }

        $userId = $user->id ?? throw new Exception('User needs to authenticate with Spotify.');

        return $this->request('post', "/users/{$userId}/playlists", [
            'name' => $playlistName,
            'public' => $isPublic,
            'description' => 'Created with AI Playlist Generator',
        ]);
    }

    public function getTrackIds($artist, $album, $track)
    {
        // Only include album if it's meaningful
        if (!empty($album) && strlen(trim($album)) > 2) {
            $preciseQuery = sprintf(
                'artist:"%s" track:"%s" album:"%s"',
                addslashes($artist),
                addslashes($track),
                addslashes($album)
            );

            $result = $this->searchWithQuery($preciseQuery);
            if ($this->isGoodMatch($result, $artist, $track)) {
                return $result;
            }
        }

        // Strategy 2: Artist + track only
        $simpleQuery = sprintf(
            'artist:"%s" track:"%s"',
            addslashes($artist),
            addslashes($track)
        );

        $result = $this->searchWithQuery($simpleQuery);
        if ($this->isGoodMatch($result, $artist, $track)) {
            return $result;
        }

        // Strategy 3: Last resort
        $looseQuery = sprintf('"%s" "%s"', addslashes($artist), addslashes($track));
        return $this->searchWithQuery($looseQuery);
    }

    private function searchWithQuery($query)
    {
        return $this->request('get', '/search', [
            'q' => $query,
            'type' => 'track',
            'limit' => 5, // Get more results to choose from
        ]);
    }

    private function isGoodMatch($searchResult, $expectedArtist, $expectedTrack)
    {
        if (empty($searchResult['tracks']['items'])) {
            return false;
        }

        $firstResult = $searchResult['tracks']['items'][0];
        $foundArtist = $firstResult['artists'][0]['name'] ?? '';
        $foundTrack = $firstResult['name'] ?? '';

        // Check if artist name is similar (handles slight variations)
        $artistMatch = $this->stringSimilarity($foundArtist, $expectedArtist) > 0.8;
        $trackMatch = $this->stringSimilarity($foundTrack, $expectedTrack) > 0.7;

        return $artistMatch && $trackMatch;
    }

    private function stringSimilarity($str1, $str2)
    {
        // Simple similarity check
        similar_text(strtolower($str1), strtolower($str2), $percent);
        return $percent / 100;
    }

    private function request($method, $endPoint, $data = [])
    {
        // if (!session()->has('spotify_access_token')) {
        //     throw new Exception('User needs to authenticate with Spotify.');
        // }

        // $accessToken = session('spotify_access_token');

        $accessToken = $this->accessToken;

        $response = Http::withToken($accessToken)
            ->$method(
                config('services.spotify.api_url') . $endPoint,
                $data
            );

        if ($response->failed()) {
            $this->handleErrors($response);
        }

        return $response->json();
    }

    public function addTracksToPlaylist($playlistId, $trackUris)
    {
        return $this->request('post', "/playlists/{$playlistId}/tracks", [
            'uris' => $trackUris,
        ]);
    }

    public function getEmbedCode($playlistId)
    {
        return '<iframe src="https://open.spotify.com/embed/playlist/' . $playlistId . '" width="100%" height="600" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe>';
    }

    private function handleErrors($response)
    {
        $message = 'Failed to make request to Spotify API.';
        $statusCode = $response->status();
        $errorBody = $response->body();
        $errorHeaders = $response->headers();

        logger()->error('SpotifyService HTTP request failed: ', [
            'status' => $statusCode,
            'body' => $errorBody,
            'headers' => $errorHeaders,
        ]);

        throw new Exception($message);
    }
}
