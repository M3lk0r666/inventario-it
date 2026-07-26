<div class="flex items-center justify-end gap-1">
    <a href="{{ route('admin.assets.show', $id) }}" class="btn-icon" title="Ver detalle">
        <i class="ri-eye-line text-base"></i>
    </a>
    @can('assets.edit')
        <button type="button" onclick="Livewire.dispatch('open-asset-form', { id: {{ $id }} })"
            class="btn-icon" title="Editar">
            <i class="ri-pencil-line text-base"></i>
        </button>
    @endcan
    @can('assets.delete')
        <button type="button" onclick="Livewire.dispatch('confirm-asset-delete', { id: {{ $id }} })"
            class="p-1.5 text-outline rounded-lg hover:text-alert hover:bg-alert/10 transition-colors" title="Eliminar">
            <i class="ri-delete-bin-line text-base"></i>
        </button>
    @endcan
</div>
