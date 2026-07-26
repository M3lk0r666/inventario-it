<x-admin-layout title="Recordatorios" :breadcrumbs="[['name' => 'Herramientas'], ['name' => 'Recordatorios']]">
    <x-page-header title="Recordatorios"
        description="Avisos con fechas; comparte los públicos con todo el equipo o mantenlos privados." />

    <livewire:admin.reminders.reminders-manager />
</x-admin-layout>
