@props(['model', 'options' => [], 'placeholder' => 'Seleccionar…'])

@php
    $items = collect($options)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])->values()->all();
@endphp

{{--
  Selector de una opción tipo "caja" que, al hacer clic, despliega un menú
  con las opciones en forma de badges/pastillas. Al elegir una, se muestra
  en la caja y el menú se cierra. Ideal para campos con pocas opciones.
--}}
<div x-data="{
        open: false,
        selected: @entangle($model).live,
        items: @js($items),
        get selectedLabel() {
            const f = this.items.find(i => String(i.value) === String(this.selected));
            return f ? f.label : '';
        },
        choose(v) { this.selected = v; this.open = false; },
    }"
    @click.outside="open = false"
    @keydown.escape="open = false"
    class="relative">

    {{-- Caja (se ve como un campo/select) --}}
    <button type="button" @click="open = !open"
        class="form-input flex items-center justify-between text-left w-full">
        <span x-show="selectedLabel" class="chip-info" x-text="selectedLabel" x-cloak></span>
        <span x-show="!selectedLabel" class="text-outline">{{ $placeholder }}</span>
        <i class="ri-arrow-down-s-line text-outline shrink-0 ms-2 transition-transform" :class="open ? 'rotate-180' : ''"></i>
    </button>

    {{-- Menú desplegable con badges --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
        class="absolute z-40 mt-1 w-full bg-white border border-border-soft rounded-lg shadow-lg p-3">
        <div class="flex flex-wrap gap-2">
            <template x-for="item in items" :key="item.value">
                <button type="button" @click="choose(item.value)"
                    :class="String(item.value) === String(selected)
                        ? 'bg-primary-container text-white border-primary-container'
                        : 'bg-white text-on-surface-variant border-border-soft hover:border-primary-container hover:text-primary'"
                    class="px-3 py-1.5 rounded-full border text-body-sm transition-colors"
                    x-text="item.label"></button>
            </template>
        </div>
    </div>
</div>
