<?php
// app/Services/LocalLibraryService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LocalLibraryService
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.local_library.api_url', 'http://localhost:8888');
    }

    /**
     * Search using semantic/natural language query
     */
    public function searchSemantic($query, $limit = 20, $minSimilarity = 0.5)
    {
        try {
            $response = Http::timeout(10)->post($this->apiUrl . '/search/semantic', [
                'query' => $query,
                'limit' => $limit,
                'min_similarity' => $minSimilarity
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Local library semantic search failed', [
                'query' => $query,
                'status' => $response->status()
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error('Local library API error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Try to find a specific track
     */
    public function findTrack($artist, $trackName, $threshold = 0.8)
    {
        $cacheKey = "local_track_" . md5(strtolower($artist . $trackName));

        return Cache::remember($cacheKey, 3600, function () use ($artist, $trackName, $threshold) {
            try {
                $response = Http::timeout(5)->post($this->apiUrl . '/match/track', [
                    'artist' => $artist,
                    'track' => $trackName,
                    'threshold' => $threshold
                ]);

                if ($response->successful() && $response->json()) {
                    return $response->json();
                }

                return null;
            } catch (\Exception $e) {
                Log::warning('Failed to find local track', [
                    'artist' => $artist,
                    'track' => $trackName,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Check if service is available
     */
    public function isAvailable()
    {
        try {
            $response = Http::timeout(2)->get($this->apiUrl . '/docs');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
