<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlbumRecommendationService
{
    protected $openaiKey;
    protected $claudeKey;
    protected $spotifyService;

    public function __construct(SpotifyService $spotifyService = null)
    {
        $this->openaiKey = config('services.openai.api_key');
        $this->claudeKey = config('services.claude.api_key');
        $this->spotifyService = $spotifyService;
    }

    /**
     * Generate album/artist recommendations from any prompt
     */
    public function getRecommendations($prompt, $count = 10, $options = [])
    {
        $options = array_merge([
            'provider' => 'claude',
            'type' => 'albums', // 'albums', 'artists', or 'mixed'
            'include_metadata' => true,
            'fetch_album_art' => true,
        ], $options);

        try {
            // Generate recommendations with AI
            if ($options['provider'] === 'claude' && $this->claudeKey) {
                $recommendations = $this->generateWithClaude($prompt, $count, $options);
            } else {
                $recommendations = $this->generateWithOpenAI($prompt, $count, $options);
            }

            // Enrich with Spotify data (album art, availability, etc.)
            if ($options['fetch_album_art']) {
                Log::info("Attempting to enrich with Spotify data. SpotifyService available: " . ($this->spotifyService ? 'yes' : 'no'));
                $recommendations = $this->enrichWithSpotifyData($recommendations, $options);
            }

            return [
                'success' => true,
                'recommendations' => $recommendations,
                'metadata' => [
                    'prompt' => $prompt,
                    'count' => count($recommendations),
                    'type' => $options['type'],
                    'generated_at' => now()
                ]
            ];
        } catch (Exception $e) {
            Log::error('Album recommendation failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'recommendations' => []
            ];
        }
    }

    /**
     * Generate recommendations using Claude
     */
    protected function generateWithClaude($prompt, $count, $options)
    {
        $systemPrompt = $this->buildPrompt($prompt, $count, $options);

        $response = Http::withHeaders([
            'x-api-key' => $this->claudeKey,
            'content-type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 3000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $systemPrompt
                ]
            ]
        ])->throw()->json();

        $content = $response['content'][0]['text'];
        return $this->parseJsonResponse($content);
    }

    /**
     * Generate recommendations using OpenAI
     */
    protected function generateWithOpenAI($prompt, $count, $options)
    {
        $systemPrompt = $this->buildPrompt($prompt, $count, $options);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiKey,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini-2024-07-18',
            'temperature' => 0.8,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert music curator with encyclopedic knowledge of artists and albums across all genres and eras.'
                ],
                [
                    'role' => 'user',
                    'content' => $systemPrompt
                ]
            ],
        ])->throw()->json();

        $content = $response['choices'][0]['message']['content'];
        return $this->parseJsonResponse($content);
    }

    /**
     * Build the AI prompt based on recommendation type
     */
    protected function buildPrompt($prompt, $count, $options)
    {
        $type = $options['type'];

        if ($type === 'albums') {
            return "Based on this request: \"{$prompt}\"

Suggest exactly {$count} albums that match this description. These should be FULL ALBUMS, not singles or EPs.

For each album, provide:
- Artist name
- Album title
- Release year
- Genre/style
- Brief reason why it fits (1 sentence)

OUTPUT FORMAT (JSON only, no other text):
[
  {
    \"artist\": \"Artist Name\",
    \"album\": \"Album Title\",
    \"year\": 2020,
    \"genre\": \"Genre/Style\",
    \"reason\": \"Why this album fits the request\"
  }
]";
        } elseif ($type === 'artists') {
            return "Based on this request: \"{$prompt}\"

Suggest exactly {$count} artists that match this description.

For each artist, provide:
- Artist name
- Genre/style
- A representative/essential album
- Brief reason why they fit (1 sentence)

OUTPUT FORMAT (JSON only, no other text):
[
  {
    \"artist\": \"Artist Name\",
    \"genre\": \"Genre/Style\",
    \"essential_album\": \"Album Title\",
    \"year\": 2020,
    \"reason\": \"Why this artist fits the request\"
  }
]";
        } else { // mixed
            return "Based on this request: \"{$prompt}\"

Suggest exactly {$count} music recommendations (mix of albums and artists).

OUTPUT FORMAT (JSON only, no other text):
[
  {
    \"type\": \"album\",
    \"artist\": \"Artist Name\",
    \"album\": \"Album Title\",
    \"year\": 2020,
    \"genre\": \"Genre/Style\",
    \"reason\": \"Why this fits\"
  }
]";
        }
    }

    /**
     * Parse JSON from AI response
     */
    protected function parseJsonResponse($content)
    {
        // Find JSON array in response
        $startPos = strpos($content, '[');
        $endPos = strrpos($content, ']');

        if ($startPos === false || $endPos === false) {
            throw new Exception('No JSON array found in AI response');
        }

        $jsonData = substr($content, $startPos, $endPos - $startPos + 1);
        $recommendations = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in AI response: ' . json_last_error_msg());
        }

        return $recommendations;
    }

    /**
     * Enrich recommendations with Spotify data (album art, links, availability)
     */
    protected function enrichWithSpotifyData($recommendations, $options)
    {
        if (!$this->spotifyService) {
            Log::warning("SpotifyService is null, skipping enrichment but will still try to fetch data");
            // Don't return early - we can still use the session token
        }

        $enriched = [];

        foreach ($recommendations as $rec) {
            try {
                $artist = $rec['artist'];
                $album = $rec['album'] ?? $rec['essential_album'] ?? null;

                if ($album) {
                    $accessToken = session('spotify_access_token');

                    if (!$accessToken) {
                        Log::warning("No Spotify access token available for album search");
                        $rec['spotify_data'] = ['available' => false];
                        $enriched[] = $rec;
                        continue;
                    }

                    // Try exact search first
                    $query = sprintf('artist:"%s" album:"%s"', $artist, $album);

                    Log::info("Searching Spotify for: " . $query);

                    $searchResult = Http::withToken($accessToken)
                        ->get(config('services.spotify.api_url') . '/search', [
                            'q' => $query,
                            'type' => 'album',
                            'limit' => 1
                        ])->json();

                    Log::info("Spotify search result:", ['items_count' => count($searchResult['albums']['items'] ?? [])]);

                    // If no exact match, try broader search
                    if (empty($searchResult['albums']['items'])) {
                        $query = sprintf('%s %s', $artist, $album);
                        Log::info("Trying broader search: " . $query);

                        $searchResult = Http::withToken($accessToken)
                            ->get(config('services.spotify.api_url') . '/search', [
                                'q' => $query,
                                'type' => 'album',
                                'limit' => 1
                            ])->json();

                        Log::info("Broader search result:", ['items_count' => count($searchResult['albums']['items'] ?? []), 'error' => $searchResult['error'] ?? null]);
                    }

                    if (!empty($searchResult['albums']['items'])) {
                        $spotifyAlbum = $searchResult['albums']['items'][0];

                        $rec['spotify_data'] = [
                            'id' => $spotifyAlbum['id'],
                            'url' => $spotifyAlbum['external_urls']['spotify'],
                            'image' => $spotifyAlbum['images'][0]['url'] ?? null,
                            'available' => true,
                            'total_tracks' => $spotifyAlbum['total_tracks'] ?? null,
                        ];
                    } else {
                        $rec['spotify_data'] = ['available' => false];
                    }
                } else {
                    // Artist only - get artist info
                    $query = sprintf('artist:"%s"', addslashes($artist));

                    $searchResult = Http::withToken($this->spotifyService->accessToken ?? session('spotify_access_token'))
                        ->get(config('services.spotify.api_url') . '/search', [
                            'q' => $query,
                            'type' => 'artist',
                            'limit' => 1
                        ])->json();

                    if (!empty($searchResult['artists']['items'])) {
                        $spotifyArtist = $searchResult['artists']['items'][0];

                        $rec['spotify_data'] = [
                            'id' => $spotifyArtist['id'],
                            'url' => $spotifyArtist['external_urls']['spotify'],
                            'image' => $spotifyArtist['images'][0]['url'] ?? null,
                            'available' => true,
                        ];
                    } else {
                        $rec['spotify_data'] = ['available' => false];
                    }
                }
            } catch (Exception $e) {
                Log::warning("Error enriching recommendation: " . $e->getMessage());
                $rec['spotify_data'] = ['available' => false];
            }

            $enriched[] = $rec;
        }

        return $enriched;
    }

    /**
     * Add selected albums/artists to Spotify library
     */
    public function addToSpotifyLibrary($recommendations, $type = 'albums')
    {
        $accessToken = session('spotify_access_token');

        if (!$accessToken) {
            throw new Exception('Not authenticated with Spotify');
        }

        $added = [];
        $failed = [];

        foreach ($recommendations as $rec) {
            try {
                if ($type === 'albums' && isset($rec['spotify_data']['id'])) {
                    // Save album to library
                    $albumId = $rec['spotify_data']['id'];

                    Http::withToken($accessToken)
                        ->put(config('services.spotify.api_url') . '/me/albums', [
                            'ids' => [$albumId]
                        ])->throw();

                    $added[] = $rec;
                }
            } catch (Exception $e) {
                Log::error("Failed to add to library: " . $e->getMessage());
                $failed[] = $rec;
            }
        }

        return [
            'success' => count($added) > 0,
            'added' => $added,
            'failed' => $failed,
            'count' => count($added)
        ];
    }
}
