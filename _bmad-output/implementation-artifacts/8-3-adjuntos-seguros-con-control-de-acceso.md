# Story 8.3: Adjuntos seguros con control de acceso

Status: done

Story Key: `8-3-adjuntos-seguros-con-control-de-acceso`  
Epic: `8` (Gate 5: Trazabilidad y evidencia)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-01-24

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 8 / Story 8.3; FR34, NFR6)
- `_bmad-output/implementation-artifacts/prd.md` (FR34, NFR6, NFR4, NFR5)
- `_bmad-output/implementation-artifacts/architecture.md` (patrones: Livewire-first, controllers “bordes”, storage local privado)
- `_bmad-output/implementation-artifacts/ux.md` (reglas UX transversales; iconografía)
- `docsBmad/project-context.md` + `project-context.md` (reglas críticas)
- `docsBmad/rbac.md` + `gatic/app/Providers/AuthServiceProvider.php` (gates `attachments.manage`, `attachments.view`)
- Story previa (Epic 8): `_bmad-output/implementation-artifacts/8-1-auditoria-consultable-best-effort.md` (auditoría best-effort)
- Story previa (Epic 8): `_bmad-output/implementation-artifacts/8-2-notas-manuales-en-entidades-relevantes.md` (panel reusable + patrón UI)
- Código base relevante:
  - `gatic/config/filesystems.php` (disk `local` → `storage/app/private`, `serve => true`)
  - `gatic/app/Support/Audit/AuditRecorder.php` (allowlist de `context`)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

<!-- template-output: story_requirements -->

## Story

As a Admin/Editor,  
I want subir/ver/eliminar adjuntos asociados a registros,  
so that exista evidencia documental cuando aplique (FR34, NFR6).

## Alcance (MVP)

Esta story implementa un **módulo de adjuntos (archivos) seguro** con control de acceso estricto (server-side) y storage local privado.

Incluye:
- Adjuntos asociados a **registros relevantes** (MVP): `Product`, `Asset`, `Employee`.
- UI en pantallas de detalle para:
  - Listar adjuntos existentes (nombre original, tamaño, autor, fecha).
  - Subir adjunto (Admin/Editor).
  - Descargar/ver adjunto (Admin/Editor).
  - Eliminar adjunto (Admin/Editor).
- Validación de archivo (tipo/tamaño) y persistencia de metadata en DB.
- Guardado en disco con **nombre seguro** (UUID) en storage privado.
- Seguridad: Lector **no** puede ver/descargar/subir/eliminar adjuntos (MVP).
- Auditoría best-effort (alineación Epic 8): registrar eventos relevantes sin incluir contenido del archivo.

## Fuera de alcance (NO hacer aquí)

- S3/Cloud storage; se usa filesystem local (on-prem).
- Preview inline avanzado (PDF viewer/imagenes embebidas con transformaciones).
- Versionado de archivos, reemplazo con historial, o “restore previous”.
- Compartir por link público / expirables para usuarios externos.
- Antivirus/escaneo de malware (solo validación básica tipo/tamaño).
- Adjuntos masivos en listados/búsqueda global (solo en pantallas de detalle).
- Adjuntos sobre “Papelera” (8.4) o reglas especiales post-soft-delete más allá de no exponer en vistas normales.

## Definiciones (para evitar ambigüedad)

- **Adjunto:** archivo subido por un usuario autenticado, asociado a un registro del dominio (attachable).
- **Attachable:** entidad del dominio que admite adjuntos (MVP: Product/Asset/Employee).
- **Nombre original:** nombre del archivo como lo subió el usuario (solo para UI).
- **Nombre seguro:** UUID generado por el servidor para guardar en disco (no adivinable).
- **Storage privado:** archivos en `storage/app/private` (disk `local`), accesibles solo vía endpoint autenticado/autorizado.

## Acceptance Criteria

### AC1 — Subir adjunto con nombre seguro + nombre original (FR34, NFR6)

**Given** un Admin/Editor autenticado en el detalle de un registro attachable  
**When** sube un archivo permitido  
**Then** el sistema guarda el archivo en storage privado con **nombre seguro (UUID)**  
**And** conserva `original_name` para mostrarlo en UI  
**And** persiste metadata mínima (autor, tipo, tamaño, timestamps) en DB.

### AC2 — Validación de tipo/tamaño (NFR6)

**Given** un archivo a subir  
**When** el usuario intenta subirlo  
**Then** el servidor valida tipo y tamaño según política definida  
**And** en caso de fallo muestra mensaje claro en español sin filtrar detalles sensibles.

### AC3 — Ver/listar/descargar adjuntos solo para Admin/Editor (FR34, NFR4, NFR5)

**Given** un Admin/Editor autenticado  
**When** abre el detalle de un registro attachable  
**Then** puede ver la lista de adjuntos y descargarlos.

**Given** un usuario con rol Lector  
**When** intenta acceder a adjuntos (UI o request directo)  
**Then** el servidor bloquea el acceso (deny/403 o 404 según corresponda)  
**And** la UI no expone acciones ni links de descarga en MVP.

### AC4 — Eliminar adjunto (FR34)

**Given** un Admin/Editor autenticado  
**When** elimina un adjunto  
**Then** el registro de DB se elimina (o marca eliminado)  
**And** el archivo en disco se elimina también  
**And** el sistema maneja fallos de IO de forma segura (no deja UI en estado ambiguo).

### AC5 — Seguridad anti-enumeración (MVP)

**Given** que los archivos se guardan en storage privado  
**When** un usuario intenta acceder al archivo por ruta o ID adivinable  
**Then** no puede descargarlo sin pasar por autorización server-side.

### AC6 — Auditoría best-effort alineada a Epic 8 (NFR8)

**Given** un evento relevante de adjuntos (subida/eliminación; descarga si se decide auditar)  
**When** se registra auditoría  
**Then** se emite un evento best-effort sin bloquear la operación principal  
**And** el `context` NO incluye contenido del archivo ni datos sensibles (allowlist).

<!-- template-output: developer_context_section -->

## Developer Context (lo más importante)

### Reuse first (evitar reinventar)

- Ya existen gates en `gatic/app/Providers/AuthServiceProvider.php`:
  - `attachments.manage` (Admin/Editor)
  - `attachments.view` (Admin/Editor)
- Ya existe infraestructura de auditoría best-effort (Epic 8.1):
  - `gatic/app/Support/Audit/AuditRecorder.php` (allowlist de `context`)
  - `gatic/app/Jobs/RecordAuditLog.php`
  - `gatic/app/Models/AuditLog.php` (constantes `ACTION_*`)
- Ya existe patrón de “panel reusable” en detalle (Epic 8.2):
  - `gatic/app/Livewire/Ui/NotesPanel.php` + `gatic/resources/views/livewire/ui/notes-panel.blade.php`
  - Integración en detail views: `gatic/resources/views/livewire/inventory/products/product-show.blade.php`, etc.

### Puntos de integración UI (MVP)

Agregar un panel de adjuntos en:
- Producto: `gatic/resources/views/livewire/inventory/products/product-show.blade.php`
- Activo: `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
- Empleado: `gatic/resources/views/livewire/employees/employee-show.blade.php`

Regla UX:
- El panel debe ser consistente con “Notas”: card con header “Adjuntos”, empty-state útil, tabla densa desktop-first.
- Si listar adjuntos puede tardar >3s (casos con muchos adjuntos o storage lento), envolver el área de resultados con `<x-ui.long-request />` (ver `gatic/resources/views/components/ui/long-request.blade.php`).

### Guardrails (anti-errores recurrentes)

- **No usar storage público** para adjuntos (nada en `storage/app/public` ni `public/`).
- **No exponer rutas directas** al archivo en disco; descargas siempre via endpoint autenticado y autorizado.
- **No almacenar contenido del archivo** en DB ni en auditoría.
- **No confiar en la UI**: aplicar permisos server-side en Livewire + controller de descarga.
- **No filtrar paths/stack traces** en mensajes de validación/errores de adjuntos.

<!-- template-output: technical_requirements -->

## Technical Requirements

### Persistencia (DB)

Crear tabla `attachments` (polimórfica):
- `id`
- `attachable_type` (string) + `attachable_id` (unsignedBigInt)  ← morph
- `uploaded_by_user_id` (unsignedBigInt, FK users.id)
- `original_name` (string)  ← para UI (escapado en Blade)
- `disk` (string)  ← p.ej. `local`
- `path` (string)  ← ruta relativa dentro del disk
- `mime_type` (string)
- `size_bytes` (unsignedBigInt)
- `created_at`, `updated_at`

Índices mínimos:
- índice compuesto por morph: (`attachable_type`, `attachable_id`, `created_at`)
- índice por `uploaded_by_user_id`

Modelo: `App\Models\Attachment`
- `attachable(): morphTo`
- `uploader(): belongsTo(User::class, 'uploaded_by_user_id')`

### Storage (disco privado)

Usar el disk `local` (ver `gatic/config/filesystems.php`):
- Root: `storage/app/private`
- Adjuntos: `attachments/<attachable>/<attachable_id>/<uuid>` (sin confiar en input del usuario)

Reglas:
- Guardar con **UUID** como nombre seguro (sin “slug” del nombre original).
- El nombre original se usa solo como `Content-Disposition` en descarga y para UI.

### Subida (Livewire)

Implementar panel reusable tipo “Notas”:
- Livewire `App\Livewire\Ui\AttachmentsPanel` usando `WithFileUploads`.
- Reglas de permisos:
  - Listar/descargar: `Gate::authorize('attachments.view')`
  - Subir/eliminar: `Gate::authorize('attachments.manage')`
  - Además, validar que el usuario puede ver el attachable (p.ej. `inventory.view`/`inventory.manage`) para no abrir side-channels.

Validación recomendada (ajustable por configuración):
- Máx tamaño: 10 MB (o lo que se defina en `config/gatic.php`).
- Tipos permitidos (MVP sugerido): `application/pdf`, `image/png`, `image/jpeg`, `image/webp`,
  `text/plain`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`,
  `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`.
- Denegar explícitamente ejecutables (`.php`, `.exe`, `.js`, `.sh`, etc.).

### Descarga (controller “borde”)

Crear controller dedicado (patrón arquitectura):
- `App\Http\Controllers\Attachments\DownloadAttachmentController`

Reglas:
- Resolver el `Attachment` por ID y autorizar (gates + attachable).
- Descargar con `Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name)`.
- Nunca exponer `storage_path(...)` ni paths en UI/errores.

### Eliminación

Reglas:
- Permiso `attachments.manage`.
- Eliminar el archivo en disco y el registro DB de forma segura:
  - Si falta el archivo (ya borrado), permitir limpiar el registro.
  - Si falla el delete por IO, mostrar error claro y no perder trazabilidad (no dejar UI “OK” si no lo está).

### Auditoría (best-effort)

Reusar `AuditRecorder` (Epic 8.1) con `context` allowlist:
- Acciones sugeridas (constantes en `App\Models\AuditLog`):
  - `attachments.upload`
  - `attachments.delete`
  - (Opcional) `attachments.download` si se decide auditar descargas (nota: no auditar lecturas en general; justificar si se incluye).

Contexto permitido (ejemplo):
- `summary` (string corto)
- `reason` (si aplica)
- `product_id` / `asset_id` / `employee_id`
- (agregar `attachment_id` al allowlist si hace falta; mantenerlo scalar)

<!-- template-output: architecture_compliance -->

## Architecture Compliance (must-follow)

- **Livewire-first:** el panel de adjuntos es un componente Livewire reusable (como `NotesPanel`).
- **Controllers solo “bordes”:** la descarga de adjuntos se expone con un controller dedicado (stream/download) porque es un borde I/O.
- **RBAC server-side:** usar gates `attachments.view` / `attachments.manage` (ver `docsBmad/rbac.md` y `gatic/app/Providers/AuthServiceProvider.php`).
- **Storage local privado:** disk `local` apunta a `storage/app/private` (ver `gatic/config/filesystems.php`). No publicar adjuntos vía `storage:link`.
- **Errores con `error_id`:** ante fallos inesperados (IO, storage, DB) usar el patrón de `ErrorReporter` (ver `gatic/app/Support/Errors/ErrorReporter.php`) y mostrar feedback consistente (mensaje humano + `error_id`) sin revelar paths.
- **Auditoría best-effort:** emitir eventos sin bloquear (reusar `AuditRecorder` y allowlist; no auditar contenido del archivo).

<!-- template-output: library_framework_requirements -->

## Library / Framework Requirements

- **Laravel + Livewire (versiones del repo):**
  - Laravel/framework: `v11.47.0` (desde `gatic/composer.lock`)
  - Livewire: `v3.7.3` (desde `gatic/composer.lock`)
- **Subida de archivos:** usar `Livewire\\WithFileUploads` (sin paquetes extra tipo “media library”).
- **Storage:** usar `Illuminate\\Support\\Facades\\Storage` con disk `local` (privado) y descarga vía controller.
- **UI:** Bootstrap 5 + Bootstrap Icons (ya adoptado por UX).
- **No introducir dependencias nuevas** para adjuntos en MVP (evitar Spatie u otros) salvo que el stack lo requiera explícitamente.

<!-- template-output: file_structure_requirements -->

## File Structure Requirements (expected changes)

**Nuevos (MVP):**
- `gatic/database/migrations/*_create_attachments_table.php`
- `gatic/app/Models/Attachment.php`
- `gatic/app/Livewire/Ui/AttachmentsPanel.php`
- `gatic/resources/views/livewire/ui/attachments-panel.blade.php`
- `gatic/app/Http/Controllers/Attachments/DownloadAttachmentController.php`
- `gatic/tests/Feature/Attachments/AttachmentsRbacTest.php`
- `gatic/tests/Feature/Attachments/AttachmentsUploadValidationTest.php`
- `gatic/tests/Feature/Attachments/AttachmentsDownloadTest.php`
- `gatic/tests/Feature/Attachments/AttachmentsDeleteTest.php`

**Modificados (MVP):**
- `gatic/routes/web.php` (ruta de descarga protegida)
- `gatic/app/Models/Product.php` (relación `attachments()` morphMany)
- `gatic/app/Models/Asset.php` (relación `attachments()` morphMany)
- `gatic/app/Models/Employee.php` (relación `attachments()` morphMany)
- `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (integrar panel)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (integrar panel)
- `gatic/resources/views/livewire/employees/employee-show.blade.php` (integrar panel)
- `gatic/app/Models/AuditLog.php` (constantes `ACTION_ATTACHMENT_*`)
- `gatic/app/Support/Audit/AuditRecorder.php` (si hace falta agregar `attachment_id` al allowlist)
- `gatic/config/gatic.php` (política de tamaño/tipos permitidos, si se decide centralizar)

<!-- template-output: testing_requirements -->

## Testing Requirements

### Feature tests (mínimo)

- **RBAC (crítico):**
  - Admin/Editor: pueden listar/descargar/subir/eliminar.
  - Lector: no puede ver/descargar ni mutar (403/404 consistente).
- **Upload validation:**
  - Rechaza tipo no permitido.
  - Rechaza tamaño > límite.
  - Rechaza nombre vacío / archivo corrupto (cuando aplique).
- **Storage correctness:**
  - Se guarda en disk `local` (privado) bajo `attachments/...`.
  - El filename en disco es UUID (no contiene el nombre original).
  - DB guarda `original_name`, `mime_type`, `size_bytes`, `uploaded_by_user_id`.
- **Download:**
  - Respuesta correcta con `Content-Disposition` usando `original_name`.
  - No permite download sin autorización (anti-enumeración).
- **Delete:**
  - Elimina registro DB y archivo en disco.
  - Maneja gracefully archivo faltante (limpieza DB permitida).
- **Auditoría best-effort:**
  - Subida/eliminación disparan `RecordAuditLog` (usar `Queue::fake()`).
  - `context` respeta allowlist (sin contenido del archivo).

### Special Checks (anti-regresiones BMAD)

- **UX long-request (lección Epic 5–6):**
  - Si el panel lista adjuntos y puede tardar >3s (p.ej. cientos de adjuntos), integrar `<x-ui.long-request />`.
- **Soft-delete regression (lección Epic 6):**
  - Si el attachable usa SoftDeletes y el detalle normal excluye `deleted_at`, verificar que el panel de adjuntos NO use `withTrashed()` por default y no exponga adjuntos de registros eliminados en vistas normales.

<!-- template-output: previous_story_intelligence -->

## Previous Story Intelligence (must-apply)

### De Story 8.1 (Auditoría best-effort)

- Reusar `AuditRecorder` para emitir eventos (job + `afterCommit()`).
- Mantener `context` en allowlist y **no** meter payloads grandes ni datos sensibles.
- Si falla auditoría, la operación principal continúa (NFR8) y se loggea warning.

### De Story 8.2 (Panel reusable en detalles)

- Reusar el patrón “panel UI reusable” (`NotesPanel`) para evitar duplicar lógica en cada pantalla.
- Si se usa paginación, usar un **paginador con nombre dedicado** (evita colisión con `page` de la pantalla).
- Autorización siempre server-side en `mount()`, `render()` y acciones mutantes.

<!-- template-output: git_intelligence_summary -->

## Git Intelligence Summary (recent patterns)

Commits recientes relevantes (orden más reciente):
- `4932112` feat(notes): notas manuales en entidades relevantes (Product/Asset/Employee)
- `6f39880` feat(audit): implementacion modulo auditoria best-effort (Story 8.1)

Implicaciones para 8.3:
- Mantener el mismo patrón de organización: `app/Models`, `app/Livewire/Ui`, `app/Support`, `app/Jobs`, controllers solo bordes.
- Reusar `AuditRecorder` y el estilo de tests Feature existentes (Storage/Queue fakes).

<!-- template-output: latest_tech_information -->

## Latest Tech Information (2026-01-24)

- Livewire maneja uploads con archivos temporales en `livewire-tmp/`; la validación se hace igual que en Laravel estándar. Considerar:
  - Mantener reglas explícitas en el componente (tipo/tamaño).
  - Si se requiere, ajustar reglas globales de uploads temporales en `gatic/config/livewire.php` (sin aumentar límites sin justificación).
- Laravel `Storage::download($path, $name, $headers)` permite forzar el nombre que el usuario ve (usar `original_name`).
- Mantener el enfoque “MVP on-prem”: storage local privado + controller con autorización; evitar features “cloud-first”.

<!-- template-output: project_context_reference -->

## Project Context Reference (must-read)

- `docsBmad/project-context.md`
  - Reglas base (roles, adjuntos, errores, on-prem).
- `project-context.md`
  - Reglas críticas para agentes (idioma, stack, tooling Windows).
- `docsBmad/rbac.md`
  - Gates `attachments.*` y regla de aplicación server-side.
- `_bmad-output/implementation-artifacts/architecture.md`
  - Livewire-first; controllers bordes; storage local privado; error_id.
- `_bmad-output/implementation-artifacts/ux.md`
  - Desktop-first, long-request, iconografía.
- `_bmad-output/implementation-artifacts/8-1-auditoria-consultable-best-effort.md`
  - Reuso de `AuditRecorder` y best-effort.
- `_bmad-output/implementation-artifacts/8-2-notas-manuales-en-entidades-relevantes.md`
  - Patrón panel reusable + tests.
- `gatic/config/filesystems.php`
  - Disk `local` privado (`storage/app/private`).
- `gatic/app/Support/Errors/ErrorReporter.php`
  - Patrón de `error_id` para fallos inesperados.

<!-- template-output: story_completion_status -->

## Tasks / Subtasks

- [x] 1) Persistencia `attachments` (AC: 1–5)
  - [x] Migración `attachments` (morph + FK uploader + índices)
  - [x] Modelo `App\\Models\\Attachment` + relaciones
  - [x] Relación `attachments()` en `Product`, `Asset`, `Employee`
- [x] 2) UI: Panel reusable de adjuntos (AC: 1–4)
  - [x] Livewire `App\\Livewire\\Ui\\AttachmentsPanel` (list + upload + delete)
  - [x] Vista `attachments-panel.blade.php` (tabla densa + empty state + toasts)
  - [x] Integración en pantallas de detalle (Product/Asset/Employee)
- [x] 3) Descarga segura (AC: 3,5)
  - [x] Controller "borde" `DownloadAttachmentController` con authorize
  - [x] Ruta protegida + naming consistente
- [x] 4) Auditoría best-effort (AC: 6)
  - [x] Agregar constantes `ACTION_ATTACHMENT_*` en `AuditLog`
  - [x] Emitir eventos en upload/delete (y download si aplica)
  - [x] Validar allowlist en `AuditRecorder` (agregar `attachment_id` si es necesario)
- [x] 5) Tests Feature (AC: 1–6)
  - [x] RBAC: Lector bloqueado; Admin/Editor ok
  - [x] Upload validation + storage
  - [x] Download + anti-enumeración
  - [x] Delete + cleanup de archivo
  - [x] Auditoría (Queue fake + payload allowlist)

## Dev Notes

### Decisiones sugeridas (MVP)

- **Attachables MVP:** Product/Asset/Employee (alineado con notas manuales).
- **Path en storage:** `attachments/<attachable>/<id>/<uuid>` (evita colisiones y facilita limpieza).
- **Nombre seguro:** UUID sin extensión; descargar con nombre original.

### Preguntas pendientes (resolver antes de implementar)

1) ¿Cuáles son los tipos de archivo permitidos exactos en MVP (solo PDF/imagenes, o también Office)?  
2) ¿Tamaño máximo por archivo (10MB vs 25MB) y límite de adjuntos por registro?  
3) ¿Se audita la descarga (lectura) o solo subidas/eliminaciones?  
4) ¿Se requiere “ver” inline (preview) para imágenes/PDF o solo descargar?  

## Story Completion Status

- Status: **done**
- Completion note: **Implementation complete. All 5 tasks done: DB migration, Attachment model, AttachmentsPanel Livewire component, DownloadAttachmentController, audit integration, and 51 feature tests (all passing). Full test suite: 531 tests pass with no regressions.**

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (claude-opus-4-5-20251101)

### Implementation Plan

1. Created DB migration `2026_01_24_000000_create_attachments_table.php` with morph columns, FK to users, and composite indexes
2. Created `App\Models\Attachment` model with constants for max size (10MB) and allowed MIME types
3. Added `attachments()` morphMany relation to Product, Asset, Employee models
4. Created `App\Livewire\Ui\AttachmentsPanel` component following NotesPanel pattern
5. Created `attachments-panel.blade.php` view with upload form, table list, and delete buttons
6. Integrated panel into product-show, asset-show, employee-show views (behind `@can('attachments.view')`)
7. Created `DownloadAttachmentController` as "border" controller for secure file streaming
8. Added download route with `attachments.view` gate middleware
9. Added `ACTION_ATTACHMENT_UPLOAD` and `ACTION_ATTACHMENT_DELETE` constants to AuditLog
10. Added `attachment_id` to AuditRecorder allowlist
11. Created 51 feature tests covering RBAC, upload validation, download, delete, and audit

### Completion Notes

- All acceptance criteria (AC1-AC6) satisfied
- RBAC: Admin/Editor can view/upload/delete; Lector blocked completely (403)
- Storage: Files saved in `storage/app/private/attachments/{Type}/{id}/{uuid}` with UUID names
- Validation: PDF, images (PNG/JPG/WEBP), TXT, DOCX, XLSX allowed; 10MB max; executables blocked
- Download: Secure via authenticated controller with entity visibility check
- Anti-enumeration: Cannot access attachments of different entities or soft-deleted records
- Audit: Best-effort logging for upload/delete with context allowlist (no file content)
- 51 tests added; full suite passes (531 tests, 0 failures)
- Post-review fixes: upload cleanup on DB failure; delete audit includes `attachment_id`; storage delete failures logged (non-blocking)

### Debug Log References

- `_bmad-output/implementation-artifacts/sprint-status.yaml` (auto-selección del primer story en `backlog`: `8-3-adjuntos-seguros-con-control-de-acceso`)
- `_bmad-output/implementation-artifacts/epics.md` (Story 8.3 / FR34, NFR6)
- `_bmad-output/implementation-artifacts/prd.md` (FR34, NFR6, NFR4, NFR5)
- `_bmad-output/implementation-artifacts/architecture.md` (storage privado, controllers bordes)
- `docsBmad/rbac.md` + `gatic/app/Providers/AuthServiceProvider.php` (gates `attachments.*`)
- `gatic/config/filesystems.php` (disk `local` privado)
- `gatic/composer.lock` (versiones Laravel/Livewire)
- `_bmad-output/implementation-artifacts/8-1-auditoria-consultable-best-effort.md` (AuditRecorder)
- `_bmad-output/implementation-artifacts/8-2-notas-manuales-en-entidades-relevantes.md` (patrón panel reusable)

### File List

**New files:**
- `gatic/database/migrations/2026_01_24_000000_create_attachments_table.php`
- `gatic/app/Models/Attachment.php`
- `gatic/app/Livewire/Ui/AttachmentsPanel.php`
- `gatic/resources/views/livewire/ui/attachments-panel.blade.php`
- `gatic/app/Http/Controllers/Attachments/DownloadAttachmentController.php`
- `gatic/tests/Feature/Attachments/AttachmentsRbacTest.php`
- `gatic/tests/Feature/Attachments/AttachmentsUploadValidationTest.php`
- `gatic/tests/Feature/Attachments/AttachmentsDownloadTest.php`
- `gatic/tests/Feature/Attachments/AttachmentsDeleteTest.php`
- `gatic/tests/Feature/Attachments/AttachmentsAuditTest.php`

**Modified files:**
- `gatic/routes/web.php` (added download route)
- `gatic/app/Models/Product.php` (added attachments() relation)
- `gatic/app/Models/Asset.php` (added attachments() relation)
- `gatic/app/Models/Employee.php` (added attachments() relation)
- `gatic/app/Models/AuditLog.php` (added ACTION_ATTACHMENT_* constants)
- `gatic/app/Jobs/RecordAuditLog.php` (audit job robustness improvements)
- `gatic/app/Livewire/Ui/NotesPanel.php` (minor audit/UX alignment)
- `gatic/app/Support/Audit/AuditRecorder.php` (added attachment_id to allowlist)
- `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (integrated panel)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (integrated panel)
- `gatic/resources/views/livewire/employees/employee-show.blade.php` (integrated panel)
- `gatic/tests/Feature/PendingTasks/PendingTaskLockOverrideTest.php` (audit assertions alignment)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status: in-progress → review)
- `_bmad-output/implementation-artifacts/8-3-adjuntos-seguros-con-control-de-acceso.md` (this file)

## Senior Developer Review (AI)

Date: 2026-01-24  
Outcome: **Approved (fixes applied)**  

### Hallazgos y fixes aplicados

- Se corrigió el riesgo de **archivo huérfano** si el upload guarda en disco pero falla la inserción DB (cleanup best-effort).
- Se mejoró la **observabilidad**: se loguean fallos al borrar archivo en storage (sin afectar UX).
- Auditoría: delete ahora incluye `attachment_id` (mejor trazabilidad).
- Story: File List sincronizado con cambios reales de git.

## Change Log

| Date       | Author            | Changes |
|------------|-------------------|---------|
| 2026-01-24 | Claude Opus 4.5    | Implementación completa de Story 8.3 (adjuntos seguros + RBAC + auditoría + tests) |
| 2026-01-24 | Codex (AI Review)  | Code review + fixes: cleanup upload en fallo DB, logging en delete storage, auditoría delete con `attachment_id`, File List sync, 1 test adicional |
