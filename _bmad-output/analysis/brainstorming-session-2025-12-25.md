---
stepsCompleted: [1, 2, 3, 4]
inputDocuments:
  - '01-business-context.md'
  - '02-prd.md'
  - '03-visual-style-guide.md'
  - '_bmad/bmm/data/project-context-template.md'
session_topic: 'Planificación integral de GATIC (producto + repositorio)'
session_goals: 'Alinear el producto y este repositorio para ejecución: clarificar alcance/MVP y necesidades, convertirlo en plan/backlog técnico, y respetar la guía visual corporativa mientras se validan decisiones tecnológicas.'
selected_approach: 'progressive-flow'
techniques_used:
  - 'Question Storming'
  - 'Mind Mapping'
  - 'Solution Matrix'
  - 'Decision Tree Mapping'
ideas_generated:
  - 'P-01 UX base'
  - 'P-02 Inventario Productos'
  - 'P-03 Detalles Producto/Activo'
  - 'P-04 Flujos serializados (estados + acciones)'
  - 'P-05 No serializados (cantidad + kardex)'
  - 'P-06 Empleados (RPE)'
  - 'P-07 Tareas Pendientes (carrito + procesamiento + locks)'
  - 'P-08 Auditoría + notas manuales'
  - 'P-09 Adjuntos (Admin/Editor)'
  - 'P-10 Papelera (soft-delete)'
  - 'R-01 Base stack'
  - 'R-02 Auth + RBAC'
  - 'R-03 Entorno local Sail'
  - 'R-04 Calidad + CI'
  - 'R-05 Migraciones + seeders'
  - 'R-06 Deploy Compose (TBD)'
context_file: '_bmad/bmm/data/project-context-template.md'
technique_execution_complete: true
idea_organization_complete: true
session_active: false
workflow_completed: true
facilitation_notes:
  - 'Decisiones pragmáticas: valor alto en UX/operatividad con complejidad técnica controlada.'
  - 'Polling visible (sin websockets) como mejora de fluidez con costo bajo.'
  - 'Foco fuerte en integridad y concurrencia (locks/TTL/heartbeat + admin override).'
---

# Brainstorming Session Results

**Facilitator:** Carlos
**Date:** 2025-12-25

## Session Overview

**Topic:** Planificación integral de GATIC (producto + repositorio)
**Goals:** Alinear el producto y este repositorio para ejecución: clarificar alcance/MVP y necesidades, convertirlo en plan/backlog técnico, y respetar la guía visual corporativa mientras se validan decisiones tecnológicas.

### Context Guidance

- Priorizar: problemas del usuario, ideas de funcionalidades, enfoque técnico, UX, valor de negocio, diferenciación, riesgos y métricas.
- Restricción dura: `03-visual-style-guide.md` (lineamientos corporativos de diseño).
- Tecnología base (a validar): HTML/CSS/Bootstrap + JS + PHP/Laravel + MySQL.

### Session Setup

- Enfoque elegido: Progressive Technique Flow (de exploración amplia → plan accionable).

## Technique Selection

**Approach:** Progressive Technique Flow
**Journey Design:** De exploración amplia (divergente) → organización → desarrollo → plan de acción

**Progressive Techniques:**

- **Phase 1 - Exploration:** Question Storming (definir el espacio con preguntas antes de decidir)
- **Phase 2 - Pattern Recognition:** Mind Mapping (agrupar y ver temas/relaciones)
- **Phase 3 - Development:** Solution Matrix (convertir temas en opciones y priorizar)
- **Phase 4 - Action Planning:** Decision Tree Mapping (mapear decisiones y siguiente ejecución)

**Journey Rationale:** Como aún no están cerradas las “salidas”, empezamos por preguntas (producto + repo), luego estructuramos hallazgos, maduramos opciones (incluida tecnología), y aterrizamos en decisiones y próximos pasos, respetando la guía visual corporativa como restricción no negociable.

## Technique Execution (Completed)

### Phase 1 — Question Storming (Round 1)

**Preguntas capturadas (tal como salieron):**

- ¿Qué sucede exactamente si un Editor intenta acceder a la gestión de usuarios (URL directa)? (hipótesis: se le redireccionará)
- ¿Qué pasa si dos Editores intentan procesar la misma "Tarea Pendiente" al mismo tiempo?
- En la Carga Rápida, ¿qué validación mínima debe tener el campo de "Número de Serie"?
- ¿Se deben poder fusionar dos tareas pendientes de carga si son del mismo lote?
- Si se hace un Retiro Rápido por error, ¿cómo se revierte esa acción de inmediato?
- ¿El buscador global debe encontrar contenido dentro de las "Tareas Pendientes"?
- ¿Qué pasa con el historial de un activo si se borra y luego se restaura de la Papelera?
- ¿Cómo manejamos la unicidad de los números de serie (permitimos duplicados temporalmente)?
- Si cambio la categoría de un producto, ¿se recalculan estadísticas históricas?
- En el soft-delete, ¿cuánto tiempo guardamos la data antes de una purga definitiva (o es eterna)?
- ¿Cómo se debe comportar la UI si la API tarda más de 3 segundos en responder una búsqueda?
- ¿El dashboard de métricas se actualiza en tiempo real o solo al refrescar?

**Casos borde y errores:**

- ¿Qué pasa si el servicio de auditoría falla silenciosamente durante un préstamo crítico?
- ¿Cómo manejamos caracteres especiales en los nombres de archivos adjuntos (ej. ñ, tildes)?
- Si un activo está "Prestado", ¿el sistema debe bloquear intentar asignarlo a otra persona?
- ¿Qué sucede si intentas eliminar una Ubicación que tiene 500 activos asignados?
- ¿Cómo se reporta un error de sistema al usuario: código técnico o mensaje amigable genérico?

**Patrones que ya se ven (pistas):**

- Permisos/seguridad (URL directa por rol) + auditoría.
- Concurrencia/integridad (tareas pendientes, unicidad de series, estados de activos).
- Retención/soft-delete + trazabilidad/historial.
- UX/performance (latencia >3s) + observabilidad/métricas.

**Respuestas / hipótesis (Round 1):**

- Acceso editor a gestión de usuarios por URL directa: redirección al dashboard o 403.
- Concurrencia en "Tarea Pendiente": claim/lock al dar clic en "Procesar" (estado `in_progress` + `processing_by`) con heartbeat/TTL; Admin puede liberar/forzar reclamo.
- Validación mínima "Número de Serie" (Carga Rápida): alfanumérico, mínimo 4 caracteres.
- Fusionar tareas pendientes del mismo lote: no por ahora (MVP).
- Revertir Retiro Rápido por error: botón "Deshacer" en toast o revertir desde detalle (cambio de estado).
- Buscador global sobre "Tareas Pendientes": no (módulos separados para evitar ruido).
- Historial si se borra y restaura (Papelera): se mantiene intacto; borrar/restaurar es un evento más.
- Unicidad de series: permitir duplicados en pendientes; bloquear al "Procesar" (inventario real).
- Cambio de categoría: estadísticas históricas no se recalculan; snapshots sí.
- Soft-delete: retención indefinida hasta que un admin vacíe papelera.
- UX si API tarda >3s: skeleton loaders + mensaje de progreso.
- Dashboard/indicadores: actualización por polling (Livewire `wire:poll`) sin WebSockets (barato y moderno), con refresco manual como fallback.
  - Badges/estados (menú, listas): cada **15s**.
  - Métricas del dashboard: cada **60s**.
  - Se usará `wire:poll.visible` para reducir carga cuando haya muchas pestañas abiertas.
- Auditoría falla silenciosa: la operación del usuario procede; se registra en log interno.
- Adjuntos con caracteres especiales: sanitizar al guardar (UUID en disco) y mostrar nombre original en UI.
- Activo "Prestado": bloquear reasignación hasta volver a "Disponible".
- Eliminar ubicación con activos asignados: bloqueado; requiere reasignación previa.
- Mensajes de error al usuario: en dev detalle técnico completo; en prod mensaje amigable + detalle completo solo para Admin.

**Baseline técnico (Round 2 — repo + arquitectura Laravel):**

- Repo contiene el código Laravel (este repo es la fuente de verdad del código).
- UI: Blade + Livewire (con Bootstrap 5 como base visual; respetar `03-visual-style-guide.md`).
- Stack objetivo: Laravel 11 + PHP 8.2+ + MySQL.
- Auth scaffolding: Breeze (Blade) + remaquetado a Bootstrap 5 (mantener stack oficial).
  - Nota: Breeze trae Tailwind por defecto; se adapta/remueve para seguir `03-visual-style-guide.md`.
- Autorización: Policies/Gates + roles/permissions (p. ej. Spatie).
- Auditoría: log en BD y/o eventos; "best effort", no bloqueante; si falla, se registra internamente (alineado con `01-business-context.md` y `02-prd.md`).
- Entorno local: Laravel Sail (Docker) para paridad de versiones (PHP/MySQL) y evitar "works on my machine".
- Despliegue: Docker Compose.
- Colas: driver `database` (suficiente para auditoría y tareas async).
- Frontend build: Vite/NPM (recomendado con Breeze).
- Estilos corporativos: combinación de variables CSS + tema Bootstrap vía Sass (para alinear a `03-visual-style-guide.md`).
- Calidad/CI: `pint + phpunit + larastan` como mínimo para merge.
- Tests: PHPUnit.
- Producción (Compose): Nginx + PHP-FPM.
- Logs/alertas: logs + email simple a admins ante fallos graves.
- Repo readiness:
  - Git: trunk-based.
  - Merge: CI verde (sin aprobaciones por ahora).
  - Entornos: solo local por ahora (intranet on-premise; sin staging externo aún).
  - Deploy: por definir cuando exista acceso al servidor físico final.
  - Datos iniciales: seeders robustos (roles/permisos + admin + datos demo) para reinicios frecuentes de BD local.
- Errores en UI:
  - En dev: detalle completo en pantalla (debug) para acelerar desarrollo (equipo TI).
  - En prod: mensaje amigable + opción de ver detalle completo solo para Admin ("TI autenticado").

**Decisiones abiertas / riesgos (a validar):**

- Concurrencia en "Tarea Pendiente": claim/lock al procesar (estado `in_progress` + `processing_by`), bloqueando a otros usuarios.
- Locking: definir intervalo de heartbeat/polling y “lease TTL” (corto) para liberar rápido si se cierra la pestaña; mantener timeout razonable para sesiones largas.

**Política de lock/claim (detalle acordado):**

- El lock se adquiere al dar clic en **"Procesar"** (preventivo, antes de llenar el formulario).
- Timeout: **15 minutos** (rolling por actividad/heartbeat).
- Heartbeat: Livewire polling cada **10s** renueva el lock mientras la pestaña está activa.
- Unlock “best effort” al cerrar pestaña/ventana (beforeunload) + fallback al timeout.
- Para liberar “rápido” si se cierra sin unlock: lease TTL **3 min** renovado por heartbeat.
- Idle guard: solo renovar el lock si hubo actividad real del usuario en los últimos **2 min** (si no, dejar que caiga por TTL/timeout).
- Operación: Admin puede **liberar/forzar reclamo** del lock (acción auditada).

### Phase 2 — Mind Mapping (Round 1)

**Nodo central:** GATI-C (Producto + Repo)

**Producto:**

1. Módulos Core (Inventario, Activos, Ubicaciones, Categorías)
2. Flujo de Trabajo (Carga Rápida, Procesamiento, Bloqueos/Locks)
3. Seguridad y Acceso (Roles, Permisos Granulares, Auditoría)
4. Experiencia de Usuario (UI Bootstrap, Livewire Polling, Feedback errores)

**Repo/Plataforma:**

1. Stack Base (Laravel 11, Livewire 3, Bootstrap 5 + Vite)
2. Infraestructura Local (Docker, Sail, MySQL 8)
3. Calidad de Código (PHPUnit, Pint, Larastan, CI Checks)
4. Gestión de Datos (Migraciones, Seeders robustos, Modelos)

#### P1 — Módulos Core (expansión)

**P1.1 Inventario (vista jerárquica) + terminología**

- Inventario lista **Productos (modelo/catálogo)** con indicador QTY; al entrar al detalle se ven **Activos (unidad física)**.
- Serialización/representación: depende de la categoría; `categorías.is_serialized` define si se gestiona por unidades serializadas vs por cantidad.
- Terminología estándar: “Producto” = modelo; “Activo” = unidad física.

**P1.2 No serializados (por cantidad) + asignaciones/préstamos + unicidad de serie**

- Si `categorías.is_serialized = false`: se gestiona **por cantidad** (stock agregado por Producto; sin filas por unidad).
- Para no serializados: permitir **Asignaciones/Préstamos por cantidad** (ej. Juan tiene 1 mouse) para control y accountability.
- Si un ítem requiere trazabilidad individual, se convierte en serializado (etiqueta interna) y pasa a gestionarse por unidad.
- Unicidad de serie para serializados al “Procesar”: **(producto_id + serial)** como llave única (evitar bloqueos injustificados por duplicados entre productos).
- Identificador interno empresa (`asset_tag`/etiqueta corporativa): existe para algunos activos (típicamente los asignados a usuarios), y se liga al RPE/empleado; cuando exista debe ser **único global**.

**P1.3 Ubicaciones (stock por ubicación)**

- No serializados: no manejar stock multi-ubicación; stock vive en un “Almacén” (ubicación por defecto) y se controla globalmente.
- Serializados: `ubicacion_id` se mantiene como ubicación física (edificio/almacén/oficina); la asignación/préstamo se muestra aparte (no se “mueve” la ubicación por asignar).

**P1.4 Catálogos (Admin)**

- Marcas: incluir en MVP (Admin crea/edita).
- Categorías: además de `is_serialized`, agregar `requires_asset_tag` (p. ej. laptops = true).
- Borrado de catálogos: bloqueado si está referenciado; si no, soft-delete (alineado con PRD).

**P1.5 QTY (inventario) + estados**

- UI: mostrar indicador QTY “padre” por Producto con 3 badges: **Total** (secundario), **Disponibles** (verde), **No disponibles** (warning) + tooltip con desglose por estado (alineado con `03-visual-style-guide.md` §10.2 y el PRD “10 4 6”).
- Serializados: **Disponibles** = solo estado `Disponible` (no incluye `Asignado`). `Retirado` se excluye del Inventario/QTY por defecto (solo vía filtro/historial).
- No serializados (por cantidad): mostrar el mismo indicador (ej. “Cable HDMI”: Total 40, Disponibles 31, No disponibles 9).
- Semántica recomendada:
  - **No disponibles** = (Asignado + Prestado + Pendiente de Retiro).
  - **Disponibles** = Total - No disponibles.
- No agregar estado “En reparación” por ahora.

**P1.6 Inventario (listado de Productos) + búsqueda/filtros (propuesta recomendada)**

- UI: tabla Bootstrap (`table-responsive`, `table-hover`, `table-striped`, `align-middle`) alineada con `03-visual-style-guide.md` §10.
- Columnas sugeridas:
  - **Producto**: nombre + (marca/modelo si aplica) + badge de tipo (**Serializado** / **Cantidad**).
  - **Categoría** y **Marca** (para filtrar rápido).
  - **QTY**: los 3 badges (Total/Disponibles/No disponibles) con tooltip de desglose (P1.5).
  - **Ubicación**:
    - No serializados: “Almacén” (default).
    - Serializados: “Varias” + tooltip con conteo y ubicaciones más comunes (sin stock multi-ubicación para no serializados).
  - **Acciones**: Ver detalle (y acciones según rol).
- Buscador: **unificado** (producto/modelo/marca/categoría + serial + `asset_tag`) con autocompletado agrupado (**Productos** vs **Activos**); si hay match exacto de serie/`asset_tag`, navegar directo al **Detalle de Activo**.
- Filtros mínimos:
  - Categoría, Marca, Tipo (Serializado/Cantidad).
  - Toggle “Solo con disponibles” (Disponibles > 0).
- Sin stock: **no se oculta**; si `Disponibles = 0`, resaltar en **rojo** (p. ej. badge de Disponibles en `bg-danger` y/o nombre/fila con `text-danger`).
- Ordenamiento: por nombre (default) + por disponibles (desc) para operativa.
- Rendimiento/UX: paginación + `wire:poll.visible` cada 15s solo para refrescar badges/indicadores cuando la pestaña está visible (evita carga con muchas pestañas).

**P1.7 Detalle de Producto (estructura de pantalla)**

- Tabs (Producto):
  - **Resumen**
  - **Activos** (si es serializado) / **Movimientos** (si es por cantidad)
  - **Tareas Pendientes** (relacionadas)
  - **Historial** (auditoría)
- Adjuntos: **solo** en **Detalle de Activo** (no a nivel Producto).

**P1.8 Detalle de Activo (serializado)**

- Header: **Producto + Serial + `asset_tag` (si existe)** + badge de **Estado** + **Ubicación**.
- Tabs: **Info** | **Asignación/Préstamos** | **Adjuntos** | **Historial**.
- Acciones (según estado): **Asignar**, **Prestar**, **Devolver**, **Marcar “Pendiente de Retiro”**, **Retirar (final)**.

**P1.9 Productos por cantidad (tab “Movimientos”)**

- En “Movimientos” mostrar 2 bloques:
  1) **Asignaciones/Préstamos por cantidad** (quién tiene qué cantidad, desde cuándo, y devolución).
  2) **Kardex de stock** (solo lo que cambia el *Total*): Entradas (cargas), Retiros definitivos y **Ajustes manuales**.
- **Ajuste manual**: solo **Admin**, requiere “motivo” obligatorio y queda en auditoría.

**P1.10 Activos serializados (estados + transiciones MVP)**

- Estados: `Disponible`, `Asignado`, `Prestado`, `Pendiente de Retiro`, `Retirado`.
- Transiciones permitidas:
  - `Disponible` → `Asignado` / `Prestado` / `Pendiente de Retiro`
  - `Asignado` → `Disponible` (desasignar) / `Pendiente de Retiro`
  - `Prestado` → `Disponible` (devolver) / `Pendiente de Retiro`
  - `Pendiente de Retiro` → `Retirado` (procesar retiro) / `Disponible` (deshacer)
- Regla clave: **un Activo “Asignado” NO se puede “Prestar”**; primero se debe **Desasignar** (simplifica la operativa).

**P1.11 Préstamos (vencimiento opcional + “vencimiento pendiente”)**

- La fecha de devolución esperada es **opcional** al crear el préstamo.
- Si no se captura: el préstamo queda con badge **“Vencimiento pendiente”** (seguimiento) y aparece/contabiliza aparte (ej. dashboard “Préstamos sin vencimiento”).
- El indicador “Préstamos vencidos” solo considera préstamos con vencimiento definido.
- Regla de escalamiento: si pasan **3 días** desde la creación y sigue sin vencimiento, se marca como **“Urgente / Crítico”** (rojo) hasta que se defina.
- UX: acción rápida **“Definir vencimiento”** desde lista/detalle + filtro “Sin vencimiento”.

**P1.12 Préstamos (destinatarios)**

- Los préstamos se realizan **solo a empleados** identificados por **RPE** (sin “externos” en MVP).

**P1.13 Asignaciones (larga duración)**

- La **Asignación** es de larga duración: el activo queda ligado al **RPE** hasta que se **desasigne**.
- El **Préstamo** se mantiene como flujo separado (temporal) con vencimiento opcional (P1.11).

#### P2 - Flujo de Trabajo (expansión)

**P2.1 Carga Rápida (crear Tarea Pendiente de ingreso)**

- UX base: ComboBox para seleccionar **Producto existente**; si no existe, permitir **placeholder** (solo nombre).
- Placeholder: el Editor debe elegir **Tipo obligatorio**: **Serializado** / **Cantidad** (no se infiere automáticamente).
- Captura dinámica:
  - **Serializado:** pegar **1 serie por línea** (contador + validación alfanumérica min 4).
  - **Cantidad:** campo **Cantidad** (entero > 0).
- Estructura de tarea: **1 tarea puede incluir varios productos** (modo “carrito” de carga rápida).
- Catálogos (marca/categoría/ubicación): opcionales en flujo rápido; se completan en “Formulario Completo” al procesar (PRD).

**P2.2 Procesamiento de Tarea “carrito” (parcial por renglón)**

- Al dar clic en **Procesar** se abre una vista/formulario que permite completar **por renglón** (por producto o por serie) y dejar la tarea **parcialmente procesada**.
- Cada renglón tiene estado propio (ej. Pendiente / Procesado / Error-validación) para poder retomar después.
- La tarea se considera “Completada” cuando **todos** los renglones están procesados; si no, queda “En progreso” (retomable).

**P2.3 Procesamiento (aplicación diferida)**

- Al procesar renglones, los cambios se guardan como **borrador/preparado** y **no afectan el inventario** hasta que se “Finalice” (aplique) la tarea completa.
- Beneficio: evita estados intermedios extraños y permite revisar/validar antes de impactar stock real.

**P2.4 Finalización (aplicación parcial si hay errores)**

- Al Finalizar, el sistema **aplica lo válido** y deja los renglones con problema en estado **Error** (con mensaje detallado).
- Los renglones con Error se pueden **corregir** y reintentar en otra “Finalización”.
- UX: mostrar resumen con conteo **Aplicados / Con error / Pendientes**.

**P2.5 Manejo de errores en renglones (sin descartar)**

- No se permite “Descartar renglón” en MVP: los renglones con Error deben **corregirse** para poder completar la tarea de forma limpia.

**P2.6 Locks (nivel de tarea)**

- El lock/claim se aplica a nivel **Tarea completa** (una sola persona procesa/edita la tarea a la vez).
- Se mantienen los parámetros acordados: lock al clic en **Procesar**, timeout 15 min (rolling), heartbeat 10s, lease TTL 3 min, `wire:poll.visible` + idle guard, Admin puede forzar liberar/reclamar (auditado).

**P2.7 UI cuando una tarea está locked**

- Comportamiento: abrir en **solo lectura** (read-only) con banner visible “En proceso por {usuario}” + estado del lock.
- Acciones:
  - Botón **“Solicitar liberación”** (para pedir que la persona libere).
  - Si el usuario actual es **Admin**, además mostrar **“Forzar liberación”** (auditado).

**P2.8 “Solicitar liberación” (sin notificaciones automáticas)**

- MVP: al solicitar liberación solo se muestra modal con el usuario que tiene el lock y recomendación de **contactarlo** (sin notificación automática en app/email).

#### P3 - Seguridad y Acceso (expansión)

**P3.1 Roles (visibilidad de Lector)**

- Roles base: **Admin**, **Editor**, **Lector**.
- Lector: puede ver **todo el detalle** (incluye seriales, `asset_tag`, asignaciones/préstamos e historial).

**P3.2 Roles (fijos en UI)**

- En MVP los roles son **fijos** (Admin/Editor/Lector); el Admin **solo asigna** rol (sin crear roles personalizados en la UI).

**P3.3 Directorio de Empleados (RPE) para asignaciones/préstamos**

- Separar conceptos:
  - **Usuarios del sistema** (login + rol: Admin/Editor/Lector).
  - **Empleados** (personas de la empresa identificadas por **RPE**; pueden no tener login).
- En formularios de Asignación/Préstamo:
  - Campo de búsqueda tipo **autocomplete** por **Nombre** y/o **RPE**.
  - Si existe coincidencia: seleccionar empleado existente.
  - Si no existe: opción **“Agregar empleado”** inline (alta rápida).
- Módulo “Empleados” (directorio):
  - Vista/listado con búsqueda.
  - Ficha de empleado con: **Nombre**, **RPE**, **Departamento**, **Puesto**, **Extensión**, **Correo**, y sección **“Activos del empleado”** (asignados y/o préstamos).

**P3.4 Permisos del Directorio de Empleados**

- **Admin + Editor** pueden **crear/editar** empleados.
- **Lector**: solo lectura.

**P3.5 Auditoría (notas manuales)**

- Además del log automático (best effort), permitir agregar **notas manuales** (comentarios internos) al historial de un **Activo**, **Producto**, **Tarea Pendiente** y **Empleado** para explicar contexto/decisiones.

**P3.6 Adjuntos (permisos de acceso)**

- Los **adjuntos** (SISE/contratos) se pueden **ver/descargar** solo por **Admin + Editor**.
- **Lector**: no puede ver/descargar adjuntos.

#### P4 - Experiencia de Usuario (expansión)

**P4.1 Layout + navegación**

- Patrón UI: **sidebar izquierda** (colapsable) con módulos + **topbar** con buscador global y menú de usuario.

**P4.2 Polling (indicador UX)**

- En vistas con polling, mostrar indicador discreto: **“Actualizado hace Xs”** (y/o “Última actualización: HH:MM”).

**P4.3 Búsquedas lentas (>3s)**

- UX: usar **skeleton loaders** + mensaje “Estamos trabajando…”.
- Permitir **Cancelar** (detener request/polling de esa búsqueda) y mantener resultados previos visibles.

**P4.4 Errores (producción)**

- En producción, ante error backend (500): mostrar **mensaje amigable** + **ID de error** para reporte.
- Si el usuario es **Admin**, permitir botón **“Copiar detalle”** (ver stacktrace/contexto); para no-Admin, sin detalles técnicos.

**P4.5 Toasts + “Deshacer”**

- Para acciones **reversibles** (asignar, prestar, devolver, marcar pendiente), mostrar toast de éxito con botón **“Deshacer”** (~10s) para revertir inmediatamente.
- Se mantiene “Deshacer” en Retiro Rápido (ya acordado).

### Phase 3 - Solution Matrix (Completed)

**Formato elegido:** Scoring **1–5** por iniciativa.

- Columnas: **Valor**, **Esfuerzo**, **Riesgo**, **Dependencias**.
- Rúbrica (por confirmar):
  - Valor: 1 = nice-to-have; 5 = indispensable para operación diaria / evita dolor fuerte.
  - Esfuerzo: 1 = trivial; 5 = grande / multi-módulo.
  - Riesgo: 1 = bajo; 5 = alto (integridad, concurrencia, seguridad, rework).
  - Dependencias: 1 = casi independiente; 5 = muchos prerequisitos/capítulos.

**Matriz (Producto)**

| ID | Iniciativa | Valor | Esfuerzo | Riesgo | Dependencias | Notas |
|---|---|---:|---:|---:|---:|---|
| P-01 | UX base (layout, loaders, cancelar, polling indicator, toasts+undo, errores prod con ID + detalle solo Admin) | 5 | 5 | 2 | 2 | Ajustado por Carlos: Valor 5, Esfuerzo 5 |
| P-02 | Inventario Productos (listado + QTY tooltip + sin stock rojo + filtros + ubicación + buscador unificado + polling visible) | 5 | 5 | 3 | 3 | Core de operación diaria |
| P-03 | Detalles (Producto tabs + Activo tabs) | 4 | 4 | 2 | 3 | Depende de modelos base (P-02/P-06) |
| P-04 | Flujos Activo serializado (estados, acciones, reglas) | 5 | 4 | 4 | 4 | Alto riesgo por integridad de estados y reglas |
| P-05 | No serializados (asignación/préstamo por cantidad + kardex + ajustes) | 4 | 4 | 4 | 3 | Riesgo de consistencia de stock por cantidad |
| P-06 | Empleados (RPE) (directorio + autocomplete + ficha) | 5 | 4 | 2 | 2 | Desbloquea asignaciones/préstamos y trazabilidad |
| P-07 | Tareas Pendientes (carga rápida carrito + procesamiento renglón + aplicar final + locks) | 5 | 5 | 5 | 4 | Complejidad alta (borradores, locks, finalización parcial) |
| P-08 | Auditoría + notas (best effort + notas manuales) | 4 | 3 | 2 | 3 | Transversal; puede iterarse por etapas |
| P-09 | Adjuntos (upload/descarga + UUID + permisos) | 3 | 3 | 3 | 2 | Considerar límites/antivirus/almacenamiento |
| P-10 | Papelera (soft-delete + restaurar + vaciado manual) | 3 | 2 | 2 | 2 | Depende de soft-delete consistente |

**Matriz (Repo/Plataforma)**

| ID | Iniciativa | Valor | Esfuerzo | Riesgo | Dependencias | Notas |
|---|---|---:|---:|---:|---:|---|
| R-01 | Base stack (Laravel 11 + Livewire 3 + Vite + Bootstrap 5) | 5 | 3 | 2 | 1 | Fundacional |
| R-02 | Auth + RBAC (Breeze Blade remaquetado a Bootstrap + roles fijos + policies/gates) | 5 | 4 | 3 | 2 | Incluye restricciones por rol (403/redirect) |
| R-03 | Entorno local (Sail/Docker + MySQL 8 + paridad prod) | 5 | 3 | 2 | 1 | Reduce “works on my machine” |
| R-04 | Calidad + CI (trunk-based, Pint, PHPUnit, Larastan; merge solo si CI verde) | 5 | 3 | 2 | 2 | Alineado a tu regla de CI verde |
| R-05 | Datos base (migraciones + seeders robustos: roles, admin, catálogos demo) | 5 | 3 | 2 | 2 | Vital si reinicias BD seguido |
| R-06 | Deploy (Docker Compose) | 3 | 2 | 3 | 4 | Pendiente hasta acceso a servidor físico |

### Phase 4 - Decision Tree Mapping (Completed)

**Objetivo del árbol:** definir un **camino MVP** (orden de implementación) y “gates” claros para saber cuándo avanzar.

```text
MVP Operativo (intranet)
|
|-- Gate 0: Repo listo (R-01/R-02/R-03/R-04/R-05)
|     - Sail + MySQL 8 + seeders base + auth/roles + CI verde
|
|-- Gate 1: UX + Navegación base (P-01)
|     - Sidebar+Topbar, loaders/cancel, polling indicator, toasts+undo, errores prod con ID
|
|-- Gate 2: Inventario navegable (P-02 + P-03)
|     - Listado Productos + QTY/tooltip + buscador unificado + sin stock en rojo
|     - Detalle Producto (tabs) + Detalle Activo (tabs/acciones visibles según estado)
|
|-- Gate 3: Control operativo (P-06 + P-04 + P-05)
|     - Directorio Empleados (RPE) + autocomplete + ficha con activos
|     - Serializados: estados + asignar/prestar/devolver + reglas
|     - Cantidad: asignaciones/préstamos por cantidad + kardex + ajustes manuales Admin (motivo)
|
|-- Gate 4: Aceleradores diarios (P-07)
|     - Carga Rápida carrito + Procesamiento por renglón (borrador)
|     - Finalización (aplicación parcial) + Locks a nivel tarea (read-only si locked)
|
`-- Gate 5: Cierre de ciclo (P-08 + P-09 + P-10)
      - Auditoría + notas manuales
      - Adjuntos (Admin/Editor)
      - Papelera (soft-delete + restaurar + vaciado manual)
```

**Orden recomendado (con base en tus respuestas):**

1) **Gate 0 (Repo listo)**: porque reinicias BD seguido y quieres paridad/CI desde el inicio.
2) **Gate 1 (UX base)**: lo marcaste como Valor 5 / Esfuerzo 5, y permea todo lo demás.
3) **Gate 2 (Inventario + detalles)**: primer “slice” usable para consulta/operación.
4) **Gate 3 (Empleados + acciones + stock por cantidad)**: habilita el uso real (asignaciones/préstamos).
5) **Gate 4 (Tareas Pendientes)**: es el bloque más complejo/riesgoso; mejor con modelos/UX ya sólidos.
6) **Gate 5 (Auditoría/Adjuntos/Papelera)**: completa compliance y trazabilidad.

**Nota (sin releases):** aunque no haya necesidad de "releases" intermedios, estos Gates funcionan como **hitos internos** para ti (1 solo dev) para validar progreso, reducir riesgo y evitar integrar todo al final.

## Idea Organization and Prioritization (Completed)

### Organización por temas

**Tema A — Plataforma y repo (fundación)**

- Sail/Docker + MySQL 8 (paridad con producción).
- Auth con Breeze (Blade) remaquetado a Bootstrap.
- Roles fijos (Admin/Editor/Lector) + restricciones por rol.
- Calidad: CI verde, Pint, PHPUnit, Larastan.
- Seeders robustos (reinicios frecuentes de BD).

**Tema B — UX y performance (productividad)**

- Layout desktop-first: sidebar colapsable + topbar.
- Polling: `wire:poll.visible` (badges 15s, métricas 60s), “Actualizado hace Xs”.
- Búsquedas lentas: skeleton + botón Cancelar.
- Toasts con “Deshacer” (~10s) para acciones reversibles.
- Errores: mensaje amigable + ID (detalle solo Admin).

**Tema C — Inventario y catálogos (columna vertebral)**

- Vista jerárquica: Productos → Activos.
- QTY (Total/Disponibles/No disponibles) + tooltip con desglose.
- Sin stock: visible y resaltado en rojo cuando `Disponibles = 0`.
- Buscador unificado (Productos + Activos por serial/`asset_tag`, con salto directo a Activo).
- Marcas en MVP; `categorías.is_serialized` y `categorías.requires_asset_tag`.
- Ubicaciones: no-serializados = “Almacén”; serializados = ubicación física (Producto muestra “Varias” + tooltip).

**Tema D — Operación diaria (personas + acciones)**

- Directorio de Empleados (RPE) separado de usuarios del sistema.
- Autocomplete por nombre/RPE + “Agregar empleado” inline.
- Asignación (larga duración) vs Préstamo (temporal).
- Préstamo sin vencimiento: “Vencimiento pendiente” → a los 3 días “Urgente/Crítico”.
- Estados serializados y transiciones; regla: Asignado no se presta (desasignar primero).
- No serializados: asignaciones/préstamos por cantidad + kardex + ajustes manuales Admin con motivo.

**Tema E — Flujo de trabajo (Tareas Pendientes + locks)**

- Carga Rápida tipo “carrito” (varios productos por tarea).
- Procesamiento por renglón (retomable) con **aplicación diferida**.
- Finalización con **aplicación parcial** (lo válido aplica; errores quedan).
- Locks a nivel **tarea**: read-only cuando está locked; “Solicitar liberación” solo informativo; Admin fuerza liberación.

**Tema F — Trazabilidad y evidencia**

- Auditoría best-effort + notas manuales.
- Adjuntos: UUID en disco, nombre original en UI; acceso solo Admin/Editor.
- Papelera: soft-delete, restauración conserva historial; purga manual (eterna hasta que Admin vacíe).

### Priorización (resultado)

Basado en tu confirmación del árbol (sin releases), el orden de implementación recomendado queda:

1) Gate 0 (Repo listo) → 2) Gate 1 (UX base) → 3) Gate 2 (Inventario + detalles) → 4) Gate 3 (Operación diaria) → 5) Gate 4 (Tareas Pendientes) → 6) Gate 5 (Trazabilidad y evidencia)

### Plan de acción (hitos internos)

**Gate 0 — Repo listo (DoD)**

- App levanta en Sail; MySQL 8 listo; seeders crean Admin/roles/datos mínimos.
- Login/roles funcionando y bloqueos por rol (Editor/Lector no entran a usuarios).
- CI en verde (Pint + PHPUnit + Larastan) en PR/merge.

**Gate 1 — UX base (DoD)**

- Layout base (sidebar/topbar) + componentes base (toasts, loaders, errors).
- Polling indicator implementado donde aplique; cancelación en búsquedas.

**Gate 2 — Inventario + detalles (DoD)**

- Listado de Productos con QTY+tooltip y buscador unificado.
- Detalle Producto (tabs) y Detalle Activo (tabs) navegables.

**Gate 3 — Operación diaria (DoD)**

- Directorio Empleados (RPE) completo con ficha y activos asociados.
- Serializados: asignar/prestar/devolver + pendientes/retiro con reglas.
- No serializados: asignación/préstamo por cantidad + kardex + ajuste manual Admin.

**Gate 4 — Tareas Pendientes (DoD)**

- Carga Rápida carrito + procesamiento por renglón (borrador) + finalización parcial.
- Locks a nivel tarea + read-only cuando locked.

**Gate 5 - Trazabilidad (DoD)**

- Auditoría + notas manuales; adjuntos (Admin/Editor); papelera (soft-delete/restaurar/vaciar).

## Session Summary and Insights

**Logros clave**

- Se definió un modelo claro: **Producto (modelo)** vs **Activo (unidad)**, con categorías `is_serialized` y manejo por **cantidad** vs **serial**.
- Se cerró una estrategia operativa de **concurrencia/locks** para Tareas Pendientes (claim preventivo, heartbeat/TTL, override Admin).
- Se alineó la UX a entorno TI interno: **Bootstrap corporativo**, errores con **ID** (detalle solo Admin) y UX rápida (loaders, cancel, undo).

**Decisiones que impactan la arquitectura**

- Se adopta **Livewire polling** (`wire:poll.visible`) con intervalos acordados (badges 15s, métricas 60s; locks heartbeat 10s + TTL 3m).
- Procesamiento de Tareas Pendientes: **carrito**, avance por renglón, **aplicación diferida** y **finalización parcial** si hay errores.

**Riesgos identificados (para tratar temprano)**

- P-07 (Tareas Pendientes) y P-04/P-05 (estados/stock) requieren pruebas y validaciones fuertes para evitar inconsistencias.

**Siguiente paso recomendado**

- Ejecutar el plan por **Gates 0–5** como hitos internos, manteniendo CI verde y seeders robustos para iteración rápida.
