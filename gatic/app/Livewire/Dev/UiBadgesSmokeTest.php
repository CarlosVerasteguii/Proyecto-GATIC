<?php

namespace App\Livewire\Dev;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UiBadgesSmokeTest extends Component
{
    public function render(): View
    {
        return view('livewire.dev.ui-badges-smoke-test');
    }
}

