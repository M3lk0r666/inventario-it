<x-admin-layout title="Auditoría" :breadcrumbs="[['name' => 'Administración'], ['name' => 'Auditoría']]">
    <x-page-header title="Bitácora de actividad"
        description="Registro de cambios del sistema: quién hizo qué y cuándo." />

    <livewire:admin.audit.activity-log />
</x-admin-layout>
