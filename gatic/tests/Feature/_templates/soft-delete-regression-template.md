# Soft-Delete Regression Test Template

## Propósito

Este template proporciona un patrón reutilizable para crear tests de regresión que verifican que los registros soft-deleted NO aparecen en conteos, listados o queries.

**Lección aprendida:** Epic 6 Story 6.2 detectó que conteos de `assets_total`/`assets_unavailable` incluían registros con `deleted_at` (bug HIGH corregido en code review).

## Cuándo usar este template

- ✅ Stories que tocan conteos o agregaciones sobre modelos con soft-delete
- ✅ Stories que implementan listados o búsquedas
- ✅ Stories que implementan filtros sobre modelos con soft-delete
- ✅ Stories que calculan disponibilidad, totales o métricas

## Modelos con Soft-Delete en GATIC

| Modelo | Trait | Campos afectados |
|--------|-------|------------------|
| Asset | SoftDeletes | assets_total, assets_unavailable, disponibilidad |
| Product | SoftDeletes | conteos de productos, inventario |
| Category | SoftDeletes | filtros por categoría |
| Brand | SoftDeletes | filtros por marca |
| Location | SoftDeletes | filtros por ubicación |
| Employee | SoftDeletes | búsquedas, asignaciones |

## Patrón de Test

```php
<?php

namespace Tests\Feature\{Namespace};

use App\Models\{Model};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {Feature}SoftDeleteRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_{feature}_excludes_soft_deleted_records(): void
    {
        // Arrange: Usuario con permisos
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Arrange: Registros activos
        $activeRecord = {Model}::factory()->create([
            // campos relevantes
        ]);

        // Arrange: Registro soft-deleted (CRÍTICO)
        $deletedRecord = {Model}::factory()->create([
            // campos relevantes
        ]);
        $deletedRecord->delete(); // Soft-delete

        // Act: Ejecutar la acción que se está testeando
        $response = $this->actingAs($user)
            ->get(route('{route.name}'));

        // Assert: El registro activo SÍ aparece
        $response->assertSee($activeRecord->{campo_visible});

        // Assert: El registro soft-deleted NO aparece (REGRESIÓN)
        $response->assertDontSee($deletedRecord->{campo_visible});
    }

    public function test_{feature}_count_excludes_soft_deleted_records(): void
    {
        // Arrange: Usuario con permisos
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Arrange: 3 registros activos
        {Model}::factory()->count(3)->create();

        // Arrange: 2 registros soft-deleted (NO deben contar)
        $deleted1 = {Model}::factory()->create();
        $deleted2 = {Model}::factory()->create();
        $deleted1->delete();
        $deleted2->delete();

        // Act: Obtener conteo
        $response = $this->actingAs($user)
            ->get(route('{route.name}'));

        // Assert: El conteo es 3, NO 5 (REGRESIÓN)
        $response->assertSee('Total: 3'); // Ajustar según UI
    }
}
```

## Ejemplo Real: Assets en ProductsIndex (Epic 6.2)

```php
public function test_products_index_availability_excludes_soft_deleted_assets(): void
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    // Producto serializado
    $product = Product::factory()->create(['is_serialized' => true]);

    // 2 activos activos (disponibles)
    Asset::factory()->count(2)->create([
        'product_id' => $product->id,
        'status' => 'Disponible',
    ]);

    // 1 activo soft-deleted (NO debe contar)
    $deletedAsset = Asset::factory()->create([
        'product_id' => $product->id,
        'status' => 'Disponible',
    ]);
    $deletedAsset->delete();

    $response = $this->actingAs($user)
        ->get(route('inventory.products.index'));

    // El producto debe mostrar 2 disponibles, NO 3
    $response->assertSee('2'); // Disponibles
    $response->assertDontSee('3'); // Regresión si aparece 3
}
```

## Checklist de Implementación

- [ ] Identificar modelos con soft-delete involucrados en la story
- [ ] Crear test que verifique exclusión en listados
- [ ] Crear test que verifique exclusión en conteos/agregaciones
- [ ] Crear test que verifique exclusión en búsquedas
- [ ] Ejecutar tests y confirmar que pasan
- [ ] Documentar en Dev Notes de la story

## Referencias

- Epic 6 Retrospectiva: `_bmad-output/implementation-artifacts/epic-6-retro-2026-01-17.md`
- Hallazgo original: Story 6.2 code review (HIGH - soft-delete counts)
- Patrón de fix: `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
