# Checklist pre-flight (antes de implementar una story)

Esta checklist es para **Dev humano o Dev LLM**. La intención es reducir “olvidos recurrentes” (RBAC, long-request UX, pruebas deterministas) antes de escribir código.

## 1) Claridad de alcance

- [ ] Leí el story file completo y entiendo ACs + fuera de alcance.
- [ ] Identifiqué dependencias (models/Actions/Livewire/rutas) y riesgos (concurrencia, performance, seguridad).
- [ ] Si hay ambigüedad de producto, la resolví en un doc (o la dejé explícita en el story) antes de codear.

## 2) Seguridad / RBAC (server-side)

- [ ] Identifiqué el Gate/Policy que aplica y dónde se autoriza (no solo UI).
- [ ] Hay tests que prueban **403** para roles que no deben acceder.
- [ ] No hay endpoints “bordes” (descargas/JSON) sin autorización.

## 3) UX (consistencia BMAD)

- [ ] Si una consulta/acción puede tardar `>3s`, agregué UX de long-request (loader/progreso/cancelar) donde aplica.
- [ ] Copy/UI en español; rutas/identificadores/código en inglés.
- [ ] Estados visibles/polling donde el usuario lo necesita (sin WebSockets).

## 4) Robustez (best effort, transacciones, errores)

- [ ] Si hay auditoría/side-effects, es best-effort: un fallo NO bloquea la operación principal.
- [ ] Operaciones críticas usan transacciones (cuando corresponde) y son idempotentes cuando aplica.
- [ ] Errores quedan registrables con `error_id` (y detalle solo Admin en prod si aplica).

## 5) Performance (mínimo)

- [ ] Evité N+1 (eager loading / subqueries según patrón del módulo).
- [ ] Si agrego listados/filters, hay índices DB razonables (o una justificación explícita).
- [ ] El listing es paginado.

## 6) Pruebas (deterministas)

- [ ] Agregué tests feature/unit para ACs clave.
- [ ] Tests deterministas (sin `sleep`); uso `Carbon::setTestNow()` si hay tiempo/TTL.
- [ ] Cubrí regresión mínima para edge cases ya conocidos del repo (soft-delete, RBAC, long-request, locks).

## 7) Tracking y cierre de story

- [ ] Actualicé `sprint-status.yaml` (estado, notas relevantes).
- [ ] Dejé notas de implementación/decisiones en el story file (si aporta a mantenimiento).

