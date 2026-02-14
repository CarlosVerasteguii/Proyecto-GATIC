<?php

namespace App\Livewire\Movements;

use App\Actions\Movements\Undo\UndoMovementByToken;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Support\Errors\ErrorReporter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class UndoManager extends Component
{
    use InteractsWithToasts;

    #[On('ui:undo-movement')]
    public function undo(string $token): void
    {
        Gate::authorize('inventory.manage');

        try {
            $result = (new UndoMovementByToken)->execute([
                'token_id' => $token,
                'actor_user_id' => auth()->id(),
            ]);

            $this->toast(
                type: (string) ($result['type'] ?? 'info'),
                title: is_string($result['title'] ?? null) ? $result['title'] : null,
                message: (string) ($result['message'] ?? ''),
            );

            /** @var array<string, mixed> $context */
            $context = is_array($result['context'] ?? null) ? $result['context'] : [];

            if (isset($context['asset_id'])) {
                $this->dispatch('inventory:asset-changed', assetId: (int) $context['asset_id']);
            }

            if (isset($context['product_id'])) {
                $this->dispatch('inventory:product-changed', productId: (int) $context['product_id']);
            }

            if (isset($context['batch_uuid'])) {
                /** @var list<int> $assetIds */
                $assetIds = is_array($context['asset_ids'] ?? null) ? array_values($context['asset_ids']) : [];

                $this->dispatch(
                    'inventory:assets-batch-changed',
                    batchUuid: (string) $context['batch_uuid'],
                    assetIds: $assetIds,
                );
            }
        } catch (ValidationException $e) {
            $message = 'No se pudo deshacer el movimiento.';
            foreach ($e->errors() as $messages) {
                foreach ($messages as $m) {
                    $message = $m;
                    break 2;
                }
            }

            $this->toastWarning(message: $message, title: 'No se pudo deshacer');
        } catch (Throwable $e) {
            if (app()->environment(['local', 'testing'])) {
                throw $e;
            }

            $errorId = app(ErrorReporter::class)->report($e, request());

            $this->toastError(
                message: 'Ocurrió un error inesperado al deshacer el movimiento.',
                title: 'Error inesperado',
                errorId: $errorId,
            );
        }
    }

    public function render(): View
    {
        return view('livewire.movements.undo-manager');
    }
}
