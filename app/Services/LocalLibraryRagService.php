<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class LocalLibraryRagService
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.local_library.rag_api_url', 'http://localhost:8889');
    }

    /**
     * Get RAG-powered recommendations
     */
    public function getRecommendations($query, $options = [])
    {
        $response = Http::timeout(30)->post($this->apiUrl . '/rag/recommend', [
            'query' => $query,
            'k' => $options['k'] ?? 10,
            'use_clusters' => $options['use_clusters'] ?? true,
            'include_context' => $options['include_context'] ?? false
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('RAG recommendation failed');
    }

    /**
     * Explore music with complex queries
     */
    public function exploreMusicSpace($query, $filters = [])
    {
        // Example: "Underground electronic from 2010s that's danceable but chill"

        $response = Http::post($this->apiUrl . '/rag/explore', [
            'query' => $query,
            'filters' => $filters
        ]);

        return $response->json();
    }

    /**
     * Get personalized daily recommendations
     */
    // public function getDailyRecommendations($userId)
    // {
    //     // Based on user's listening history
    //     $userPreferences = $this->getUserPreferences($userId);

    //     $queries = [
    //         "Similar to what I've been playing but with fresh discoveries",
    //         "Hidden gems matching my taste for " . implode(', ', $userPreferences['genres']),
    //         "Mood: " . $this->getCurrentMood() // time-based
    //     ];

    //     $recommendations = [];
    //     foreach ($queries as $query) {
    //         $recs = $this->getRecommendations($query, ['k' => 5]);
    //         $recommendations[] = $recs;
    //     }

    //     return $this->mergeAndDeduplicate($recommendations);
    // }
}
