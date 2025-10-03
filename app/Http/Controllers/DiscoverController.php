<?php

namespace App\Http\Controllers;

use App\Services\AlbumRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DiscoverController extends Controller
{
    protected $albumRecommendationService;

    public function __construct(AlbumRecommendationService $albumRecommendationService)
    {
        $this->albumRecommendationService = $albumRecommendationService;
    }

    /**
     * Show the discover page
     */
    public function index()
    {
        return view('discover.index');
    }

    /**
     * Generate album/artist recommendations
     */
    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:500',
            'count' => 'integer|min:1|max:50',
            'type' => 'string|in:albums,artists,mixed'
        ]);

        $prompt = $request->input('prompt');
        $count = $request->input('count', 10);
        $type = $request->input('type', 'albums');
        $provider = $request->input('provider', 'claude');

        $result = $this->albumRecommendationService->getRecommendations($prompt, $count, [
            'provider' => $provider,
            'type' => $type,
            'fetch_album_art' => true
        ]);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'recommendations' => $result['recommendations'],
                'metadata' => $result['metadata']
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => $result['error']
            ], 500);
        }
    }

    /**
     * Add selected recommendations to Spotify library
     */
    public function addToLibrary(Request $request)
    {
        $request->validate([
            'recommendations' => 'required|array',
            'type' => 'string|in:albums,artists'
        ]);

        $recommendations = $request->input('recommendations');
        $type = $request->input('type', 'albums');

        try {
            $result = $this->albumRecommendationService->addToSpotifyLibrary($recommendations, $type);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to add to library: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
