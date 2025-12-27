# Backlog de Tareas ÔÇö GATIÔÇæC (Carlos)

> Fuente: `_bmad-output/analysis/brainstorming-session-2025-12-25.md` (Gates 0ÔÇô5).
> Objetivo: convertir decisiones en tareas ejecutables (1 solo dev, intranet onÔÇæpremise).

## Convenciones

- IDs: `G{Gate}-T{NN}` (tarea), `G{Gate}-E{NN}` (├®pica).
- DoD = Definition of Done (criterios de aceptaci├│n m├¡nimos).
- Dependencias: tareas/├®picas que deben estar listas antes.

---

## Gate 0 ÔÇö Repo listo (fundaci├│n)

**DoD del Gate**
- App corre en **Laravel Sail** con **MySQL 8**.
- Auth + roles fijos (Admin/Editor/Lector) funcionando, con bloqueos (Editor no entra a usuarios).
- CI en verde: Pint + PHPUnit + Larastan.
- Seeders crean: roles, usuario Admin, cat├ílogos m├¡nimos demo.

### G0ÔÇæE01: Esqueleto y entorno
- [ ] **G0ÔÇæT01** Decidir layout del repo (app en ra├¡z vs subcarpeta) y documentarlo.
- [ ] **G0ÔÇæT02** Inicializar proyecto **Laravel 11** (estructura base, `.env.example`).
- [ ] **G0ÔÇæT03** Instalar y configurar **Laravel Sail** (PHP, MySQL 8).
- [ ] **G0ÔÇæT04** Documentar setup local en `README.md` (Sail up/down, migrate/seed, tests).

### G0ÔÇæE02: UI stack base (Bootstrap)
- [ ] **G0ÔÇæT05** Instalar **Laravel Breeze (Blade)**.
- [ ] **G0ÔÇæT06** ReÔÇæmaquetar Breeze a **Bootstrap 5** (eliminar Tailwind) respetando `03-visual-style-guide.md`.
- [ ] **G0ÔÇæT07** Configurar **Vite** para Bootstrap (JS/CSS) + Bootstrap Icons.
- [ ] **G0ÔÇæT08** Instalar **Livewire 3** (verificar build y carga en layout).

### G0ÔÇæE03: Seguridad (roles fijos) + rutas protegidas
- [ ] **G0ÔÇæT09** Implementar roles fijos (Admin/Editor/Lector) y asignaci├│n a usuarios (seeders).
- [ ] **G0ÔÇæT10** Definir policies/gates base (ver, crear, editar, adjuntos, adminÔÇæonly).
- [ ] **G0ÔÇæT11** Hardening de acceso: si Editor entra a `/admin/usuarios` por URL directa ÔåÆ **redirect dashboard + 403**.

### G0ÔÇæE04: Calidad y CI
- [ ] **G0ÔÇæT12** Configurar **Laravel Pint** (reglas) y comando CI.
- [ ] **G0ÔÇæT13** Configurar **Larastan** (nivel inicial) y baseline si aplica.
- [ ] **G0ÔÇæT14** Crear GitHub Action: `pint --test`, `phpunit`, `phpstan`.
- [ ] **G0ÔÇæT15** Agregar 2ÔÇô3 tests ÔÇ£smokeÔÇØ (auth + role access).

---

## Gate 1 ÔÇö UX base + navegaci├│n

**DoD del Gate**
- Layout desktopÔÇæfirst: **sidebar colapsable + topbar**.
- Skeleton loaders + bot├│n **Cancelar** en b├║squedas.
- Toasts con **Deshacer (~10s)** para acciones reversibles.
- Manejo de error prod: mensaje amigable + **ID**; detalle solo Admin.
- Polling UX: ÔÇ£Actualizado hace XsÔÇØ.

### G1ÔÇæE01: Layout + navegaci├│n
- [ ] **G1ÔÇæT01** Implementar layout base (sidebar/topbar) con slots para m├│dulos.
- [ ] **G1ÔÇæT02** Definir men├║ por rol (Admin/Editor/Lector) en sidebar.
- [ ] **G1ÔÇæT03** Implementar topbar con ÔÇ£buscador globalÔÇØ (ver Gate 2) y user menu.

### G1ÔÇæE02: Componentes UX reutilizables
- [ ] **G1ÔÇæT04** Componente Toast (success/error) + ÔÇ£DeshacerÔÇØ (hooks Livewire).
- [ ] **G1ÔÇæT05** Skeleton loader est├índar (tablas/forms) alineado a gu├¡a visual.
- [ ] **G1ÔÇæT06** Patr├│n ÔÇ£CancelarÔÇØ en b├║squedas lentas (mantener resultados previos).
- [ ] **G1ÔÇæT07** Indicador ÔÇ£Actualizado hace XsÔÇØ para vistas con polling.

### G1ÔÇæE03: Errores
- [ ] **G1ÔÇæT08** Middleware/handler para generar **ID de error** y log estructurado.
- [ ] **G1ÔÇæT09** P├ígina/Modal de error: amigable + ID; bot├│n ÔÇ£Copiar detalleÔÇØ solo Admin.

### G1ÔÇæE04: Polling base
- [ ] **G1ÔÇæT10** Implementar patr├│n `wire:poll.visible` reutilizable (configurable).

---

## Gate 2 ÔÇö Inventario navegable (Productos + Detalles)

**DoD del Gate**
- Listado de **Productos** con QTY (Total/Disp/No disp) + tooltip con desglose.
- Productos sin stock visibles y resaltados en rojo cuando `Disponibles = 0`.
- B├║squeda unificada: Productos + Activos por `serial`/`asset_tag` (autocompletado por grupos).
- Detalle Producto (tabs) y Detalle Activo (tabs) navegables.

### G2ÔÇæE01: Modelo de datos ÔÇ£columna vertebralÔÇØ
- [ ] **G2ÔÇæT01** Migraciones: `categories` (`is_serialized`, `requires_asset_tag`), `brands`, `locations`, `products`.
- [ ] **G2ÔÇæT02** Migraciones: `assets` (serializados) con `product_id`, `serial`, `asset_tag` (nullable, unique global), `status`, `location_id`.
- [ ] **G2ÔÇæT03** Constraints: unique `(product_id, serial)`; `asset_tag` unique cuando exista.
- [ ] **G2ÔÇæT04** Seeders: categor├¡as demo, marcas demo, ubicaci├│n ÔÇ£Almac├®nÔÇØ, productos demo.

### G2ÔÇæE02: Listado Inventario (Productos)
- [ ] **G2ÔÇæT05** Vista Inventario Productos (tabla) alineada a `03-visual-style-guide.md`.
- [ ] **G2ÔÇæT06** QTY badges (Total/Disponibles/No disponibles) + tooltip de desglose.
- [ ] **G2ÔÇæT07** Sem├íntica QTY: No disponibles = Asignado + Prestado + Pendiente de Retiro; Disponibles = Total ÔêÆ No disponibles.
- [ ] **G2ÔÇæT08** Sin stock: resaltar rojo cuando `Disponibles = 0`.
- [ ] **G2ÔÇæT09** Filtros: categor├¡a, marca, tipo (serializado/cantidad), ÔÇ£solo con disponiblesÔÇØ.
- [ ] **G2ÔÇæT10** Ubicaci├│n en listado: no serializados = ÔÇ£Almac├®nÔÇØ; serializados = ÔÇ£VariasÔÇØ + tooltip.
- [ ] **G2ÔÇæT11** Polling 15s (badges) usando `wire:poll.visible`.

### G2ÔÇæE03: B├║squeda unificada
- [ ] **G2ÔÇæT12** Autocomplete agrupado: ÔÇ£ProductosÔÇØ vs ÔÇ£ActivosÔÇØ.
- [ ] **G2ÔÇæT13** Regla: match exacto por `serial`/`asset_tag` ÔåÆ navegar directo a Detalle Activo.
- [ ] **G2ÔÇæT14** Confirmar que NO indexa ÔÇ£Tareas PendientesÔÇØ (evitar ruido).

### G2ÔÇæE04: Detalle Producto / Activo
- [ ] **G2ÔÇæT15** Detalle Producto tabs: Resumen / Activos o Movimientos / Tareas Pendientes / Historial.
- [ ] **G2ÔÇæT16** Detalle Activo tabs: Info / Asignaci├│nÔÇæPr├®stamos / Adjuntos / Historial.
- [ ] **G2ÔÇæT17** Acciones visibles por estado (botonera), sin l├│gica a├║n (se completa Gate 3).

---

## Gate 3 ÔÇö Operaci├│n diaria (Empleados + acciones + cantidad)

**DoD del Gate**
- Directorio de Empleados (RPE) completo: CRUD (Admin+Editor), ficha con activos.
- Serializados: estados + transiciones + acciones (asignar/prestar/devolver/pendiente/retiro) con regla ÔÇ£Asignado no se prestaÔÇØ.
- Pr├®stamos: vencimiento opcional ÔåÆ ÔÇ£pendienteÔÇØ; a 3 d├¡as ÔåÆ ÔÇ£urgente/cr├¡ticoÔÇØ.
- No serializados: asignaciones/pr├®stamos por cantidad + kardex + ajuste manual Admin (motivo).

### G3ÔÇæE01: Empleados (RPE)
- [ ] **G3ÔÇæT01** Migraci├│n `employees` (RPE unique, nombre, depto, puesto, extensi├│n, correo).
- [ ] **G3ÔÇæT02** UI: listado + b├║squeda + ficha empleado (incluye activos asignados/prestados).
- [ ] **G3ÔÇæT03** Autocomplete por nombre/RPE + ÔÇ£Agregar empleadoÔÇØ inline en flujos.

### G3ÔÇæE02: Estados y acciones (serializados)
- [ ] **G3ÔÇæT04** Definir enum/constantes de estado: Disponible, Asignado, Prestado, Pendiente de Retiro, Retirado.
- [ ] **G3ÔÇæT05** Implementar transiciones permitidas + validaciones serverÔÇæside.
- [ ] **G3ÔÇæT06** Regla: activo Asignado no se presta (obligar desasignar).
- [ ] **G3ÔÇæT07** UI + comandos: asignar/desasignar; prestar/devolver; marcar pendiente; procesar retiro final.

### G3ÔÇæE03: Pr├®stamos (vencimiento)
- [ ] **G3ÔÇæT08** Modelo `loans` (empleado, activo, fechas, estado).
- [ ] **G3ÔÇæT09** Vencimiento opcional: badge ÔÇ£Vencimiento pendienteÔÇØ.
- [ ] **G3ÔÇæT10** Escalamiento: sin vencimiento por 3 d├¡as ÔåÆ ÔÇ£Urgente/Cr├¡ticoÔÇØ.
- [ ] **G3ÔÇæT11** Acci├│n ÔÇ£Definir vencimientoÔÇØ desde lista/detalle.

### G3ÔÇæE04: No serializados (cantidad)
- [ ] **G3ÔÇæT12** Definir d├│nde vive el stock total por Producto (campo `stock_total` o tabla de stock).
- [ ] **G3ÔÇæT13** Asignaciones/pr├®stamos por cantidad (qui├®n tiene qu├® cantidad) + devoluciones.
- [ ] **G3ÔÇæT14** Kardex (entradas, retiros definitivos, ajustes manuales).
- [ ] **G3ÔÇæT15** Ajuste manual Admin: motivo obligatorio + auditor├¡a.

### G3ÔÇæE05: Dashboard (m├®tricas)
- [ ] **G3ÔÇæT16** Implementar dashboard m├¡nimo: ÔÇ£Pr├®stamos vencidosÔÇØ, ÔÇ£Pr├®stamos sin vencimientoÔÇØ, ÔÇ£Pendientes de retiroÔÇØ.
- [ ] **G3ÔÇæT17** Polling 60s (m├®tricas) con `wire:poll.visible`.

---

## Gate 4 ÔÇö Tareas Pendientes (Carga/Procesamiento/Locks)

**DoD del Gate**
- Carga R├ípida tipo ÔÇ£carritoÔÇØ (varios productos por tarea).
- Procesamiento por rengl├│n (retomable) + aplicaci├│n diferida.
- Finalizaci├│n con aplicaci├│n parcial (lo v├ílido aplica; errores quedan).
- Locks a nivel tarea: claim al ÔÇ£ProcesarÔÇØ, heartbeat 10s, TTL 3m, timeout 15m rolling; readÔÇæonly si locked; Admin force unlock.

### G4ÔÇæE01: Modelo de tareas
- [ ] **G4ÔÇæT01** Migraciones: `pending_tasks` + `pending_task_lines` (carrito).
- [ ] **G4ÔÇæT02** Estados por rengl├│n: Pendiente / Preparado / Error / Aplicado.
- [ ] **G4ÔÇæT03** Validaci├│n: series alfanum├®ricas min 4; permitir duplicados en tarea, bloquear al aplicar inventario.

### G4ÔÇæE02: Carga R├ípida (carrito)
- [ ] **G4ÔÇæT04** UI: agregar productos existentes o placeholder (solo nombre).
- [ ] **G4ÔÇæT05** Placeholder: tipo obligatorio (Serializado/Cantidad).
- [ ] **G4ÔÇæT06** Serializado: pegar series 1 por l├¡nea + contador; Cantidad: entero > 0.

### G4ÔÇæE03: Procesamiento (rengl├│n + aplicaci├│n diferida)
- [ ] **G4ÔÇæT07** Pantalla de procesamiento: editar renglones; marcar ÔÇ£preparadoÔÇØ.
- [ ] **G4ÔÇæT08** ÔÇ£FinalizarÔÇØ: aplicar inventario; si error ÔåÆ rengl├│n Error con mensaje detallado; aplicar lo v├ílido.
- [ ] **G4ÔÇæT09** Reintento: corregir renglones Error y volver a finalizar.
- [ ] **G4ÔÇæT10** Sin ÔÇ£descartar rengl├│nÔÇØ (MVP).

### G4ÔÇæE04: Locks (concurrencia)
- [ ] **G4ÔÇæT11** Campos/tabla de lock: `locked_by`, `lock_expires_at`, `heartbeat_at`.
- [ ] **G4ÔÇæT12** Claim preventivo al clic en ÔÇ£ProcesarÔÇØ; readÔÇæonly para otros.
- [ ] **G4ÔÇæT13** Heartbeat: 10s; TTL 3m; timeout 15m rolling; idle guard (no renovar si inactivo).
- [ ] **G4ÔÇæT14** ÔÇ£Solicitar liberaci├│nÔÇØ: modal informativo (sin notificaciones autom├íticas).
- [ ] **G4ÔÇæT15** Admin ÔÇ£Forzar liberaci├│nÔÇØ (auditado).

---

## Gate 5 ÔÇö Trazabilidad y evidencia (auditor├¡a, adjuntos, papelera)

**DoD del Gate**
- Auditor├¡a bestÔÇæeffort + notas manuales.
- Adjuntos con sanitizaci├│n/UUID; permisos Admin+Editor.
- Papelera: softÔÇædelete/restaurar; purga manual Admin; historial intacto.

### G5ÔÇæE01: Auditor├¡a bestÔÇæeffort + notas
- [ ] **G5ÔÇæT01** Tabla `audit_logs` (actor, entidad, acci├│n, cambios JSON, timestamp).
- [ ] **G5ÔÇæT02** Disparo async (queue DB) y fallback silent si falla (log warning).
- [ ] **G5ÔÇæT03** Notas manuales: tabla `notes` (entidad, autor, texto) + UI en tabs Historial.

### G5ÔÇæE02: Adjuntos
- [ ] **G5ÔÇæT04** Tabla `attachments` (entidad, uploader, nombre original, ruta UUID, mime, size).
- [ ] **G5ÔÇæT05** Storage: guardar con UUID; sanitizar; mostrar nombre original.
- [ ] **G5ÔÇæT06** Permisos: solo Admin/Editor; Lector sin acceso.
- [ ] **G5ÔÇæT07** L├¡mites: max 100MB por archivo (PRD) + validaciones.

### G5ÔÇæE03: Papelera
- [ ] **G5ÔÇæT08** Soft deletes consistentes (productos, activos, empleados, etc. seg├║n aplique).
- [ ] **G5ÔÇæT09** UI Papelera: listar, restaurar, vaciar (Admin).
- [ ] **G5ÔÇæT10** Restauraci├│n conserva historial completo (borrado es un evento m├ís).

