---
project: GATIC
last_updated: 2025-12-27
sources:
  - _bmad-output/analysis/brainstorming-session-2025-12-25.md
  - GitHub Milestones Gate 0–5 (CarlosVerasteguii/Proyecto-GATIC)
  - GitHub Project “GATI-C” (Project v2 #3)
---

# Project Context Bible — GATIC

Este documento es la referencia principal (“bible”) para decisiones de producto/UX/arquitectura. Si algo contradice este archivo, se corrige lo que contradiga.

## Visión (MVP)

- Producto: sistema interno (intranet/on-prem) para gestionar **Inventario** y **Activos**, con **trazabilidad** y **operación diaria** (asignaciones/préstamos).
- Enfoque: valor alto en UX/operatividad con complejidad técnica controlada (sin WebSockets; preferir polling).
- Hitos: ejecución por **Gates 0–5** como milestones internos (ver `docsBmad/gates-execution.md`).

## Usuarios y Roles

Roles (MVP, fijos):

- **Admin**: operación completa + override (ej. forzar liberación de locks) + acceso a adjuntos.
- **Editor**: opera inventario/activos, pero con restricciones admin-only (ej. gestión de usuarios).
- **Lector**: consulta (sin acciones destructivas ni adjuntos en MVP).

Nota: **Empleado (RPE)** NO es lo mismo que “Usuario del sistema”. Los préstamos/asignaciones se hacen a **Empleados**.

## Glosario y Modelo Mental

Términos “canónicos”:

- **Producto**: modelo/catálogo (ej. “Laptop Dell X”), puede ser serializado o por cantidad.
- **Activo**: unidad física (ej. una laptop concreta).
- **Categoría**:
  - `is_serialized`: define si se gestiona por unidad (Activos) o por cantidad (stock agregado).
  - `requires_asset_tag`: para categorías que requieren etiqueta interna (`asset_tag`).
- **Serial**: identificador dentro del Producto; unicidad recomendada: **(product_id + serial)**.
- **asset_tag**: etiqueta interna empresa; si existe debe ser **única global**.

Estados (serializados, MVP):

- `Disponible`, `Asignado`, `Prestado`, `Pendiente de Retiro`, `Retirado`

Semántica QTY (inventario):

- **No disponibles** = `Asignado + Prestado + Pendiente de Retiro`
- **Disponibles** = `Total - No disponibles`
- `Retirado` no cuenta en inventario por defecto (solo vía filtro/historial).

## Restricciones (no negociables)

- Diseño: **seguir `03-visual-style-guide.md`** (restricción dura).
- Intranet on-prem: priorizar simplicidad operativa y paridad local↔prod (Docker/Sail/Compose).
- Concurrencia en “Tareas Pendientes”: debe existir lock/claim (ver sección Locks).

## Decisiones de UX y Operación

- Polling (sin WebSockets):
  - Badges/estados en listas: cada **15s** (`wire:poll.visible`).
  - Métricas dashboard: cada **60s** (`wire:poll.visible`).
  - Locks heartbeat: cada **10s**.
- Si API tarda >3s: skeleton loaders + mensaje de progreso + opción de cancelar búsqueda.
- Errores:
  - Dev: detalle técnico completo.
  - Prod: mensaje amigable + **ID de error**; detalle completo solo Admin (“TI autenticado”).
- Soft-delete: retención indefinida hasta que Admin vacíe papelera.
- Adjuntos: sanitizar nombre (guardar UUID en disco), mostrar nombre original en UI.
- Auditoría: **best effort**, no bloqueante; si falla, operación del usuario procede (registrar en log interno).

## Política de Locks (Tareas Pendientes)

Objetivo: evitar que dos editores procesen la misma Tarea simultáneamente.

- El lock se adquiere al hacer clic en **“Procesar”** (preventivo, antes del formulario).
- Timeout: **15 minutos** (rolling por actividad/heartbeat).
- Heartbeat: cada **10s** renueva lock si la pestaña está activa.
- Unlock “best effort” al cerrar pestaña/ventana + fallback por timeout.
- Para liberar rápido si se cierra sin unlock: **lease TTL 3 min** renovado por heartbeat.
- Idle guard: solo renovar si hubo actividad real del usuario en los últimos **2 min**.
- Admin puede **liberar/forzar reclamo** del lock (acción auditada).
- MVP: “Solicitar liberación” es informativo (sin notificaciones automáticas).

## Baseline Técnico (stack objetivo)

- Backend: **Laravel 11**, **PHP 8.2+**, **MySQL 8**.
- UI: Blade + **Livewire 3** + **Bootstrap 5** (alineado a guía corporativa).
- Auth: **Breeze (Blade)**, adaptado a Bootstrap (Breeze trae Tailwind por defecto).
- Autorización: Policies/Gates + roles/permisos (ej. Spatie, por validar).
- Queue: driver `database` (suficiente para auditoría/tareas async).
- Build: Vite/NPM.
- Local dev: **Laravel Sail**.
- Producción (Compose): Nginx + PHP-FPM (por definir detalles al tener servidor).
- Calidad/CI: `pint + phpunit + larastan` como mínimo; trunk-based; merge solo con CI verde.
- Seeders robustos (roles + admin + datos demo) para reinicios frecuentes de BD local.

## Roadmap por Gates (0–5)

Definición de Done (DoD) resumida:

- **Gate 0 (Repo listo):** Sail+MySQL8, auth+roles, CI verde, seeders base.
- **Gate 1 (UX base):** layout + componentes UX + errores prod con ID + patrón polling.
- **Gate 2 (Inventario + detalles):** listado Productos (QTY+tooltip) + búsqueda unificada + detalle Producto/Activo.
- **Gate 3 (Operación diaria):** empleados (RPE), estados+acciones serializados, no serializados por cantidad, dashboard mínimo.
- **Gate 4 (Tareas Pendientes):** carga rápida tipo carrito + procesamiento por renglón + finalización parcial + locks.
- **Gate 5 (Trazabilidad):** auditoría+notas, adjuntos, papelera (soft-delete/restaurar/vaciar).

Ejecución detallada: ver `docsBmad/gates-execution.md`.

## Fuentes

- Brainstorming: `../_bmad-output/analysis/brainstorming-session-2025-12-25.md`
- Milestones/Issues: `https://github.com/CarlosVerasteguii/Proyecto-GATIC/milestones`
- Project Board: `https://github.com/users/CarlosVerasteguii/projects/3`
