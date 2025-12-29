<?php

namespace App\Livewire\Dev;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LivewireSmokeTest extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function render(): View
    {
        return view('livewire.dev.livewire-smoke-test');
    }
}
