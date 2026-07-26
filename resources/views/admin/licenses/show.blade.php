<x-admin-layout :title="'Licencia — ' . $license->software_name"
    :breadcrumbs="[
        ['name' => 'Inventario'],
        ['name' => 'Licencias', 'href' => route('admin.licenses.index')],
        ['name' => $license->software_name],
    ]">

    <livewire:admin.licenses.license-detail :license-id="$license->id" />
    <livewire:admin.licenses.license-form />
</x-admin-layout>
