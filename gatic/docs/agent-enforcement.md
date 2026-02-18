# Agent Enforcement — invariantes mecánicas (GATIC)

Este documento conecta “golden principles + taste invariants → enforcement”. Su objetivo es que las reglas no se queden como intención: cada invariante debe tener una **fuente de verdad**, un **método de verificación** y una **remediación** clara.

Piensa en esto como un registro operativo para agentes y humanos: qué regla existe, dónde se define, cómo se hace cumplir hoy (si ya hay automatización) y qué enforcement barato conviene agregar cuando aún es “solo manual”. Si hay conflicto entre documentos, **no se negocia**: se resuelve siguiendo la jerarquía de fuentes.

Cuando una regla cambie (producto/UX/arquitectura), actualiza primero la fuente de verdad correspondiente y luego este registro para que el enforcement sea coherente y auto-remediable.

## Jerarquía de fuentes de verdad

- [`docsBmad/project-context.md`](../../docsBmad/project-context.md) > [`project-context.md`](../../project-context.md) > docs de `gatic/docs/*` > código/tests > `_bmad-output/*`.

## Matriz de invariantes

| Invariante | Source of Truth (archivo) | Enforcement actual (Pint / PHPUnit / PHPStan / CI / manual) | Cómo verificar (comando o archivo/test) | Remediación (qué cambiar / dónde ver el patrón) |
|---|---|---|---|---|
| App vive en `gatic/` (layout) | [`project-context.md`](../../project-context.md)<br>[`.github/workflows/ci.yml`](../../.github/workflows/ci.yml) | CI (implícito) + manual | Ver `.github/workflows/ci.yml` (`working-directory: gatic`). | Si agregas tooling/CI/docs, referencia rutas relativas desde `gatic/` (patrón: workflow CI). |
| Identificadores (código/DB/rutas) en inglés; copy UI en español | [`project-context.md`](../../project-context.md) | manual | Revisar cambios en `gatic/routes/*.php`, migraciones y UI (Blade/Livewire). | Renombrar rutas/columnas/variables a inglés; mantener labels/mensajes en español (ver “Critical Implementation Rules”). |
| Convención de rutas: path `kebab-case`, name `dot.case` | [`project-context.md`](../../project-context.md) | manual | Revisar `gatic/routes/web.php` y `php artisan route:list`. | Normalizar paths (kebab-case) y names (dot.case) antes de merge; evita mezclar español/inglés en rutas. |
| Livewire-first (route → componente); controllers solo bordes | [`project-context.md`](../../project-context.md) | manual | Revisar `gatic/routes/web.php` (debe mapear a componentes Livewire). | Mover lógica de UI a componentes Livewire; dejar controllers solo para descargas/JSON interno puntual. |
| No helpers globales: preferir `app/Actions/*` y `app/Support/*` | [`project-context.md`](../../project-context.md) | manual | Buscar “helpers” nuevos (p. ej. `git grep -n \"function_exists\" gatic/app`). | Extraer lógica a `gatic/app/Actions/*` (casos de uso) o `gatic/app/Support/*` (utilidades de dominio). |
| Autorización server-side (Policies/Gates), roles fijos Admin/Editor/Lector | [`docsBmad/project-context.md`](../../docsBmad/project-context.md)<br>[`project-context.md`](../../project-context.md) | PHPUnit + manual | Correr `cd gatic && php artisan test` (ej. `gatic/tests/Feature/Admin/UsersAuthorizationTest.php`, `gatic/tests/Feature/Attachments/AttachmentsRbacTest.php`). | Agregar/ajustar Policy/Gate; asegurar que solo existan roles `Admin`/`Editor`/`Lector` en seeders y checks. |
| Polling (sin WebSockets) con intervalos (listas ~15s, métricas ~60s, locks heartbeat ~10s) | [`docsBmad/project-context.md`](../../docsBmad/project-context.md)<br>[`project-context.md`](../../project-context.md)<br>[`gatic/docs/patterns/concurrency-locks.md`](patterns/concurrency-locks.md) | manual | Ver `gatic/config/gatic.php` y buscar `wire:poll` en views: `git grep -n \"wire:poll\" gatic/resources`. | Usar `wire:poll.visible` y respetar intervalos; centralizar valores en config (`gatic.ui.polling.*`). |
| Locks: claim al entrar a “Procesar” + lease TTL 3m + idle guard 2m + heartbeat 10s; Admin puede forzar | [`docsBmad/project-context.md`](../../docsBmad/project-context.md)<br>[`project-context.md`](../../project-context.md)<br>[`gatic/docs/patterns/concurrency-locks.md`](patterns/concurrency-locks.md) | PHPUnit + manual | Correr `cd gatic && php artisan test` (tests: `gatic/tests/Feature/PendingTasks/PendingTaskLockTest.php`, `gatic/tests/Feature/PendingTasks/PendingTaskLockOverrideTest.php`). | Implementar claim/heartbeat/release vía `gatic/app/Actions/PendingTasks/*` y config `gatic.pending_tasks.locks.*`; no “inventar” locks en UI. |
| Operaciones críticas transaccionales (movimientos/locks) | [`project-context.md`](../../project-context.md)<br>[`gatic/docs/patterns/concurrency-locks.md`](patterns/concurrency-locks.md) | manual | `git grep -n \"DB::transaction\" gatic/app/Actions` y `git grep -n \"lockForUpdate\" gatic/app`. | Envolver en `DB::transaction()`; usar `lockForUpdate()` donde aplique; mantener idempotencia (ver patrón locks). |
| Idempotencia por renglón en `PendingTask`: líneas `applied` no se reaplican | [`gatic/docs/patterns/concurrency-locks.md`](patterns/concurrency-locks.md)<br>[`gatic/docs/state-machines/pending-task-line-states.md`](state-machines/pending-task-line-states.md) | PHPUnit + manual | Correr `cd gatic && php artisan test` (ej. `gatic/tests/Feature/PendingTasks/FinalizePendingTaskTest.php` → “already applied lines are skipped”). | En `FinalizePendingTask`, mantener guardas por estado + transacción + `lockForUpdate()` por renglón. |
| Adjuntos: UUID en disco + control de acceso + descarga vía controller | [`project-context.md`](../../project-context.md)<br>[`gatic/app/Models/README.md`](../app/Models/README.md) | PHPUnit + manual | Correr `cd gatic && php artisan test` (ej. `gatic/tests/Feature/Attachments/*`) y revisar que la UI no genere links directos a `/storage/` (buscar `Storage::url()` / `/storage/`). | Guardar UUID en storage privado; exponer descarga solo vía controller con autorización; nunca link directo a `storage/`. |
| Errores inesperados (global): UI amigable + `error_id` persistible best-effort; detalle técnico solo Admin | [`docsBmad/project-context.md`](../../docsBmad/project-context.md)<br>[`project-context.md`](../../project-context.md)<br>[`gatic/docs/support/admin-support-guide.md`](support/admin-support-guide.md)<br>[`gatic/app/Support/Errors/ErrorReporter.php`](../app/Support/Errors/ErrorReporter.php) | PHPUnit + manual | Correr `cd gatic && php artisan test` (ej. `gatic/tests/Feature/ErrorReports/ProductionUnhandledExceptionTest.php`). | Asegurar `ErrorReporter` + persistencia best-effort + lookup admin; en prod mostrar `error_id` y gatear detalle por rol Admin. |
| `PendingTaskLine`: error inesperado incluye `error_id` (prefijo `ERR-`) en `error_message` | [`gatic/docs/state-machines/pending-task-line-states.md`](state-machines/pending-task-line-states.md)<br>[`gatic/app/Actions/PendingTasks/FinalizePendingTask.php`](../app/Actions/PendingTasks/FinalizePendingTask.php) | manual | Revisar `FinalizePendingTask` (bloque `catch (\Throwable $e)`), y validar en UI al provocar un error inesperado. | Mantener formato “Error inesperado (ID: …)” sin filtrar detalle técnico; el detalle va a logs. |
| Soft-delete/papelera: retención hasta purga Admin | [`docsBmad/project-context.md`](../../docsBmad/project-context.md)<br>[`project-context.md`](../../project-context.md)<br>[`gatic/app/Models/README.md`](../app/Models/README.md) | PHPUnit + manual | Correr `cd gatic && php artisan test` (ej. `gatic/tests/Feature/Admin/Trash/*`, `gatic/tests/Feature/Catalogs/CatalogsTrashTest.php`). | Mantener `SoftDeletes` + acciones admin-only en `gatic/app/Actions/Trash/*`; no borrar duro en flujos normales. |
| Inventario serializado: `Retirado` se excluye de totales; “no disponible” = `Asignado`/`Prestado`/`Pendiente de Retiro` | [`gatic/app/Models/README.md`](../app/Models/README.md)<br>[`gatic/docs/state-machines/asset-states.md`](state-machines/asset-states.md) | PHPUnit + manual | Correr `cd gatic && php artisan test` (ej. `gatic/tests/Feature/Inventory/ProductsTest.php` → excluye `Retirado` en conteos; `gatic/tests/Feature/DashboardMetricsTest.php` → excluye `Retirado` en valor). | Ajustar constantes y cálculos UI para que `available = total - unavailable` (mínimo 0) y `Retirado` no cuente en totales operativos. |
| State machines documentadas son la interfaz: no romper transiciones sin actualizar docs | [`gatic/docs/state-machines/asset-states.md`](state-machines/asset-states.md)<br>[`gatic/docs/state-machines/pending-task-states.md`](state-machines/pending-task-states.md) | manual | Revisar docs de `gatic/docs/state-machines/*` al tocar Enums/Actions. | Si cambias estados/transiciones: 1) actualiza Enum/Actions, 2) actualiza doc state-machine, 3) agrega/ajusta tests de flujo. |
| Quality gates: Pint + PHPUnit + Larastan (referencia a `.github/workflows/ci.yml`) | [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml)<br>[`project-context.md`](../../project-context.md) | CI | `cd gatic && ./vendor/bin/pint --test && php artisan test && ./vendor/bin/phpstan analyse --no-progress` | Arreglar estilo con Pint, fallos con tests, y tipos/analysis con Larastan; no hacer merge con CI rojo. |
| Tests: deterministas; feature tests para RBAC + locks + movimientos; usar `RefreshDatabase` cuando aplique | [`project-context.md`](../../project-context.md) | manual + PHPUnit | `cd gatic && php artisan test` (y revisar `gatic/tests`). | Agregar tests de feature para invariantes críticos; evitar dependencias externas y flakiness. |

## Enforcement backlog (propuesto, futuro)

Ideas de checks mecánicos baratos (objetivo: que el error sea auto-remediable por agentes):

- **Route lint**: test que falle si `gatic/routes/web.php` registra controllers fuera de una allowlist (descargas/JSON) o si no apunta a componentes Livewire.
- **Route naming lint**: parsear `php artisan route:list --json` y validar `kebab-case` en paths + `dot.case` en names (con mensaje que sugiera el rename exacto).
- **No-helpers lint**: grep-lint en CI que falle si aparece `gatic/app/helpers.php` o `if (! function_exists(...))` en archivos nuevos.
- **Polling lint**: grep-lint que prohíba intervalos hardcodeados distintos a los de `gatic/config/gatic.php` y sugiera “usa config `gatic.ui.polling.*`”.
- **Attachments lint**: grep-lint que falle si UI construye URLs directas a `storage/` o usa `Storage::url()` para adjuntos; sugerir controller de descarga + policy.
- **Error UX lint**: test de feature que fuerce a que errores inesperados devuelvan `error_id` y que el detalle completo solo sea visible para Admin.
- **Lock contract tests**: tests de feature para claim/heartbeat/timeout/force-release (basados en [`gatic/docs/patterns/concurrency-locks.md`](patterns/concurrency-locks.md)).
- **Transactional boundary lint**: PHPStan rule o grep-lint que exija `DB::transaction()` en `gatic/app/Actions/Movements/*` y en acciones de locks.

## Cómo escribir lints como prompts

- Empieza por **“qué pasó”**: nombra el invariante exacto (ideal: mismo texto que en la tabla).
- Explica **“por qué importa”** en 1 línea (riesgo: seguridad, data corruption, UX).
- Da **“cómo arreglar”** en pasos accionables (máximo 3), con el archivo/clase objetivo.
- Incluye **“dónde leer”**: link relativo a la fuente (p. ej. [`project-context.md`](../../project-context.md) o un patrón en `gatic/docs/`).
- Agrega **un comando de verificación** que pase cuando esté bien (p. ej. `php artisan test` o un `git grep` concreto).
- Si aplica, sugiere una **remediación segura por defecto** (la opción menos disruptiva).
- Mantén el mensaje **determinista**: sin “quizá”, sin heurísticas ambiguas, sin instrucciones que dependan de red.

## Mantenimiento / doc-gardening

- Actualiza este doc cuando cambie: un invariante, su fuente de verdad, o el enforcement (por ejemplo CI en [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml)).
- Después de un bug/regresión, agrega una fila o fortalece “Cómo verificar” para que se pueda detectar temprano.
- Evita que se vuelva monolítico: si una explicación supera ~10 líneas, crea/actualiza un doc en `gatic/docs/patterns/*` o `gatic/docs/state-machines/*` y aquí solo enlaza.
- Mantén `project-context.md` lean (reglas críticas) y usa `docsBmad/project-context.md` como bible; este doc es el detalle “enforcement-ready”.
- Usa AGENTS como **mapa de navegación** y este doc como **detalle verificable** (comandos, archivos, remediación).
