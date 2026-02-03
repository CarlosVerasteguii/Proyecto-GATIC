<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LoanAlertsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_unauthenticated_user_cannot_access_loan_alerts(): void
    {
        $this
            ->get('/alerts/loans')
            ->assertRedirect('/login');
    }

    public function test_user_without_inventory_manage_cannot_access_loan_alerts(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Lector]);

        $this
            ->actingAs($user)
            ->get('/alerts/loans')
            ->assertForbidden();
    }

    public function test_overdue_filter_shows_only_overdue_loans(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
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
            'serial' => 'SER-OVERDUE',
            'loan_due_date' => Carbon::today()->subDay(),
        ]);

        $dueSoon = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'current_employee_id' => $employee->id,
            'status' => Asset::STATUS_LOANED,
            'serial' => 'SER-DUE-SOON',
            'loan_due_date' => Carbon::today()->addDays(3),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/loans?type=overdue');

        $response->assertOk();
        $response->assertSee($overdue->serial);
        $response->assertDontSee($dueSoon->serial);
    }

    public function test_due_soon_filter_respects_window_days(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        config([
            'gatic.alerts.loans.due_soon_window_days_default' => 7,
            'gatic.alerts.loans.due_soon_window_days_options' => [7, 14, 30],
        ]);

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $employee = Employee::factory()->create();
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $within = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'current_employee_id' => $employee->id,
            'status' => Asset::STATUS_LOANED,
            'serial' => 'SER-WITHIN',
            'loan_due_date' => Carbon::today()->addDays(7),
        ]);

        $outside = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'current_employee_id' => $employee->id,
            'status' => Asset::STATUS_LOANED,
            'serial' => 'SER-OUTSIDE',
            'loan_due_date' => Carbon::today()->addDays(8),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/loans?type=due-soon&windowDays=7');

        $response->assertOk();
        $response->assertSee($within->serial);
        $response->assertDontSee($outside->serial);
    }
}
