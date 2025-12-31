import './bootstrap';
import { registerToasts } from './ui/toasts';
import { registerFreshness } from './ui/freshness';
import { registerLongRequestUi } from './ui/long-request';
import { registerCopyToClipboard } from './ui/copy-to-clipboard';
import { registerLivewireErrorHandling } from './ui/livewire-error-handling';

registerToasts();
registerFreshness();
registerLongRequestUi();
registerCopyToClipboard();
registerLivewireErrorHandling();
