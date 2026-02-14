<?php

declare(strict_types=1);

namespace App\Support\Ui;

final class FlashToasts
{
    /**
     * Flash a toast payload to the next request/navigation.
     *
     * @param  array<string, mixed>  $toast
     */
    public static function flash(array $toast): void
    {
        $existing = session()->get('ui_toasts', []);

        if (! is_array($existing)) {
            $existing = [];
        }

        if (! array_is_list($existing)) {
            $existing = [$existing];
        }

        $existing[] = $toast;

        session()->flash('ui_toasts', $existing);
    }
}
