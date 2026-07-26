<x-admin-layout title="Roles y permisos" :breadcrumbs="[['name' => 'Administración'], ['name' => 'Usuarios', 'href' => route('admin.users.index')], ['name' => 'Roles y permisos']]">
    <x-page-header title="Roles y permisos"
        description="Define qué puede ver y hacer cada rol en cada módulo." />

    <livewire:admin.roles.role-manager />
</x-admin-layout>
