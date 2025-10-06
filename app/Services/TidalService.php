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
     * Search for an album on Tidal (returns first match)
     */
    public function searchAlbum(string $artist, string $album, string $accessToken): ?array
    {
        $albums = $this->searchAlbums($artist, $album, $accessToken);
        return $albums[0] ?? null;
    }

    /**
     * Search for albums on Tidal (returns all matches)
     */
    public function searchAlbums(string $artist, string $album, string $accessToken): array
    {
        $query = "{$artist} - {$album}";
        $encodedQuery = urlencode($query);

        $url = "{$this->apiUrl}/v2/searchResults/{$encodedQuery}";

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Accept' => 'application/vnd.api+json',
            ])
            ->get($url, [
                'countryCode' => 'US',
                'include' => 'albums,albums.coverArt',
            ]);

        if ($response->successful()) {
            $data = $response->json();

            \Log::info('Tidal search response', [
                'included_count' => count($data['included'] ?? []),
                'sample_included' => isset($data['included'][0]) ? $data['included'][0] : null,
            ]);

            // Build a map of coverArt by ID from included resources
            $coverArtMap = [];
            if (!empty($data['included'])) {
                foreach ($data['included'] as $item) {
                    if (isset($item['type']) && $item['type'] === 'images') {
                        $coverArtMap[$item['id']] = $item;
                        \Log::info('Found coverArt', ['id' => $item['id'], 'data' => $item]);
                    }
                }
            }

            // Tidal API returns albums in the 'included' array (JSON:API format)
            if (!empty($data['included'])) {
                $albums = [];
                foreach ($data['included'] as $item) {
                    if (isset($item['type']) && $item['type'] === 'albums') {
                        \Log::info('Processing album', [
                            'title' => $item['attributes']['title'] ?? 'unknown',
                            'relationships' => $item['relationships'] ?? [],
                        ]);

                        // Attach coverArt data if available
                        if (!empty($item['relationships']['coverArt']['data'][0]['id'])) {
                            $coverArtId = $item['relationships']['coverArt']['data'][0]['id'];
                            if (isset($coverArtMap[$coverArtId])) {
                                $item['coverArt'] = $coverArtMap[$coverArtId];
                                \Log::info('Attached coverArt to album', ['coverArtId' => $coverArtId]);
                            }
                        }
                        $albums[] = $item;
                    }
                }
                return $albums;
            }
        }

        return [];
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
            ->withHeaders([
                'Accept' => 'application/vnd.api+json',
            ])
            ->get("{$this->apiUrl}/v2/users/me");

        if ($response->successful()) {
            $data = $response->json();
            return $data['data']['id'] ?? null;
        }

        return null;
    }

    /**
     * Add album by ID to user's collection
     */
    public function addAlbumById(string $albumId, string $accessToken): array
    {
        // Get user ID
        $userId = $this->getUserId($accessToken);

        if (!$userId) {
            return [
                'success' => false,
                'message' => 'Could not retrieve user ID',
            ];
        }

        // Add to collection using v2 API
        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ])
            ->post("{$this->apiUrl}/v2/userCollections/{$userId}/relationships/albums", [
                'data' => [
                    [
                        'id' => $albumId,
                        'type' => 'albums',
                    ],
                ],
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => "Added to Tidal",
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to add album to collection: ' . $response->body(),
        ];
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

        $albumId = $tidalAlbum['id'];
        $albumTitle = $tidalAlbum['attributes']['title'] ?? 'Unknown Album';

        $result = $this->addAlbumById($albumId, $accessToken);

        if ($result['success']) {
            $result['message'] = "Added to Tidal: {$albumTitle}";
            $result['tidal_album'] = $tidalAlbum;
        }

        return $result;
    }
}
