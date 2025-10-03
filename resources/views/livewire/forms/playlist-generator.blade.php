<div>
    <form wire:submit="submit">
        <div class="space-y-6">
            <!-- Playlist Name -->
            <flux:input
                wire:model="name"
                label="Playlist Name"
                placeholder="My Awesome Playlist"
                required
            />

            <!-- Inspiration/Description -->
            <flux:textarea
                wire:model="description"
                label="Inspiration"
                placeholder="Describe what kind of music you want... (e.g., 'upbeat indie rock from the 2000s' or 'chill lo-fi beats for studying')"
                rows="4"
                required
            />

            <!-- Number of Tracks -->
            <flux:input
                wire:model.number="numberOfTracks"
                type="number"
                label="Number of Tracks"
                min="1"
                max="50"
                required
            />

            <!-- Public Playlist Toggle -->
            <flux:checkbox
                wire:model="isPublic"
                label="Make playlist public"
            />

            <!-- Submit Button -->
            <flux:button
                type="submit"
                variant="primary"
                class="w-full bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600"
                :disabled="$isLoading">
                @if($isLoading)
                    <flux:icon.loading class="w-5 h-5 mr-2" />
                    Generating Playlist...
                @else
                    Generate Playlist
                @endif
            </flux:button>
        </div>
    </form>

    <!-- Success/Playlist Link -->
    @if($playlistId)
        <div class="mt-6">
            <flux:card class="bg-zinc-800 border-green-500">
                <div class="text-center py-4">
                    <p class="text-white mb-4">✨ Your playlist has been created!</p>
                    <flux:button
                        href="https://open.spotify.com/playlist/{{ $playlistId }}"
                        target="_blank"
                        variant="primary"
                        class="bg-[#1DB954] hover:bg-green-600">
                        Open in Spotify
                    </flux:button>
                </div>
            </flux:card>
        </div>
    @endif
</div>
