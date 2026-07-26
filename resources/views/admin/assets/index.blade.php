<x-admin-layout title="Activos" :breadcrumbs="[['name' => 'Inventario'], ['name' => 'Activos']]">
    <x-page-header title="Inventario de activos"
        description="Gestiona y rastrea los bienes informáticos de la organización de forma centralizada.">
        <x-slot name="actions">
            @can('assets.export')
                <button type="button" class="btn-ghost" onclick="Livewire.dispatch('export-assets')">
                    <i class="ri-download-2-line"></i> Exportar CSV
                </button>
            @endcan
            @can('assets.create')
                <button type="button" class="btn-primary" onclick="Livewire.dispatch('open-asset-form', { id: null })">
                    <i class="ri-add-line"></i> Dar de alta activo
                </button>
            @endcan
        </x-slot>
    </x-page-header>

    {{-- Métricas rápidas --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter mb-6">
        <div class="card p-5 flex flex-col justify-between">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Total de activos</span>
            <span class="mt-3 text-display-lg text-on-surface">{{ number_format($stats['total']) }}</span>
        </div>
        <div class="card p-5 flex flex-col justify-between">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Asignados</span>
            <div class="mt-3 flex items-end justify-between">
                <span class="text-display-lg text-on-surface">{{ number_format($stats['assigned']) }}</span>
                <span class="text-body-sm text-on-surface-variant">
                    {{ $stats['total'] ? round($stats['assigned'] / $stats['total'] * 100, 1) : 0 }}%
                </span>
            </div>
        </div>
        <div class="card p-5 flex flex-col justify-between">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Disponibles</span>
            <span class="mt-3 text-display-lg text-on-surface">{{ number_format($stats['available']) }}</span>
        </div>
        <div class="card p-5 flex flex-col justify-between">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">En reparación</span>
            <div class="mt-3 flex items-end justify-between">
                <span class="text-display-lg text-on-surface">{{ number_format($stats['repair']) }}</span>
                @if ($stats['repair'] > 0)
                    <span class="chip-alert">Atención</span>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="p-4">
            <livewire:admin.assets.assets-table />
        </div>
    </div>

    <livewire:admin.assets.asset-form />
</x-admin-layout>
