<?php

namespace App\Livewire\Modals;

use LivewireUI\Modal\ModalComponent;

class TestModal extends ModalComponent
{
    public function render()
    {
        return view('livewire.modals.test-modal');
    }
}
