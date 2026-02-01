<div
    wire:ignore.self
    class="modal fade"
    id="gaticCommandPaletteModal"
    tabindex="-1"
    aria-labelledby="gaticCommandPaletteLabel"
    aria-hidden="true"
    data-command-palette="true"
>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gaticCommandPaletteLabel">Paleta de comandos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </span>
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Escribe un comando o busca (serial, asset tag, RPE)…"
                        aria-label="Buscar comando"
                        autocomplete="off"
                        wire:model.live.debounce.150ms="query"
                        data-command-palette-input
                    />
                </div>

                <div class="mt-3" data-command-palette-results>
                    @php($flatIndex = 0)
                    @forelse ($this->groups as $group)
                        <div class="text-uppercase small text-muted mt-3 mb-1">
                            {{ $group['label'] }}
                        </div>

                        <div class="list-group">
                            @foreach ($group['items'] as $item)
                                <a
                                    href="{{ $item['url'] }}"
                                    class="list-group-item list-group-item-action d-flex gap-2 align-items-start"
                                    data-command-palette-item
                                    data-command-palette-index="{{ $flatIndex }}"
                                >
                                    <div class="mt-1 text-muted" style="width: 1.25rem;">
                                        @if ($item['icon'])
                                            <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                                        @endif
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="fw-medium">{{ $item['label'] }}</div>
                                        @if ($item['description'])
                                            <div class="small text-muted">{{ $item['description'] }}</div>
                                        @endif
                                    </div>
                                </a>
                                @php($flatIndex++)
                            @endforeach
                        </div>
                    @empty
                        <div class="text-muted small mt-2">Sin comandos disponibles.</div>
                    @endforelse
                </div>

                <div class="small text-muted mt-3">
                    <span class="me-2"><kbd>↑</kbd>/<kbd>↓</kbd> navegar</span>
                    <span class="me-2"><kbd>Enter</kbd> ejecutar</span>
                    <span><kbd>Esc</kbd> cerrar</span>
                </div>
            </div>
        </div>
    </div>
</div>
