# GATIC - Sistema de Gestión de Inventario y Activos

Sistema interno (intranet/on-prem) para gestionar **Inventario** y **Activos**, con **trazabilidad** y **operación diaria** (asignaciones/préstamos).

## Decisión de Layout del Repositorio

La aplicación Laravel vive en la subcarpeta `gatic/`, **no en la raíz del repositorio**.

### Justificación

Esta decisión permite:

1. **Separación clara** entre artefactos de planeación/BMAD y código de aplicación
2. **Organización del proyecto** manteniendo docs, brainstorming y planning en la raíz
3. **Evitar contaminación** del código con artefactos de gestión del proyecto
4. **Flexibilidad** para múltiples ambientes (local, staging, production) sin conflictos

### Árbol Mínimo del Repositorio

```
.
├── _bmad/                          # Sistema BMAD (gestión del proyecto)
├── _bmad-output/                   # Artefactos generados (PRD, arquitectura, epics, stories)
├── docsBmad/                       # Documentación de planeación y contexto
├── gatic/                          # ⭐ APLICACIÓN LARAVEL 11
│   ├── app/
│   ├── artisan
│   ├── composer.json
│   ├── .env.example
│   └── ...
├── .gitignore                      # Ignores globales del repo
├── COMMIT_CONVENTIONS.md           # Convenciones de commits
├── project-context.md              # Bible del proyecto
└── README.md                       # Este archivo
```

## Ubicación de la Aplicación

La aplicación Laravel se encuentra en: **`gatic/`**

Para trabajar con la aplicación:

```bash
# Verificar versión de Laravel
php gatic/artisan --version

# Navegar a la carpeta de la aplicación
cd gatic/

# Ejecutar comandos artisan
php artisan <comando>
```

## Tecnologías (Stack)

- **Backend:** Laravel 11, PHP 8.2+, MySQL 8
- **Frontend:** Blade + Livewire 3 + Bootstrap 5
- **Auth:** Laravel Breeze (adaptado a Bootstrap)
- **Dev Local:** Laravel Sail
- **Build:** Vite/NPM

## Roadmap

El proyecto se ejecuta por Gates (0-5). Consulta `docsBmad/gates-execution.md` para detalles.

## Referencias

- **Bible del proyecto:** `docsBmad/project-context.md`
- **Arquitectura:** `_bmad-output/architecture.md`
- **PRD:** `_bmad-output/prd.md`
- **Epics & Stories:** `_bmad-output/project-planning-artifacts/epics.md`
- **Tracking:** `_bmad-output/implementation-artifacts/sprint-status.yaml`
