<div>
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Replace Track</h3>

        <!-- Album Art & Track Info -->
        <div class="flex items-start gap-4 mb-6">
            @if(isset($track['album_image']) && !empty($track['album_image']))
                <img src="{{ $track['album_image'] }}"
                     alt="{{ $track['album'] ?? '' }}"
                     class="w-24 h-24 rounded object-cover">
            @else
                <div class="w-24 h-24 rounded bg-gray-200 flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                    </svg>
                </div>
            @endif
            <div class="flex-1">
                <h4 class="font-semibold text-gray-900 mb-1">{{ $track['track'] }}</h4>
                <p class="text-sm text-gray-600">{{ $track['artist'] }}</p>
                <p class="text-sm text-gray-500">{{ $track['album'] ?? 'Unknown Album' }}</p>
                @if(isset($track['year']))
                    <p class="text-xs text-gray-400 mt-1">{{ $track['year'] }}</p>
                @endif
            </div>
        </div>

        <!-- Replacement Input -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Replacement Suggestion (optional)
            </label>
            <input wire:model="replacementSuggestion"
                   type="text"
                   placeholder="e.g., 'Artist - Song Title' or leave empty for automatic"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <p class="text-xs text-gray-500 mt-1">Leave empty for AI to suggest a replacement automatically</p>
        </div>
    </div>

    <!-- Footer -->
    <div class="px-6 py-4 bg-gray-50 flex gap-3 justify-end border-t">
        <button wire:click="$dispatch('closeModal')" type="button"
                class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-lg">
            Cancel
        </button>
        <button wire:click="replaceTrack" type="button"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg">
            Replace Track
        </button>
    </div>
</div>
