<x-admin-layout title="Consumibles" :breadcrumbs="[['name' => 'Inventario'], ['name' => 'Consumibles']]">
    <x-page-header title="Consumibles"
        description="Control de existencias con entradas y salidas; alertas de stock bajo.">
        <x-slot name="actions">
            @can('consumables.create')
                <button type="button" class="btn-primary" onclick="Livewire.dispatch('open-consumable-form', { id: null })">
                    <i class="ri-add-line"></i> Nuevo consumible
                </button>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-gutter mb-6">
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Consumibles</span>
            <span class="mt-2 block text-display-lg text-on-surface">{{ number_format($stats['total']) }}</span>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Con stock bajo</span>
            <div class="mt-2 flex items-end justify-between">
                <span class="text-display-lg text-on-surface">{{ number_format($stats['low']) }}</span>
                @if ($stats['low'] > 0) <span class="chip-alert">Revisar</span> @endif
            </div>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Existencia total</span>
            <span class="mt-2 block text-display-lg text-on-surface">{{ number_format($stats['units']) }}</span>
        </div>
    </div>

    <div class="card">
        <div class="p-4">
            <livewire:admin.consumables.consumables-table />
        </div>
    </div>

    <livewire:admin.consumables.consumable-form />
</x-admin-layout>
