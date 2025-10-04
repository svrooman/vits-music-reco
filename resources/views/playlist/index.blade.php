<x-layouts.music title="Create Playlist">
    <div>
        <!-- Header Section -->
        <div class="mb-8 flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Create Playlist</h1>
                <p class="text-gray-600">Generate track-by-track playlists from any inspiration using AI</p>
            </div>
            @if(!session()->has('spotify_user_data'))
                <a href="{{ route('spotify.auth') }}"
                   class="flex items-center gap-2 px-6 py-3 bg-[#1DB954] hover:bg-[#1ed760] text-white font-bold rounded-full transition-colors shadow-lg">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                    </svg>
                    Connect Spotify
                </a>
            @endif
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                <strong class="font-medium">Success!</strong> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <strong class="font-medium">Error!</strong> {{ session('error') }}
            </div>
        @endif

        <!-- Playlist Generation Form -->
        <div class="mb-12">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Generate Playlist</h2>
                </div>
                <div class="p-6">
                    @livewire('forms.playlist-generator')
                </div>
            </div>
        </div>

        <!-- Recent Playlists -->
        @if(count($playlists) > 0)
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Recent Playlists</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($playlists as $playlist)
                        <div class="bg-white border border-gray-200 rounded-lg hover:border-gray-300 transition-all overflow-hidden">
                            @if($playlist->cover_image_url)
                                <img src="{{ $playlist->cover_image_url }}"
                                     alt="{{ $playlist->name }}"
                                     class="w-full aspect-square object-cover">
                            @else
                                <div class="w-full aspect-square bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-white opacity-50" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="p-5">
                                <h3 class="text-lg font-semibold text-gray-900 truncate">
                                    {{ $playlist->name }}
                                </h3>
                                <p class="text-gray-600 text-sm mt-2 line-clamp-2">
                                    {{ Illuminate\Support\Str::limit($playlist->description, 100) }}
                                </p>
                                <div class="flex mt-4 justify-between items-center">
                                    <span class="text-xs text-gray-500">{{ $playlist->created_at->format('M d, Y') }}</span>
                                    @if($playlist->spotify_playlist_id)
                                        <a href="https://open.spotify.com/playlist/{{ $playlist->spotify_playlist_id }}"
                                           target="_blank"
                                           class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded transition-colors">
                                            Open
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.music>
