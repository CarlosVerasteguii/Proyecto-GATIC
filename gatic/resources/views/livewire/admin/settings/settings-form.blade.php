<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-gear me-1" aria-hidden="true"></i> Configuración del sistema</span>
                    @if ($hasOverrides)
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-warning"
                            wire:click="restoreDefaults"
                            wire:confirm="¿Restaurar todos los valores a los defaults del sistema? Esta acción no se puede deshacer."
                        >
                            <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>
                            Restaurar defaults
                        </button>
                    @endif
                </div>

                <div class="card-body">
                    <form wire:submit="save">
                        {{-- Alertas: Préstamos --}}
                        <h6 class="text-secondary-emphasis mb-3">
                            <i class="bi bi-bell me-1" aria-hidden="true"></i>
                            Ventanas "por vencer" (alertas)
                        </h6>

                        <div class="mb-3">
                            <label for="loansDueSoonDefault" class="form-label">
                                Préstamos &mdash; ventana por vencer (días)
                            </label>
                            <select
                                id="loansDueSoonDefault"
                                class="form-select @error('loansDueSoonDefault') is-invalid @enderror"
                                wire:model="loansDueSoonDefault"
                            >
                                @foreach ($loansOptions as $opt)
                                    <option value="{{ $opt }}">{{ $opt }} días</option>
                                @endforeach
                            </select>
                            @error('loansDueSoonDefault')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Alertas de préstamos próximos a vencer se calculan con esta ventana.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="warrantiesDueSoonDefault" class="form-label">
                                Garantías &mdash; ventana por vencer (días)
                            </label>
                            <select
                                id="warrantiesDueSoonDefault"
                                class="form-select @error('warrantiesDueSoonDefault') is-invalid @enderror"
                                wire:model="warrantiesDueSoonDefault"
                            >
                                @foreach ($warrantiesOptions as $opt)
                                    <option value="{{ $opt }}">{{ $opt }} días</option>
                                @endforeach
                            </select>
                            @error('warrantiesDueSoonDefault')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Alertas de garantías próximas a vencer.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="renewalsDueSoonDefault" class="form-label">
                                Renovaciones &mdash; ventana por vencer (días)
                            </label>
                            <select
                                id="renewalsDueSoonDefault"
                                class="form-select @error('renewalsDueSoonDefault') is-invalid @enderror"
                                wire:model="renewalsDueSoonDefault"
                            >
                                @foreach ($renewalsOptions as $opt)
                                    <option value="{{ $opt }}">{{ $opt }} días</option>
                                @endforeach
                            </select>
                            @error('renewalsDueSoonDefault')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Alertas de renovaciones (vida útil) próximas a vencer.
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Moneda --}}
                        <h6 class="text-secondary-emphasis mb-3">
                            <i class="bi bi-currency-exchange me-1" aria-hidden="true"></i>
                            Moneda
                        </h6>

                        <div class="mb-3">
                            <label for="defaultCurrency" class="form-label">
                                Moneda default del sistema
                            </label>
                            @if (count($allowedCurrencies) <= 1)
                                <input
                                    id="defaultCurrency"
                                    type="text"
                                    class="form-control"
                                    value="{{ $defaultCurrency }}"
                                    disabled
                                    readonly
                                />
                                <div class="form-text">
                                    Solo hay una moneda configurada. Para agregar más, modifique <code>config/gatic.php</code>.
                                </div>
                            @else
                                <select
                                    id="defaultCurrency"
                                    class="form-select @error('defaultCurrency') is-invalid @enderror"
                                    wire:model="defaultCurrency"
                                >
                                    @foreach ($allowedCurrencies as $curr)
                                        <option value="{{ $curr }}">{{ $curr }}</option>
                                    @endforeach
                                </select>
                                @error('defaultCurrency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <hr class="my-4">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">
                                    <i class="bi bi-check-lg me-1" aria-hidden="true"></i>
                                    Guardar configuración
                                </span>
                                <span wire:loading wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Guardando...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
