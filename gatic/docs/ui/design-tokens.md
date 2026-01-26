# Design Tokens - GATIC UI

Design tokens are the single source of truth for visual design decisions in GATIC. They are defined as CSS custom properties in `resources/sass/_tokens.scss`.

## Architecture

```
_variables.scss   → Bootstrap SCSS variables (compile-time)
_tokens.scss      → CSS custom properties (runtime, after Bootstrap)
```

**Why both?** Bootstrap requires SCSS variables at compile time. CSS custom properties enable runtime theming and are used by our custom components.

## Token Categories

### CFE Brand Colors

| Token | Value | Usage |
|-------|-------|-------|
| `--cfe-green` | `#008e5a` | Accent color, links, highlights |
| `--cfe-green-dark` | `#006b47` | Primary buttons, headers |
| `--cfe-green-light` | `#00b36f` | Hover states |
| `--cfe-green-bg` | `rgba(0,142,90,0.1)` | Subtle backgrounds |

### Semantic Colors (States)

| Token | Value | Usage |
|-------|-------|-------|
| `--color-success` | `#198754` | Success messages, available status |
| `--color-warning` | `#ffc107` | Warnings |
| `--color-danger` | `#dc3545` | Errors, retired status |
| `--color-info` | `#0dcaf0` | Informational |
| `--color-secondary` | `#6c757d` | Muted, disabled |

Each has a `-bg` variant for subtle backgrounds.

### Asset Status Colors

These map to the `App\Models\Asset` status constants:

| Status | Token | Color | Background |
|--------|-------|-------|------------|
| Disponible | `--status-available` | Green | `--status-available-bg` |
| Prestado | `--status-loaned` | Amber | `--status-loaned-bg` |
| Asignado | `--status-assigned` | Purple | `--status-assigned-bg` |
| Pendiente de Retiro | `--status-pending` | Orange | `--status-pending-bg` |
| Retirado | `--status-retired` | Red | `--status-retired-bg` |

### Spacing Scale

Based on 8px grid:

| Token | Value | Pixels |
|-------|-------|--------|
| `--space-0` | `0` | 0 |
| `--space-1` | `0.25rem` | 4px |
| `--space-2` | `0.5rem` | 8px |
| `--space-3` | `0.75rem` | 12px |
| `--space-4` | `1rem` | 16px |
| `--space-5` | `1.25rem` | 20px |
| `--space-6` | `1.5rem` | 24px |
| `--space-8` | `2rem` | 32px |
| `--space-10` | `2.5rem` | 40px |
| `--space-12` | `3rem` | 48px |

### Border Radius

| Token | Value | Usage |
|-------|-------|-------|
| `--radius-sm` | `0.25rem` | Buttons, inputs |
| `--radius-md` | `0.375rem` | Cards |
| `--radius-lg` | `0.5rem` | Modals |
| `--radius-full` | `9999px` | Pills, avatars |

### Shadows

| Token | Usage |
|-------|-------|
| `--shadow-sm` | Subtle elevation (cards) |
| `--shadow-md` | Dropdowns, popovers |
| `--shadow-lg` | Modals, drawers |

### Transitions

| Token | Duration | Usage |
|-------|----------|-------|
| `--transition-fast` | 150ms | Hover states |
| `--transition-normal` | 200ms | Most interactions |
| `--transition-slow` | 300ms | Drawers, modals |

### Layout

| Token | Value | Usage |
|-------|-------|-------|
| `--sidebar-width` | `18rem` | Desktop sidebar |
| `--sidebar-collapsed-width` | `4.5rem` | Collapsed sidebar |
| `--topbar-height` | `3.5rem` | Top navigation |

## Components Using Tokens

### Status Badge

Use the `<x-ui.status-badge>` component for consistent status display:

```blade
{{-- Basic usage --}}
<x-ui.status-badge :status="$asset->status" />

{{-- Solid variant (high emphasis) --}}
<x-ui.status-badge :status="$asset->status" solid />

{{-- Without icon --}}
<x-ui.status-badge :status="$asset->status" :icon="false" />
```

The component automatically maps status values to the correct styling.

## Usage in Custom CSS

```scss
.my-component {
  padding: var(--space-4);
  background: var(--cfe-green-bg);
  border-radius: var(--radius-md);
  transition: all var(--transition-normal);
}
```

## Adding New Tokens

1. Add the token to `resources/sass/_tokens.scss`
2. If needed for Bootstrap, mirror in `resources/sass/_variables.scss`
3. Update this documentation
4. Run `npm run build` to compile

## Related Files

- `resources/sass/_tokens.scss` - Token definitions
- `resources/sass/_variables.scss` - Bootstrap SCSS variables
- `resources/views/components/ui/status-badge.blade.php` - Status badge component
