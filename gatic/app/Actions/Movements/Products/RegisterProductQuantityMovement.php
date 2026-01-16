<?php

declare(strict_types=1);

namespace App\Actions\Movements\Products;

use App\Actions\Inventory\Products\LockQuantityProduct;
use App\Models\ProductQuantityMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegisterProductQuantityMovement
{
    /**
     * @param  array{product_id: int, employee_id: int, direction: string, qty: int, note: string, actor_user_id: int}  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): ProductQuantityMovement
    {
        Validator::make($data, [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'direction' => ['required', 'string', Rule::in(ProductQuantityMovement::DIRECTIONS)],
            'qty' => ['required', 'integer', 'min:1'],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
            'actor_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ], [
            'product_id.required' => 'El producto es obligatorio.',
            'product_id.exists' => 'El producto seleccionado no existe.',
            'employee_id.required' => 'El empleado es obligatorio.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'direction.required' => 'La direccion del movimiento es obligatoria.',
            'direction.in' => 'La direccion debe ser "out" (salida) o "in" (entrada).',
            'qty.required' => 'La cantidad es obligatoria.',
            'qty.min' => 'La cantidad debe ser al menos 1.',
            'note.required' => 'La nota es obligatoria.',
            'note.min' => 'La nota debe tener al menos :min caracteres.',
            'note.max' => 'La nota no puede exceder :max caracteres.',
        ])->validate();

        return DB::transaction(function () use ($data): ProductQuantityMovement {
            $product = (new LockQuantityProduct)->execute($data['product_id']);

            if ($product->qty_total === null) {
                throw ValidationException::withMessages([
                    'qty' => [
                        'El stock actual de este producto no está inicializado. Ajusta el inventario (Admin) antes de registrar movimientos.',
                    ],
                ]);
            }

            $qtyBefore = (int) $product->qty_total;
            $qty = (int) $data['qty'];
            $direction = $data['direction'];

            if ($direction === ProductQuantityMovement::DIRECTION_OUT) {
                // Salida: disminuir stock
                if ($qtyBefore < $qty) {
                    throw ValidationException::withMessages([
                        'qty' => ["Stock insuficiente. Disponible: {$qtyBefore}, solicitado: {$qty}."],
                    ]);
                }
                $qtyAfter = $qtyBefore - $qty;
            } else {
                // Entrada/Devolucion: aumentar stock
                $qtyAfter = $qtyBefore + $qty;
            }

            // Update product stock
            $product->qty_total = $qtyAfter;
            $product->save();

            // Create movement record
            return ProductQuantityMovement::create([
                'product_id' => $product->id,
                'employee_id' => $data['employee_id'],
                'actor_user_id' => $data['actor_user_id'],
                'direction' => $direction,
                'qty' => $qty,
                'qty_before' => $qtyBefore,
                'qty_after' => $qtyAfter,
                'note' => $data['note'],
            ]);
        });
    }
}
