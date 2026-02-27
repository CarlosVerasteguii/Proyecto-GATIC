@php
    use App\Enums\PendingTaskLineStatus;
    use App\Enums\PendingTaskStatus;
    use App\Models\Asset;

    $badgeExamples = [
        'entity' => [
            ['tone' => 'success', 'label' => 'Disponible'],
            ['tone' => 'warning', 'label' => 'Prestado'],
            ['tone' => 'info', 'label' => 'Asignado'],
            ['tone' => 'secondary', 'label' => 'Pendiente de retiro'],
            ['tone' => 'danger', 'label' => 'Retirado'],
        ],
        'workflow' => [
            ['tone' => 'secondary', 'label' => PendingTaskStatus::Draft->label()],
            ['tone' => 'info', 'label' => PendingTaskStatus::Ready->label()],
            ['tone' => 'warning', 'label' => PendingTaskStatus::Processing->label()],
            ['tone' => 'success', 'label' => PendingTaskStatus::Completed->label()],
            ['tone' => 'danger', 'label' => PendingTaskStatus::Cancelled->label()],
        ],
        'kpi' => [
            ['tone' => 'neutral', 'label' => 'Resultados', 'value' => '3'],
            ['tone' => 'neutral', 'label' => 'Seleccionados', 'value' => '12'],
            ['tone' => 'neutral', 'label' => 'Filtrado', 'value' => 'Activos'],
        ],
        'roles' => [
            ['tone' => 'role-admin', 'label' => 'Admin'],
            ['tone' => 'role-editor', 'label' => 'Editor'],
            ['tone' => 'role-lector', 'label' => 'Lector'],
        ],
        'availability' => [
            ['tone' => 'success', 'label' => 'Activo'],
            ['tone' => 'secondary', 'label' => 'Inactivo'],
        ],
        'tags' => [
            ['tone' => 'neutral', 'label' => 'Etiqueta'],
            ['tone' => 'neutral', 'label' => 'Proveedor'],
            ['tone' => 'neutral', 'label' => 'Tipo'],
        ],
        'alerts' => [
            ['tone' => 'warning', 'label' => 'Stock bajo'],
            ['tone' => 'danger', 'label' => 'Vencido'],
        ],
    ];

    $badgePalettes = [
        [
            'key' => 'a',
            'title' => 'Paleta A: Pill sutil',
            'subtitle' => 'Pill redondeado, texto con color, fondo suave. Muy legible en tabla.',
            'class' => 'ui-palette--pill',
        ],
        [
            'key' => 'b',
            'title' => 'Paleta B: Rail',
            'subtitle' => 'Texto neutro + acento lateral. Menos ruido visual, buen escaneo.',
            'class' => 'ui-palette--rail',
        ],
        [
            'key' => 'c',
            'title' => 'Paleta C: Tag compacto',
            'subtitle' => 'Label compacto, may&uacute;sculas, look industrial. M&aacute;s denso.',
            'class' => 'ui-palette--tag',
        ],
        [
            'key' => 'd',
            'title' => 'Paleta D: Tonal Mist (inspiraci&oacute;n Material)',
            'subtitle' => 'Fondo tonal suave, texto neutro y punto de color. Menos agresiva que solid.',
            'class' => 'ui-palette--tonal-mist',
        ],
        [
            'key' => 'e',
            'title' => 'Paleta E: Lozenge Soft (inspiraci&oacute;n Atlassian)',
            'subtitle' => 'Lozenge suave: borde m&aacute;s definido, fondo apenas visible. Buena para roles/tags.',
            'class' => 'ui-palette--lozenge-soft',
        ],
        [
            'key' => 'f',
            'title' => 'Paleta F: Ink Outline (inspiraci&oacute;n Carbon)',
            'subtitle' => 'Ghost/outlined: casi sin fondo, borde y tipograf&iacute;a mandan. Muy seria.',
            'class' => 'ui-palette--carbon-ink-outline',
        ],
    ];
@endphp

<div class="container ui-badge-lab">
    <div class="row justify-content-center">
        <div class="col-xl-11 col-xxl-10">
            <div class="card mb-4">
                <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-2">
                    <div>
                        <div class="fw-semibold">Laboratorio de badges (smoke)</div>
                        <div class="small text-muted">
                            Re&uacute;ne estilos existentes para elegir un badge consistente (ej. "Estado" en Operaciones).
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="dash-chip"><strong>Tip:</strong> prueba en light y dark</span>
                        <a href="{{ route('dev.livewire-smoke') }}" class="btn btn-outline-secondary btn-sm">
                            Smoke general
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-12">
                            <section id="ui-badge-palettes" class="border rounded p-3">
                                <h2 class="h6 fw-semibold mb-2">Propuestas de paletas (en sinton&iacute;a)</h2>
                                <p class="small text-muted mb-3">
                                    Cada paleta usa el <strong>mismo</strong> tama&ntilde;o, tipograf&iacute;a y forma para estatus, KPIs, roles, tags y alertas.
                                    Solo cambian las reglas visuales (pill/rail/tag/tonal/lozenge/outline) para que puedas comparar r&aacute;pido.
                                </p>

                                <div class="row g-3">
                                    @foreach ($badgePalettes as $palette)
                                        <div class="col-12 col-xl-6">
                                            <div class="ui-palette {{ $palette['class'] }} border rounded p-3 h-100">
                                                <div class="d-flex align-items-start justify-content-between gap-2">
                                                    <div>
                                                        <div class="fw-semibold">{{ $palette['title'] }}</div>
                                                        <div class="small text-muted">{{ $palette['subtitle'] }}</div>
                                                    </div>

                                                    <span class="ui-badge ui-badge--neutral" role="note">
                                                        <span class="ui-badge__mark" aria-hidden="true"></span>
                                                        Preview
                                                    </span>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="ui-palette__label">Estatus (entidad)</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach ($badgeExamples['entity'] as $badge)
                                                            <span class="ui-badge ui-badge--{{ $badge['tone'] }}" role="status">
                                                                <span class="ui-badge__mark" aria-hidden="true"></span>
                                                                {{ $badge['label'] }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="ui-palette__label">Estatus (flujo)</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach ($badgeExamples['workflow'] as $badge)
                                                            <span class="ui-badge ui-badge--{{ $badge['tone'] }}" role="status">
                                                                <span class="ui-badge__mark" aria-hidden="true"></span>
                                                                {{ $badge['label'] }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="ui-palette__label">KPIs / contexto</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach ($badgeExamples['kpi'] as $badge)
                                                            <span class="ui-badge ui-badge--{{ $badge['tone'] }}">
                                                                <span class="ui-badge__mark" aria-hidden="true"></span>
                                                                {{ $badge['label'] }} <strong>{{ $badge['value'] }}</strong>
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="ui-palette__label">Roles / RBAC</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach ($badgeExamples['roles'] as $badge)
                                                            <span class="ui-badge ui-badge--{{ $badge['tone'] }}">
                                                                <span class="ui-badge__mark" aria-hidden="true"></span>
                                                                {{ $badge['label'] }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="ui-palette__label">Activo/Inactivo</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach ($badgeExamples['availability'] as $badge)
                                                            <span class="ui-badge ui-badge--{{ $badge['tone'] }}" role="status">
                                                                <span class="ui-badge__mark" aria-hidden="true"></span>
                                                                {{ $badge['label'] }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="ui-palette__label">Tags (metadata)</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach ($badgeExamples['tags'] as $badge)
                                                            <span class="ui-badge ui-badge--{{ $badge['tone'] }}">
                                                                <span class="ui-badge__mark" aria-hidden="true"></span>
                                                                {{ $badge['label'] }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="ui-palette__label">Alertas</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach ($badgeExamples['alerts'] as $badge)
                                                            <span class="ui-badge ui-badge--{{ $badge['tone'] }}" role="status">
                                                                <span class="ui-badge__mark" aria-hidden="true"></span>
                                                                {{ $badge['label'] }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        </div>

                        <div class="col-12">
                            <section class="border rounded p-3">
                                <h2 class="h6 fw-semibold mb-2">Paleta: cu&aacute;ndo usar cada badge</h2>
                                <p class="small text-muted mb-3">
                                    Regla pr&aacute;ctica: los colores fuertes son para <strong>estatus</strong> o <strong>alertas</strong>; los chips neutros para
                                    <strong>conteos</strong> y <strong>contexto</strong>.
                                </p>

                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Categor&iacute;a</th>
                                                <th>Uso recomendado</th>
                                                <th>Badge sugerido</th>
                                            </tr>
                                        </thead>
                                        <tbody class="small">
                                            <tr>
                                                <td class="fw-semibold">Estatus (entidad)</td>
                                                <td>Estados estables: activo, prestado, retirado, etc.</td>
                                                <td>
                                                    <x-ui.status-badge :status="Asset::STATUS_AVAILABLE" />
                                                    <x-ui.status-badge :status="Asset::STATUS_RETIRED" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Estatus (flujo)</td>
                                                <td>Workflows: borrador, listo, procesando, finalizado.</td>
                                                <td class="ops-page">
                                                    <span class="{{ PendingTaskStatus::Processing->badgeClass() }}">
                                                        <span class="ops-status-chip__dot" aria-hidden="true"></span>
                                                        {{ PendingTaskStatus::Processing->label() }}
                                                    </span>
                                                    <span class="{{ PendingTaskStatus::Completed->badgeClass() }}">
                                                        <span class="ops-status-chip__dot" aria-hidden="true"></span>
                                                        {{ PendingTaskStatus::Completed->label() }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Conteos / KPIs</td>
                                                <td>Resultados, seleccionados, totales, etc.</td>
                                                <td><span class="dash-chip"><strong>Resultados</strong> 3</span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Roles / RBAC</td>
                                                <td>Roles del usuario (Admin/Editor/Lector).</td>
                                                <td class="admin-users-page">
                                                    <span class="badge rounded-pill admin-users-role admin-users-role--editor">Editor</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Disponibilidad</td>
                                                <td>Activo/Inactivo, habilitado/deshabilitado.</td>
                                                <td class="admin-users-page">
                                                    <span class="badge rounded-pill admin-users-status admin-users-status--active">
                                                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                                        Activo
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Etiquetas</td>
                                                <td>Metadatos: labels, categor&iacute;as, tipos, etc. (bajo &eacute;nfasis).</td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">Etiqueta</span>
                                                    <span class="badge bg-light text-dark border">Tipo</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Alertas r&aacute;pidas</td>
                                                <td>Se&ntilde;ales fuertes: vencido, stock bajo, sin disponibles.</td>
                                                <td>
                                                    <span class="badge text-bg-warning" role="status">Stock bajo</span>
                                                    <span class="badge text-bg-danger" role="status">Vencido</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>

                        <div class="col-12">
                            <section class="border rounded p-3">
                                <h2 class="h6 fw-semibold mb-2">1) Inventario: <code>&lt;x-ui.status-badge&gt;</code></h2>
                                <p class="small text-muted mb-3">
                                    Badge tokenizado y reusable (ver <code>resources/sass/_tokens.scss</code>).
                                </p>

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <x-ui.status-badge :status="Asset::STATUS_AVAILABLE" />
                                    <x-ui.status-badge :status="Asset::STATUS_LOANED" />
                                    <x-ui.status-badge :status="Asset::STATUS_ASSIGNED" />
                                    <x-ui.status-badge :status="Asset::STATUS_PENDING_RETIREMENT" />
                                    <x-ui.status-badge :status="Asset::STATUS_RETIRED" />
                                    <x-ui.status-badge status="Desconocido" />
                                </div>

                                <div class="mt-3 small text-muted">Variante s&oacute;lida</div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <x-ui.status-badge :status="Asset::STATUS_AVAILABLE" solid />
                                    <x-ui.status-badge :status="Asset::STATUS_LOANED" solid />
                                    <x-ui.status-badge :status="Asset::STATUS_ASSIGNED" solid />
                                    <x-ui.status-badge :status="Asset::STATUS_PENDING_RETIREMENT" solid />
                                    <x-ui.status-badge :status="Asset::STATUS_RETIRED" solid />
                                </div>

                                <div class="mt-3 small text-muted">Sin &iacute;cono</div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <x-ui.status-badge :status="Asset::STATUS_AVAILABLE" :icon="false" />
                                    <x-ui.status-badge :status="Asset::STATUS_LOANED" :icon="false" />
                                    <x-ui.status-badge :status="Asset::STATUS_ASSIGNED" :icon="false" />
                                    <x-ui.status-badge :status="Asset::STATUS_PENDING_RETIREMENT" :icon="false" />
                                    <x-ui.status-badge :status="Asset::STATUS_RETIRED" :icon="false" />
                                </div>
                            </section>
                        </div>

                        <div class="col-12">
                            <section class="border rounded p-3 ops-page">
                                <h2 class="h6 fw-semibold mb-2">2) Operaciones: <code>.ops-status-chip</code></h2>
                                <p class="small text-muted mb-3">
                                    Chip usado actualmente en "Tareas pendientes" para estatus de tarea y rengl&oacute;n.
                                </p>

                                <div class="small text-muted mb-2">Estatus de tarea</div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    @foreach (PendingTaskStatus::cases() as $status)
                                        <span class="{{ $status->badgeClass() }}">
                                            <span class="ops-status-chip__dot" aria-hidden="true"></span>
                                            {{ $status->label() }}
                                        </span>
                                    @endforeach
                                </div>

                                <div class="small text-muted mt-3 mb-2">Estatus de rengl&oacute;n</div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    @foreach (PendingTaskLineStatus::cases() as $status)
                                        <span class="{{ $status->badgeClass() }}">
                                            <span class="ops-status-chip__dot" aria-hidden="true"></span>
                                            {{ $status->label() }}
                                        </span>
                                    @endforeach
                                </div>
                            </section>
                        </div>

                        <div class="col-12">
                            <section class="border rounded p-3">
                                <h2 class="h6 fw-semibold mb-2">3) Dashboard/Cat&aacute;logos: <code>.dash-chip</code></h2>
                                <p class="small text-muted mb-3">Chip para conteos y contexto (ej. "Resultados").</p>

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="dash-chip"><strong>Resultados</strong> 3</span>
                                    <span class="dash-chip"><strong>Seleccionados</strong> 12</span>
                                    <span class="dash-chip"><strong>Filtrado</strong> Activos</span>
                                </div>
                            </section>
                        </div>

                        <div class="col-12 col-lg-6">
                            <section class="border rounded p-3 admin-users-page h-100">
                                <h2 class="h6 fw-semibold mb-2">4) Admin: Usuarios (pills)</h2>
                                <p class="small text-muted mb-3">
                                    Pills con borde + fondo sutil (roles y estatus).
                                </p>

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge rounded-pill admin-users-role admin-users-role--admin">Admin</span>
                                    <span class="badge rounded-pill admin-users-role admin-users-role--editor">Editor</span>
                                    <span class="badge rounded-pill admin-users-role admin-users-role--lector">Lector</span>
                                </div>

                                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge rounded-pill admin-users-status admin-users-status--active">
                                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                        Activo
                                    </span>
                                    <span class="badge rounded-pill admin-users-status admin-users-status--inactive">
                                        <i class="bi bi-dash-circle-fill" aria-hidden="true"></i>
                                        Inactivo
                                    </span>
                                </div>
                            </section>
                        </div>

                        <div class="col-12 col-lg-6">
                            <section class="border rounded p-3 admin-settings-page h-100">
                                <h2 class="h6 fw-semibold mb-2">5) Admin: Settings (resumen)</h2>
                                <p class="small text-muted mb-3">
                                    Badge custom (no Bootstrap) para resumir estado de configuraci&oacute;n.
                                </p>

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="admin-settings-summary-badge">
                                        <i class="bi bi-gear-fill" aria-hidden="true"></i>
                                        Default
                                    </span>
                                    <span class="admin-settings-summary-badge admin-settings-summary-badge--custom">
                                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                        Custom
                                    </span>
                                </div>

                                <div class="mt-3 small text-muted">Pills de m&eacute;tricas</div>
                                <div class="admin-settings-summary-pill-group">
                                    <span class="admin-settings-summary-pill"><strong>12</strong> m&eacute;tricas</span>
                                    <span class="admin-settings-summary-pill"><strong>3</strong> alertas</span>
                                    <span class="admin-settings-summary-pill"><strong>1</strong> pendiente</span>
                                </div>
                            </section>
                        </div>

                        <div class="col-12">
                            <section class="border rounded p-3">
                                <h2 class="h6 fw-semibold mb-2">6) Tags neutros (Timeline / etiquetas)</h2>
                                <p class="small text-muted mb-3">
                                    Badges suaves para labels y metadatos (usado en Timeline panel).
                                </p>

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge bg-light text-dark border">Etiqueta</span>
                                    <span class="badge bg-light text-dark border">Proveedor</span>
                                    <span class="badge bg-light text-dark border">Movimiento</span>
                                </div>
                            </section>
                        </div>

                        <div class="col-12">
                            <section class="border rounded p-3">
                                <h2 class="h6 fw-semibold mb-2">7) Bootstrap (variantes r&aacute;pidas)</h2>
                                <p class="small text-muted mb-3">
                                    &Uacute;tiles para prototipar, pero suelen perder consistencia si se usan sin reglas.
                                </p>

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge text-bg-primary" role="status">Primary</span>
                                    <span class="badge text-bg-secondary" role="status">Secondary</span>
                                    <span class="badge text-bg-success" role="status">Success</span>
                                    <span class="badge text-bg-warning" role="status">Warning</span>
                                    <span class="badge text-bg-danger" role="status">Danger</span>
                                    <span class="badge text-bg-info" role="status">Info</span>
                                    <span class="badge text-bg-light border" role="status">Light</span>
                                    <span class="badge text-bg-dark" role="status">Dark</span>
                                </div>

                                <div class="mt-3 small text-muted">Rounded pill</div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge rounded-pill text-bg-success" role="status">Listo</span>
                                    <span class="badge rounded-pill text-bg-warning" role="status">Procesando</span>
                                    <span class="badge rounded-pill text-bg-secondary" role="status">Borrador</span>
                                    <span class="badge rounded-pill text-bg-danger" role="status">Error</span>
                                </div>

                                <div class="mt-3 small text-muted">Outline (sin fondo)</div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge bg-transparent border border-success text-success" role="status">Success</span>
                                    <span class="badge bg-transparent border border-warning text-warning" role="status">Warning</span>
                                    <span class="badge bg-transparent border border-danger text-danger" role="status">Danger</span>
                                    <span class="badge bg-transparent border border-info text-info" role="status">Info</span>
                                    <span class="badge bg-transparent border border-secondary text-secondary" role="status">Secondary</span>
                                    <span class="badge bg-transparent border border-dark text-body" role="status">Neutral</span>
                                </div>

                                <div class="mt-3 small text-muted">Subtle (si est&aacute; disponible en Bootstrap)</div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge border border-success-subtle text-success-emphasis bg-success-subtle" role="status">Success</span>
                                    <span class="badge border border-warning-subtle text-warning-emphasis bg-warning-subtle" role="status">Warning</span>
                                    <span class="badge border border-danger-subtle text-danger-emphasis bg-danger-subtle" role="status">Danger</span>
                                    <span class="badge border border-info-subtle text-info-emphasis bg-info-subtle" role="status">Info</span>
                                    <span class="badge border border-secondary-subtle text-secondary-emphasis bg-secondary-subtle" role="status">Secondary</span>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<style>
    /* Badge Lab (dev) - scoped to this page */
    .ui-badge-lab {
        --ui-brand-rgb: 0, 142, 90;
    }

    [data-bs-theme="dark"] .ui-badge-lab {
        --ui-brand-rgb: 0, 179, 111;
    }

    #ui-badge-palettes .ui-palette__label {
        margin-bottom: 0.45rem;
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        font-weight: 800;
        color: var(--bs-secondary-color);
    }

    #ui-badge-palettes .ui-palette {
        /* Unified sizing across ALL palettes */
        --ui-badge-font-size: 0.78rem;
        --ui-badge-font-weight: 800;
        --ui-badge-line-height: 1.05;
        --ui-badge-gap: 0.35rem;
        --ui-badge-py: 0.22rem;
        --ui-badge-px: 0.55rem;
        --ui-badge-radius: var(--radius-full);
        --ui-badge-letter-spacing: 0.01em;
        --ui-badge-text-transform: none;
        --ui-badge-border-alpha: 0.22;
        --ui-badge-bg-alpha: 0.1;
        --ui-badge-mark-size: 0.45rem;
        --ui-badge-mark-radius: var(--radius-full);
        --ui-badge-mark-alpha: 0.9;
        --ui-badge-text-mode: accent; /* accent | neutral */
    }

    #ui-badge-palettes .ui-badge {
        --ui-badge-accent-rgb: var(--bs-secondary-rgb);
        display: inline-flex;
        align-items: center;
        gap: var(--ui-badge-gap);
        padding: var(--ui-badge-py) var(--ui-badge-px);
        border-radius: var(--ui-badge-radius);
        font-size: var(--ui-badge-font-size);
        font-weight: var(--ui-badge-font-weight);
        letter-spacing: var(--ui-badge-letter-spacing);
        text-transform: var(--ui-badge-text-transform);
        line-height: var(--ui-badge-line-height);
        white-space: nowrap;
        border: 1px solid rgba(var(--ui-badge-accent-rgb), var(--ui-badge-border-alpha));
        background: rgba(var(--ui-badge-accent-rgb), var(--ui-badge-bg-alpha));
        color: rgba(var(--ui-badge-accent-rgb), 1);
    }

    #ui-badge-palettes .ui-palette[role="note"] .ui-badge {
        /* no-op; kept for clarity */
    }

    #ui-badge-palettes .ui-palette--rail .ui-badge,
    #ui-badge-palettes .ui-palette--pill .ui-badge,
    #ui-badge-palettes .ui-palette--tag .ui-badge,
    #ui-badge-palettes .ui-palette--tonal-mist .ui-badge,
    #ui-badge-palettes .ui-palette--lozenge-soft .ui-badge,
    #ui-badge-palettes .ui-palette--carbon-ink-outline .ui-badge {
        /* placeholder to keep specificity stable */
    }

    #ui-badge-palettes .ui-badge strong {
        font-weight: 900;
        color: var(--bs-emphasis-color);
    }

    #ui-badge-palettes .ui-badge__mark {
        width: var(--ui-badge-mark-size);
        height: var(--ui-badge-mark-size);
        border-radius: var(--ui-badge-mark-radius);
        background: rgba(var(--ui-badge-accent-rgb), var(--ui-badge-mark-alpha));
        flex: 0 0 auto;
    }

    /* Tones */
    #ui-badge-palettes .ui-badge--success { --ui-badge-accent-rgb: var(--bs-success-rgb); }
    #ui-badge-palettes .ui-badge--warning { --ui-badge-accent-rgb: var(--bs-warning-rgb); }
    #ui-badge-palettes .ui-badge--danger { --ui-badge-accent-rgb: var(--bs-danger-rgb); }
    #ui-badge-palettes .ui-badge--info { --ui-badge-accent-rgb: var(--bs-info-rgb); }
    #ui-badge-palettes .ui-badge--secondary { --ui-badge-accent-rgb: var(--bs-secondary-rgb); }
    #ui-badge-palettes .ui-badge--neutral {
        --ui-badge-accent-rgb: var(--bs-secondary-rgb);
        color: var(--bs-secondary-color);
    }

    /* Roles (use a stable hue per role) */
    #ui-badge-palettes .ui-badge--role-admin { --ui-badge-accent-rgb: 15, 118, 110; }
    #ui-badge-palettes .ui-badge--role-editor { --ui-badge-accent-rgb: 29, 78, 216; }
    #ui-badge-palettes .ui-badge--role-lector { --ui-badge-accent-rgb: 71, 85, 105; }

    /* Palette A: Pill sutil (accent text) */
    #ui-badge-palettes .ui-palette--pill {
        --ui-badge-radius: var(--radius-full);
        --ui-badge-bg-alpha: 0.1;
        --ui-badge-border-alpha: 0.22;
        --ui-badge-text-transform: none;
        --ui-badge-letter-spacing: 0.01em;
        --ui-badge-mark-size: 0.45rem;
        --ui-badge-mark-radius: var(--radius-full);
    }

    /* Palette B: Rail (neutral text, accent bar) */
    #ui-badge-palettes .ui-palette--rail {
        --ui-badge-radius: var(--radius-full);
        --ui-badge-bg-alpha: 0.12;
        --ui-badge-border-alpha: 0.18;
        --ui-badge-text-mode: neutral;
        --ui-badge-mark-size: 0.18rem;
        --ui-badge-mark-radius: var(--radius-full);
        --ui-badge-mark-alpha: 0.95;
    }

    #ui-badge-palettes .ui-palette--rail .ui-badge {
        color: var(--bs-emphasis-color);
    }

    #ui-badge-palettes .ui-palette--rail .ui-badge__mark {
        height: 0.92rem;
    }

    /* Palette C: Tag compacto (uppercase, squared) */
    #ui-badge-palettes .ui-palette--tag {
        --ui-badge-radius: var(--radius-sm);
        --ui-badge-bg-alpha: 0.06;
        --ui-badge-border-alpha: 0.24;
        --ui-badge-letter-spacing: 0.07em;
        --ui-badge-text-transform: uppercase;
        --ui-badge-mark-size: 0.44rem;
        --ui-badge-mark-radius: var(--radius-sm);
        --ui-badge-mark-alpha: 0.9;
    }

    /* Palette D: Tonal Mist (Material-inspired) */
    #ui-badge-palettes .ui-palette--tonal-mist {
        --ui-badge-radius: var(--radius-full);
        --ui-badge-bg-alpha: 0.18;
        --ui-badge-border-alpha: 0.14;
        --ui-badge-mark-size: 0.5rem;
        --ui-badge-mark-alpha: 0.92;
    }

    #ui-badge-palettes .ui-palette--tonal-mist .ui-badge {
        color: var(--bs-emphasis-color);
    }

    /* Palette E: Lozenge Soft (Atlassian-inspired) */
    #ui-badge-palettes .ui-palette--lozenge-soft {
        --ui-badge-radius: var(--radius-md);
        --ui-badge-bg-alpha: 0.08;
        --ui-badge-border-alpha: 0.32;
        --ui-badge-mark-size: 0.22rem;
        --ui-badge-mark-radius: var(--radius-sm);
        --ui-badge-mark-alpha: 0.85;
    }

    #ui-badge-palettes .ui-palette--lozenge-soft .ui-badge__mark {
        height: 0.92rem;
    }

    /* Palette F: Ink Outline (Carbon-inspired) */
    #ui-badge-palettes .ui-palette--carbon-ink-outline {
        --ui-badge-radius: var(--radius-full);
        --ui-badge-bg-alpha: 0.02;
        --ui-badge-border-alpha: 0.42;
        --ui-badge-letter-spacing: 0.03em;
        --ui-badge-mark-alpha: 0;
        --ui-badge-mark-size: 0;
        --ui-badge-gap: 0;
    }

    #ui-badge-palettes .ui-palette--carbon-ink-outline .ui-badge {
        background: transparent;
    }

    #ui-badge-palettes .ui-palette--carbon-ink-outline .ui-badge__mark {
        display: none;
    }

    /* Improve dark-mode legibility for subtle palettes */
    [data-bs-theme="dark"] #ui-badge-palettes .ui-palette--pill {
        --ui-badge-bg-alpha: 0.14;
        --ui-badge-border-alpha: 0.28;
    }

    [data-bs-theme="dark"] #ui-badge-palettes .ui-palette--rail {
        --ui-badge-bg-alpha: 0.16;
        --ui-badge-border-alpha: 0.26;
    }

    [data-bs-theme="dark"] #ui-badge-palettes .ui-palette--tonal-mist {
        --ui-badge-bg-alpha: 0.24;
        --ui-badge-border-alpha: 0.26;
    }

    [data-bs-theme="dark"] #ui-badge-palettes .ui-palette--lozenge-soft {
        --ui-badge-bg-alpha: 0.14;
        --ui-badge-border-alpha: 0.34;
    }

    [data-bs-theme="dark"] #ui-badge-palettes .ui-palette--carbon-ink-outline {
        --ui-badge-bg-alpha: 0.08;
        --ui-badge-border-alpha: 0.55;
    }
</style>
</div>
