<div>
    <x-slide-over model="open" :title="$editingId ? 'Editar proveedor' : 'Nuevo proveedor'" icon="ri-truck-line">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="form-label">Nombre / Razón social <span class="text-error">*</span></label>
                <input type="text" wire:model="data.name" class="form-input">
                @error('data.name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">RFC</label>
                    <input type="text" wire:model="data.rfc" class="form-input">
                    @error('data.rfc') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Nombre de contacto</label>
                    <input type="text" wire:model="data.contact_name" class="form-input">
                    @error('data.contact_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Correo</label>
                    <input type="email" wire:model="data.email" class="form-input">
                    @error('data.email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Teléfono</label>
                    <input type="text" wire:model="data.phone" class="form-input">
                    @error('data.phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="form-label">Sitio web</label>
                <input type="text" wire:model="data.website" class="form-input" placeholder="https://…">
                @error('data.website') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Dirección</label>
                <textarea wire:model="data.address" rows="2" class="form-input"></textarea>
            </div>

            <div>
                <label class="form-label">Notas</label>
                <textarea wire:model="data.notes" rows="2" class="form-input"></textarea>
            </div>

            <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                <button type="button" class="btn-ghost" wire:click="$set('open', false)">Cancelar</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">Guardar</button>
            </div>
        </form>
    </x-slide-over>

    @if ($confirmingDeleteId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-full bg-error-container flex items-center justify-center">
                        <i class="ri-delete-bin-line text-on-error-container text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-title-md text-on-surface">Eliminar proveedor</h3>
                        <p class="mt-1 text-body-md text-on-surface-variant">
                            ¿Eliminar <span class="font-semibold text-on-surface">{{ $confirmingDeleteLabel }}</span>?
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="cancelDelete">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="delete" wire:loading.attr="disabled">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
