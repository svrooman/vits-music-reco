<div x-data="{
    selectedId: @entangle('album_id'),
    selectAlbum(id) {
        this.selectedId = id;
    }
}" class="space-y-4">
    <!-- Source Album -->
    <div class="mb-6">
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Your Album:</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border-2 border-gray-200 dark:border-gray-700">
            <div class="flex items-start gap-4">
                @if($sourceAlbum->image_url)
                    <img src="{{ $sourceAlbum->image_url }}"
                         alt="{{ $sourceAlbum->artist }} - {{ $sourceAlbum->album }}"
                         class="w-24 h-24 rounded-lg object-cover flex-shrink-0">
                @else
                    <div class="w-24 h-24 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                        </svg>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <h4 class="text-base font-semibold text-gray-900 dark:text-white truncate">{{ $sourceAlbum->album }}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $sourceAlbum->artist }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ ucfirst($sourceAlbum->source) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tidal Matches -->
    <div>
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Select matching album on Tidal:</h3>
        <div class="space-y-2 max-h-96 overflow-y-auto">
            @foreach($matches as $match)
                @php
                    $matchId = $match['id'];
                    $title = $match['attributes']['title'] ?? 'Unknown';
                    $releaseDate = isset($match['attributes']['releaseDate'])
                        ? date('Y', strtotime($match['attributes']['releaseDate']))
                        : '';

                    // Get album artwork from relationships
                    $artworkId = null;
                    if (isset($match['relationships']['coverArt']['data'][0]['id'])) {
                        $artworkId = $match['relationships']['coverArt']['data'][0]['id'];
                    }

                    // Construct Tidal image URL (standard format)
                    $imageUrl = $artworkId
                        ? "https://resources.tidal.com/images/" . str_replace('-', '/', $artworkId) . "/750x750.jpg"
                        : null;
                @endphp

                <div @click="selectAlbum('{{ $matchId }}')"
                     :class="selectedId === '{{ $matchId }}' ? 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-800'"
                     class="flex items-start gap-4 p-3 rounded-lg border-2 border-gray-200 dark:border-gray-700 cursor-pointer transition-all">

                    @if($imageUrl)
                        <img src="{{ $imageUrl }}"
                             alt="{{ $title }}"
                             class="w-20 h-20 rounded-lg object-cover flex-shrink-0"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                        <div class="w-20 h-20 rounded-lg bg-gray-200 dark:bg-gray-700 items-center justify-center flex-shrink-0 hidden">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                            </svg>
                        </div>
                    @else
                        <div class="w-20 h-20 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                            </svg>
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $title }}</h4>
                                @if($releaseDate)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $releaseDate }}</p>
                                @endif
                            </div>
                            <div x-show="selectedId === '{{ $matchId }}'"
                                 class="flex-shrink-0 text-primary-500">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
