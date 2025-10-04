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
                <div class="max-h-96 overflow-y-auto" id="track-list" wire:ignore.self>
                    @foreach($generatedTracks as $index => $track)
                        <div class="track-item flex items-center gap-3 p-3 border-b border-gray-100 last:border-b-0 hover:bg-gray-50" data-index="{{ $index }}">
                            <!-- Drag Handle -->
                            <div class="drag-handle flex-shrink-0 cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/>
                                </svg>
                            </div>
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
                            <!-- View/Replace Track Button -->
                            <button wire:click="openReplaceModal({{ $index }})" type="button"
                                    class="flex-shrink-0 px-3 py-1 text-sm text-gray-600 hover:text-indigo-600 border border-gray-300 hover:border-indigo-600 rounded transition-colors">
                                View
                            </button>
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

    <!-- Replace Track Modal -->
    @if($showReplaceModal && isset($generatedTracks[$replaceTrackIndex]))
        <div class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true"
             x-data="{ show: false }"
             x-init="setTimeout(() => show = true, 50)"
             x-show="show"
             @click="$wire.closeReplaceModal()">

            <!-- Background backdrop -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity"
                 x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"></div>

            <!-- Modal panel -->
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
                         @click.stop
                         x-show="show"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                        @php
                            $track = $generatedTracks[$replaceTrackIndex];
                        @endphp

                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900" id="modal-title">Replace Track</h3>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6">
                            <!-- Album Art & Track Info -->
                            <div class="flex items-start gap-4 mb-6">
                                @if(isset($track['album_image']))
                                    <img src="{{ $track['album_image'] }}"
                                         alt="{{ $track['album'] ?? '' }}"
                                         class="w-24 h-24 rounded object-cover flex-shrink-0">
                                @else
                                    <div class="w-24 h-24 rounded bg-gray-200 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-900 mb-1">{{ $track['track'] }}</h4>
                                    <p class="text-sm text-gray-600">{{ $track['artist'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $track['album'] ?? 'Unknown Album' }}</p>
                                    @if(isset($track['year']))
                                        <p class="text-xs text-gray-400 mt-1">{{ $track['year'] }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Suggestion Input -->
                            <div class="mb-4">
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

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-gray-50 flex gap-3 sm:flex-row-reverse">
                            <button wire:click="replaceTrack" type="button"
                                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:w-auto">
                                Replace Track
                            </button>
                            <button wire:click="closeReplaceModal" type="button"
                                    class="inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:w-auto">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
