<x-admin-layout title="Usuarios" :breadcrumbs="[['name' => 'Administración'], ['name' => 'Usuarios']]">
    <x-page-header title="Usuarios del sistema"
        description="Cuentas de acceso al portal y sus roles.">
        <x-slot name="actions">
            @can('users.edit')
                <a href="{{ route('admin.roles.index') }}" class="btn-ghost">
                    <i class="ri-shield-keyhole-line"></i> Roles y permisos
                </a>
            @endcan
            @can('users.create')
                <button type="button" class="btn-primary" onclick="Livewire.dispatch('open-user-form', { id: null })">
                    <i class="ri-user-add-line"></i> Nuevo usuario
                </button>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="p-4">
            <livewire:admin.users.users-table />
        </div>
    </div>

    <livewire:admin.users.user-form />
</x-admin-layout>
