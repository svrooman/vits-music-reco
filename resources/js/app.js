import Sortable from 'sortablejs';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

// Import wire-elements modal
import '../../vendor/wire-elements/modal/resources/js/modal';

// Register Alpine plugins
Alpine.plugin(focus);

// Make Alpine available globally
window.Alpine = Alpine;

// Start Alpine
Alpine.start();

// Initialize Sortable when Livewire component loads
document.addEventListener('DOMContentLoaded', function() {
    initializeSortable();
});

// Re-initialize when Livewire updates the DOM
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', ({ el, component }) => {
        initializeSortable();
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
