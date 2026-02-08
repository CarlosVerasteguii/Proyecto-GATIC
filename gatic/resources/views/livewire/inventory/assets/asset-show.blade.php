<div class="container position-relative">
    @php
        $returnQuery = array_filter(
            request()->only(['q', 'page']),
            static fn ($value): bool => $value !== null && $value !== ''
        );
    @endphp
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            {{-- Detail Header --}}
            <x-ui.detail-header :title="$asset->serial" :subtitle="$product->name">
                <x-slot:breadcrumbs>
                    @if (is_string($returnTo) && $returnTo !== '')
                        <x-ui.breadcrumbs :items="[
                            ['label' => 'Inicio', 'url' => route('dashboard')],
                            ['label' => 'Activos', 'url' => $returnTo],
                            ['label' => $asset->serial, 'url' => null],
                        ]" />
                    @else
                        <x-ui.breadcrumbs :items="[
                            ['label' => 'Inicio', 'url' => route('dashboard')],
                            ['label' => 'Productos', 'url' => route('inventory.products.index', $returnQuery)],
                            ['label' => $product->name, 'url' => route('inventory.products.show', ['product' => $product->id] + $returnQuery)],
                            ['label' => 'Activos', 'url' => route('inventory.products.assets.index', ['product' => $product->id] + $returnQuery)],
                            ['label' => $asset->serial, 'url' => null],
                        ]" />
                    @endif
                </x-slot:breadcrumbs>

                <x-slot:status>
                    <x-ui.status-badge :status="$asset->status" solid />
                </x-slot:status>

                <x-slot:actions>
                    @can('inventory.manage')
                        @if (\App\Support\Assets\AssetStatusTransitions::canAssign($asset->status))
                            <a class="btn btn-sm btn-success" href="{{ route('inventory.products.assets.assign', ['product' => $product->id, 'asset' => $asset->id]) }}">
                                <i class="bi bi-person-check me-1" aria-hidden="true"></i>Asignar
                            </a>
                        @endif
                        @if (\App\Support\Assets\AssetStatusTransitions::canLoan($asset->status))
                            <a class="btn btn-sm btn-info text-dark" href="{{ route('inventory.products.assets.loan', ['product' => $product->id, 'asset' => $asset->id]) }}">
                                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Prestar
                            </a>
                        @endif
                        @if (\App\Support\Assets\AssetStatusTransitions::canReturn($asset->status))
                            <a class="btn btn-sm btn-info text-dark" href="{{ route('inventory.products.assets.return', ['product' => $product->id, 'asset' => $asset->id] + (is_string($returnTo) && $returnTo !== '' ? ['returnTo' => $returnTo] : [])) }}">
                                <i class="bi bi-arrow-return-left me-1" aria-hidden="true"></i>Devolver
                            </a>
                        @endif
                        <a class="btn btn-sm btn-primary" href="{{ route('inventory.products.assets.edit', ['product' => $product->id, 'asset' => $asset->id]) }}">
                            <i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar
                        </a>
                    @endcan
                    @can('admin-only')
                        <a class="btn btn-sm btn-warning" href="{{ route('inventory.products.assets.adjust', ['product' => $product->id, 'asset' => $asset->id] + $returnQuery) }}">
                            Ajustar
                        </a>
                    @endcan
                </x-slot:actions>
            </x-ui.detail-header>

            <div class="card">
                <div class="card-header">
                    Información del activo
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Serial</dt>
                        <dd class="col-sm-9">{{ $asset->serial }}</dd>

                        <dt class="col-sm-3">Asset tag</dt>
                        <dd class="col-sm-9">{{ $asset->asset_tag ?? '-' }}</dd>

                        <dt class="col-sm-3">Estado</dt>
                        <dd class="col-sm-9">
                            <x-ui.status-badge :status="$asset->status" />
                        </dd>

                        <dt class="col-sm-3">Ubicación</dt>
                        <dd class="col-sm-9">{{ $asset->location?->name ?? '-' }}</dd>

                        <dt class="col-sm-3">Costo de adquisición</dt>
                        <dd class="col-sm-9">
                            @if ($asset->acquisition_cost !== null)
                                @php
                                    $settingsStore = app(\App\Support\Settings\SettingsStore::class);
                                    $defaultCurrency = $settingsStore->getString('gatic.inventory.money.default_currency', 'MXN');
                                    $currency = is_string($asset->acquisition_currency) && $asset->acquisition_currency !== ''
                                        ? $asset->acquisition_currency
                                        : ($defaultCurrency !== '' ? $defaultCurrency : 'MXN');
                                @endphp
                                {{ number_format((float) $asset->acquisition_cost, 2) }} {{ $currency }}
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-3">Vida útil (meses)</dt>
                        <dd class="col-sm-9">
                            @php
                                $effectiveUsefulLifeMonths = $asset->useful_life_months ?? $product->category?->default_useful_life_months;
                            @endphp
                            {{ $effectiveUsefulLifeMonths ?? '—' }}
                        </dd>

                        <dt class="col-sm-3">Fecha estimada de reemplazo</dt>
                        <dd class="col-sm-9">
                            @if ($asset->expected_replacement_date)
                                {{ $asset->expected_replacement_date->format('d/m/Y') }}
                                @php
                                    $today = \Illuminate\Support\Carbon::today();
                                    $renewalStore = app(\App\Support\Settings\SettingsStore::class);
                                    $allowedOptions = $renewalStore->getIntList('gatic.alerts.renewals.due_soon_window_days_options', [30, 60, 90, 180]);
                                    if ($allowedOptions === []) {
                                        $allowedOptions = [30, 60, 90, 180];
                                    }
                                    sort($allowedOptions);

                                    $renewalWindowDays = $renewalStore->getInt('gatic.alerts.renewals.due_soon_window_days_default', $allowedOptions[0] ?? 90);
                                    if (! in_array($renewalWindowDays, $allowedOptions, true)) {
                                        $renewalWindowDays = (int) ($allowedOptions[0] ?? 90);
                                    }

                                    $isOverdue = $asset->expected_replacement_date->lt($today);
                                    $isDueSoon = ! $isOverdue && $asset->expected_replacement_date->lte($today->copy()->addDays($renewalWindowDays));
                                @endphp
                                @if ($isOverdue)
                                    <span class="badge bg-danger ms-1">Vencido</span>
                                @elseif ($isDueSoon)
                                    <span class="badge bg-warning text-dark ms-1">Por vencer</span>
                                @else
                                    <span class="badge bg-success ms-1">En tiempo</span>
                                @endif
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    Tenencia actual
                </div>
                <div class="card-body">
                    @php
                        $hasHolder = in_array($asset->status, [\App\Models\Asset::STATUS_ASSIGNED, \App\Models\Asset::STATUS_LOANED], true);
                    @endphp

                    @if (! $hasHolder)
                        <p class="mb-0 text-muted">N/A — El activo está disponible</p>
                    @else
                        @if ($asset->currentEmployee)
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <x-ui.status-badge :status="$asset->status" />
                                <a href="{{ route('employees.show', ['employee' => $asset->currentEmployee->id]) }}" class="text-decoration-none">
                                    <strong>{{ $asset->currentEmployee->rpe }}</strong> — {{ $asset->currentEmployee->name }}
                                </a>
                            </div>
                        @else
                            <div class="d-flex align-items-center gap-2 text-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>Sin tenencia registrada (estado legacy o ajuste manual)</span>
                            </div>
                        @endif

                        @if ($asset->status === \App\Models\Asset::STATUS_LOANED && $asset->loan_due_date)
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <strong>Vence:</strong> {{ $asset->loan_due_date->format('d/m/Y') }}
                                </small>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Contract card --}}
            <div class="card mt-3">
                <div class="card-header">
                    Contrato
                </div>
                <div class="card-body">
                    @if ($asset->contract)
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Identificador</dt>
                            <dd class="col-sm-9">
                                <a href="{{ route('inventory.contracts.show', ['contract' => $asset->contract->id]) }}" class="text-decoration-none">
                                    {{ $asset->contract->identifier }}
                                </a>
                            </dd>

                            <dt class="col-sm-3">Tipo</dt>
                            <dd class="col-sm-9">{{ $asset->contract->type_label }}</dd>

                            @if ($asset->contract->supplier)
                                <dt class="col-sm-3">Proveedor</dt>
                                <dd class="col-sm-9">{{ $asset->contract->supplier->name }}</dd>
                            @endif

                            @if ($asset->contract->start_date || $asset->contract->end_date)
                                <dt class="col-sm-3">Vigencia</dt>
                                <dd class="col-sm-9">
                                    {{ $asset->contract->start_date?->format('d/m/Y') ?? '—' }}
                                    al
                                    {{ $asset->contract->end_date?->format('d/m/Y') ?? '—' }}
                                </dd>
                            @endif
                        </dl>
                    @else
                        <p class="mb-0 text-muted">N/A — Sin contrato vinculado</p>
                    @endif
                </div>
            </div>

            {{-- Warranty card --}}
            <div class="card mt-3">
                <div class="card-header">
                    Garantía
                </div>
                <div class="card-body">
                    @php
                        $hasWarranty = $asset->warranty_start_date || $asset->warranty_end_date || $asset->warranty_supplier_id || $asset->warranty_notes;
                    @endphp

                    @if ($hasWarranty)
                        <dl class="row mb-0">
                            @if ($asset->warranty_start_date || $asset->warranty_end_date)
                                <dt class="col-sm-3">Vigencia</dt>
                                <dd class="col-sm-9">
                                    {{ $asset->warranty_start_date?->format('d/m/Y') ?? '—' }}
                                    al
                                    {{ $asset->warranty_end_date?->format('d/m/Y') ?? '—' }}
                                    @if ($asset->warranty_end_date)
                                        @php
                                            $today = \Illuminate\Support\Carbon::today();
                                            $isExpired = $asset->warranty_end_date->lt($today);
                                            $warrantyStore = app(\App\Support\Settings\SettingsStore::class);
                                            $dueSoonDays = $warrantyStore->getInt('gatic.alerts.warranties.due_soon_window_days_default', 30);
                                            if ($dueSoonDays <= 0) {
                                                $dueSoonDays = 30;
                                            }
                                            $isDueSoon = ! $isExpired && $asset->warranty_end_date->lte($today->copy()->addDays($dueSoonDays));
                                        @endphp
                                        @if ($isExpired)
                                            <span class="badge bg-danger ms-1">Vencida</span>
                                        @elseif ($isDueSoon)
                                            <span class="badge bg-warning text-dark ms-1">Por vencer</span>
                                        @else
                                            <span class="badge bg-success ms-1">Vigente</span>
                                        @endif
                                    @endif
                                </dd>
                            @endif

                            @if ($asset->warrantySupplier)
                                <dt class="col-sm-3">Proveedor</dt>
                                <dd class="col-sm-9">{{ $asset->warrantySupplier->name }}</dd>
                            @endif

                            @if ($asset->warranty_notes)
                                <dt class="col-sm-3">Notas</dt>
                                <dd class="col-sm-9">{{ $asset->warranty_notes }}</dd>
                            @endif
                        </dl>
                    @else
                        <p class="mb-0 text-muted">N/A — Sin garantía registrada</p>
                    @endif
                </div>
            </div>

            {{-- Timeline panel --}}
            <livewire:ui.timeline-panel
                :entity-type="\App\Models\Asset::class"
                :entity-id="$asset->id"
            />

            {{-- Notes panel --}}
            <livewire:ui.notes-panel
                :noteable-type="\App\Models\Asset::class"
                :noteable-id="$asset->id"
            />

            {{-- Attachments panel (Admin/Editor only) --}}
            @can('attachments.view')
                <livewire:ui.attachments-panel
                    :attachable-type="\App\Models\Asset::class"
                    :attachable-id="$asset->id"
                />
            @endcan
        </div>
    </div>
</div>
