<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\ErrorReport;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentEntry;
use App\Models\Location;
use App\Models\Note;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Extended demo dataset for local QA/dev.
 *
 * Complements DemoInventorySeeder + DemoDashboardSeeder by seeding:
 * - Suppliers + supplier links on Products
 * - Contracts + linking some Assets
 * - Warranty supplier fields on Assets
 * - Notes + Attachments (with real files in storage)
 * - Audit logs + Error reports
 * - Soft-deleted records for Trash QA
 * - Inventory adjustment sample (for Adjustments/Kardex UI)
 *
 * This seeder is additive and safe to run multiple times.
 * It does NOT wipe data.
 */
class DemoFullSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $today = Carbon::today();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@gatic.local'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );

        // ──────────────────────────────────────────────────────────────────────
        // Suppliers
        // ──────────────────────────────────────────────────────────────────────
        $supplierComputo = Supplier::query()->updateOrCreate(
            ['name' => 'Distribuciones Computo MX'],
            [
                'contact' => 'Ventas - (55) 5555-0101',
                'notes' => 'Proveedor demo (cómputo).',
            ],
        );

        $supplierRedes = Supplier::query()->updateOrCreate(
            ['name' => 'Redes y Comunicacion SA'],
            [
                'contact' => 'Soporte - (55) 5555-0202',
                'notes' => 'Proveedor demo (redes y comunicaciones).',
            ],
        );

        $supplierConsumibles = Supplier::query()->updateOrCreate(
            ['name' => 'Suministros de Oficina Central'],
            [
                'contact' => 'Compras - (55) 5555-0303',
                'notes' => 'Proveedor demo (consumibles).',
            ],
        );

        $supplierGarantias = Supplier::query()->updateOrCreate(
            ['name' => 'Servicios de Garantia TI'],
            [
                'contact' => 'Garantias - (55) 5555-0404',
                'notes' => 'Proveedor demo (garantias/mantenimiento).',
            ],
        );

        // Soft-deleted demo record for Catalogs Trash QA.
        $trashedSupplier = Supplier::withTrashed()->firstOrCreate(
            ['name' => 'Proveedor Papelera QA'],
            [
                'contact' => 'QA - (55) 5555-9999',
                'notes' => 'Registro demo en papelera (QA).',
            ],
        );
        if ($trashedSupplier->deleted_at === null) {
            $trashedSupplier->delete();
        }

        // ──────────────────────────────────────────────────────────────────────
        // Product ↔ Supplier links (fill only if missing)
        // ──────────────────────────────────────────────────────────────────────
        $this->assignSupplierIfMissing('Laptop Dell Latitude 5540', $supplierComputo->id);
        $this->assignSupplierIfMissing('Laptop HP EliteBook 840', $supplierComputo->id);
        $this->assignSupplierIfMissing('Switch Cisco Catalyst 9200', $supplierRedes->id);
        $this->assignSupplierIfMissing('Cable UTP Cat6 (caja)', $supplierConsumibles->id);
        $this->assignSupplierIfMissing('Conectores RJ45 (paquete)', $supplierConsumibles->id);
        $this->assignSupplierIfMissing('Etiquetas Asset Tag (rollo)', $supplierConsumibles->id);

        // ──────────────────────────────────────────────────────────────────────
        // Contracts + linking Assets
        // ──────────────────────────────────────────────────────────────────────
        $contractPurchase = Contract::query()->updateOrCreate(
            ['identifier' => 'CTR-2026-001'],
            [
                'type' => Contract::TYPE_PURCHASE,
                'supplier_id' => $supplierComputo->id,
                'start_date' => $today->copy()->subDays(45)->toDateString(),
                'end_date' => $today->copy()->addDays(320)->toDateString(),
                'notes' => 'Contrato demo (compra de equipo de computo).',
            ],
        );

        $contractLease = Contract::query()->updateOrCreate(
            ['identifier' => 'CTR-2026-002'],
            [
                'type' => Contract::TYPE_LEASE,
                'supplier_id' => $supplierRedes->id,
                'start_date' => $today->copy()->subDays(10)->toDateString(),
                'end_date' => $today->copy()->addDays(720)->toDateString(),
                'notes' => 'Contrato demo (arrendamiento / servicios).',
            ],
        );

        // Link a few assets to contracts (only if not already linked)
        $this->linkAssetsIfUnlinked($contractPurchase->id, 'SN-DASH-%', 6);
        $this->linkAssetsIfUnlinked($contractLease->id, 'SN-DEMO-%', 2);

        // ──────────────────────────────────────────────────────────────────────
        // Warranty supplier fields on Assets (fill only if missing)
        // ──────────────────────────────────────────────────────────────────────
        $this->assignWarrantySupplierForProduct('Laptop Dell Latitude 5540', $supplierGarantias->id);
        $this->assignWarrantySupplierForProduct('Laptop HP EliteBook 840', $supplierGarantias->id);
        $this->assignWarrantySupplierForProduct('Switch Cisco Catalyst 9200', $supplierRedes->id);

        // ──────────────────────────────────────────────────────────────────────
        // Notes + Attachments (create real files in storage)
        // ──────────────────────────────────────────────────────────────────────
        $demoEmployee = Employee::query()->where('rpe', 'RPE-001')->first();
        $demoProduct = Product::query()->where('name', 'Laptop Dell Latitude 5540')->first();
        $demoAsset = Asset::query()
            ->where('serial', 'SN-DEMO-002')
            ->with('product')
            ->first();

        if ($demoProduct) {
            Note::query()->firstOrCreate(
                [
                    'noteable_type' => Product::class,
                    'noteable_id' => $demoProduct->id,
                    'author_user_id' => $admin->id,
                    'body' => 'DEMO-FULL: Nota de ejemplo en producto para validar panel de notas.',
                ],
            );

            $this->seedAttachment(
                attachableType: Product::class,
                attachableId: $demoProduct->id,
                uploadedByUserId: (int) $admin->id,
                originalName: 'demo-producto.txt',
                path: "attachments/seeders/Product/{$demoProduct->id}/demo-producto.txt",
                mimeType: 'text/plain',
                content: "GATIC DEMO\n\nAdjunto de ejemplo para Producto #{$demoProduct->id}.\n",
            );
        }

        if ($demoAsset) {
            Note::query()->firstOrCreate(
                [
                    'noteable_type' => Asset::class,
                    'noteable_id' => $demoAsset->id,
                    'author_user_id' => $admin->id,
                    'body' => 'DEMO-FULL: Nota de ejemplo en activo (serial) para validar trazabilidad.',
                ],
            );

            $this->seedAttachment(
                attachableType: Asset::class,
                attachableId: $demoAsset->id,
                uploadedByUserId: (int) $admin->id,
                originalName: 'demo-activo.txt',
                path: "attachments/seeders/Asset/{$demoAsset->id}/demo-activo.txt",
                mimeType: 'text/plain',
                content: "GATIC DEMO\n\nAdjunto de ejemplo para Activo #{$demoAsset->id}.\n",
            );
        }

        if ($demoEmployee) {
            Note::query()->firstOrCreate(
                [
                    'noteable_type' => Employee::class,
                    'noteable_id' => $demoEmployee->id,
                    'author_user_id' => $admin->id,
                    'body' => 'DEMO-FULL: Nota de ejemplo en empleado para validar permisos (manage).',
                ],
            );

            $this->seedAttachment(
                attachableType: Employee::class,
                attachableId: $demoEmployee->id,
                uploadedByUserId: (int) $admin->id,
                originalName: 'demo-empleado.txt',
                path: "attachments/seeders/Employee/{$demoEmployee->id}/demo-empleado.txt",
                mimeType: 'text/plain',
                content: "GATIC DEMO\n\nAdjunto de ejemplo para Empleado #{$demoEmployee->id}.\n",
            );
        }

        // ──────────────────────────────────────────────────────────────────────
        // Audit Logs (seed once)
        // ──────────────────────────────────────────────────────────────────────
        $hasDemoAuditLogs = DB::table('audit_logs')
            ->where('context->demo', 'full')
            ->exists();

        if (! $hasDemoAuditLogs) {
            $subjectAssetId = $demoAsset?->id
                ?? Asset::query()->where('serial', 'like', 'SN-DASH-%')->orderBy('id')->value('id');
            $subjectProductId = $demoProduct?->id
                ?? Product::query()->orderBy('id')->value('id');

            $now = Carbon::now();

            if ($subjectAssetId) {
                AuditLog::query()->create([
                    'created_at' => $now->copy()->subMinutes(15),
                    'actor_user_id' => (int) $admin->id,
                    'action' => AuditLog::ACTION_ASSET_ASSIGN,
                    'subject_type' => Asset::class,
                    'subject_id' => (int) $subjectAssetId,
                    'context' => [
                        'demo' => 'full',
                        'note' => 'Demo: asignacion simulada.',
                    ],
                ]);

                AuditLog::query()->create([
                    'created_at' => $now->copy()->subMinutes(10),
                    'actor_user_id' => (int) $admin->id,
                    'action' => AuditLog::ACTION_ASSET_LOAN,
                    'subject_type' => Asset::class,
                    'subject_id' => (int) $subjectAssetId,
                    'context' => [
                        'demo' => 'full',
                        'note' => 'Demo: prestamo simulado.',
                    ],
                ]);
            }

            if ($subjectProductId) {
                AuditLog::query()->create([
                    'created_at' => $now->copy()->subMinutes(5),
                    'actor_user_id' => (int) $admin->id,
                    'action' => AuditLog::ACTION_NOTE_MANUAL_CREATE,
                    'subject_type' => Product::class,
                    'subject_id' => (int) $subjectProductId,
                    'context' => [
                        'demo' => 'full',
                        'note' => 'Demo: nota manual creada (seed).',
                    ],
                ]);
            }
        }

        // ──────────────────────────────────────────────────────────────────────
        // Error Reports (seed one known ID)
        // ──────────────────────────────────────────────────────────────────────
        ErrorReport::query()->updateOrCreate(
            ['error_id' => 'DEMO-ERROR-0001'],
            [
                'environment' => app()->environment(),
                'user_id' => (int) $admin->id,
                'user_role' => $admin->role,
                'method' => 'GET',
                'url' => url('/inventory/products'),
                'route' => 'inventory.products.index',
                'exception_class' => \RuntimeException::class,
                'exception_message' => 'Error demo para validar lookup por ID.',
                'stack_trace' => "RuntimeException: Error demo\n#0 {main}",
                'context' => [
                    'demo' => 'full',
                    'request' => [
                        'path' => '/inventory/products',
                    ],
                ],
            ],
        );

        // ──────────────────────────────────────────────────────────────────────
        // Inventory Adjustment sample (Adjustments index + Kardex)
        // ──────────────────────────────────────────────────────────────────────
        $qtyProduct = Product::query()->where('name', 'Conectores RJ45 (paquete)')->first();
        if ($qtyProduct && $qtyProduct->qty_total !== null) {
            $adjustment = InventoryAdjustment::query()->firstOrCreate(
                ['reason' => 'DEMO-FULL: Ajuste de inventario (Conectores RJ45)'],
                ['actor_user_id' => (int) $admin->id],
            );

            $existingEntry = InventoryAdjustmentEntry::query()
                ->where('inventory_adjustment_id', $adjustment->id)
                ->where('subject_type', Product::class)
                ->where('subject_id', $qtyProduct->id)
                ->first();

            if (! $existingEntry) {
                $beforeQty = (int) $qtyProduct->qty_total;
                $afterQty = $beforeQty + 4;

                $qtyProduct->qty_total = $afterQty;
                $qtyProduct->save();

                InventoryAdjustmentEntry::query()->create([
                    'inventory_adjustment_id' => $adjustment->id,
                    'subject_type' => Product::class,
                    'subject_id' => $qtyProduct->id,
                    'product_id' => $qtyProduct->id,
                    'asset_id' => null,
                    'before' => ['qty_total' => $beforeQty],
                    'after' => ['qty_total' => $afterQty],
                ]);
            }
        }

        // ──────────────────────────────────────────────────────────────────────
        // Trash QA (Admin trash: products/assets/employees)
        // ──────────────────────────────────────────────────────────────────────
        $categoryConsumibles = Category::query()->firstOrCreate(
            ['name' => 'Consumibles'],
            ['is_serialized' => false, 'requires_asset_tag' => false],
        );

        $brandSamsung = Brand::query()->firstOrCreate(['name' => 'Samsung']);
        $location = Location::query()->firstOrCreate(['name' => 'Oficina Principal']);

        $trashedProduct = Product::withTrashed()->firstOrCreate(
            ['name' => 'Producto Papelera QA'],
            [
                'category_id' => $categoryConsumibles->id,
                'brand_id' => $brandSamsung->id,
                'supplier_id' => $supplierConsumibles->id,
                'qty_total' => 42,
                'low_stock_threshold' => 10,
            ],
        );
        if ($trashedProduct->deleted_at === null) {
            $trashedProduct->delete();
        }

        if ($demoProduct) {
            $trashedAsset = Asset::withTrashed()->firstOrCreate(
                ['product_id' => $demoProduct->id, 'serial' => 'SN-TRASH-001'],
                [
                    'location_id' => $location->id,
                    'asset_tag' => 'AT-TRASH-001',
                    'status' => Asset::STATUS_AVAILABLE,
                    'current_employee_id' => null,
                ],
            );

            if ($trashedAsset->deleted_at === null) {
                $trashedAsset->delete();
            }
        }

        $trashedEmployee = Employee::withTrashed()->firstOrCreate(
            ['rpe' => 'RPE-TRASH-001'],
            [
                'name' => 'Empleado Papelera QA',
                'department' => 'QA',
                'job_title' => 'Tester',
            ],
        );
        if ($trashedEmployee->deleted_at === null) {
            $trashedEmployee->delete();
        }
    }

    private function assignSupplierIfMissing(string $productName, int $supplierId): void
    {
        $product = Product::query()->where('name', $productName)->first();
        if (! $product) {
            return;
        }

        if ($product->supplier_id !== null) {
            return;
        }

        $product->supplier_id = $supplierId;
        $product->save();
    }

    private function linkAssetsIfUnlinked(int $contractId, string $serialLike, int $limit): void
    {
        $alreadyLinked = Asset::query()
            ->whereNull('deleted_at')
            ->where('contract_id', $contractId)
            ->exists();

        if ($alreadyLinked) {
            return;
        }

        $assetIds = Asset::query()
            ->whereNull('deleted_at')
            ->whereNull('contract_id')
            ->where('serial', 'like', $serialLike)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();

        if ($assetIds === []) {
            return;
        }

        Asset::query()
            ->whereIn('id', $assetIds)
            ->whereNull('contract_id')
            ->update(['contract_id' => $contractId]);
    }

    private function assignWarrantySupplierForProduct(string $productName, int $supplierId): void
    {
        $product = Product::query()->where('name', $productName)->first();
        if (! $product) {
            return;
        }

        Asset::query()
            ->whereNull('deleted_at')
            ->where('product_id', $product->id)
            ->whereNotNull('warranty_end_date')
            ->whereNull('warranty_supplier_id')
            ->update([
                'warranty_supplier_id' => $supplierId,
                'warranty_notes' => 'DEMO-FULL: Garantia asociada por seeder.',
            ]);
    }

    private function seedAttachment(
        string $attachableType,
        int $attachableId,
        int $uploadedByUserId,
        string $originalName,
        string $path,
        string $mimeType,
        string $content,
    ): void {
        $disk = 'local';

        Storage::disk($disk)->put($path, $content);

        Attachment::query()->updateOrCreate(
            [
                'attachable_type' => $attachableType,
                'attachable_id' => $attachableId,
                'path' => $path,
            ],
            [
                'uploaded_by_user_id' => $uploadedByUserId,
                'original_name' => $originalName,
                'disk' => $disk,
                'mime_type' => $mimeType,
                'size_bytes' => strlen($content),
            ],
        );
    }
}
