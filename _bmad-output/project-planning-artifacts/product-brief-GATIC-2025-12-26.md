---
stepsCompleted: [1, 2, 3, 4, 5]
inputDocuments:
  - '_bmad-output/analysis/brainstorming-session-2025-12-25.md'
  - '_bmad-output/project-planning-artifacts/gatic-backlog.md'
  - 'docsBmad/project-context.md'
  - 'docsBmad/gates-execution.md'
  - '03-visual-style-guide.md'
inputDocumentCaveats:
  '03-visual-style-guide.md': 'Generada fuera de BMAD; no está actualizada; usarla solo como guía genérica de colores corporativos.'
workflowType: 'product-brief'
lastStep: 5
project_name: 'GATIC'
user_name: 'Carlos'
date: '2025-12-26'
---

# Product Brief: GATIC

<!-- Content will be appended sequentially through collaborative workflow steps -->

## Executive Summary

GATIC es un sistema interno (intranet) para el equipo de TI —especialmente Soporte— que permite saber con precisión qué activos e insumos existen, cuántos hay disponibles y a quién se le prestó/asignó cada cosa, reduciendo pérdidas, “sorpresas” operativas y compras innecesarias.

Hoy la realidad es: un Excel desactualizado (cero confiable) y préstamos sin registro (“se queda en la mente”). El resultado es descubrir faltantes hasta el momento crítico (ej. audífonos para una reunión) y no poder responder rápido preguntas básicas como “¿tenemos X?”, “¿cuántos faltan?” o “¿quién lo tiene?”.

La visión del MVP es pasar de “intuición + Excel” a un inventario vivo con trazabilidad, diseñado para adopción: registrar debe ser más fácil que no registrar. Esto implica conteos exactos por ítem, estados/ubicación para activos serializados, préstamos/asignaciones a empleados (RPE) y señales claras de qué falta y qué comprar, con UX rápida y de mínima fricción.

---

## Core Vision

### Problem Statement

El equipo de TI/Soporte necesita un inventario confiable y un registro simple de préstamos/asignaciones; hoy no hay control real (Excel desactualizado y préstamos sin registro), lo que provoca faltantes, pérdidas y decisiones de compra sin datos.

### Problem Impact

- Se descubre que faltan ítems hasta el último momento (operación reactiva).
- No existe certeza de conteos (“a ciencia exacta”) por tipo de ítem.
- Se prestan cosas y se olvidan: no hay responsable ni historial verificable.
- Se complica decidir compras (“¿qué debemos comprar?”) por falta de visibilidad de disponibilidad y faltantes.

### Why Existing Solutions Fall Short

- Excel depende 100% de disciplina manual: se desactualiza y deja de ser confiable.
- El registro de préstamos hoy no es parte del flujo: si se deja “para luego”, nunca pasa.
- Sin trazabilidad, no hay respuestas rápidas ni rendición de cuentas (“quién tiene qué” y “por qué falta”).

### Proposed Solution

Un sistema web interno, diseñado para operación de Soporte TI (sala tipo almacén), que permita:

- Inventario navegable con conteos exactos por ítem.
- Gestión diferenciada de artículos serializados (por unidad) vs artículos por cantidad.
- Registro de préstamos/asignaciones a empleados (RPE) con estados y disponibilidad, capturado en el momento.
- Búsqueda rápida para contestar en segundos: “¿tenemos X?”, “¿cuántos hay?”, “¿quién lo tiene?”, “¿qué falta comprar?”.
- Flujos rápidos que reduzcan la fricción de registrar movimientos (adopción por diseño) y mantengan la información viva.

### Key Differentiators

- Adopción-first: diseñado para que el registro sea natural y rápido (no “otra carga administrativa”).
- Trazabilidad práctica: responsable + historial para evitar pérdidas y explicar faltantes.
- Enfoque exacto al contexto TI Soporte (sin “almacén formal”, con múltiples perfiles dentro del depto).
- Valor inmediato: respuestas en segundos y claridad para compras/reposición.

## Target Users

### Primary Users

**Persona 1 — “Alex” (Técnico/a de Soporte TI, operador diario)**

- **Contexto:** Parte del equipo de Soporte (dentro de un depto de TI ~13 personas). Opera una sala tipo almacén con laptops, monitores, teclados, audífonos, etc.
- **Objetivos:** Saber “a ciencia exacta” qué hay y qué falta; prestar/recibir sin perder trazabilidad; decidir compras con datos.
- **Dolores actuales:** Excel desactualizado (cero confiable); préstamos “de memoria”; faltantes detectados en el momento crítico (ej. audífonos para reunión).
- **Motivaciones:** Control y trazabilidad sin fricción; evitar pérdidas y “quedar mal” por faltantes.
- **Necesidad clave de UX (adopción-first):** Registrar movimientos con lo mínimo indispensable: **(1) nombre/alias del receptor** + **(2) un textbox de info/nota**, dejando el resto como opcional para no frenar la operación.

### Secondary Users

**Persona 2 — “Jefe/Coordinador TI” (Admin / oversight)**

- **Contexto:** Lidera o supervisa el área; necesita visibilidad y control.
- **Objetivos:** Tener inventario confiable; reducir pérdidas; justificar compras; asegurar que el proceso se cumpla.
- **Cómo interactúa:** Configura/valida reglas, revisa reportes/indicadores, resuelve excepciones (y define estándares de uso).

**No-usuarios (entidades del sistema)**

- **Empleados (RPE):** No tienen cuenta; existen como registros para asociar préstamos/asignaciones y responder “¿quién lo tiene?”.

### User Journey

**Soporte TI (operación diaria)**

- **Discovery:** El equipo reconoce que Excel no sirve y que los préstamos sin registro generan pérdidas/faltantes.
- **Onboarding:** Admin define el esquema inicial (catálogos mínimos) y Soporte aprende el flujo rápido de consulta + registro.
- **Core Usage:**
  - Consulta rápida: “¿tenemos X?” / “¿cuántos hay?”.
  - Movimiento rápido: prestar/asignar capturando mínimo (nombre/alias + nota), opcionalmente llenando más detalle.
  - Revisión: identificar faltantes, responsables y necesidades de compra.
- **Success Moment (Aha):** En segundos responden disponibilidad y, al prestar, queda registro inmediato (ya no depende de memoria).
- **Long-term:** Se vuelve la fuente de verdad; mejora la confiabilidad del inventario y la planeación de compras/reposición.

## Success Metrics

### User Success Metrics (Soporte TI)

- **Respuesta rápida a consultas (“¿tenemos X?”):** objetivo `<10s` desde que el usuario busca hasta que obtiene respuesta útil (disponibilidad / responsable / stock).
- **Trazabilidad en críticos (laptop/monitor/pc):** `100%` de préstamos/asignaciones registrados en el sistema (mínimo: **nombre/alias** + **nota/info**) al momento de entregar.
- **Trazabilidad en artículos por cantidad (cables/teclados/etc.):** `≥80%` de salidas por cantidad registradas con **nombre/alias del receptor** durante el mes 1.
- **Incidentes “explicados”:** `≥80%` de incidentes de “faltante/no encontrado” quedan explicados con un motivo verificable (p.ej. prestado a X, agotado, reubicado, pendiente de retiro), en lugar de quedar como “nadie sabe”.

### Business Objectives

- **Reducir pérdidas y olvidos** al crear responsabilidad y rastro (quién tiene qué / qué salió / por qué falta).
- **Mejorar decisiones de compra** con datos (qué falta, qué se mueve, qué se agota).
- **Subir la confianza del inventario**: pasar de “Excel muerto” a “fuente de verdad” operativa.

### Key Performance Indicators (Month 1)

- KPI-1: Tiempo de respuesta de consulta: `<10s` (objetivo).
- KPI-2: Cobertura de registro en críticos (laptop/monitor/pc): `100%` préstamos/asignaciones con alias+nota.
- KPI-3: Cobertura de registro en cantidad: `≥80%` salidas por cantidad con alias.
- KPI-4: Incidentes explicados: `≥80%` con motivo/explicación registrada.

## MVP Scope

### Core Features

**Estrategia de entrega**

- Sin releases intermedios: se trabaja por **Gates 0–5** como hitos internos y se entrega cuando el “MVP completo” (Gate 5) está listo.

**Incluido en MVP (Gates 0–5 completos)**

- **Gate 0 (Fundación):** app base + entorno local + roles/autorización + calidad/CI + seeders.
- **Gate 1 (UX base):** layout + componentes UX + manejo de errores + patrón de actualización/polling donde aplique.
- **Gate 2 (Inventario navegable):** Productos/Activos + búsqueda unificada + detalles.
- **Gate 3 (Operación diaria):** Empleados (RPE) + préstamos/asignaciones + manejo por cantidad + métricas básicas.
- **Gate 4 (Flujo rápido):** Tareas Pendientes (carga/procesamiento/locks) y aceleradores como carga/retiro rápido.
- **Gate 5 (Cierre de ciclo):** trazabilidad y evidencia (auditoría/adjuntos/papelera).

**Roles/usuarios**

- Usuarios operativos: **Soporte TI**.
- **Empleados (RPE)** existen como registros para asociar préstamos/asignaciones; **no tienen cuenta**.
- Roles mínimos en MVP: **Admin + Editor**.

**Regla de adopción (clave)**

- En registros de movimiento, lo obligatorio es: **nombre/alias del receptor + nota/info**; el resto opcional para no frenar la operación.

### Out of Scope for MVP

- **Reportes avanzados**
- **Notificaciones por email**
- **Multi-almacén / multi-ubicación como stock separado**

### MVP Success Criteria

- Cumplir DoD por Gates 0–5.
- Métricas de éxito (mes 1):
  - Consultas “¿tenemos X?” en `<10s`.
  - `100%` de préstamos/asignaciones registrados para críticos (laptop/monitor/pc) con alias+nota.
  - `≥80%` de salidas por cantidad registradas con alias.
  - `≥80%` de incidentes con explicación verificable.

### Future Vision

- Post-MVP (candidato): reportes avanzados, notificaciones email, multi-almacén.
- Otros “nice to have” quedan **por definir** según uso real del equipo.
