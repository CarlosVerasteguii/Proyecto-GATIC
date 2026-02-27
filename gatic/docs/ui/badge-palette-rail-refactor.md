# Refactor Global de Badges: Paleta B (Rail)

Este documento define el plan por fases para estandarizar **toda** la UI de badges/chips/tags en GATIC usando una sola paleta visual: **Paleta B (Rail)**.

> Fuente visual viva: `GET /dev/ui-badges` (`dev.ui-badges`) muestra comparativos y se usara como "contrato" durante el refactor.

## Skills (obligatorio)

- `ui-ux-pro-max`
- `web-design-guidelines`
- `laravel-livewire`
- `laravel-blade`
- `systematic-debugging`
- `performance-profiling`
- `clean-code`
- `laravel-testing`

## Objetivo

- Consistencia: una sola "familia" de badge para estatus, roles, KPIs y tags, con reglas claras.
- Jerarquia: el badge acompana; no domina la tabla ni compite con el dato principal.
- Accesibilidad: legible sin depender solo del color (texto claro + contraste).
- Mantenibilidad: un componente y tokens reutilizables; menos CSS ad-hoc por pantalla.

## Restricciones

- No romper RBAC, rutas, ni patrones Livewire.
- No degradar performance:
  - no introducir polling adicional
  - mantener `debounce` en busquedas
  - no agregar consultas/joins innecesarios (idealmente no tocar queries por este cambio)
- Mantener consistencia con el sistema actual (Bootstrap 5 + tokens).
- Cambios incrementales, limpios y testeables.

## Definicion de la Paleta B (Rail)

**Rail** = texto neutro (emphasis) + acento lateral (barra) + borde/fondo sutil por tono.

Reglas base (deben ser constantes en todo el sistema):

- Metrica unica: font-size, font-weight, padding, radius, gap.
- Semantica unica: `success/warning/danger/info/secondary/neutral`.
- Roles: colores fijos para `admin/editor/lector` (no re-usar estos tonos para estatus).
- Variantes permitidas: `default`, `compact` (solo si es estrictamente necesario).

No permitido:

- Glow/animaciones constantes.
- Mezcla arbitraria de `badge text-bg-*` para estatus (fuera de alertas puntuales).

## Fase 1: Diagnostico (Auditoria)

### 1.1 Inventario de usos

Salida: tabla (en este doc o en un archivo aparte) con:

- Patron actual (componente/clase):
  - `<x-ui.status-badge>`
  - `.ops-status-chip`
  - `.dash-chip`
  - `.admin-users-role`, `.admin-users-status`
  - `.admin-settings-summary-badge`, `.admin-settings-summary-pill`
  - `class="badge ..."` (Bootstrap directo)
- Archivos donde aparece (path + breve contexto).
- Categoria UX (estatus entidad / estatus flujo / KPI / rol / tag / alerta).
- Riesgo de migracion:
  - bajo: solo CSS / wrapper
  - medio: markup repetido en muchas vistas
  - alto: componente compartido con muchos estados o dependencias

Comandos sugeridos (desde `gatic/`):

- `rg -n "<x-ui\\.status-badge|ops-status-chip|dash-chip|admin-users-role|admin-settings-summary-badge|\\bclass=\\\"badge" resources/views -S`

### 1.2 Auditoria UX/UI y A11y (por severidad)

Checklist (registrar hallazgos):

- Tamaño y peso inconsistentes (se siente "sin sintonia").
- Contraste insuficiente en dark mode.
- Badges que parecen interactivos sin serlo.
- Icon-only sin `aria-label` (si aplica).
- Dependencia de color (sin texto/forma que ayude).

### 1.3 Auditoria de performance (riesgo)

Este refactor debe ser principalmente visual, pero validar:

- No se agregan renders condicionales costosos en tablas grandes.
- No se introduce DOM excesivo por fila (evitar wrappers innecesarios).
- No se agregan watchers/polling/JS adicional.

Salida: nota breve "impacto esperado" (debe ser neutral o positivo).

## Fase 2: Propuesta (Plan sobre lo auditado)

Salida: una propuesta concreta con:

- Taxonomia final (categorias) y mapeo a Paleta B.
- Decision: "un componente canonico" y "wrappers de compatibilidad".
- Orden de migracion (por impacto vs riesgo).
- Tradeoffs y riesgos + mitigacion.

### 2.1 Sistema canonico (propuesto)

Propuesta tecnica (a validar con el audit):

1. Crear `x-ui.badge` (nuevo componente Blade) como base.
2. Implementar `.gatic-badge` (SCSS) con Paleta B (Rail) usando tokens CSS.
3. Convertir componentes existentes a wrappers:
   - `x-ui.status-badge` -> wrapper que mapea status -> `tone`
   - `.dash-chip` -> alias visual / wrapper (hasta migrar markup)
   - `.ops-status-chip` -> alias visual / wrapper (hasta migrar markup)

### 2.2 Reglas de uso (contrato)

Definir por categoria:

- Estatus (entidad): usar `x-ui.status-badge` (pero internamente Rail).
- Estatus (flujo): usar `x-ui.badge tone="..."`.
- KPI/conteos: usar `x-ui.badge tone="neutral"` (sin semantica de alerta).
- Roles: usar `x-ui.badge tone="role-editor"` (o equivalente).
- Tags metadata: `tone="neutral"` + variant compact (si aplica).
- Alertas: `tone="warning/danger"` y permitir variante "high-emphasis" solo si existe caso real.

### 2.3 Plan incremental (PRs)

PR 1 (fundacion):

- Crear `x-ui.badge` + `.gatic-badge` (Rail).
- Actualizar `/dev/ui-badges` para usar el nuevo componente en la seccion de paletas.
- Tests basicos del componente.

PR 2 (wrappers/compat):

- Refactor interno de `x-ui.status-badge` para que renderice via `x-ui.badge`.
- Alias CSS para `.dash-chip` y `.ops-status-chip` hacia Rail (sin tocar markup aun).

PR 3 (migracion de vistas por modulo):

- Migrar Operaciones (Pending Tasks, Empleados).
- Migrar Inventario (assets/products/search) si aun usa mezclas directas.
- Migrar Admin (users/settings/audit).

PR 4 (limpieza):

- Remover estilos legacy o dejarlos "deprecated" con fecha de retiro.
- Actualizar docs: `docs/ui/badges.md` + este plan con la fecha y estado.

## Fase 3: Ejecucion (Implementacion)

Regla: ejecutar por etapas, con diffs pequenos, y validacion por PR.

### 3.1 Implementar el componente canonico

Tareas:

- `resources/views/components/ui/badge.blade.php` (nuevo).
- Estilos en `resources/sass/_tokens.scss` o archivo dedicado `resources/sass/_badges.scss` (decidir segun el estilo del repo).
- Importar en `resources/sass/app.scss`.

Notas:

- Evitar overrides globales de `.badge` (Bootstrap). Migrar usos a `x-ui.badge`.
- Mantener un solo root element por componente Livewire.

### 3.2 Compatibilidad y migraciones

- Refactor de wrappers (sin cambiar las rutas ni permisos).
- Sustitucion progresiva de `class="badge ..."` en vistas a `x-ui.badge` segun categoria.

### 3.3 Checklist de accesibilidad por cada cambio

- Texto siempre presente.
- Contraste OK en light/dark.
- Si el badge transmite estado critico, incluir icono opcional o texto mas explicito.

## Fase 4: Validacion

### 4.1 Tests y quality gates

Comandos canonicos (desde `gatic/`):

- `./vendor/bin/pint --test`
- `php artisan test`
- `./vendor/bin/phpstan analyse --no-progress`

### 4.2 Validacion manual (smoke)

Pantallas minimas:

- `/dev/ui-badges` (contract)
- Pending tasks: `/pending-tasks`
- Inventario activos: `/inventory/assets`
- Admin users: `/admin/users`
- Dashboard: `/dashboard`

### 4.3 Build de assets (si aplica)

Si el entorno corre con build estatico (sin `public/hot`):

- `docker compose -f compose.yaml exec -T laravel.test npm run build`
- Reiniciar si es necesario: `docker compose -f compose.yaml restart laravel.test`

## Entregables por fase

- Fase 1 (Diagnostico): inventario + lista priorizada de inconsistencias (UI/UX/A11y/perf).
- Fase 2 (Propuesta): mapeo final, orden de migracion, riesgos y mitigaciones.
- Fase 3 (Ejecucion): lista de cambios por archivo (por PR) + screenshots comparativos (si se documenta).
- Fase 4 (Validacion): comandos corridos + resultado + checklist de pantallas revisadas.

