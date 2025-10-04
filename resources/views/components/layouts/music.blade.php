<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Music AI - Discover & Create' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 antialiased">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo/Brand -->
                <a href="{{ route('home') }}" class="text-xl font-semibold text-gray-900">
                    Music AI
                </a>

                <!-- Navigation -->
                <nav class="flex gap-4">
                    <a href="{{ route('home') }}"
                       class="px-3 py-2 text-sm font-medium {{ request()->routeIs('home') || request()->routeIs('playlist.*') ? 'text-primary-600' : 'text-gray-700 hover:text-gray-900' }}">
                        Playlists
                    </a>
                    <a href="{{ route('discover.index') }}"
                       class="px-3 py-2 text-sm font-medium {{ request()->routeIs('discover.*') ? 'text-primary-600' : 'text-gray-700 hover:text-gray-900' }}">
                        Discover
                    </a>
                </nav>

                <!-- Spotify Auth -->
                <div class="flex items-center gap-3">
                    @if(session()->has('spotify_user_data'))
                        <div class="flex items-center gap-2">
                            @php
                                $spotifyUser = session('spotify_user_data');
                                $userName = is_object($spotifyUser) ? ($spotifyUser->display_name ?? 'User') : ($spotifyUser['display_name'] ?? 'User');
                                $userImage = is_object($spotifyUser) ? ($spotifyUser->images[0]->url ?? null) : ($spotifyUser['images'][0]['url'] ?? null);
                            @endphp
                            @if($userImage)
                                <img src="{{ $userImage }}" alt="Profile" class="w-8 h-8 rounded-full">
                            @endif
                            <span class="text-sm text-gray-600">{{ $userName }}</span>
                            <a href="{{ route('spotify.logout') }}"
                               class="ml-2 text-sm text-gray-500 hover:text-gray-700">
                                Logout
                            </a>
                        </div>
                    @else
                        <a href="{{ route('spotify.auth') }}"
                           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Connect Spotify
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200 mt-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} Music AI. Powered by Claude & Spotify.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
