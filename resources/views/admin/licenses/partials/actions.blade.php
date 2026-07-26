<div class="flex items-center justify-end gap-1">
    <a href="{{ route('admin.licenses.show', $id) }}"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-label-md text-primary-container border border-primary-container/40 rounded-lg hover:bg-primary-fixed/40 transition-colors"
        title="Ver detalle y asignar asientos">
        <i class="ri-eye-line"></i> Detalle
    </a>
    @can('licenses.edit')
        <button type="button" onclick="Livewire.dispatch('open-license-form', { id: {{ $id }} })"
            class="btn-icon" title="Editar">
            <i class="ri-pencil-line text-base"></i>
        </button>
    @endcan
    @can('licenses.delete')
        <button type="button" onclick="Livewire.dispatch('confirm-license-delete', { id: {{ $id }} })"
            class="p-1.5 text-outline rounded-lg hover:text-alert hover:bg-alert/10 transition-colors" title="Eliminar">
            <i class="ri-delete-bin-line text-base"></i>
        </button>
    @endcan
</div>
