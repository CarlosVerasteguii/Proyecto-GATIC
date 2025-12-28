# Gates 0–5 — Resumen ejecutable (GitHub Milestones/Project)

Fuente principal de alcance: `_bmad-output/analysis/brainstorming-session-2025-12-25.md` + Milestones “Gate 0…5”.

> Nota: este documento es una **vista opcional** para mantener GitHub (Milestones/Project/Issues) ordenado.
> La fuente de verdad del backlog y ejecución es BMAD: `_bmad-output/project-planning-artifacts/epics.md` + `_bmad-output/implementation-artifacts/sprint-status.yaml`.

## Reglas de ejecución

- Trabajar **un Gate a la vez**: no se considera “hecho” hasta cumplir el DoD del Gate.
- En GitHub:
  - **Milestone** = Gate.
  - **Issues tipo épica** = `G{N}-E{NN}` (agregan contexto y checklist de tareas).
  - **Issues tipo tarea** = `G{N}-T{NN}` (unidad de ejecución).
  - Project “GATI-C” (v2) usa `Status: Todo / In Progress / Done` (hoy todo está en `Todo`).

---

## Gate 0 — Repo listo (fundación)

**Milestone (GitHub):** “Repo listo (fundación): Laravel Sail, Auth+roles, CI verde, Seeders”

**DoD (mínimo):**

- App levanta en Sail; MySQL 8 listo; seeders crean Admin/roles/datos mínimos.
- Login/roles funcionando y bloqueos por rol (Editor/Lector no entran a usuarios).
- CI en verde (Pint + PHPUnit + Larastan) en PR/merge.

**Épicas:**

- #1 `G0-E01` Esqueleto y entorno
  - #5 `G0-T01` Decidir layout del repo
  - #6 `G0-T02` Inicializar proyecto Laravel 11
  - #7 `G0-T03` Instalar y configurar Laravel Sail
  - #8 `G0-T04` Documentar setup local en README.md
- #2 `G0-E02` UI stack base (Bootstrap)
  - #9 `G0-T05` Instalar Laravel Breeze (Blade)
  - #10 `G0-T06` Re-maquetar Breeze a Bootstrap 5
  - #11 `G0-T07` Configurar Vite para Bootstrap
  - #12 `G0-T08` Instalar Livewire 3
- #3 `G0-E03` Seguridad (roles fijos) + rutas protegidas
  - #13 `G0-T09` Implementar roles fijos
  - #14 `G0-T10` Definir policies/gates base
  - #15 `G0-T11` Hardening de acceso
- #4 `G0-E04` Calidad y CI
  - #16 `G0-T12` Configurar Laravel Pint
  - #17 `G0-T13` Configurar Larastan
  - #18 `G0-T14` Crear GitHub Action (CI)
  - #19 `G0-T15` Agregar tests smoke

---

## Gate 1 — UX base + navegación

**Milestone (GitHub):** “UX base + navegación: Layout, componentes reutilizables, errores, polling”

**DoD (mínimo):**

- Layout base (sidebar/topbar) + componentes base (toasts, loaders, errors).
- Polling indicator implementado donde aplique; cancelación en búsquedas.

**Épicas:**

- #20 `G1-E01` Layout + navegación
  - #24 `G1-T01` Implementar layout base
  - #25 `G1-T02` Definir menú por rol
  - #26 `G1-T03` Implementar topbar
- #21 `G1-E02` Componentes UX reutilizables
  - #27 `G1-T04` Componente Toast + Deshacer
  - #28 `G1-T05` Skeleton loader estándar
  - #29 `G1-T06` Patrón Cancelar en búsquedas
  - #30 `G1-T07` Indicador “Actualizado hace Xs”
- #22 `G1-E03` Errores
  - #31 `G1-T08` Middleware/handler para ID de error
  - #32 `G1-T09` Página/Modal de error amigable
- #23 `G1-E04` Polling base
  - #33 `G1-T10` Implementar patrón `wire:poll.visible`

---

## Gate 2 — Inventario navegable (Productos + Detalles)

**Milestone (GitHub):** “Inventario navegable: Productos, Activos, búsqueda unificada, detalles”

**DoD (mínimo):**

- Listado de Productos con QTY+tooltip y buscador unificado.
- Detalle Producto (tabs) y Detalle Activo (tabs) navegables.

**Épicas:**

- #34 `G2-E01` Modelo de datos “columna vertebral”
  - #38 `G2-T01` Migraciones categorías/marcas/ubicaciones/productos
  - #39 `G2-T02` Migraciones assets (serializados)
  - #40 `G2-T03` Constraints únicos
  - #41 `G2-T04` Seeders demo
- #35 `G2-E02` Listado Inventario (Productos)
  - #42 `G2-T05` Vista Inventario Productos (tabla)
  - #43 `G2-T06` QTY badges + tooltip
  - #44 `G2-T07` Semántica QTY
  - #45 `G2-T08` Sin stock (resaltar rojo)
  - #46 `G2-T09` Filtros (categoría, marca, tipo)
  - #47 `G2-T10` Ubicación en listado
  - #48 `G2-T11` Polling 15s (badges)
- #36 `G2-E03` Búsqueda unificada
  - #49 `G2-T12` Autocomplete agrupado
  - #50 `G2-T13` Match exacto → Detalle Activo
  - #51 `G2-T14` NO indexar Tareas Pendientes
- #37 `G2-E04` Detalle Producto / Activo
  - #52 `G2-T15` Detalle Producto tabs
  - #53 `G2-T16` Detalle Activo tabs
  - #54 `G2-T17` Acciones visibles por estado (placeholder)

---

## Gate 3 — Operación diaria

**Milestone (GitHub):** “Operación diaria: Empleados, estados, préstamos, no serializados, dashboard”

**DoD (mínimo):**

- Directorio Empleados (RPE) completo con ficha y activos asociados.
- Serializados: asignar/prestar/devolver + pendientes/retiro con reglas.
- No serializados: asignación/préstamo por cantidad + kardex + ajuste manual Admin.

**Épicas:**

- #55 `G3-E01` Empleados (RPE)
  - #60 `G3-T01` Migración employees
  - #61 `G3-T02` UI listado + búsqueda + ficha empleado
  - #62 `G3-T03` Autocomplete + agregar empleado inline
- #56 `G3-E02` Estados y acciones (serializados)
  - #63 `G3-T04` Definir enum/constantes de estado
  - #64 `G3-T05` Implementar transiciones + validaciones
  - #65 `G3-T06` Regla: Asignado no se presta
  - #66 `G3-T07` UI + comandos para todas las acciones
- #57 `G3-E03` Préstamos (vencimiento)
  - #67 `G3-T08` Modelo loans
  - #68 `G3-T09` Vencimiento opcional (badge)
  - #69 `G3-T10` Escalamiento 3 días → Urgente/Crítico
  - #70 `G3-T11` Acción “Definir vencimiento”
- #58 `G3-E04` No serializados (cantidad)
  - #71 `G3-T12` Definir dónde vive stock_total
  - #72 `G3-T13` Asignaciones/préstamos por cantidad
  - #73 `G3-T14` Kardex (entradas, retiros, ajustes)
  - #74 `G3-T15` Ajuste manual Admin con motivo
- #59 `G3-E05` Dashboard (métricas)
  - #75 `G3-T16` Implementar dashboard mínimo
  - #76 `G3-T17` Polling 60s (métricas)

---

## Gate 4 — Tareas Pendientes (Carga/Procesamiento/Locks)

**Milestone (GitHub):** “Tareas Pendientes: Carga rápida, procesamiento, locks concurrencia”

**DoD (mínimo):**

- Carga Rápida carrito + procesamiento por renglón (borrador) + finalización parcial.
- Locks a nivel tarea + read-only cuando locked (Admin force unlock).

**Épicas:**

- #77 `G4-E01` Modelo de tareas
  - #81 `G4-T01` Migraciones pending_tasks + pending_task_lines
  - #82 `G4-T02` Estados por renglón
  - #83 `G4-T03` Validación series alfanuméricas
- #78 `G4-E02` Carga Rápida (carrito)
  - #84 `G4-T04` UI agregar productos o placeholder
  - #85 `G4-T05` Placeholder tipo obligatorio
  - #86 `G4-T06` Serializado (pegar series) / Cantidad (entero)
- #79 `G4-E03` Procesamiento (renglón + aplicación diferida)
  - #87 `G4-T07` Pantalla procesamiento (editar renglones)
  - #88 `G4-T08` Finalizar con aplicación parcial
  - #89 `G4-T09` Reintento (corregir errores)
  - #90 `G4-T10` Sin descartar renglón (MVP)
- #80 `G4-E04` Locks (concurrencia)
  - #91 `G4-T11` Campos/tabla de lock
  - #92 `G4-T12` Claim preventivo + read-only
  - #93 `G4-T13` Heartbeat + TTL + timeout + idle guard
  - #94 `G4-T14` Solicitar liberación (modal)
  - #95 `G4-T15` Admin forzar liberación

---

## Gate 5 — Trazabilidad y evidencia

**Milestone (GitHub):** “Trazabilidad y evidencia: Auditoría, adjuntos, papelera”

**DoD (mínimo):**

- Auditoría + notas manuales; adjuntos (Admin/Editor); papelera (soft-delete/restaurar/vaciar).

**Épicas:**

- #96 `G5-E01` Auditoría best-effort + notas
  - #99 `G5-T01` Tabla audit_logs
  - #100 `G5-T02` Disparo async + fallback silent
  - #101 `G5-T03` Notas manuales + UI
- #97 `G5-E02` Adjuntos
  - #102 `G5-T04` Tabla attachments
  - #103 `G5-T05` Storage UUID + sanitizar
  - #104 `G5-T06` Permisos Admin/Editor
  - #105 `G5-T07` Límites 100MB + validaciones
- #98 `G5-E03` Papelera
  - #106 `G5-T08` Soft deletes consistentes
  - #107 `G5-T09` UI Papelera (listar, restaurar, vaciar)
  - #108 `G5-T10` Restauración conserva historial
