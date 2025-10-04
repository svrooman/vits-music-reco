<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class ThemeSwitcher extends Component
{
    public $theme = 'system';

    public function mount()
    {
        $this->theme = session('theme', 'system');
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;
        session(['theme' => $theme]);

        $this->dispatch('theme-changed', theme: $theme);
    }

    public function render()
    {
        return view('livewire.theme-switcher');
    }
}
