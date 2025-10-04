<x-layouts.music title="Discover Music">
    <div>
        <!-- Prompt Input Section -->
        <div class="mb-8">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Discover Music</h2>
                </div>
                <div class="p-6 space-y-4">
                    <input
                        type="text"
                        id="discover-prompt"
                        placeholder="What music do you want to discover?"
                        autofocus
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="type-select" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select id="type-select"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="albums" selected>Albums</option>
                                <option value="artists">Artists</option>
                                <option value="mixed">Mixed</option>
                            </select>
                        </div>

                        <div>
                            <label for="count-select" class="block text-sm font-medium text-gray-700 mb-1">Count</label>
                            <select id="count-select"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="5">5 items</option>
                                <option value="10" selected>10 items</option>
                                <option value="15">15 items</option>
                                <option value="20">20 items</option>
                            </select>
                        </div>
                    </div>

                    <button
                        id="generate-btn"
                        class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                        Generate
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading" class="hidden text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="text-gray-600 mt-4">Generating recommendations...</p>
        </div>

        <!-- Results Section -->
        <div id="results" class="hidden">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Recommendations</h2>
                <div class="flex gap-3">
                    <button
                        id="select-all-btn"
                        class="px-4 py-2 text-gray-700 hover:bg-gray-100 border border-gray-300 font-medium rounded-lg transition-colors">
                        Select All
                    </button>
                    <button
                        id="add-to-spotify-btn"
                        disabled
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Add to Spotify
                    </button>
                </div>
            </div>

            <div id="recommendations-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Recommendations will be inserted here -->
            </div>
        </div>
    </div>

    <script>
        const promptInput = document.getElementById('discover-prompt');
        const generateBtn = document.getElementById('generate-btn');
        const typeSelect = document.getElementById('type-select');
        const countSelect = document.getElementById('count-select');
        const loading = document.getElementById('loading');
        const results = document.getElementById('results');
        const recommendationsContainer = document.getElementById('recommendations-container');
        const selectAllBtn = document.getElementById('select-all-btn');
        const addToSpotifyBtn = document.getElementById('add-to-spotify-btn');

        let selectedRecommendations = [];

        // Generate recommendations
        generateBtn.addEventListener('click', async () => {
            const prompt = promptInput.value.trim();

            if (!prompt) {
                alert('Please enter a prompt');
                return;
            }

            generateBtn.disabled = true;
            loading.classList.remove('hidden');
            results.classList.add('hidden');

            try {
                const response = await fetch('{{ route('discover.generate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        prompt: prompt,
                        type: typeSelect.value,
                        count: parseInt(countSelect.value)
                    })
                });

                const data = await response.json();

                if (data.success) {
                    displayRecommendations(data.recommendations);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to generate recommendations');
            } finally {
                generateBtn.disabled = false;
                loading.classList.add('hidden');
            }
        });

        // Display recommendations
        function displayRecommendations(recommendations) {
            recommendationsContainer.innerHTML = '';
            selectedRecommendations = [];

            console.log('Recommendations received:', recommendations);

            recommendations.forEach((rec, index) => {
                console.log(`Recommendation ${index}:`, rec);
                const card = createRecommendationCard(rec, index);
                recommendationsContainer.appendChild(card);
            });

            results.classList.remove('hidden');
            updateAddButton();
        }

        // Create recommendation card
        function createRecommendationCard(rec, index) {
            const div = document.createElement('div');
            div.className = 'bg-white border border-gray-200 rounded-lg overflow-hidden hover:border-gray-300 transition-all cursor-pointer group';

            const hasSpotifyData = rec.spotify_data?.available;
            const imageUrl = rec.spotify_data?.image || 'https://placehold.co/300x300/e5e7eb/6b7280?text=No+Image';

            div.innerHTML = `
                <div class="flex gap-4 p-4">
                    <div class="relative flex-shrink-0">
                        <input
                            type="checkbox"
                            class="recommendation-checkbox absolute top-2 left-2 w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 z-10"
                            data-index="${index}"
                            ${!hasSpotifyData ? 'disabled' : ''}
                        />
                        <img
                            src="${imageUrl}"
                            alt="${rec.album || rec.artist}"
                            class="w-24 h-24 object-cover rounded-lg ${!hasSpotifyData ? 'opacity-50' : ''}"
                        />
                        ${hasSpotifyData ? `
                            <a href="${rec.spotify_data.url}" target="_blank" class="absolute bottom-2 right-2 bg-[#1DB954] p-1.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                                </svg>
                            </a>
                        ` : ''}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-gray-900 font-semibold truncate">${rec.artist}</h3>
                        <p class="text-gray-600 text-sm truncate">${rec.album || rec.essential_album || 'Various'}</p>
                        ${rec.year ? `<p class="text-gray-500 text-xs mt-1">${rec.year}${rec.genre ? ' • ' + rec.genre : ''}</p>` : ''}
                        ${rec.reason ? `<p class="text-gray-600 text-xs mt-2 line-clamp-2">${rec.reason}</p>` : ''}
                        ${!hasSpotifyData ? `<span class="inline-block mt-2 px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">Not on Spotify</span>` : ''}
                    </div>
                </div>
            `;

            // Click card to toggle checkbox
            div.addEventListener('click', (e) => {
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'A' && hasSpotifyData) {
                    const checkbox = div.querySelector('.recommendation-checkbox');
                    checkbox.checked = !checkbox.checked;
                    updateSelectedRecommendations();
                }
            });

            // Checkbox change
            div.querySelector('.recommendation-checkbox').addEventListener('change', updateSelectedRecommendations);

            return div;
        }

        // Update selected recommendations
        function updateSelectedRecommendations() {
            const checkboxes = document.querySelectorAll('.recommendation-checkbox:checked');
            selectedRecommendations = Array.from(checkboxes).map(cb => {
                const index = parseInt(cb.dataset.index);
                return window.currentRecommendations[index];
            });
            updateAddButton();
        }

        // Update add button state
        function updateAddButton() {
            addToSpotifyBtn.disabled = selectedRecommendations.length === 0;
            addToSpotifyBtn.textContent = selectedRecommendations.length > 0
                ? `Add ${selectedRecommendations.length} to Spotify`
                : 'Add to Spotify';
        }

        // Select all
        selectAllBtn.addEventListener('click', () => {
            const checkboxes = document.querySelectorAll('.recommendation-checkbox:not(:disabled)');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            selectAllBtn.textContent = allChecked ? 'Select All' : 'Deselect All';
            updateSelectedRecommendations();
        });

        // Add to Spotify
        addToSpotifyBtn.addEventListener('click', async () => {
            if (selectedRecommendations.length === 0) return;

            addToSpotifyBtn.disabled = true;
            addToSpotifyBtn.textContent = 'Adding...';

            try {
                const response = await fetch('{{ route('discover.addToLibrary') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        recommendations: selectedRecommendations,
                        type: typeSelect.value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`Successfully added ${data.count} items to your Spotify library!`);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to add to Spotify library');
            } finally {
                addToSpotifyBtn.disabled = false;
                updateAddButton();
            }
        });

        // Enter key to generate
        promptInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                generateBtn.click();
            }
        });

        // Store recommendations globally for access
        window.currentRecommendations = [];

        const originalDisplayRecommendations = displayRecommendations;
        displayRecommendations = function(recommendations) {
            window.currentRecommendations = recommendations;
            originalDisplayRecommendations(recommendations);
        };
    </script>
</x-layouts.music>
