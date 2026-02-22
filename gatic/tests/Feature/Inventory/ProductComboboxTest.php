<?php

namespace Tests\Feature\Inventory;

use App\Actions\Products\SearchProducts;
use App\Enums\UserRole;
use App\Livewire\Ui\ProductCombobox;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ProductComboboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        Product::factory()->create([
            'name' => 'Laptop Elite',
            'category_id' => $category->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductCombobox::class)
            ->set('search', 'Lap')
            ->assertSee('Laptop Elite')
            ->assertSee('Serializado');
    }

    public function test_editor_can_search_products(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $category = Category::factory()->create(['is_serialized' => false]);
        Product::factory()->create([
            'name' => 'Cable HDMI',
            'category_id' => $category->id,
        ]);

        Livewire::actingAs($editor)
            ->test(ProductCombobox::class)
            ->set('search', 'Cab')
            ->assertSee('Cable HDMI')
            ->assertSee('Por cantidad');
    }

    public function test_shows_min_chars_hint_when_search_has_less_than_two_characters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        Product::factory()->create([
            'name' => 'Laptop Pro',
            'category_id' => $category->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductCombobox::class)
            ->set('search', 'L')
            ->assertSee('Escribe al menos 2 caracteres.')
            ->assertDontSee('Laptop Pro');
    }

    public function test_search_results_are_limited_to_ten_items(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => false]);

        for ($i = 1; $i <= 15; $i++) {
            Product::factory()->create([
                'name' => 'Producto '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'category_id' => $category->id,
            ]);
        }

        $component = Livewire::actingAs($admin)
            ->test(ProductCombobox::class)
            ->set('search', 'Producto');

        $this->assertSame(10, substr_count($component->html(), 'role="option"'));
    }

    public function test_soft_deleted_products_and_categories_are_excluded_from_suggestions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $activeCategory = Category::factory()->create(['is_serialized' => true]);
        Product::factory()->create([
            'name' => 'Producto Activo',
            'category_id' => $activeCategory->id,
        ]);

        $deletedProduct = Product::factory()->create([
            'name' => 'Producto Eliminado',
            'category_id' => $activeCategory->id,
        ]);
        $deletedProduct->delete();

        $deletedCategory = Category::factory()->create(['is_serialized' => false]);
        $deletedCategory->delete();
        Product::factory()->create([
            'name' => 'Producto Categoria Eliminada',
            'category_id' => $deletedCategory->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductCombobox::class)
            ->set('search', 'Producto')
            ->assertSee('Producto Activo')
            ->assertDontSee('Producto Eliminado')
            ->assertDontSee('Producto Categoria Eliminada');
    }

    public function test_shows_create_product_cta_with_return_to_and_prefill_when_no_results(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(ProductCombobox::class, ['returnTo' => '/pending-tasks?tab=draft'])
            ->set('search', 'Producto Nuevo')
            ->assertSee('Crear producto “Producto Nuevo”')
            ->assertSee('returnTo=%2Fpending-tasks%3Ftab%3Ddraft')
            ->assertSee('prefill=Producto%20Nuevo');
    }

    public function test_autoselects_created_product_from_query_param_and_dispatches_info_toast(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $product = Product::factory()->create([
            'name' => 'Producto Recien Creado',
            'category_id' => $category->id,
        ]);

        Livewire::withQueryParams(['created_id' => (string) $product->id])
            ->actingAs($admin)
            ->test(ProductCombobox::class)
            ->assertSet('productId', $product->id)
            ->assertSet('productLabel', 'Producto Recien Creado')
            ->assertDispatched('ui:toast', type: 'info');
    }

    public function test_autoselects_created_product_from_referer_header_during_livewire_request(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $product = Product::factory()->create([
            'name' => 'Producto Desde Referer',
            'category_id' => $category->id,
        ]);

        Livewire::withHeaders([
            'X-Livewire' => 'true',
            'referer' => 'http://localhost/pending-tasks?tab=draft&created_id='.$product->id,
        ])->actingAs($admin)
            ->test(ProductCombobox::class)
            ->assertSet('productId', $product->id)
            ->assertSet('productLabel', 'Producto Desde Referer')
            ->assertDispatched('ui:toast', type: 'info');
    }

    public function test_create_product_cta_uses_browser_current_from_referer_in_livewire_requests(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::withHeaders([
            'X-Livewire' => 'true',
            'referer' => 'http://localhost/pending-tasks?tab=draft&created_id=999',
        ])->actingAs($admin)
            ->test(ProductCombobox::class)
            ->set('search', 'Producto Nuevo')
            ->assertSee('Crear producto “Producto Nuevo”')
            ->assertSee('returnTo=%2Fpending-tasks%3Ftab%3Ddraft')
            ->assertSee('prefill=Producto%20Nuevo')
            ->assertDontSee('created_id=999');
    }

    public function test_lector_cannot_execute_product_combobox_actions(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector);

        $component = new ProductCombobox;

        foreach (['mount', 'updatedSearch', 'clearSelection', 'closeDropdown', 'retrySearch'] as $method) {
            try {
                $component->{$method}();
                $this->fail("Expected AuthorizationException for {$method}().");
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }

        try {
            $component->selectProduct(1);
            $this->fail('Expected AuthorizationException for selectProduct().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_multiple_instances_have_unique_aria_ids(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $html = Blade::render('<livewire:ui.product-combobox /><livewire:ui.product-combobox />');

        preg_match_all('/aria-controls=\"(product-listbox-[^\"]+)\"/', $html, $ariaControlsMatches);
        preg_match_all('/id=\"(product-listbox-[^\"]+)\"/', $html, $listboxIdMatches);

        $this->assertCount(2, $ariaControlsMatches[1]);
        $this->assertCount(2, $listboxIdMatches[1]);
        $this->assertNotSame($ariaControlsMatches[1][0], $ariaControlsMatches[1][1]);
        $this->assertNotSame($listboxIdMatches[1][0], $listboxIdMatches[1][1]);
        $this->assertSame($ariaControlsMatches[1][0], $listboxIdMatches[1][0]);
        $this->assertSame($ariaControlsMatches[1][1], $listboxIdMatches[1][1]);
    }

    public function test_unexpected_search_error_reports_error_id_and_keeps_ui_usable(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->app->bind(SearchProducts::class, static function () {
            return new class
            {
                public function execute(mixed ...$args): void
                {
                    throw new RuntimeException('Search failure');
                }
            };
        });

        $component = Livewire::actingAs($admin)
            ->test(ProductCombobox::class)
            ->set('search', 'Prod')
            ->assertSee('Ocurrió un error inesperado.');

        $this->assertNotNull($component->get('errorId'));

        $component
            ->call('retrySearch')
            ->assertSet('showDropdown', true);
    }
}
