<div>
    <x-slide-over model="open" :title="$editingId ? 'Editar usuario' : 'Nuevo usuario'" icon="ri-user-settings-line">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="form-label">Nombre <span class="text-error">*</span></label>
                <input type="text" wire:model="name" class="form-input">
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Correo <span class="text-error">*</span></label>
                <input type="email" wire:model="email" class="form-input">
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">
                    Contraseña @unless ($editingId) <span class="text-error">*</span> @endunless
                </label>
                <input type="password" wire:model="password" class="form-input"
                    placeholder="{{ $editingId ? 'Dejar vacío para no cambiar' : '' }}">
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Rol <span class="text-error">*</span></label>
                <select wire:model.live="role" class="form-input">
                    <option value="">— Seleccionar rol —</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r }}">{{ $r }}</option>
                    @endforeach
                </select>
                @error('role') <p class="form-error">{{ $message }}</p> @enderror
                @if ($role)
                    @can('users.edit')
                        <a href="{{ route('admin.roles.index', ['roleId' => \Spatie\Permission\Models\Role::where('name', $role)->value('id')]) }}"
                            class="mt-1 inline-flex items-center gap-1 text-body-sm text-primary hover:underline">
                            <i class="ri-eye-line"></i> Ver / editar permisos de "{{ $role }}"
                        </a>
                    @endcan
                @endif
            </div>
            @unless ($editingId)
                <label class="inline-flex items-center gap-2 text-body-md text-on-surface">
                    <input type="checkbox" wire:model="notify"
                        class="rounded border-border-soft text-primary-container focus:ring-primary-container">
                    Enviar correo de acceso (con enlace para establecer su contraseña)
                </label>
            @endunless

            <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                <button type="button" class="btn-ghost" wire:click="$set('open', false)">Cancelar</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">Guardar</button>
            </div>
        </form>
    </x-slide-over>

    @if ($confirmingResendId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('confirmingResendId', null)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface">Reenviar acceso</h3>
                <p class="mt-1 text-body-md text-on-surface-variant">Se enviará un nuevo correo con enlace para establecer la contraseña (válido 24 h) a <strong>{{ $confirmingResendLabel }}</strong>.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('confirmingResendId', null)">Cancelar</button>
                    <button type="button" class="btn-primary" wire:click="resendAccess" wire:loading.attr="disabled" wire:target="resendAccess">Reenviar</button>
                </div>
            </div>
        </div>
    @endif

    @if ($confirmingDeleteId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface">Eliminar usuario</h3>
                <p class="mt-1 text-body-md text-on-surface-variant">¿Eliminar <span class="font-semibold text-on-surface">{{ $confirmingDeleteLabel }}</span>? Perderá el acceso al sistema.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="cancelDelete">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="delete" wire:loading.attr="disabled">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
