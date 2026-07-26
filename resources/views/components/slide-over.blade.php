@props(['model' => 'open', 'title' => '', 'width' => 'max-w-lg'])

{{-- Panel lateral deslizante reutilizable. Se controla con una propiedad booleana Livewire ($model). --}}
<div x-data="{ open: @entangle($model) }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-hidden"
    aria-labelledby="slide-over-title"
    role="dialog"
    aria-modal="true">

    {{-- Fondo oscuro --}}
    <div x-show="open"
        x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in-out duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-gray-900/50"
        @click="open = false"></div>

    {{-- Panel --}}
    <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
        <div x-show="open"
            x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="w-screen {{ $width }}">
            <div class="flex h-full flex-col bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-border-soft px-4 py-3">
                    <h2 class="text-headline-sm text-on-surface" id="slide-over-title">{{ $title }}</h2>
                    <button type="button" class="p-1.5 text-outline hover:text-on-surface rounded-lg" @click="open = false">
                        <span class="sr-only">Cerrar</span>
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-4 py-4">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
