# Aceptación PO por Épica (Sign-off)

**Objetivo:** definir un proceso simple, repetible y verificable para que la **Product Owner (PO)** acepte una épica antes de marcarla como `done` en el tracking.

> Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
> Cierre de épica: `_bmad-output/implementation-artifacts/epic-<N>-retro-YYYY-MM-DD.md`

---

## Principios

- **Sin sorpresas:** la aceptación valida que el resultado coincide con el alcance acordado (stories + ACs).
- **Evidencia > opiniones:** se firma con base en artefactos, tests y un smoke mínimo.
- **No bloquear operación:** si algo es “best effort” (p.ej. auditoría), se valida el comportamiento esperado (no bloqueante) y el fallback.
- **Un solo lugar para la firma:** la firma vive en el **retro de la épica**.

---

## Cuándo aplica

- **Siempre** que una épica vaya a marcarse como `done`.
- Se ejecuta **después** de:
  - Todas las stories de la épica estén `done` en `sprint-status.yaml`.
  - Se haya corrido `code-review` por story.

---

## Checklist de aceptación (PO)

La PO revisa y confirma:

- Alcance:
  - [ ] Todas las stories de la épica están `done`.
  - [ ] No se implementó “fuera de alcance” que comprometa UX/seguridad.
- Seguridad / RBAC:
  - [ ] Lo admin-only realmente es admin-only (server-side).
  - [ ] No hay rutas/acciones accesibles por Editor/Lector que no correspondan.
- UX (mínimo):
  - [ ] Flujos principales operables (sin callejones sin salida).
  - [ ] Si una operación puede tardar `>3s`, existe UX de long-request donde aplica (loader/progreso/cancelar).
- Calidad (mínimo):
  - [ ] Suite de tests pasa (o se registran excepciones explícitas).
  - [ ] No hay regresiones obvias en flujos ya entregados.
- Evidencia:
  - [ ] Retro de la épica existe y resume hallazgos y decisiones.
  - [ ] “Known issues” (si existen) están listadas con impacto y decisión (aceptado / diferido).

---

## Bloque de firma (pegar en el retro)

Copia/pega este bloque al final de cada `epic-<N>-retro-YYYY-MM-DD.md`:

```md
## Aceptación PO (Sign-off)

- PO: ✅ / ⚠️ Aceptado con condiciones / ❌ Rechazado
- Nombre: Alice (Product Owner)
- Fecha: YYYY-MM-DD

### Evidencia revisada

- Stories: <lista de story keys>
- Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`
- Artefacto de retro: `_bmad-output/implementation-artifacts/epic-<N>-retro-YYYY-MM-DD.md`

### Smoke mínimo (resumen)

- Caso 1: ...
- Caso 2: ...
- Caso 3: ...

### Condiciones / Comentarios

- ...
```

---

## Regla operativa (enforcement)

- **No marcar `epic-<N>: done`** en `sprint-status.yaml` hasta que el retro de esa épica incluya el bloque **“Aceptación PO (Sign-off)”** con estado ✅ o ⚠️.
- Si el estado es ❌, la épica permanece `in-progress` (o se revierte) y se crea una story de corrección.

