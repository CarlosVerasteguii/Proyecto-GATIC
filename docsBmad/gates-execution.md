# Gates 0ÔÇô5 ÔÇö Resumen ejecutable (GitHub Milestones/Project)

Fuente principal de alcance: `_bmad-output/analysis/brainstorming-session-2025-12-25.md` + Milestones ÔÇ£Gate 0ÔÇª5ÔÇØ.

## Reglas de ejecuci├│n

- Trabajar **un Gate a la vez**: no se considera ÔÇ£hechoÔÇØ hasta cumplir el DoD del Gate.
- En GitHub:
  - **Milestone** = Gate.
  - **Issues tipo ├®pica** = `G{N}-E{NN}` (agregan contexto y checklist de tareas).
  - **Issues tipo tarea** = `G{N}-T{NN}` (unidad de ejecuci├│n).
  - Project ÔÇ£GATI-CÔÇØ (v2) usa `Status: Todo / In Progress / Done` (hoy todo est├í en `Todo`).

---

## Gate 0 ÔÇö Repo listo (fundaci├│n)

**Milestone (GitHub):** ÔÇ£Repo listo (fundaci├│n): Laravel Sail, Auth+roles, CI verde, SeedersÔÇØ

**DoD (m├¡nimo):**

- App levanta en Sail; MySQL 8 listo; seeders crean Admin/roles/datos m├¡nimos.
- Login/roles funcionando y bloqueos por rol (Editor/Lector no entran a usuarios).
- CI en verde (Pint + PHPUnit + Larastan) en PR/merge.

**├ëpicas:**

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

## Gate 1 ÔÇö UX base + navegaci├│n

**Milestone (GitHub):** ÔÇ£UX base + navegaci├│n: Layout, componentes reutilizables, errores, pollingÔÇØ

**DoD (m├¡nimo):**

- Layout base (sidebar/topbar) + componentes base (toasts, loaders, errors).
- Polling indicator implementado donde aplique; cancelaci├│n en b├║squedas.

**├ëpicas:**

- #20 `G1-E01` Layout + navegaci├│n
  - #24 `G1-T01` Implementar layout base
  - #25 `G1-T02` Definir men├║ por rol
  - #26 `G1-T03` Implementar topbar
- #21 `G1-E02` Componentes UX reutilizables
  - #27 `G1-T04` Componente Toast + Deshacer
  - #28 `G1-T05` Skeleton loader est├índar
  - #29 `G1-T06` Patr├│n Cancelar en b├║squedas
  - #30 `G1-T07` Indicador ÔÇ£Actualizado hace XsÔÇØ
- #22 `G1-E03` Errores
  - #31 `G1-T08` Middleware/handler para ID de error
  - #32 `G1-T09` P├ígina/Modal de error amigable
- #23 `G1-E04` Polling base
  - #33 `G1-T10` Implementar patr├│n `wire:poll.visible`

---

## Gate 2 ÔÇö Inventario navegable (Productos + Detalles)

**Milestone (GitHub):** ÔÇ£Inventario navegable: Productos, Activos, b├║squeda unificada, detallesÔÇØ

**DoD (m├¡nimo):**

- Listado de Productos con QTY+tooltip y buscador unificado.
- Detalle Producto (tabs) y Detalle Activo (tabs) navegables.

**├ëpicas:**

- #34 `G2-E01` Modelo de datos ÔÇ£columna vertebralÔÇØ
  - #38 `G2-T01` Migraciones categor├¡as/marcas/ubicaciones/productos
  - #39 `G2-T02` Migraciones assets (serializados)
  - #40 `G2-T03` Constraints ├║nicos
  - #41 `G2-T04` Seeders demo
- #35 `G2-E02` Listado Inventario (Productos)
  - #42 `G2-T05` Vista Inventario Productos (tabla)
  - #43 `G2-T06` QTY badges + tooltip
  - #44 `G2-T07` Sem├íntica QTY
  - #45 `G2-T08` Sin stock (resaltar rojo)
  - #46 `G2-T09` Filtros (categor├¡a, marca, tipo)
  - #47 `G2-T10` Ubicaci├│n en listado
  - #48 `G2-T11` Polling 15s (badges)
- #36 `G2-E03` B├║squeda unificada
  - #49 `G2-T12` Autocomplete agrupado
  - #50 `G2-T13` Match exacto ÔåÆ Detalle Activo
  - #51 `G2-T14` NO indexar Tareas Pendientes
- #37 `G2-E04` Detalle Producto / Activo
  - #52 `G2-T15` Detalle Producto tabs
  - #53 `G2-T16` Detalle Activo tabs
  - #54 `G2-T17` Acciones visibles por estado (placeholder)

---

## Gate 3 ÔÇö Operaci├│n diaria

**Milestone (GitHub):** ÔÇ£Operaci├│n diaria: Empleados, estados, pr├®stamos, no serializados, dashboardÔÇØ

**DoD (m├¡nimo):**

- Directorio Empleados (RPE) completo con ficha y activos asociados.
- Serializados: asignar/prestar/devolver + pendientes/retiro con reglas.
- No serializados: asignaci├│n/pr├®stamo por cantidad + kardex + ajuste manual Admin.

**├ëpicas:**

- #55 `G3-E01` Empleados (RPE)
  - #60 `G3-T01` Migraci├│n employees
  - #61 `G3-T02` UI listado + b├║squeda + ficha empleado
  - #62 `G3-T03` Autocomplete + agregar empleado inline
- #56 `G3-E02` Estados y acciones (serializados)
  - #63 `G3-T04` Definir enum/constantes de estado
  - #64 `G3-T05` Implementar transiciones + validaciones
  - #65 `G3-T06` Regla: Asignado no se presta
  - #66 `G3-T07` UI + comandos para todas las acciones
- #57 `G3-E03` Pr├®stamos (vencimiento)
  - #67 `G3-T08` Modelo loans
  - #68 `G3-T09` Vencimiento opcional (badge)
  - #69 `G3-T10` Escalamiento 3 d├¡as ÔåÆ Urgente/Cr├¡tico
  - #70 `G3-T11` Acci├│n ÔÇ£Definir vencimientoÔÇØ
- #58 `G3-E04` No serializados (cantidad)
  - #71 `G3-T12` Definir d├│nde vive stock_total
  - #72 `G3-T13` Asignaciones/pr├®stamos por cantidad
  - #73 `G3-T14` Kardex (entradas, retiros, ajustes)
  - #74 `G3-T15` Ajuste manual Admin con motivo
- #59 `G3-E05` Dashboard (m├®tricas)
  - #75 `G3-T16` Implementar dashboard m├¡nimo
  - #76 `G3-T17` Polling 60s (m├®tricas)

---

## Gate 4 ÔÇö Tareas Pendientes (Carga/Procesamiento/Locks)

**Milestone (GitHub):** ÔÇ£Tareas Pendientes: Carga r├ípida, procesamiento, locks concurrenciaÔÇØ

**DoD (m├¡nimo):**

- Carga R├ípida carrito + procesamiento por rengl├│n (borrador) + finalizaci├│n parcial.
- Locks a nivel tarea + read-only cuando locked (Admin force unlock).

**├ëpicas:**

- #77 `G4-E01` Modelo de tareas
  - #81 `G4-T01` Migraciones pending_tasks + pending_task_lines
  - #82 `G4-T02` Estados por rengl├│n
  - #83 `G4-T03` Validaci├│n series alfanum├®ricas
- #78 `G4-E02` Carga R├ípida (carrito)
  - #84 `G4-T04` UI agregar productos o placeholder
  - #85 `G4-T05` Placeholder tipo obligatorio
  - #86 `G4-T06` Serializado (pegar series) / Cantidad (entero)
- #79 `G4-E03` Procesamiento (rengl├│n + aplicaci├│n diferida)
  - #87 `G4-T07` Pantalla procesamiento (editar renglones)
  - #88 `G4-T08` Finalizar con aplicaci├│n parcial
  - #89 `G4-T09` Reintento (corregir errores)
  - #90 `G4-T10` Sin descartar rengl├│n (MVP)
- #80 `G4-E04` Locks (concurrencia)
  - #91 `G4-T11` Campos/tabla de lock
  - #92 `G4-T12` Claim preventivo + read-only
  - #93 `G4-T13` Heartbeat + TTL + timeout + idle guard
  - #94 `G4-T14` Solicitar liberaci├│n (modal)
  - #95 `G4-T15` Admin forzar liberaci├│n

---

## Gate 5 ÔÇö Trazabilidad y evidencia

**Milestone (GitHub):** ÔÇ£Trazabilidad y evidencia: Auditor├¡a, adjuntos, papeleraÔÇØ

**DoD (m├¡nimo):**

- Auditor├¡a + notas manuales; adjuntos (Admin/Editor); papelera (soft-delete/restaurar/vaciar).

**├ëpicas:**

- #96 `G5-E01` Auditor├¡a best-effort + notas
  - #99 `G5-T01` Tabla audit_logs
  - #100 `G5-T02` Disparo async + fallback silent
  - #101 `G5-T03` Notas manuales + UI
- #97 `G5-E02` Adjuntos
  - #102 `G5-T04` Tabla attachments
  - #103 `G5-T05` Storage UUID + sanitizar
  - #104 `G5-T06` Permisos Admin/Editor
  - #105 `G5-T07` L├¡mites 100MB + validaciones
- #98 `G5-E03` Papelera
  - #106 `G5-T08` Soft deletes consistentes
  - #107 `G5-T09` UI Papelera (listar, restaurar, vaciar)
  - #108 `G5-T10` Restauraci├│n conserva historial

