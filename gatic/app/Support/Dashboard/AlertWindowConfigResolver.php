<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use App\Support\Settings\SettingsStore;

/**
 * @phpstan-type AlertWindowConfig array{
 *     allowedOptions: list<int>,
 *     fallbackWindowDays: int,
 *     defaultWindowDays: int
 * }
 */
final class AlertWindowConfigResolver
{
    public function __construct(
        private readonly SettingsStore $store,
    ) {}

    /**
     * @param  non-empty-list<int>  $fallbackOptions
     * @return AlertWindowConfig
     */
    public function resolve(
        string $optionsKey,
        string $defaultKey,
        array $fallbackOptions,
    ): array {
        $allowedOptions = $this->store->getIntList($optionsKey, $fallbackOptions);
        if ($allowedOptions === []) {
            $allowedOptions = $fallbackOptions;
        }

        $fallbackWindowDays = $allowedOptions[0];
        $defaultWindowDays = $this->store->getInt($defaultKey, $fallbackWindowDays);

        if (! in_array($defaultWindowDays, $allowedOptions, true)) {
            $defaultWindowDays = $fallbackWindowDays;
        }

        return [
            'allowedOptions' => $allowedOptions,
            'fallbackWindowDays' => $fallbackWindowDays,
            'defaultWindowDays' => $defaultWindowDays,
        ];
    }
}
