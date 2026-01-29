# GATIC - QA + UX Planeacion (Hallazgos + Propuesta + Plan de Pruebas)

Fecha: 2026-01-28
Ambiente: Docker Compose (gatic/compose.yaml)
URL local: http://localhost:8080 (APP_PORT=8080)

Roles seed:
- Admin: admin@gatic.local / password
- Editor: editor@gatic.local / password
- Lector: lector@gatic.local / password

Objetivo
- Detectar errores visuales y mejoras UI/UX "drasticas" enfocadas en operacion diaria (inventario/activos).
- Dejar un plan accionable (diseno + implementacion) con validaciones y escenarios de prueba.

Alcance revisado (E2E manual)
- Login (Admin/Editor/Lector)
- Inventario -> Productos -> Detalle de producto -> Activos
- Detalle de activo (secciones: Tenencia, Notas, Adjuntos)
- Acciones: asignar / desasignar (nota requerida)
- Busqueda unificada (nombre vs serial/asset_tag exacto)
- Filtros (categoria/marca/ubicacion/estado)
- Tareas Pendientes (sin datos; se detalla plan de prueba para cuando existan)

No alcanzado por falta de data
- Validacion de Locks/claim/heartbeat en Tareas Pendientes (no habia tareas creadas).

---

## Roadmap por fases (3 fases)

Nota: Las fases estan ordenadas por impacto operativo y riesgo (primero: friccion y acciones criticas).

### Fase 1 (P0) - Operacion diaria: acciones + formularios (Asignar/Desasignar)

Objetivo:
- Quitar friccion y ambiguedad en acciones de inventario serializado.

Incluye:
- Formularios Asignar/Desasignar:
  - Seleccion de empleado visible y persistente (pill/tarjeta con RPE + nombre + depto).
  - Errores inline accionables y que se limpian al corregir el campo.
  - Focus al primer error en submit; submit con loading/disabled (anti doble submit).
- Listado de activos (columna Acciones):
  - Evitar icon-only como accion principal; labels claros.
  - Targets tactiles >= 44x44 px.
  - Coherencia RBAC visual: Lector solo "Ver" (sin acciones operativas).

Salida / DoD:
- Asignar/Desasignar se sienten "a prueba de errores" y no deja estados ambiguos.
- Se agregan pruebas minimas de regresion para asignacion/desasignacion y RBAC.

### Fase 2 (P0) - Consistencia visual: estados + jerarquia en detalles

Objetivo:
- Mejorar lecturabilidad y velocidad de decision (estado + CTA + KPIs).

Incluye:
- Sistema de chips/badges consistente por estado (Disponible/Asignado/Prestado/Pendiente de Retiro/Retirado).
- Header unificado en detalle de Producto/Activo (titulo + estado + KPIs + CTA principal).

Salida / DoD:
- El mismo estado se ve igual en todas las pantallas.
- En 3 segundos un usuario ubica entidad, estado y accion principal.

### Fase 3 (P1) - QA velocity + Tareas Pendientes/Locks

Objetivo:
- Hacer QA reproducible (seeders demo) y validar el flujo de Locks.

Incluye:
- Seeders demo robustos (catalogos/inventario/empleados + activos en varios estados).
- Crear data/fixtures para Tareas Pendientes y ejecutar pruebas de locks (claim/ttl/heartbeat/override).

Salida / DoD:
- migrate:fresh --seed deja la app lista para el recorrido sin data manual.
- Locks con señales visuales claras y pruebas de concurrencia (2 editores + admin override).

---

## 0) Setup reproducible (incluye data minima)

### Comandos (Sail/Compose)
1) docker compose -f gatic/compose.yaml up -d
2) docker compose -f gatic/compose.yaml exec -T laravel.test php artisan migrate:fresh --seed

### Gap detectado en seeders
- El seed crea usuarios/roles, pero NO genero catalogos/inventario (Category/Brand/Location/Product/Asset/Employee quedaron en 0).
- Impacto: no se puede recorrer UI de inventario sin crear data manual; QA se vuelve mas lento e inconsistente.

### Data minima creada (para desbloquear el recorrido)
Requerida por el flujo:
- 1 categoria serializada (is_serialized=true, requires_asset_tag=true)
- 1 marca
- 1 ubicacion
- 1 producto serializado
- 2 activos
- 1 empleado (RPE)
- Estado garantizado:
  - 1 activo en estado Asignado con empleado
  - 1 activo en estado Disponible

Nota: esta data deberia vivir en seeders para QA/Dev (sugerencia en seccion 2).

---

## 1) Hallazgos UI/UX (priorizados)

### Resumen rapido (5 puntos)
- Densidad baja: pantallas con demasiado "aire" sin informacion, especialmente listados.
- Jerarquia confusa en detalle (Producto/Activo): acciones y datos clave no compiten bien por atencion.
- Acciones por fila poco descubribles (icon-only + targets chicos) para operaciones criticas.
- Inconsistencia visual/semantica de estados (mismo estado con colores distintos segun contexto).
- Formularios: feedback inconsistente (errores que no se limpian y seleccion poco visible).

### Tabla de issues (con evidencia)

| ID | Severidad | Vista/URL | Rol | Hallazgo | Impacto | Evidencia |
|---|---|---|---|---|---|---|
| UI-01 | MED | /inventory/products | Lector | Exceso de whitespace: tabla ocupa franja, sobra espacio "muerto" | Menor eficiencia de escaneo; sensacion de app vacia | ui-issue-01-empty-whitespace.png |
| UI-02 | MED | /inventory/products/1 | Admin/Editor | Jerarquia debil: KPIs parecen campos, CTAs dispersas (Volver/Activos) | Mas tiempo para entender y actuar | ui-issue-02-product-detail-density.png |
| UI-03 | HIGH | /inventory/products/1/assets | Admin/Editor | Acciones por fila ambiguas: boton icon-only + acciones sueltas; targets pequenos | Riesgo misclick; baja descubribilidad de "Asignar/Desasignar" | ui-issue-03-assets-list-actions.png |
| UI-04 | MED | /inventory/products/1/assets/1 | Admin/Editor | Estado "Asignado" se ve con estilos/colores distintos en Estado vs Tenencia | Color deja de ser semantico; confunde | ui-issue-04-asset-detail-actions.png |
| UI-05 | HIGH | /inventory/products/1/assets/2/assign | Admin | Seleccion de empleado no es evidente + error no se limpia tras corregir | Se percibe roto; baja confianza, friccion alta | ui-issue-07-error-not-cleared.png |

Notas adicionales (menores, pero repetibles)
- Microcopy inconsistente (ej: "Ubicacion" vs "Ubicacion/Ubiacion" en algunas vistas). Recomendar unificar "Ubicacion" con "Ubicacion" / "Ubicacion" segun estandar de copy.
- Navegacion: varios links en Playwright tardan en reflejar navegacion (no necesariamente issue de usuario, pero sugiere revisar eventos/navegacion y feedback de carga).

---

## 2) Propuesta de mejoras (drasticas) + criterios de aceptacion

Principios a aplicar (UI/UX Pro Max + Web Interface Guidelines)
- Accesibilidad: foco visible, labels/aria para icon-only, contraste minimo, navegacion teclado.
- Interaccion: targets >= 44x44 px, feedback inmediato en acciones async, estados no solo por color.
- Layout: densidad intencional (evitar "layout fatigue"), contenido al 100% del ancho util.
- Consistencia: mismo estado = mismo chip/badge en todo el sistema; mismo tipo de accion = mismo patron.

### Mejora 1 (P0) - "Listados operativos" (densidad + estructura)
Problema que ataca: UI-01
Accion concreta:
- Convertir listados (Productos/Activos) a "full-width" real: contenedor mas ancho, tabla con mejor uso del espacio, y/o panel lateral de filtros.
- Filtros en una barra superior sticky (o lateral) para que la tabla sea el protagonista.
- Paginacion/contador arriba (si aplica) y estado vacio con CTA contextual.
Validaciones / Aceptacion:
- No hay area vacia dominante sin intencion (ej: 50%+ de viewport vacio) en 1366x768.
- Sin scroll horizontal en 375px (mobile).
- "Compacto" (si existe toggle) debe cambiar claramente densidad (padding/line-height) y persistir.

### Mejora 2 (P0) - Header de detalle unificado (Producto y Activo)
Problema que ataca: UI-02
Accion concreta:
- Un "Detail Header" consistente:
  - Titulo (nombre/identificadores)
  - Chip de estado principal (si aplica)
  - 2-4 KPIs (Total/Disponibles/No disponibles)
  - CTA primaria (segun rol) + acciones secundarias (dropdown)
- Evitar que KPIs parezcan campos; usar card/metric style real.
Validaciones / Aceptacion:
- En 3 segundos, un usuario puede ubicar: (a) que entidad es, (b) en que estado esta, (c) cual es la accion principal.
- Acciones criticas visibles sin scroll.

### Mejora 3 (P0) - Acciones por fila: un solo patron claro
Problema que ataca: UI-03
Accion concreta:
- Estandarizar "Acciones" en tablas:
  - Boton "Acciones" con texto + caret (no icon-only)
  - O boton primario contextual (Asignar/Desasignar) + kebab para extras
- Aumentar targets y separar acciones destructivas con divider y confirmacion.
Validaciones / Aceptacion:
- Touch targets >= 44x44 px (especialmente en columnas de acciones).
- Cada accion tiene label legible (no solo icono).
- Para Lector, solo aparece "Ver" y nunca aparecen acciones de edicion/operacion.

### Mejora 4 (P0) - Sistema de estados consistente (chips/badges)
Problema que ataca: UI-04
Accion concreta:
- Definir 1 mapping visual por estado:
  - Disponible / Asignado / Prestado / Pendiente de Retiro / Retirado
- Reusar el mismo componente en:
  - Tablas
  - Detalle del activo
  - Tenencia actual
  - Ajustes/movimientos
Validaciones / Aceptacion:
- El mismo estado se ve igual en TODO el sistema (mismo color, icono, tipografia, padding).
- Contraste: texto del badge cumple WCAG (min 4.5:1 para texto normal).
- No depender solo del color: incluir icono + texto (ya existe en varios puntos).

### Mejora 5 (P0) - Formularios de asignacion/desasignacion "a prueba de errores"
Problema que ataca: UI-05
Accion concreta:
- Seleccion de empleado:
  - mostrar "pill" con RPE + nombre + depto (visual y persistente)
  - boton claro "Cambiar" / "Limpiar"
- Errores:
  - al corregir un campo, su error debe desaparecer de inmediato
  - en submit: focus al primer error + resumen arriba (opcional)
- Nota requerida:
  - contador + min length claro (5+) + ejemplo
Validaciones / Aceptacion:
- Tras seleccionar empleado, el error no debe persistir y la seleccion debe ser visible.
- Errores deben estar junto al campo y con mensaje accionable.
- Boton submit deshabilitado durante procesamiento; mostrar loading.

### Mejora 6 (P1) - Seeders demo (QA/dev velocity)
Accion concreta:
- Incluir en seeder data "demo minima" (igual al checklist de arriba) + 1-2 casos extra:
  - 1 activo Prestado
  - 1 activo Pendiente de Retiro
  - 1 activo Retirado (para validar filtro y semantica baseline)
Validaciones / Aceptacion:
- Tras migrate:fresh --seed, se puede recorrer Productos->Detalle->Activos->Detalle sin crear data manual.

### Mejora 7 (P1) - Estados de carga/performance (segun bible)
Accion concreta:
- Si una accion/lista tarda >3s: skeleton + mensaje + opcion "cancelar busqueda".
Validaciones / Aceptacion:
- No hay "congelamiento" sin feedback.

---

## 3) Plan de implementacion (por fases)

### Fase 1 (P0) - Acciones + Formularios
1) Refactor de columna de acciones en activos (patron unico, targets grandes, sin icon-only).
2) Ajustes de formularios (empleado visible + clearing de errores + focus al primer error + loading en submit).
3) Regresion: pruebas minimas de asignar/desasignar + RBAC (Admin/Editor/Lector).

### Fase 2 (P0) - Estados + Jerarquia
4) Unificar componente de estado (badge) y aplicarlo en tablas/detalles/tenencia.
5) Header de detalle unificado (Producto/Activo): titulo + estado + KPIs + CTA principal.
6) Re-ajuste de layout puntual (sin redisenar todo): reducir whitespace y mejorar lectura de KPIs.

### Fase 3 (P1) - Seeders demo + Locks
7) Seeders demo robustos (data minima + casos extra por estado).
8) Data/fixtures para Tareas Pendientes y ejecucion de escenarios locks (2 editores + admin).
9) Feedback de performance (skeleton/cancel) en puntos que superen 3s.

Dependencias / riesgos
- Si se usa icon font con caracteres PUA, verificar fallback y accesibilidad (aria-labels) en icon-only buttons.
- Mantener coherencia RBAC: la UI ayuda, pero no reemplaza autorizacion server-side.

---

## 4) Validaciones (checklist de QA antes de cerrar un ticket)

### A11y / Web Interface Guidelines
- Focus visible en todos los elementos interactivos (inputs, links, dropdown, botones).
- Botones icon-only tienen aria-label (o texto visible).
- Tab order coincide con orden visual (sin saltos raros).
- Labels asociados a inputs (no solo placeholder).
- Contraste minimo (texto normal 4.5:1). Revisar especialmente badges y textos grises.
- No depender solo del color para estados (texto + icono).

### UI Consistency
- Badge de estado consistente (mismo color/forma/icono) en listados y detalles.
- CTAs: color/jerarquia consistente (primario vs secundario vs peligro).
- Spacing consistente: paddings de cards/tablas/formularios; alineaciones en columnas.

### Formularios
- Errores inline: aparecen junto al campo, con copy accionable.
- Errores se limpian al corregir el campo (sin persistencia incorrecta).
- Submit deshabilitado mientras procesa; previene doble submit.
- Focus automatico al primer error en submit (si hay).
- Minimo de nota (5) validado y comunicado.

### Responsive
- Breakpoints: 375px / 768px / 1024px / 1440px
- Sin scroll horizontal.
- Targets tactiles >= 44x44 px.
- Tablas: overflow controlado (scroll solo en tabla si es inevitable).

### UX Operativo (inventario)
- Busqueda unificada:
  - match exacto por serial/asset_tag => redirige a detalle activo
  - busqueda por nombre => lista de productos
- Filtros combinables y con "reset" claro.
- "Retirado" no cuenta en baseline, pero se puede filtrar/ver como informativo.

---

## 5) Escenarios de prueba (manual E2E + regresion)

Formato: Caso | Rol | Precondiciones | Pasos | Resultado esperado

### 5.1 Autenticacion / Roles

TC-AUTH-01 | Admin | usuario activo | 1) Login 2) Abrir menu lateral | Ve modulos admin (Usuarios/Papelera/Errores soporte)
TC-AUTH-02 | Editor | usuario activo | 1) Login 2) Abrir menu lateral | NO ve modulos admin; si ve Inventario/Tareas/Empleados/Catalogos segun RBAC
TC-AUTH-03 | Lector | usuario activo | 1) Login 2) Navegar a /admin/users | Acceso denegado (403 o redirect) + mensaje claro

### 5.2 Productos - Listado + Filtros

TC-PROD-01 | Admin/Editor/Lector | existe >= 1 producto | 1) Ir a Productos | Tabla carga; muestra columnas y acciones segun rol
TC-PROD-02 | Admin/Editor | existe >= 1 producto | 1) Productos 2) Click "Nuevo producto" | Form visible; Lector no ve boton
TC-PROD-03 | Todos | existe categoria/marca | 1) Filtrar por Categoria 2) Filtrar por Marca | Tabla actualiza; filtros combinan; no rompe layout
TC-PROD-04 | Todos | existe disponibles/no disponibles | 1) Filtrar Disponibilidad "Con disponibles" | Solo productos con disponibles; indicador claro
TC-PROD-05 | Todos | N/A | 1) Limpiar filtros (manual) | Regresa a "Todas" sin estado residual

### 5.3 Producto - Detalle

TC-PROD-DET-01 | Todos | producto serializado con activos | 1) Abrir detalle del producto | KPIs correctos; desglose por estado correcto
TC-PROD-DET-02 | Todos | existe activos Retirado | 1) Ver Total y nota de baseline | Se entiende que Retirado no cuenta en baseline
TC-PROD-DET-03 | Admin/Editor | N/A | 1) Click "Activos" desde detalle | Navega a listado de activos del producto

### 5.4 Activos - Listado + Busqueda + Filtros

TC-ASSET-01 | Todos | 2 activos (Asignado/Disponible) | 1) Abrir Activos del producto | Tabla muestra chips correctos; acciones segun rol
TC-ASSET-02 | Todos | 2 activos | 1) Buscar por serial exacto (SN-1001) desde Busqueda global | Redirige a detalle de activo
TC-ASSET-03 | Todos | 2 activos | 1) Buscar por asset_tag exacto (AT-1001) | Redirige a detalle de activo
TC-ASSET-04 | Todos | 2 activos | 1) Buscar parcial (SN-) en Activos | Filtra lista; no redirige; mantiene contexto
TC-ASSET-05 | Todos | ubicacion existente | 1) Filtrar por Ubicacion | Lista se reduce; sin "saltos" visuales
TC-ASSET-06 | Todos | varios estados | 1) Filtrar por Estado (Disponible/Asignado/...) | Chips consistent; conteo correcto

### 5.5 Activo - Detalle (Tenencia/Notas/Adjuntos)

TC-ASSET-DET-01 | Todos | activo Asignado | 1) Abrir detalle de activo | "Tenencia actual" muestra estado + empleado
TC-ASSET-DET-02 | Admin/Editor | N/A | 1) Agregar nota 2) Guardar | Nota aparece en lista; feedback de exito
TC-ASSET-DET-03 | Admin/Editor | archivo permitido <10MB | 1) Subir adjunto | Aparece en lista con nombre original; acceso controlado
TC-ASSET-DET-04 | Lector | existe adjunto | 1) Abrir detalle con adjuntos | (Segun MVP) no debe poder ver/descargar si esta restringido; UI no muestra acciones indebidas

### 5.6 Asignar / Desasignar (nota requerida)

TC-MOVE-01 | Admin/Editor | activo Disponible + empleado | 1) Acciones -> Asignar 2) Seleccionar empleado 3) Nota >=5 4) Submit | Estado cambia a Asignado; empleado queda como current; mensaje de exito; no doble submit
TC-MOVE-02 | Admin/Editor | activo Disponible | 1) Asignar 2) Submit vacio | Errores en Empleado y Nota; foco al primer error
TC-MOVE-03 | Admin/Editor | activo Disponible | 1) Trigger error 2) Corregir empleado | Error de empleado desaparece inmediatamente; seleccion visible
TC-MOVE-04 | Admin/Editor | activo Asignado | 1) Desasignar 2) Nota <5 3) Submit | Error claro de minimo; no cambia estado
TC-MOVE-05 | Admin/Editor | activo Asignado | 1) Desasignar 2) Nota >=5 3) Submit | Estado cambia a Disponible; tenencia se limpia; movimiento registrado
TC-MOVE-06 | Lector | activo Disponible/Asignado | 1) Abrir listado y detalle | No ve acciones Asignar/Desasignar/Editar/Ajustar

### 5.7 Busqueda unificada (global)

TC-SEARCH-01 | Todos | existe producto con nombre | 1) /inventory/search 2) Buscar por nombre | Lista Productos (no redirige a activo)
TC-SEARCH-02 | Todos | serial exacto | 1) Buscar SN exacto | Redirige a detalle del activo
TC-SEARCH-03 | Todos | asset_tag exacto | 1) Buscar AT exacto | Redirige a detalle del activo
TC-SEARCH-04 | Todos | no existe termino | 1) Buscar "XYZ-404" | Estado vacio con tip y sin layout roto

### 5.8 Tareas Pendientes + Locks (cuando exista data)

Precondicion recomendada de data:
- 1 tarea en estado "Listo" con renglones.
- 2 usuarios Editor (para simular concurrencia).

TC-LOCK-01 | Editor A + Editor B | misma tarea | 1) A click "Procesar" 2) B intenta "Procesar" | B ve lock visual + mensaje quien lo tiene + tiempo restante
TC-LOCK-02 | Editor | lock activo | 1) Permanecer activo > 10s | Heartbeat renueva lock; indicador actualizado
TC-LOCK-03 | Editor | lock activo | 1) Inactividad > 2m | Idle guard deja de renovar; lock expira en ~3m
TC-LOCK-04 | Admin | tarea lockeada | 1) Forzar liberacion | Lock se libera; auditoria registrada; UI advierte impacto

---

## 6) Recomendaciones de instrumentacion (para QA continuo)

- Agregar un "QA smoke script" (manual o semi-automatizado) que:
  - cree data demo si no existe
  - valide rutas principales por rol (403/visibilidad)
- Checklist de regresion UI por release:
  - Productos (listado/detalle)
  - Activos (listado/detalle/asignar/desasignar)
  - Busqueda global
  - Accesibilidad basica (focus/labels/targets)

---

## 7) Anexos

Evidencias (capturas):
- ui-issue-01-empty-whitespace.png
- ui-issue-02-product-detail-density.png
- ui-issue-03-assets-list-actions.png
- ui-issue-04-asset-detail-actions.png
- ui-issue-07-error-not-cleared.png
