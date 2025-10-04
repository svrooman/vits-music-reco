<div>
    <form wire:submit="submit">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Playlist Name -->
            <div class="sm:col-span-2">
                <x-input
                    wire:model="name"
                    label="Playlist Name"
                    placeholder="My Awesome Playlist"
                    required
                />
            </div>

            <!-- Inspiration/Description -->
            <div class="sm:col-span-2">
                <x-textarea
                    wire:model="description"
                    label="Inspiration"
                    placeholder="Describe what kind of music you want... (e.g., 'upbeat indie rock from the 2000s' or 'chill lo-fi beats for studying')"
                    rows="4"
                    required
                />
            </div>

            <!-- Number of Tracks -->
            <x-input
                wire:model="numberOfTracks"
                type="number"
                label="Number of Tracks"
                min="1"
                max="50"
                required
            />

            <!-- Public Playlist Toggle -->
            <div class="flex items-end pb-2">
                <x-checkbox
                    wire:model="isPublic"
                    label="Make playlist public"
                />
            </div>
        </div>

        <!-- Footer with buttons -->
        <div class="border-secondary-200 dark:border-secondary-600 rounded-b-md bg-transparent border-t px-4 py-4 sm:px-6 flex items-center justify-end gap-x-3 mt-6">
            <x-button
                type="submit"
                teal
                :disabled="$isLoading"
                spinner>
                @if($isLoading)
                    Generating...
                @else
                    Generate Playlist
                @endif
            </x-button>
        </div>
    </form>

    <!-- Success/Playlist Link -->
    @if($playlistId)
        <div class="mt-6 -mx-2 md:-mx-4">
            <div class="bg-positive-50 dark:bg-positive-900/20 border-t border-positive-200 dark:border-positive-700 rounded-b-md p-6">
                <div class="text-center">
                    <p class="text-secondary-700 dark:text-secondary-400 mb-4">✨ Your playlist has been created!</p>
                    <x-button
                        href="https://open.spotify.com/playlist/{{ $playlistId }}"
                        target="_blank"
                        teal>
                        Open in Spotify
                    </x-button>
                </div>
            </div>
        </div>
    @endif
</div>
