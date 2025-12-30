<?php

namespace Tests\Feature\Dev;

use App\Livewire\Dev\LivewireSmokeTest;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireSmokeComponentTest extends TestCase
{
    public function test_component_renders_and_increments_counter(): void
    {
        Livewire::test(LivewireSmokeTest::class)
            ->assertSet('count', 0)
            ->call('increment')
            ->assertSet('count', 1);
    }

    public function test_can_dispatch_toasts_from_livewire(): void
    {
        Livewire::test(LivewireSmokeTest::class)
            ->call('toastSuccessDemo')
            ->assertDispatched('ui:toast', type: 'success')
            ->call('toastErrorDemo')
            ->assertDispatched('ui:toast', type: 'error');
    }

    public function test_toast_undo_flow_reverts_state(): void
    {
        Livewire::test(LivewireSmokeTest::class)
            ->assertSet('toggle', false)
            ->call('toggleWithUndo')
            ->assertSet('toggle', true)
            ->assertDispatched('ui:toast', function (string $name, array $params): bool {
                return $name === 'ui:toast'
                    && ($params['action']['label'] ?? null) === 'Deshacer'
                    && ($params['action']['event'] ?? null) === 'ui:undo-toggle';
            })
            ->dispatch('ui:undo-toggle', previous: false)
            ->assertSet('toggle', false)
            ->assertDispatched('ui:toast', type: 'info');
    }
}
