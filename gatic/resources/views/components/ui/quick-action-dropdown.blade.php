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

    $canAssign = AssetStatusTransitions::canAssign($asset->status);
    $canLoan = AssetStatusTransitions::canLoan($asset->status);
    $canReturn = AssetStatusTransitions::canReturn($asset->status);
    $canUnassign = AssetStatusTransitions::canUnassign($asset->status);

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
                aria-label="Acciones rápidas"
            >
                <i class="bi bi-lightning-charge" aria-hidden="true"></i>
                <span class="visually-hidden">Acciones</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @if($canAssign)
                    <li>
                        <a
                            class="dropdown-item"
                            href="{{ route('inventory.products.assets.assign', ['product' => $productId, 'asset' => $asset->id]) }}"
                        >
                            <i class="bi bi-person-check me-2 text-success" aria-hidden="true"></i>
                            Asignar
                        </a>
                    </li>
                @endif

                @if($canLoan)
                    <li>
                        <a
                            class="dropdown-item"
                            href="{{ route('inventory.products.assets.loan', ['product' => $productId, 'asset' => $asset->id]) }}"
                        >
                            <i class="bi bi-box-arrow-up-right me-2 text-info" aria-hidden="true"></i>
                            Prestar
                        </a>
                    </li>
                @endif

                @if($canReturn)
                    <li>
                        <a
                            class="dropdown-item"
                            href="{{ route('inventory.products.assets.return', ['product' => $productId, 'asset' => $asset->id]) }}"
                        >
                            <i class="bi bi-arrow-return-left me-2 text-warning" aria-hidden="true"></i>
                            Devolver
                        </a>
                    </li>
                @endif

                @if($canUnassign)
                    <li>
                        <a
                            class="dropdown-item"
                            href="{{ route('inventory.products.assets.return', ['product' => $productId, 'asset' => $asset->id]) }}"
                        >
                            <i class="bi bi-person-x me-2 text-danger" aria-hidden="true"></i>
                            Desasignar
                        </a>
                    </li>
                @endif

                @if($canAssign || $canLoan)
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a
                            class="dropdown-item"
                            href="{{ route('inventory.products.assets.show', ['product' => $productId, 'asset' => $asset->id]) }}"
                        >
                            <i class="bi bi-eye me-2" aria-hidden="true"></i>
                            Ver detalle
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    @else
        {{-- No actions available for this status --}}
        <span class="text-muted small" title="Sin acciones disponibles para este estado">
            <i class="bi bi-dash" aria-hidden="true"></i>
        </span>
    @endif
@endcan
