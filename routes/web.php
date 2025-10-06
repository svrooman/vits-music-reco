<?php

use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\SpotifyController;
use App\Http\Controllers\TidalController;
use App\Http\Controllers\Api\PlaylistController as ApiPlaylistController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

// Music AI Routes
Route::get('/', [PlaylistController::class, 'index'])->name('home');
Route::get('/playlist/{id}', [PlaylistController::class, 'show'])->name('playlist.show');
Route::post('/playlist/generate-and-create', [ApiPlaylistController::class, 'generateAndCreate']);

// Discover routes
Route::get('/discover', [DiscoverController::class, 'index'])->name('discover.index');
Route::post('/discover/generate', [DiscoverController::class, 'generate'])->name('discover.generate');
Route::post('/discover/add-to-library', [DiscoverController::class, 'addToLibrary'])->name('discover.addToLibrary');

// Spotify OAuth routes
Route::get('/spotify/auth', [SpotifyController::class, 'redirectToSpotify'])->name('spotify.auth');
Route::get('/spotify/callback', [SpotifyController::class, 'handleSpotifyCallback'])->name('spotify.callback');
Route::get('/spotify/create-playlist', [SpotifyController::class, 'createPlaylist'])->name('spotify.createPlaylist');
Route::get('/spotify/check-auth', [SpotifyController::class, 'checkAuth'])->name('spotify.checkAuth');
Route::get('/spotify/logout', [SpotifyController::class, 'logout'])->name('spotify.logout');
Route::get('/spotify/token', [SpotifyController::class, 'getAccessToken'])->name('spotify.token');

// Tidal OAuth routes
Route::get('/tidal/auth', [TidalController::class, 'redirectToTidal'])->name('tidal.auth');
Route::get('/tidal/callback', [TidalController::class, 'handleTidalCallback'])->name('tidal.callback');
Route::get('/tidal/check-auth', [TidalController::class, 'checkAuth'])->name('tidal.checkAuth');
Route::get('/tidal/logout', [TidalController::class, 'logout'])->name('tidal.logout');
Route::get('/tidal/token', [TidalController::class, 'getAccessToken'])->name('tidal.token');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

require __DIR__.'/auth.php';
