<x-admin-layout :title="'Activo ' . $asset->asset_tag"
    :breadcrumbs="[
        ['name' => 'Inventario'],
        ['name' => 'Activos', 'href' => route('admin.assets.index')],
        ['name' => $asset->asset_tag],
    ]">

    <livewire:admin.assets.asset-detail :asset-id="$asset->id" />

    <livewire:admin.assets.asset-form />
    <livewire:admin.assignments.assignment-form />
    <livewire:admin.assignments.return-form />
    @can('problems.create')
        <livewire:admin.problems.problem-form />
    @endcan
</x-admin-layout>
