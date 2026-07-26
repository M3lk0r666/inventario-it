<x-admin-layout title="Problemas" :breadcrumbs="[['name' => 'Soporte'], ['name' => 'Problemas']]">
    <x-page-header title="Problemas de soporte"
        description="Incidencias ligadas a activos: diagnóstico, seguimiento y costos de reparación.">
        <x-slot name="actions">
            @can('problems.create')
                <button type="button" class="btn-primary" onclick="Livewire.dispatch('open-problem-form', { id: null })">
                    <i class="ri-add-line"></i> Reportar problema
                </button>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-gutter mb-6">
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Total</span>
            <span class="mt-2 block text-display-lg text-on-surface">{{ number_format($stats['total']) }}</span>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Abiertos</span>
            <div class="mt-2 flex items-end justify-between">
                <span class="text-display-lg text-on-surface">{{ number_format($stats['open']) }}</span>
                @if ($stats['open'] > 0) <span class="chip-warning">En curso</span> @endif
            </div>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Críticos abiertos</span>
            <div class="mt-2 flex items-end justify-between">
                <span class="text-display-lg {{ $stats['critical'] > 0 ? 'text-alert' : 'text-on-surface' }}">{{ number_format($stats['critical']) }}</span>
                @if ($stats['critical'] > 0) <span class="chip-alert">Atención</span> @endif
            </div>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Costo reparaciones</span>
            <span class="mt-2 block text-display-lg text-on-surface">${{ number_format($stats['cost'], 2) }}</span>
        </div>
    </div>

    <div class="card">
        <div class="p-4">
            <livewire:admin.problems.problems-table />
        </div>
    </div>

    <livewire:admin.problems.problem-form />
</x-admin-layout>
