<div class="relative" x-data="{ open: false }">
    <!-- Theme Button -->
    <button @click="open = !open" class="p-2 rounded-lg hover:bg-zinc-800 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            @if($theme === 'light')
                <!-- Sun Icon -->
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            @elseif($theme === 'dark')
                <!-- Moon Icon -->
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            @else
                <!-- System Icon -->
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            @endif
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open" @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-lg py-1 z-50">

        <button wire:click="setTheme('light')" @click="open = false"
                class="w-full text-left px-4 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors flex items-center gap-3 {{ $theme === 'light' ? 'text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Light
        </button>

        <button wire:click="setTheme('dark')" @click="open = false"
                class="w-full text-left px-4 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors flex items-center gap-3 {{ $theme === 'dark' ? 'text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            Dark
        </button>

        <button wire:click="setTheme('system')" @click="open = false"
                class="w-full text-left px-4 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors flex items-center gap-3 {{ $theme === 'system' ? 'text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            System
        </button>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('theme-changed', (event) => {
            const theme = event.theme;

            if (theme === 'light') {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.removeItem('theme');
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        });
    });
</script>
