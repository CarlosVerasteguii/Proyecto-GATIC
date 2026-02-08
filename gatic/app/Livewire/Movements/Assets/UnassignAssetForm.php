<?php

declare(strict_types=1);

namespace App\Livewire\Movements\Assets;

use App\Actions\Movements\Assets\UnassignAssetFromEmployee;
use App\Models\Asset;
use App\Models\Product;
use App\Support\Assets\AssetStatusTransitions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class UnassignAssetForm extends Component
{
    public int $productId;

    public int $assetId;

    public ?Product $productModel = null;

    public ?Asset $assetModel = null;

    public ?string $returnTo = null;

    public ?int $employeeId = null;

    public bool $employeeLocked = false;

    public string $note = '';

    public ?string $errorId = null;

    public bool $isSubmitting = false;

    public function mount(string $product, string $asset): void
    {
        Gate::authorize('inventory.manage');

        if (! ctype_digit($product) || ! ctype_digit($asset)) {
            abort(404);
        }

        $this->productId = (int) $product;
        $this->assetId = (int) $asset;
        $this->returnTo = $this->sanitizeReturnTo(request()->query('returnTo'));

        $this->productModel = Product::query()
            ->with('category')
            ->findOrFail($this->productId);

        if (! $this->productModel->category?->is_serialized) {
            abort(404);
        }

        $this->assetModel = Asset::query()
            ->with(['location', 'currentEmployee'])
            ->where('product_id', $this->productId)
            ->findOrFail($this->assetId);

        if (! AssetStatusTransitions::canUnassign($this->assetModel->status)) {
            session()->flash('error', AssetStatusTransitions::getBlockingReason($this->assetModel->status, 'unassign'));

            $returnTo = $this->sanitizeReturnTo($this->returnTo);

            $this->redirectRoute('inventory.products.assets.show', [
                'product' => $this->productId,
                'asset' => $this->assetId,
            ] + ($returnTo !== null ? ['returnTo' => $returnTo] : []), navigate: true);

            return;
        }

        if ($this->assetModel->current_employee_id !== null) {
            if ($this->assetModel->currentEmployee) {
                $this->employeeId = $this->assetModel->current_employee_id;
                $this->employeeLocked = true;
            }
        }
    }

    public function updatedEmployeeId(): void
    {
        $this->resetErrorBag('employeeId');
    }

    public function updatedNote(): void
    {
        $this->resetErrorBag('note');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'employeeId' => [$this->requiresEmployeeSelection() ? 'required' : 'nullable', 'integer', 'exists:employees,id'],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    private function requiresEmployeeSelection(): bool
    {
        return $this->assetModel !== null
            && ($this->assetModel->current_employee_id === null || $this->assetModel->currentEmployee === null);
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'employeeId' => 'empleado',
            'note' => 'nota',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'employeeId.required' => 'Debes seleccionar un empleado.',
            'note.required' => 'La nota es obligatoria.',
            'note.min' => 'La nota debe tener al menos :min caracteres.',
        ];
    }

    public function unassignAsset(): void
    {
        Gate::authorize('inventory.manage');

        $this->isSubmitting = true;

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->isSubmitting = false;
            $firstErrorField = array_key_first($e->errors());
            if ($firstErrorField) {
                $this->dispatch('focus-field', field: $firstErrorField);
            }
            throw $e;
        }

        try {
            $actorUserId = auth()->id();
            if ($actorUserId === null) {
                abort(403);
            }

            $action = new UnassignAssetFromEmployee;
            $action->execute([
                'asset_id' => $this->assetId,
                'employee_id' => $this->employeeId,
                'note' => $this->note,
                'actor_user_id' => (int) $actorUserId,
            ]);

            $this->dispatch(
                'ui:toast',
                type: 'success',
                title: 'Desasignación exitosa',
                message: 'El activo ha sido desasignado correctamente.',
            );

            $returnTo = $this->sanitizeReturnTo($this->returnTo);
            if ($returnTo !== null) {
                $this->redirect($returnTo, navigate: true);

                return;
            }

            $this->redirectRoute('inventory.products.assets.show', [
                'product' => $this->productId,
                'asset' => $this->assetId,
            ], navigate: true);
        } catch (ValidationException $e) {
            $this->isSubmitting = false;

            if (isset($e->errors()['employee_id'][0])) {
                $this->addError('employeeId', $e->errors()['employee_id'][0]);

                return;
            }

            if (isset($e->errors()['asset_id'][0])) {
                $this->dispatch(
                    'ui:toast',
                    type: 'error',
                    title: 'No se puede desasignar',
                    message: $e->errors()['asset_id'][0],
                );

                $returnTo = $this->sanitizeReturnTo($this->returnTo);

                $this->redirectRoute('inventory.products.assets.show', [
                    'product' => $this->productId,
                    'asset' => $this->assetId,
                ] + ($returnTo !== null ? ['returnTo' => $returnTo] : []), navigate: true);

                return;
            }

            throw $e;
        } catch (Throwable $e) {
            $this->isSubmitting = false;
            $this->errorId = app(\App\Support\Errors\ErrorReporter::class)->report($e, request());

            $this->dispatch(
                'ui:toast',
                type: 'error',
                title: 'Error inesperado',
                message: 'Ocurrió un error al desasignar el activo.',
                errorId: $this->errorId,
            );
        }
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.movements.assets.unassign-asset-form', [
            'product' => $this->productModel,
            'asset' => $this->assetModel,
            'isSubmitting' => $this->isSubmitting,
        ]);
    }

    private function sanitizeReturnTo(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || ! str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return null;
        }

        if (str_contains($value, "\n") || str_contains($value, "\r") || strlen($value) > 2000) {
            return null;
        }

        return $value;
    }
}
