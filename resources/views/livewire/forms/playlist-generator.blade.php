<div>
    <form wire:submit="submit" class="space-y-4">
        <x-input
            wire:model="name"
            label="Playlist Name"
            placeholder="My Awesome Playlist"
            required
        />

        <x-textarea
            wire:model="description"
            label="Inspiration"
            placeholder="Describe what kind of music you want... (e.g., 'upbeat indie rock from the 2000s' or 'chill lo-fi beats for studying')"
            rows="4"
            required
        />

        <x-input
            wire:model="numberOfTracks"
            type="number"
            label="Number of Tracks"
            min="1"
            max="50"
            required
        />

        <x-checkbox
            wire:model="isPublic"
            label="Make playlist public"
        />

        <x-button
            type="submit"
            primary
            class="w-full"
            :disabled="$isLoading"
            spinner>
            @if($isLoading)
                Generating Playlist...
            @else
                Generate Playlist
            @endif
        </x-button>
    </form>

    @if($playlistId)
        <div class="mt-6">
            <x-alert positive title="Success!">
                Your playlist has been created!
                <x-slot name="action">
                    <x-button
                        href="https://open.spotify.com/playlist/{{ $playlistId }}"
                        target="_blank"
                        positive
                        xs>
                        Open in Spotify
                    </x-button>
                </x-slot>
            </x-alert>
        </div>
    @endif
</div>
