<div>
    <x-slide-over model="open" :title="$editingId ? 'Editar consumible' : 'Nuevo consumible'" icon="ri-archive-line">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="form-label">Nombre <span class="text-error">*</span></label>
                <input type="text" wire:model="data.name" class="form-input" placeholder="Tóner HP 85A">
                @error('data.name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Descripción</label>
                <textarea wire:model="data.description" rows="2" class="form-input"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Unidad <span class="text-error">*</span></label>
                    <input type="text" wire:model="data.unit" class="form-input" placeholder="pieza, caja, litro…">
                    @error('data.unit') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Mínimo (alerta) <span class="text-error">*</span></label>
                    <input type="number" min="0" wire:model="data.min_stock" class="form-input">
                    @error('data.min_stock') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            @unless ($editingId)
                <div>
                    <label class="form-label">Existencia inicial <span class="text-error">*</span></label>
                    <input type="number" min="0" wire:model="data.initial_stock" class="form-input">
                    <p class="form-help">Se registra como primer movimiento de entrada en el kardex.</p>
                    @error('data.initial_stock') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            @endunless

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Ubicación</label>
                    <div class="flex items-center gap-2">
                        <select wire:model="data.location_id" class="form-input flex-1">
                            <option value="">— Sin ubicación —</option>
                            @foreach ($locations as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @can('catalogs.create')
                            <button type="button" class="shrink-0 p-2 text-primary-container border border-primary-container/40 hover:bg-primary-fixed/40 rounded-lg"
                                onclick="Livewire.dispatch('open-quick-create', { catalog: 'ubicaciones' })" title="Agregar ubicación">
                                <i class="ri-add-line"></i>
                            </button>
                        @endcan
                    </div>
                </div>
                <div>
                    <label class="form-label">Proveedor</label>
                    <div class="flex items-center gap-2">
                        <select wire:model="data.supplier_id" class="form-input flex-1">
                            <option value="">— Sin proveedor —</option>
                            @foreach ($suppliers as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @can('catalogs.create')
                            <button type="button" class="shrink-0 p-2 text-primary-container border border-primary-container/40 hover:bg-primary-fixed/40 rounded-lg"
                                onclick="Livewire.dispatch('open-quick-create', { catalog: 'proveedores' })" title="Agregar proveedor">
                                <i class="ri-add-line"></i>
                            </button>
                        @endcan
                    </div>
                </div>
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
                        <h3 class="text-title-md text-on-surface">Eliminar consumible</h3>
                        <p class="mt-1 text-body-md text-on-surface-variant">
                            ¿Eliminar <span class="font-semibold text-on-surface">{{ $confirmingDeleteLabel }}</span>?
                            El histórico de movimientos se conserva (borrado lógico).
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
