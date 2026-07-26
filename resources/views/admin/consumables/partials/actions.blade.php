<div class="flex items-center justify-end gap-1">
    <a href="{{ route('admin.consumables.show', $id) }}" class="btn-icon" title="Ver detalle / kardex">
        <i class="ri-eye-line text-base"></i>
    </a>
    @can('consumables.edit')
        <button type="button" onclick="Livewire.dispatch('open-consumable-form', { id: {{ $id }} })"
            class="btn-icon" title="Editar">
            <i class="ri-pencil-line text-base"></i>
        </button>
    @endcan
    @can('consumables.delete')
        <button type="button" onclick="Livewire.dispatch('confirm-consumable-delete', { id: {{ $id }} })"
            class="p-1.5 text-outline rounded-lg hover:text-alert hover:bg-alert/10 transition-colors" title="Eliminar">
            <i class="ri-delete-bin-line text-base"></i>
        </button>
    @endcan
</div>
