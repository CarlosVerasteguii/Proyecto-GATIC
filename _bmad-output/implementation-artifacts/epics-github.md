# Epics & Stories (GitHub Sync)

> Generated automatically from GitHub Issues.
> Repo: CarlosVerasteguii/Proyecto-GATIC
> Date: 2025-12-27T00:34:56.8394403-06:00

**Mapping rules:**
- `Epic N` = GitHub issue number with label `type:epic`
- `Story N.X` = task `G*-TXX` under that epic; story title is `Issue-<number>` so story keys stay stable

## Epic 1: G0-E01: Esqueleto y entorno (Gate 0)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/1
- Milestone: Gate 0

### Story 1.1: Issue-5

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/5
- Title: G0-T01: Decidir layout del repo
- Gate: 0

**Descripcion**
Decidir layout del repo (app en raíz vs subcarpeta) y documentarlo.

**Criterios de aceptacion**
- [ ] Decisión tomada y justificada
- [ ] Documentado en el repo

### Story 1.2: Issue-6

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/6
- Title: G0-T02: Inicializar proyecto Laravel 11
- Gate: 0

**Descripcion**
Inicializar proyecto Laravel 11 (estructura base, `.env.example`).

**Criterios de aceptacion**
- [ ] Laravel 11 instalado
- [ ] `.env.example` configurado
- [ ] Estructura base creada

### Story 1.3: Issue-7

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/7
- Title: G0-T03: Instalar y configurar Laravel Sail
- Gate: 0

**Descripcion**
Instalar y configurar Laravel Sail (PHP, MySQL 8).

**Criterios de aceptacion**
- [ ] Laravel Sail instalado
- [ ] PHP configurado
- [ ] MySQL 8 funcionando
- [ ] `sail up` ejecuta sin errores

### Story 1.4: Issue-8

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/8
- Title: G0-T04: Documentar setup local en README.md
- Gate: 0

**Descripcion**
Documentar setup local en `README.md` (Sail up/down, migrate/seed, tests).

**Criterios de aceptacion**
- [ ] README con instrucciones de Sail up/down
- [ ] Comandos migrate/seed documentados
- [ ] Comandos de tests documentados
- [ ] Un nuevo dev puede levantar el proyecto siguiendo el README

## Epic 2: G0-E02: UI stack base (Bootstrap) (Gate 0)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/2
- Milestone: Gate 0

### Story 2.5: Issue-9

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/9
- Title: G0-T05: Instalar Laravel Breeze (Blade)
- Gate: 0

**Descripcion**
Instalar Laravel Breeze con stack Blade.

**Criterios de aceptacion**
- [ ] Breeze instalado con Blade
- [ ] Auth scaffolding funcionando
- [ ] Login/Register/Logout operativos

### Story 2.6: Issue-10

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/10
- Title: G0-T06: Re-maquetar Breeze a Bootstrap 5
- Gate: 0

**Descripcion**
Re-maquetar Breeze a Bootstrap 5 (eliminar Tailwind) respetando `03-visual-style-guide.md`.

**Criterios de aceptacion**
- [ ] Tailwind eliminado completamente
- [ ] Bootstrap 5 integrado
- [ ] Vistas de auth convertidas a Bootstrap
- [ ] Estilos respetan visual style guide

### Story 2.7: Issue-11

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/11
- Title: G0-T07: Configurar Vite para Bootstrap
- Gate: 0

**Descripcion**
Configurar Vite para Bootstrap (JS/CSS) + Bootstrap Icons.

**Criterios de aceptacion**
- [ ] Vite configurado para compilar Bootstrap
- [ ] Bootstrap Icons disponibles
- [ ] Hot reload funcionando
- [ ] Build de producción sin errores

### Story 2.8: Issue-12

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/12
- Title: G0-T08: Instalar Livewire 3
- Gate: 0

**Descripcion**
Instalar Livewire 3 (verificar build y carga en layout).

**Criterios de aceptacion**
- [ ] Livewire 3 instalado
- [ ] Scripts de Livewire en layout
- [ ] Componente de prueba funciona
- [ ] Sin conflictos con Bootstrap

## Epic 3: G0-E03: Seguridad (roles fijos) + rutas protegidas (Gate 0)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/3
- Milestone: Gate 0

### Story 3.9: Issue-13

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/13
- Title: G0-T09: Implementar roles fijos
- Gate: 0

**Descripcion**
Implementar roles fijos (Admin/Editor/Lector) y asignación a usuarios (seeders).

**Criterios de aceptacion**
- [ ] Tabla/campo de roles creado
- [ ] 3 roles definidos: Admin, Editor, Lector
- [ ] Seeder crea los roles
- [ ] Seeder crea usuario Admin inicial
- [ ] Usuarios pueden tener rol asignado

### Story 3.10: Issue-14

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/14
- Title: G0-T10: Definir policies/gates base
- Gate: 0

**Descripcion**
Definir policies/gates base (ver, crear, editar, adjuntos, admin-only).

**Criterios de aceptacion**
- [ ] Gates definidos para operaciones base
- [ ] Policy base para recursos
- [ ] Permisos por rol documentados
- [ ] Admin tiene acceso total
- [ ] Editor no accede a gestión de usuarios
- [ ] Lector solo lectura

### Story 3.11: Issue-15

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/15
- Title: G0-T11: Hardening de acceso
- Gate: 0

**Descripcion**
Hardening de acceso: si Editor entra a `/admin/usuarios` por URL directa → redirect dashboard + 403.

**Criterios de aceptacion**
- [ ] Middleware protege rutas admin
- [ ] Acceso no autorizado retorna 403
- [ ] Redirect a dashboard después de 403
- [ ] Log de intento de acceso (opcional)

## Epic 4: G0-E04: Calidad y CI (Gate 0)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/4
- Milestone: Gate 0

### Story 4.12: Issue-16

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/16
- Title: G0-T12: Configurar Laravel Pint
- Gate: 0

**Descripcion**
Configurar Laravel Pint (reglas) y comando CI.

**Criterios de aceptacion**
- [ ] Pint instalado
- [ ] `pint.json` con reglas configuradas
- [ ] Comando `./vendor/bin/pint --test` funciona
- [ ] Código actual pasa Pint

### Story 4.13: Issue-17

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/17
- Title: G0-T13: Configurar Larastan
- Gate: 0

**Descripcion**
Configurar Larastan (nivel inicial) y baseline si aplica.

**Criterios de aceptacion**
- [ ] Larastan instalado
- [ ] `phpstan.neon` configurado
- [ ] Nivel inicial definido (recomendado: 5)
- [ ] Baseline generado si hay errores legacy
- [ ] Código actual pasa análisis

### Story 4.14: Issue-18

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/18
- Title: G0-T14: Crear GitHub Action (CI)
- Gate: 0

**Descripcion**
Crear GitHub Action: `pint --test`, `phpunit`, `phpstan`.

**Criterios de aceptacion**
- [ ] Workflow `.github/workflows/ci.yml` creado
- [ ] Ejecuta Pint en modo test
- [ ] Ejecuta PHPUnit
- [ ] Ejecuta PHPStan
- [ ] Falla si alguno falla
- [ ] Corre en push y PR a main

### Story 4.15: Issue-19

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/19
- Title: G0-T15: Agregar tests smoke
- Gate: 0

**Descripcion**
Agregar 2–3 tests "smoke" (auth + role access).

**Criterios de aceptacion**
- [ ] Test: usuario puede hacer login
- [ ] Test: usuario sin rol no accede a rutas protegidas
- [ ] Test: Admin accede a ruta admin
- [ ] Test: Editor NO accede a ruta admin-only
- [ ] Tests pasan en CI

## Epic 20: G1-E01: Layout + navegación (Gate 1)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/20
- Milestone: Gate 1

### Story 20.1: Issue-24

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/24
- Title: G1-T01: Implementar layout base
- Gate: 1

**Descripcion**
Implementar layout base (sidebar/topbar) con slots para módulos.

**Criterios de aceptacion**
- [ ] Sidebar colapsable implementado
- [ ] Topbar implementado
- [ ] Slots para contenido de módulos
- [ ] Responsive desktop-first
- [ ] Estado colapsado persiste en sesión

### Story 20.2: Issue-25

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/25
- Title: G1-T02: Definir menú por rol
- Gate: 1

**Descripcion**
Definir menú por rol (Admin/Editor/Lector) en sidebar.

**Criterios de aceptacion**
- [ ] Menú muestra ítems según rol de usuario
- [ ] Admin ve todas las opciones
- [ ] Editor ve opciones sin gestión de usuarios
- [ ] Lector ve solo opciones de lectura
- [ ] Menú dinámico basado en policies

### Story 20.3: Issue-26

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/26
- Title: G1-T03: Implementar topbar
- Gate: 1

**Descripcion**
Implementar topbar con "buscador global" (ver Gate 2) y user menu.

**Criterios de aceptacion**
- [ ] Topbar con espacio para buscador global
- [ ] User menu con nombre de usuario
- [ ] Dropdown con opciones: Perfil, Logout
- [ ] Avatar o icono de usuario
- [ ] Alineado a visual style guide

## Epic 21: G1-E02: Componentes UX reutilizables (Gate 1)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/21
- Milestone: Gate 1

### Story 21.4: Issue-27

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/27
- Title: G1-T04: Componente Toast + Deshacer
- Gate: 1

**Descripcion**
Componente Toast (success/error) + "Deshacer" (hooks Livewire).

**Criterios de aceptacion**
- [ ] Toast success/error/warning implementado
- [ ] Botón "Deshacer" en toasts reversibles
- [ ] Timeout ~10 segundos para deshacer
- [ ] Integración con Livewire events
- [ ] Auto-dismiss después de timeout
- [ ] Stack de múltiples toasts

### Story 21.5: Issue-28

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/28
- Title: G1-T05: Skeleton loader estándar
- Gate: 1

**Descripcion**
Skeleton loader estándar (tablas/forms) alineado a guía visual.

**Criterios de aceptacion**
- [ ] Skeleton para tablas (rows + columns)
- [ ] Skeleton para formularios (inputs, buttons)
- [ ] Animación de carga (pulse/shimmer)
- [ ] Reutilizable como componente
- [ ] Respeta visual style guide (colores, spacing)

### Story 21.6: Issue-29

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/29
- Title: G1-T06: Patrón Cancelar en búsquedas
- Gate: 1

**Descripcion**
Patrón "Cancelar" en búsquedas lentas (mantener resultados previos).

**Criterios de aceptacion**
- [ ] Botón "Cancelar" visible durante búsquedas
- [ ] Cancelar detiene request en curso
- [ ] Resultados previos permanecen visibles
- [ ] Loading indicator durante búsqueda
- [ ] No bloquea UI durante búsqueda

### Story 21.7: Issue-30

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/30
- Title: G1-T07: Indicador "Actualizado hace Xs"
- Gate: 1

**Descripcion**
Indicador "Actualizado hace Xs" para vistas con polling.

**Criterios de aceptacion**
- [ ] Componente muestra timestamp relativo
- [ ] Actualización automática del tiempo relativo
- [ ] Formato: "Actualizado hace 5s" / "1m" / "5m"
- [ ] Reutilizable para cualquier vista con polling
- [ ] Opcional: icono de refresh

## Epic 22: G1-E03: Errores (Gate 1)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/22
- Milestone: Gate 1

### Story 22.8: Issue-31

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/31
- Title: G1-T08: Middleware/handler para ID de error
- Gate: 1

**Descripcion**
Middleware/handler para generar ID de error y log estructurado.

**Criterios de aceptacion**
- [ ] Excepciones generan ID único (UUID)
- [ ] Log estructurado con ID, stack, contexto
- [ ] ID disponible en response para mostrar a usuario
- [ ] Separación: mensaje amigable vs detalle técnico
- [ ] Integración con Laravel exception handler

### Story 22.9: Issue-32

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/32
- Title: G1-T09: Página/Modal de error amigable
- Gate: 1

**Descripcion**
Página/Modal de error: amigable + ID; botón "Copiar detalle" solo Admin.

**Criterios de aceptacion**
- [ ] Mensaje amigable para usuario (no stack trace)
- [ ] Muestra ID de error prominente
- [ ] Botón "Copiar detalle" solo visible para Admin
- [ ] Detalle incluye: ID, timestamp, URL, user
- [ ] Opción para reportar error o volver

## Epic 23: G1-E04: Polling base (Gate 1)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/23
- Milestone: Gate 1

### Story 23.10: Issue-33

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/33
- Title: G1-T10: Implementar patrón wire:poll.visible
- Gate: 1

**Descripcion**
Implementar patrón `wire:poll.visible` reutilizable (configurable).

**Criterios de aceptacion**
- [ ] Trait o componente base para polling
- [ ] Configurable: intervalo de polling
- [ ] Solo polling cuando componente visible
- [ ] Detiene polling cuando usuario inactivo
- [ ] Documentado para reutilización
- [ ] Integra con indicador "Actualizado hace Xs"

## Epic 34: G2-E01: Modelo de datos "columna vertebral" (Gate 2)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/34
- Milestone: Gate 2

### Story 34.1: Issue-38

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/38
- Title: G2-T01: Migraciones categorías/marcas/ubicaciones/productos
- Gate: 2

**Descripcion**
Migraciones: `categories` (`is_serialized`, `requires_asset_tag`), `brands`, `locations`, `products`.

**Criterios de aceptacion**
- [ ] Migración `categories` con campos: name, is_serialized, requires_asset_tag
- [ ] Migración `brands` con campos: name
- [ ] Migración `locations` con campos: name
- [ ] Migración `products` con campos: name, description, category_id, brand_id, etc.
- [ ] Foreign keys configuradas correctamente
- [ ] Migraciones ejecutan sin errores

### Story 34.2: Issue-39

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/39
- Title: G2-T02: Migraciones assets (serializados)
- Gate: 2

**Descripcion**
Migraciones: `assets` (serializados) con `product_id`, `serial`, `asset_tag` (nullable, unique global), `status`, `location_id`.

**Criterios de aceptacion**
- [ ] Migración `assets` con campos requeridos
- [ ] Campo `serial` (string, obligatorio)
- [ ] Campo `asset_tag` (nullable, unique global)
- [ ] Campo `status` (enum o string)
- [ ] Campo `location_id` (foreign key)
- [ ] Campo `product_id` (foreign key)
- [ ] Timestamps y soft deletes si aplica

### Story 34.3: Issue-40

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/40
- Title: G2-T03: Constraints únicos
- Gate: 2

**Descripcion**
Constraints: unique `(product_id, serial)`; `asset_tag` unique cuando exista.

**Criterios de aceptacion**
- [ ] Constraint unique compuesto (product_id, serial)
- [ ] Constraint unique en asset_tag (nullable unique)
- [ ] Prevents duplicados en DB
- [ ] Errores de validación informativos
- [ ] Tests de constraint violations

### Story 34.4: Issue-41

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/41
- Title: G2-T04: Seeders demo
- Gate: 2

**Descripcion**
Seeders: categorías demo, marcas demo, ubicación "Almacén", productos demo.

**Criterios de aceptacion**
- [ ] Seeder crea categorías demo (serializados y no serializados)
- [ ] Seeder crea marcas demo (Dell, HP, Logitech, etc.)
- [ ] Seeder crea ubicación "Almacén" + otras ubicaciones
- [ ] Seeder crea productos demo con variedad
- [ ] Seeder crea activos demo para productos serializados
- [ ] Comando `db:seed` funciona correctamente

## Epic 35: G2-E02: Listado Inventario (Productos) (Gate 2)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/35
- Milestone: Gate 2

### Story 35.5: Issue-42

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/42
- Title: G2-T05: Vista Inventario Productos (tabla)
- Gate: 2

**Descripcion**
Vista Inventario Productos (tabla) alineada a `03-visual-style-guide.md`.

**Criterios de aceptacion**
- [ ] Tabla de productos con columnas: Nombre, Categoría, Marca, QTY, Ubicación
- [ ] Diseño alineado a visual style guide
- [ ] Paginación o scroll infinito
- [ ] Responsive (desktop-first)
- [ ] Link a detalle de producto

### Story 35.6: Issue-43

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/43
- Title: G2-T06: QTY badges + tooltip
- Gate: 2

**Descripcion**
QTY badges (Total/Disponibles/No disponibles) + tooltip de desglose.

**Criterios de aceptacion**
- [ ] Badge "Total" con color neutro
- [ ] Badge "Disponibles" con color success
- [ ] Badge "No disponibles" con color warning/danger
- [ ] Tooltip muestra desglose detallado
- [ ] Tooltip incluye: Asignado, Prestado, Pendiente Retiro
- [ ] Badges actualizan con polling

### Story 35.7: Issue-44

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/44
- Title: G2-T07: Semántica QTY
- Gate: 2

**Descripcion**
Semántica QTY: No disponibles = Asignado + Prestado + Pendiente de Retiro; Disponibles = Total - No disponibles.

**Criterios de aceptacion**
- [ ] Cálculo correcto de "Total"
- [ ] Cálculo correcto de "No disponibles" (suma estados)
- [ ] Cálculo correcto de "Disponibles" (Total - No disponibles)
- [ ] Lógica centralizada (accessor o método)
- [ ] Tests unitarios de cálculos

### Story 35.8: Issue-45

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/45
- Title: G2-T08: Sin stock (resaltar rojo)
- Gate: 2

**Descripcion**
Sin stock: resaltar rojo cuando `Disponibles = 0`.

**Criterios de aceptacion**
- [ ] Fila o badge resaltado en rojo cuando Disponibles = 0
- [ ] Indicador visual claro (color, icono)
- [ ] No oculta productos sin stock (deben ser visibles)
- [ ] Filtro opcional "solo con disponibles"
- [ ] Respeta visual style guide

### Story 35.9: Issue-46

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/46
- Title: G2-T09: Filtros (categoría, marca, tipo)
- Gate: 2

**Descripcion**
Filtros: categoría, marca, tipo (serializado/cantidad), "solo con disponibles".

**Criterios de aceptacion**
- [ ] Filtro por categoría (dropdown)
- [ ] Filtro por marca (dropdown o autocomplete)
- [ ] Filtro por tipo: serializado / cantidad
- [ ] Checkbox "solo con disponibles"
- [ ] Filtros combinables (AND)
- [ ] URL parameters para compartir filtros
- [ ] Resetear filtros

### Story 35.10: Issue-47

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/47
- Title: G2-T10: Ubicación en listado
- Gate: 2

**Descripcion**
Ubicación en listado: no serializados = "Almacén"; serializados = "Varias" + tooltip.

**Criterios de aceptacion**
- [ ] Productos no serializados muestran "Almacén"
- [ ] Productos serializados muestran "Varias"
- [ ] Tooltip en "Varias" muestra desglose de ubicaciones
- [ ] Tooltip incluye: cantidad por ubicación
- [ ] Formato claro y legible

### Story 35.11: Issue-48

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/48
- Title: G2-T11: Polling 15s (badges)
- Gate: 2

**Descripcion**
Polling 15s (badges) usando `wire:poll.visible`.

**Criterios de aceptacion**
- [ ] Badges QTY actualizan cada 15s
- [ ] Usa wire:poll.visible (solo cuando visible)
- [ ] Indicador "Actualizado hace Xs" visible
- [ ] No degrada performance
- [ ] Detiene polling cuando usuario inactivo

## Epic 36: G2-E03: Búsqueda unificada (Gate 2)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/36
- Milestone: Gate 2

### Story 36.12: Issue-49

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/49
- Title: G2-T12: Autocomplete agrupado
- Gate: 2

**Descripcion**
Autocomplete agrupado: "Productos" vs "Activos".

**Criterios de aceptacion**
- [ ] Buscador global en topbar
- [ ] Autocomplete muestra resultados agrupados
- [ ] Grupo "Productos" con coincidencias
- [ ] Grupo "Activos" con coincidencias (serial/asset_tag)
- [ ] Mínimo 3 caracteres para buscar
- [ ] Debounce 300ms
- [ ] Limitar a 10 resultados por grupo

### Story 36.13: Issue-50

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/50
- Title: G2-T13: Match exacto → Detalle Activo
- Gate: 2

**Descripcion**
Regla: match exacto por `serial`/`asset_tag` → navegar directo a Detalle Activo.

**Criterios de aceptacion**
- [ ] Match exacto en serial navega directo a detalle
- [ ] Match exacto en asset_tag navega directo a detalle
- [ ] No muestra autocomplete si hay match exacto
- [ ] Case-insensitive matching
- [ ] Funciona con Enter o click

### Story 36.14: Issue-51

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/51
- Title: G2-T14: NO indexar Tareas Pendientes
- Gate: 2

**Descripcion**
Confirmar que NO indexa "Tareas Pendientes" (evitar ruido).

**Criterios de aceptacion**
- [ ] Búsqueda NO incluye resultados de Tareas Pendientes
- [ ] Solo busca en: Productos y Activos
- [ ] Documentado en código
- [ ] Test que confirma exclusión

## Epic 37: G2-E04: Detalle Producto / Activo (Gate 2)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/37
- Milestone: Gate 2

### Story 37.15: Issue-52

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/52
- Title: G2-T15: Detalle Producto tabs
- Gate: 2

**Descripcion**
Detalle Producto tabs: Resumen / Activos o Movimientos / Tareas Pendientes / Historial.

**Criterios de aceptacion**
- [ ] Tab "Resumen": info básica del producto
- [ ] Tab "Activos" (si serializado) o "Movimientos" (si no serializado)
- [ ] Tab "Tareas Pendientes": placeholder
- [ ] Tab "Historial": placeholder
- [ ] Navegación entre tabs funcional
- [ ] URL refleja tab activo

### Story 37.16: Issue-53

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/53
- Title: G2-T16: Detalle Activo tabs
- Gate: 2

**Descripcion**
Detalle Activo tabs: Info / Asignación-Préstamos / Adjuntos / Historial.

**Criterios de aceptacion**
- [ ] Tab "Info": serial, asset_tag, estado, ubicación
- [ ] Tab "Asignación-Préstamos": placeholder (se completa Gate 3)
- [ ] Tab "Adjuntos": placeholder (se completa Gate 5)
- [ ] Tab "Historial": placeholder (se completa Gate 5)
- [ ] Navegación entre tabs funcional
- [ ] URL refleja tab activo

### Story 37.17: Issue-54

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/54
- Title: G2-T17: Acciones visibles por estado (botonera placeholder)
- Gate: 2

**Descripcion**
Acciones visibles por estado (botonera), sin lógica aún (se completa Gate 3).

**Criterios de aceptacion**
- [ ] Botones visibles según estado del activo
- [ ] Disponible: "Asignar", "Prestar"
- [ ] Asignado: "Desasignar", "Pendiente Retiro"
- [ ] Prestado: "Devolver"
- [ ] Pendiente Retiro: "Procesar Retiro"
- [ ] Botones disabled (sin lógica aún)
- [ ] Tooltips explican que se habilitarán en Gate 3

## Epic 55: G3-E01: Empleados (RPE) (Gate 3)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/55
- Milestone: Gate 3

### Story 55.1: Issue-60

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/60
- Title: G3-T01: Migración employees
- Gate: 3

**Descripcion**
Migración `employees` (RPE unique, nombre, depto, puesto, extensión, correo).

**Criterios de aceptacion**
- [ ] Tabla employees creada
- [ ] Campo RPE (unique, string)
- [ ] Campos: nombre, depto, puesto, extensión, correo
- [ ] Modelo Eloquent configurado
- [ ] Seeder con empleados demo

### Story 55.2: Issue-61

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/61
- Title: G3-T02: UI listado + búsqueda + ficha empleado
- Gate: 3

**Descripcion**
UI: listado + búsqueda + ficha empleado (incluye activos asignados/prestados).

**Criterios de aceptacion**
- [ ] Listado de empleados (tabla)
- [ ] Búsqueda por nombre/RPE
- [ ] Ficha de empleado con datos completos
- [ ] Ficha muestra activos asignados
- [ ] Ficha muestra préstamos activos
- [ ] CRUD para Admin+Editor

### Story 55.3: Issue-62

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/62
- Title: G3-T03: Autocomplete + agregar empleado inline
- Gate: 3

**Descripcion**
Autocomplete por nombre/RPE + "Agregar empleado" inline en flujos.

**Criterios de aceptacion**
- [ ] Autocomplete busca por nombre o RPE
- [ ] Botón "Agregar empleado" en flujos (asignar/prestar)
- [ ] Modal inline para crear empleado rápido
- [ ] Validación de RPE único
- [ ] Empleado creado disponible inmediatamente

## Epic 56: G3-E02: Estados y acciones (serializados) (Gate 3)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/56
- Milestone: Gate 3

### Story 56.4: Issue-63

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/63
- Title: G3-T04: Definir enum/constantes de estado
- Gate: 3

**Descripcion**
Definir enum/constantes de estado: Disponible, Asignado, Prestado, Pendiente de Retiro, Retirado.

**Criterios de aceptacion**
- [ ] Enum o constantes de estado definidos
- [ ] Estados: Disponible, Asignado, Prestado, Pendiente de Retiro, Retirado
- [ ] Documentado en código
- [ ] Usado consistentemente en toda la app

### Story 56.5: Issue-64

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/64
- Title: G3-T05: Implementar transiciones + validaciones
- Gate: 3

**Descripcion**
Implementar transiciones permitidas + validaciones server-side.

**Criterios de aceptacion**
- [ ] Matriz de transiciones válidas
- [ ] Validación server-side de transiciones
- [ ] Errores claros si transición no permitida
- [ ] Tests de transiciones
- [ ] Documentado diagrama de estados

### Story 56.6: Issue-65

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/65
- Title: G3-T06: Regla: Asignado no se presta
- Gate: 3

**Descripcion**
Regla: activo Asignado no se presta (obligar desasignar).

**Criterios de aceptacion**
- [ ] Validación impide prestar activo Asignado
- [ ] Mensaje claro: "Debe desasignar primero"
- [ ] UI deshabilita botón "Prestar" en estado Asignado
- [ ] Test de validación
- [ ] Documentado en reglas de negocio

### Story 56.7: Issue-66

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/66
- Title: G3-T07: UI + comandos para todas las acciones
- Gate: 3

**Descripcion**
UI + comandos: asignar/desasignar; prestar/devolver; marcar pendiente; procesar retiro final.

**Criterios de aceptacion**
- [ ] UI para asignar/desasignar
- [ ] UI para prestar/devolver
- [ ] UI para marcar pendiente retiro
- [ ] UI para procesar retiro final
- [ ] Validaciones en cada acción
- [ ] Confirmaciones cuando aplique
- [ ] Toasts de éxito/error

## Epic 57: G3-E03: Préstamos (vencimiento) (Gate 3)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/57
- Milestone: Gate 3

### Story 57.8: Issue-67

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/67
- Title: G3-T08: Modelo loans
- Gate: 3

**Descripcion**
Modelo `loans` (empleado, activo, fechas, estado).

**Criterios de aceptacion**
- [ ] Migración tabla loans
- [ ] Campos: employee_id, asset_id, loaned_at, due_date (nullable), returned_at
- [ ] Campo estado (activo, devuelto, vencido)
- [ ] Modelo Eloquent con relaciones
- [ ] Constraints de integridad

### Story 57.9: Issue-68

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/68
- Title: G3-T09: Vencimiento opcional (badge)
- Gate: 3

**Descripcion**
Vencimiento opcional: badge "Vencimiento pendiente".

**Criterios de aceptacion**
- [ ] Vencimiento es opcional al crear préstamo
- [ ] Badge "Vencimiento pendiente" si no tiene fecha
- [ ] Badge "Vence en X días" si tiene fecha
- [ ] Badge "Vencido" si pasó fecha
- [ ] Colores según urgencia

### Story 57.10: Issue-69

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/69
- Title: G3-T10: Escalamiento 3 días → Urgente/Crítico
- Gate: 3

**Descripcion**
Escalamiento: sin vencimiento por 3 días → "Urgente/Crítico".

**Criterios de aceptacion**
- [ ] Préstamos sin vencimiento por >3 días marcados "Urgente"
- [ ] Badge especial para urgentes
- [ ] Filtro para ver solo urgentes
- [ ] Comando/job que revisa diariamente
- [ ] Notificación en dashboard

### Story 57.11: Issue-70

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/70
- Title: G3-T11: Acción "Definir vencimiento"
- Gate: 3

**Descripcion**
Acción "Definir vencimiento" desde lista/detalle.

**Criterios de aceptacion**
- [ ] Botón/acción "Definir vencimiento" en préstamos
- [ ] Modal para seleccionar fecha
- [ ] Validación: fecha futura
- [ ] Actualiza badge inmediatamente
- [ ] Auditoría de cambio

## Epic 58: G3-E04: No serializados (cantidad) (Gate 3)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/58
- Milestone: Gate 3

### Story 58.12: Issue-71

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/71
- Title: G3-T12: Definir dónde vive stock_total
- Gate: 3

**Descripcion**
Definir dónde vive el stock total por Producto (campo `stock_total` o tabla de stock).

**Criterios de aceptacion**
- [ ] Decisión documentada: campo vs tabla
- [ ] Campo stock_total en products (opción 1) O
- [ ] Tabla stock_movements (opción 2)
- [ ] Cálculo correcto de stock disponible
- [ ] Migración implementada

### Story 58.13: Issue-72

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/72
- Title: G3-T13: Asignaciones/préstamos por cantidad
- Gate: 3

**Descripcion**
Asignaciones/préstamos por cantidad (quién tiene qué cantidad) + devoluciones.

**Criterios de aceptacion**
- [ ] Asignar cantidad a empleado
- [ ] Prestar cantidad a empleado
- [ ] Devolver cantidad (total o parcial)
- [ ] Validación: no exceder disponible
- [ ] Historial de movimientos por empleado
- [ ] Cálculo correcto de disponibles

### Story 58.14: Issue-73

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/73
- Title: G3-T14: Kardex (entradas, retiros, ajustes)
- Gate: 3

**Descripcion**
Kardex (entradas, retiros definitivos, ajustes manuales).

**Criterios de aceptacion**
- [ ] Registro de entradas (nuevas compras)
- [ ] Registro de retiros definitivos
- [ ] Registro de ajustes (+ / -)
- [ ] Vista kardex por producto
- [ ] Columnas: fecha, tipo, cantidad, balance, usuario
- [ ] Solo Admin puede hacer ajustes

### Story 58.15: Issue-74

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/74
- Title: G3-T15: Ajuste manual Admin con motivo
- Gate: 3

**Descripcion**
Ajuste manual Admin: motivo obligatorio + auditoría.

**Criterios de aceptacion**
- [ ] Solo Admin puede ajustar stock
- [ ] Motivo obligatorio (campo texto)
- [ ] Cantidad puede ser + o -
- [ ] Validación: stock no puede ser negativo
- [ ] Auditoría completa (quién, cuándo, motivo)
- [ ] Confirmación antes de aplicar

## Epic 59: G3-E05: Dashboard (métricas) (Gate 3)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/59
- Milestone: Gate 3

### Story 59.16: Issue-75

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/75
- Title: G3-T16: Implementar dashboard mínimo
- Gate: 3

**Descripcion**
Implementar dashboard mínimo: "Préstamos vencidos", "Préstamos sin vencimiento", "Pendientes de retiro".

**Criterios de aceptacion**
- [ ] Card "Préstamos vencidos" con contador
- [ ] Card "Préstamos sin vencimiento" con contador
- [ ] Card "Pendientes de retiro" con contador
- [ ] Click en card navega a lista filtrada
- [ ] Diseño alineado a visual style guide
- [ ] Responsive

### Story 59.17: Issue-76

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/76
- Title: G3-T17: Polling 60s (métricas)
- Gate: 3

**Descripcion**
Polling 60s (métricas) con `wire:poll.visible`.

**Criterios de aceptacion**
- [ ] Dashboard actualiza cada 60s
- [ ] Usa wire:poll.visible
- [ ] Indicador "Actualizado hace Xs"
- [ ] No degrada performance
- [ ] Detiene polling si usuario inactivo

## Epic 77: G4-E01: Modelo de tareas (Gate 4)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/77
- Milestone: Gate 4

### Story 77.1: Issue-81

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/81
- Title: G4-T01: Migraciones pending_tasks + pending_task_lines
- Gate: 4

**Descripcion**
Migraciones: `pending_tasks` + `pending_task_lines` (carrito).

**Criterios de aceptacion**
- [ ] Tabla pending_tasks (id, user_id, status, created_at, etc.)
- [ ] Tabla pending_task_lines (id, task_id, product_id, type, data, status)
- [ ] Relaciones configuradas
- [ ] Modelos Eloquent
- [ ] Soft deletes si aplica

### Story 77.2: Issue-82

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/82
- Title: G4-T02: Estados por renglón
- Gate: 4

**Descripcion**
Estados por renglón: Pendiente / Preparado / Error / Aplicado.

**Criterios de aceptacion**
- [ ] Enum/constantes de estado por renglón
- [ ] Estados: Pendiente, Preparado, Error, Aplicado
- [ ] Transiciones válidas
- [ ] Campo error_message para estado Error
- [ ] UI muestra estado con badges

### Story 77.3: Issue-83

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/83
- Title: G4-T03: Validación series alfanuméricas
- Gate: 4

**Descripcion**
Validación: series alfanuméricas min 4; permitir duplicados en tarea, bloquear al aplicar inventario.

**Criterios de aceptacion**
- [ ] Series alfanuméricas mínimo 4 caracteres
- [ ] Permite duplicados dentro de tarea
- [ ] Valida duplicados al aplicar a inventario
- [ ] Mensajes de error claros
- [ ] Tests de validación

## Epic 78: G4-E02: Carga Rápida (carrito) (Gate 4)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/78
- Milestone: Gate 4

### Story 78.4: Issue-84

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/84
- Title: G4-T04: UI agregar productos o placeholder
- Gate: 4

**Descripcion**
UI: agregar productos existentes o placeholder (solo nombre).

**Criterios de aceptacion**
- [ ] Autocomplete para buscar productos existentes
- [ ] Opción "Agregar placeholder" (producto nuevo)
- [ ] Placeholder solo requiere nombre
- [ ] Lista tipo carrito con renglones
- [ ] Eliminar renglón de carrito
- [ ] Contador de renglones

### Story 78.5: Issue-85

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/85
- Title: G4-T05: Placeholder tipo obligatorio
- Gate: 4

**Descripcion**
Placeholder: tipo obligatorio (Serializado/Cantidad).

**Criterios de aceptacion**
- [ ] Al crear placeholder, tipo es obligatorio
- [ ] Radio buttons: Serializado / Cantidad
- [ ] Tipo determina campos siguientes
- [ ] Validación de tipo antes de continuar
- [ ] Visual claro del tipo seleccionado

### Story 78.6: Issue-86

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/86
- Title: G4-T06: Serializado (pegar series) / Cantidad (entero)
- Gate: 4

**Descripcion**
Serializado: pegar series 1 por línea + contador; Cantidad: entero > 0.

**Criterios de aceptacion**
- [ ] Si serializado: textarea para pegar series
- [ ] Pegar múltiples series (1 por línea)
- [ ] Contador automático de series
- [ ] Si cantidad: input numérico > 0
- [ ] Validación antes de agregar
- [ ] Preview de renglones a crear

## Epic 79: G4-E03: Procesamiento (renglón + aplicación diferida) (Gate 4)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/79
- Milestone: Gate 4

### Story 79.7: Issue-87

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/87
- Title: G4-T07: Pantalla procesamiento (editar renglones)
- Gate: 4

**Descripcion**
Pantalla de procesamiento: editar renglones; marcar "preparado".

**Criterios de aceptacion**
- [ ] Tabla con todos los renglones
- [ ] Editar datos de cada renglón
- [ ] Checkbox "Preparado" por renglón
- [ ] Validaciones inline
- [ ] Guardar cambios sin aplicar
- [ ] Indicador de renglones preparados vs pendientes

### Story 79.8: Issue-88

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/88
- Title: G4-T08: Finalizar con aplicación parcial
- Gate: 4

**Descripcion**
"Finalizar": aplicar inventario; si error → renglón Error con mensaje detallado; aplicar lo válido.

**Criterios de aceptacion**
- [ ] Botón "Finalizar" aplica a inventario
- [ ] Procesa renglón por renglón
- [ ] Si error: marca renglón Error + mensaje
- [ ] Continúa con siguientes renglones
- [ ] Lo válido se aplica a inventario
- [ ] Resumen final: aplicados / errores
- [ ] Transacción por renglón (no todo-o-nada)

### Story 79.9: Issue-89

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/89
- Title: G4-T09: Reintento (corregir errores)
- Gate: 4

**Descripcion**
Reintento: corregir renglones Error y volver a finalizar.

**Criterios de aceptacion**
- [ ] Filtro "Solo errores" en tabla
- [ ] Editar renglones en Error
- [ ] Botón "Reintentar" solo procesa errores
- [ ] Renglones aplicados no se reintenta
- [ ] Ciclo: corregir → reintentar → hasta 0 errores
- [ ] Historial de intentos

### Story 79.10: Issue-90

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/90
- Title: G4-T10: Sin descartar renglón (MVP)
- Gate: 4

**Descripcion**
Sin "descartar renglón" (MVP).

**Criterios de aceptacion**
- [ ] No hay funcionalidad para descartar renglón
- [ ] Renglones permanecen hasta tarea completa
- [ ] Documentado para versión futura
- [ ] Workaround: editar cantidad a 0 (si aplica)

## Epic 80: G4-E04: Locks (concurrencia) (Gate 4)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/80
- Milestone: Gate 4

### Story 80.11: Issue-91

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/91
- Title: G4-T11: Campos/tabla de lock
- Gate: 4

**Descripcion**
Campos/tabla de lock: `locked_by`, `lock_expires_at`, `heartbeat_at`.

**Criterios de aceptacion**
- [ ] Campos en pending_tasks: locked_by, lock_expires_at, heartbeat_at
- [ ] locked_by referencia a users
- [ ] Timestamps correctos
- [ ] ├ìndices para performance
- [ ] Migración ejecuta correctamente

### Story 80.12: Issue-92

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/92
- Title: G4-T12: Claim preventivo + read-only
- Gate: 4

**Descripcion**
Claim preventivo al clic en "Procesar"; read-only para otros.

**Criterios de aceptacion**
- [ ] Al entrar a "Procesar": claim lock automático
- [ ] Lock incluye: user, expira en 3m
- [ ] Otros usuarios ven tarea read-only
- [ ] Banner: "Usuario X está procesando esta tarea"
- [ ] Validación server-side de lock
- [ ] Si lock expiró, permite claim

### Story 80.13: Issue-93

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/93
- Title: G4-T13: Heartbeat + TTL + timeout + idle guard
- Gate: 4

**Descripcion**
Heartbeat: 10s; TTL 3m; timeout 15m rolling; idle guard (no renovar si inactivo).

**Criterios de aceptacion**
- [ ] Heartbeat cada 10s actualiza lock
- [ ] TTL 3m: lock expira si no hay heartbeat
- [ ] Timeout 15m rolling: libera después de 15m total
- [ ] Idle guard: detecta inactividad (no mouse/teclado)
- [ ] No renueva lock si usuario inactivo >2m
- [ ] JavaScript para heartbeat
- [ ] Endpoint para renovar lock

### Story 80.14: Issue-94

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/94
- Title: G4-T14: Solicitar liberación (modal)
- Gate: 4

**Descripcion**
"Solicitar liberación": modal informativo (sin notificaciones automáticas).

**Criterios de aceptacion**
- [ ] Botón "Solicitar liberación" si tarea locked
- [ ] Modal muestra: quién tiene el lock, desde cuándo
- [ ] Texto: "Contacta a [usuario] para liberar"
- [ ] No envía notificación automática (MVP)
- [ ] Botón copiar email del usuario
- [ ] Cerrar modal

### Story 80.15: Issue-95

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/95
- Title: G4-T15: Admin forzar liberación
- Gate: 4

**Descripcion**
Admin "Forzar liberación" (auditado).

**Criterios de aceptacion**
- [ ] Botón "Forzar liberación" solo para Admin
- [ ] Confirmación: "¿Seguro? Se perderá trabajo no guardado"
- [ ] Libera lock inmediatamente
- [ ] Auditoría: quién forzó, cuándo, tarea
- [ ] Notifica al usuario que tenía el lock (opcional)
- [ ] Log de evento

## Epic 96: G5-E01: Auditoría best-effort + notas (Gate 5)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/96
- Milestone: Gate 5

### Story 96.1: Issue-99

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/99
- Title: G5-T01: Tabla audit_logs
- Gate: 5

**Descripcion**
Tabla `audit_logs` (actor, entidad, acción, cambios JSON, timestamp).

**Criterios de aceptacion**
- [ ] Tabla audit_logs creada
- [ ] Campos: user_id, auditable_type, auditable_id, action, changes (JSON)
- [ ] Timestamps
- [ ] ├ìndices para consultas
- [ ] Modelo Eloquent

### Story 96.2: Issue-100

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/100
- Title: G5-T02: Disparo async + fallback silent
- Gate: 5

**Descripcion**
Disparo async (queue DB) y fallback silent si falla (log warning).

**Criterios de aceptacion**
- [ ] Job para guardar audit log
- [ ] Queue database configurada
- [ ] Try-catch con fallback silent
- [ ] Log warning si falla auditoría
- [ ] No bloquea operación principal
- [ ] Trait reutilizable para models

### Story 96.3: Issue-101

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/101
- Title: G5-T03: Notas manuales + UI
- Gate: 5

**Descripcion**
Notas manuales: tabla `notes` (entidad, autor, texto) + UI en tabs Historial.

**Criterios de aceptacion**
- [ ] Tabla notes (notable_type, notable_id, user_id, text)
- [ ] Componente agregar nota en tab Historial
- [ ] Lista de notas con autor y fecha
- [ ] Solo Admin/Editor pueden agregar
- [ ] Todos pueden ver
- [ ] Markdown opcional en notas

## Epic 97: G5-E02: Adjuntos (Gate 5)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/97
- Milestone: Gate 5

### Story 97.4: Issue-102

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/102
- Title: G5-T04: Tabla attachments
- Gate: 5

**Descripcion**
Tabla `attachments` (entidad, uploader, nombre original, ruta UUID, mime, size).

**Criterios de aceptacion**
- [ ] Tabla attachments creada
- [ ] Campos: attachable_type, attachable_id, uploader_id
- [ ] Campos: original_filename, stored_path, mime_type, size_bytes
- [ ] Timestamps
- [ ] Modelo Eloquent con relaciones

### Story 97.5: Issue-103

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/103
- Title: G5-T05: Storage UUID + sanitizar
- Gate: 5

**Descripcion**
Storage: guardar con UUID; sanitizar; mostrar nombre original.

**Criterios de aceptacion**
- [ ] Archivos guardados con UUID en nombre
- [ ] Sanitización de nombre original
- [ ] Storage en filesystem configurado
- [ ] UI muestra nombre original
- [ ] Download usa nombre original
- [ ] Validación de tipos permitidos

### Story 97.6: Issue-104

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/104
- Title: G5-T06: Permisos Admin/Editor
- Gate: 5

**Descripcion**
Permisos: solo Admin/Editor; Lector sin acceso.

**Criterios de aceptacion**
- [ ] Admin puede subir/descargar/eliminar adjuntos
- [ ] Editor puede subir/descargar/eliminar adjuntos
- [ ] Lector NO puede subir/eliminar (puede ver si tiene acceso)
- [ ] Validación server-side
- [ ] UI condicional según rol

### Story 97.7: Issue-105

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/105
- Title: G5-T07: Límites 100MB + validaciones
- Gate: 5

**Descripcion**
Límites: max 100MB por archivo (PRD) + validaciones.

**Criterios de aceptacion**
- [ ] Validación: máximo 100MB por archivo
- [ ] Validación: tipos MIME permitidos
- [ ] Mensajes de error claros
- [ ] Progress bar durante upload
- [ ] Configuración en .env para límites
- [ ] Tests de validación

## Epic 98: G5-E03: Papelera (Gate 5)

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/98
- Milestone: Gate 5

### Story 98.8: Issue-106

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/106
- Title: G5-T08: Soft deletes consistentes
- Gate: 5

**Descripcion**
Soft deletes consistentes (productos, activos, empleados, etc. según aplique).

**Criterios de aceptacion**
- [ ] SoftDeletes trait en models aplicables
- [ ] Migraciones: deleted_at en tablas
- [ ] Queries excluyen soft deleted por defecto
- [ ] withTrashed() para incluir eliminados
- [ ] onlyTrashed() para solo eliminados
- [ ] Documentado qué entidades usan soft delete

### Story 98.9: Issue-107

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/107
- Title: G5-T09: UI Papelera (listar, restaurar, vaciar)
- Gate: 5

**Descripcion**
UI Papelera: listar, restaurar, vaciar (Admin).

**Criterios de aceptacion**
- [ ] Vista Papelera con tabs por tipo (Productos, Activos, Empleados)
- [ ] Acción "Restaurar" por ítem
- [ ] Acción "Vaciar papelera" solo Admin
- [ ] Confirmación antes de vaciar
- [ ] Filtros y búsqueda
- [ ] Muestra fecha de eliminación

### Story 98.10: Issue-108

- GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/108
- Title: G5-T10: Restauración conserva historial
- Gate: 5

**Descripcion**
Restauración conserva historial completo (borrado es un evento más).

**Criterios de aceptacion**
- [ ] Restaurar no borra historial
- [ ] Eliminación registrada en audit log
- [ ] Restauración registrada en audit log
- [ ] Historial muestra eliminación + restauración
- [ ] Relaciones se mantienen intactas
- [ ] Tests de restauración completa


