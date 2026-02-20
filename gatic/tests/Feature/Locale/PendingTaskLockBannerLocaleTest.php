<?php

namespace Tests\Feature\Locale;

use App\Enums\PendingTaskStatus;
use App\Enums\UserRole;
use App\Models\PendingTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingTaskLockBannerLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_lock_banner_relative_time_is_spanish(): void
    {
        config(['app.locale' => 'en']);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Processing,
            'creator_user_id' => $admin->id,
            'locked_by_user_id' => $admin->id,
            'locked_at' => now()->subSeconds(14),
            'heartbeat_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $response = $this->actingAs($admin)->get('/pending-tasks/'.$task->id);

        $response->assertOk();
        $response->assertSee('Bloqueada por ti');
        $response->assertSee('desde');
        $response->assertDontSee('seconds ago');
        $response->assertDontSee('ago');
    }
}
