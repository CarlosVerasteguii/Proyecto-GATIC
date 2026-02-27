<?php

namespace App\Livewire\Catalogs\Locations;

use App\Models\Location;
use App\Support\Ui\ReturnToPath;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LocationForm extends Component
{
    public ?int $locationId = null;

    public string $name = '';

    public ?string $returnTo = null;

    public function mount(?string $location = null): void
    {
        Gate::authorize('catalogs.manage');

        $returnToQuery = request()->query('returnTo');
        $this->returnTo = is_string($returnToQuery)
            ? ReturnToPath::sanitize($returnToQuery)
            : null;

        if (! $location) {
            return;
        }

        if (! ctype_digit($location)) {
            abort(404);
        }

        $this->locationId = (int) $location;

        $model = Location::query()->findOrFail($this->locationId);
        $this->name = $model->name;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('locations', 'name')->ignore($this->locationId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'La ubicación ya existe.',
        ];
    }

    public function save(): mixed
    {
        Gate::authorize('catalogs.manage');

        $this->name = Location::normalizeName($this->name) ?? '';

        try {
            $validated = $this->validate();
        } catch (ValidationException $exception) {
            $failed = $exception->validator->failed();
            $failedNameRules = array_change_key_case($failed['name'] ?? [], CASE_LOWER);

            if (
                $this->locationId === null
                && array_key_exists('unique', $failedNameRules)
                && Location::query()->onlyTrashed()->where('name', $this->name)->exists()
            ) {
                $exception->validator->errors()->forget('name');
                $exception->validator->errors()->add(
                    'name',
                    'El nombre ya existe en la Papelera. Restaura la ubicación desde Catálogos → Papelera.'
                );
            }

            throw $exception;
        }

        try {
            if (! $this->locationId) {
                $created = Location::query()->create([
                    'name' => $validated['name'],
                ]);

                $returnTo = ReturnToPath::sanitize($this->returnTo);
                if ($returnTo !== null) {
                    return redirect()
                        ->to(ReturnToPath::withQuery($returnTo, ['created_id' => (int) $created->id]));
                }

                return redirect()
                    ->route('catalogs.locations.index')
                    ->with('status', 'Ubicación creada.');
            }

            $model = Location::query()->findOrFail($this->locationId);
            $model->name = $validated['name'];
            $model->save();
        } catch (QueryException $exception) {
            if ($this->isDuplicateNameException($exception)) {
                $this->addError('name', 'La ubicación ya existe.');

                return null;
            }

            throw $exception;
        }

        return redirect()
            ->route('catalogs.locations.index')
            ->with('status', 'Ubicación actualizada.');
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        return view('livewire.catalogs.locations.location-form', [
            'isEdit' => (bool) $this->locationId,
        ]);
    }

    private function isDuplicateNameException(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo;

        if (! is_array($errorInfo) || count($errorInfo) < 2) {
            return false;
        }

        $driverCode = (int) ($errorInfo[1] ?? 0);

        return $driverCode === 1062;
    }
}
