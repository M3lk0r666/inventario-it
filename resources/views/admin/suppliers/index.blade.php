<x-admin-layout title="Proveedores" :breadcrumbs="[['name' => 'Gestión'], ['name' => 'Proveedores']]">
    <x-page-header title="Proveedores"
        description="Directorio de proveedores con datos de contacto.">
        <x-slot name="actions">
            @can('suppliers.create')
                <button type="button" class="btn-primary" onclick="Livewire.dispatch('open-supplier-form', { id: null })">
                    <i class="ri-add-line"></i> Nuevo proveedor
                </button>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="p-4">
            <livewire:admin.suppliers.suppliers-table />
        </div>
    </div>

    <livewire:admin.suppliers.supplier-form />
</x-admin-layout>
