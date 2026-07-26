<div class="flex items-center justify-end gap-1">
    @can('responsive_letters.view')
        <a href="{{ route('admin.letters.pdf', $id) }}" target="_blank" class="btn-icon" title="Descargar PDF">
            <i class="ri-download-2-line text-base"></i>
        </a>
    @endcan
    @if ($signed)
        <a href="{{ route('admin.letters.signed', $id) }}" target="_blank"
            class="p-1.5 text-success rounded-lg hover:bg-success/10" title="Ver carta firmada (evidencia)">
            <i class="ri-file-shield-2-line text-base"></i>
        </a>
    @endif
    @can('responsive_letters.reprint')
        <a href="{{ route('admin.letters.reprint', $id) }}" target="_blank" class="btn-icon" title="Reimprimir">
            <i class="ri-printer-line text-base"></i>
        </a>
    @endcan
    @if ($status !== 'cancelled')
        @can('responsive_letters.edit')
            <button type="button" onclick="Livewire.dispatch('sign-letter', { id: {{ $id }} })"
                class="p-1.5 text-outline rounded-lg hover:text-success hover:bg-success/10"
                title="{{ $signed ? 'Reemplazar carta firmada' : 'Subir carta firmada' }}">
                <i class="ri-quill-pen-line text-base"></i>
            </button>
        @endcan
        @can('responsive_letters.cancel')
            <button type="button" onclick="Livewire.dispatch('confirm-cancel-letter', { id: {{ $id }} })"
                class="p-1.5 text-outline rounded-lg hover:text-alert hover:bg-alert/10" title="Anular">
                <i class="ri-forbid-2-line text-base"></i>
            </button>
        @endcan
    @endif
</div>
