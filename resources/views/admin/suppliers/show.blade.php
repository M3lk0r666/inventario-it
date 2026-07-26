<x-admin-layout :title="'Proveedor — ' . $supplier->name"
    :breadcrumbs="[
        ['name' => 'Gestión'],
        ['name' => 'Proveedores', 'href' => route('admin.suppliers.index')],
        ['name' => $supplier->name],
    ]">

    <x-page-header :title="$supplier->name" :description="$supplier->rfc ? 'RFC: '.$supplier->rfc : null">
        <x-slot name="actions">
            @can('suppliers.edit')
                <button type="button" class="btn-primary" onclick="Livewire.dispatch('open-supplier-form', { id: {{ $supplier->id }} })">
                    <i class="ri-pencil-line"></i> Editar
                </button>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-[340px,1fr] gap-gutter items-start">
        {{-- Datos de contacto --}}
        <div class="card p-5">
            <h3 class="text-label-md text-on-surface-variant uppercase tracking-wider mb-3">Contacto</h3>
            <dl class="space-y-3">
                @foreach ([
                    'Contacto' => $supplier->contact_name,
                    'Correo' => $supplier->email,
                    'Teléfono' => $supplier->phone,
                    'Sitio web' => $supplier->website,
                    'Dirección' => $supplier->address,
                ] as $label => $value)
                    <div>
                        <dt class="text-body-sm text-on-surface-variant">{{ $label }}</dt>
                        <dd class="text-body-md text-on-surface">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
            @if ($supplier->notes)
                <div class="mt-4 border-t border-border-soft pt-3">
                    <dt class="text-body-sm text-on-surface-variant">Notas</dt>
                    <dd class="text-body-md text-on-surface whitespace-pre-line">{{ $supplier->notes }}</dd>
                </div>
            @endif
        </div>

        {{-- Relacionados --}}
        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-gutter">
                <div class="card p-5">
                    <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Activos suministrados</span>
                    <span class="mt-2 block text-display-lg text-on-surface">{{ $supplier->assets_count }}</span>
                </div>
                <div class="card p-5">
                    <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Licencias</span>
                    <span class="mt-2 block text-display-lg text-on-surface">{{ $supplier->licenses_count }}</span>
                </div>
            </div>

            @if ($supplier->assets->isNotEmpty())
                <div class="card">
                    <div class="px-4 py-3 border-b border-border-soft">
                        <h3 class="text-title-md text-on-surface">Activos de este proveedor</h3>
                    </div>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left">
                            <thead class="bg-[#F9FAFB] border-b border-border-soft">
                                <tr>
                                    @foreach (['Etiqueta', 'Nombre', 'Tipo', 'Estado'] as $th)
                                        <th class="px-4 py-table-cell-padding text-label-md text-on-surface-variant uppercase tracking-wider">{{ $th }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-soft">
                                @foreach ($supplier->assets as $asset)
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.assets.show', $asset->id) }}" class="text-mono-sm font-mono text-primary hover:underline">{{ $asset->asset_tag }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-body-md">{{ $asset->name }}</td>
                                        <td class="px-4 py-3 text-body-md">{{ $asset->type?->name }}</td>
                                        <td class="px-4 py-3 text-body-md">{{ $asset->status?->name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <livewire:admin.suppliers.supplier-form />
</x-admin-layout>
