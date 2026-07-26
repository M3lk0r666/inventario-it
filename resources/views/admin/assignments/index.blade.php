<x-admin-layout title="Asignaciones" :breadcrumbs="[['name' => 'Inventario'], ['name' => 'Asignaciones']]">
    <x-page-header title="Asignaciones de bienes"
        description="Entrega y devolución de activos a empleados, con su histórico completo.">
        <x-slot name="actions">
            @can('assignments.edit')
                <button type="button" class="btn-ghost" onclick="Livewire.dispatch('open-reception-form')">
                    <i class="ri-inbox-archive-line"></i> Recepción (salida)
                </button>
            @endcan
            @can('assignments.create')
                <button type="button" class="btn-primary" onclick="Livewire.dispatch('open-assignment-form', { assetId: null })">
                    <i class="ri-user-received-line"></i> Nueva asignación
                </button>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="p-4">
            <livewire:admin.assignments.assignments-table />
        </div>
    </div>

    <livewire:admin.assignments.assignment-form />
    <livewire:admin.assignments.return-form />
    <livewire:admin.assignments.reception-form />
</x-admin-layout>
