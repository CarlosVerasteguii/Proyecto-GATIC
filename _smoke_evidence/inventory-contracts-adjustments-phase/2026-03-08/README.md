Smoke del bloque `Inventory Contracts / Adjustments` ejecutado el 2026-03-08.

Evidencia disponible:
- `login-snapshot.yaml`: snapshot de `playwright-cli` en la pantalla de login.

Bloqueo observado:
- El servidor local respondió con latencias anómalas y respuestas interrumpidas durante navegación autenticada.
- `playwright-cli` pudo abrir sesión y resolver la pantalla de login, pero la navegación posterior no fue estable para capturar desktop/mobile completos del bloque.

Rutas intentadas:
- `/login`
- `/inventory/contracts`
- `/inventory/contracts/create`
- `/inventory/adjustments`

Nota:
- La validación visual completa del bloque quedó parcialmente bloqueada por el estado del entorno, no por errores de Blade/PHP confirmados en el diff.
