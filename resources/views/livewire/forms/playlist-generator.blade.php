<div>
    <form wire:submit="submit">
        <div class="space-y-6">
            <!-- Playlist Name -->
            <x-input
                wire:model="name"
                label="Playlist Name"
                placeholder="My Awesome Playlist"
                required
            />

            <!-- Inspiration/Description -->
            <x-textarea
                wire:model="description"
                label="Inspiration"
                placeholder="Describe what kind of music you want... (e.g., 'upbeat indie rock from the 2000s' or 'chill lo-fi beats for studying')"
                rows="4"
                required
            />

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
            <x-checkbox
                wire:model="isPublic"
                label="Make playlist public"
            />

            <!-- Submit Button -->
            <x-button
                type="submit"
                primary
                class="w-full bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600"
                :disabled="$isLoading"
                spinner>
                @if($isLoading)
                    Generating Playlist...
                @else
                    Generate Playlist
                @endif
            </x-button>
        </div>
    </form>

    <!-- Success/Playlist Link -->
    @if($playlistId)
        <div class="mt-6">
            <div class="bg-zinc-800 border border-green-500 rounded-lg p-6">
                <div class="text-center">
                    <p class="text-white mb-4">✨ Your playlist has been created!</p>
                    <x-button
                        href="https://open.spotify.com/playlist/{{ $playlistId }}"
                        target="_blank"
                        primary
                        class="bg-[#1DB954] hover:bg-green-600">
                        Open in Spotify
                    </x-button>
                </div>
            </div>
        </div>
    @endif
</div>
