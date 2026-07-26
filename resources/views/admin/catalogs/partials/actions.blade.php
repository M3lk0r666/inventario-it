<div class="flex items-center justify-end gap-1">
    @can('catalogs.edit')
        <button type="button"
            onclick="Livewire.dispatch('open-catalog-form', { catalog: '{{ $catalog }}', id: {{ $id }} })"
            class="btn-icon" title="Editar">
            <i class="ri-pencil-line text-base"></i>
        </button>
    @endcan
    @can('catalogs.delete')
        <button type="button"
            onclick="Livewire.dispatch('confirm-catalog-delete', { catalog: '{{ $catalog }}', id: {{ $id }} })"
            class="p-1.5 text-outline rounded-lg hover:text-alert hover:bg-alert/10 transition-colors" title="Eliminar">
            <i class="ri-delete-bin-line text-base"></i>
        </button>
    @endcan
</div>
