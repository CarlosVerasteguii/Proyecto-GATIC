{{--
    Drawer Component
    ----------------
    Slide-in panel from the right side for forms and details.

    Usage:
        <x-ui.drawer id="movement-drawer" title="Registrar movimiento">
            <form>...</form>
        </x-ui.drawer>

        <button data-drawer-toggle="movement-drawer">Open Drawer</button>

    Props:
        - id (string): Unique ID for the drawer (required)
        - title (string): Header title
        - width (string): Width class (default: 'drawer-md', options: drawer-sm, drawer-md, drawer-lg)
        - closeOnEsc (bool): Close when Escape is pressed (default: true)

    Livewire Usage:
        Use wire:ignore.self on the drawer and dispatch events:
        - $dispatch('drawer-open', { id: 'movement-drawer' })
        - $dispatch('drawer-close', { id: 'movement-drawer' })
--}}
@props([
    'id',
    'title' => '',
    'width' => 'drawer-md',
    'closeOnEsc' => true,
])

<div
    {{ $attributes->merge([
        'class' => "drawer $width",
        'id' => $id,
        'role' => 'dialog',
        'aria-modal' => 'true',
        'aria-labelledby' => "{$id}-title",
        'data-drawer' => $id,
        'data-close-on-esc' => $closeOnEsc ? 'true' : 'false',
    ]) }}
>
    <div class="drawer-backdrop" data-drawer-close="{{ $id }}"></div>

    <div class="drawer-panel">
        {{-- Header --}}
        <div class="drawer-header">
            <h5 class="drawer-title" id="{{ $id }}-title">{{ $title }}</h5>
            <button
                type="button"
                class="btn-close"
                data-drawer-close="{{ $id }}"
                aria-label="Cerrar"
            ></button>
        </div>

        {{-- Body --}}
        <div class="drawer-body">
            {{ $slot }}
        </div>

        {{-- Footer (optional slot) --}}
        @if(isset($footer))
            <div class="drawer-footer">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
