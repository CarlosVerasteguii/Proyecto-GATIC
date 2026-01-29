# Smoke Test Checklist - GATIC

Checklist de QA manual para validación rápida después de cada release.

## Credenciales de prueba

| Rol    | Email               | Password |
|--------|---------------------|----------|
| Admin  | admin@gatic.local   | password |
| Editor | editor@gatic.local  | password |
| Lector | lector@gatic.local  | password |

## Pre-requisitos

```bash
docker compose -f gatic/compose.yaml up -d
docker compose -f gatic/compose.yaml exec -T laravel.test php artisan migrate:fresh --seed
```

---

## 1. Autenticación y Roles

### Admin

- [ ] Login con admin@gatic.local
- [ ] Dashboard muestra métricas
- [ ] Sidebar muestra: Inicio, Usuarios, Papelera, Errores (soporte), Búsqueda, Productos, Tareas Pendientes, Empleados, Categorías, Marcas, Ubicaciones, Papelera catálogos
- [ ] Puede acceder a /admin/users

### Editor

- [ ] Login con editor@gatic.local
- [ ] Dashboard muestra métricas
- [ ] Sidebar muestra: Inicio, Búsqueda, Productos, Tareas Pendientes, Empleados, Categorías, Marcas, Ubicaciones
- [ ] NO ve: Usuarios, Papelera, Errores (soporte), Papelera catálogos
- [ ] Acceso a /admin/users devuelve 403

### Lector

- [ ] Login con lector@gatic.local
- [ ] Dashboard muestra métricas
- [ ] Sidebar muestra SOLO: Inicio, Búsqueda, Productos
- [ ] NO ve acciones de creación/edición en productos
- [ ] NO ve acciones de asignar/desasignar/prestar en activos
- [ ] Acceso a /pending-tasks devuelve 403

---

## 2. Inventario - Productos

### Listado (/inventory/products)

- [ ] Tabla carga con productos demo
- [ ] Filtros: Categoría, Marca, Disponibilidad funcionan
- [ ] Botón "Limpiar" aparece cuando hay filtros activos
- [ ] Búsqueda por nombre filtra la tabla
- [ ] Click en nombre de producto navega al detalle
- [ ] Columnas visibles: Nombre, Categoría, Marca, Tipo, Total, Disponibles, No disponibles, Acciones
- [ ] Badge "Sin disponibles" aparece cuando corresponde

### Detalle de Producto (/inventory/products/{id})

- [ ] Header muestra nombre, estado, KPIs
- [ ] KPIs muestran Total, Disponibles, No disponibles correctamente
- [ ] Botón "Activos" navega al listado de activos
- [ ] Admin/Editor ven botón "Editar"
- [ ] Lector NO ve botón "Editar"

---

## 3. Inventario - Activos

### Listado (/inventory/products/{id}/assets)

- [ ] Tabla carga con activos demo
- [ ] Filtros: Ubicación, Estado funcionan
- [ ] Badges de estado son consistentes (mismo color/icono)
- [ ] Admin/Editor ven menú "Acciones" en cada fila
- [ ] Lector solo ve botón "Ver", NO ve "Acciones" ni "Editar"

### Detalle de Activo (/inventory/products/{pid}/assets/{aid})

- [ ] Header muestra serial, badge de estado, producto
- [ ] Estado "Disponible": muestra acciones Asignar, Prestar
- [ ] Estado "Asignado": muestra acciones Desasignar
- [ ] Estado "Prestado": muestra acción Devolver
- [ ] Tenencia actual muestra empleado si aplica
- [ ] Panel de Notas visible (Admin/Editor pueden agregar)
- [ ] Panel de Adjuntos visible para Admin/Editor
- [ ] Lector NO ve formularios de notas ni adjuntos

---

## 4. Flujos de Operación

### Asignar Activo

- [ ] Desde activo "Disponible", click en "Asignar"
- [ ] Buscar empleado por RPE o nombre
- [ ] Seleccionar empleado muestra pill visual con RPE + nombre
- [ ] Nota requerida (mínimo 5 caracteres)
- [ ] Submit con loading spinner
- [ ] Después de asignar, estado cambia a "Asignado"

### Desasignar Activo

- [ ] Desde activo "Asignado", click en "Desasignar"
- [ ] Nota requerida
- [ ] Después de desasignar, estado cambia a "Disponible"

---

## 5. Búsqueda Global

### Búsqueda Unificada (/inventory/search)

- [ ] Buscar por nombre de producto → lista productos
- [ ] Buscar por serial exacto (SN-DEMO-001) → redirige a detalle de activo
- [ ] Buscar por asset tag exacto (AT-001) → redirige a detalle de activo
- [ ] Buscar término inexistente → estado vacío amigable

---

## 6. Tareas Pendientes (Admin/Editor)

- [ ] Acceder a /pending-tasks
- [ ] Ver tarea en estado "Listo"
- [ ] Click "Procesar" adquiere lock
- [ ] Indicador de lock visible
- [ ] Admin puede liberar lock de otro usuario

---

## 7. Accesibilidad (A11y)

### Focus Visible

- [ ] Tab navega todos los controles interactivos en orden lógico
- [ ] Focus visible (borde verde) en inputs, botones, links
- [ ] Dropdowns navegables con teclado (↑↓ Enter Escape)

### Labels y ARIA

- [ ] Inputs de formulario tienen labels asociados
- [ ] Botones icon-only tienen aria-label
- [ ] Badges de estado tienen texto + ícono (no solo color)

---

## 8. Responsive

### 375px (Mobile)

- [ ] Sin scroll horizontal
- [ ] Filtros colapsados en botón "Filtros"
- [ ] Sidebar en offcanvas (hamburger menu)
- [ ] Botones de acciones accesibles

### 768px (Tablet)

- [ ] Layout ajustado sin overflow
- [ ] Sidebar visible

### 1440px (Desktop)

- [ ] Full layout con sidebar
- [ ] Tablas con todas las columnas visibles

---

## 9. Comandos de Verificación

```bash
# Tests
docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test

# Linter
docker compose -f gatic/compose.yaml exec -T laravel.test vendor/bin/pint --test

# Análisis estático
docker compose -f gatic/compose.yaml exec -T laravel.test vendor/bin/phpstan analyse
```

---

## Resultado

| Sección | Estado | Notas |
|---------|--------|-------|
| 1. Autenticación | ⬜ | |
| 2. Productos | ⬜ | |
| 3. Activos | ⬜ | |
| 4. Flujos Operación | ⬜ | |
| 5. Búsqueda Global | ⬜ | |
| 6. Tareas Pendientes | ⬜ | |
| 7. Accesibilidad | ⬜ | |
| 8. Responsive | ⬜ | |
| 9. Comandos | ⬜ | |

**Fecha:** _______________  
**Tester:** _______________  
**Versión:** _______________
