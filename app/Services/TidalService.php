<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TidalService
{
    protected string $apiUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;

    public function __construct()
    {
        $this->apiUrl = config('services.tidal.api_url');
        $this->clientId = config('services.tidal.client_id');
        $this->clientSecret = config('services.tidal.client_secret');
        $this->redirectUri = config('services.tidal.redirect_uri');
    }

    /**
     * Get authorization URL for OAuth flow
     */
    public function getAuthUrl(): string
    {
        $scopes = [
            'user.read',
            'collection.read',
            'collection.write',
            'search.read',
            'playlists.read',
            'playlists.write',
            'entitlements.read',
            'playback',
            'recommendations.read',
            'search.write',
        ];

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
        ]);

        return "https://login.tidal.com/authorize?{$params}";
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $code): ?array
    {
        $response = Http::asForm()->post('https://auth.tidal.com/v1/oauth2/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        $response = Http::asForm()->post('https://auth.tidal.com/v1/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Search for an album on Tidal
     */
    public function searchAlbum(string $artist, string $album, string $accessToken): ?array
    {
        $query = "{$artist} {$album}";

        $response = Http::withToken($accessToken)
            ->get("{$this->apiUrl}/search", [
                'query' => $query,
                'type' => 'ALBUMS',
                'limit' => 5,
                'countryCode' => 'US',
            ]);

        if ($response->successful()) {
            $data = $response->json();

            // Return the best match if found
            if (!empty($data['albums']['items'])) {
                return $data['albums']['items'][0];
            }
        }

        return null;
    }

    /**
     * Add album to user's favorites
     */
    public function addAlbumToFavorites(string $albumId, string $accessToken): bool
    {
        $response = Http::withToken($accessToken)
            ->put("{$this->apiUrl}/users/{userId}/favorites/albums/{$albumId}", [
                'countryCode' => 'US',
            ]);

        return $response->successful();
    }

    /**
     * Get user ID from access token
     */
    public function getUserId(string $accessToken): ?string
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->apiUrl}/users/me");

        if ($response->successful()) {
            $data = $response->json();
            return $data['id'] ?? null;
        }

        return null;
    }

    /**
     * Search and add album to favorites (convenience method)
     */
    public function searchAndAddAlbum(string $artist, string $album, string $accessToken): array
    {
        // Search for album
        $tidalAlbum = $this->searchAlbum($artist, $album, $accessToken);

        if (!$tidalAlbum) {
            return [
                'success' => false,
                'message' => 'Album not found on Tidal',
            ];
        }

        // Get user ID
        $userId = $this->getUserId($accessToken);

        if (!$userId) {
            return [
                'success' => false,
                'message' => 'Could not retrieve user ID',
            ];
        }

        // Add to favorites
        $response = Http::withToken($accessToken)
            ->put("{$this->apiUrl}/users/{$userId}/favorites/albums", [
                'albumIds' => [$tidalAlbum['id']],
                'countryCode' => 'US',
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => "Added to Tidal: {$tidalAlbum['title']} by {$tidalAlbum['artist']['name']}",
                'tidal_album' => $tidalAlbum,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to add album to favorites',
        ];
    }
}
