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
     * Get authorization URL for OAuth flow with PKCE
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

        // Generate PKCE code verifier and challenge
        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);

        // Store code verifier in session for callback
        session(['tidal_code_verifier' => $codeVerifier]);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return "https://login.tidal.com/authorize?{$params}";
    }

    /**
     * Generate PKCE code verifier
     */
    private function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * Generate PKCE code challenge from verifier
     */
    private function generateCodeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    /**
     * Exchange authorization code for access token with PKCE
     */
    public function getAccessToken(string $code): ?array
    {
        $codeVerifier = session('tidal_code_verifier');

        if (!$codeVerifier) {
            \Log::error('Tidal: No code verifier found in session');
            return null;
        }

        $response = Http::asForm()->post('https://auth.tidal.com/v1/oauth2/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code_verifier' => $codeVerifier,
        ]);

        // Clear the code verifier from session
        session()->forget('tidal_code_verifier');

        if ($response->successful()) {
            return $response->json();
        }

        \Log::error('Tidal token exchange failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

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

        \Log::info('Tidal: Searching for album', [
            'query' => $query,
            'url' => "{$this->apiUrl}/v2/searchresults/top-hits",
        ]);

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Accept' => 'application/vnd.tidal.v1+json',
            ])
            ->get("{$this->apiUrl}/v2/searchresults/top-hits", [
                'query' => $query,
                'countryCode' => 'US',
            ]);

        \Log::info('Tidal: Search response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->body(),
        ]);

        if ($response->successful()) {
            $data = $response->json();

            // Check for albums in response
            if (!empty($data['albums'])) {
                \Log::info('Tidal: Found albums', ['count' => count($data['albums'])]);
                return $data['albums'][0];
            }

            if (!empty($data['data'])) {
                foreach ($data['data'] as $item) {
                    if ($item['type'] === 'album' || isset($item['resource']['album'])) {
                        \Log::info('Tidal: Found album in data');
                        return $item['resource'] ?? $item;
                    }
                }
            }

            \Log::warning('Tidal: No albums found in response', ['keys' => array_keys($data)]);
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
