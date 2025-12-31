<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <h1 class="h4 mb-0">Consultar error por ID</h1>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form class="d-flex flex-column flex-md-row gap-2" wire:submit="search">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Ej. 01JH2J3W3M5P7G9R8C1V2B3N4M"
                            wire:model="errorId"
                            data-testid="error-reports-lookup-input"
                        />

                        <button type="submit" class="btn btn-primary" data-testid="error-reports-lookup-submit">
                            Buscar
                        </button>
                    </form>
                </div>
            </div>

            @if ($searched && ! $report)
                <div class="alert alert-warning" role="alert" data-testid="error-reports-lookup-not-found">
                    No se encontró un error con ese ID.
                </div>
            @endif

            @if ($report)
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold">Error encontrado</div>
                            <div class="small opacity-75">
                                <span>ID:</span>
                                <code class="ms-1" data-testid="error-reports-lookup-id">{{ $report->error_id }}</code>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            data-copy-to-clipboard
                            data-copy-text="{{ $report->error_id }}"
                        >
                            Copiar ID
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="small opacity-75">Fecha</div>
                                <div>{{ $report->created_at?->toDateTimeString() }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small opacity-75">Entorno</div>
                                <div>{{ $report->environment }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small opacity-75">Usuario</div>
                                <div>
                                    @if ($report->user_id)
                                        #{{ $report->user_id }}
                                        @if ($report->user_role)
                                            <span class="opacity-75">({{ $report->user_role }})</span>
                                        @endif
                                    @else
                                        <span class="opacity-75">N/A</span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="small opacity-75">Request</div>
                                <div class="d-flex flex-column gap-1">
                                    <div><span class="opacity-75">Método:</span> {{ $report->method ?? 'N/A' }}</div>
                                    <div><span class="opacity-75">Ruta:</span> {{ $report->route ?? 'N/A' }}</div>
                                    <div class="text-break"><span class="opacity-75">URL:</span> {{ $report->url ?? 'N/A' }}</div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="small opacity-75">Excepción</div>
                                <div class="text-break">
                                    <div><code>{{ $report->exception_class }}</code></div>
                                    @if (is_string($report->exception_message) && $report->exception_message !== '')
                                        <div class="mt-1">{{ $report->exception_message }}</div>
                                    @endif
                                </div>
                            </div>

                            @if (is_string($report->stack_trace) && $report->stack_trace !== '')
                                <div class="col-md-12">
                                    <div class="small opacity-75">Stack trace</div>
                                    <pre class="bg-light border rounded p-3 small overflow-auto" style="max-height: 24rem" data-testid="error-reports-lookup-stack">{{ $report->stack_trace }}</pre>
                                </div>
                            @endif

                            @if (is_array($report->context) && count($report->context) > 0)
                                <div class="col-md-12">
                                    <div class="small opacity-75">Contexto (redactado)</div>
                                    <pre class="bg-light border rounded p-3 small overflow-auto" style="max-height: 24rem" data-testid="error-reports-lookup-context">{{ json_encode($report->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

