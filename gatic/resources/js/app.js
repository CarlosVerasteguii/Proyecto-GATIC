import './bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import { registerToasts } from './ui/toasts';
import { registerFreshness } from './ui/freshness';
import { registerLongRequestUi } from './ui/long-request';
import { registerCopyToClipboard } from './ui/copy-to-clipboard';
import { registerLivewireErrorHandling } from './ui/livewire-error-handling';
import { registerGlobalSearchShortcuts } from './ui/global-search-shortcuts';
import { registerHotkeys } from './ui/hotkeys';
import { registerCommandPalette } from './ui/command-palette';
import { registerSidebarToggle } from './ui/sidebar-toggle';
import { registerDrawer } from './ui/drawer';
import { registerDensityToggle } from './ui/density-toggle';
import { registerThemeToggle } from './ui/theme-toggle';
import { registerColumnManager } from './ui/column-manager';
import { registerDropdownPopperFix } from './ui/dropdown-popper-fix';

registerToasts();
registerFreshness();
registerLongRequestUi();
registerCopyToClipboard();
registerLivewireErrorHandling();
registerGlobalSearchShortcuts();
registerHotkeys();
registerCommandPalette();
registerSidebarToggle();
registerDrawer();
registerDensityToggle();
registerThemeToggle();
registerColumnManager();
registerDropdownPopperFix();

document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('[data-page="dashboard"]')) return;
    import('./pages/dashboard').then((m) => m.registerDashboardCharts());
});
