{{--
    Quick Action Dropdown Component
    --------------------------------
    Dropdown menu for quick actions on asset rows based on current status.

    Usage:
        <x-ui.quick-action-dropdown
            :asset="$asset"
            :productId="$product->id"
        />

    Props:
        - asset: The Asset model instance
        - productId: The product ID for route generation
        - size: Button size ('sm' or regular, default: 'sm')
--}}
@props([
    'asset',
    'productId',
    'size' => 'sm',
])

@php
    use App\Support\Assets\AssetStatusTransitions;
    use App\Models\Asset;
    use Illuminate\Support\Facades\Route;

    $canAssign = AssetStatusTransitions::canAssign($asset->status);
    $canLoan = AssetStatusTransitions::canLoan($asset->status);
    $canReturn = AssetStatusTransitions::canReturn($asset->status);
    $canUnassign = Route::has('inventory.products.assets.unassign') && AssetStatusTransitions::canUnassign($asset->status);

    $hasActions = $canAssign || $canLoan || $canReturn || $canUnassign;

    $btnSize = $size === 'sm' ? 'btn-sm' : '';
@endphp

@can('inventory.manage')
    @if($hasActions)
        <div class="dropdown d-inline-block">
            <button
                type="button"
                class="btn {{ $btnSize }} btn-outline-primary dropdown-toggle"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                aria-label="Acciones rapidas para este activo"
                style="min-width: 44px; min-height: 44px; padding: 0.5rem 0.75rem;"
            >
                <i class="bi bi-lightning-charge me-1" aria-hidden="true"></i>
                <span>Acciones</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @if($canAssign)
                    <li>
                        <a
                            class="dropdown-item py-2"
                            href="{{ route('inventory.products.assets.assign', ['product' => $productId, 'asset' => $asset->id]) }}"
                            style="min-height: 44px; display: flex; align-items: center;"
                        >
                            <i class="bi bi-person-check me-2 text-success" aria-hidden="true"></i>
                            Asignar a empleado
                        </a>
                    </li>
                @endif

                @if($canLoan)
                    <li>
                        <a
                            class="dropdown-item py-2"
                            href="{{ route('inventory.products.assets.loan', ['product' => $productId, 'asset' => $asset->id]) }}"
                            style="min-height: 44px; display: flex; align-items: center;"
                        >
                            <i class="bi bi-box-arrow-up-right me-2 text-info" aria-hidden="true"></i>
                            Prestar
                        </a>
                    </li>
                @endif

                @if($canReturn)
                    <li>
                        <a
                            class="dropdown-item py-2"
                            href="{{ route('inventory.products.assets.return', ['product' => $productId, 'asset' => $asset->id]) }}"
                            style="min-height: 44px; display: flex; align-items: center;"
                        >
                            <i class="bi bi-arrow-return-left me-2 text-warning" aria-hidden="true"></i>
                            Devolver
                        </a>
                    </li>
                @endif

                @if($canUnassign)
                    <li>
                        <a
                            class="dropdown-item py-2"
                            href="{{ route('inventory.products.assets.unassign', ['product' => $productId, 'asset' => $asset->id]) }}"
                            style="min-height: 44px; display: flex; align-items: center;"
                        >
                            <i class="bi bi-person-x me-2 text-danger" aria-hidden="true"></i>
                            Desasignar
                        </a>
                    </li>
                @endif

                <li><hr class="dropdown-divider"></li>
                <li>
                    <a
                        class="dropdown-item py-2"
                        href="{{ route('inventory.products.assets.show', ['product' => $productId, 'asset' => $asset->id]) }}"
                        style="min-height: 44px; display: flex; align-items: center;"
                    >
                        <i class="bi bi-eye me-2" aria-hidden="true"></i>
                        Ver detalle
                    </a>
                </li>
            </ul>
        </div>
    @endif
@endcan
