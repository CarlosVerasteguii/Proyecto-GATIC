# Backlog de Tareas — GATI-C (Carlos)

> Fuente: `_bmad-output/analysis/brainstorming-session-2025-12-25.md` (Gates 0–5).
> Objetivo: convertir decisiones en tareas ejecutables (1 solo dev, intranet on-premise).

## Convenciones

- IDs: `G{Gate}-T{NN}` (tarea), `G{Gate}-E{NN}` (épica).
- DoD = Definition of Done (criterios de aceptación mínimos).
- Dependencias: tareas/épicas que deben estar listas antes.

---

## Gate 0 — Repo listo (fundación)

**DoD del Gate**
- App corre en **Laravel Sail** con **MySQL 8**.
- Auth + roles fijos (Admin/Editor/Lector) funcionando, con bloqueos (Editor no entra a usuarios).
- CI en verde: Pint + PHPUnit + Larastan.
- Seeders crean: roles, usuario Admin, catálogos mínimos demo.

### G0-E01: Esqueleto y entorno
- [ ] **G0-T01** Decidir layout del repo (app en raíz vs subcarpeta) y documentarlo.
- [ ] **G0-T02** Inicializar proyecto **Laravel 11** (estructura base, `.env.example`).
- [ ] **G0-T03** Instalar y configurar **Laravel Sail** (PHP, MySQL 8).
- [ ] **G0-T04** Documentar setup local en `README.md` (Sail up/down, migrate/seed, tests).

### G0-E02: UI stack base (Bootstrap)
- [ ] **G0-T05** Instalar **Laravel Breeze (Blade)**.
- [ ] **G0-T06** Re-maquetar Breeze a **Bootstrap 5** (eliminar Tailwind) respetando `03-visual-style-guide.md`.
- [ ] **G0-T07** Configurar **Vite** para Bootstrap (JS/CSS) + Bootstrap Icons.
- [ ] **G0-T08** Instalar **Livewire 3** (verificar build y carga en layout).

### G0-E03: Seguridad (roles fijos) + rutas protegidas
- [ ] **G0-T09** Implementar roles fijos (Admin/Editor/Lector) y asignación a usuarios (seeders).
- [ ] **G0-T10** Definir policies/gates base (ver, crear, editar, adjuntos, admin-only).
- [ ] **G0-T11** Hardening de acceso: si Editor entra a `/admin/usuarios` por URL directa → **redirect dashboard + 403**.

### G0-E04: Calidad y CI
- [ ] **G0-T12** Configurar **Laravel Pint** (reglas) y comando CI.
- [ ] **G0-T13** Configurar **Larastan** (nivel inicial) y baseline si aplica.
- [ ] **G0-T14** Crear GitHub Action: `pint --test`, `phpunit`, `phpstan`.
- [ ] **G0-T15** Agregar 2–3 tests “smoke” (auth + role access).

---

## Gate 1 — UX base + navegación

**DoD del Gate**
- Layout desktop-first: **sidebar colapsable + topbar**.
- Skeleton loaders + botón **Cancelar** en búsquedas.
- Toasts con **Deshacer (~10s)** para acciones reversibles.
- Manejo de error prod: mensaje amigable + **ID**; detalle solo Admin.
- Polling UX: “Actualizado hace Xs”.

### G1-E01: Layout + navegación
- [ ] **G1-T01** Implementar layout base (sidebar/topbar) con slots para módulos.
- [ ] **G1-T02** Definir menú por rol (Admin/Editor/Lector) en sidebar.
- [ ] **G1-T03** Implementar topbar con “buscador global” (ver Gate 2) y user menu.

### G1-E02: Componentes UX reutilizables
- [ ] **G1-T04** Componente Toast (success/error) + “Deshacer” (hooks Livewire).
- [ ] **G1-T05** Skeleton loader estándar (tablas/forms) alineado a guía visual.
- [ ] **G1-T06** Patrón “Cancelar” en búsquedas lentas (mantener resultados previos).
- [ ] **G1-T07** Indicador “Actualizado hace Xs” para vistas con polling.

### G1-E03: Errores
- [ ] **G1-T08** Middleware/handler para generar **ID de error** y log estructurado.
- [ ] **G1-T09** Página/Modal de error: amigable + ID; botón “Copiar detalle” solo Admin.

### G1-E04: Polling base
- [ ] **G1-T10** Implementar patrón `wire:poll.visible` reutilizable (configurable).

---

## Gate 2 — Inventario navegable (Productos + Detalles)

**DoD del Gate**
- Listado de **Productos** con QTY (Total/Disp/No disp) + tooltip con desglose.
- Productos sin stock visibles y resaltados en rojo cuando `Disponibles = 0`.
- Búsqueda unificada: Productos + Activos por `serial`/`asset_tag` (autocompletado por grupos).
- Detalle Producto (tabs) y Detalle Activo (tabs) navegables.

### G2-E01: Modelo de datos “columna vertebral”
- [ ] **G2-T01** Migraciones: `categories` (`is_serialized`, `requires_asset_tag`), `brands`, `locations`, `products`.
- [ ] **G2-T02** Migraciones: `assets` (serializados) con `product_id`, `serial`, `asset_tag` (nullable, unique global), `status`, `location_id`.
- [ ] **G2-T03** Constraints: unique `(product_id, serial)`; `asset_tag` unique cuando exista.
- [ ] **G2-T04** Seeders: categorías demo, marcas demo, ubicación “Almacén”, productos demo.

### G2-E02: Listado Inventario (Productos)
- [ ] **G2-T05** Vista Inventario Productos (tabla) alineada a `03-visual-style-guide.md`.
- [ ] **G2-T06** QTY badges (Total/Disponibles/No disponibles) + tooltip de desglose.
- [ ] **G2-T07** Semántica QTY: No disponibles = Asignado + Prestado + Pendiente de Retiro; Disponibles = Total − No disponibles.
- [ ] **G2-T08** Sin stock: resaltar rojo cuando `Disponibles = 0`.
- [ ] **G2-T09** Filtros: categoría, marca, tipo (serializado/cantidad), “solo con disponibles”.
- [ ] **G2-T10** Ubicación en listado: no serializados = “Almacén”; serializados = “Varias” + tooltip.
- [ ] **G2-T11** Polling 15s (badges) usando `wire:poll.visible`.

### G2-E03: Búsqueda unificada
- [ ] **G2-T12** Autocomplete agrupado: “Productos” vs “Activos”.
- [ ] **G2-T13** Regla: match exacto por `serial`/`asset_tag` → navegar directo a Detalle Activo.
- [ ] **G2-T14** Confirmar que NO indexa “Tareas Pendientes” (evitar ruido).

### G2-E04: Detalle Producto / Activo
- [ ] **G2-T15** Detalle Producto tabs: Resumen / Activos o Movimientos / Tareas Pendientes / Historial.
- [ ] **G2-T16** Detalle Activo tabs: Info / Asignación-Préstamos / Adjuntos / Historial.
- [ ] **G2-T17** Acciones visibles por estado (botonera), sin lógica aún (se completa Gate 3).

---

## Gate 3 — Operación diaria (Empleados + acciones + cantidad)

**DoD del Gate**
- Directorio de Empleados (RPE) completo: CRUD (Admin+Editor), ficha con activos.
- Serializados: estados + transiciones + acciones (asignar/prestar/devolver/pendiente/retiro) con regla “Asignado no se presta”.
- Préstamos: vencimiento opcional → “pendiente”; a 3 días → “urgente/crítico”.
- No serializados: asignaciones/préstamos por cantidad + kardex + ajuste manual Admin (motivo).

### G3-E01: Empleados (RPE)
- [ ] **G3-T01** Migración `employees` (RPE unique, nombre, depto, puesto, extensión, correo).
- [ ] **G3-T02** UI: listado + búsqueda + ficha empleado (incluye activos asignados/prestados).
- [ ] **G3-T03** Autocomplete por nombre/RPE + “Agregar empleado” inline en flujos.

### G3-E02: Estados y acciones (serializados)
- [ ] **G3-T04** Definir enum/constantes de estado: Disponible, Asignado, Prestado, Pendiente de Retiro, Retirado.
- [ ] **G3-T05** Implementar transiciones permitidas + validaciones server-side.
- [ ] **G3-T06** Regla: activo Asignado no se presta (obligar desasignar).
- [ ] **G3-T07** UI + comandos: asignar/desasignar; prestar/devolver; marcar pendiente; procesar retiro final.

### G3-E03: Préstamos (vencimiento)
- [ ] **G3-T08** Modelo `loans` (empleado, activo, fechas, estado).
- [ ] **G3-T09** Vencimiento opcional: badge “Vencimiento pendiente”.
- [ ] **G3-T10** Escalamiento: sin vencimiento por 3 días → “Urgente/Crítico”.
- [ ] **G3-T11** Acción “Definir vencimiento” desde lista/detalle.

### G3-E04: No serializados (cantidad)
- [ ] **G3-T12** Definir dónde vive el stock total por Producto (campo `stock_total` o tabla de stock).
- [ ] **G3-T13** Asignaciones/préstamos por cantidad (quién tiene qué cantidad) + devoluciones.
- [ ] **G3-T14** Kardex (entradas, retiros definitivos, ajustes manuales).
- [ ] **G3-T15** Ajuste manual Admin: motivo obligatorio + auditoría.

### G3-E05: Dashboard (métricas)
- [ ] **G3-T16** Implementar dashboard mínimo: “Préstamos vencidos”, “Préstamos sin vencimiento”, “Pendientes de retiro”.
- [ ] **G3-T17** Polling 60s (métricas) con `wire:poll.visible`.

---

## Gate 4 — Tareas Pendientes (Carga/Procesamiento/Locks)

**DoD del Gate**
- Carga Rápida tipo “carrito” (varios productos por tarea).
- Procesamiento por renglón (retomable) + aplicación diferida.
- Finalización con aplicación parcial (lo válido aplica; errores quedan).
- Locks a nivel tarea: claim al “Procesar”, heartbeat 10s, TTL 3m, timeout 15m rolling; read-only si locked; Admin force unlock.

### G4-E01: Modelo de tareas
- [ ] **G4-T01** Migraciones: `pending_tasks` + `pending_task_lines` (carrito).
- [ ] **G4-T02** Estados por renglón: Pendiente / Preparado / Error / Aplicado.
- [ ] **G4-T03** Validación: series alfanuméricas min 4; permitir duplicados en tarea, bloquear al aplicar inventario.

### G4-E02: Carga Rápida (carrito)
- [ ] **G4-T04** UI: agregar productos existentes o placeholder (solo nombre).
- [ ] **G4-T05** Placeholder: tipo obligatorio (Serializado/Cantidad).
- [ ] **G4-T06** Serializado: pegar series 1 por línea + contador; Cantidad: entero > 0.

### G4-E03: Procesamiento (renglón + aplicación diferida)
- [ ] **G4-T07** Pantalla de procesamiento: editar renglones; marcar “preparado”.
- [ ] **G4-T08** “Finalizar”: aplicar inventario; si error → renglón Error con mensaje detallado; aplicar lo válido.
- [ ] **G4-T09** Reintento: corregir renglones Error y volver a finalizar.
- [ ] **G4-T10** Sin “descartar renglón” (MVP).

### G4-E04: Locks (concurrencia)
- [ ] **G4-T11** Campos/tabla de lock: `locked_by`, `lock_expires_at`, `heartbeat_at`.
- [ ] **G4-T12** Claim preventivo al clic en “Procesar”; read-only para otros.
- [ ] **G4-T13** Heartbeat: 10s; TTL 3m; timeout 15m rolling; idle guard (no renovar si inactivo).
- [ ] **G4-T14** “Solicitar liberación”: modal informativo (sin notificaciones automáticas).
- [ ] **G4-T15** Admin “Forzar liberación” (auditado).

---

## Gate 5 — Trazabilidad y evidencia (auditoría, adjuntos, papelera)

**DoD del Gate**
- Auditoría best-effort + notas manuales.
- Adjuntos con sanitización/UUID; permisos Admin+Editor.
- Papelera: soft-delete/restaurar; purga manual Admin; historial intacto.

### G5-E01: Auditoría best-effort + notas
- [ ] **G5-T01** Tabla `audit_logs` (actor, entidad, acción, cambios JSON, timestamp).
- [ ] **G5-T02** Disparo async (queue DB) y fallback silent si falla (log warning).
- [ ] **G5-T03** Notas manuales: tabla `notes` (entidad, autor, texto) + UI en tabs Historial.

### G5-E02: Adjuntos
- [ ] **G5-T04** Tabla `attachments` (entidad, uploader, nombre original, ruta UUID, mime, size).
- [ ] **G5-T05** Storage: guardar con UUID; sanitizar; mostrar nombre original.
- [ ] **G5-T06** Permisos: solo Admin/Editor; Lector sin acceso.
- [ ] **G5-T07** Límites: max 100MB por archivo (PRD) + validaciones.

### G5-E03: Papelera
- [ ] **G5-T08** Soft deletes consistentes (productos, activos, empleados, etc. según aplique).
- [ ] **G5-T09** UI Papelera: listar, restaurar, vaciar (Admin).
- [ ] **G5-T10** Restauración conserva historial completo (borrado es un evento más).
