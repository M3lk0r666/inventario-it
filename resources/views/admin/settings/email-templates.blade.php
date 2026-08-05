<x-admin-layout title="Plantillas de correo"
    :breadcrumbs="[['name' => 'Administración'], ['name' => 'Plantillas de correo']]">
    <x-page-header title="Plantillas de correo"
        description="Personaliza los textos y el color de los correos de aviso/notificación del sistema." />

    <livewire:admin.settings.email-templates-manager />
</x-admin-layout>
