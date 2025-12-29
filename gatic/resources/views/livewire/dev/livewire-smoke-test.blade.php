<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Prueba Livewire (smoke)</div>

                <div class="card-body">
                    <p class="mb-3">Contador: <strong>{{ $count }}</strong></p>

                    <button type="button" class="btn btn-primary" wire:click="increment">
                        +1
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

