<div class="flex items-center justify-end gap-1">
    @can('users.edit')
        <button type="button" onclick="Livewire.dispatch('resend-user-access', { id: {{ $id }} })"
            class="btn-icon" title="Reenviar acceso por correo">
            <i class="ri-mail-send-line text-base"></i>
        </button>
        <button type="button" onclick="Livewire.dispatch('open-user-form', { id: {{ $id }} })" class="btn-icon" title="Editar">
            <i class="ri-pencil-line text-base"></i>
        </button>
    @endcan
    @can('users.delete')
        @if ($protected || $isSelf)
            <span class="p-1.5 text-outline/40" title="{{ $protected ? 'Cuenta protegida' : 'No puedes eliminarte' }}">
                <i class="ri-lock-2-line text-base"></i>
            </span>
        @else
            <button type="button" onclick="Livewire.dispatch('confirm-user-delete', { id: {{ $id }} })"
                class="p-1.5 text-outline rounded-lg hover:text-alert hover:bg-alert/10 transition-colors" title="Eliminar">
                <i class="ri-delete-bin-line text-base"></i>
            </button>
        @endif
    @endcan
</div>
