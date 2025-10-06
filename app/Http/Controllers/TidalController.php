<?php

namespace App\Http\Controllers;

use App\Services\TidalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TidalController extends Controller
{
    protected TidalService $tidalService;

    public function __construct(TidalService $tidalService)
    {
        $this->tidalService = $tidalService;
    }

    /**
     * Redirect to Tidal OAuth
     */
    public function redirectToTidal()
    {
        $authUrl = $this->tidalService->getAuthUrl();
        return redirect($authUrl);
    }

    /**
     * Handle Tidal OAuth callback
     */
    public function handleTidalCallback(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            return redirect('/admin/discovered-albums')
                ->with('error', 'Tidal authorization failed');
        }

        // Exchange code for access token
        $tokenData = $this->tidalService->getAccessToken($code);

        if (!$tokenData) {
            return redirect('/admin/discovered-albums')
                ->with('error', 'Failed to get Tidal access token');
        }

        // Store tokens in user model
        $user = Auth::user();
        $user->update([
            'tidal_access_token' => $tokenData['access_token'],
            'tidal_refresh_token' => $tokenData['refresh_token'] ?? null,
            'tidal_expires_at' => now()->addSeconds($tokenData['expires_in']),
        ]);

        return redirect('/admin/discovered-albums')
            ->with('success', 'Connected to Tidal successfully!');
    }

    /**
     * Check Tidal auth status
     */
    public function checkAuth()
    {
        $user = Auth::user();

        if (!$user->tidal_access_token) {
            return response()->json(['authenticated' => false]);
        }

        // Check if token is expired
        if ($user->tidal_expires_at && $user->tidal_expires_at->isPast()) {
            // Try to refresh token
            if ($user->tidal_refresh_token) {
                $tokenData = $this->tidalService->refreshAccessToken($user->tidal_refresh_token);

                if ($tokenData) {
                    $user->update([
                        'tidal_access_token' => $tokenData['access_token'],
                        'tidal_refresh_token' => $tokenData['refresh_token'] ?? $user->tidal_refresh_token,
                        'tidal_expires_at' => now()->addSeconds($tokenData['expires_in']),
                    ]);

                    return response()->json(['authenticated' => true]);
                }
            }

            return response()->json(['authenticated' => false]);
        }

        return response()->json(['authenticated' => true]);
    }

    /**
     * Logout from Tidal
     */
    public function logout()
    {
        $user = Auth::user();
        $user->update([
            'tidal_access_token' => null,
            'tidal_refresh_token' => null,
            'tidal_expires_at' => null,
        ]);

        return redirect('/admin/discovered-albums')
            ->with('success', 'Disconnected from Tidal');
    }

    /**
     * Get access token (for use in services)
     */
    public function getAccessToken()
    {
        $user = Auth::user();

        if (!$user->tidal_access_token) {
            return response()->json(['token' => null], 401);
        }

        // Check if token needs refresh
        if ($user->tidal_expires_at && $user->tidal_expires_at->isPast()) {
            if ($user->tidal_refresh_token) {
                $tokenData = $this->tidalService->refreshAccessToken($user->tidal_refresh_token);

                if ($tokenData) {
                    $user->update([
                        'tidal_access_token' => $tokenData['access_token'],
                        'tidal_refresh_token' => $tokenData['refresh_token'] ?? $user->tidal_refresh_token,
                        'tidal_expires_at' => now()->addSeconds($tokenData['expires_in']),
                    ]);

                    return response()->json(['token' => $tokenData['access_token']]);
                }
            }

            return response()->json(['token' => null], 401);
        }

        return response()->json(['token' => $user->tidal_access_token]);
    }
}
