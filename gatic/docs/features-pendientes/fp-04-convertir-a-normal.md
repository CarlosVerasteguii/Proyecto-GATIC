# FP-04 — Convertir captura rápida a tarea normal editable

Fecha: 2026-02-11

## Objetivo (MVP)

Después de **procesar** una captura rápida (`payload.schema = fp03.quick_capture`), la tarea:

- deja de estar “especial/bloqueada”
- se comporta como una **PendingTask normal** (editable/procesable según `status`)
- mantiene trazabilidad **solo lectura** del origen (payload + resumen de conversión)

## Regla clave: “pendiente” vs “convertida”

En el código existen dos conceptos distintos:

- **Origen quick capture**: `PendingTask::hasQuickCapturePayload()` → `true` siempre que `payload.schema === fp03.quick_capture` (incluso después de convertir).
- **Quick capture pendiente (bloqueante)**: `PendingTask::isQuickCaptureTask()` → `true` solo si:
  - tiene `schema = fp03.quick_capture`, y
  - `payload.converted_at` es `null` (sin conversión)

Regla MVP: cualquier guardrail que “bloquea quick capture” debe depender de **`isQuickCaptureTask()`** (pendiente), no de `hasQuickCapturePayload()` (origen).

## Estado final esperado después de “Procesar captura rápida”

La acción `ProcessQuickCapturePendingTask` fija:

- `payload.converted_at` (ISO8601) y `payload.conversion` (resumen/histórico)
- (según modo) el `status` final:

1) **Modo `lines` (genera renglones)**  
   - `status` queda en **Draft**  
   - la tarea ya NO es quick capture pendiente (`isQuickCaptureTask() === false`)  
   - el usuario puede completar el flujo normal: editar renglones → “Marcar como lista” → “Procesar” (locks) → “Finalizar”

2) **Modo `assets_stock_in` / `assets_retirement` (aplica directo a inventario)**  
   - `status` queda en **Completed** o **PartiallyCompleted** (si hubo alertas)  
   - no requiere flujo de renglones; la tarea se muestra como historial/auditoría normal (read-only por estado final)

## Acciones habilitadas (UI + backend)

### A) Quick capture pendiente (`isQuickCaptureTask() === true`)

- UI: mostrar CTA **“Procesar captura rápida”**.
- UI + backend: bloquear acciones normales (según reglas actuales):
  - agregar/editar/eliminar renglones
  - “Marcar como lista”
  - “Procesar” / locks / “Finalizar”

### B) Quick capture convertida (`hasQuickCapturePayload() === true` y `isQuickCaptureTask() === false`)

- UI: ocultar/deshabilitar CTA “Procesar captura rápida”.
- UI: mostrar un badge/nota **“Origen: Captura rápida”** (trazabilidad).
- UI + backend: permitir el flujo normal, respetando reglas existentes:
  - edición de renglones solo si el `status` lo permite (Draft)
  - “Marcar como lista” requiere al menos 1 renglón pendiente
  - “Procesar/Finalizar” siempre con RBAC `inventory.manage` + locks

## No re-procesar captura rápida

- UI: el CTA “Procesar captura rápida” no debe aparecer cuando ya existe `payload.converted_at`.
- Server-side (Livewire/Action): si se invoca el método de quick process con `converted_at` presente, debe bloquear con mensaje claro.

