<x-admin-layout title="Empleados" :breadcrumbs="[['name' => 'Gestión'], ['name' => 'Empleados']]">
    <x-page-header title="Empleados"
        description="Colaboradores de la empresa, sus cuentas y bienes asignados.">
        <x-slot name="actions">
            @can('employees.create')
                <button type="button" class="btn-primary" onclick="Livewire.dispatch('open-employee-form', { id: null })">
                    <i class="ri-user-add-line"></i> Nuevo empleado
                </button>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="p-4">
            <livewire:admin.employees.employees-table />
        </div>
    </div>

    <livewire:admin.employees.employee-form />
</x-admin-layout>
