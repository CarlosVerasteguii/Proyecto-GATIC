<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardQuickActionsWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_quick_actions_widget_shows_manage_actions_for_admin(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Admin,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('data-testid="dashboard-quick-actions"', false);

        $response->assertSee('Carga rápida');
        $response->assertSee('Retiro rápido');
        $response->assertSee('Tareas pendientes');
        $response->assertSee('Crear producto');

        $content = (string) $response->getContent();
        $this->assertStringContainsString(route('pending-tasks.index'), $content);
        $this->assertStringContainsString(route('inventory.products.create'), $content);
    }

    public function test_dashboard_quick_actions_widget_hides_manage_actions_for_lector(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Lector,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('data-testid="dashboard-quick-actions"', false);

        $response->assertDontSee('Carga rápida');
        $response->assertDontSee('Retiro rápido');
        $response->assertDontSee('Tareas pendientes');
        $response->assertDontSee('Crear producto');

        $content = (string) $response->getContent();
        $this->assertStringNotContainsString(route('pending-tasks.index'), $content);
        $this->assertStringNotContainsString(route('inventory.products.create'), $content);
        $this->assertStringContainsString(route('inventory.search'), $content);
        $this->assertStringContainsString(route('inventory.assets.index'), $content);
        $this->assertStringContainsString(route('inventory.products.index'), $content);
    }
}
