@props([
    'model',
    'options' => [],
    'placeholder' => 'Seleccionar…',
    'searchPlaceholder' => 'Buscar…',
])

@php
    // $options: arreglo asociativo value => label
    $items = collect($options)
        ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])
        ->values()
        ->all();
@endphp

<div x-data="{
        open: false,
        search: '',
        selected: @entangle($model).live,
        items: @js($items),
        get filtered() {
            if (this.search.trim() === '') return this.items;
            const s = this.search.toLowerCase();
            return this.items.filter(i => i.label.toLowerCase().includes(s));
        },
        get selectedLabel() {
            const f = this.items.find(i => String(i.value) === String(this.selected));
            return f ? f.label : '';
        },
        choose(v) { this.selected = v; this.open = false; this.search = ''; },
        toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.$refs.search?.focus()); },
    }"
    @click.outside="open = false"
    @keydown.escape="open = false"
    class="relative">

    <button type="button" @click="toggle()"
        class="form-input flex items-center justify-between text-left w-full">
        <span x-text="selectedLabel || @js($placeholder)"
            :class="selectedLabel ? 'text-on-surface' : 'text-outline'" class="truncate"></span>
        <i class="ri-arrow-down-s-line text-outline shrink-0 ms-2"></i>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
        class="absolute z-40 mt-1 w-full bg-white border border-border-soft rounded-lg shadow-lg max-h-72 flex flex-col overflow-hidden">
        <div class="p-2 border-b border-border-soft">
            <div class="relative">
                <i class="ri-search-line absolute start-2.5 top-1/2 -translate-y-1/2 text-outline"></i>
                <input type="text" x-model="search" x-ref="search" @click.stop
                    placeholder="{{ $searchPlaceholder }}"
                    class="form-input !py-1.5 ps-8 text-body-sm">
            </div>
        </div>
        <ul class="overflow-y-auto custom-scrollbar py-1">
            <template x-for="item in filtered" :key="item.value">
                <li>
                    <button type="button" @click="choose(item.value)"
                        class="w-full text-left px-3 py-2 text-body-md hover:bg-surface-container-low transition-colors"
                        :class="String(item.value) === String(selected) ? 'bg-primary-fixed/40 text-primary font-medium' : 'text-on-surface'"
                        x-text="item.label"></button>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-3 text-body-sm text-on-surface-variant text-center">
                Sin resultados
            </li>
        </ul>
    </div>
</div>
