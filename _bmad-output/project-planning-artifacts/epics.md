---
stepsCompleted: [1, 2, 3, 4]
inputDocuments:
  - 03-visual-style-guide.md
  - _bmad-output/prd.md
  - _bmad-output/analysis/brainstorming-session-2025-12-25.md
  - _bmad-output/project-planning-artifacts/product-brief-GATIC-2025-12-26.md
  - _bmad-output/project-planning-artifacts/gatic-backlog.md
  - docsBmad/project-context.md
  - docsBmad/gates-execution.md
---

# GATIC - Epic Breakdown

## Overview

This document provides the complete epic and story breakdown for GATIC, decomposing the requirements from the PRD, UX Design if it exists, and Architecture requirements into implementable stories.

## Requirements Inventory

### Functional Requirements

FR1: Usuario puede iniciar y cerrar sesión.
FR2: El sistema puede aplicar control de acceso por rol (Admin/Editor/Lector) en todas las acciones.
FR3: Admin puede crear, deshabilitar y asignar rol a usuarios del sistema.
FR4: Admin puede gestionar Categorías, incluyendo si son serializadas y si requieren `asset_tag`.
FR5: Admin/Editor puede gestionar Marcas.
FR6: Admin/Editor puede gestionar Ubicaciones.
FR7: El sistema puede impedir eliminar catálogos referenciados y permitir soft-delete cuando no lo estén.
FR8: Admin/Editor puede crear y mantener Productos y sus atributos/catálogos asociados.
FR9: El sistema puede manejar Productos como serializados o por cantidad según su Categoría.
FR10: Admin/Editor puede crear y mantener Activos (para productos serializados) con `serial` y `asset_tag` (si aplica).
FR11: El sistema puede aplicar unicidad de `asset_tag` global y unicidad de `serial` por Producto.
FR12: Usuario puede ver detalle de Producto con conteos de disponibilidad y desglose por estado.
FR13: Usuario puede ver detalle de Activo con su estado actual, ubicación y tenencia actual (si aplica).
FR14: Admin puede realizar ajustes de inventario registrando un motivo.
FR15: Admin/Editor puede crear y mantener Empleados (RPE) como receptores de movimientos.
FR16: Usuario puede buscar/seleccionar Empleados al registrar movimientos.
FR17: Admin/Editor puede asignar un Activo serializado a un Empleado.
FR18: Admin/Editor puede prestar un Activo serializado a un Empleado.
FR19: Admin/Editor puede registrar devoluciones de Activos serializados.
FR20: El sistema puede aplicar reglas de transición/validación para evitar acciones en conflicto (según estados).
FR21: Admin/Editor puede registrar movimientos por cantidad (salida/entrada) vinculados a Producto y Empleado.
FR22: El sistema puede mantener historial de movimientos (kardex) para productos por cantidad.
FR23: Usuario puede buscar Productos y Activos por nombre e identificadores (serial, `asset_tag`).
FR24: Usuario puede filtrar inventario por categoría, marca, ubicación y estado/disponibilidad.
FR25: El sistema puede presentar indicadores de disponibilidad (total/disponibles/no disponibles) por Producto.
FR26: Admin/Editor puede crear una Tarea Pendiente para procesar múltiples renglones en lote.
FR27: Admin/Editor puede añadir/editar/eliminar renglones de una Tarea Pendiente antes de finalizarla.
FR28: El sistema puede permitir procesamiento por renglón y finalización parcial (aplica lo válido y deja pendientes/errores).
FR29: El sistema puede asegurar procesamiento exclusivo por un solo Editor mediante lock/claim.
FR30: El sistema puede mostrar estado del lock (quién lo tiene y desde cuándo) a otros usuarios.
FR31: Admin puede liberar o forzar el reclamo de un lock de Tarea Pendiente.
FR32: El sistema puede registrar y permitir consultar auditoría de acciones clave a roles autorizados.
FR33: Usuario puede agregar notas manuales a registros relevantes (según permisos).
FR34: Admin/Editor puede subir/ver/eliminar adjuntos asociados a registros; Lector no puede acceder a adjuntos en MVP.
FR35: El sistema puede hacer soft-delete y permitir a Admin restaurar o purgar definitivamente desde Papelera.
FR36: El sistema puede mostrar un ID de error ante fallos inesperados y permitir a Admin consultar el detalle asociado.

### NonFunctional Requirements

NFR1: El sistema debe soportar operación diaria en intranet con UX fluida (desktop-first) en flujos de consulta y registro.
NFR2: Si una consulta/búsqueda tarda `>3s`, la UI debe mostrar loader/skeleton + mensaje de progreso + opción de cancelar.
NFR3: Actualización de estados vía polling (sin WebSockets) cuando aplique: badges/estados en listas cada ~15s; métricas dashboard cada ~60s; heartbeat de locks cada ~10s.
NFR4: Autenticación obligatoria y autorización por rol aplicada del lado servidor (no solo en UI).
NFR5: Lector no debe poder ejecutar acciones destructivas ni acceder a adjuntos en MVP.
NFR6: Adjuntos deben almacenarse con nombre seguro (UUID en disco) y mostrarse con nombre original en UI; validar tipo/tamaño según política definida.
NFR7: Operaciones críticas (movimientos, cambios de estado, procesamiento de tareas) deben ser atómicas; no debe quedar inventario en estado inconsistente.
NFR8: Auditoría “best effort”: si falla el registro de auditoría, la operación principal del usuario no debe bloquearse; el fallo debe quedar registrado internamente.
NFR9: Locks de Tareas Pendientes deben evitar bloqueos “eternos”: timeout rolling ~15 min; lease TTL ~3 min renovado por heartbeat; idle guard no renovar si no hubo actividad real ~2 min; Admin puede liberar/forzar reclamo (auditado).
NFR10: En producción, errores inesperados deben mostrarse con mensaje amigable + ID de error; detalle técnico solo visible para Admin.

### Additional Requirements

- Stack objetivo (starter): Laravel 11 + PHP 8.2+ + MySQL 8, Blade + Livewire 3 + Bootstrap 5, Auth con Breeze (Blade) adaptado a Bootstrap, build con Vite/NPM.
- Local dev: Laravel Sail; producción prevista con Docker Compose (Nginx + PHP-FPM) por definir.
- Diseño: usar `03-visual-style-guide.md` solo como referencia de colores/branding corporativo (está desactualizada; no es un catálogo rígido de componentes).
- Concurrencia Tareas Pendientes: claim preventivo al hacer clic en “Procesar”; read-only para otros; “Solicitar liberación” es informativo en MVP (sin notificaciones).
- Regla de adopción (movimientos): mínimo obligatorio al registrar un préstamo/asignación/salida es **alias/nombre del receptor + nota/info**; el resto opcional.
- UX base (Gate 1): layout desktop-first con sidebar colapsable + topbar; skeleton loaders; botón “Cancelar” en búsquedas; toasts con “Deshacer” (~10s); indicador “Actualizado hace Xs”.
- Búsqueda unificada (Gate 2): NO indexar/mostrar Tareas Pendientes en resultados.
- Validación Tareas Pendientes: series alfanuméricas mínimo 4; permitir duplicados dentro de la tarea pero bloquear/validar al aplicar a inventario.
- Calidad/CI: `pint + phpunit + larastan` (merge solo con CI verde).
- Auditoría/async: usar queue `database`; “best effort” (si falla, no bloquear la operación; registrar warning interno).

### FR Coverage Map

FR1: Epic 1 - Acceso seguro (login/logout)
FR2: Epic 1 - Control de acceso por rol
FR3: Epic 1 - Administración de usuarios y roles
FR4: Epic 2 - Catálogo de Categorías (serializado / asset_tag)
FR5: Epic 2 - Catálogo de Marcas
FR6: Epic 2 - Catálogo de Ubicaciones
FR7: Epic 2 - Integridad de catálogos (no borrar referenciados) + soft-delete
FR8: Epic 3 - CRUD de Productos
FR9: Epic 3 - Producto serializado vs por cantidad
FR10: Epic 3 - CRUD de Activos serializados (serial / asset_tag)
FR11: Epic 3 - Reglas de unicidad (serial / asset_tag)
FR12: Epic 3 - Detalle de Producto (conteos / disponibilidad)
FR13: Epic 3 - Detalle de Activo (estado / ubicación / tenencia)
FR14: Epic 3 - Ajustes de inventario (con motivo)
FR15: Epic 4 - CRUD de Empleados (RPE)
FR16: Epic 4 - Selección/búsqueda de Empleados en movimientos
FR17: Epic 5 - Asignar activo a empleado
FR18: Epic 5 - Prestar activo a empleado
FR19: Epic 5 - Registrar devolución
FR20: Epic 5 - Validaciones y transiciones de estado
FR21: Epic 5 - Movimientos por cantidad (salida/entrada) con empleado
FR22: Epic 5 - Kardex/historial para cantidad
FR23: Epic 6 - Búsqueda de Productos/Activos (nombre/serial/asset_tag)
FR24: Epic 6 - Filtros por catálogos/estado/disponibilidad
FR25: Epic 3 - Indicadores de disponibilidad por Producto (listado de inventario)
FR26: Epic 7 - Crear Tarea Pendiente
FR27: Epic 7 - Editar renglones antes de finalizar
FR28: Epic 7 - Procesamiento por renglón + finalización parcial
FR29: Epic 7 - Exclusividad por lock/claim
FR30: Epic 7 - Visibilidad del lock (quién / desde cuándo)
FR31: Epic 7 - Admin puede liberar/forzar lock
FR32: Epic 8 - Auditoría consultable
FR33: Epic 8 - Notas manuales
FR34: Epic 8 - Adjuntos (Admin/Editor) con control de acceso
FR35: Epic 8 - Papelera (soft-delete / restaurar / purgar)
FR36: Epic 8 - Error ID + consulta de detalle (Admin)

## Epic List

### Epic 1: Acceso seguro y administración de usuarios
Permite que el equipo TI acceda al sistema y que Admin gestione usuarios/roles para operar el inventario con permisos consistentes.
**FRs covered:** FR1, FR2, FR3

### Epic 2: Catálogos base de inventario (Categorías/Marcas/Ubicaciones)
Permite configurar los catálogos necesarios para clasificar el inventario y mantener integridad (bloqueos/soft-delete de catálogos).
**FRs covered:** FR4, FR5, FR6, FR7

### Epic 3: Inventario navegable (Productos/Activos) + ajustes
Permite crear y mantener Productos y Activos, aplicar reglas de unicidad, consultar detalles y realizar ajustes con motivo.
**FRs covered:** FR8, FR9, FR10, FR11, FR12, FR13, FR14, FR25

Nota: la tenencia real (Empleado RPE) y movimientos (asignar/prestar/devolver) se implementan en Epica 4/5.

### Epic 4: Directorio de Empleados (RPE)
Permite administrar Empleados (RPE) y seleccionarlos rápidamente como receptores de movimientos.
**FRs covered:** FR15, FR16

### Epic 5: Operación diaria de movimientos (serializados y por cantidad)
Permite asignar/prestar/devolver activos, validar transiciones, registrar movimientos por cantidad y mantener kardex.
**FRs covered:** FR17, FR18, FR19, FR20, FR21, FR22

### Epic 6: Búsqueda y filtros del inventario
Permite encontrar Productos/Activos por identificadores y filtrar por catálogos/estado, mostrando disponibilidad clara.
**FRs covered:** FR23, FR24

### Epic 7: Tareas Pendientes + locks de concurrencia
Permite crear y procesar tareas por renglón con finalización parcial y exclusividad por lock/claim con override Admin.
**FRs covered:** FR26, FR27, FR28, FR29, FR30, FR31

### Epic 8: Trazabilidad y evidencia (auditoría, notas, adjuntos, papelera, errores)
Permite auditoría consultable, notas, adjuntos con permisos, papelera (soft-delete/restaurar/purgar) y errores con ID.
**FRs covered:** FR32, FR33, FR34, FR35, FR36

### Epic 9: Hardening y operación (post Gate 5)
Consolidación post-Gate 5: documentación crítica, runbooks de operación y eliminación de “parches” recurrentes.
**FRs covered:** N/A (hardening)

### Epic 10: UI uplift (desktop-first productividad)
Mejora incremental de UI para acercarla al UX spec (densidad, velocidad, atajos, consistencia visual) sin reescribir el sistema.
**FRs covered:** N/A (UX/productividad)

## Epic 1: Acceso seguro y administración de usuarios

Permite que el equipo TI acceda al sistema y que Admin gestione usuarios/roles para operar el inventario con permisos consistentes.

### Story 1.1: Repo inicial (layout) + Laravel 11 base

As a desarrollador del proyecto,
I want definir el layout del repo e inicializar Laravel 11,
So that el equipo tenga una base consistente para construir el MVP (Arquitectura).

**Acceptance Criteria:**

**Given** un repositorio nuevo
**When** se define el layout del repo (app en raíz o subcarpeta)
**Then** la decisión y su justificación quedan documentadas en `README.md`
**And** la estructura del repo coincide con lo documentado

**Given** el proyecto inicializado
**When** se ejecuta `php artisan --version`
**Then** el comando reporta Laravel 11.x
**And** existe `.env.example` con variables mínimas para boot (sin secretos)

### Story 1.2: Entorno local con Sail + MySQL 8 + seeders mínimos

As a desarrollador del proyecto,
I want levantar el proyecto en Laravel Sail con MySQL 8 y seeders base,
So that pueda iterar rápido y reproducir el entorno local de forma consistente (Arquitectura).

**Acceptance Criteria:**

**Given** Docker instalado y el repo clonado
**When** se ejecuta `./vendor/bin/sail up -d`
**Then** los contenedores levantan sin errores
**And** la app responde en el entorno local esperado

**Given** una base de datos vacía
**When** se ejecuta `./vendor/bin/sail artisan migrate --seed`
**Then** las migraciones aplican sin errores
**And** existen datos mínimos para operar el sistema (roles/usuario admin/datos demo básicos)

### Story 1.3: Autenticación base (Breeze Blade) operativa

As a usuario interno,
I want iniciar sesión y cerrar sesión,
So that pueda acceder de forma segura al sistema (FR1).

**Acceptance Criteria:**

**Given** un usuario válido en el sistema
**When** ingresa credenciales correctas
**Then** inicia sesión exitosamente
**And** accede a la página principal autorizada

**Given** un usuario autenticado
**When** ejecuta logout
**Then** la sesión se invalida
**And** ya no puede acceder a rutas protegidas sin autenticarse

### Story 1.4: UI base Bootstrap 5 (sin Tailwind) alineada a guía visual

As a usuario interno,
I want que las pantallas base (auth + layout) usen Bootstrap 5 y la guía visual corporativa,
So that la UX sea consistente con el estándar interno (NFR1, Arquitectura).

**Acceptance Criteria:**

**Given** la instalación de auth base
**When** se construyen los assets frontend
**Then** Bootstrap 5 está integrado y funcionando
**And** Tailwind no se utiliza en las vistas del proyecto

**Given** las pantallas de autenticación
**When** un usuario navega login/registro/recuperación (si aplica)
**Then** la maquetación usa componentes Bootstrap
**And** respeta colores/branding corporativo tomando `03-visual-style-guide.md` como referencia

### Story 1.5: Livewire 3 instalado e integrado en el layout

As a desarrollador del proyecto,
I want contar con Livewire 3 configurado en el layout,
So that pueda implementar pantallas reactivas (polling/acciones) sin complejidad extra (Arquitectura, NFR3).

**Acceptance Criteria:**

**Given** el proyecto con UI base
**When** se instala Livewire 3
**Then** los assets/scripts requeridos quedan incluidos en el layout
**And** un componente mínimo de prueba renderiza sin errores

### Story 1.6: Roles fijos + policies/gates base (server-side)

As a Admin,
I want gestionar usuarios y roles y que el sistema aplique autorización por rol,
So that el acceso esté controlado en todas las acciones (FR2, FR3).

**Acceptance Criteria:**

**Given** los roles fijos (Admin/Editor/Lector)
**When** un usuario intenta ejecutar una acción no permitida por su rol
**Then** el servidor bloquea la operación (403 o redirección segura)
**And** la UI oculta/inhabilita acciones no permitidas (defensa en profundidad)

**Given** un Admin autenticado
**When** crea un usuario y le asigna un rol (Admin/Editor/Lector)
**Then** el usuario queda creado con ese rol
**And** el rol aplicado define su acceso efectivo al navegar el sistema

**Given** un Admin autenticado
**When** deshabilita un usuario
**Then** ese usuario no puede iniciar sesión
**And** cualquier sesión activa queda invalidada o expira según la política definida

**Given** un Admin autenticado
**When** cambia el rol de un usuario
**Then** los permisos efectivos del usuario cambian inmediatamente (server-side)
**And** la UI refleja el nuevo rol en el menú y acciones visibles

**Given** un usuario con rol Editor
**When** intenta acceder por URL directa a gestión de usuarios
**Then** no obtiene acceso a la pantalla
**And** se redirige o muestra un 403 según la política definida

### Story 1.7: Calidad y CI mínima (Pint + PHPUnit + Larastan)

As a mantenedor del repositorio,
I want un pipeline de CI que ejecute formato, tests y análisis estático,
So that los merges mantengan calidad y no rompan el sistema (Arquitectura, Calidad/CI).

**Acceptance Criteria:**

**Given** un Pull Request abierto
**When** corre el workflow de CI
**Then** ejecuta `pint --test`, `phpunit` y `phpstan`/Larastan
**And** el PR bloquea merge si alguna verificación falla

**Given** el repositorio recién clonado
**When** se ejecutan los comandos de calidad en local
**Then** corren sin configuración adicional oculta
**And** existe documentación mínima para ejecutarlos

### Story 1.8: Layout base (sidebar/topbar) + navegación por rol

As a usuario interno,
I want un layout desktop-first con navegación clara y menú por rol,
So that pueda moverme rápido por los módulos del sistema (FR2, NFR1).

**Acceptance Criteria:**

**Given** un usuario autenticado
**When** entra al sistema
**Then** ve un layout con sidebar + topbar
**And** el menú muestra solo opciones permitidas por su rol

### Story 1.9: Componentes UX reutilizables (toasts, loaders, cancelar, “Actualizado hace Xs”)

As a usuario interno,
I want feedback inmediato (toasts/loaders) y control en búsquedas lentas,
So that el sistema sea rápido y predecible en operación diaria (NFR1, NFR2).

**Acceptance Criteria:**

**Given** una acción exitosa o fallida
**When** el sistema responde
**Then** se muestra un toast de éxito/error consistente
**And** las acciones reversibles muestran opción “Deshacer” con ventana de ~10s (si aplica)

**Given** una búsqueda o carga que tarda más de 3 segundos
**When** el usuario espera resultados
**Then** se muestra skeleton/loader + mensaje de progreso
**And** existe una opción de “Cancelar” que detiene la espera y conserva el estado anterior

**Given** una vista con polling
**When** se actualizan los datos automáticamente
**Then** se muestra el indicador “Actualizado hace Xs”
**And** el contador se actualiza de forma consistente

### Story 1.10: Manejo de errores en producción con ID (detalle solo Admin)

As a usuario interno,
I want ver un mensaje amigable con un ID de error cuando algo falla,
So that pueda reportar el problema y TI lo pueda diagnosticar (FR36, NFR10).

**Acceptance Criteria:**

**Given** un error inesperado en producción
**When** ocurre una excepción no controlada
**Then** el usuario ve un mensaje amigable con un ID de error
**And** el ID de error se registra junto con el detalle técnico

**Given** un usuario con rol Admin
**When** consulta el detalle del error por ID
**Then** puede ver información diagnóstica suficiente para soporte
**And** un usuario no Admin no puede ver el detalle técnico

### Story 1.11: Patrón de polling base (wire:poll.visible) reutilizable

As a usuario interno,
I want que ciertos indicadores se actualicen automáticamente sin recargar la página,
So that la información operativa esté vigente sin usar WebSockets (NFR3).

**Acceptance Criteria:**

**Given** una vista configurada con polling visible
**When** la pestaña está visible
**Then** se actualiza en intervalos configurables (por defecto: 15s para badges, 60s para métricas)
**And** el polling se detiene cuando la vista no está visible

## Epic 2: Catálogos base de inventario (Categorías/Marcas/Ubicaciones)

Permite configurar los catálogos necesarios para clasificar el inventario y mantener integridad (bloqueos/soft-delete de catálogos).

### Story 2.1: Gestionar Categorías (incluye serializado/asset_tag)

As a Admin,
I want crear y mantener Categorías indicando si son serializadas y si requieren `asset_tag`,
So that el sistema aplique reglas correctas de inventario (FR4).

**Acceptance Criteria:**

**Given** un Admin autenticado
**When** crea o edita una Categoría
**Then** puede configurar `is_serialized` y `requires_asset_tag`
**And** las validaciones impiden valores inconsistentes

### Story 2.2: Gestionar Marcas

As a Admin/Editor,
I want crear y mantener Marcas,
So that pueda clasificar productos de inventario (FR5).

**Acceptance Criteria:**

**Given** un Admin/Editor autenticado
**When** crea/edita/elimina (soft-delete) una Marca
**Then** la operación se completa exitosamente según permisos
**And** la lista refleja el cambio sin inconsistencias

### Story 2.3: Gestionar Ubicaciones

As a Admin/Editor,
I want crear y mantener Ubicaciones,
So that pueda registrar dónde están los activos (FR6).

**Acceptance Criteria:**

**Given** un Admin/Editor autenticado
**When** crea/edita/elimina (soft-delete) una Ubicación
**Then** la operación se completa exitosamente según permisos
**And** la lista refleja el cambio sin inconsistencias

### Story 2.4: Soft-delete y restauración de catálogos

As a Admin/Editor,
I want eliminar catálogos solo mediante soft-delete (sin borrado físico) y poder restaurarlos,
So that se mantenga integridad referencial y trazabilidad en el inventario (FR7).

**Acceptance Criteria:**

**Given** un usuario con permisos de catálogo (Admin/Editor según corresponda)
**And** el catálogo NO está referenciado por registros del inventario
**When** elimina una Marca/Categoría/Ubicación
**Then** el registro se marca como soft-deleted (no se borra físicamente)
**And** deja de aparecer en listados normales

**Given** un catálogo referenciado por registros del inventario
**When** el usuario intenta eliminarlo (soft-delete)
**Then** el sistema bloquea la operación
**And** muestra un mensaje claro indicando que el catálogo está en uso

**Given** un catálogo en soft-delete
**When** Admin lo restaura
**Then** el catálogo vuelve a estar disponible en el sistema
**And** se conservan referencias e historial según la política definida

## Epic 3: Inventario navegable (Productos/Activos) + ajustes

Permite crear y mantener Productos y Activos, aplicar reglas de unicidad, consultar detalles y realizar ajustes con motivo.

**Nota (Gate 2 / Epic 3):**

- En esta epica se construye inventario navegable (productos/activos, estados, conteos, ajustes).
- La "tenencia" real (asignar/prestar/devolver a Empleados RPE) se implementa en Epica 4 (Empleados) y Epica 5 (Movimientos).

### Story 3.1: Crear y mantener Productos

As a Admin/Editor,
I want crear y mantener Productos con sus atributos y catálogos asociados,
So that el sistema maneje Productos serializados o por cantidad según su Categoría (FR8, FR9).

**Acceptance Criteria:**

**Given** un Admin/Editor autenticado
**When** crea o edita un Producto
**Then** puede asociarlo a Categoria y Marca (segun modelo definido)
**And** la ubicacion operativa se captura a nivel de Activo (para productos serializados)
**And** las validaciones impiden datos incompletos

**Given** una Categoría con `is_serialized = true`
**When** se crea un Producto en esa Categoría
**Then** el Producto se considera serializado
**And** el sistema habilita la gestión de Activos para ese Producto

**Given** una Categoría con `is_serialized = false`
**When** se crea un Producto en esa Categoría
**Then** el Producto se considera por cantidad
**And** el Producto expone/almacena un stock total para operar inventario por cantidad

### Story 3.2: Crear y mantener Activos (serializados) con reglas de unicidad

As a Admin/Editor,
I want crear y mantener Activos serializados con `serial` y `asset_tag` (cuando aplique),
So that pueda identificar unidades físicas sin ambigüedad (FR10, FR11).

**Acceptance Criteria:**

**Given** un Producto cuya Categoría es serializada
**When** se registra un Activo con `serial`
**Then** el sistema aplica unicidad de `serial` por Producto
**And** rechaza duplicados con un mensaje claro

**Given** una Categoría que requiere `asset_tag`
**When** se registra un Activo sin `asset_tag`
**Then** el sistema rechaza la creación/edición
**And** muestra un mensaje claro indicando que es obligatorio

**Given** una Categoría que requiere `asset_tag`
**When** se registra un Activo con `asset_tag`
**Then** el sistema aplica unicidad global de `asset_tag`
**And** rechaza duplicados con un mensaje claro

### Story 3.3: Listado de Inventario (Productos) con indicadores de disponibilidad

As a usuario interno,
I want ver un listado de Productos con disponibilidad clara (total/disponibles/no disponibles),
So that pueda responder rápido “¿tenemos X?” (FR25).

**Acceptance Criteria:**

**Given** una lista de Productos
**When** el usuario abre el módulo de inventario
**Then** ve indicadores de Total/Disponibles/No disponibles por Producto
**And** los Productos con `Disponibles = 0` se resaltan visualmente

**Given** la semántica de estados serializados definida
**When** se calculan disponibilidades
**Then** No disponibles = Asignado + Prestado + Pendiente de Retiro
**And** Disponibles = Total - No disponibles
**And** en esta epica los estados pueden capturarse/ajustarse sin requerir Movimientos (Epica 5)

### Story 3.4: Detalle de Producto con conteos y desglose por estado

As a usuario interno,
I want ver el detalle de un Producto con conteos de disponibilidad y desglose por estado,
So that pueda entender qué unidades están disponibles y por qué (FR12).

**Acceptance Criteria:**

**Given** un Producto existente
**When** el usuario entra al detalle del Producto
**Then** ve conteos (total/disponibles/no disponibles)
**And** ve desglose por estado para activos serializados o un resumen de stock actual para productos por cantidad (según corresponda)
**And** el desglose por estado se basa en el estado actual de los Activos (sin depender de Movimientos en esta epica)

### Story 3.5: Detalle de Activo con estado, ubicación y tenencia actual

As a usuario interno,
I want ver el detalle de un Activo con su estado actual, ubicación y tenencia,
So that pueda saber quién lo tiene o dónde está (FR13).

**Acceptance Criteria:**

**Given** un Activo existente
**When** el usuario entra al detalle del Activo
**Then** ve estado actual y ubicacion
**And** ve una seccion "Tenencia actual" con valor "N/A (se habilita en Epica 4/5)" mientras no existan Empleados y Movimientos

### Story 3.6: Ajustes de inventario (Admin) con motivo

As a Admin,
I want realizar ajustes de inventario registrando un motivo,
So that el sistema refleje la realidad física con trazabilidad (FR14, NFR7).

**Acceptance Criteria:**

**Given** un Admin autenticado
**When** realiza un ajuste (cantidad o estado/registro según aplique)
**Then** el sistema requiere un motivo
**And** el ajuste queda registrado de forma auditable (registro de ajuste con actor, timestamp y motivo)
**And** el ajuste es transaccional (no deja inventario en estado inconsistente)

## Epic 4: Directorio de Empleados (RPE)

Permite administrar Empleados (RPE) y seleccionarlos rápidamente como receptores de movimientos.

### Story 4.1: Crear y mantener Empleados (RPE)

As a Admin/Editor,
I want crear y mantener Empleados (RPE) como receptores,
So that pueda asociar movimientos a personas reales (FR15).

**Acceptance Criteria:**

**Given** un Admin/Editor autenticado
**When** crea o edita un Empleado
**Then** el sistema valida unicidad de RPE (si aplica)
**And** permite buscar/listar empleados fácilmente

### Story 4.2: Buscar/seleccionar Empleados al registrar movimientos (autocomplete)

As a usuario interno,
I want buscar/seleccionar Empleados al registrar movimientos,
So that registrar préstamos/asignaciones sea rápido y sin fricción (FR16).

**Acceptance Criteria:**

**Given** un formulario de movimiento
**When** el usuario escribe nombre o RPE
**Then** ve sugerencias (autocomplete) relevantes
**And** puede seleccionar un Empleado para asociarlo al movimiento

### Story 4.3: Ficha de Empleado (detalle) y activos asociados (si existen)

As a usuario interno,
I want ver la ficha de un Empleado y, cuando existan, sus activos asignados/prestados,
So that pueda responder "¿qué tiene esta persona?" (FR15).

**Acceptance Criteria:**

**Given** un Empleado existente
**When** el usuario abre la ficha del Empleado
**Then** ve la información de contexto suficiente para soporte (ej. nombre, RPE, depto, puesto)
**And** ve secciones de "Activos asignados" y "Activos prestados" con estado vacío si no hay registros asociados

## Epic 5: Operación diaria de movimientos (serializados y por cantidad)

Permite asignar/prestar/devolver activos, validar transiciones, registrar movimientos por cantidad y mantener kardex.

### Story 5.1: Reglas de estado y transiciones para activos serializados

As a Admin/Editor,
I want que el sistema valide transiciones de estado para evitar acciones en conflicto,
So that el inventario no quede inconsistente (FR20, NFR7).

**Acceptance Criteria:**

**Given** un Activo en estado Asignado
**When** el usuario intenta prestarlo
**Then** el sistema bloquea la acción
**And** muestra el motivo (debe desasignar primero)

**Given** un Activo en estado Prestado
**When** el usuario intenta asignarlo a otra persona
**Then** el sistema bloquea la acción
**And** obliga a devolución/cambio válido antes de reasignar

### Story 5.2: Asignar un Activo serializado a un Empleado

As a Admin/Editor,
I want asignar un Activo serializado a un Empleado,
So that quede responsable claro del equipo (FR17).

**Acceptance Criteria:**

**Given** un Activo en estado Disponible
**When** el usuario lo asigna a un Empleado, captura una nota obligatoria y guarda
**Then** el Activo pasa a estado Asignado
**And** queda registrada la tenencia actual asociada al Empleado

**Given** el formulario de asignación
**When** el usuario intenta guardar sin nota
**Then** el sistema bloquea la operación
**And** muestra un mensaje de validación indicando que la nota es obligatoria

### Story 5.3: Prestar y devolver un Activo serializado

As a Admin/Editor,
I want prestar un Activo y registrar su devolución,
So that exista trazabilidad del préstamo (FR18, FR19).

**Acceptance Criteria:**

**Given** un Activo en estado Disponible
**When** el usuario lo presta a un Empleado y captura una nota obligatoria
**Then** el Activo pasa a estado Prestado
**And** queda registrada la operación con la nota asociada

**Given** el formulario de préstamo
**When** el usuario intenta guardar sin nota
**Then** el sistema bloquea la operación
**And** muestra un mensaje de validación indicando que la nota es obligatoria

**Given** un Activo en estado Prestado
**When** el usuario registra la devolución y captura una nota obligatoria
**Then** el Activo vuelve a estado Disponible (u otro válido según reglas)
**And** la tenencia actual queda vacía o actualizada correctamente

**Given** el formulario de devolución
**When** el usuario intenta guardar sin nota
**Then** el sistema bloquea la operación
**And** muestra un mensaje de validación indicando que la nota es obligatoria

### Story 5.4: Movimientos por cantidad vinculados a Producto y Empleado

As a Admin/Editor,
I want registrar salidas/entradas por cantidad vinculadas a Producto y Empleado,
So that el stock y la responsabilidad queden claros (FR21).

**Acceptance Criteria:**

**Given** un Producto por cantidad con stock disponible
**When** el usuario registra una salida por cantidad a un Empleado y captura una nota obligatoria
**Then** el stock disminuye en la cantidad registrada
**And** el sistema evita que el stock quede negativo

**Given** el formulario de salida por cantidad
**When** el usuario intenta guardar sin nota
**Then** el sistema bloquea la operación
**And** muestra un mensaje de validación indicando que la nota es obligatoria

**Given** una salida previa registrada
**When** el usuario registra una devolución/entrada y captura una nota obligatoria
**Then** el stock aumenta en la cantidad registrada
**And** el movimiento queda asociado al Empleado

**Given** el formulario de devolución/entrada por cantidad
**When** el usuario intenta guardar sin nota
**Then** el sistema bloquea la operación
**And** muestra un mensaje de validación indicando que la nota es obligatoria

### Story 5.5: Kardex/historial para productos por cantidad

As a usuario interno,
I want consultar el kardex de movimientos por cantidad,
So that pueda auditar entradas/salidas/ajustes de stock (FR22).

**Acceptance Criteria:**

**Given** un Producto por cantidad con movimientos registrados
**When** el usuario consulta su historial/kardex
**Then** ve una lista cronológica de movimientos
**And** cada movimiento muestra tipo, cantidad, fecha, usuario actor y Empleado receptor (si aplica)

### Story 5.6: Dashboard mínimo de métricas operativas (polling)

As a usuario interno,
I want ver métricas operativas básicas (préstamos, pendientes de retiro, etc.) actualizadas,
So that priorice acciones del día sin recorrer todo el sistema (NFR3).

**Acceptance Criteria:**

**Given** el dashboard habilitado
**When** el usuario lo abre
**Then** ve métricas mínimas definidas por el producto
**And** las métricas se actualizan por polling aproximadamente cada 60s cuando está visible

## Epic 6: Búsqueda y filtros del inventario

Permite encontrar Productos/Activos por identificadores y filtrar por catálogos/estado, mostrando disponibilidad clara.

### Story 6.1: Búsqueda unificada (Productos + Activos) con salto directo por match exacto

As a usuario interno,
I want buscar Productos y Activos por nombre, `serial` y `asset_tag`,
So that encuentre rápido lo que necesito (FR23).

**Acceptance Criteria:**

**Given** el buscador unificado disponible
**When** el usuario busca por nombre de Producto
**Then** obtiene resultados relevantes de Productos
**And** puede navegar al detalle del Producto

**Given** el buscador unificado disponible
**When** el usuario busca por `serial` o `asset_tag` con match exacto
**Then** el sistema prioriza el Activo correspondiente
**And** permite navegar directamente al detalle del Activo

### Story 6.2: Filtros de inventario por catálogos y estado/disponibilidad

As a usuario interno,
I want filtrar el inventario por categoría, marca, ubicación y estado/disponibilidad,
So that encuentre rápidamente subconjuntos útiles del inventario (FR24).

**Acceptance Criteria:**

**Given** la vista de inventario
**When** el usuario aplica filtros (categoría/marca/ubicación/estado)
**Then** la lista se actualiza mostrando solo resultados que cumplen los filtros
**And** los filtros pueden limpiarse para volver al estado inicial

## Epic 7: Tareas Pendientes + locks de concurrencia

Permite crear y procesar tareas por renglón con finalización parcial y exclusividad por lock/claim con override Admin.

### Story 7.1: Crear Tarea Pendiente y administrar renglones

As a Admin/Editor,
I want crear una Tarea Pendiente para procesar varios renglones en lote,
So that pueda registrar operaciones de forma rápida y estructurada (FR26, FR27).

**Acceptance Criteria:**

**Given** un Admin/Editor autenticado
**When** crea una Tarea Pendiente
**Then** la tarea queda registrada con estado inicial
**And** puede añadir/editar/eliminar renglones antes de finalizar

### Story 7.2: Captura de renglones (serializado / cantidad) con validaciones mínimas

As a Admin/Editor,
I want capturar renglones serializados (pegar series) o por cantidad,
So that la carga rápida sea eficiente y con validación temprana (FR27).

**Acceptance Criteria:**

**Given** un renglón de tipo Serializado
**When** el usuario pega series (1 por línea)
**Then** el sistema valida el formato mínimo (alfanum, longitud mínima acordada)
**And** muestra contador y errores por línea si aplica
**And** permite duplicados dentro de la tarea (los resalta) sin bloquear el guardado

**Given** un renglón de tipo Cantidad
**When** el usuario ingresa una cantidad
**Then** el sistema valida que sea entero > 0
**And** no permite guardar cantidades inválidas

### Story 7.3: Procesamiento por renglón (edición + estados) y finalización parcial

As a Admin/Editor,
I want procesar una Tarea Pendiente por renglón y poder finalizar aplicando lo válido,
So that errores no bloqueen todo el lote (FR28).

**Acceptance Criteria:**

**Given** una Tarea Pendiente con renglones
**When** el usuario procesa un renglón
**Then** puede editarlo y marcarlo preparado/validado
**And** el sistema guarda estado por renglón

**Given** una Tarea Pendiente con renglones válidos e inválidos
**When** el usuario selecciona "Finalizar"
**Then** el sistema aplica los renglones válidos
**And** deja los renglones con error marcados con mensaje accionable

**Given** una Tarea Pendiente serializada con series duplicadas (en la tarea) o que ya existen en inventario
**When** el usuario selecciona "Finalizar"
**Then** el sistema marca esos renglones como Error (sin aplicar)
**And** aplica los renglones válidos restantes de forma parcial

### Story 7.4: Locks de concurrencia (claim + estado visible + heartbeat/TTL)

As a Admin/Editor,
I want que solo un editor procese una Tarea Pendiente a la vez con lock visible,
So that se eviten conflictos y doble aplicación (FR29, FR30, NFR9).

**Acceptance Criteria:**

**Given** una Tarea Pendiente sin lock activo
**When** un Editor hace clic en “Procesar”
**Then** el sistema adquiere un lock/claim para ese Editor
**And** otros usuarios ven quién tiene el lock y desde cuándo

**Given** un lock activo
**When** el Editor mantiene la pestaña activa
**Then** se envía heartbeat aproximadamente cada 10s
**And** el lock expira según TTL/timeout si no hay actividad/heartbeat
**And** el lock no se renueva si no hubo actividad real del usuario en ~2 min (idle guard)

### Story 7.5: Admin puede liberar/forzar reclamo de lock

As a Admin,
I want liberar o forzar el reclamo de un lock en Tareas Pendientes,
So that pueda destrabar operación cuando un editor se queda bloqueado (FR31).

**Acceptance Criteria:**

**Given** una Tarea Pendiente con lock activo por otro usuario
**When** Admin ejecuta “Forzar liberación” o “Forzar reclamo”
**Then** el lock se actualiza según la acción
**And** la acción queda auditada

## Epic 8: Trazabilidad y evidencia (auditoría, notas, adjuntos, papelera, errores)

Permite auditoría consultable, notas, adjuntos con permisos, papelera (soft-delete/restaurar/purgar) y errores con ID.

### Story 8.1: Auditoría consultable (best-effort)

As a Admin,
I want que el sistema registre auditoría de acciones clave y sea consultable,
So that exista trazabilidad sin bloquear la operación (FR32, NFR8).

**Acceptance Criteria:**

**Given** una acción auditable (ej. préstamo/asignación/ajuste/lock override)
**When** ocurre la acción
**Then** se registra un evento de auditoría con actor, entidad y timestamp
**And** si el registro de auditoría falla, la operación principal no se bloquea

### Story 8.2: Notas manuales en entidades relevantes

As a usuario interno,
I want agregar notas manuales a registros relevantes,
So that pueda documentar contexto operativo (FR33).

**Acceptance Criteria:**

**Given** una entidad que soporte notas
**When** un Admin/Editor agrega una nota
**Then** la nota se guarda con autor y fecha
**And** es visible a roles autorizados según política definida

### Story 8.3: Adjuntos seguros con control de acceso

As a Admin/Editor,
I want subir/ver/eliminar adjuntos asociados a registros,
So that exista evidencia documental cuando aplique (FR34, NFR6).

**Acceptance Criteria:**

**Given** un Admin/Editor autenticado
**When** sube un archivo permitido
**Then** el sistema lo guarda con nombre seguro (UUID en disco) y conserva el nombre original para UI
**And** valida tamaño/tipo según política definida

**Given** un usuario con rol Lector
**When** intenta acceder a adjuntos
**Then** el servidor bloquea el acceso
**And** la UI no expone acciones ni links de descarga en MVP

### Story 8.4: Papelera (soft-delete, restaurar, purgar)

As a Admin,
I want una papelera para restaurar o purgar elementos eliminados (soft-delete),
So that el sistema sea tolerante a errores y mantenga historial (FR35).

**Acceptance Criteria:**

**Given** un registro eliminable
**When** un usuario autorizado lo elimina
**Then** se aplica soft-delete (no borrado físico)
**And** el registro deja de aparecer en vistas normales

**Given** un registro en papelera
**When** Admin lo restaura
**Then** vuelve a estar disponible en el sistema
**And** la restauración conserva historial y relaciones según corresponda

### Story 8.5: Error ID consultable por Admin (end-to-end)

As a Admin,
I want poder consultar el detalle técnico asociado a un ID de error,
So that pueda diagnosticar incidentes reportados por usuarios (FR36, NFR10).

**Acceptance Criteria:**

**Given** un ID de error generado en producción
**When** Admin lo consulta
**Then** puede ver stack/contexto relevante
**And** queda claro cuándo ocurrió y en qué endpoint/acción

## Epic 9: Hardening y operación (post Gate 5)

Consolidación post-Gate 5: documentación crítica, runbooks de operación y eliminación de “parches” recurrentes.

### Story 9.1: Diagramas de estado (Mermaid) para entidades principales

As a equipo (Dev/QA/PO),
I want diagramas de estado claros para entidades críticas,
So that haya una fuente de verdad para UX/validaciones y no se “adivinen” transiciones.

**Acceptance Criteria:**

**Given** los estados canon definidos en `docsBmad/project-context.md`
**When** se consulta la documentación del proyecto
**Then** existen diagramas Mermaid en `gatic/docs/state-machines/` para:
- `asset-states.md` (Disponible → Asignado → Prestado → Pendiente de Retiro → Retirado)
- `pending-task-states.md` (borrador/procesando/finalización según diseño actual)
- `pending-task-line-states.md` (sin procesar/procesado/error)

**And** cada doc incluye una tabla “Acción → permitido en estado(s)” alineada a lo implementado.

### Story 9.2: Documentar patrón de locks de concurrencia (Tareas Pendientes)

As a equipo de desarrollo,
I want una guía de patrón de locks reutilizable,
So that podamos extender concurrencia sin re-inventar ni introducir race conditions.

**Acceptance Criteria:**

**Given** el patrón actual (claim + heartbeat + TTL + idle guard + override Admin)
**When** se consulta la documentación
**Then** existe `gatic/docs/patterns/concurrency-locks.md` con:
- decisión (columnas en tabla vs tabla genérica),
- trade-offs y umbral de refactor,
- snippet/pseudocódigo de claim atómico + renovación + expiración,
- UX esperada (lock visible, “lock perdido”, retry, mensajes seguros).

### Story 9.3: Consolidar y documentar conteos/scopes de inventario

As a equipo,
I want conteos de disponibilidad centralizados y documentados,
So that la semántica no se duplique en Livewire/queries y sea consistente en todo el sistema.

**Acceptance Criteria:**

**Given** la semántica actual (Disponibles / No disponibles / Retirado no cuenta por defecto)
**When** se revisa el código base
**Then** las reglas viven en un lugar claro (scopes/helpers en modelos o servicio dedicado)
**And** se documentan en `gatic/app/Models/README.md` con ejemplos de uso.

### Story 9.4: Runbook Gate 5 (retención, purga, storage privado)

As a operación/soporte,
I want un runbook para Gate 5,
So that el sistema sea operable sin sorpresas (crecimiento de datos, purga, permisos, storage).

**Acceptance Criteria:**

**Given** módulos `audit_logs`, `error_reports`, adjuntos y papelera
**When** se consulta documentación de operación
**Then** existe `gatic/docs/ops/gate-5-runbook.md` que define:
- qué se retiene y qué se purga, y cómo,
- riesgos típicos (growth, permisos, storage),
- checklist de verificación en entorno real (descargas privadas, RBAC, `error_id`).

### Story 9.5: Guía Admin/Soporte (auditoría + papelera + error_id)

As a Admin/Soporte,
I want una guía práctica de investigación,
So that pueda diagnosticar incidentes sin depender de “memoria tribal”.

**Acceptance Criteria:**

**Given** un incidente reportado con `error_id` o una entidad eliminada por error
**When** el Admin sigue la guía
**Then** existe `gatic/docs/support/admin-support-guide.md` que cubre flujos concretos:
- lookup por `error_id`,
- revisar auditoría (filtros + detalle),
- restaurar/purgar en papelera de forma segura,
- cómo escalar (qué evidencia adjuntar).

## Epic 10: UI uplift (desktop-first productividad)

Mejora incremental de UI para acercarla al UX spec (densidad, velocidad, atajos, consistencia visual) sin reescribir el sistema.

### Story 10.1: Iconografía Bootstrap Icons + consistencia visual base

As a usuario interno,
I want iconografía consistente y clara,
So that identifique acciones rápido y la UI se sienta “pro”.

**Acceptance Criteria:**

**Given** la UI usa clases `bi bi-*`
**When** el usuario navega el sistema
**Then** los íconos se renderizan correctamente en todas las pantallas
**And** se estandarizan íconos para acciones repetidas (ver, editar, eliminar, descargar, adjuntar, buscar).

### Story 10.2: Búsqueda rápida en topbar + atajos mínimos

As a usuario interno,
I want buscar sin perder el contexto,
So that el loop “Search → Resolve → Record” sea rápido.

**Acceptance Criteria:**

**Given** el usuario está en cualquier pantalla
**When** presiona `/`
**Then** el foco cae en el input de búsqueda rápida (topbar)
**And** `Esc` cierra/cancela interacciones visibles (cuando aplique).

### Story 10.3: Layout más denso (toolbars/tablas/empty states)

As a usuario interno (desktop-first),
I want tablas y layout más densos y consistentes,
So that pueda operar más rápido con menos scroll.

**Acceptance Criteria:**

**Given** pantallas de listado repetitivas (inventario, catálogos, empleados)
**When** el usuario las usa
**Then** las tablas adoptan estilo compacto donde aplique (`table-sm`)
**And** los headers incluyen toolbar consistente (título + acciones + filtros)
**And** los empty states son claros y accionables.

## Epic 12: UX avanzada (tema, command palette, columnas, breadcrumbs)

Agregar features de productividad y personalización (power-user) sin romper las reglas del bible (RBAC server-side, sin WebSockets, UX desktop-first).

### Story 12.1: Dark Mode (tema claro/oscuro) + persistencia

As a usuario interno,
I want alternar entre tema claro y oscuro,
So that reduzca fatiga visual y pueda trabajar cómodo por periodos largos.

**Acceptance Criteria:**

**Given** el usuario está autenticado en cualquier pantalla
**When** cambia el tema (toggle)
**Then** la UI aplica tema claro/oscuro de forma consistente (layout, cards, tablas, modals, badges, toasts)
**And** la preferencia persiste entre recargas (mínimo: `localStorage`; opcional: preferencia por usuario)

**Given** un usuario nuevo (sin preferencia guardada)
**When** entra por primera vez
**Then** el sistema usa un default seguro (ej. `prefers-color-scheme` o `light`)
**And** el contraste cumple accesibilidad básica (texto legible en badges/alerts/inputs).

### Story 12.2: Command Palette (Ctrl/Cmd+K) con comandos y navegación

As a usuario avanzado,
I want abrir una command palette y ejecutar acciones sin mouse,
So that reduzca fricción y navegue/actúe más rápido.

**Acceptance Criteria:**

**Given** el usuario está en cualquier pantalla
**When** presiona `Ctrl/Cmd+K`
**Then** se abre un overlay (palette) con un input de búsqueda enfocado
**And** `Esc` cierra la palette sin perder estado de la pantalla actual

**Given** la palette está abierta
**When** usa teclado (↑/↓, Enter)
**Then** puede navegar resultados y ejecutar un comando
**And** los comandos visibles respetan permisos (RBAC) del usuario

**Given** el usuario escribe un identificador exacto (ej. `serial`, `asset_tag`, `RPE`)
**When** hay match exacto
**Then** la palette ofrece un “jump” directo a la entidad correspondiente
**And** mantiene separación lógica (ej. inventario vs tareas) para no mezclar resultados “por accidente”.

### Story 12.3: Column Manager por tabla + persistencia

As a usuario interno,
I want poder mostrar/ocultar columnas en tablas principales,
So that adapte la densidad de información a mi forma de trabajo.

**Acceptance Criteria:**

**Given** una pantalla con tabla (listados principales)
**When** el usuario abre “Columnas”
**Then** puede habilitar/deshabilitar columnas soportadas sin recargar la página
**And** la selección persiste por tabla (mínimo: `localStorage`; opcional: preferencia por usuario)

**Given** una tabla con columnas críticas (ej. “Acciones”, identificador principal)
**When** el usuario intenta ocultarlas
**Then** el sistema lo impide o aplica un fallback seguro (siempre visible).

### Story 12.4: Breadcrumbs globales y consistentes

As a usuario interno,
I want breadcrumbs consistentes en pantallas de listado/detalle,
So that tenga orientación y vuelva rápido al contexto anterior.

**Acceptance Criteria:**

**Given** una pantalla navegable (módulos principales)
**When** se renderiza el header de la vista
**Then** muestra breadcrumbs consistentes con links (excepto el ítem actual)
**And** los labels son claros (UI en español) y el componente es accesible (aria-label).

## Epic 13: Alertas operativas (préstamos vencidos/por vencer, inventario bajo)

Agregar señales operativas accionables (alertas) para operación diaria: vencimientos de préstamos y stock bajo, con vistas y dashboard.

### Story 13.1: Fecha de vencimiento en préstamos de activos (modelo + UI)

As a Admin/Editor,
I want capturar una fecha de vencimiento al prestar un activo,
So that el sistema pueda alertar préstamos vencidos o por vencer.

**Acceptance Criteria:**

**Given** un usuario autorizado presta un activo
**When** captura una fecha de vencimiento
**Then** el sistema valida el dato (fecha válida; no en el pasado)
**And** persiste la fecha en BD en un campo canónico (para el préstamo vigente)

**Given** un activo prestado
**When** se consulta el detalle del activo o del empleado
**Then** se muestra la fecha de vencimiento (si existe) de forma clara.

### Story 13.2: Alertas de préstamos vencidos / por vencer (listas + dashboard)

As a Admin/Editor,
I want ver préstamos vencidos y por vencer en un solo lugar,
So that priorice devoluciones y reduzca incidencias.

**Acceptance Criteria:**

**Given** existen activos prestados con fecha de vencimiento
**When** el usuario entra al dashboard
**Then** ve contadores de “Vencidos” y “Por vencer” (ventana configurable, ej. 7/14/30 días)
**And** puede navegar a un listado filtrado desde cada contador

**Given** el usuario está en el listado de alertas
**When** revisa un préstamo
**Then** ve activo, empleado, fecha de vencimiento, días vencidos/restantes
**And** tiene acciones rápidas (ver detalle / devolver si aplica y tiene permisos).

### Story 13.3: Inventario bajo (umbrales por producto) + alertas

As a Admin/Editor,
I want configurar umbrales de “stock bajo” por producto y ver alertas,
So that reponga consumibles a tiempo y evite quedarme sin inventario.

**Acceptance Criteria:**

**Given** un producto por cantidad
**When** el stock (`qty_total`) cae a `<= low_stock_threshold`
**Then** el sistema lo considera “stock bajo”
**And** aparece en un listado de alertas y en el dashboard

**Given** un usuario autorizado edita un producto
**When** define/cambia el umbral
**Then** el sistema valida que sea un entero `>= 0`
**And** el umbral se usa en cálculos/indicadores de disponibilidad donde aplique.

## Epic 14: Datos de negocio (garantías, costos, proveedores, configuración, timeline, dashboard avanzado)

Extender el modelo para capturar datos “enterprise” (garantías/costos/vida útil) y habilitar dashboard/consultas avanzadas sin perder simplicidad operativa.

### Story 14.1: Proveedores (catálogo) + relación con Productos

As a Admin/Editor,
I want gestionar proveedores y asociarlos a productos,
So that capture origen de compra y facilite auditorías/gestión.

**Acceptance Criteria:**

**Given** un usuario autorizado
**When** crea/edita un proveedor
**Then** puede mantener campos mínimos (nombre) y campos opcionales (contacto/notas)
**And** el proveedor puede asociarse a un Producto (ej. `products.supplier_id`)

**Given** un proveedor en uso
**When** se intenta eliminar
**Then** el sistema bloquea borrado físico (o aplica soft-delete según política) y evita inconsistencias.

### Story 14.2: Contratos (compra/arrendamiento) + relación con Activos

As a Admin/Editor,
I want registrar contratos y asociarlos a activos,
So that tenga trazabilidad contractual (compra/arrendamiento/garantías extendidas).

**Acceptance Criteria:**

**Given** un usuario autorizado
**When** crea/edita un contrato
**Then** puede capturar identificador, proveedor, vigencia y notas
**And** puede vincular uno o más Activos a un contrato (relación opcional)

**Given** un activo con contrato
**When** se consulta su detalle
**Then** se muestra el vínculo al contrato y su vigencia de forma clara.

### Story 14.3: Garantías en activos (fechas + alertas)

As a Admin/Editor,
I want registrar fechas de garantía por activo,
So that el sistema muestre garantías vencidas o por vencer.

**Acceptance Criteria:**

**Given** un activo serializado
**When** el usuario captura garantía (inicio/fin/proveedor/notas)
**Then** los campos se guardan y se muestran en el detalle del activo
**And** existen consultas para “garantía vencida” y “por vencer” (ventana configurable)

### Story 14.4: Costos y valor del inventario

As a Admin/Editor,
I want registrar costos de adquisición y valor estimado de activos,
So that el dashboard pueda mostrar valor total y distribución por categorías/marcas.

**Acceptance Criteria:**

**Given** un activo
**When** el usuario captura `acquisition_cost` (y moneda)
**Then** el sistema valida formato/moneda y guarda el dato
**And** el valor total de inventario excluye `Retirado` por defecto (salvo filtros explícitos)

### Story 14.5: Vida útil y renovación (planning)

As a Admin/Editor,
I want definir vida útil y fecha estimada de reemplazo,
So that planifique renovaciones y presupuesto.

**Acceptance Criteria:**

**Given** un activo o una categoría con vida útil default
**When** el sistema tiene fecha de compra/alta y meses de vida útil
**Then** calcula (o permite capturar) `expected_replacement_date`
**And** habilita un reporte/listado de activos “por renovar” en un periodo.

### Story 14.6: Configuración del sistema (settings) para umbrales y ventanas de alerta

As a Admin,
I want configurar valores globales (días de alerta, defaults),
So that no dependa de cambios de código para ajustes operativos.

**Acceptance Criteria:**

**Given** un Admin autenticado
**When** ajusta settings (ej. días “por vencer”, moneda default, etc.)
**Then** el sistema aplica los cambios en cálculos y UI
**And** los settings tienen defaults seguros si no existen.

### Story 14.7: Perfil de usuario interno (campos extra) + preferencias UI

As a Admin,
I want capturar campos extra de usuarios (departamento/puesto) y preferencias UI,
So that el sistema sea más útil para operación y personalización.

**Acceptance Criteria:**

**Given** un usuario del sistema
**When** Admin edita su perfil (admin-only)
**Then** puede mantener campos extra (ej. `department`, `position`)
**And** las preferencias UI (tema/densidad/sidebar/columnas) pueden persistirse por usuario (opcional).

### Story 14.8: Timeline / changelog por entidad (audit + notas + movimientos + adjuntos)

As a usuario interno,
I want ver una línea de tiempo unificada por entidad,
So that tenga contexto completo en un solo lugar.

**Acceptance Criteria:**

**Given** una entidad (Activo/Producto/Empleado/Tarea)
**When** el usuario abre su detalle
**Then** ve un timeline cronológico combinando auditoría, notas y movimientos
**And** respeta RBAC (Lector sin adjuntos; acciones destructivas ocultas y bloqueadas en server).

### Story 14.9: Dashboard avanzado (métricas de negocio + actividad reciente)

As a usuario interno,
I want un dashboard más completo (alertas + métricas + actividad),
So that tenga visibilidad operativa y de negocio del sistema.

**Acceptance Criteria:**

**Given** existen datos de garantías/costos/actividad
**When** el usuario consulta el dashboard
**Then** ve cards/métricas como: garantías vencidas/por vencer, valor inventario, top categorías/marcas, actividad reciente
**And** las cards son navegables a vistas filtradas y se actualizan por polling (sin WebSockets).
