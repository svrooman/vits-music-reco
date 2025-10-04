<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;
use App\Services\SpotifyService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SpotifyController extends Controller
{
    protected $session;
    protected $api;
    protected $spotifyService;

    public function __construct(SpotifyService $spotifyService)
    {
        $this->session = new Session(
            config('services.spotify.client_id'),
            config('services.spotify.client_secret'),
            config('services.spotify.redirect_uri')
        );

        $this->spotifyService = $spotifyService;

        $this->api = new SpotifyWebAPI();
    }

    public function redirectToSpotify()
    {
        // Store the selected service in the session
        session(['selected_service' => 'spotify']);

        $options = [
            'scope' => [
                'playlist-modify-private',
                'playlist-modify-public',
                'user-read-email',
                'user-read-private',
            ],
        ];

        return redirect($this->session->getAuthorizeUrl($options));
    }

    public function handleSpotifyCallback(Request $request)
    {
        try {
            $this->session->requestAccessToken($request->query('code'));

            $this->api->setAccessToken($this->session->getAccessToken());

            $spotifyUser = $this->api->me();

            // Find or create user based on Spotify data
            $user = $this->findOrCreateUser($spotifyUser);

            // Authenticate the user if not already logged in
            if (!Auth::check()) {
                Auth::login($user);
            }

            session([
                'spotify_user_data' => $spotifyUser,
                'spotify_access_token' => $this->session->getAccessToken(),
                'spotify_refresh_token' => $this->session->getRefreshToken(),
                'spotify_token_expires_at' => $this->session->getTokenExpiration(),
                'selected_service' => 'spotify'
            ]);

            // Flash success message for toast notification
            session()->flash('success', 'Successfully connected to Spotify!');

            return redirect()->route('playlist.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to authenticate with Spotify: ' . $e->getMessage());
            return redirect()->route('playlist.index');
        }
    }

    /**
     * Find or create a user based on Spotify profile data
     */
    protected function findOrCreateUser($spotifyUser)
    {
        // Try to find user by Spotify ID
        $user = User::where('spotify_id', $spotifyUser->id)->first();

        if (!$user) {
            // Try to find by email if available
            if (isset($spotifyUser->email)) {
                $user = User::where('email', $spotifyUser->email)->first();
            }

            // Create new user if not found
            if (!$user) {
                $user = User::create([
                    'name' => $spotifyUser->display_name ?? 'Spotify User',
                    'email' => $spotifyUser->email ?? $spotifyUser->id . '@spotify.user',
                    'password' => bcrypt(Str::random(16)),
                    'spotify_id' => $spotifyUser->id,
                ]);
            } else {
                // Update existing user with Spotify ID
                $user->update([
                    'spotify_id' => $spotifyUser->id
                ]);
            }
        }

        return $user;
    }

    public function createPlaylist()
    {
        $accessToken = session('spotify_access_token');

        if (!$accessToken) {
            session()->flash('error', 'You need to connect to Spotify first.');
            return redirect()->route('spotify.auth');
        }

        $this->api->setAccessToken($accessToken);

        try {
            $userId = $this->api->me()->id;
            $playlistName = request('name', 'AI Generated Playlist');

            $playlist = $this->api->createPlaylist($userId, [
                'name' => $playlistName,
                'public' => request('is_public', false),
                'description' => request('description', 'Created with AI Playlist Generator'),
            ]);

            session()->flash('success', 'Playlist created successfully!');
            return response()->json($playlist);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create playlist: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Check if user is authenticated with Spotify
     */
    public function checkAuth()
    {
        $isAuthenticated = session()->has('spotify_access_token');

        return response()->json([
            'authenticated' => $isAuthenticated,
            'service' => session('selected_service')
        ]);
    }

    /**
     * Logout from Spotify (disconnect the service)
     */
    public function logout()
    {
        // Clear Spotify-related session data
        session()->forget([
            'spotify_access_token',
            'spotify_refresh_token',
            'spotify_user_data',
            'selected_service',
            'spotify_token_expires_at',
        ]);

        session()->flash('success', 'Successfully disconnected from Spotify.');

        return redirect()->route('playlist.index');
    }

    public function getAccessToken()
    {
        if (!session()->has('spotify_access_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated with Spotify'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'access_token' => session('spotify_access_token'),
            'expires_at' => session('spotify_token_expires_at'),
            'refresh_token' => session('spotify_refresh_token'),
            'user' => session('spotify_user_data'),
        ]);
    }
}
