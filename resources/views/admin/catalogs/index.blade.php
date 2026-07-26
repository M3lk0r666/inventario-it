@php($def = \App\Support\CatalogRegistry::get($catalog))

<x-admin-layout :title="'Catálogos — ' . $def['label']"
    :breadcrumbs="[['name' => 'Catálogos'], ['name' => $def['label']]]">

    <x-page-header :title="$def['label']"
        description="Administra este catálogo; los cambios se reflejan en todos los módulos que lo usan.">
        <x-slot name="actions">
            @can('catalogs.create')
                <button type="button" class="btn-primary"
                    onclick="Livewire.dispatch('open-catalog-form', { catalog: '{{ $catalog }}', id: null })">
                    <i class="ri-add-line"></i> Nuevo
                </button>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="p-4">
            <livewire:admin.catalogs.catalog-table :catalog="$catalog" :key="'table-'.$catalog" />
        </div>
    </div>

    <livewire:admin.catalogs.catalog-form :catalog="$catalog" :key="'form-'.$catalog" />
</x-admin-layout>
