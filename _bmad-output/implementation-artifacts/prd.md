---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
inputDocuments:
  - '_bmad-output/project-planning-artifacts/product-brief-GATIC-2025-12-26.md'
  - '_bmad-output/analysis/brainstorming-session-2025-12-25.md'
documentCounts:
  briefs: 1
  research: 0
  brainstorming: 1
  projectDocs: 0
workflowType: 'prd'
lastStep: 11
project_name: 'GATIC'
user_name: 'Carlos'
date: '2025-12-26'
---

# Product Requirements Document - GATIC

**Author:** Carlos
**Date:** 2025-12-26

<!-- Content will be appended sequentially through collaborative workflow steps -->

## Executive Summary

GATIC es una aplicación web interna (intranet / on‑prem) para el equipo de TI (especialmente Soporte) que convierte el inventario en una fuente de verdad operativa: qué existe, cuántos hay disponibles y a quién se le prestó o asignó cada cosa.

Hoy el problema es doble: (1) el inventario en Excel se desactualiza y deja de ser confiable, y (2) los movimientos (préstamos/asignaciones) se registran “de memoria”, provocando faltantes sorpresa, pérdidas y compras sin datos. El MVP busca responder en segundos preguntas críticas (“¿tenemos X?”, “¿cuántos hay?”, “¿quién lo tiene?”) y dejar trazabilidad en el momento de la entrega.

El alcance se centra en: Productos vs Activos (serializados) y artículos por cantidad (stock), estados y acciones de operación diaria (asignar/prestar/devolver/retiro), y un flujo de “Tareas Pendientes” para carga/operación rápida con locks de concurrencia. El sistema está pensado para uso diario en escritorio, en un entorno on‑prem, priorizando simplicidad operativa (sin WebSockets; polling cuando aplique).

### What Makes This Special

- Adopción‑first: registrar es más fácil que “no registrar”, reduciendo fricción en operación diaria.
- Mínimo obligatorio en movimientos: alias/nombre del receptor + nota/info; el resto opcional.
- Trazabilidad práctica: disponibilidad clara + responsable + historial verificable.
- UX rápida para intranet: búsqueda enfocada, feedback inmediato y actualización por polling.

## Project Classification

**Technical Type:** web_app  
**Domain:** general  
**Complexity:** low  
**Project Context:** Greenfield - new project

Racional:

- Es una aplicación web interna; el valor está en flujos CRUD + operación diaria (no SEO/marketing).
- Los “requisitos de tiempo real” se cubren con polling/estados visibles para evitar complejidad innecesaria.
- Diseño visual: `03-visual-style-guide.md` se usará solo como referencia de colores corporativos (está desactualizado).

## Success Criteria

### User Success

- Responder consultas “¿tenemos X?” con resultado útil (disponibilidad/responsable/stock) en `<10s`.
- Trazabilidad en “críticos” (laptop/monitor/pc): `100%` de préstamos/asignaciones registrados al momento, con **alias/nombre + nota** (mínimo obligatorio).
- Artículos por cantidad: `≥80%` de salidas registradas con **alias/nombre** durante el mes 1.
- Incidentes de “faltante/no encontrado” quedan **explicados**: `≥80%` con motivo verificable (prestado a X, agotado, reubicado, pendiente de retiro, etc.).

### Business Success

- Reducir pérdidas/olvidos al establecer responsable + historial verificable por movimiento.
- Mejorar decisiones de compra/reposición con visibilidad real de disponibilidad y faltantes.
- Elevar la confianza del inventario como “fuente de verdad” operativa (Soporte deja de depender de Excel/memoria).

### Technical Success

- Operación on‑prem con UX rápida de baja fricción (desktop‑first).
- Concurrencia controlada en “Tareas Pendientes” mediante lock/claim con heartbeat/TTL/timeout y override Admin.
- Actualización por polling donde aplique (sin WebSockets) para mantener estados/badges confiables.
- Manejo de errores en producción con mensaje amigable + **ID de error**; detalle técnico solo para Admin.
- Adjuntos seguros (nombre saneado, UUID en disco; mostrar nombre original en UI).
- Auditoría “best effort”: si falla, no bloquea la operación del usuario (se registra internamente).

### Measurable Outcomes

- Mes 1:
  - `<10s` para consultas clave.
  - `100%` críticos con registro (alias+nota).
  - `≥80%` salidas por cantidad con alias.
  - `≥80%` incidentes explicados.

## Product Scope

### MVP - Minimum Viable Product

- Entrega por Gates 0–5 completa:
  - Base repo + auth/roles + CI/seeders.
  - UX base + patrón de polling/errores.
  - Inventario (Productos/Activos) + búsqueda + detalles.
  - Operación diaria (empleados RPE, préstamos/asignaciones, estados, cantidad).
  - Tareas Pendientes con locks + finalización parcial.
  - Trazabilidad (auditoría/notas), adjuntos, papelera (soft-delete).

### Growth Features (Post-MVP)

- Reportes avanzados.
- Notificaciones por email.
- Multi‑almacén / multi‑ubicación como stock separado.

### Vision (Future)

- Evolución de reporting/alertas y capacidades multi‑ubicación según adopción y uso real.

## User Journeys

### Journey 1 — “Alex” (Editor / Soporte TI): consulta + préstamo/asignación (happy path)

Alex está en operación diaria y le piden “¿tenemos audífonos para una reunión?”. Entra a GATIC desde su estación (intranet) y usa el buscador unificado. En segundos ve el Producto, con QTY claro (Total / Disponibles / No disponibles) y puede entrar al detalle si necesita ver el desglose.

Confirma disponibilidad y, al momento de entregar, registra el movimiento con el mínimo obligatorio para no frenar la operación: selecciona el **Empleado (RPE)** (autocomplete por nombre/RPE o alta rápida si aplica) y captura una **nota/info**. Si el ítem es serializado, selecciona el Activo específico; si es por cantidad, registra la cantidad. Guarda y recibe confirmación inmediata (toast) y el sistema refleja el nuevo estado/disponibilidad sin depender de memoria o Excel.

Resultado: Alex resuelve la consulta y deja trazabilidad en el momento, con fricción mínima.

### Journey 2 — “Alex” (Editor / Soporte TI): Tarea Pendiente con lock (edge case / concurrencia)

Alex necesita procesar una “Tarea Pendiente” de carga/retiro (flujo rápido tipo carrito). Al hacer clic en “Procesar”, el sistema intenta adquirir el lock/claim de la tarea. Si el lock se adquiere, Alex trabaja con la confianza de que nadie más procesará lo mismo en paralelo mientras el heartbeat mantiene vigente el claim.

Si la tarea ya está bloqueada por otro Editor, Alex entra en modo solo lectura: ve quién la tiene y desde cuándo; puede “Solicitar liberación” (informativo) y esperar a que el lock expire por timeout/TTL o pedir a Admin que lo libere/force-claim si es urgente. Cuando el lock queda libre, Alex puede reclamarlo y continuar.

Resultado: se evita doble procesamiento y se mantiene consistencia operativa.

### Journey 3 — Admin (supervisión y excepciones): gobernanza + resolución de bloqueos + mantenimiento

Admin prepara el sistema para el equipo: gestiona usuarios y roles, valida catálogos base (categorías/flags como serializado vs cantidad), y revisa que las reglas operativas se cumplan.

En el día a día, Admin atiende excepciones: si un Editor quedó con una Tarea Pendiente bloqueada por cierre inesperado, Admin puede liberar/forzar reclamo del lock (acción auditada). Si ocurre un error en producción, Admin usa el **ID de error** para ver detalle técnico y apoyar diagnóstico sin bloquear la operación. Admin también gestiona el ciclo de vida: revisar auditoría/notas, administrar adjuntos (cuando aplique) y operar la papelera (restaurar/vaciar) bajo controles.

Resultado: el sistema se mantiene operable y controlado, con caminos claros para excepciones.

### Journey 4 — Lector (solo consulta): visibilidad sin riesgo

Un usuario con rol Lector entra para consultar disponibilidad y trazabilidad básica sin ejecutar acciones. Usa búsqueda y navegación para ver Productos/Activos, estados y conteos, respondiendo “¿tenemos X?” sin riesgo de cambios accidentales. No realiza acciones destructivas ni movimientos, y en MVP no accede a adjuntos.

Resultado: transparencia operativa sin ampliar superficie de riesgo.

### Journey Requirements Summary

Estas journeys revelan necesidad de capacidades en:

- Autenticación + RBAC (Admin/Editor/Lector) y restricción de acciones por rol.
- Búsqueda unificada (Producto + Activo por serial/asset_tag) y listados con QTY (Total/Disponibles/No disponibles).
- Detalles de Producto/Activo con estados y responsables visibles.
- Directorio de Empleados (RPE) y selección rápida en flujos de movimiento.
- Flujos de préstamos/asignaciones/devoluciones y movimientos por cantidad.
- “Tareas Pendientes” con procesamiento y locks (claim/heartbeat/TTL/timeout + override Admin).
- UX de intranet: feedback inmediato, manejo de lentitud (loaders/cancel) y polling donde aplique.
- Errores en prod con ID + detalle solo Admin; auditoría best-effort; adjuntos y papelera según Gate 5.

## Web App Specific Requirements

### Project-Type Overview

GATIC es una aplicación web interna (intranet / on‑prem) orientada a escritorio, implementada como **MPA** con Laravel + Blade + Livewire para interactividad sin convertirla en SPA.

### Technical Architecture Considerations

- **Rendering:** MPA (server‑rendered) con componentes Livewire para acciones rápidas y feedback inmediato.
- **Real‑time:** no; solo polling donde aplique (sin WebSockets).
- **SEO:** no aplica (intranet, detrás de autenticación).
- **Acceso:** autenticación obligatoria y autorización por rol (Admin/Editor/Lector).
- **Entorno objetivo:** uso interno controlado (sin necesidad de soporte amplio de navegadores).

### Browser Matrix

- Soporte objetivo: **Chrome** y **Edge** (versiones actuales) en entorno corporativo.
- No prioritario en MVP: Safari/Firefox, navegadores móviles.

### Responsive Design

- **Desktop‑first**: layout optimizado para operación diaria (listas, tablas, formularios).
- Responsive “lo suficiente”: degradación correcta en pantallas medianas (sin enfoque mobile‑first en MVP).

### Performance Targets

- La experiencia debe permitir responder consultas “¿tenemos X?” en `<10s` (objetivo UX).
- Si una búsqueda tarda `>3s`: mostrar loader/skeleton + mensaje de progreso + opción de cancelar.
- Evitar operaciones pesadas en request; usar procesos async cuando sea necesario (sin bloquear al usuario).

### SEO Strategy

- Sin requisitos de SEO (no indexación pública; app interna con login).

### Accessibility Level

- Accesibilidad básica para intranet: HTML semántico, labels/errores claros, navegación por teclado en flujos principales y manejo correcto de foco (modales/diálogos).
- No se planifica compliance formal (WCAG/508) en MVP.

## Project Scoping & Phased Development

### MVP Strategy & Philosophy

**MVP Approach:** Platform + Experience (entregar una base sólida y una UX operable para uso diario)  
**Resource Requirements:** 1 persona (Full‑stack Laravel + QA)

### MVP Feature Set (Phase 1)

**Core User Journeys Supported:**

- Editor (Soporte TI): consulta + movimientos (serializados y por cantidad)
- Editor: Tareas Pendientes con lock (concurrencia)
- Admin: gobernanza + resolución de excepciones (locks/errores)
- Lector: consulta (bajo riesgo) *(rol final; uso operativo menor en MVP)*

**Must-Have Capabilities:**

- Auth + RBAC (Admin/Editor/Lector) con restricción por rol
- Inventario navegable: Productos/Activos + búsqueda unificada
- Operación diaria: préstamos/asignaciones/devoluciones y movimientos por cantidad
- Empleados (RPE) como entidad para asignar/prestar
- Tareas Pendientes: carrito/procesamiento + locks (claim/heartbeat/TTL/timeout + override Admin)
- UX intranet: feedback inmediato, loaders/cancel si >3s, polling donde aplique
- Trazabilidad y cierre de ciclo (Gate 5): auditoría/notas, adjuntos, papelera

### Post-MVP Features

**Phase 2 (Post-MVP):**

- Reportes avanzados
- Notificaciones por email

**Phase 3 (Expansion):**

- Multi‑almacén / multi‑ubicación como stock separado

### Risk Mitigation Strategy

**Technical Risks:**

- Concurrencia/locks en Tareas Pendientes → implementar temprano y validar con casos borde (timeout/force‑release)
- Integridad de inventario (serializados vs cantidad) → reglas claras de estados/transiciones y validaciones estrictas

**Market Risks:**

- Adopción (que vuelvan a “Excel/memoria”) → UX de baja fricción (mínimo obligatorio alias/nombre + nota) y flujos rápidos

**Resource Risks:**

- Equipo de 1 persona → ejecución por Gates, foco en “must‑have”, evitar scope creep y priorizar CI/seeders para iteración rápida

## Functional Requirements

### Access & Roles

- FR1: Usuario puede iniciar y cerrar sesión.
- FR2: El sistema puede aplicar control de acceso por rol (Admin/Editor/Lector) en todas las acciones.
- FR3: Admin puede crear, deshabilitar y asignar rol a usuarios del sistema.

### Catalogs & Configuration

- FR4: Admin puede gestionar Categorías, incluyendo si son serializadas y si requieren `asset_tag`.
- FR5: Admin/Editor puede gestionar Marcas.
- FR6: Admin/Editor puede gestionar Ubicaciones.
- FR7: El sistema puede impedir eliminar catálogos referenciados y permitir soft-delete cuando no lo estén.

### Inventory: Products & Assets

- FR8: Admin/Editor puede crear y mantener Productos y sus atributos/catálogos asociados.
- FR9: El sistema puede manejar Productos como serializados o por cantidad según su Categoría.
- FR10: Admin/Editor puede crear y mantener Activos (para productos serializados) con `serial` y `asset_tag` (si aplica).
- FR11: El sistema puede aplicar unicidad de `asset_tag` global y unicidad de `serial` por Producto.
- FR12: Usuario puede ver detalle de Producto con conteos de disponibilidad y desglose por estado.
- FR13: Usuario puede ver detalle de Activo con su estado actual, ubicación y tenencia actual (si aplica).
- FR14: Admin puede realizar ajustes de inventario registrando un motivo.

### Employees (RPE)

- FR15: Admin/Editor puede crear y mantener Empleados (RPE) como receptores de movimientos.
- FR16: Usuario puede buscar/seleccionar Empleados al registrar movimientos.

### Movements & Daily Operations

- FR17: Admin/Editor puede asignar un Activo serializado a un Empleado.
- FR18: Admin/Editor puede prestar un Activo serializado a un Empleado.
- FR19: Admin/Editor puede registrar devoluciones de Activos serializados.
- FR20: El sistema puede aplicar reglas de transición/validación para evitar acciones en conflicto (según estados).
- FR21: Admin/Editor puede registrar movimientos por cantidad (salida/entrada) vinculados a Producto y Empleado.
- FR22: El sistema puede mantener historial de movimientos (kardex) para productos por cantidad.

### Search & Discovery

- FR23: Usuario puede buscar Productos y Activos por nombre e identificadores (serial, `asset_tag`).
- FR24: Usuario puede filtrar inventario por categoría, marca, ubicación y estado/disponibilidad.
- FR25: El sistema puede presentar indicadores de disponibilidad (total/disponibles/no disponibles) por Producto.

### Pending Tasks & Concurrency Locks

- FR26: Admin/Editor puede crear una Tarea Pendiente para procesar múltiples renglones en lote.
- FR27: Admin/Editor puede añadir/editar/eliminar renglones de una Tarea Pendiente antes de finalizarla.
- FR28: El sistema puede permitir procesamiento por renglón y finalización parcial (aplica lo válido y deja pendientes/errores).
- FR29: El sistema puede asegurar procesamiento exclusivo por un solo Editor mediante lock/claim.
- FR30: El sistema puede mostrar estado del lock (quién lo tiene y desde cuándo) a otros usuarios.
- FR31: Admin puede liberar o forzar el reclamo de un lock de Tarea Pendiente.

### Traceability, Attachments & Trash

- FR32: El sistema puede registrar y permitir consultar auditoría de acciones clave a roles autorizados.
- FR33: Usuario puede agregar notas manuales a registros relevantes (según permisos).
- FR34: Admin/Editor puede subir/ver/eliminar adjuntos asociados a registros; Lector no puede acceder a adjuntos en MVP.
- FR35: El sistema puede hacer soft-delete y permitir a Admin restaurar o purgar definitivamente desde Papelera.
- FR36: El sistema puede mostrar un ID de error ante fallos inesperados y permitir a Admin consultar el detalle asociado.

## Non-Functional Requirements

### Performance & Responsiveness

- NFR1: El sistema debe soportar operación diaria en intranet con UX fluida (desktop-first) en flujos de consulta y registro.
- NFR2: Si una consulta/búsqueda tarda `>3s`, la UI debe mostrar loader/skeleton + mensaje de progreso + opción de cancelar.
- NFR3: Actualización de estados vía polling (sin WebSockets) cuando aplique:
  - Badges/estados en listas: cada ~15s
  - Métricas dashboard: cada ~60s
  - Heartbeat de locks: cada ~10s

### Security & Access Control

- NFR4: Autenticación obligatoria y autorización por rol aplicada del lado servidor (no solo en UI).
- NFR5: Lector no debe poder ejecutar acciones destructivas ni acceder a adjuntos en MVP.
- NFR6: Adjuntos deben almacenarse con nombre seguro (UUID en disco) y mostrarse con nombre original en UI; validar tipo/tamaño según política definida.

### Reliability & Data Integrity

- NFR7: Operaciones críticas (movimientos, cambios de estado, procesamiento de tareas) deben ser atómicas; no debe quedar inventario en estado inconsistente.
- NFR8: Auditoría “best effort”: si falla el registro de auditoría, la operación principal del usuario no debe bloquearse; el fallo debe quedar registrado internamente.
- NFR9: Locks de Tareas Pendientes deben evitar bloqueos “eternos”:
  - Timeout rolling: ~15 min
  - Lease TTL: ~3 min renovado por heartbeat
  - Idle guard: no renovar si no hubo actividad real ~2 min
  - Admin puede liberar/forzar reclamo (auditado)

### Operability

- NFR10: En producción, errores inesperados deben mostrarse con mensaje amigable + ID de error; detalle técnico solo visible para Admin.
