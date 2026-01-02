---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14]
inputDocuments:
  - "_bmad-output/architecture.md"
  - "_bmad-output/prd.md"
  - "_bmad-output/project-planning-artifacts/epics.md"
  - "_bmad-output/project-planning-artifacts/product-brief-GATIC-2025-12-26.md"
  - "03-visual-style-guide.md"
workflowType: 'ux-design'
lastStep: 14
project_name: "GATIC"
user_name: "Carlos"
date: "2025-12-27T16:45:01.1265028-06:00"
---

# UX Design Specification GATIC

**Author:** Carlos
**Date:** 2025-12-27T16:45:01.1265028-06:00

---

<!-- UX design content will be appended sequentially through collaborative workflow steps -->

## Executive Summary

### Project Vision

GATIC es una aplicación web interna (intranet/on‑prem) para el equipo de TI (especialmente Soporte) que convierte el inventario en una “fuente de verdad” operativa: qué existe, cuántos hay disponibles y quién tiene cada cosa cuando se presta o asigna.

El objetivo del MVP es eliminar la dependencia de Excel/memoria y reducir pérdidas y sorpresas operativas mediante trazabilidad inmediata en el punto de entrega. La UX se diseña “adoption-first”: registrar un movimiento debe ser más fácil que no registrarlo (mínimo obligatorio: receptor + nota).

El uso será 100% en escritorio/laptop con monitores de resolución grande, priorizando densidad de información, velocidad y flujos repetibles.

### Target Users

- Soporte TI (Editor, usuario primario): opera inventario a diario; busca respuestas rápidas (“¿tenemos X?”, “¿cuántos?”, “¿quién lo tiene?”) y necesita registrar préstamos/asignaciones/devoluciones sin fricción.
- Coordinador/Jefe TI (Admin, usuario secundario): configura catálogos, usuarios/roles, resuelve excepciones (ej. locks), y requiere visibilidad/auditoría para control.
- Lector (consulta): acceso solo a lectura; sin acciones destructivas; sin adjuntos en MVP.
- Empleados (RPE): receptores de movimientos (no son usuarios con cuenta).

### Key Design Challenges

- Adopción y fricción (“da hueva”): formularios y flujos deben ser ultrarrápidos, con lo mínimo indispensable, defaults inteligentes y soporte a repetición (sin sentirse “burocrático”).
- Búsqueda y respuesta operativa en <10s: búsqueda unificada por nombre/serial/asset_tag, resultados escaneables, y detalle que responda disponibilidad + responsable + estado sin pasos extra.
- Modelo dual (serializados vs por cantidad): comunicar claramente conteos, estados, y qué acciones aplican según tipo; evitar errores de operación.
- Operación diaria y estados: guiar transiciones (asignar/prestar/devolver/retiro) con validaciones claras y mensajes accionables.
- Tareas Pendientes + concurrencia: UX del lock (quién lo tiene, desde cuándo, expiración/heartbeat, override Admin) debe ser entendible y evitar doble procesamiento.
- “Near-real-time” sin WebSockets: polling visible sin distracciones; indicadores de “última actualización”; manejo de estados stale y cargas >3s (skeleton/progreso/cancelar).
- Seguridad/roles y evidencia: acciones y adjuntos restringidos por rol; errores con ID (mensaje amigable + rastreabilidad Admin).

### Design Opportunities

- UI de productividad desktop-first: tablas densas, filtros rápidos, búsqueda persistente, vistas master-detail/split, acciones rápidas en contexto y navegación por teclado.
- Flujos “mínimo + opcional”: capturar receptor + nota como obligatorio, dejando el resto como opcional sin bloquear operación; reducir clicks y tipeo repetitivo.
- Claridad visual y confianza: usar `03-visual-style-guide.md` como branding (colores CFE, tono profesional) y definir componentes Bootstrap 5 consistentes para estados, badges y feedback.
- Prevención de errores y aprendizaje: microcopy directo, validaciones inline, confirmaciones solo donde agregan valor, estados vacíos útiles y guías ligeras para promover adopción.
- Eficiencia en Tareas Pendientes: progreso por renglón, resumen de errores, finalización parcial clara y reanudación sin confusión.

## Core User Experience

### Defining Experience

El corazón de GATIC es un loop operativo repetible:

1) **Consultar** (en segundos): buscar “¿tenemos X?”, ver disponibilidad/conteos y, si aplica, “¿quién lo tiene?”.
2) **Actuar** (en el mismo contexto): registrar movimiento (prestar/asignar/devolver/retiro) con el mínimo de fricción.
3) **Volver al trabajo**: confirmación clara y regreso automático a búsqueda/lista/detalle para continuar el siguiente caso.

La experiencia debe sentirse más rápida y menos “pesada” que el proceso actual (Excel + memoria). La consulta debe ser el punto de entrada natural; el registro de movimiento debe ser un “siguiente paso” obvio y fácil.

Se considera explícitamente el modelo dual:
- **Serializados**: foco en identificar unidad (serial/asset_tag) y su estado/tenencia actual.
- **Por cantidad**: foco en conteos (total/disponibles/no disponibles) y salidas/entradas con trazabilidad mínima.

### Platform Strategy

- **Plataforma**: aplicación web intranet/on‑prem.
- **Dispositivos**: 100% uso en **PC/laptop** con monitores de resolución grande (desktop-first).
- **Interacción**: mouse + teclado; **atajos de teclado** como aceleradores de productividad.
- **Navegadores**: Edge y Chrome (principalmente).
- **Conectividad**: se asume conectividad interna estable; si hay latencia, la UI debe comunicar progreso/estado sin bloquear al usuario.

### Effortless Interactions

- **Búsqueda siempre lista**: un campo de búsqueda prominente, con foco rápido (atajo) y resultados escaneables.
- **De “consulta” a “acción” sin pasos extra**: desde resultados o detalle, acciones rápidas para prestar/asignar/devolver/retiro (según permisos y estado).
- **Movimiento “mínimo + opcional”**: obligatorio solo lo indispensable (receptor + nota) y el resto como opcional/colapsable para no frenar operación.
- **Autocompletar y defaults**:
  - Autocompletar receptor (Empleados RPE) rápido.
  - Recordar/precargar valores comunes cuando aplique (por confirmar cuáles: p.ej. último receptor, último tipo de movimiento, ubicación por defecto).
- **Teclado-first en flujos repetitivos**:
  - Atajos propuestos (a confirmar): `/` enfoca búsqueda, `Esc` cierra/cancela, `Ctrl+Enter` guarda en formularios, navegación por listas con teclado cuando sea viable.
- **Feedback inmediato y seguro**:
  - Confirmación clara al registrar (qué cambió y estado final).
  - Mensajes accionables ante validaciones (qué corregir, sin “errores genéricos”).
  - Si una operación tarda >3s: skeleton/loader + progreso + opción de cancelar.

### Critical Success Moments

- **Momento “aha”**: el usuario siente que “ya no da hueva” porque logra **consultar y resolver** (consultar + registrar) de forma rápida y sin pensar.
- **Make-or-break (a confirmar, propuesta)**:
  - La búsqueda responde confiable y rápido (si falla o confunde, se rompe la confianza).
  - Registrar un movimiento “en el momento” es fácil (si se vuelve lento o burocrático, la gente no lo usa y vuelve Excel/memoria).
  - No hay ambigüedad de estado/tenencia (el sistema debe contestar “quién lo tiene” de forma clara).
  - En Tareas Pendientes, el lock/concurrencia es entendible (evita doble procesamiento y frustración).

### Experience Principles

- **Velocidad sobre burocracia**: capturar lo mínimo indispensable primero; lo demás opcional.
- **Respuesta operativa inmediata**: el sistema debe contestar rápido y con claridad (disponibilidad/estado/responsable).
- **Acciones contextualizadas**: ofrecer solo lo que aplica según estado/tipo de ítem y rol.
- **Claridad de estado y tenencia**: siempre visible “qué es”, “en qué estado está” y “quién lo tiene” (si aplica).
- **Desktop-first productividad**: densidad, tablas, filtros rápidos y atajos de teclado.
- **Brand-consistent UI**: usar `03-visual-style-guide.md` como referencia de branding (colores/tono) con componentes Bootstrap 5 consistentes.

## Desired Emotional Response

### Primary Emotional Goals

- **Control y confianza**: “sé exactamente qué hay y quién lo tiene”.
- **Rapidez/productividad**: “lo resolví sin esfuerzo”.
- **Alivio**: “ya quedó registrado” (cero duda de si se apuntó o no).

### Emotional Journey Mapping

- **Al entrar**: claridad inmediata (sin ruido) y sensación de “aquí sí está la verdad”.
- **Al consultar**: confianza rápida (respuesta clara, escaneable, sin ambigüedad).
- **Al registrar un movimiento**: flujo y seguridad (pocos pasos, confirmación inequívoca).
- **Si algo falla**: calma (mensaje humano, acción sugerida, y trazabilidad vía ID de error para Admin).
- **Al volver otro día**: familiaridad (misma estructura/atajos) y continuidad (retoma donde lo dejó).

### Micro-Emotions

- **Confianza > confusión**: estados y conteos siempre explicables.
- **Tranquilidad > ansiedad**: el sistema guía y previene errores operativos.
- **Satisfacción > frustración**: tareas repetitivas se sienten ligeras (teclado-first, defaults, menos clicks).
- **Credibilidad > escepticismo**: la UI refuerza que el dato es actual (y cuándo se actualizó) incluso con polling.

### Design Implications

- Para **confianza/control**: estados/badges consistentes, “quién lo tiene” visible, historial accesible, y reglas claras según tipo (serializado vs cantidad).
- Para **rapidez/productividad**: búsqueda siempre enfocable, acciones rápidas en contexto, formularios mínimos + opcional, navegación por teclado y confirmaciones discretas pero claras.
- Para **alivio**: confirmación post-acción que explique “qué cambió” (estado/tenencia/cantidad) y deje al usuario listo para el siguiente caso.
- Para **calma ante errores**: mensajes sin culpa, validaciones accionables, opción de reintentar, y “ID de error” cuando sea inesperado (detalle solo Admin).

### Emotional Design Principles

- **Quitar fricción**: si “da hueva”, no se adopta; el sistema debe sentirse más fácil que no registrar.
- **Mostrar certeza**: “fuente de verdad” se comunica con claridad, consistencia y actualidad del dato.
- **Ser predecible**: mismos patrones, mismos lugares, mismas acciones; reduce carga mental.
- **Fallar con dignidad**: cuando algo salga mal, el usuario se siente acompañado, no castigado.

## UX Pattern Analysis & Inspiration

### Inspiring Products Analysis

**Outlook (patrón “inbox” aplicado a inventario)**
- Lista escaneable con jerarquía clara (títulos, metadatos, estados).
- Vista de detalle rápida (leer/entender sin navegar demasiado).
- Acciones rápidas en contexto (resolver sin salir del flujo).
- Estructura predecible (carpetas/filtros) que reduce carga mental.

**Admin dashboards (arquetipo Shopify/Stripe/Backoffice)**
- Tablas densas y productivas (desktop-first) con filtros fuertes.
- Badges de estado consistentes y “a simple vista” (confianza/control).
- Acciones por fila + acciones masivas (cuando aplica).
- “Vistas guardadas” / filtros persistentes para trabajo repetitivo.

**Command palette / búsqueda tipo VSCode/Windows**
- Foco inmediato a búsqueda (teclado-first) y resultados rápidos.
- Fuzzy search + ranking (encuentra lo correcto sin pensar).
- Acciones rápidas desde resultados (consultar y actuar en el mismo paso).

### Transferable UX Patterns

**Navigation Patterns**
- Layout tipo “inbox”: lista (resultados) + panel de detalle + acciones en contexto.
- Filtros como “views” guardadas: Disponible / Prestado / Asignado / Pendiente / Retirado, etc.
- Breadcrumbs y rutas cortas para no perderse.

**Interaction Patterns**
- Búsqueda global siempre accesible (atajo `/`) + `Esc` para limpiar/cerrar.
- Acciones rápidas por fila/detalle (prestar/asignar/devolver/retiro) según estado y permisos.
- Formularios mínimos (receptor + nota) + campos opcionales colapsables.
- Confirmación post-acción que diga “qué cambió” (estado/tenencia/cantidad) y deje listo para el siguiente caso.
- Teclado-first: `Ctrl+Enter` para guardar, navegación por listas con teclado donde aplique.

**Visual Patterns**
- Badges de estado consistentes (colores/semántica) alineados al branding de `03-visual-style-guide.md`.
- Indicadores de “actualidad del dato” (ej. “actualizado hace X”) para reforzar confianza con polling.
- Mensajes claros y accionables (microcopy directo, sin tecnicismos).

### Anti-Patterns to Avoid

- “Muro de campos” obligatorios (mata adopción: vuelve la experiencia burocrática).
- Modales en cascada / demasiadas confirmaciones (rompe velocidad).
- Estados ambiguos (“no sé si está disponible o quién lo tiene”).
- Búsqueda lenta o sin ranking útil (si no confían en la búsqueda, regresan a Excel/memoria).
- Errores genéricos sin guía (“algo salió mal”) o sin trazabilidad para soporte (ID de error).

### Design Inspiration Strategy

**What to Adopt**
- Layout tipo inbox (lista + detalle + acciones rápidas) para el loop consultar→actuar.
- Búsqueda global siempre disponible + atajos de teclado para productividad.
- Badges/estados consistentes para control y confianza.

**What to Adapt**
- “Vistas guardadas” como filtros predefinidos para Soporte (no como configuración compleja).
- Command palette simplificada (si aporta velocidad sin meter complejidad innecesaria).
- Patrones de confirmación: discretos pero inequívocos (alivio: “ya quedó registrado”).

**What to Avoid**
- Formularios pesados y validaciones que bloquean por detalles no esenciales.
- UX que obligue a navegar pantallas para hacer una acción simple.
- “Near-real-time” confuso: si hay polling, siempre indicar cuándo se actualizó y permitir refresh manual.

## Design System Foundation

### 1.1 Design System Choice

**Bootstrap 5 (Established + Themeable) como base**, con una capa de branding/tokens para CFE/GATIC tomando `03-visual-style-guide.md` como referencia de colores/tono visual (no como catálogo rígido de componentes).

### Rationale for Selection

- **Alineación con el stack** (Blade + Livewire + Bootstrap 5) ya definido en arquitectura.
- **Velocidad y adopción**: componentes probados, menos fricción para implementar y mantener.
- **Consistencia y accesibilidad base**: defaults razonables + patrones conocidos para apps internas.
- **Branding suficiente sin sobrecosto**: se logra identidad CFE/GATIC con tema (variables/tokens) y pocos componentes custom.
- **Mantenibilidad**: menor superficie de CSS custom, menos deuda técnica.

### Implementation Approach

- Usar **Bootstrap 5** como librería de componentes (forms, tables, modals, alerts, toasts, nav, badges).
- Implementar una **capa de UI reutilizable** (Blade partials/Livewire components) para patrones repetidos: búsqueda, tabla con filtros, panel de detalle, formularios mínimos, confirmaciones.
- Estandarizar **estados** (Disponible/Prestado/Asignado/Pendiente/Retirado/etc.) con badges y semántica consistente.
- Adoptar **Bootstrap Icons** (o set equivalente) para acciones frecuentes (buscar, prestar, devolver, editar, eliminar, adjuntar).
- Enfocar en **desktop-first**: densidad controlada, tablas productivas, y soporte de atajos de teclado.

### Customization Strategy

- Definir **design tokens** (colores, estados, tipografía, spacing) basados en `03-visual-style-guide.md`:
  - `--bs-primary` y links con verde CFE; focus ring y estados alineados.
  - Paleta de estados (success/warning/danger/info) consistente para inventario/movimientos.
- Mantener custom CSS **mínimo y sistemático** (variables + utilidades), evitando estilos “por pantalla”.
- Iniciar con **modo claro**; dejar preparado `data-bs-theme="dark"` como opción futura si se necesita.

## 2. Core User Experience

### 2.1 Defining Experience

La experiencia definitoria de GATIC es el “Search → Resolve → Record” en una sola secuencia:

- **Search**: encontrar el ítem correcto en segundos (por nombre, serial o asset_tag).
- **Resolve**: entender de inmediato si está disponible, cuántos hay (si es por cantidad) y/o quién lo tiene (si es serializado).
- **Record**: ejecutar la acción correcta (prestar/asignar/devolver/retiro) con fricción mínima (obligatorio: receptor + nota), dejando el sistema actualizado y confiable.

Si este loop se siente rápido y obvio, la adopción ocurre; si se siente burocrático, el equipo regresará a Excel/memoria.

### 2.2 User Mental Model

**Modelo actual (hoy):**
- “Excel + memoria”: buscar en listas, confiar en conteos aproximados, preguntar o recordar “quién lo tiene”.
- Registrar préstamos “luego” (y muchas veces no ocurre).

**Modelo esperado en GATIC:**
- “Lo tecleo y lo encuentro”: búsqueda como herramienta principal.
- “El sistema me dice la verdad”: disponibilidad/tenencia clara y consistente.
- “Registrar es el paso natural”: no es burocracia, es parte del flujo (mínimo indispensable).

**Riesgos de confusión (a evitar):**
- Ambigüedad entre serializados vs por cantidad.
- Acciones visibles que no aplican al estado actual.
- Confirmaciones/validaciones que frenan por campos no esenciales.

### 2.3 Success Criteria

- Consultas operativas (¿tenemos X?, ¿quién lo tiene?) responden con resultado útil en **<10s** (PRD).
- Registrar movimientos se siente “sin hueva”: mínimo obligatorio **receptor + nota**, el resto opcional.
- El usuario siempre sabe si tuvo éxito: confirmación clara de **qué cambió** (estado/tenencia/cantidad).
- La información post-acción queda coherente y visible (conteos/estado/responsable actualizados).
- “Fuentes de verdad” por adopción: 100% de movimientos en críticos registrados al momento (PRD) y alta cobertura en cantidad (PRD).

### 2.4 Novel UX Patterns

La base usa **patrones establecidos** (backoffice/inbox + búsqueda + acciones en contexto).
Lo “especial” está en la combinación para intranet de Soporte:
- Loop consultar→actuar diseñado para adopción (mínimo obligatorio).
- Señales de actualidad del dato (polling) sin confundir.
- Locks visibles en Tareas Pendientes (para evitar conflictos) con mensajes entendibles.

### 2.5 Experience Mechanics

**1) Initiation**
- Usuario entra y el foco cae en búsqueda (atajo `/` para reenfocar).
- Puede buscar por nombre o identificadores; match exacto por serial/asset_tag debe llevar al activo correcto.

**2) Interaction**
- Resultados escaneables (tipo, estado, disponibilidad/conteos).
- Selección abre detalle (sin perder el contexto de búsqueda).
- Acciones disponibles se muestran según tipo/estado/rol.

**3) Feedback**
- Validación inline accionable.
- Al guardar: toast/alert discreta pero inequívoca + resumen “qué cambió”.
- Si tarda >3s: loader/skeleton + opción de cancelar.
- Si falla inesperado: mensaje humano + ID de error (detalle solo Admin).

**4) Completion**
- Regresa listo para el siguiente caso: búsqueda preservada o limpia (según flujo) y estado actualizado visible.

## Visual Design Foundation

### Color System

**Brand (CFE / GATIC) – fuente:** `03-visual-style-guide.md`

**Tokens base (brand):**
- `--cfe-green-brand`: `#008E5A`
- `--cfe-green-dark`: `#006B47`
- `--cfe-green-very-dark`: `#004D33`
- `--cfe-green-soft`: `#E6F4EF`
- `--cfe-text-on-green`: `#FFFFFF`
- `--cfe-black`: `#111111`

**Decisión de accesibilidad (preventivo):**
- Para cumplir contraste AA en texto sobre “Primary”, usar:
  - `--bs-primary` = `#006B47` (CFE green dark)
  - Reservar `#008E5A` como acento/branding (íconos, bordes, highlights, fondos suaves)
- Links:
  - `--bs-link-color` = `#006B47` (mejor contraste en fondo claro)
  - hover = `#004D33`

**Estados semánticos (inventario/movimientos) – con texto SIEMPRE:**
- Disponible: `bg-success text-white`
- Prestado: `bg-warning text-dark`
- Asignado: `bg-purple text-white` (p.ej. `#6610f2`)
- Pendiente: `bg-orange text-dark` (p.ej. `#fd7e14` + texto oscuro)
- Retirado/Destructivo: `bg-danger text-white`
- Info/ayuda: `bg-light` / `bg-body` + bordes suaves (evitar “gritar” en UI productiva)

**Failure modes → Prevention**
- Contraste insuficiente (botones/links/badges) → fijar `--bs-primary` en `#006B47` y usar `text-dark` en warning/orange.
- “Color-only states” → siempre badge con texto + (opcional) ícono.
- Semántica inconsistente entre pantallas → tabla única de estados (mismos colores/labels en todo el sistema).

### Typography System

**Fuente:** Noto Sans (recomendada) con fallback a sistema.
- Default: `system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif`
- Noto Sans: usar solo si se puede **autohospedar** (on‑prem); evitar depender de CDN.

**Jerarquía (desktop-first):**
- Título de pantalla: moderado (evitar “gigante”): `h2/h3` o `.fs-3/.fs-4`
- Metadata/ayuda: `.small` y `.text-muted` con contraste suficiente

**Failure modes → Prevention**
- Fuente no disponible (CDN bloqueado) → fallback robusto o fuente local.
- Texto pequeño sin contraste → mínimo AA + evitar “muted” excesivo en datos críticos.

### Spacing & Layout Foundation

**Principio:** productividad desktop-first con densidad controlada (rápido de escanear, no apretado).

**Reglas:**
- Espaciado: escala Bootstrap (4px); usar 8/12/16px entre bloques.
- Formularios: `g-2` (compacto) o `g-3` (default); labels siempre visibles.
- Tablas/listas: `table-sm` cuando sea flujo repetitivo; acciones con targets clicables suficientes (no mini‑íconos imposibles).
- Layout recomendado: patrón “inbox/backoffice” (lista + detalle + acciones en contexto).

**Failure modes → Prevention**
- UI demasiado densa → preset “default vs compact” (decidir por módulo) y mantener consistencia.
- Acciones difíciles de clicar → tamaños mínimos razonables y separación de íconos/acciones.

### Accessibility Considerations

- Contraste WCAG AA: ≥ 4.5:1 en texto normal (y ≥ 3:1 en componentes UI/bordes relevantes).
- Focus visible consistente: ring verde CFE (alpha) en inputs/buttons.
- No depender solo del color: estado = texto + (opcional) ícono.
- Feedback:
  - Success: toast discreto.
  - Error: mensaje persistente + guía accionable; fallos inesperados con **ID de error** (detalle solo Admin).

**Failure modes → Prevention**
- Usuario “no confía” (polling) → mostrar “actualizado hace X” + refresh manual.
- Errores genéricos → microcopy accionable + ID de error en excepciones.


## Design Direction Decision

### Design Directions Explored

Baseline real (prototipo actual): `http://localhost:3000/inventario`
- Layout: sidebar + header, toolbar con busqueda/filtros, tabla agrupada con expansion.
- Patrones ya presentes: `Columnas` (menu de columnas), `Filtros Avanzados` (drawer), `Anadir Producto` (modal con tabs), modal de atajos.
- Evidencia visual: `_bmad-output/ux-design-directions.html` + `_bmad-output/ux-snapshots/*`

Direcciones exploradas (variaciones sobre la base):
1) Baseline + Pulido Operativo
2) Table-first Compact (operador)
3) Master-Detail Drawer (menos clicks)
4) Command Palette / Keyboard-first
5) Movement-first Wizard (registrar rapido)
6) Trust & Audit (confianza + trazabilidad)
7) CFE Bold Brand (high contrast accesible)
8) Calm Enterprise (neutral / comodo)

### Chosen Direction

**Direccion elegida:** Baseline + Pulido Operativo (replicar tu UI actual como base, pero mejorada para operacion diaria).

Mantener (estructura/patrones):
- Sidebar + header como estan.
- Toolbar: busqueda principal + filtros rapidos (Categoria/Marca/Estado) + `Columnas` + `Filtros Avanzados`.
- Inventario como tabla agrupada (Producto) con expansion (unidades serializadas cuando aplique).
- Drawer para filtros avanzados y modal para alta (`Anadir Producto`).

Mejorar (pulido "operator grade"):
- Acciones en contexto: habilitar `Acciones de Stock` y convertirlo en el "siguiente paso" natural: **Registrar movimiento** desde la tabla/detalle con flujo minimo (obligatorio: Receptor + Nota).
- Reducir friccion: defaults inteligentes + "Registrar otro" (mantener contexto y receptor reciente).
- Densidad controlada: modo `Compacto` para operacion diaria + presets de columnas (Default/Operador/Auditoria) persistentes por usuario.
- Estados semanticos consistentes: badges con texto (no solo color) para Disponible/Prestado/Asignado/Pendiente/Retirado; conteos claros para stock.
- Keyboard-first real: `/` enfoca busqueda, `Ctrl+K` comandos/acciones, navegacion de tabla por teclado, guardado (`Ctrl+S`) y cierre (`Esc`).
- Confianza del dato: "Actualizado hace X" + refresh manual + feedback de polling discreto (sin distraer).

Decision de contenedor para "Registrar movimiento":
- Preferir drawer derecho (no pierde el contexto de tabla/filtros). Modal se reserva para alta/edicion (ej. `Anadir Producto`).

### Design Rationale

- Adopcion-first: registrar debe ser mas facil que no registrar ("da hueva" => se elimina friccion).
- Desktop-first (Edge/Chrome): densidad, atajos y flujos repetibles son la prioridad.
- Mantener un layout familiar baja el costo de aprendizaje; el pulido ataca puntos de dolor (acciones deshabilitadas, estados poco claros, demasiados clicks).
- Branding CFE sin romper accesibilidad: primary recomendado `#006B47` (AA) y `#008E5A` solo como acento/branding.

### Implementation Approach

- Reusar patrones existentes del prototipo:
  - Drawer para detalle/movimiento (mismo patron que `Filtros Avanzados`).
  - Menu de acciones por fila con habilitacion por estado/rol y microcopy accionable.
- Estandarizar componentes clave:
  - Data table con modo compacto + columnas persistentes + expansion Producto -> unidades.
  - Quick movement (2 campos obligatorios) con defaults y "Registrar otro".
  - Sistema de estados/badges centralizado (mismos labels/colores en Inventario/Prestamos/Asignados/Historial).
  - Indicadores de "freshness" (polling) y errores con `error_id`.
- Guardrails:
  - No "color-only states"; contraste AA; focus visible consistente.
  - Operaciones lentas (>3s): skeleton/progreso + opcion de cancelar.

## User Journey Flows

### Journey 1 - Editor (Soporte): Consultar inventario + registrar movimiento

Objetivo: responder "tenemos X?" en <10s y registrar el movimiento con friccion minima (obligatorio: Receptor + Nota), sin perder el contexto de la tabla.

```mermaid
flowchart TD
  A[Inicio: Usuario entra a Inventario] --> B{Rol}
  B -->|Lector| B1[Busca y consulta (solo lectura)] --> B2[Ve detalle/estado y conteos] --> Z[Fin]
  B -->|Editor/Admin| C[Enfoca busqueda (/)] --> D[Escribe nombre/serial/asset_tag]
  D --> E{Resultados?}
  E -->|No| E1[Empty state: sugerencias + limpiar filtros] --> D
  E -->|Si| F[Ve tabla agrupada + conteos]
  F --> G[Selecciona producto / expande unidades]
  G --> H{Tipo de item}
  H -->|Stock (cantidad)| I[Accion: Asignar/Prestar/Retiro desde Stock]
  H -->|Serializado| J[Selecciona unidad (serial/asset_tag)] --> K[Accion: Asignar/Prestar/Devolver/Retiro]
  I --> L[Abre drawer "Registrar movimiento"]
  K --> L
  L --> M[Captura Receptor (RPE) + Nota (obligatorios)]
  M --> N{Campos validos?}
  N -->|No| N1[Validacion inline + foco al campo] --> M
  N -->|Si| O[Guardar (Ctrl+S)]
  O --> P{Respuesta servidor}
  P -->|OK| Q[Toast + resumen de cambio] --> R[Actualizar tabla/estado + "Actualizado hace X"] --> S{Registrar otro?}
  S -->|Si| D
  S -->|No| Z
  P -->|Error esperado| P1[Mensaje accionable + opcion reintentar] --> O
  P -->|Error inesperado| P2[Mensaje humano + error_id + refresh] --> Z
```

### Journey 2 - Editor (Soporte): Tarea Pendiente con lock (concurrencia)

Objetivo: evitar doble procesamiento con lock visible, heartbeat, expiracion, modo read-only para terceros y camino claro para desbloqueo.

```mermaid
flowchart TD
  A[Inicio: Abrir Tareas Pendientes] --> B[Selecciona tarea]
  B --> C[Click "Procesar"]
  C --> D{Lock disponible?}

  D -->|Si| E[Adquiere claim + inicia heartbeat (~10s)] --> F[Modo edicion por renglon]
  F --> G[Validar/editar renglon] --> H{Renglon OK?}
  H -->|Si| I[Marcar listo] --> J{Mas renglones?}
  H -->|No| H1[Marcar error + mensaje accionable] --> J
  J -->|Si| G
  J -->|No| K[Finalizar (aplicar parcial)]
  K --> L{Transaccion OK?}
  L -->|OK| M[Resumen: aplicados vs errores] --> N[Libera lock] --> Z[Fin]
  L -->|Error| L1[Mostrar error + mantener estado por renglon] --> F

  D -->|No| R[Modo solo lectura: muestra quien/desde cuando] --> R1{Opciones}
  R1 -->|Esperar/Refresh| C
  R1 -->|Solicitar liberacion| R2[Mensaje informativo] --> C
  R1 -->|Pedir Admin| R3[Escalar unlock/force-claim] --> C

  E --> X{Lock perdido? (TTL/override/idle)}
  X -->|Si| X1[Banner: lock perdido -> read-only + reintentar claim] --> C
  X -->|No| F
```

### Journey 3 - Admin: Gobernanza + excepciones (locks + error_id)

Objetivo: mantener operabilidad: resolver bloqueos y diagnosticar fallos sin frenar la operacion diaria.

```mermaid
flowchart TD
  A[Inicio: Admin entra a panel] --> B{Tipo de excepcion}

  B -->|Lock bloqueando tarea| C[Ver lista de tareas bloqueadas]
  C --> D[Abre tarea + ve lock (usuario, desde cuando, TTL)]
  D --> E{Accion}
  E -->|Liberar lock| F[Confirmar -> auditar accion] --> G[Lock liberado] --> Z[Fin]
  E -->|Force-claim| H[Confirmar -> auditar accion] --> I[Admin toma control / reasigna] --> Z

  B -->|Investigar error_id| J[Ir a errores/soporte]
  J --> K[Ingresar error_id]
  K --> L{Existe?}
  L -->|No| L1[Mensaje: no encontrado / expiro] --> Z
  L -->|Si| M[Ver detalle: endpoint, usuario, timestamp, contexto]
  M --> N[Compartir con dev / marcar seguimiento] --> Z
```

### Journey Patterns

- Navigation patterns:
  - Sidebar consistente + entry point Inventario como home operativo.
  - Drawers para flujos que no deben perder contexto (detalle, registrar movimiento).
- Decision patterns:
  - Serializado vs stock.
  - Rol (Editor/Admin/Lector).
  - Lock disponible vs bloqueado.
- Feedback patterns:
  - Toast con "que cambio" + estados/badges consistentes.
  - "Actualizado hace X" + refresh manual.
- Error patterns:
  - Validacion inline (foco al campo).
  - Errores inesperados con `error_id`.
  - Reintento seguro; el estado del usuario no se pierde.

### Flow Optimization Principles

- Minimizar pasos a valor: movimiento en 1 drawer, 2 campos obligatorios (Receptor + Nota).
- Desktop-first: modo compacto, atajos (/, Ctrl+K, Ctrl+S, Esc) y navegacion por teclado en tabla.
- Progressive disclosure: campos raros (proveedor/contrato/costo) en avanzado, no en el happy path.
- Confianza: polling discreto + timestamps; locks visibles con TTL; mensajes accionables.

## Component Strategy

### Design System Components

**Design system:** Bootstrap 5 + tokens CFE (primary recomendado `#006B47`, `#008E5A` solo acento) + focus ring consistente.

**Foundation components (Bootstrap 5):**
- Layout: grid, containers, utilities (spacing, display, flex), typography.
- Navegacion: nav, breadcrumbs, sidebar (nav vertical), dropdowns.
- Formularios: input, select, textarea, input-group, validation, switch/checkbox/radio.
- Data display: tables (`table`, `table-sm`), badges, alerts.
- Overlays: modal, offcanvas (drawer), dropdown menu, tooltips/popovers.
- Feedback: toasts, spinners/progress.
- Estructura: cards, accordions/collapse, tabs.
- Pagination: pagination + page size selector.

### Custom Components

### AppShell (Sidebar + Header + Breadcrumbs)
**Purpose:** layout consistente desktop-first.
**Usage:** wrapper de todas las pantallas.
**Anatomy:** sidebar (nav + active state), header (titulo, acciones globales, user menu, theme toggle), breadcrumb.
**States:** normal, sidebar colapsado, loading session.
**Variants:** normal/compact sidebar.
**Accessibility:** landmarks (`nav`, `header`, `main`), foco visible, skip-to-content.
**Interaction Behavior:** atajo para colapsar sidebar (Ctrl+B).

### InventoryToolbar (Search + Quick Filters + Actions)
**Purpose:** punto de entrada "consultar en <10s".
**Usage:** arriba de la tabla en `/inventario`.
**Anatomy:** SearchInput (con icono), quick filters (Categoria/Marca/Estado), ColumnManager, AdvancedFilterDrawer trigger, CTA (Anadir Producto).
**States:** idle, typing, filters active, loading (polling).
**Variants:** Default vs Compact mode toggle.
**Accessibility:** label/aria para search, keyboard shortcuts hint.
**Interaction Behavior:** `/` enfoca busqueda; filtros no deben perder el foco/scroll.

### ColumnManager (Columnas + Presets)
**Purpose:** controlar densidad y "modo operador".
**Usage:** menu `Columnas` + presets (Default/Operador/Auditoria).
**Anatomy:** dropdown con checklist + acciones (Reset, Guardar preset).
**States:** open/closed, checked/unchecked, disabled (columna obligatoria).
**Variants:** presets por rol (Lector vs Editor).
**Accessibility:** `menuitemcheckbox`, soporte teclado (↑↓, Space).
**Interaction Behavior:** persistir preferencia por usuario.

### InventoryDataTable (Grouped + Expandable)
**Purpose:** responder "tenemos X?" y habilitar acciones en contexto.
**Usage:** lista principal inventario (producto agrupado + expansion a unidades/activos).
**Anatomy:** header sortable, rows grouped, expand control, cells (estado/conteos), RowActionsMenu, pagination.
**States:** loading (skeleton), empty, filtered, error (load fail), row expanded/collapsed, row selected.
**Variants:** Compact vs Default density; columnas segun preset.
**Accessibility:** `table` semantics, focus management en filas, shortcuts no interfieren con inputs.
**Content Guidelines:** estado siempre con texto + (opcional) icono; conteos Total/Disp/NoDisp claros.
**Interaction Behavior:** sort, expand, row actions; navegacion por teclado en tabla.

### RowActionsMenu (Acciones de Stock / Unidad)
**Purpose:** "siguiente paso" para registrar movimiento.
**Usage:** en cada row (producto) y/o unidad (serializado).
**Anatomy:** dropdown menu con acciones habilitadas segun estado/rol.
**States:** enabled/disabled por regla, loading on submit.
**Variants:** stock vs serializado (acciones cambian).
**Accessibility:** menu keyboard nav, labels claros.
**Interaction Behavior:** una accion abre MovementDrawer con defaults.

### MovementDrawer (Registrar movimiento)
**Purpose:** reducir friccion para registrar (adopcion-first).
**Usage:** desde RowActionsMenu o detalle (drawer derecho).
**Anatomy:** titulo + resumen del item, campos obligatorios (Receptor + Nota), opcionales colapsados, CTA Guardar, "Registrar otro".
**States:** pristine, validating, saving, success toast, error (expected/unexpected con `error_id`).
**Variants:** stock (cantidad) vs serializado (seleccion unidad).
**Accessibility:** focus trap, Esc cierra, Ctrl+S guarda.
**Interaction Behavior:** no perder contexto de tabla; al guardar refresca estado + "Actualizado hace X".

### ComboboxAsync (Marca/Proveedor/RPE)
**Purpose:** seleccionar rapido sin escribir exacto.
**Usage:** formularios (Anadir Producto, MovementDrawer).
**Anatomy:** input + lista resultados + opcion "crear rapido" (si aplica).
**States:** loading, no results, selected.
**Variants:** single vs creatable.
**Accessibility:** ARIA combobox/listbox, teclado (↑↓, Enter).
**Interaction Behavior:** debounce, conserva seleccion reciente.

### AdvancedFilterDrawer
**Purpose:** filtros detallados sin perder tabla.
**Usage:** drawer derecho tipo "Filtros Avanzados".
**Anatomy:** campos (rango fechas, proveedor, contrato, costo min/max, serial-only), botones limpiar/aplicar.
**States:** open/closed, dirty, applying.
**Accessibility:** focus trap, labels claros.
**Interaction Behavior:** aplicar sin resetear scroll/seleccion.

### FreshnessIndicator (Polling + "Actualizado hace X")
**Purpose:** confianza del dato sin distraer.
**Usage:** toolbar + tabla/detalle.
**States:** synced, syncing, stale, error.
**Accessibility:** no solo color; texto + icono.
**Interaction Behavior:** refresh manual; polling visible/condicional.

### LockBanner (Pending Tasks)
**Purpose:** hacer lock obvio y accionable.
**Usage:** cabecera de tarea y modo read-only.
**States:** locked-by-me, locked-by-other, expired, force-released.
**Accessibility:** alert region, texto claro.
**Interaction Behavior:** acciones: reintentar claim, solicitar liberacion, info TTL.

### ShortcutHelpModal + ShortcutHints
**Purpose:** convertir atajos en "momento aha".
**Usage:** modal de atajos + hints debajo de search.
**States:** open/closed.
**Accessibility:** focus trap, Esc cierra.
**Interaction Behavior:** no dispara atajos cuando el foco esta en inputs.

### ErrorAlertWithId
**Purpose:** soporte/operabilidad.
**Usage:** errores inesperados (MovementDrawer, tareas, etc).
**Anatomy:** mensaje humano + `error_id` + "Copiar".
**Accessibility:** `role=alert`, copy accesible.
**Interaction Behavior:** reintento cuando aplica.

### Component Implementation Strategy

- Base: Bootstrap 5 + variables CSS CFE (tokens de Step 8).
- Interaccion:
  - Blade components para presentacion (AppShell, badges, toolbar pieces).
  - Livewire components para estado/acciones (InventoryDataTable, drawers, combobox async, locks).
  - JS minimo solo donde se necesita (command palette + layer de atajos), sin romper navegacion normal.
- Consistencia: estados/badges y reglas de habilitacion centralizadas (por rol/estado/tipo).
- Accesibilidad: foco visible, ARIA en combobox/menu, no depender solo de color.

### Implementation Roadmap

**Phase 1 - Core (MVP loop Consultar -> Registrar):**
- AppShell, InventoryToolbar, InventoryDataTable (baseline), StatusBadge/Qty indicators
- RowActionsMenu + MovementDrawer (2 campos obligatorios) + Toasts
- ComboboxAsync (RPE) + ErrorAlertWithId

**Phase 2 - Operacion diaria (speed + control):**
- Compact mode + ColumnManager con presets persistentes
- AdvancedFilterDrawer refinado + FreshnessIndicator ("Actualizado hace X")
- ShortcutHelpModal + hints + navegacion teclado en tabla

**Phase 3 - Enhancements:**
- Command palette (Ctrl+K) con acciones globales
- AuditTimeline/Kardex en drawer de detalle
- AttachmentUploader (cuando aplique) + mejoras bulk

## UX Consistency Patterns

### Button Hierarchy

**When to Use:** en todas las pantallas y overlays.

**Visual Design:**
- Primary: 1 por vista/overlay (ej. `Guardar Cambios`, `Aplicar Filtros`, `Anadir Producto`). Color primary `#006B47`.
- Secondary: acciones no destructivas/alternas (ej. `Cancelar`, `Limpiar`, `Columnas`, `Filtros Avanzados`).
- Tertiary: links/icon buttons (ej. `Ver atajos`, `Copiar`, `Abrir menu`).
- Destructive: rojo solo para acciones irreversibles (Retiro definitivo, Purga papelera).

**Behavior:**
- Primary siempre visible en el footer del drawer/modal (alineado derecha) y soporta `Ctrl+S` cuando aplique.
- `Esc` siempre cierra overlays (si no hay riesgo de perder datos, o con confirm).
- Disabled siempre explica el por que (tooltip o texto auxiliar): no solo “gris”.

**Accessibility:**
- Focus visible (ring verde alpha) en todos los botones.
- Icon-only debe tener `aria-label` y tooltip.

**Variants:**
- Default vs Compact (misma jerarquia, cambia padding/typography).

### Feedback Patterns

**Success (OK):**
- Toast discreto (bottom-right) con “que cambio” (ej. “Prestamo registrado: Laptop Dell -> Juan Perez”).
- Si refresca datos: actualizar `Actualizado hace X` y mantener contexto (busqueda/filtros).

**Validation (Error esperado):**
- Inline en campo + mensaje accionable (que hacer).
- Foco automatico al primer error al submit.

**Error inesperado:**
- Mensaje humano + `error_id` (copiable) + opcion “Reintentar” cuando sea seguro.

**Warning/Info:**
- Banner no bloqueante para “dato stale”, “lock perdido”, “sin permisos”.
- Nunca depender solo de color: icono + texto.

**Loading:**
- Si tarda >300ms: skeleton/placeholder.
- Si tarda >3s: mensaje de progreso + opcion cancelar.

### Form Patterns

**Regla MVP (adopcion-first):**
- Para “Registrar movimiento”: solo 2 obligatorios (Receptor + Nota). Todo lo demas opcional y colapsado.

**Defaults:**
- Fechas a hoy, cantidad=1, recordar ultimo receptor cuando el usuario elija “Registrar otro”.

**Combobox/Typeahead:**
- Para RPE/Marca/Proveedor: async combobox con debounce, teclado (↑↓ Enter), y “No results” claro.

**Submit:**
- Deshabilitar submit mientras guarda + spinner.
- En exito: cerrar drawer o limpiar segun el flujo (“Registrar otro” mantiene drawer y enfoca receptor).

**Multi-step:**
- `Anadir Producto` puede ser tabs (Basica/Tecnica/Docs).
- Movimiento NO debe ser multi-tab: una sola pantalla.

### Navigation Patterns

**Shell:**
- Sidebar fija (desktop-first) con activo claro + opcion colapsar.
- Header con titulo de pagina y acciones globales (atajos, usuario).

**Breadcrumbs:**
- Siempre presentes para contexto, no como navegacion primaria.

**Context preservation:**
- Volver a Inventario mantiene: busqueda, filtros, preset de columnas, modo compacto, scroll y fila expandida (si posible).

### Additional Patterns

#### Search & Filtering
- `/` enfoca busqueda local del modulo.
- `Ctrl+K` abre busqueda/command palette global.
- Busqueda por serial/asset_tag: match exacto debe priorizar unidad/activo.
- Quick filters (Categoria/Marca/Estado) + Advanced filters en drawer.
- Chips de filtros activos siempre visibles + accion `Limpiar`.

#### Tables & Lists (Inventario baseline)
- Tabla agrupada (Producto) con expansion a unidades.
- Densidad: Default vs Compact (toggle).
- Columnas: presets (Default/Operador/Auditoria) persistentes por usuario.
- Sorting claro (icon + orden), header sticky cuando aplique.
- Row actions consistentes: menu “Acciones” con habilitacion por estado/rol y microcopy de por que esta disabled.

#### Modals, Drawers & Menus
- Drawer derecho: tareas en contexto (Detalle / Registrar movimiento / Filtros avanzados).
- Modal: altas/ediciones “grandes” (Anadir Producto), confirmaciones destructivas.
- Menus (dropdown): acciones por fila; cerrar con click afuera o `Esc`.
- Focus trap en modal/drawer, y devolver foco al elemento que abrio.

#### Loading & Empty States
- “No results” (hay data, pero filtros/busqueda no encuentran): sugerir limpiar filtros + ejemplos de busqueda.
- “No data yet” (sistema vacio): CTA `Anadir Producto` + explicacion corta.
- Skeleton para tabla y para drawers cuando cargan.

#### Polling & Freshness
- Mostrar “Actualizado hace X” + boton refresh manual.
- Polling discreto (no spamear toasts).
- Si stale: banner “Datos pueden estar desactualizados” + refresh.

#### Locks & Concurrency
- Si lock es tuyo: mostrar estado + heartbeat activo.
- Si lock es de otro: read-only + “quien y desde cuando” + acciones (esperar/solicitar/admin).
- Si lock se pierde: banner + volver a modo seguro (read-only) y reintentar claim.

#### Keyboard Shortcuts
- Atajos globales consistentes: `/`, `Ctrl+K`, `Ctrl+S`, `Esc`, `Ctrl+Shift+I` (Inventario), etc.
- Atajos no deben dispararse cuando el foco esta en inputs/textarea (excepto `Esc`).
- Hints visibles cerca de busqueda (“/ para buscar • Ctrl+K comandos”).

## Responsive Design & Accessibility

### Responsive Strategy

**Desktop (prioridad / operador):**
- Layout completo: sidebar fija + header + breadcrumb + toolbar + tabla agrupada.
- Densidad: Default vs Compact (toggle) + presets de columnas persistentes (Default/Operador/Auditoría).
- Overlays: drawer derecho para flujos en contexto (Detalle / Registrar movimiento / Filtros avanzados).
- Soporte teclado: `/` (buscar), `Ctrl+K` (comandos), `Ctrl+S` (guardar), `Esc` (cerrar), navegación de tabla por teclado.

**Tablet (soportado, no primario):**
- Sidebar colapsa a offcanvas (hamburger).
- Reducir columnas visibles por default; priorizar “Nombre, Categoría, Estado/Disponibilidad, Acciones”.
- Targets táctiles >= 44x44px; Compact mode desactivado por default.

**Mobile (funcional, no optimizado para MVP):**
- Navegación: sidebar -> offcanvas.
- Tabla -> lista/cards (campos mínimos) + detalle en drawer/pantalla.
- Acciones disponibles pero simplificadas; ocultar columnas avanzadas (ColumnManager sigue existiendo pero con presets mínimos).

### Breakpoint Strategy

**Base:** breakpoints estándar de Bootstrap 5.
- `xs <576`: layout colapsado, lista/cards.
- `sm >=576`: lista densa, acciones accesibles.
- `md >=768`: tabla simplificada + sidebar offcanvas.
- `lg >=992`: layout completo “operator”.
- `xl >=1200` / `xxl >=1400`: más real estate para densidad/paneles.

**Tamaños clave a validar (Windows):**
- 1366x768 (laptop común), 1440x900 (laptop), 1920x1080 (monitor oficina).
- Escalado 100% y 125% (tipografía/targets).

### Accessibility Strategy

**Nivel objetivo:** WCAG **AA**.

**Color/Contraste:**
- Primary recomendado `#006B47` con texto blanco.
- `#008E5A` solo como acento (bordes/iconos/highlights), no como fondo primario con texto blanco.
- Estados: siempre texto + (opcional) icono; nunca “solo color”.

**Keyboard & Focus:**
- Todo operable por teclado (tab order lógico).
- Focus visible consistente (ring verde alpha).
- Skip-to-content en AppShell.
- Shortcuts no disparan dentro de inputs/textarea (excepto `Esc`).

**Screen reader / Semantics:**
- HTML semántico (landmarks, headings jerárquicos).
- Tablas con `caption`/headers claros; menús con roles correctos; combobox ARIA.
- Toasts/alerts con `aria-live` (sin spam).

**Touch targets (cuando aplique):**
- 44x44px en tablet/touch; en desktop mantener >=32px pero sin “iconos mini”.

**Motion:**
- Respetar `prefers-reduced-motion` (evitar animaciones fuertes).

### Testing Strategy

**Responsive:**
- Chrome + Edge (prioridad) con DevTools + validación en resoluciones clave.
- Verificar overflow horizontal en tablas (muchas columnas) y comportamiento de drawer/offcanvas.

**Accessibility:**
- Automated: Lighthouse + axe.
- Manual: keyboard-only (tab/shift-tab/enter/esc), contraste AA, focus order.
- Screen reader (Windows): NVDA (mínimo smoke test en flujos críticos).

**User testing (operativo):**
- Medir: tiempo “consultar” (<10s) y “registrar movimiento” (<25s) en casos reales.

### Implementation Guidelines

- Unidades relativas (`rem`, `%`) y utilidades Bootstrap; evitar heights fijos.
- Tablas: permitir scroll horizontal cuando haya muchas columnas; header sticky cuando aplique.
- Drawer/offcanvas/modal: focus trap + devolver foco al trigger; `Esc` cierra.
- Mensajes: validación inline accionable; errores inesperados con `error_id` copiable.
- Dark mode: asegurar AA también en modo oscuro (tokens + estados).
