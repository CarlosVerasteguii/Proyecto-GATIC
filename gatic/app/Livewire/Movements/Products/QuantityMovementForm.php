<?php

declare(strict_types=1);

namespace App\Livewire\Movements\Products;

use App\Actions\Movements\Products\RegisterProductQuantityMovement;
use App\Actions\Movements\Undo\CreateUndoToken;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Models\UndoToken;
use App\Support\Ui\FlashToasts;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class QuantityMovementForm extends Component
{
    public int $productId;

    public ?Product $productModel = null;

    public string $direction = ProductQuantityMovement::DIRECTION_OUT;

    public ?int $qty = null;

    public ?int $employeeId = null;

    public string $note = '';

    public ?string $errorId = null;

    public function mount(string $product): void
    {
        Gate::authorize('inventory.manage');

        if (! ctype_digit($product)) {
            abort(404);
        }

        $this->productId = (int) $product;

        $this->productModel = Product::query()
            ->with('category')
            ->findOrFail($this->productId);

        // Only quantity-based products allowed
        if ($this->productModel->category?->is_serialized) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'direction' => ['required', 'string', 'in:out,in'],
            'qty' => ['required', 'integer', 'min:1'],
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
            'direction' => 'direccion',
            'qty' => 'cantidad',
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
            'direction.required' => 'Debes seleccionar el tipo de movimiento.',
            'direction.in' => 'El tipo de movimiento no es valido.',
            'qty.required' => 'La cantidad es obligatoria.',
            'qty.min' => 'La cantidad debe ser al menos 1.',
            'employeeId.required' => 'Debes seleccionar un empleado.',
            'employeeId.exists' => 'El empleado seleccionado no existe.',
            'note.required' => 'La nota es obligatoria.',
            'note.min' => 'La nota debe tener al menos :min caracteres.',
        ];
    }

    public function register(): void
    {
        Gate::authorize('inventory.manage');

        $this->validate();

        try {
            $actorUserId = auth()->id();
            if ($actorUserId === null) {
                abort(403);
            }

            $action = new RegisterProductQuantityMovement;
            $movement = $action->execute([
                'product_id' => $this->productId,
                'employee_id' => $this->employeeId,
                'direction' => $this->direction,
                'qty' => (int) $this->qty,
                'note' => $this->note,
                'actor_user_id' => (int) $actorUserId,
            ]);

            $directionLabel = $movement->direction === ProductQuantityMovement::DIRECTION_OUT
                ? 'Salida'
                : 'Entrada';

            $undoTokenId = null;
            try {
                $undoTokenId = (new CreateUndoToken)->execute([
                    'actor_user_id' => (int) $actorUserId,
                    'movement_kind' => UndoToken::KIND_PRODUCT_QTY_MOVEMENT,
                    'movement_id' => $movement->id,
                ])->id;
            } catch (Throwable) {
                $undoTokenId = null;
            }

            $toast = [
                'type' => 'success',
                'title' => "{$directionLabel} registrada",
                'message' => "Stock actualizado: {$movement->qty_before} → {$movement->qty_after}",
            ];

            if (is_string($undoTokenId) && $undoTokenId !== '') {
                $toast['action'] = [
                    'label' => 'Deshacer',
                    'event' => 'ui:undo-movement',
                    'params' => ['token' => $undoTokenId],
                ];
            }

            FlashToasts::flash($toast);

            $this->redirectRoute('inventory.products.show', [
                'product' => $this->productId,
            ], navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->errorId = app(\App\Support\Errors\ErrorReporter::class)->report($e, request());

            $this->dispatch(
                'ui:toast',
                type: 'error',
                title: 'Error inesperado',
                message: 'Ocurrio un error al registrar el movimiento.',
                errorId: $this->errorId,
            );
        }
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.movements.products.quantity-movement-form', [
            'product' => $this->productModel,
            'currentStock' => (int) ($this->productModel->qty_total ?? 0),
        ]);
    }
}
