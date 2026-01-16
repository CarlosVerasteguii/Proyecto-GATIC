<?php

declare(strict_types=1);

namespace App\Livewire\Movements\Assets;

use App\Actions\Movements\Assets\LoanAssetToEmployee;
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
class LoanAssetForm extends Component
{
    public int $productId;

    public int $assetId;

    public ?Product $productModel = null;

    public ?Asset $assetModel = null;

    public ?int $employeeId = null;

    public string $note = '';

    public ?string $errorId = null;

    public function mount(string $product, string $asset): void
    {
        Gate::authorize('inventory.manage');

        if (! ctype_digit($product) || ! ctype_digit($asset)) {
            abort(404);
        }

        $this->productId = (int) $product;
        $this->assetId = (int) $asset;

        $this->productModel = Product::query()
            ->with('category')
            ->findOrFail($this->productId);

        if (! $this->productModel->category?->is_serialized) {
            abort(404);
        }

        $this->assetModel = Asset::query()
            ->with('location')
            ->where('product_id', $this->productId)
            ->findOrFail($this->assetId);

        if (! AssetStatusTransitions::canLoan($this->assetModel->status)) {
            session()->flash('error', AssetStatusTransitions::getBlockingReason($this->assetModel->status, 'loan'));
            $this->redirectRoute('inventory.products.assets.show', [
                'product' => $this->productId,
                'asset' => $this->assetId,
            ], navigate: true);

            return;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'employeeId' => ['required', 'integer', 'exists:employees,id'],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ];
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

    public function loan(): void
    {
        Gate::authorize('inventory.manage');

        $this->validate();

        try {
            $actorUserId = auth()->id();
            if ($actorUserId === null) {
                abort(403);
            }

            $action = new LoanAssetToEmployee;
            $action->execute([
                'asset_id' => $this->assetId,
                'employee_id' => $this->employeeId,
                'note' => $this->note,
                'actor_user_id' => (int) $actorUserId,
            ]);

            $this->dispatch(
                'ui:toast',
                type: 'success',
                title: 'Prestamo exitoso',
                message: 'El activo ha sido prestado correctamente.',
            );

            $this->redirectRoute('inventory.products.assets.show', [
                'product' => $this->productId,
                'asset' => $this->assetId,
            ], navigate: true);
        } catch (ValidationException $e) {
            if (isset($e->errors()['asset_id'][0])) {
                $this->dispatch(
                    'ui:toast',
                    type: 'error',
                    title: 'No se puede prestar',
                    message: $e->errors()['asset_id'][0],
                );

                $this->redirectRoute('inventory.products.assets.show', [
                    'product' => $this->productId,
                    'asset' => $this->assetId,
                ], navigate: true);

                return;
            }

            throw $e;
        } catch (Throwable $e) {
            $this->errorId = app(\App\Support\Errors\ErrorReporter::class)->report($e, request());

            $this->dispatch(
                'ui:toast',
                type: 'error',
                title: 'Error inesperado',
                message: 'Ocurrio un error al prestar el activo.',
                errorId: $this->errorId,
            );
        }
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.movements.assets.loan-asset-form', [
            'product' => $this->productModel,
            'asset' => $this->assetModel,
        ]);
    }
}
