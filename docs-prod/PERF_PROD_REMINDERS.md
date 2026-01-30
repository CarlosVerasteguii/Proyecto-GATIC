# Recordatorios Prod — Performance (GATIC)

Este archivo guarda **recordatorios orientados a producción/despliegue** (cosas “de prod”) extraídos de `PERF_FIX_PLAN.md`.

---

## 1) “Prod-like performance” en despliegue (caches + OPcache)

> Antes de producción / staging, no necesariamente durante desarrollo diario.

**Qué cambiaría (área):**
- Deployment pipeline/compose prod: correr `php artisan optimize` (o `config:cache`, `route:cache`, `view:cache`) + habilitar OPcache en PHP-FPM.

**Por qué funciona (y fuentes):**
- Laravel recomienda cachear config/routes/views en despliegue para reducir tiempo de bootstrap/registro.  
  - https://laravel.com/docs/12.x/deployment  
  - https://laravel.com/docs/11.x/configuration

**Impacto esperado:**
- Baja de TTFB “base” en entornos Linux/servidor (donde autoload/boot es parte relevante).

**Cómo validar:**
- Medir p50/p90 de `/login` y `/inventory/products` en staging/prod con caches ON vs OFF.
