<x-admin-layout :title="'Consumible — ' . $consumable->name"
    :breadcrumbs="[
        ['name' => 'Inventario'],
        ['name' => 'Consumibles', 'href' => route('admin.consumables.index')],
        ['name' => $consumable->name],
    ]">

    <livewire:admin.consumables.consumable-detail :consumable-id="$consumable->id" />
    <livewire:admin.consumables.consumable-form />
</x-admin-layout>
