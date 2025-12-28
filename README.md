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

## Dev local con Sail

> Nota: si ejecutas `php`, `composer` o `artisan` en host, actualmente el repo requiere **PHP >= 8.4** (por dependencias en `gatic/composer.lock`). Si no lo tienes, corre los comandos desde WSL2/Git Bash o usa Sail.

### Prerrequisitos

- Docker Desktop + Docker Compose v2 (`docker compose`)
- WSL2 (recomendado) o Git Bash (necesitas `bash` para correr `./vendor/bin/sail`)
- Puertos libres: `APP_PORT` (default 8080) y `FORWARD_DB_PORT` (default 3306)

### Setup / Arranque

```bash
cd gatic
cp .env.example .env

# Si NO tienes PHP 8.4+ en host, puedes instalar dependencias con Docker:
docker run --rm -v "$(pwd)":/var/www/html -w /var/www/html laravelsail/php84-composer:latest composer install

./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
```

### Credenciales seed (solo dev)

- Admin: `admin@gatic.local` / `password`
- Editor: `editor@gatic.local` / `password`
- Lector: `lector@gatic.local` / `password`

### Tests

```bash
cd gatic
./vendor/bin/sail test
```

### Troubleshooting (Windows/WSL2)

- Si `./vendor/bin/sail` falla por `bash`/permisos: usa WSL2 o Git Bash.
- Si el puerto 8080/3306 está ocupado: ajusta `APP_PORT` / `FORWARD_DB_PORT` en `gatic/.env`.

## Roadmap

El proyecto se ejecuta por Gates (0-5). Consulta `docsBmad/gates-execution.md` para detalles.

## Referencias

- **Bible del proyecto:** `docsBmad/project-context.md`
- **Arquitectura:** `_bmad-output/architecture.md`
- **PRD:** `_bmad-output/prd.md`
- **Epics & Stories:** `_bmad-output/project-planning-artifacts/epics.md`
- **Tracking:** `_bmad-output/implementation-artifacts/sprint-status.yaml`
