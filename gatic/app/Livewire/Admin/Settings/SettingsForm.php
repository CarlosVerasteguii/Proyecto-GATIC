<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\AuditLog;
use App\Support\Settings\SettingsStore;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingsForm extends Component
{
    use InteractsWithToasts;

    // Loans
    public int $loansDueSoonDefault = 7;

    // Warranties
    public int $warrantiesDueSoonDefault = 30;

    // Renewals
    public int $renewalsDueSoonDefault = 90;

    // Currency
    public string $defaultCurrency = 'MXN';

    /** @var list<string> */
    public array $allowedCurrencies = [];

    /** @var list<int> */
    public array $loansOptions = [];

    /** @var list<int> */
    public array $warrantiesOptions = [];

    /** @var list<int> */
    public array $renewalsOptions = [];

    public bool $hasOverrides = false;

    public function mount(): void
    {
        Gate::authorize('admin-only');

        $store = app(SettingsStore::class);

        $this->loadOptions($store);
        $this->loadCurrentValues($store);
        $this->hasOverrides = count($store->getAllOverrides()) > 0;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'loansDueSoonDefault' => ['required', 'integer', 'min:1', 'max:3650'],
            'warrantiesDueSoonDefault' => ['required', 'integer', 'min:1', 'max:3650'],
            'renewalsDueSoonDefault' => ['required', 'integer', 'min:1', 'max:3650'],
            'defaultCurrency' => ['required', 'string', 'size:3'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'loansDueSoonDefault' => 'ventana por vencer (préstamos)',
            'warrantiesDueSoonDefault' => 'ventana por vencer (garantías)',
            'renewalsDueSoonDefault' => 'ventana por vencer (renovaciones)',
            'defaultCurrency' => 'moneda default',
        ];
    }

    public function save(): void
    {
        Gate::authorize('admin-only');
        $this->validate();

        $store = app(SettingsStore::class);

        // Validate default is within options
        if (! in_array($this->loansDueSoonDefault, $this->loansOptions, true)) {
            $this->addError('loansDueSoonDefault', 'El valor debe ser una de las opciones permitidas.');

            return;
        }
        if (! in_array($this->warrantiesDueSoonDefault, $this->warrantiesOptions, true)) {
            $this->addError('warrantiesDueSoonDefault', 'El valor debe ser una de las opciones permitidas.');

            return;
        }
        if (! in_array($this->renewalsDueSoonDefault, $this->renewalsOptions, true)) {
            $this->addError('renewalsDueSoonDefault', 'El valor debe ser una de las opciones permitidas.');

            return;
        }
        if (! in_array($this->defaultCurrency, $this->allowedCurrencies, true)) {
            $this->addError('defaultCurrency', 'La moneda debe ser una de las permitidas.');

            return;
        }

        $userId = auth()->id();

        $configDefaults = [
            'loans_due_soon_default' => (int) config('gatic.alerts.loans.due_soon_window_days_default', 7),
            'warranties_due_soon_default' => (int) config('gatic.alerts.warranties.due_soon_window_days_default', 30),
            'renewals_due_soon_default' => (int) config('gatic.alerts.renewals.due_soon_window_days_default', 90),
            'default_currency' => strtoupper(trim((string) config('gatic.inventory.money.default_currency', 'MXN'))),
        ];
        if ($configDefaults['default_currency'] === '') {
            $configDefaults['default_currency'] = 'MXN';
        }

        // Collect old values for audit
        $oldValues = [
            'loans_due_soon_default' => $store->getInt('gatic.alerts.loans.due_soon_window_days_default', 7),
            'warranties_due_soon_default' => $store->getInt('gatic.alerts.warranties.due_soon_window_days_default', 30),
            'renewals_due_soon_default' => $store->getInt('gatic.alerts.renewals.due_soon_window_days_default', 90),
            'default_currency' => $store->getString('gatic.inventory.money.default_currency', 'MXN'),
        ];

        // Save overrides (store only when different from config default)
        if ($this->loansDueSoonDefault === $configDefaults['loans_due_soon_default']) {
            $store->forget('gatic.alerts.loans.due_soon_window_days_default');
        } else {
            $store->set('gatic.alerts.loans.due_soon_window_days_default', $this->loansDueSoonDefault, $userId);
        }

        if ($this->warrantiesDueSoonDefault === $configDefaults['warranties_due_soon_default']) {
            $store->forget('gatic.alerts.warranties.due_soon_window_days_default');
        } else {
            $store->set('gatic.alerts.warranties.due_soon_window_days_default', $this->warrantiesDueSoonDefault, $userId);
        }

        if ($this->renewalsDueSoonDefault === $configDefaults['renewals_due_soon_default']) {
            $store->forget('gatic.alerts.renewals.due_soon_window_days_default');
        } else {
            $store->set('gatic.alerts.renewals.due_soon_window_days_default', $this->renewalsDueSoonDefault, $userId);
        }

        if ($this->defaultCurrency === $configDefaults['default_currency']) {
            $store->forget('gatic.inventory.money.default_currency');
        } else {
            $store->set('gatic.inventory.money.default_currency', $this->defaultCurrency, $userId);
        }

        $newValues = [
            'loans_due_soon_default' => $this->loansDueSoonDefault,
            'warranties_due_soon_default' => $this->warrantiesDueSoonDefault,
            'renewals_due_soon_default' => $this->renewalsDueSoonDefault,
            'default_currency' => $this->defaultCurrency,
        ];

        // Audit (best-effort)
        try {
            $changed = array_filter(
                $newValues,
                fn (mixed $value, string $key) => ($oldValues[$key] ?? null) !== $value,
                ARRAY_FILTER_USE_BOTH,
            );

            if ($changed !== []) {
                AuditLog::create([
                    'created_at' => now(),
                    'actor_user_id' => $userId,
                    'action' => AuditLog::ACTION_SETTINGS_UPDATE,
                    'subject_type' => 'system',
                    'subject_id' => 0,
                    'context' => [
                        'changed_keys' => array_keys($changed),
                        'old' => array_intersect_key($oldValues, $changed),
                        'new' => $changed,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Settings: audit log failed (best-effort)', ['error' => $e->getMessage()]);
        }

        $this->hasOverrides = count($store->getAllOverrides()) > 0;
        $this->toastSuccess('Configuración guardada correctamente.');
    }

    public function restoreDefaults(): void
    {
        Gate::authorize('admin-only');

        $store = app(SettingsStore::class);

        // Audit (best-effort)
        try {
            $overrides = $store->getAllOverrides();
            if ($overrides !== []) {
                AuditLog::create([
                    'created_at' => now(),
                    'actor_user_id' => auth()->id(),
                    'action' => AuditLog::ACTION_SETTINGS_UPDATE,
                    'subject_type' => 'system',
                    'subject_id' => 0,
                    'context' => [
                        'action' => 'restore_defaults',
                        'removed_overrides' => array_keys($overrides),
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Settings: audit log failed (best-effort)', ['error' => $e->getMessage()]);
        }

        $store->forgetAll();

        $this->loadOptions($store);
        $this->loadCurrentValues($store);
        $this->hasOverrides = false;

        $this->toastSuccess('Configuración restaurada a valores por defecto.');
    }

    public function render(): View
    {
        Gate::authorize('admin-only');

        return view('livewire.admin.settings.settings-form');
    }

    private function loadOptions(SettingsStore $store): void
    {
        $this->loansOptions = $this->normalizeOptions(
            $store->getIntList('gatic.alerts.loans.due_soon_window_days_options', [7, 14, 30]),
            [7, 14, 30],
        );
        $this->warrantiesOptions = $this->normalizeOptions(
            $store->getIntList('gatic.alerts.warranties.due_soon_window_days_options', [7, 14, 30]),
            [7, 14, 30],
        );
        $this->renewalsOptions = $this->normalizeOptions(
            $store->getIntList('gatic.alerts.renewals.due_soon_window_days_options', [30, 60, 90, 180]),
            [30, 60, 90, 180],
        );

        /** @var mixed $currencies */
        $currencies = config('gatic.inventory.money.allowed_currencies', ['MXN']);
        $this->allowedCurrencies = is_array($currencies) && $currencies !== []
            ? array_values(array_filter(
                array_map(
                    static fn (mixed $v): ?string => is_string($v) ? strtoupper(trim($v)) : null,
                    $currencies,
                ),
                static fn (?string $v): bool => is_string($v) && (bool) preg_match('/^[A-Z]{3}$/', $v),
            ))
            : ['MXN'];

        if ($this->allowedCurrencies === []) {
            $this->allowedCurrencies = ['MXN'];
        }
    }

    private function loadCurrentValues(SettingsStore $store): void
    {
        $this->loansDueSoonDefault = $store->getInt('gatic.alerts.loans.due_soon_window_days_default', 7);
        if (! in_array($this->loansDueSoonDefault, $this->loansOptions, true)) {
            $this->loansDueSoonDefault = $this->loansOptions[0] ?? 7;
        }

        $this->warrantiesDueSoonDefault = $store->getInt('gatic.alerts.warranties.due_soon_window_days_default', 30);
        if (! in_array($this->warrantiesDueSoonDefault, $this->warrantiesOptions, true)) {
            $this->warrantiesDueSoonDefault = $this->warrantiesOptions[0] ?? 30;
        }

        $this->renewalsDueSoonDefault = $store->getInt('gatic.alerts.renewals.due_soon_window_days_default', 90);
        if (! in_array($this->renewalsDueSoonDefault, $this->renewalsOptions, true)) {
            $this->renewalsDueSoonDefault = $this->renewalsOptions[0] ?? 90;
        }

        $currency = $store->getString('gatic.inventory.money.default_currency', 'MXN');
        $this->defaultCurrency = in_array($currency, $this->allowedCurrencies, true)
            ? $currency
            : ($this->allowedCurrencies[0] ?? 'MXN');
    }

    /**
     * @param  list<int>  $options
     * @param  list<int>  $fallback
     * @return list<int>
     */
    private function normalizeOptions(array $options, array $fallback): array
    {
        $filtered = array_values(array_filter($options, static fn (int $v): bool => $v >= 1 && $v <= 3650));
        sort($filtered);

        return $filtered !== [] ? $filtered : $fallback;
    }
}
