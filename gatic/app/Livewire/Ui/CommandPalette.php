<?php

namespace App\Livewire\Ui;

use App\Models\Asset;
use App\Models\Employee;
use App\Models\PendingTask;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CommandPalette extends Component
{
    public string $query = '';

    #[On('ui:command-palette-reset')]
    public function resetPalette(): void
    {
        $this->query = '';
    }

    /**
     * @return list<array{label: string, items: list<array{label: string, description: string|null, url: string, icon: string|null}>}>
     */
    #[Computed]
    public function groups(): array
    {
        if (! Auth::check()) {
            return [];
        }

        $user = Auth::user();
        $query = trim($this->query);

        $groups = [];

        // ===== Navigation (always safe) =====
        $nav = [
            $this->item('Dashboard', route('dashboard'), 'bi-speedometer2', null),
        ];

        if ($user->can('inventory.view')) {
            $nav[] = $this->item('Inventario: Productos', route('inventory.products.index'), 'bi-box-seam', null);
            $nav[] = $this->item('Inventario: Búsqueda', route('inventory.search'), 'bi-search', null);
        }

        if ($user->can('inventory.manage')) {
            $nav[] = $this->item('Empleados', route('employees.index'), 'bi-people', null);
            $nav[] = $this->item('Tareas pendientes', route('pending-tasks.index'), 'bi-list-check', null);
        }

        if ($user->can('catalogs.manage')) {
            $nav[] = $this->item('Catálogos: Categorías', route('catalogs.categories.index'), 'bi-tags', null);
            $nav[] = $this->item('Catálogos: Marcas', route('catalogs.brands.index'), 'bi-badge-tm', null);
            $nav[] = $this->item('Catálogos: Ubicaciones', route('catalogs.locations.index'), 'bi-geo-alt', null);
        }

        if ($user->can('users.manage')) {
            $nav[] = $this->item('Admin: Usuarios', route('admin.users.index'), 'bi-shield-lock', null);
        }

        if ($user->can('admin-only')) {
            $nav[] = $this->item('Admin: Configuración', route('admin.settings.index'), 'bi-gear', null);
        }

        $groups[] = [
            'label' => 'Navegación',
            'items' => $nav,
        ];

        // ===== Create shortcuts =====
        $create = [];

        if ($user->can('inventory.manage')) {
            $create[] = $this->item('Crear producto', route('inventory.products.create'), 'bi-plus-lg', 'Alta de producto');
        }

        if ($user->can('inventory.manage')) {
            $create[] = $this->item('Crear tarea pendiente', route('pending-tasks.create'), 'bi-plus-square', 'Nueva tarea para procesar');
        }

        if ($user->can('users.manage')) {
            $create[] = $this->item('Crear usuario', route('admin.users.create'), 'bi-person-plus', 'Acceso al sistema');
        }

        if ($create !== []) {
            $groups[] = [
                'label' => 'Crear',
                'items' => $create,
            ];
        }

        // ===== Query-driven commands =====
        if ($query !== '') {
            $search = [];

            if ($user->can('inventory.view')) {
                $search[] = $this->item(
                    "Buscar inventario: \"{$query}\"",
                    route('inventory.search', ['q' => $query]),
                    'bi-search',
                    null
                );
            }

            if ($search !== []) {
                $groups[] = [
                    'label' => 'Buscar',
                    'items' => $search,
                ];
            }

            // Exact matches (jump)
            $jumpInventory = [];
            if ($user->can('inventory.view')) {
                $serial = Asset::normalizeSerial($query);
                $assetTag = Asset::normalizeAssetTag($query);

                if ($serial !== null || $assetTag !== null) {
                    $assets = Asset::query()
                        ->where(function ($q) use ($serial, $assetTag): void {
                            if ($serial !== null) {
                                $q->orWhere('serial', $serial);
                            }
                            if ($assetTag !== null) {
                                $q->orWhere('asset_tag', $assetTag);
                            }
                        })
                        ->with('product:id,name')
                        ->limit(5)
                        ->get();

                    foreach ($assets as $asset) {
                        $jumpInventory[] = $this->item(
                            "Activo: {$asset->serial}",
                            route('inventory.products.assets.show', ['product' => $asset->product_id, 'asset' => $asset->id]),
                            'bi-hdd',
                            $asset->product?->name
                        );
                    }
                }
            }

            if ($jumpInventory !== []) {
                $groups[] = [
                    'label' => 'Inventario (match exacto)',
                    'items' => $jumpInventory,
                ];
            }

            $jumpEmployees = [];
            if ($user->can('inventory.manage')) {
                $employee = Employee::query()
                    ->where('rpe', $query)
                    ->limit(1)
                    ->first();

                if ($employee) {
                    $jumpEmployees[] = $this->item(
                        "Empleado: {$employee->rpe}",
                        route('employees.show', ['employee' => $employee->id]),
                        'bi-person-badge',
                        $employee->name
                    );
                }
            }

            if ($jumpEmployees !== []) {
                $groups[] = [
                    'label' => 'Empleados (match exacto)',
                    'items' => $jumpEmployees,
                ];
            }

            $jumpTasks = [];
            if ($user->can('inventory.manage') && ctype_digit($query)) {
                $taskId = (int) $query;
                if ($taskId > 0) {
                    $task = PendingTask::query()->find($taskId);
                    if ($task) {
                        $jumpTasks[] = $this->item(
                            "Tarea pendiente #{$task->id}",
                            route('pending-tasks.show', $task->id),
                            'bi-list-check',
                            $task->type->label()
                        );
                    }
                }
            }

            if ($jumpTasks !== []) {
                $groups[] = [
                    'label' => 'Tareas pendientes (match exacto)',
                    'items' => $jumpTasks,
                ];
            }
        }

        return $groups;
    }

    /**
     * @return array{label: string, description: string|null, url: string, icon: string|null}
     */
    private function item(string $label, string $url, ?string $icon, ?string $description): array
    {
        return [
            'label' => $label,
            'description' => $description,
            'url' => $url,
            'icon' => $icon,
        ];
    }

    public function render(): View
    {
        return view('livewire.ui.command-palette');
    }
}
