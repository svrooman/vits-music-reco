import Sortable from 'sortablejs';

// Audio player state
let currentAudio = null;
let currentTrackItem = null;

// Initialize Sortable when Livewire component loads
document.addEventListener('DOMContentLoaded', function() {
    initializeSortable();
    initializeAudioPlayers();
});

// Re-initialize when Livewire updates the DOM
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', ({ el, component }) => {
        initializeSortable();
        initializeAudioPlayers();
    });
});

function initializeSortable() {
    const trackList = document.getElementById('track-list');

    if (!trackList) return;

    // Destroy existing sortable instance if it exists
    if (trackList.sortableInstance) {
        trackList.sortableInstance.destroy();
    }

    // Create new sortable instance
    trackList.sortableInstance = Sortable.create(trackList, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'bg-indigo-50',
        chosenClass: 'bg-indigo-100',
        dragClass: 'opacity-50',
        forceFallback: true,
        onEnd: function (evt) {
            // Get the new order of track indexes
            const items = trackList.querySelectorAll('.track-item');
            const newOrder = Array.from(items).map(item => parseInt(item.dataset.index));

            // Find the Livewire component and call the method
            const component = trackList.closest('[wire\\:id]');
            if (component) {
                const componentId = component.getAttribute('wire:id');
                if (componentId && window.Livewire) {
                    window.Livewire.find(componentId).call('updateTrackOrder', newOrder);
                }
            }
        }
    });
}

function initializeAudioPlayers() {
    const trackItems = document.querySelectorAll('.track-item[data-preview-url]');

    trackItems.forEach((trackItem) => {
        const previewUrl = trackItem.dataset.previewUrl;
        if (!previewUrl) return;

        const playButton = trackItem.querySelector('[data-play-button]');
        const playIcon = trackItem.querySelector('[data-play-icon]');
        const pauseIcon = trackItem.querySelector('[data-pause-icon]');
        const progressBar = trackItem.querySelector('[data-progress-bar]');

        if (!playButton) return;

        // Remove existing listeners
        const newPlayButton = playButton.cloneNode(true);
        playButton.parentNode.replaceChild(newPlayButton, playButton);

        newPlayButton.addEventListener('click', (e) => {
            e.stopPropagation();

            // If clicking the same track that's playing, pause it
            if (currentAudio && currentTrackItem === trackItem && !currentAudio.paused) {
                currentAudio.pause();
                return;
            }

            // Stop any currently playing audio
            if (currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
                resetTrackUI(currentTrackItem);
            }

            // Create and play new audio
            currentAudio = new Audio(previewUrl);
            currentTrackItem = trackItem;

            // Update UI to show playing state
            const currentPlayIcon = trackItem.querySelector('[data-play-icon]');
            const currentPauseIcon = trackItem.querySelector('[data-pause-icon]');
            if (currentPlayIcon && currentPauseIcon) {
                currentPlayIcon.classList.add('hidden');
                currentPauseIcon.classList.remove('hidden');
            }

            // Update progress bar as track plays
            currentAudio.addEventListener('timeupdate', () => {
                const progress = (currentAudio.currentTime / currentAudio.duration) * 100;
                const currentProgressBar = trackItem.querySelector('[data-progress-bar]');
                if (currentProgressBar) {
                    currentProgressBar.style.transform = `scaleX(${progress / 100})`;
                }
            });

            // When track ends
            currentAudio.addEventListener('ended', () => {
                resetTrackUI(trackItem);
                currentAudio = null;
                currentTrackItem = null;

                // Auto-play next track
                const nextTrack = trackItem.nextElementSibling;
                if (nextTrack && nextTrack.classList.contains('track-item')) {
                    const nextButton = nextTrack.querySelector('[data-play-button]');
                    if (nextButton) {
                        setTimeout(() => nextButton.click(), 500);
                    }
                }
            });

            // When track is paused
            currentAudio.addEventListener('pause', () => {
                const currentPlayIcon = trackItem.querySelector('[data-play-icon]');
                const currentPauseIcon = trackItem.querySelector('[data-pause-icon]');
                if (currentPlayIcon && currentPauseIcon) {
                    currentPlayIcon.classList.remove('hidden');
                    currentPauseIcon.classList.add('hidden');
                }
            });

            currentAudio.play();
        });
    });
}

function resetTrackUI(trackItem) {
    if (!trackItem) return;

    const playIcon = trackItem.querySelector('[data-play-icon]');
    const pauseIcon = trackItem.querySelector('[data-pause-icon]');
    const progressBar = trackItem.querySelector('[data-progress-bar]');

    if (playIcon) playIcon.classList.remove('hidden');
    if (pauseIcon) pauseIcon.classList.add('hidden');
    if (progressBar) progressBar.style.transform = 'scaleX(0)';
}
