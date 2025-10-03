<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class ChatGptService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    public function generatePlaylist($inspirationTrack, $numberOfTracks = 10)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])
            ->post(
                'https://api.openai.com/v1/chat/completions',
                [
                    'model' => 'gpt-4o-mini-2024-07-18',
                    'temperature' => 0.7,
                    'messages' => [
                        [
                            "role" => "assistant",
                            "content" => "You are a music expert."
                        ],
                        [
                            'role' => 'user',
                            'content' => 'Generate a list of ' . $numberOfTracks . ' songs with distinct artists inspired by ' . $inspirationTrack . '. Please try to match the timbre of the music. Can the list be in the format { "artist": "Artist Name", "album": "Album Name", "track": "Track Name", "track_number": "Track Number from Album" } json that can be parsed easily by a PHP script.'
                        ]
                    ],
                ],
            )
            ->throw()
            ->json();

        $content = $response['choices'][0]['message']['content'];

        $startPos = strpos($content, '[');
        $endPos = strrpos($content, ']');

        if ($startPos !== false && $endPos !== false) {
            $jsonSongData = substr($content, $startPos, $endPos - $startPos + 1);
            $jsonSongData = trim(preg_replace('/\s+/', ' ', $jsonSongData));
            $jsonSongData = json_decode($jsonSongData, true);
        } else {
            throw new Exception('Failed to extract JSON data from the response.');
        }

        return $jsonSongData;
    }
}
