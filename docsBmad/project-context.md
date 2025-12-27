---
project: GATIC
last_updated: 2025-12-27
sources:
  - _bmad-output/analysis/brainstorming-session-2025-12-25.md
  - GitHub Milestones Gate 0ÔÇô5 (CarlosVerasteguii/Proyecto-GATIC)
  - GitHub Project ÔÇ£GATI-CÔÇØ (Project v2 #3)
---

# Project Context Bible ÔÇö GATIC

Este documento es la referencia principal (ÔÇ£bibleÔÇØ) para decisiones de producto/UX/arquitectura. Si algo contradice este archivo, se corrige lo que contradiga.

## Visi├│n (MVP)

- Producto: sistema interno (intranet/onÔÇæprem) para gestionar **Inventario** y **Activos**, con **trazabilidad** y **operaci├│n diaria** (asignaciones/pr├®stamos).
- Enfoque: valor alto en UX/operatividad con complejidad t├®cnica controlada (sin WebSockets; preferir polling).
- Hitos: ejecuci├│n por **Gates 0ÔÇô5** como milestones internos (ver `docsBmad/gates-execution.md`).

## Usuarios y Roles

Roles (MVP, fijos):

- **Admin**: operaci├│n completa + override (ej. forzar liberaci├│n de locks) + acceso a adjuntos.
- **Editor**: opera inventario/activos, pero con restricciones admin-only (ej. gesti├│n de usuarios).
- **Lector**: consulta (sin acciones destructivas ni adjuntos en MVP).

Nota: **Empleado (RPE)** NO es lo mismo que ÔÇ£Usuario del sistemaÔÇØ. Los pr├®stamos/asignaciones se hacen a **Empleados**.

## Glosario y Modelo Mental

T├®rminos ÔÇ£can├│nicosÔÇØ:

- **Producto**: modelo/cat├ílogo (ej. ÔÇ£Laptop Dell XÔÇØ), puede ser serializado o por cantidad.
- **Activo**: unidad f├¡sica (ej. una laptop concreta).
- **Categor├¡a**:
  - `is_serialized`: define si se gestiona por unidad (Activos) o por cantidad (stock agregado).
  - `requires_asset_tag`: para categor├¡as que requieren etiqueta interna (`asset_tag`).
- **Serial**: identificador dentro del Producto; unicidad recomendada: **(product_id + serial)**.
- **asset_tag**: etiqueta interna empresa; si existe debe ser **├║nica global**.

Estados (serializados, MVP):

- `Disponible`, `Asignado`, `Prestado`, `Pendiente de Retiro`, `Retirado`

Sem├íntica QTY (inventario):

- **No disponibles** = `Asignado + Prestado + Pendiente de Retiro`
- **Disponibles** = `Total - No disponibles`
- `Retirado` no cuenta en inventario por defecto (solo v├¡a filtro/historial).

## Restricciones (no negociables)

- Dise├▒o: **seguir `03-visual-style-guide.md`** (restricci├│n dura).
- Intranet onÔÇæprem: priorizar simplicidad operativa y paridad localÔåöprod (Docker/Sail/Compose).
- Concurrencia en ÔÇ£Tareas PendientesÔÇØ: debe existir lock/claim (ver secci├│n Locks).

## Decisiones de UX y Operaci├│n

- Polling (sin WebSockets):
  - Badges/estados en listas: cada **15s** (`wire:poll.visible`).
  - M├®tricas dashboard: cada **60s** (`wire:poll.visible`).
  - Locks heartbeat: cada **10s**.
- Si API tarda >3s: skeleton loaders + mensaje de progreso + opci├│n de cancelar b├║squeda.
- Errores:
  - Dev: detalle t├®cnico completo.
  - Prod: mensaje amigable + **ID de error**; detalle completo solo Admin (ÔÇ£TI autenticadoÔÇØ).
- Soft-delete: retenci├│n indefinida hasta que Admin vac├¡e papelera.
- Adjuntos: sanitizar nombre (guardar UUID en disco), mostrar nombre original en UI.
- Auditor├¡a: **best effort**, no bloqueante; si falla, operaci├│n del usuario procede (registrar en log interno).

## Pol├¡tica de Locks (Tareas Pendientes)

Objetivo: evitar que dos editores procesen la misma Tarea simult├íneamente.

- El lock se adquiere al hacer clic en **ÔÇ£ProcesarÔÇØ** (preventivo, antes del formulario).
- Timeout: **15 minutos** (rolling por actividad/heartbeat).
- Heartbeat: cada **10s** renueva lock si la pesta├▒a est├í activa.
- Unlock ÔÇ£best effortÔÇØ al cerrar pesta├▒a/ventana + fallback por timeout.
- Para liberar r├ípido si se cierra sin unlock: **lease TTL 3 min** renovado por heartbeat.
- Idle guard: solo renovar si hubo actividad real del usuario en los ├║ltimos **2 min**.
- Admin puede **liberar/forzar reclamo** del lock (acci├│n auditada).
- MVP: ÔÇ£Solicitar liberaci├│nÔÇØ es informativo (sin notificaciones autom├íticas).

## Baseline T├®cnico (stack objetivo)

- Backend: **Laravel 11**, **PHP 8.2+**, **MySQL 8**.
- UI: Blade + **Livewire 3** + **Bootstrap 5** (alineado a gu├¡a corporativa).
- Auth: **Breeze (Blade)**, adaptado a Bootstrap (Breeze trae Tailwind por defecto).
- Autorizaci├│n: Policies/Gates + roles/permisos (ej. Spatie, por validar).
- Queue: driver `database` (suficiente para auditor├¡a/tareas async).
- Build: Vite/NPM.
- Local dev: **Laravel Sail**.
- Producci├│n (Compose): Nginx + PHP-FPM (por definir detalles al tener servidor).
- Calidad/CI: `pint + phpunit + larastan` como m├¡nimo; trunkÔÇæbased; merge solo con CI verde.
- Seeders robustos (roles + admin + datos demo) para reinicios frecuentes de BD local.

## Roadmap por Gates (0ÔÇô5)

Definici├│n de Done (DoD) resumida:

- **Gate 0 (Repo listo):** Sail+MySQL8, auth+roles, CI verde, seeders base.
- **Gate 1 (UX base):** layout + componentes UX + errores prod con ID + patr├│n polling.
- **Gate 2 (Inventario + detalles):** listado Productos (QTY+tooltip) + b├║squeda unificada + detalle Producto/Activo.
- **Gate 3 (Operaci├│n diaria):** empleados (RPE), estados+acciones serializados, no serializados por cantidad, dashboard m├¡nimo.
- **Gate 4 (Tareas Pendientes):** carga r├ípida tipo carrito + procesamiento por rengl├│n + finalizaci├│n parcial + locks.
- **Gate 5 (Trazabilidad):** auditor├¡a+notas, adjuntos, papelera (soft-delete/restaurar/vaciar).

Ejecuci├│n detallada: ver `docsBmad/gates-execution.md`.

## Fuentes

- Brainstorming: `../_bmad-output/analysis/brainstorming-session-2025-12-25.md`
- Milestones/Issues: `https://github.com/CarlosVerasteguii/Proyecto-GATIC/milestones`
- Project Board: `https://github.com/users/CarlosVerasteguii/projects/3`

