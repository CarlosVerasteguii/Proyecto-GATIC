<?php

namespace Tests\Feature\Dev;

use App\Livewire\Dev\LivewireSmokeTest;
use Illuminate\Support\Carbon;
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

    public function test_poll_tick_increments_counter_and_updates_freshness_timestamp(): void
    {
        $first = Carbon::parse('2025-01-01T00:00:00Z');
        Carbon::setTestNow($first);

        $component = Livewire::test(LivewireSmokeTest::class)
            ->assertSet('pollCount', 0)
            ->assertSet('lastUpdatedAtIso', $first->toIso8601String());

        $second = $first->copy()->addSeconds(10);
        Carbon::setTestNow($second);

        $component
            ->call('pollTick')
            ->assertSet('pollCount', 1)
            ->assertSet('lastUpdatedAtIso', $second->toIso8601String());

        Carbon::setTestNow();
    }
}
