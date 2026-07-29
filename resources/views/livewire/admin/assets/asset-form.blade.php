<div>
    <x-slide-over model="open" :title="$editingId ? 'Editar activo' : 'Dar de alta activo'" width="max-w-xl">
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Etiqueta / No. inventario <span class="text-error">*</span></label>
                    <input type="text" wire:model="data.asset_tag" class="form-input font-mono" placeholder="INV-00001">
                    @error('data.asset_tag') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Número de serie</label>
                    <input type="text" wire:model="data.serial_number" class="form-input font-mono">
                    @error('data.serial_number') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="form-label">Nombre <span class="text-error">*</span></label>
                <input type="text" wire:model="data.name" class="form-input" placeholder="Laptop Dell Latitude 5440">
                @error('data.name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Tipo <span class="text-error">*</span></label>
                    <div class="flex items-center gap-2">
                        <select wire:model.live="data.asset_type_id" class="form-input flex-1">
                            <option value="">— Seleccionar —</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @can('catalogs.create')
                            <button type="button" class="shrink-0 p-2 text-primary-container border border-primary-container/40 hover:bg-primary-fixed/40 rounded-lg"
                                onclick="Livewire.dispatch('open-quick-create', { catalog: 'tipos-de-activo' })" title="Agregar tipo">
                                <i class="ri-add-line"></i>
                            </button>
                        @endcan
                    </div>
                    @error('data.asset_type_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Modelo (marca)</label>
                    <select wire:model="data.asset_model_id" class="form-input">
                        <option value="">— Sin modelo —</option>
                        @foreach ($models as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('data.asset_model_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Estado <span class="text-error">*</span></label>
                    <x-badge-select model="data.asset_status_id" :options="$statuses" />
                    @can('catalogs.create')
                        <button type="button" class="mt-2 inline-flex items-center gap-1 text-body-sm text-primary hover:underline"
                            onclick="Livewire.dispatch('open-quick-create', { catalog: 'estados-de-activo' })">
                            <i class="ri-add-line"></i> Agregar estado
                        </button>
                    @endcan
                    @error('data.asset_status_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
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
                    @error('data.location_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                <div>
                    <label class="form-label">Fecha de compra</label>
                    <input type="date" wire:model="data.purchase_date" class="form-input">
                    @error('data.purchase_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Costo (MXN)</label>
                    <input type="number" step="0.01" min="0" wire:model="data.purchase_cost" class="form-input">
                    @error('data.purchase_cost') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="form-label">Garantía vigente hasta</label>
                <input type="date" wire:model="data.warranty_expires_at" class="form-input sm:max-w-[50%]">
                @error('data.warranty_expires_at') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            {{-- Specs dinámicos según tipo --}}
            @if (count($specFields))
                <div class="border border-border-soft rounded-lg p-4 bg-surface-container-low/40">
                    <h4 class="text-label-md text-on-surface-variant uppercase tracking-wider mb-3">Especificaciones</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($specFields as $spec)
                            <div wire:key="spec-{{ $spec['key'] }}">
                                <label class="form-label">{{ $spec['label'] }}</label>
                                <input type="text" wire:model="specs.{{ $spec['key'] }}" class="form-input">
                                @error('specs.' . $spec['key']) <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <label class="form-label">Notas</label>
                <textarea wire:model="data.notes" rows="2" class="form-input"></textarea>
            </div>

            {{-- Imágenes --}}
            <div class="border border-border-soft rounded-lg p-4">
                <h4 class="text-label-md text-on-surface-variant uppercase tracking-wider mb-3">Imágenes</h4>

                @if ($existingAttachments->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach ($existingAttachments as $attachment)
                            <div class="relative group" wire:key="att-{{ $attachment->id }}">
                                <img src="{{ $attachment->url() }}" alt="{{ $attachment->file_name }}"
                                    class="w-20 h-20 object-cover rounded-lg border border-border-soft">
                                @can('assets.edit')
                                    <button type="button" wire:click="deleteAttachment({{ $attachment->id }})"
                                        wire:confirm="¿Eliminar esta imagen?"
                                        class="absolute -top-1.5 -end-1.5 w-5 h-5 bg-alert text-white rounded-full text-[10px] leading-none hidden group-hover:flex items-center justify-center">
                                        <i class="ri-close-line"></i>
                                    </button>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                @endif

                <input type="file" wire:model="photos" multiple accept="image/*"
                    class="block w-full text-body-sm text-on-surface-variant file:me-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary-fixed file:text-primary file:text-label-md hover:file:opacity-80">
                <div wire:loading wire:target="photos" class="form-help">Subiendo imágenes…</div>
                @error('photos.*') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                <button type="button" class="btn-ghost" wire:click="$set('open', false)">Cancelar</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save, photos">
                    <span wire:loading.remove wire:target="save">Guardar</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </button>
            </div>
        </form>
    </x-slide-over>

    {{-- Confirmación de borrado --}}
    @if ($confirmingDeleteId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-full bg-error-container flex items-center justify-center">
                        <i class="ri-delete-bin-line text-on-error-container text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-title-md text-on-surface">Eliminar activo</h3>
                        <p class="mt-1 text-body-md text-on-surface-variant">
                            ¿Seguro que deseas eliminar <span class="font-semibold text-on-surface">{{ $confirmingDeleteLabel }}</span>?
                            El histórico se conserva (borrado lógico).
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="cancelDelete">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
