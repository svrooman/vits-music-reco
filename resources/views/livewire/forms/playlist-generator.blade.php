<div class="relative min-h-[400px]">
    <!-- Loading State -->
    <div wire:loading class="absolute inset-0 flex items-center justify-center bg-white z-10">
        <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="text-gray-600 mt-4">
                <span wire:loading.delay wire:target="submit">Generating tracks...</span>
                <span wire:loading.delay wire:target="createPlaylist">Creating playlist...</span>
            </p>
        </div>
    </div>

    <!-- Track Preview -->
    <div wire:loading.remove>
    @if($showPreview)
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Generated Tracks ({{ count($generatedTracks) }})</h3>
                <button wire:click="$set('showPreview', false)" class="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to form
                </button>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="max-h-96 overflow-y-auto">
                    @foreach($generatedTracks as $index => $track)
                        <div class="flex items-center gap-3 p-3 border-b border-gray-100 last:border-b-0 hover:bg-gray-50">
                            <div class="flex-shrink-0 text-gray-400 text-sm w-6">{{ $index + 1 }}</div>
                            @if(isset($track['album_image']))
                                <img src="{{ $track['album_image'] }}"
                                     alt="{{ $track['album'] ?? '' }}"
                                     class="w-10 h-10 rounded object-cover"
                                     onerror="this.src='https://placehold.co/40x40/e5e7eb/6b7280?text={{ urlencode(substr($track['artist'], 0, 1)) }}'">
                            @else
                                <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center text-gray-400 text-xs font-semibold">
                                    {{ strtoupper(substr($track['artist'], 0, 1)) }}
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 truncate">{{ $track['track'] }}</div>
                                <div class="text-sm text-gray-500 truncate">{{ $track['artist'] }} • {{ $track['album'] ?? 'Unknown Album' }}</div>
                            </div>
                            @if(isset($track['actual_duration']))
                                <div class="text-sm text-gray-400">{{ $track['actual_duration'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button wire:click="createPlaylist"
                        class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                    Create Playlist on Spotify
                </button>
                <button wire:click="$set('showPreview', false)"
                        class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-lg transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    @endif
    </div>

    <!-- Form -->
    <div wire:loading.remove>
    @if(!$showPreview)
    <form wire:submit="submit" class="space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Playlist Name</label>
            <input wire:model="name" type="text" id="name" required
                   placeholder="My Awesome Playlist"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Inspiration</label>
            <textarea wire:model="description" id="description" rows="4" required
                      placeholder="Describe what kind of music you want... (e.g., 'upbeat indie rock from the 2000s' or 'chill lo-fi beats for studying')"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
        </div>

        <div>
            <label for="numberOfTracks" class="block text-sm font-medium text-gray-700 mb-1">Number of Tracks</label>
            <input wire:model="numberOfTracks" type="number" id="numberOfTracks" min="1" max="50" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div class="flex items-center">
            <input wire:model="isPublic" type="checkbox" id="isPublic"
                   class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
            <label for="isPublic" class="ml-2 text-sm text-gray-700">Make playlist public</label>
        </div>

        <button type="submit"
                class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
            Generate Tracks
        </button>
    </form>
    @endif
    </div>

    @if($playlistId)
        <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium text-green-800">Your playlist has been created!</span>
                </div>
                <a href="https://open.spotify.com/playlist/{{ $playlistId }}" target="_blank"
                   class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded transition-colors">
                    Open in Spotify
                </a>
            </div>
        </div>
    @endif
</div>
