@php
    $flashToasts = [];

    $uiToasts = session('ui_toasts');
    if (is_array($uiToasts)) {
        if (array_is_list($uiToasts)) {
            foreach ($uiToasts as $toast) {
                if (is_array($toast)) {
                    $flashToasts[] = $toast;
                }
            }
        } else {
            $flashToasts[] = $uiToasts;
        }
    }

    $status = session('status');
    $error = session('error');
    $success = session('success');
    $warning = session('warning');
    $info = session('info');

    if (is_string($status) && $status !== '') {
        $flashToasts[] = [
            'type' => 'success',
            'message' => $status,
        ];
    }

    if (is_string($success) && $success !== '') {
        $flashToasts[] = [
            'type' => 'success',
            'message' => $success,
        ];
    }

    if (is_string($error) && $error !== '') {
        $flashToasts[] = [
            'type' => 'error',
            'message' => $error,
        ];
    }

    if (is_string($warning) && $warning !== '') {
        $flashToasts[] = [
            'type' => 'warning',
            'message' => $warning,
        ];
    }

    if (is_string($info) && $info !== '') {
        $flashToasts[] = [
            'type' => 'info',
            'message' => $info,
        ];
    }
@endphp

<div
    class="toast-container gatic-toast-container position-fixed top-0 end-0 p-3"
    data-testid="app-toasts"
    data-flash-toasts='@json($flashToasts)'
    data-toast-default-delay-ms="{{ (int) config('gatic.ui.toast.default_delay_ms', 5000) }}"
    data-toast-undo-delay-ms="{{ (int) config('gatic.ui.toast.undo_delay_ms', 10000) }}"
    data-long-request-threshold-ms="{{ (int) config('gatic.ui.long_request_threshold_ms', 3000) }}"
    aria-live="polite"
    aria-atomic="true"
></div>
