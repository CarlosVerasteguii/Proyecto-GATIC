<?php

namespace App\Livewire\Concerns;

trait InteractsWithToasts
{
    protected function toast(
        string $type,
        string $message,
        ?string $title = null,
        ?int $timeoutMs = null,
        ?string $errorId = null,
        ?array $action = null,
    ): void {
        $this->dispatch(
            'ui:toast',
            type: $type,
            title: $title,
            message: $message,
            timeoutMs: $timeoutMs,
            errorId: $errorId,
            action: $action,
        );
    }

    protected function toastSuccess(string $message, ?string $title = null, ?int $timeoutMs = null): void
    {
        $this->toast(type: 'success', message: $message, title: $title, timeoutMs: $timeoutMs);
    }

    protected function toastError(string $message, ?string $title = null, ?int $timeoutMs = null, ?string $errorId = null): void
    {
        $this->toast(type: 'error', message: $message, title: $title, timeoutMs: $timeoutMs, errorId: $errorId);
    }

    protected function toastInfo(string $message, ?string $title = null, ?int $timeoutMs = null): void
    {
        $this->toast(type: 'info', message: $message, title: $title, timeoutMs: $timeoutMs);
    }

    protected function toastWarning(string $message, ?string $title = null, ?int $timeoutMs = null): void
    {
        $this->toast(type: 'warning', message: $message, title: $title, timeoutMs: $timeoutMs);
    }
}
