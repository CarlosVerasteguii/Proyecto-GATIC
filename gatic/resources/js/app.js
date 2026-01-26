import './bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import { registerToasts } from './ui/toasts';
import { registerFreshness } from './ui/freshness';
import { registerLongRequestUi } from './ui/long-request';
import { registerCopyToClipboard } from './ui/copy-to-clipboard';
import { registerLivewireErrorHandling } from './ui/livewire-error-handling';
import { registerGlobalSearchShortcuts } from './ui/global-search-shortcuts';

registerToasts();
registerFreshness();
registerLongRequestUi();
registerCopyToClipboard();
registerLivewireErrorHandling();
registerGlobalSearchShortcuts();
