<x-admin-layout title="Licencias" :breadcrumbs="[['name' => 'Inventario'], ['name' => 'Licencias']]">
    <x-page-header title="Gestión de licencias"
        description="Supervisión y asignación de activos de software corporativos.">
        <x-slot name="actions">
            @can('licenses.create')
                <button type="button" class="btn-primary" onclick="Livewire.dispatch('open-license-form', { id: null })">
                    <i class="ri-add-line"></i> Nueva licencia
                </button>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-gutter mb-6">
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Total licencias</span>
            <span class="mt-2 block text-display-lg text-on-surface">{{ number_format($stats['total']) }}</span>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Por vencer (60 días)</span>
            <div class="mt-2 flex items-end justify-between">
                <span class="text-display-lg text-on-surface">{{ number_format($stats['expiring']) }}</span>
                @if ($stats['expiring'] > 0) <span class="chip-warning">Atención</span> @endif
            </div>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Vencidas</span>
            <div class="mt-2 flex items-end justify-between">
                <span class="text-display-lg text-on-surface">{{ number_format($stats['expired']) }}</span>
                @if ($stats['expired'] > 0) <span class="chip-alert">Revisar</span> @endif
            </div>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Renovaciones por atender</span>
            <div class="mt-2 flex items-end justify-between">
                <span class="text-display-lg {{ $stats['renewal_alerts'] > 0 ? 'text-alert' : 'text-on-surface' }}">{{ number_format($stats['renewal_alerts']) }}</span>
                @if ($stats['renewal_alerts'] > 0) <span class="chip-alert">Alerta</span> @endif
            </div>
        </div>
    </div>

    <div class="card p-5 mb-6">
        <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Asientos usados (global)</span>
        <span class="mt-2 block text-title-md text-on-surface">{{ $stats['used_seats'] }} / {{ $stats['total_seats'] }}</span>
    </div>

    <div class="card">
        <div class="p-4">
            <livewire:admin.licenses.licenses-table />
        </div>
    </div>

    <livewire:admin.licenses.license-form />
</x-admin-layout>
