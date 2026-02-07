<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings\SettingsStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ──────────────────────────────────────────────────────────────
    // RBAC (AC1)
    // ──────────────────────────────────────────────────────────────

    public function test_admin_can_access_settings_page(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);

        $this
            ->actingAs($admin)
            ->get('/admin/settings')
            ->assertOk();
    }

    public function test_editor_cannot_access_settings_page(): void
    {
        $editor = User::factory()->create(['is_active' => true, 'role' => UserRole::Editor]);

        $this
            ->actingAs($editor)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_lector_cannot_access_settings_page(): void
    {
        $lector = User::factory()->create(['is_active' => true, 'role' => UserRole::Lector]);

        $this
            ->actingAs($lector)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_settings_page(): void
    {
        $this
            ->get('/admin/settings')
            ->assertRedirect('/login');
    }

    public function test_editor_cannot_save_settings(): void
    {
        $editor = User::factory()->create(['is_active' => true, 'role' => UserRole::Editor]);

        Livewire::actingAs($editor)
            ->test(\App\Livewire\Admin\Settings\SettingsForm::class)
            ->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────
    // Settings Store (AC2)
    // ──────────────────────────────────────────────────────────────

    public function test_settings_store_returns_config_default_when_no_override(): void
    {
        $store = app(SettingsStore::class);

        $this->assertSame(
            (int) config('gatic.alerts.loans.due_soon_window_days_default'),
            $store->getInt('gatic.alerts.loans.due_soon_window_days_default')
        );
    }

    public function test_settings_store_returns_override_when_set(): void
    {
        $store = app(SettingsStore::class);

        $store->set('gatic.alerts.loans.due_soon_window_days_default', 14, null);

        $store->clearCache();

        $this->assertSame(14, $store->getInt('gatic.alerts.loans.due_soon_window_days_default'));
    }

    public function test_settings_store_forget_restores_config_default(): void
    {
        $store = app(SettingsStore::class);

        $store->set('gatic.alerts.loans.due_soon_window_days_default', 14, null);
        $store->forget('gatic.alerts.loans.due_soon_window_days_default');

        $this->assertSame(
            (int) config('gatic.alerts.loans.due_soon_window_days_default'),
            $store->getInt('gatic.alerts.loans.due_soon_window_days_default')
        );
    }

    public function test_settings_store_rejects_non_whitelisted_keys(): void
    {
        $store = app(SettingsStore::class);

        $store->set('gatic.some.random.key', 'value', null);

        $this->assertFalse($store->hasOverride('gatic.some.random.key'));
    }

    // ──────────────────────────────────────────────────────────────
    // Save & Restore (AC1, AC2)
    // ──────────────────────────────────────────────────────────────

    public function test_admin_can_save_settings(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Settings\SettingsForm::class)
            ->set('loansDueSoonDefault', 14)
            ->call('save')
            ->assertHasNoErrors();

        $store = app(SettingsStore::class);
        $store->clearCache();

        $this->assertSame(14, $store->getInt('gatic.alerts.loans.due_soon_window_days_default'));
    }

    public function test_admin_can_restore_defaults(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);

        $store = app(SettingsStore::class);
        $store->set('gatic.alerts.loans.due_soon_window_days_default', 14, $admin->id);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Settings\SettingsForm::class)
            ->call('restoreDefaults')
            ->assertHasNoErrors();

        $store->clearCache();

        $this->assertSame(0, Setting::query()->count());
        $this->assertSame(
            (int) config('gatic.alerts.loans.due_soon_window_days_default'),
            $store->getInt('gatic.alerts.loans.due_soon_window_days_default')
        );
    }

    // ──────────────────────────────────────────────────────────────
    // Window Days override in alerts (AC3)
    // ──────────────────────────────────────────────────────────────

    public function test_loan_alerts_use_overridden_window_days(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 12, 0, 0));

        $store = app(SettingsStore::class);
        $store->set('gatic.alerts.loans.due_soon_window_days_default', 30, null);

        $admin = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $employee = Employee::factory()->create();
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        // Asset due in 20 days: within 30-day window but outside 7-day default
        $within = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'current_employee_id' => $employee->id,
            'status' => Asset::STATUS_LOANED,
            'serial' => 'SER-WITHIN-OVERRIDE',
            'loan_due_date' => Carbon::today()->addDays(20),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/alerts/loans?type=due-soon');

        $response->assertOk();
        $response->assertSee($within->serial);
    }

    // ──────────────────────────────────────────────────────────────
    // Currency override (AC4)
    // ──────────────────────────────────────────────────────────────

    public function test_dashboard_uses_overridden_currency(): void
    {
        config()->set('gatic.inventory.money.allowed_currencies', ['MXN', 'USD']);
        config()->set('gatic.inventory.money.default_currency', 'USD');

        $store = app(SettingsStore::class);
        $store->set('gatic.inventory.money.default_currency', 'MXN', null);

        $admin = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);

        $response = $this
            ->actingAs($admin)
            ->get('/dashboard');

        $response->assertOk();
        // Dashboard should load without errors with the override in place
        $response->assertSee('MXN');
    }

    // ──────────────────────────────────────────────────────────────
    // Audit (AC5)
    // ──────────────────────────────────────────────────────────────

    public function test_saving_settings_creates_audit_log(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Settings\SettingsForm::class)
            ->set('loansDueSoonDefault', 14)
            ->call('save')
            ->assertHasNoErrors();

        $audit = AuditLog::query()
            ->where('action', AuditLog::ACTION_SETTINGS_UPDATE)
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($admin->id, $audit->actor_user_id);
        $this->assertIsArray($audit->context);
        $this->assertArrayHasKey('changed_keys', $audit->context);
    }

    // ──────────────────────────────────────────────────────────────
    // Sidebar navigation
    // ──────────────────────────────────────────────────────────────

    public function test_admin_sees_settings_link_in_sidebar(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);

        $response = $this
            ->actingAs($admin)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Configuración');
        $response->assertSee(route('admin.settings.index'));
    }

    public function test_editor_does_not_see_settings_link_in_sidebar(): void
    {
        $editor = User::factory()->create(['is_active' => true, 'role' => UserRole::Editor]);

        $response = $this
            ->actingAs($editor)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee(route('admin.settings.index'));
    }

    // ──────────────────────────────────────────────────────────────
    // Soft-delete regression: LoanAlertsIndex excludes soft-deleted
    // ──────────────────────────────────────────────────────────────

    public function test_loan_alerts_do_not_show_soft_deleted_assets(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 12, 0, 0));

        $admin = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $employee = Employee::factory()->create();
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $overdue = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'current_employee_id' => $employee->id,
            'status' => Asset::STATUS_LOANED,
            'serial' => 'SER-OVERDUE-DELETED',
            'loan_due_date' => Carbon::today()->subDay(),
        ]);

        $overdue->delete();

        $response = $this
            ->actingAs($admin)
            ->get('/alerts/loans?type=overdue');

        $response->assertOk();
        $response->assertDontSee('SER-OVERDUE-DELETED');
    }
}
