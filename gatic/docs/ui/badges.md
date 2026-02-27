# Paleta de Badges (GATIC UI)

Esta gu&iacute;a define qu&eacute; estilos de badge existen en el sistema y **cu&aacute;ndo** usar cada uno, para mantener consistencia visual (Cat&aacute;logos, Inventario, Operaciones y Admin).

Para ver todos los estilos en un solo lugar (solo `local/testing`): `GET /dev/ui-badges` (`dev.ui-badges`).

## Objetivo

- Consistencia: menos variantes, m&aacute;s reglas claras.
- Jerarqu&iacute;a: el badge no debe competir con el dato principal.
- Accesibilidad real: contraste, legibilidad, no depender solo de color.
- Mantenibilidad: reutilizar componentes y tokens existentes.

## Taxonom&iacute;a (categor&iacute;as)

### 1) Estatus (Entidad)

**Qu&eacute; es:** estado estable de una entidad (ej. Activo: Disponible/Prestado/Retirado).  
**Badge recomendado:** `<x-ui.status-badge>` (tokenizado en `resources/sass/_tokens.scss`).  
**Por qu&eacute;:** ya est&aacute; estandarizado, con colores y opciones (`solid`, `icon`).

Ejemplos:

```blade
<x-ui.status-badge :status="$asset->status" />
<x-ui.status-badge :status="$asset->status" solid />
<x-ui.status-badge :status="$asset->status" :icon="false" />
```

Reglas:

- En tablas/listados: preferir variante normal (no `solid`).
- En header/detalle: usar `solid` solo si el estado es cr&iacute;tico o se quiere mucha prominencia.
- Si hay 5+ estados, el &iacute;cono ayuda a escaneo; si hay 2 estados, puede omitirse.

### 2) Estatus (Flujo / Proceso)

**Qu&eacute; es:** estados de un workflow (Borrador/Listo/Procesando/Finalizado/Error).  
**Badge recomendado:** chip sutil tipo `.ops-status-chip` (pill con acento).  
**Por qu&eacute;:** es legible sin dominar la fila; funciona bien en tablas densas.

Reglas:

- Usar colores sem&aacute;nticos (`success/warning/danger/info/secondary`) para que el usuario no aprenda una paleta nueva por m&oacute;dulo.
- No usar glow/animaciones: distraen y cansan.

### 3) Conteos / KPIs / Contexto

**Qu&eacute; es:** contadores ("Resultados 3"), contexto ("Filtrado Activos").  
**Badge recomendado:** `.dash-chip` (Cat&aacute;logos/Dashboard) o `.admin-settings-summary-pill` (Admin settings).

Reglas:

- Preferir tonos neutros (sin estados sem&aacute;nticos) para no confundir con alertas.
- Deben ser compactos y secundarios al t&iacute;tulo.

### 4) Roles / RBAC

**Qu&eacute; es:** etiquetas de rol (Admin/Editor/Lector).  
**Badge recomendado:** pills del Admin (`.admin-users-role`).

Reglas:

- Usar siempre el mismo color por rol en toda la app (no redefinir por pantalla).
- No usar estos colores para estatus de procesos: rol y estado son conceptos distintos.

### 5) Disponibilidad / Toggle

**Qu&eacute; es:** activo/inactivo, habilitado/deshabilitado.  
**Badge recomendado:** `.admin-users-status` (con &iacute;cono opcional).

Reglas:

- Debe poder entenderse sin color (texto + icono ayuda).

### 6) Etiquetas (Metadata)

**Qu&eacute; es:** tags neutrales (labels, tipo, proveedor, categor&iacute;a, etc.).  
**Badge recomendado:** badge neutro (ej. `badge bg-light text-dark border`).

Reglas:

- Evitar colores sem&aacute;nticos aqu&iacute; (si no es una alerta/estatus, no uses `warning/danger`).
- Mantener bajo &eacute;nfasis.

### 7) Alertas r&aacute;pidas (Severidad)

**Qu&eacute; es:** se&ntilde;ales fuertes (Vencido, Stock bajo, Sin disponibles).  
**Badge recomendado:** Bootstrap `text-bg-warning` / `text-bg-danger` (muy visible).

Reglas:

- Usar con moderaci&oacute;n: si todo est&aacute; en rojo/amarillo, nada destaca.
- Siempre acompa&ntilde;ar con texto claro; no depender solo del color.

## Paleta de tonos (sem&aacute;ntica)

- `success`: completado, correcto, disponible.
- `warning`: en proceso, requiere atenci&oacute;n, por vencer.
- `danger`: error, cancelado, vencido, sin disponibles.
- `info`: listo, informativo, acciones recomendadas.
- `secondary`: borrador, pendiente, neutro.
- `primary` (brand): reservar para acciones primarias o estados especiales de negocio, no como default de estatus.

## Anti-patrones

- Badges con brillo/glow o animaci&oacute;n constante.
- Usar el mismo color para conceptos distintos (ej. `warning` para metadata y para alertas).
- Poner 3+ badges de colores en una sola fila sin jerarqu&iacute;a.
- Badges como sustituto de texto (si el badge no se entiende sin color, falla).

