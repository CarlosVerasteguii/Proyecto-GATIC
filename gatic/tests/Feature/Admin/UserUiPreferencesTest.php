<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Livewire\Admin\Users\UserForm;
use App\Models\User;
use App\Models\UserSetting;
use App\Support\Settings\UserSettingsStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserUiPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_and_lector_get_forbidden_for_admin_users_routes(): void
    {
        $editor = User::factory()->create([
            'role' => UserRole::Editor,
            'is_active' => true,
        ]);
        $lector = User::factory()->create([
            'role' => UserRole::Lector,
            'is_active' => true,
        ]);
        $target = User::factory()->create();

        $this->actingAs($editor)->get('/admin/users')->assertForbidden();
        $this->actingAs($editor)->get("/admin/users/{$target->id}/edit")->assertForbidden();

        $this->actingAs($lector)->get('/admin/users')->assertForbidden();
        $this->actingAs($lector)->get("/admin/users/{$target->id}/edit")->assertForbidden();
    }

    public function test_authenticated_user_can_persist_valid_ui_preferences(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Editor,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('me.ui-preferences.update'), [
                'key' => 'ui.theme',
                'value' => 'dark',
            ])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $setting = UserSetting::query()
            ->where('user_id', $user->id)
            ->where('key', 'ui.theme')
            ->first();

        $this->assertNotNull($setting);
        $this->assertSame('dark', $setting->value);
    }

    public function test_ui_preferences_endpoint_rejects_invalid_keys_and_values(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Editor,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('me.ui-preferences.update'), [
                'key' => 'ui.not-allowed',
                'value' => 'x',
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('me.ui-preferences.update'), [
                'key' => 'ui.theme',
                'value' => 'blue',
            ])
            ->assertStatus(422);

        $this->assertSame(0, UserSetting::query()->count());
    }

    public function test_authenticated_user_can_persist_column_manager_hidden_columns(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Editor,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('me.ui-preferences.update'), [
                'key' => 'ui.columns.admin-users',
                'value' => ['email', 'role'],
            ])
            ->assertOk();

        $setting = UserSetting::query()
            ->where('user_id', $user->id)
            ->where('key', 'ui.columns.admin-users')
            ->first();

        $this->assertNotNull($setting);
        $this->assertSame(['email', 'role'], $setting->value);
    }

    public function test_admin_can_reset_ui_preferences_from_user_form(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => UserRole::Editor,
            'is_active' => true,
        ]);

        UserSetting::query()->create([
            'user_id' => $target->id,
            'key' => 'ui.theme',
            'value' => 'dark',
            'updated_by_user_id' => $target->id,
        ]);

        UserSetting::query()->create([
            'user_id' => $target->id,
            'key' => 'ui.columns.admin-users',
            'value' => ['email'],
            'updated_by_user_id' => $target->id,
        ]);

        Livewire::actingAs($admin)
            ->test(UserForm::class, ['user' => (string) $target->id])
            ->call('resetUiPreferences')
            ->assertHasNoErrors();

        $this->assertSame(
            0,
            UserSetting::query()->where('user_id', $target->id)->where('key', 'like', 'ui.%')->count()
        );
    }

    public function test_user_settings_store_bootstrap_preferences_respects_json_casts(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Editor,
            'is_active' => true,
        ]);

        UserSetting::query()->create([
            'user_id' => $user->id,
            'key' => 'ui.theme',
            'value' => 'dark',
            'updated_by_user_id' => $user->id,
        ]);
        UserSetting::query()->create([
            'user_id' => $user->id,
            'key' => 'ui.density',
            'value' => 'compact',
            'updated_by_user_id' => $user->id,
        ]);
        UserSetting::query()->create([
            'user_id' => $user->id,
            'key' => 'ui.sidebar_collapsed',
            'value' => true,
            'updated_by_user_id' => $user->id,
        ]);
        UserSetting::query()->create([
            'user_id' => $user->id,
            'key' => 'ui.columns.admin-users',
            'value' => ['email', 'role'],
            'updated_by_user_id' => $user->id,
        ]);

        $prefs = app(UserSettingsStore::class)->getBootstrapPreferencesForUser($user->id);

        $this->assertSame('dark', $prefs['theme'] ?? null);
        $this->assertSame('compact', $prefs['density'] ?? null);
        $this->assertSame(true, $prefs['sidebarCollapsed'] ?? null);
        $this->assertSame(['email', 'role'], $prefs['columns']['admin-users'] ?? null);
    }
}
