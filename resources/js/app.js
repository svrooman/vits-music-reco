import Sortable from 'sortablejs';

// Initialize Sortable on track lists when Livewire loads
document.addEventListener('livewire:navigated', initSortable);
document.addEventListener('DOMContentLoaded', initSortable);

// Re-initialize after Livewire updates
document.addEventListener('livewire:update', initSortable);

function initSortable() {
    const trackList = document.getElementById('track-list');

    if (trackList && !trackList.classList.contains('sortable-initialized')) {
        Sortable.create(trackList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'bg-indigo-50',
            chosenClass: 'bg-indigo-100',
            dragClass: 'opacity-50',
            onEnd: function (evt) {
                // Get the new order of track indexes
                const items = trackList.querySelectorAll('.track-item');
                const newOrder = Array.from(items).map(item => parseInt(item.dataset.index));

                // Call Livewire method to update the order
                Livewire.find(trackList.closest('[wire\\:id]').getAttribute('wire:id'))
                    .call('updateTrackOrder', newOrder);
            }
        });

        trackList.classList.add('sortable-initialized');
    }
}
