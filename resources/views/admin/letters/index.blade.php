<x-admin-layout title="Cartas responsivas" :breadcrumbs="[['name' => 'Inventario'], ['name' => 'Cartas responsivas']]"
    x-data="{ view: 'grouped' }">
    <x-page-header title="Cartas responsivas"
        description="Expediente de cartas por colaborador: descarga, reimpresión, firma y anulación.">
        <x-slot name="actions">
            <div class="inline-flex rounded-lg border border-border-soft overflow-hidden">
                <button type="button" @click="view = 'grouped'"
                    :class="view === 'grouped' ? 'bg-primary-container text-white' : 'bg-white text-on-surface-variant'"
                    class="px-3 py-2 text-label-md transition-colors">
                    <i class="ri-group-line"></i> Por empleado
                </button>
                <button type="button" @click="view = 'list'"
                    :class="view === 'list' ? 'bg-primary-container text-white' : 'bg-white text-on-surface-variant'"
                    class="px-3 py-2 text-label-md transition-colors border-s border-border-soft">
                    <i class="ri-list-check"></i> Listado
                </button>
            </div>
        </x-slot>
    </x-page-header>

    <div x-show="view === 'grouped'" class="card p-4">
        <livewire:admin.letters.letters-by-employee />
    </div>

    <div x-show="view === 'list'" x-cloak class="card">
        <div class="p-4">
            <livewire:admin.letters.letters-table />
        </div>
    </div>

    {{-- Modales compartidos: anular y subir carta firmada --}}
    <livewire:admin.letters.letter-actions />
</x-admin-layout>
