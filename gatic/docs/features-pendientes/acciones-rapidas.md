# Acciones rápidas — qué era vs qué tenemos

## Qué era en el repo anterior (GATI-C Proyect)

En el repo anterior “Acciones rápidas” estaba muy ligado a **captura mínima** para crear solicitudes (Tareas Pendientes) sin llenar el formulario completo:

- **Carga Rápida**
  - Crea una solicitud de ingreso pendiente.
  - Permite seleccionar producto existente o crear un **placeholder** (solo nombre).
  - Para serializados: pegar múltiples seriales (1 por línea).
  - Para no serializados: capturar cantidad.
  - Catálogos (Marca/Categoría/Ubicación) podían quedarse en blanco para completarse después.
- **Retiro/Baja Rápida**
  - Modal tipo “carrito” para marcar retiros rápidos.
  - Para serializados: pegar seriales.
  - Para no serializados: producto + cantidad.
  - Motivo + notas.
  - Después se “procesa” con un formulario completo.

Entry points que existían/planteaban:

- Login (si se habilitaba `showQuickActions`) con iconos para abrir modales.
- Mención de “IP confiable para acciones rápidas sin iniciar sesión” (idea de modo kiosko).

## Qué tenemos hoy en este repo (Proyecto GATIC)

Hoy sí existen “acciones rápidas”, pero con otro enfoque:

- En listados de activos hay un **dropdown por fila** que habilita movimientos según estado:
  - `Asignar a empleado`, `Prestar`, `Devolver`, `Desasignar`.
  - Está protegido por RBAC (`inventory.manage`) y por reglas de transición de estado.
- Lo que todavía pasa es:
  - La acción rápida te manda a un **formulario de movimiento** (seleccionar empleado + nota, etc.).
  - No tenemos un “modal inline” estilo carrito para mover/cargar sin salir del contexto.

## Gap principal (por qué no se siente igual)

En el repo anterior “rápido” = **crear una intención** con mínimos datos (tarea pendiente) y completar después.

En el repo actual “rápido” = **atajo de navegación** a un formulario de movimiento (mismo flujo, menos clics).

Ambas ideas son valiosas, pero son distintas.

## Flujo actual vs flujo propuesto (ASCII)

### 1) Movimientos rápidos (sobre activos ya existentes)

**Ahora (ejemplo desde búsqueda):**

```
[Búsqueda] -> (Ver detalle) -> [Activo] -> (Asignar/Prestar/...) -> [Formulario] -> (Guardar) -> [Activo]
```

**Cómo debería sentirse:**

```
[Búsqueda] -> (Acciones ▾) -> (Asignar/Prestar/...) -> [Formulario] -> (Guardar) -> [Regresar a Búsqueda]
```

Clave UX: conservar el contexto (query/filtros/página) usando `returnTo` seguro.

### 2) Captura rápida (ingresos/retiros con mínimos datos)

**Idea (equivalente a lo del repo anterior):**

```
[Dashboard / Navbar] -> (Carga Rápida) -> [Modal minimal]
    -> (Crear tarea) -> [Tareas Pendientes]
        -> (Procesar) -> [Formulario completo] -> (Finalizar) -> [Inventario]
```

Beneficio: operación diaria no se frena por “faltan datos”.

## Campos: “registro normal” antes vs ahora

### En el repo anterior (PRD)

**Formulario completo para Carga/Edición (resumen):**

```
Info básica:
  - Producto (nombre)
  - Serial / Identificador único (si serializado)
  - Categoría (combo)
  - Marca (combo)
  - Modelo
  - Descripción detallada

Adquisición:
  - Proveedor
  - Fecha de adquisición/compra
  - Contrato / factura
  - Costo (opcional)
  - Condición al ingreso

Ubicación/Estado:
  - Ubicación actual
  - Estado (informativo)

Adjuntos:
  - Documentos (SISE/Contrato)

Otros:
  - Notas internas
  - Auditoría (auto)
```

### En este repo (hoy)

**Producto (catálogo):**

```
- Nombre
- Categoría
- Marca (opcional)
- Proveedor (opcional)
- (Si NO es serializado) Stock total + umbral stock bajo
```

**Activo (unidad serializada):**

```
- Serial
- Asset tag (si la categoría lo requiere)
- Ubicación
- Estado (+ empleado actual si aplica)
- Garantía (inicio/fin, proveedor, notas) (opcional)
- Costo de adquisición + moneda (opcional)
- Vida útil / reemplazo esperado (opcional)
```

### Qué noto (diferencias útiles)

- En el repo anterior estaban explícitos: **modelo, descripción, fecha de compra, contrato/factura, condición**.
- En este repo ya avanzamos en: **garantía + costo + vida útil/reemplazo**, pero faltan algunos campos “de captura diaria” del PRD anterior.
- Para “rápido”: hoy los movimientos requieren **nota** y **empleado**; se puede acelerar con:
  - notas sugeridas (plantillas),
  - precarga del empleado cuando aplica,
  - UI inline/offcanvas para evitar navegación.

## Recomendación: traer la idea y robustecerla

Separar “Acciones rápidas” en dos familias:

1) **Acciones rápidas (Movimientos)**: atajos para operar estados de activos desde cualquier lista/búsqueda, con `returnTo`.
2) **Acciones rápidas (Captura mínima)**: “Carga/Retiro Rápido” como creación de tarea pendiente con mínimos datos, completando después.

