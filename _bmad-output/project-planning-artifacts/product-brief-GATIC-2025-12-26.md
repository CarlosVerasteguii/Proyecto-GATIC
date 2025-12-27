---
stepsCompleted: [1, 2, 3, 4, 5]
inputDocuments:
  - '_bmad-output/analysis/brainstorming-session-2025-12-25.md'
  - '_bmad-output/project-planning-artifacts/gatic-backlog.md'
  - 'docsBmad/project-context.md'
  - 'docsBmad/gates-execution.md'
  - '03-visual-style-guide.md'
inputDocumentCaveats:
  '03-visual-style-guide.md': 'Generada fuera de BMAD; no est├í actualizada; usarla solo como gu├¡a gen├®rica de colores corporativos.'
workflowType: 'product-brief'
lastStep: 5
project_name: 'GATIC'
user_name: 'Carlos'
date: '2025-12-26'
---

# Product Brief: GATIC

<!-- Content will be appended sequentially through collaborative workflow steps -->

## Executive Summary

GATIC es un sistema interno (intranet) para el equipo de TI ÔÇöespecialmente SoporteÔÇö que permite saber con precisi├│n qu├® activos e insumos existen, cu├íntos hay disponibles y a qui├®n se le prest├│/asign├│ cada cosa, reduciendo p├®rdidas, ÔÇ£sorpresasÔÇØ operativas y compras innecesarias.

Hoy la realidad es: un Excel desactualizado (cero confiable) y pr├®stamos sin registro (ÔÇ£se queda en la menteÔÇØ). El resultado es descubrir faltantes hasta el momento cr├¡tico (ej. aud├¡fonos para una reuni├│n) y no poder responder r├ípido preguntas b├ísicas como ÔÇ£┬┐tenemos X?ÔÇØ, ÔÇ£┬┐cu├íntos faltan?ÔÇØ o ÔÇ£┬┐qui├®n lo tiene?ÔÇØ.

La visi├│n del MVP es pasar de ÔÇ£intuici├│n + ExcelÔÇØ a un inventario vivo con trazabilidad, dise├▒ado para adopci├│n: registrar debe ser m├ís f├ícil que no registrar. Esto implica conteos exactos por ├¡tem, estados/ubicaci├│n para activos serializados, pr├®stamos/asignaciones a empleados (RPE) y se├▒ales claras de qu├® falta y qu├® comprar, con UX r├ípida y de m├¡nima fricci├│n.

---

## Core Vision

### Problem Statement

El equipo de TI/Soporte necesita un inventario confiable y un registro simple de pr├®stamos/asignaciones; hoy no hay control real (Excel desactualizado y pr├®stamos sin registro), lo que provoca faltantes, p├®rdidas y decisiones de compra sin datos.

### Problem Impact

- Se descubre que faltan ├¡tems hasta el ├║ltimo momento (operaci├│n reactiva).
- No existe certeza de conteos (ÔÇ£a ciencia exactaÔÇØ) por tipo de ├¡tem.
- Se prestan cosas y se olvidan: no hay responsable ni historial verificable.
- Se complica decidir compras (ÔÇ£┬┐qu├® debemos comprar?ÔÇØ) por falta de visibilidad de disponibilidad y faltantes.

### Why Existing Solutions Fall Short

- Excel depende 100% de disciplina manual: se desactualiza y deja de ser confiable.
- El registro de pr├®stamos hoy no es parte del flujo: si se deja ÔÇ£para luegoÔÇØ, nunca pasa.
- Sin trazabilidad, no hay respuestas r├ípidas ni rendici├│n de cuentas (ÔÇ£qui├®n tiene qu├®ÔÇØ y ÔÇ£por qu├® faltaÔÇØ).

### Proposed Solution

Un sistema web interno, dise├▒ado para operaci├│n de Soporte TI (sala tipo almac├®n), que permita:

- Inventario navegable con conteos exactos por ├¡tem.
- Gesti├│n diferenciada de art├¡culos serializados (por unidad) vs art├¡culos por cantidad.
- Registro de pr├®stamos/asignaciones a empleados (RPE) con estados y disponibilidad, capturado en el momento.
- B├║squeda r├ípida para contestar en segundos: ÔÇ£┬┐tenemos X?ÔÇØ, ÔÇ£┬┐cu├íntos hay?ÔÇØ, ÔÇ£┬┐qui├®n lo tiene?ÔÇØ, ÔÇ£┬┐qu├® falta comprar?ÔÇØ.
- Flujos r├ípidos que reduzcan la fricci├│n de registrar movimientos (adopci├│n por dise├▒o) y mantengan la informaci├│n viva.

### Key Differentiators

- Adopci├│n-first: dise├▒ado para que el registro sea natural y r├ípido (no ÔÇ£otra carga administrativaÔÇØ).
- Trazabilidad pr├íctica: responsable + historial para evitar p├®rdidas y explicar faltantes.
- Enfoque exacto al contexto TI Soporte (sin ÔÇ£almac├®n formalÔÇØ, con m├║ltiples perfiles dentro del depto).
- Valor inmediato: respuestas en segundos y claridad para compras/reposici├│n.

## Target Users

### Primary Users

**Persona 1 ÔÇö ÔÇ£AlexÔÇØ (T├®cnico/a de Soporte TI, operador diario)**

- **Contexto:** Parte del equipo de Soporte (dentro de un depto de TI ~13 personas). Opera una sala tipo almac├®n con laptops, monitores, teclados, aud├¡fonos, etc.
- **Objetivos:** Saber ÔÇ£a ciencia exactaÔÇØ qu├® hay y qu├® falta; prestar/recibir sin perder trazabilidad; decidir compras con datos.
- **Dolores actuales:** Excel desactualizado (cero confiable); pr├®stamos ÔÇ£de memoriaÔÇØ; faltantes detectados en el momento cr├¡tico (ej. aud├¡fonos para reuni├│n).
- **Motivaciones:** Control y trazabilidad sin fricci├│n; evitar p├®rdidas y ÔÇ£quedar malÔÇØ por faltantes.
- **Necesidad clave de UX (adopci├│n-first):** Registrar movimientos con lo m├¡nimo indispensable: **(1) nombre/alias del receptor** + **(2) un textbox de info/nota**, dejando el resto como opcional para no frenar la operaci├│n.

### Secondary Users

**Persona 2 ÔÇö ÔÇ£Jefe/Coordinador TIÔÇØ (Admin / oversight)**

- **Contexto:** Lidera o supervisa el ├írea; necesita visibilidad y control.
- **Objetivos:** Tener inventario confiable; reducir p├®rdidas; justificar compras; asegurar que el proceso se cumpla.
- **C├│mo interact├║a:** Configura/valida reglas, revisa reportes/indicadores, resuelve excepciones (y define est├índares de uso).

**No-usuarios (entidades del sistema)**

- **Empleados (RPE):** No tienen cuenta; existen como registros para asociar pr├®stamos/asignaciones y responder ÔÇ£┬┐qui├®n lo tiene?ÔÇØ.

### User Journey

**Soporte TI (operaci├│n diaria)**

- **Discovery:** El equipo reconoce que Excel no sirve y que los pr├®stamos sin registro generan p├®rdidas/faltantes.
- **Onboarding:** Admin define el esquema inicial (cat├ílogos m├¡nimos) y Soporte aprende el flujo r├ípido de consulta + registro.
- **Core Usage:**
  - Consulta r├ípida: ÔÇ£┬┐tenemos X?ÔÇØ / ÔÇ£┬┐cu├íntos hay?ÔÇØ.
  - Movimiento r├ípido: prestar/asignar capturando m├¡nimo (nombre/alias + nota), opcionalmente llenando m├ís detalle.
  - Revisi├│n: identificar faltantes, responsables y necesidades de compra.
- **Success Moment (Aha):** En segundos responden disponibilidad y, al prestar, queda registro inmediato (ya no depende de memoria).
- **Long-term:** Se vuelve la fuente de verdad; mejora la confiabilidad del inventario y la planeaci├│n de compras/reposici├│n.

## Success Metrics

### User Success Metrics (Soporte TI)

- **Respuesta r├ípida a consultas (ÔÇ£┬┐tenemos X?ÔÇØ):** objetivo `<10s` desde que el usuario busca hasta que obtiene respuesta ├║til (disponibilidad / responsable / stock).
- **Trazabilidad en cr├¡ticos (laptop/monitor/pc):** `100%` de pr├®stamos/asignaciones registrados en el sistema (m├¡nimo: **nombre/alias** + **nota/info**) al momento de entregar.
- **Trazabilidad en art├¡culos por cantidad (cables/teclados/etc.):** `ÔëÑ80%` de salidas por cantidad registradas con **nombre/alias del receptor** durante el mes 1.
- **Incidentes ÔÇ£explicadosÔÇØ:** `ÔëÑ80%` de incidentes de ÔÇ£faltante/no encontradoÔÇØ quedan explicados con un motivo verificable (p.ej. prestado a X, agotado, reubicado, pendiente de retiro), en lugar de quedar como ÔÇ£nadie sabeÔÇØ.

### Business Objectives

- **Reducir p├®rdidas y olvidos** al crear responsabilidad y rastro (qui├®n tiene qu├® / qu├® sali├│ / por qu├® falta).
- **Mejorar decisiones de compra** con datos (qu├® falta, qu├® se mueve, qu├® se agota).
- **Subir la confianza del inventario**: pasar de ÔÇ£Excel muertoÔÇØ a ÔÇ£fuente de verdadÔÇØ operativa.

### Key Performance Indicators (Month 1)

- KPI-1: Tiempo de respuesta de consulta: `<10s` (objetivo).
- KPI-2: Cobertura de registro en cr├¡ticos (laptop/monitor/pc): `100%` pr├®stamos/asignaciones con alias+nota.
- KPI-3: Cobertura de registro en cantidad: `ÔëÑ80%` salidas por cantidad con alias.
- KPI-4: Incidentes explicados: `ÔëÑ80%` con motivo/explicaci├│n registrada.

## MVP Scope

### Core Features

**Estrategia de entrega**

- Sin releases intermedios: se trabaja por **Gates 0ÔÇô5** como hitos internos y se entrega cuando el ÔÇ£MVP completoÔÇØ (Gate 5) est├í listo.

**Incluido en MVP (Gates 0ÔÇô5 completos)**

- **Gate 0 (Fundaci├│n):** app base + entorno local + roles/autorizaci├│n + calidad/CI + seeders.
- **Gate 1 (UX base):** layout + componentes UX + manejo de errores + patr├│n de actualizaci├│n/polling donde aplique.
- **Gate 2 (Inventario navegable):** Productos/Activos + b├║squeda unificada + detalles.
- **Gate 3 (Operaci├│n diaria):** Empleados (RPE) + pr├®stamos/asignaciones + manejo por cantidad + m├®tricas b├ísicas.
- **Gate 4 (Flujo r├ípido):** Tareas Pendientes (carga/procesamiento/locks) y aceleradores como carga/retiro r├ípido.
- **Gate 5 (Cierre de ciclo):** trazabilidad y evidencia (auditor├¡a/adjuntos/papelera).

**Roles/usuarios**

- Usuarios operativos: **Soporte TI**.
- **Empleados (RPE)** existen como registros para asociar pr├®stamos/asignaciones; **no tienen cuenta**.
- Roles m├¡nimos en MVP: **Admin + Editor**.

**Regla de adopci├│n (clave)**

- En registros de movimiento, lo obligatorio es: **nombre/alias del receptor + nota/info**; el resto opcional para no frenar la operaci├│n.

### Out of Scope for MVP

- **Reportes avanzados**
- **Notificaciones por email**
- **MultiÔÇæalmac├®n / multiÔÇæubicaci├│n como stock separado**

### MVP Success Criteria

- Cumplir DoD por Gates 0ÔÇô5.
- M├®tricas de ├®xito (mes 1):
  - Consultas ÔÇ£┬┐tenemos X?ÔÇØ en `<10s`.
  - `100%` de pr├®stamos/asignaciones registrados para cr├¡ticos (laptop/monitor/pc) con alias+nota.
  - `ÔëÑ80%` de salidas por cantidad registradas con alias.
  - `ÔëÑ80%` de incidentes con explicaci├│n verificable.

### Future Vision

- PostÔÇæMVP (candidato): reportes avanzados, notificaciones email, multiÔÇæalmac├®n.
- Otros ÔÇ£nice to haveÔÇØ quedan **por definir** seg├║n uso real del equipo.
