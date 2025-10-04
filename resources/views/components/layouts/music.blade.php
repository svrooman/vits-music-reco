<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Music AI - Discover & Create' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <wireui:scripts />

    <!-- Theme Detection Script -->
    <script>
        // Set theme on page load
        const theme = localStorage.getItem('theme');
        if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="min-h-screen bg-white dark:bg-black text-zinc-900 dark:text-white antialiased transition-colors">
    <!-- Minimalist Header -->
    <header class="border-b border-zinc-200 dark:border-zinc-800 bg-white/50 dark:bg-black/50 backdrop-blur-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo/Brand -->
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="text-xl font-semibold">Music AI</span>
                </a>

                <!-- Navigation -->
                <nav class="flex gap-1">
                    <a href="{{ route('home') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('home') || request()->routeIs('playlist.*') ? 'bg-zinc-200 dark:bg-zinc-800 text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800/50' }}">
                        Playlists
                    </a>
                    <a href="{{ route('discover.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('discover.*') ? 'bg-zinc-200 dark:bg-zinc-800 text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800/50' }}">
                        Discover
                    </a>
                </nav>

                <!-- Theme Switcher & Spotify Auth -->
                <div class="flex items-center gap-3">
                    @livewire('theme-switcher')
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
                            <span class="text-sm text-zinc-400">{{ $userName }}</span>
                            <a href="{{ route('spotify.logout') }}"
                               class="ml-2 text-xs text-zinc-500 hover:text-white transition-colors">
                                Logout
                            </a>
                        </div>
                    @else
                        <a href="{{ route('spotify.auth') }}"
                           class="px-4 py-2 bg-[#1DB954] hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                            </svg>
                            Connect Spotify
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-12">
        {{ $slot }}
    </main>

    <!-- Minimalist Footer -->
    <footer class="border-t border-zinc-200 dark:border-zinc-800 mt-20">
        <div class="max-w-7xl mx-auto px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-500">
            &copy; {{ date('Y') }} Music AI. Powered by Claude & Spotify.
        </div>
    </footer>
</body>
</html>
