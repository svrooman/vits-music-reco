<div>
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

        <button type="submit" :disabled="$isLoading"
                class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            @if($isLoading)
                <span class="flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Generating Playlist...
                </span>
            @else
                Generate Playlist
            @endif
        </button>
    </form>

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
