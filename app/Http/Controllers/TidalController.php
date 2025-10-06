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
        \Log::info('Tidal callback received', [
            'all_params' => $request->all(),
            'query' => $request->query(),
        ]);

        $code = $request->get('code');
        $error = $request->get('error');
        $errorDescription = $request->get('error_description');

        if ($error) {
            \Log::error('Tidal callback: Error from Tidal', [
                'error' => $error,
                'description' => $errorDescription,
            ]);
            return redirect('/admin/discovered-albums')
                ->with('error', "Tidal authorization failed: {$error} - {$errorDescription}");
        }

        if (!$code) {
            \Log::error('Tidal callback: No code received');
            return redirect('/admin/discovered-albums')
                ->with('error', 'Tidal authorization failed - no code');
        }

        // Exchange code for access token
        $tokenData = $this->tidalService->getAccessToken($code);

        if (!$tokenData) {
            \Log::error('Tidal callback: Failed to get access token');
            return redirect('/admin/discovered-albums')
                ->with('error', 'Failed to get Tidal access token');
        }

        \Log::info('Tidal callback: Got token data', ['has_access_token' => !empty($tokenData['access_token'])]);

        // Store tokens in user model
        $user = Auth::user();

        if (!$user) {
            \Log::error('Tidal callback: No authenticated user');
            return redirect('/admin/discovered-albums')
                ->with('error', 'Not authenticated');
        }

        $user->update([
            'tidal_access_token' => $tokenData['access_token'],
            'tidal_refresh_token' => $tokenData['refresh_token'] ?? null,
            'tidal_expires_at' => now()->addSeconds($tokenData['expires_in']),
        ]);

        \Log::info('Tidal callback: Tokens stored for user', ['user_id' => $user->id]);

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
