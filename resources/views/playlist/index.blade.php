<x-layouts.music title="Create Playlist">
    <div>
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Create Playlist</h1>
            <p class="text-gray-600">Generate track-by-track playlists from any inspiration using AI</p>
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
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                @livewire('forms.playlist-generator')
            </div>
        </div>

        <!-- Recent Playlists -->
        @if(count($playlists) > 0)
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Recent Playlists</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($playlists as $playlist)
                        <div class="bg-white border border-gray-200 rounded-lg hover:border-gray-300 transition-all">
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
                                        <x-button
                                            href="https://open.spotify.com/playlist/{{ $playlist->spotify_playlist_id }}"
                                            target="_blank"
                                            xs
                                            positive>
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
