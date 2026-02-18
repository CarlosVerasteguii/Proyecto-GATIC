<div class="container position-relative admin-settings-page">
    <x-ui.long-request target="save,restoreDefaults" />

    @php
        $pageTitle = 'Configuración';
        $pageSubtitle = 'Ajusta ventanas de alertas y preferencias globales del sistema.';
        $settingsStatusLabel = $hasOverrides ? 'Personalizada' : 'Por defecto';
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">
            <div class="card admin-settings-card">
                <div class="card-header admin-settings-card__header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div class="min-w-0">
                        <x-ui.breadcrumbs :items="[
                            ['label' => 'Inicio', 'url' => route('dashboard')],
                            ['label' => 'Administración', 'url' => route('admin.users.index')],
                            ['label' => $pageTitle, 'url' => null],
                        ]" />
                        <h1 class="h5 mb-1">{{ $pageTitle }}</h1>
                        <p class="text-body-secondary mb-0 admin-settings-card__subtitle">{{ $pageSubtitle }}</p>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="dash-chip">
                            <i class="bi bi-sliders" aria-hidden="true"></i>
                            Estado <strong>{{ $settingsStatusLabel }}</strong>
                        </span>
                        <span class="dash-chip">
                            <i class="bi bi-database" aria-hidden="true"></i>
                            Overrides <strong>{{ number_format($overrideCount) }}</strong>
                        </span>

                        @if ($hasOverrides)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-warning"
                                wire:click="restoreDefaults"
                                wire:confirm="¿Restaurar todos los valores a los defaults del sistema? Esta acción no se puede deshacer."
                                wire:loading.attr="disabled"
                                wire:target="restoreDefaults"
                            >
                                <span wire:loading.remove wire:target="restoreDefaults">
                                    <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>
                                    Restaurar defaults
                                </span>
                                <span wire:loading.inline wire:target="restoreDefaults">
                                    <span class="d-inline-flex align-items-center gap-2">
                                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                        Restaurando…
                                    </span>
                                </span>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert" aria-live="assertive">
                            Revisa los campos marcados para continuar.
                        </div>
                    @endif

                    <form wire:submit="save">
                        <div class="row g-3">
                            <div class="col-12 col-xl-8">
                                <section class="admin-settings-section">
                                    <h2 class="admin-settings-section__title">
                                        <i class="bi bi-bell" aria-hidden="true"></i>
                                        Ventanas de alertas
                                    </h2>
                                    <p class="text-body-secondary small mb-3">
                                        Define la ventana (en días) para considerar elementos &ldquo;por vencer&rdquo; y generar alertas en el sistema.
                                    </p>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                                <label for="loansDueSoonDefault" class="form-label mb-0">Préstamos — ventana por vencer</label>
                                                @php($loansIsDefault = $loansDueSoonDefault === $configDefaults['loans'])
                                                <span class="admin-settings-marker {{ $loansIsDefault ? 'admin-settings-marker--default' : 'admin-settings-marker--custom' }}">
                                                    <i class="bi {{ $loansIsDefault ? 'bi-check-circle' : 'bi-pencil-square' }}" aria-hidden="true"></i>
                                                    <span>{{ $loansIsDefault ? 'Por defecto' : 'Personalizado' }}</span>
                                                </span>
                                            </div>
                                            <div class="input-group">
                                                <span class="input-group-text bg-body">
                                                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                                                </span>
                                                <select
                                                    id="loansDueSoonDefault"
                                                    class="form-select @error('loansDueSoonDefault') is-invalid @enderror"
                                                    wire:model="loansDueSoonDefault"
                                                    aria-label="Ventana por vencer para préstamos"
                                                >
                                                    @foreach ($loansOptions as $opt)
                                                        <option value="{{ $opt }}">{{ $opt }} días</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('loansDueSoonDefault')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Se usa para calcular alertas de préstamos próximos a vencer. Por defecto: {{ $configDefaults['loans'] }} días.
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                                <label for="warrantiesDueSoonDefault" class="form-label mb-0">Garantías — ventana por vencer</label>
                                                @php($warrantiesIsDefault = $warrantiesDueSoonDefault === $configDefaults['warranties'])
                                                <span class="admin-settings-marker {{ $warrantiesIsDefault ? 'admin-settings-marker--default' : 'admin-settings-marker--custom' }}">
                                                    <i class="bi {{ $warrantiesIsDefault ? 'bi-check-circle' : 'bi-pencil-square' }}" aria-hidden="true"></i>
                                                    <span>{{ $warrantiesIsDefault ? 'Por defecto' : 'Personalizado' }}</span>
                                                </span>
                                            </div>
                                            <div class="input-group">
                                                <span class="input-group-text bg-body">
                                                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                                                </span>
                                                <select
                                                    id="warrantiesDueSoonDefault"
                                                    class="form-select @error('warrantiesDueSoonDefault') is-invalid @enderror"
                                                    wire:model="warrantiesDueSoonDefault"
                                                    aria-label="Ventana por vencer para garantías"
                                                >
                                                    @foreach ($warrantiesOptions as $opt)
                                                        <option value="{{ $opt }}">{{ $opt }} días</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('warrantiesDueSoonDefault')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Alertas de garantías próximas a vencer. Por defecto: {{ $configDefaults['warranties'] }} días.
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                                <label for="renewalsDueSoonDefault" class="form-label mb-0">Renovaciones — ventana por vencer</label>
                                                @php($renewalsIsDefault = $renewalsDueSoonDefault === $configDefaults['renewals'])
                                                <span class="admin-settings-marker {{ $renewalsIsDefault ? 'admin-settings-marker--default' : 'admin-settings-marker--custom' }}">
                                                    <i class="bi {{ $renewalsIsDefault ? 'bi-check-circle' : 'bi-pencil-square' }}" aria-hidden="true"></i>
                                                    <span>{{ $renewalsIsDefault ? 'Por defecto' : 'Personalizado' }}</span>
                                                </span>
                                            </div>
                                            <div class="input-group">
                                                <span class="input-group-text bg-body">
                                                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                                </span>
                                                <select
                                                    id="renewalsDueSoonDefault"
                                                    class="form-select @error('renewalsDueSoonDefault') is-invalid @enderror"
                                                    wire:model="renewalsDueSoonDefault"
                                                    aria-label="Ventana por vencer para renovaciones"
                                                >
                                                    @foreach ($renewalsOptions as $opt)
                                                        <option value="{{ $opt }}">{{ $opt }} días</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('renewalsDueSoonDefault')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Alertas de renovaciones (vida útil) próximas a vencer. Por defecto: {{ $configDefaults['renewals'] }} días.
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="admin-settings-section">
                                    <h2 class="admin-settings-section__title">
                                        <i class="bi bi-currency-exchange" aria-hidden="true"></i>
                                        Moneda
                                    </h2>
                                    <p class="text-body-secondary small mb-3">
                                        Define la moneda por defecto usada en valorizaciones y reportes.
                                    </p>

                                    <div>
                                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                            <label for="defaultCurrency" class="form-label mb-0">Moneda default del sistema</label>
                                            @php($currencyIsDefault = $defaultCurrency === $configDefaults['currency'])
                                            <span class="admin-settings-marker {{ $currencyIsDefault ? 'admin-settings-marker--default' : 'admin-settings-marker--custom' }}">
                                                <i class="bi {{ $currencyIsDefault ? 'bi-check-circle' : 'bi-pencil-square' }}" aria-hidden="true"></i>
                                                <span>{{ $currencyIsDefault ? 'Por defecto' : 'Personalizado' }}</span>
                                            </span>
                                        </div>

                                        @if (count($allowedCurrencies) <= 1)
                                            <input
                                                id="defaultCurrency"
                                                type="text"
                                                class="form-control admin-settings-readonly"
                                                value="{{ $defaultCurrency }}"
                                                disabled
                                                readonly
                                                aria-label="Moneda default (solo lectura)"
                                            />
                                            <div class="form-text">
                                                Solo hay una moneda configurada. Para agregar más, modifica <code>config/gatic.php</code>.
                                            </div>
                                        @else
                                            <div class="input-group">
                                                <span class="input-group-text bg-body">
                                                    <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                                </span>
                                                <select
                                                    id="defaultCurrency"
                                                    class="form-select @error('defaultCurrency') is-invalid @enderror"
                                                    wire:model="defaultCurrency"
                                                    aria-label="Moneda default del sistema"
                                                >
                                                    @foreach ($allowedCurrencies as $curr)
                                                        <option value="{{ $curr }}">{{ $curr }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('defaultCurrency')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Por defecto: {{ $configDefaults['currency'] }}.
                                            </div>
                                        @endif
                                    </div>
                                </section>

                                <div class="d-flex flex-wrap gap-2 admin-settings-actions">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        wire:loading.attr="disabled"
                                        wire:target="save"
                                    >
                                        <span wire:loading.remove wire:target="save">
                                            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>
                                            Guardar configuración
                                        </span>
                                        <span wire:loading.inline wire:target="save">
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                                Guardando…
                                            </span>
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 col-xl-4">
                                <aside class="admin-settings-summary">
                                    @php($summaryStatusClass = $hasOverrides ? 'custom' : 'default')
                                    <div class="admin-settings-summary__header">
                                        <h2 class="admin-settings-summary__title mb-0">Resumen</h2>
                                        <span class="admin-settings-summary-badge admin-settings-summary-badge--{{ $summaryStatusClass }}">
                                            <i class="bi {{ $hasOverrides ? 'bi-sliders2' : 'bi-check2-circle' }}" aria-hidden="true"></i>
                                            {{ $settingsStatusLabel }}
                                        </span>
                                    </div>

                                    <div class="admin-settings-summary__metrics">
                                        <article class="admin-settings-summary-metric">
                                            <span class="admin-settings-summary-metric__icon" aria-hidden="true">
                                                <i class="bi bi-diagram-3"></i>
                                            </span>
                                            <div>
                                                <span class="admin-settings-summary-metric__label">Alcance</span>
                                                <strong class="admin-settings-summary-metric__value">Global</strong>
                                            </div>
                                        </article>

                                        <article class="admin-settings-summary-metric">
                                            <span class="admin-settings-summary-metric__icon" aria-hidden="true">
                                                <i class="bi bi-database"></i>
                                            </span>
                                            <div>
                                                <span class="admin-settings-summary-metric__label">Overrides</span>
                                                <strong class="admin-settings-summary-metric__value">{{ number_format($overrideCount) }}</strong>
                                            </div>
                                        </article>

                                        <article class="admin-settings-summary-metric">
                                            <span class="admin-settings-summary-metric__icon" aria-hidden="true">
                                                <i class="bi bi-cash-coin"></i>
                                            </span>
                                            <div>
                                                <span class="admin-settings-summary-metric__label">Moneda</span>
                                                <strong class="admin-settings-summary-metric__value">{{ $defaultCurrency }}</strong>
                                            </div>
                                        </article>
                                    </div>

                                    <section class="admin-settings-summary__alerts" aria-label="Resumen de ventanas de alerta">
                                        <h3 class="admin-settings-summary__section-title">Ventanas de alertas</h3>
                                        <div class="admin-settings-summary-pill-group">
                                            <span class="admin-settings-summary-pill">
                                                <span>Préstamos</span>
                                                <strong>{{ $loansDueSoonDefault }}d</strong>
                                            </span>
                                            <span class="admin-settings-summary-pill">
                                                <span>Garantías</span>
                                                <strong>{{ $warrantiesDueSoonDefault }}d</strong>
                                            </span>
                                            <span class="admin-settings-summary-pill">
                                                <span>Renovaciones</span>
                                                <strong>{{ $renewalsDueSoonDefault }}d</strong>
                                            </span>
                                        </div>
                                    </section>

                                    <p class="admin-settings-summary__hint mb-0">
                                        <i class="bi bi-lightbulb" aria-hidden="true"></i>
                                        <span>
                                            Consejo: usa ventanas más cortas para seguimiento más estricto; ventanas más largas reducen ruido de alertas.
                                        </span>
                                    </p>
                                </aside>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
