<x-layouts.music title="Create Playlist">
    <div class="max-w-5xl mx-auto">
        <!-- Header Section -->
        <div class="mb-12">
            <h1 class="text-4xl font-bold text-white mb-2">Create Playlist</h1>
            <p class="text-zinc-400">Generate track-by-track playlists from any inspiration using AI</p>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <x-alert title="Success!" positive class="mb-6">
                {{ session('success') }}
            </x-alert>
        @endif

        @if(session('error'))
            <x-alert title="Error!" negative class="mb-6">
                {{ session('error') }}
            </x-alert>
        @endif

        <!-- Playlist Generation Form -->
        <div class="mb-12">
            <div class="bg-zinc-900/50 border border-zinc-800 rounded-lg p-8">
                @livewire('forms.playlist-generator')
            </div>
        </div>

        <!-- Recent Playlists -->
        @if(count($playlists) > 0)
            <div class="mt-16">
                <h2 class="text-2xl font-bold text-white mb-6">Recent Playlists</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($playlists as $playlist)
                        <div class="bg-zinc-900/50 border border-zinc-800 rounded-lg hover:border-zinc-600 transition-all group">
                            <div class="p-5">
                                <h3 class="text-lg font-semibold text-white truncate">
                                    {{ $playlist->name }}
                                </h3>
                                <p class="text-zinc-400 text-sm mt-2 line-clamp-2">
                                    {{ Illuminate\Support\Str::limit($playlist->description, 100) }}
                                </p>
                                <div class="flex mt-4 justify-between items-center">
                                    <span class="text-xs text-zinc-500">{{ $playlist->created_at->format('M d, Y') }}</span>
                                    @if($playlist->spotify_playlist_id)
                                        <x-button
                                            href="https://open.spotify.com/playlist/{{ $playlist->spotify_playlist_id }}"
                                            target="_blank"
                                            xs
                                            primary
                                            class="bg-[#1DB954] hover:bg-green-600">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                                            </svg>
                                            Open
                                        </x-button>
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
