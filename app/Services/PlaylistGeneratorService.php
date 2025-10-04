<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class PlaylistGeneratorService
{
    protected $openaiKey;
    protected $claudeKey;
    protected $spotifyService;
    protected $localLibraryService;
    protected $localLibraryRagService;

    public function __construct(
        SpotifyService $spotifyService = null,
        LocalLibraryService $localLibraryService = null,
        LocalLibraryRagService $localLibraryRagService = null,
    ) {
        $this->openaiKey = config('services.openai.api_key');
        $this->claudeKey = config('services.claude.api_key');

        $this->localLibraryService = $localLibraryService;
        $this->spotifyService = $spotifyService;
        $this->localLibraryRagService = $localLibraryRagService;
    }

    public function generatePlaylistWithLocal($inspiration, $numberOfTracks = 25, $options = [])
    {
        // Check if we should search local library based on inspiration
        $searchLocal = $options['include_local'] ?? true;
        $preferLocal = $options['prefer_local'] ?? true;

        $this->localLibraryService->isAvailable() && $searchLocal ?? throw new Exception('Local library service is not available');

        // Get AI recommendations
        if ($options['provider'] === 'claude' && $this->claudeKey) {
            $aiTracks = $this->generateWithClaude($inspiration, $numberOfTracks, $options);
        } else {
            $aiTracks = $this->generateWithOpenAI($inspiration, $numberOfTracks, $options);
        }

        $finalPlaylist = [];
        $stats = [
            'local_found' => 0,
            'spotify_found' => 0,
            'not_found' => 0
        ];

        foreach ($aiTracks as $aiTrack) {
            if (count($finalPlaylist) >= $numberOfTracks) {
                break;
            }

            $trackAdded = false;

            // Try local library first (if enabled)
            if ($searchLocal) {
                $localTrack = $this->localLibraryService->findTrack(
                    $aiTrack['artist'],
                    $aiTrack['track']
                );

                if ($localTrack && $localTrack['similarity_score'] > 0.75) {
                    $finalPlaylist[] = [
                        'source' => 'local',
                        'data' => $localTrack,
                        'ai_recommendation' => $aiTrack
                    ];
                    $stats['local_found']++;
                    $trackAdded = true;

                    logger()->info('Found in local library', [
                        'track' => $aiTrack['track'],
                        'artist' => $aiTrack['artist'],
                        'similarity' => $localTrack['similarity_score']
                    ]);
                }
            }

            // Try Spotify if not found locally (or if local not preferred)
            if (!$trackAdded && (!$preferLocal || !$searchLocal)) {
                $spotifyResults = $this->spotifyService->getTrackIds(
                    $aiTrack['artist'],
                    $aiTrack['album'] ?? '',
                    $aiTrack['track']
                );

                if (!empty($spotifyResults['tracks']['items'])) {
                    $spotifyTrack = $spotifyResults['tracks']['items'][0];
                    $finalPlaylist[] = [
                        'source' => 'spotify',
                        'data' => [
                            'id' => $spotifyTrack['id'],
                            'title' => $spotifyTrack['name'],
                            'artist' => $spotifyTrack['artists'][0]['name'],
                            'album' => $spotifyTrack['album']['name'],
                            'uri' => $spotifyTrack['uri'],
                            'preview_url' => $spotifyTrack['preview_url']
                        ],
                        'ai_recommendation' => $aiTrack
                    ];
                    $stats['spotify_found']++;
                    $trackAdded = true;
                }
            }

            if (!$trackAdded) {
                $stats['not_found']++;
                logger()->warning('Track not found in any source', $aiTrack);
            }
        }

        // If we didn't get enough tracks, try semantic search on local library
        if ($searchLocal && count($finalPlaylist) < $numberOfTracks) {
            $needed = $numberOfTracks - count($finalPlaylist);
            $semanticResults = $this->localLibraryService->searchSemantic(
                $inspiration,
                $needed
            );

            foreach ($semanticResults as $track) {
                $finalPlaylist[] = [
                    'source' => 'local',
                    'data' => $track,
                    'ai_recommendation' => null,
                    'discovery_method' => 'semantic_search'
                ];
            }
        }

        return [
            'success' => true,
            'playlist' => $finalPlaylist,
            'metadata' => [
                'inspiration' => $inspiration,
                'stats' => $stats,
                'local_library_available' => $this->localLibraryService->isAvailable()
            ]
        ];
    }

    /**
     * Generate playlist from ANY inspiration (artist-song, genre, mood, etc.)
     */
    public function generatePlaylist($inspiration, $numberOfTracks = 25, $options = [])
    {
        // Flexible default options that work for any inspiration
        $options = array_merge([
            'max_duration_minutes' => 12,
            'no_repeats' => true,
            'provider' => config('services.ai.provider', 'claude'),
            'temperature' => 0.7,
            'validate_with_spotify' => true,
            'banned_artists' => [], // User can add their own (like Bob Dylan for electronic playlists!)
            'additional_constraints' => '' // Extra instructions
        ], $options);

        try {
            // Generate with specified AI provider
            if ($options['provider'] === 'claude' && $this->claudeKey) {
                $tracks = $this->generateWithClaude($inspiration, $numberOfTracks, $options);
            } else {
                $tracks = $this->generateWithOpenAI($inspiration, $numberOfTracks, $options);
            }

            // Validate and filter tracks if Spotify service available
            if ($this->spotifyService && $options['validate_with_spotify']) {
                $tracks = $this->validateTracksWithSpotify($tracks, $options);
            }

            return [
                'success' => true,
                'tracks' => $tracks,
                'metadata' => [
                    'inspiration' => $inspiration,
                    'count' => count($tracks),
                    'options' => $options,
                    'generated_at' => now()
                ]
            ];
        } catch (Exception $e) {
            Log::error('Playlist generation failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tracks' => []
            ];
        }
    }

    /**
     * Generate playlist using Claude (flexible for any inspiration)
     */
    protected function generateWithClaude($inspiration, $numberOfTracks, $options)
    {
        $prompt = $this->buildFlexiblePrompt($inspiration, $numberOfTracks, $options, 'claude');

        $response = Http::withHeaders([
            'x-api-key' => $this->claudeKey,
            'content-type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 2000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ])->throw()->json();

        $content = $response['content'][0]['text'];
        return $this->parseJsonResponse($content);
    }

    /**
     * Generate playlist using OpenAI (flexible for any inspiration)
     */
    protected function generateWithOpenAI($inspiration, $numberOfTracks, $options)
    {
        $prompt = $this->buildFlexiblePrompt($inspiration, $numberOfTracks, $options, 'openai');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiKey,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini-2024-07-18',
            'temperature' => $options['temperature'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert music curator with deep knowledge across all genres and eras. You create perfectly curated playlists that capture the essence of any musical inspiration.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
        ])->throw()->json();

        $content = $response['choices'][0]['message']['content'];
        return $this->parseJsonResponse($content);
    }

    /**
     * Build flexible prompt that adapts to any inspiration type
     */
    protected function buildFlexiblePrompt($inspiration, $numberOfTracks, $options, $provider)
    {
        $constraints = [];

        // Basic constraints that always apply
        $constraints[] = "Exactly {$numberOfTracks} tracks";
        $constraints[] = "Track length under {$options['max_duration_minutes']} minutes each";

        if ($options['no_repeats']) {
            $constraints[] = "No repeated artists (each artist appears only once)";
        }

        if (!empty($options['banned_artists'])) {
            $bannedList = implode(', ', $options['banned_artists']);
            $constraints[] = "NEVER include these artists: {$bannedList}";
        }

        if (!empty($options['additional_constraints'])) {
            $constraints[] = $options['additional_constraints'];
        }

        $constraintsList = "- " . implode("\n- ", $constraints);

        // Adapt the prompt based on inspiration type
        $inspirationType = $this->detectInspirationType($inspiration);

        $prompt = "Create a playlist inspired by: {$inspiration}

CONSTRAINTS:
{$constraintsList}

CURATION APPROACH:
{$this->getCurationGuidance($inspirationType)}

OUTPUT FORMAT:
Return only a JSON array with this exact structure:
[
  {
    \"artist\": \"Artist Name\",
    \"track\": \"Track Name\",
    \"album\": \"Album Name\",
    \"year\": 1975
  }
]

No explanations or additional text, just the JSON array.";

        return $prompt;
    }

    /**
     * Detect what type of inspiration we're working with
     */
    protected function detectInspirationType($inspiration)
    {
        $inspiration = strtolower($inspiration);

        // Artist - Song pattern (like "Radiohead - Paranoid Android")
        if (preg_match('/^[^-]+ - [^-]+$/', $inspiration)) {
            return 'artist_song';
        }

        // Genre/style keywords
        $genreKeywords = ['electronic', 'rock', 'jazz', 'hip hop', 'classical', 'ambient', 'folk', 'pop', 'metal', 'reggae', 'country', 'blues', 'punk', 'indie'];
        foreach ($genreKeywords as $genre) {
            if (strpos($inspiration, $genre) !== false) {
                return 'genre_mood';
            }
        }

        // Mood/feeling keywords
        $moodKeywords = ['chill', 'energetic', 'sad', 'happy', 'relaxing', 'aggressive', 'melancholy', 'upbeat', 'dark', 'bright'];
        foreach ($moodKeywords as $mood) {
            if (strpos($inspiration, $mood) !== false) {
                return 'mood_based';
            }
        }

        // Decade/era references
        if (preg_match('/\b(19|20)\d{2}s?\b/', $inspiration)) {
            return 'era_based';
        }

        // Default to general inspiration
        return 'general';
    }

    /**
     * Get curation guidance based on inspiration type
     */
    protected function getCurationGuidance($inspirationType)
    {
        $guidance = [
            'artist_song' => 'Find tracks that match the musical style, mood, and sonic characteristics of the reference track. Consider similar artists, complementary genres, and tracks that would naturally flow together.',

            'genre_mood' => 'Stay focused on the specified genre while capturing the described mood. Include both well-known classics and deeper cuts. Consider the energy level and flow between tracks.',

            'mood_based' => 'Prioritize tracks that evoke the specified feeling or atmosphere. Draw from multiple genres if they serve the mood. Focus on sonic textures and emotional resonance.',

            'era_based' => 'Showcase the musical characteristics of the specified time period. Include influential tracks and hidden gems. Consider the cultural and sonic context of the era.',

            'general' => 'Interpret the inspiration creatively while maintaining musical coherence. Create a journey that captures the essence of the prompt. Balance familiarity with discovery.'
        ];

        return $guidance[$inspirationType] ?? $guidance['general'];
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
        $jsonData = trim(preg_replace('/\s+/', ' ', $jsonData));

        $tracks = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in AI response: ' . json_last_error_msg());
        }

        return $tracks;
    }

    /**
     * Validate tracks using Spotify API (using your existing SpotifyService)
     */
    protected function validateTracksWithSpotify($tracks, $options)
    {
        if (!$this->spotifyService) {
            return $tracks;
        }

        // Check if user is authenticated with Spotify
        if (!session()->has('spotify_access_token')) {
            logger()->info('Spotify validation skipped - user not authenticated');
            return $tracks;
        }

        $validatedTracks = [];
        $maxDurationMs = $options['max_duration_minutes'] * 60 * 1000;

        foreach ($tracks as $track) {
            try {
                // Use your existing getTrackIds method
                $searchResults = $this->spotifyService->getTrackIds(
                    $track['artist'],
                    $track['album'] ?? '', // Use album if available, empty string if not
                    $track['track']
                );

                // Check if we got results
                if (empty($searchResults['tracks']['items'])) {
                    logger()->info("Track not found on Spotify: {$track['artist']} - {$track['track']}");
                    // Still include track without Spotify data
                    $validatedTracks[] = $track;
                    continue;
                }

                $spotifyTrack = $searchResults['tracks']['items'][0];

                // Check duration constraint
                if ($spotifyTrack['duration_ms'] > $maxDurationMs) {
                    logger()->info("Track too long: {$track['artist']} - {$track['track']} ({$spotifyTrack['duration_ms']}ms)");
                    // Skip this track - it violates user constraints
                    continue;
                }

                // Add Spotify data to track
                $track['spotify_id'] = $spotifyTrack['id'];
                $track['spotify_url'] = $spotifyTrack['external_urls']['spotify'];
                $track['actual_duration'] = $this->msToMinutesSeconds($spotifyTrack['duration_ms']);
                $track['spotify_uri'] = $spotifyTrack['uri']; // Useful for adding to playlists later

                $validatedTracks[] = $track;
            } catch (Exception $e) {
                logger()->warning("Error validating track {$track['artist']} - {$track['track']}: " . $e->getMessage());
                // Include track without Spotify data
                $validatedTracks[] = $track;
            }
        }

        return $validatedTracks;
    }

    /**
     * Convert milliseconds to MM:SS format
     */
    protected function msToMinutesSeconds($ms)
    {
        $seconds = floor($ms / 1000);
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Create actual Spotify playlist from generated tracks
     */
    public function createSpotifyPlaylist($tracks, $playlistName, $isPublic = false)
    {
        if (!$this->spotifyService) {
            throw new Exception('Spotify service not available');
        }

        try {
            // Create the playlist
            $playlist = $this->spotifyService->createPlaylist($playlistName, $isPublic);

            // Get track URIs for tracks that have Spotify data
            $trackUris = [];
            foreach ($tracks as $track) {
                if (isset($track['spotify_uri'])) {
                    $trackUris[] = $track['spotify_uri'];
                }
            }

            if (empty($trackUris)) {
                throw new Exception('No Spotify tracks found to add to playlist');
            }

            // Add tracks to playlist (Spotify limits to 100 tracks per request)
            $chunks = array_chunk($trackUris, 100);
            foreach ($chunks as $chunk) {
                $this->spotifyService->addTracksToPlaylist($playlist['id'], $chunk);
            }

            return [
                'success' => true,
                'playlist' => $playlist,
                'tracks_added' => count($trackUris),
                'embed_code' => $this->spotifyService->getEmbedCode($playlist['id'])
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function ragRecommendations($query, $options = [])
    {
        return $this->localLibraryRagService->getRecommendations($query, $options);
    }
}
