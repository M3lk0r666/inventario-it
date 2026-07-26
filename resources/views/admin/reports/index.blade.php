<x-admin-layout title="Reportes" :breadcrumbs="[['name' => 'Reportes']]">
    <x-page-header title="Reportes"
        description="Consulta y exporta a CSV o PDF los reportes del inventario." />

    <livewire:admin.reports.report-viewer />
</x-admin-layout>
