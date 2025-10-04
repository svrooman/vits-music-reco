<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'VITS Music Recommendation' }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full flex flex-col bg-gray-50 antialiased">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo/Brand -->
                <a href="{{ route('home') }}" class="text-xl font-semibold text-gray-900">
                    VITS Music Recommendation
                </a>

                <!-- Navigation -->
                <nav class="flex gap-4">
                    <a href="{{ route('home') }}"
                       class="px-3 py-2 text-sm font-medium {{ request()->routeIs('home') || request()->routeIs('playlist.*') ? 'text-indigo-600' : 'text-gray-700 hover:text-gray-900' }}">
                        Playlists
                    </a>
                    <a href="{{ route('discover.index') }}"
                       class="px-3 py-2 text-sm font-medium {{ request()->routeIs('discover.*') ? 'text-indigo-600' : 'text-gray-700 hover:text-gray-900' }}">
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
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200 bg-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} VITS Music Recommendation. Powered by Claude & Spotify.
        </div>
    </footer>

    @livewireScripts
    @livewire('wire-elements-modal')
</body>
</html>
