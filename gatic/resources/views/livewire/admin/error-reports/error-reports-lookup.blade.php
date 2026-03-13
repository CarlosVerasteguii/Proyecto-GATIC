<div class="container py-4 admin-error-reports-page">
    <x-ui.long-request target="search" />

    @php
        $hasLookupValue = trim($errorId) !== '';
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">
            <x-ui.toolbar
                title="Errores de Soporte"
                subtitle="Busca un error ID para revisar el contexto técnico persistido para soporte."
                filterId="admin-error-reports-lookup"
                :filtersCollapsible="false"
                searchColClass="col-12 col-lg-9"
            >
                <x-slot:breadcrumbs>
                    <x-ui.breadcrumbs :items="[
                        ['label' => 'Inicio', 'url' => route('dashboard')],
                        ['label' => 'Administración', 'url' => route('admin.error-reports.lookup')],
                        ['label' => 'Errores (soporte)', 'url' => null],
                    ]" />
                </x-slot:breadcrumbs>

                <x-slot:actions>
                    @if ($report)
                        <x-ui.badge tone="success" variant="compact" :with-rail="false">Error encontrado</x-ui.badge>
                    @elseif ($searched)
                        <x-ui.badge tone="warning" variant="compact" :with-rail="false">Sin resultados</x-ui.badge>
                    @endif
                </x-slot:actions>

                <x-slot:search>
                    <form wire:submit="search">
                        <label for="error-reports-lookup-input" class="form-label">Error ID</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </span>
                            <input
                                id="error-reports-lookup-input"
                                type="search"
                                class="form-control @error('errorId') is-invalid @enderror"
                                placeholder="Ej. 01JH2J3W3M5P7G9R8C1V2B3N4M…"
                                wire:model="errorId"
                                autocomplete="off"
                                spellcheck="false"
                                aria-describedby="error-reports-lookup-help"
                                data-testid="error-reports-lookup-input"
                            />
                            <button
                                type="submit"
                                class="btn btn-primary"
                                wire:loading.attr="disabled"
                                wire:target="search"
                                data-testid="error-reports-lookup-submit"
                            >
                                <span wire:loading.remove wire:target="search">Buscar</span>
                                <span wire:loading.inline wire:target="search">
                                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    Buscando…
                                </span>
                            </button>
                        </div>
                        @error('errorId')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div id="error-reports-lookup-help" class="form-text">
                            Usa el identificador mostrado al usuario final. Si no aparece aquí, revisa logs del servidor porque la persistencia es best-effort.
                        </div>
                    </form>
                </x-slot:search>

                <x-slot:clearFilters>
                    @if ($hasLookupValue || $searched)
                        <button
                            type="button"
                            class="btn btn-outline-secondary w-100"
                            wire:click="clearLookup"
                            aria-label="Limpiar búsqueda de errores"
                        >
                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Limpiar
                        </button>
                    @endif
                </x-slot:clearFilters>
            </x-ui.toolbar>

            <div class="mt-4" aria-live="polite">
                @if ($searched && ! $report)
                    <x-ui.section-card title="Resultado" icon="bi-search">
                        <x-ui.empty-state
                            icon="bi-exclamation-circle"
                            title="No se encontró un error con ese ID"
                            description="Verifica el error ID exacto o consulta logs del servidor si el incidente no alcanzó a persistirse."
                            data-testid="error-reports-lookup-not-found"
                        />
                    </x-ui.section-card>
                @endif

                @if ($report)
                    <x-ui.detail-header
                        :title="$report->error_id"
                        :subtitle="'Registrado el '.$report->created_at?->format('d/m/Y H:i:s')"
                        class="mt-4"
                    >
                        <x-slot:status>
                            <x-ui.badge tone="neutral" variant="compact" :with-rail="false">Entorno: {{ $report->environment ?? 'N/A' }}</x-ui.badge>
                            @if ($report->route)
                                <x-ui.badge tone="info" variant="compact" :with-rail="false">Ruta: {{ $report->route }}</x-ui.badge>
                            @endif
                            @if ($report->user_role)
                                <x-ui.badge tone="warning" variant="compact" :with-rail="false">Rol: {{ $report->user_role }}</x-ui.badge>
                            @endif
                        </x-slot:status>

                        <x-slot:actions>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                data-copy-to-clipboard
                                data-copy-text="{{ $report->error_id }}"
                            >
                                <i class="bi bi-copy me-1" aria-hidden="true"></i>Copiar ID
                            </button>
                        </x-slot:actions>
                    </x-ui.detail-header>

                    <div class="row g-3">
                        <div class="col-12 col-xl-4">
                            <x-ui.section-card title="Resumen" icon="bi-info-circle" class="h-100">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Fecha</dt>
                                    <dd class="col-sm-8">{{ $report->created_at?->toDateTimeString() ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">Entorno</dt>
                                    <dd class="col-sm-8">{{ $report->environment ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">Usuario</dt>
                                    <dd class="col-sm-8">
                                        @if ($report->user_id)
                                            #{{ $report->user_id }}
                                            @if ($report->user_role)
                                                <span class="text-body-secondary">({{ $report->user_role }})</span>
                                            @endif
                                        @else
                                            <span class="text-body-secondary">N/A</span>
                                        @endif
                                    </dd>

                                    <dt class="col-sm-4">Error ID</dt>
                                    <dd class="col-sm-8">
                                        <code data-testid="error-reports-lookup-id">{{ $report->error_id }}</code>
                                    </dd>
                                </dl>
                            </x-ui.section-card>
                        </div>

                        <div class="col-12 col-xl-8">
                            <x-ui.section-card title="Request" icon="bi-globe2" class="h-100">
                                @php($path = is_array($report->context) ? ($report->context['request']['path'] ?? null) : null)

                                <div class="row g-3">
                                    <div class="col-12 col-md-4">
                                        <div class="small text-body-secondary">Método</div>
                                        <div>{{ $report->method ?? 'N/A' }}</div>
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <div class="small text-body-secondary">Ruta nombrada</div>
                                        <div class="text-break">{{ $report->route ?? 'N/A' }}</div>
                                    </div>
                                    @if (is_string($path) && $path !== '')
                                        <div class="col-12">
                                            <div class="small text-body-secondary">Path</div>
                                            <div class="text-break">{{ $path }}</div>
                                        </div>
                                    @endif
                                    <div class="col-12">
                                        <div class="small text-body-secondary">URL</div>
                                        <div class="text-break">{{ $report->url ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </x-ui.section-card>
                        </div>

                        <div class="col-12">
                            <x-ui.section-card title="Excepción" icon="bi-bug">
                                <div class="border rounded-3 p-3 bg-body-tertiary">
                                    <div class="fw-semibold text-break">
                                        <code>{{ $report->exception_class }}</code>
                                    </div>
                                    @if (is_string($report->exception_message) && $report->exception_message !== '')
                                        <div class="mt-2 text-break">{{ $report->exception_message }}</div>
                                    @endif
                                </div>
                            </x-ui.section-card>
                        </div>

                        @if (is_string($report->stack_trace) && $report->stack_trace !== '')
                            <div class="col-12">
                                <x-ui.section-card title="Stack Trace" icon="bi-braces" bodyClass="p-0">
                                    <pre class="mb-0 p-3 small overflow-auto bg-body-tertiary" style="max-height: 24rem" data-testid="error-reports-lookup-stack">{{ $report->stack_trace }}</pre>
                                </x-ui.section-card>
                            </div>
                        @endif

                        @if (is_array($report->context) && count($report->context) > 0)
                            <div class="col-12">
                                <x-ui.section-card title="Contexto Redactado" icon="bi-file-earmark-code" bodyClass="p-0">
                                    <pre class="mb-0 p-3 small overflow-auto bg-body-tertiary" style="max-height: 24rem" data-testid="error-reports-lookup-context">{{ json_encode($report->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </x-ui.section-card>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
