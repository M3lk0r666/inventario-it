<div>
    <x-slide-over model="open" :title="$editingId ? 'Editar licencia' : 'Nueva licencia'" width="max-w-xl" icon="ri-key-2-line">
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Software <span class="text-error">*</span></label>
                    <input type="text" wire:model="data.software_name" class="form-input" placeholder="Microsoft 365 Empresa">
                    @error('data.software_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Versión</label>
                    <input type="text" wire:model="data.version" class="form-input">
                    @error('data.version') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Tipo <span class="text-error">*</span></label>
                    <div class="flex items-center gap-2">
                        <select wire:model="data.license_type_id" class="form-input flex-1">
                            <option value="">— Seleccionar —</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @can('catalogs.create')
                            <button type="button" class="shrink-0 p-2 text-primary-container border border-primary-container/40 hover:bg-primary-fixed/40 rounded-lg"
                                onclick="Livewire.dispatch('open-quick-create', { catalog: 'tipos-de-licencia' })" title="Agregar tipo">
                                <i class="ri-add-line"></i>
                            </button>
                        @endcan
                    </div>
                    @error('data.license_type_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Asientos totales <span class="text-error">*</span></label>
                    <input type="number" min="1" wire:model="data.seats" class="form-input">
                    @error('data.seats') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="form-label">Clave(s) de producto</label>
                <textarea wire:model="data.product_key" rows="2" class="form-input font-mono text-mono-sm"></textarea>
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
                </div>
                <div>
                    <label class="form-label">Costo</label>
                    <input type="number" step="0.01" min="0" wire:model="data.cost" class="form-input">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Expiración <span class="text-body-sm text-on-surface-variant">(vacío = perpetua)</span></label>
                    <input type="date" wire:model="data.expires_at" class="form-input">
                    @error('data.expires_at') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Fecha de renovación</label>
                    <input type="date" wire:model="data.renewal_date" class="form-input">
                    <p class="form-help">Fecha límite para renovar antes de que expire.</p>
                    @error('data.renewal_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Configuración de alerta de renovación --}}
            <div class="border border-border-soft rounded-lg p-4 bg-surface-container-low/40">
                <label class="inline-flex items-center gap-2 text-body-md text-on-surface">
                    <input type="checkbox" wire:model="data.alerts_enabled"
                        class="rounded border-border-soft text-primary-container focus:ring-primary-container">
                    Alertar la renovación
                </label>
                <div class="mt-3 flex items-center gap-2 text-body-md text-on-surface-variant">
                    <span>Avisar</span>
                    <input type="number" min="1" max="365" wire:model="data.alert_days_before" class="form-input !w-20 !py-1">
                    <span>días antes de la fecha de renovación.</span>
                </div>
                @error('data.alert_days_before') <p class="form-error">{{ $message }}</p> @enderror
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
                        <h3 class="text-title-md text-on-surface">Eliminar licencia</h3>
                        <p class="mt-1 text-body-md text-on-surface-variant">
                            ¿Eliminar <span class="font-semibold text-on-surface">{{ $confirmingDeleteLabel }}</span>?
                            Se conserva el histórico (borrado lógico).
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
