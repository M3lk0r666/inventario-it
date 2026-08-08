<div>
    <x-slide-over model="open" :title="$editingId ? 'Editar problema' : 'Reportar problema'" width="max-w-xl" icon="ri-error-warning-line">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="form-label">Título <span class="text-error">*</span></label>
                <input type="text" wire:model="data.title" class="form-input" placeholder="No enciende el equipo">
                @error('data.title') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Activo afectado <span class="text-error">*</span></label>
                <x-searchable-select model="assetId" :options="$assets"
                    placeholder="— Seleccionar activo —" searchPlaceholder="Buscar por etiqueta, nombre o serie…" />
                @error('data.asset_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Descripción</label>
                <textarea wire:model="data.description" rows="3" class="form-input"
                    placeholder="Detalle de la falla, síntomas, contexto…"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Categoría</label>
                    <div class="flex items-center gap-2">
                        <select wire:model="data.problem_category_id" class="form-input flex-1">
                            <option value="">— Sin categoría —</option>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @can('catalogs.create')
                            <button type="button" class="shrink-0 p-2 text-primary-container border border-primary-container/40 hover:bg-primary-fixed/40 rounded-lg"
                                onclick="Livewire.dispatch('open-quick-create', { catalog: 'categorias-de-problema' })" title="Agregar categoría">
                                <i class="ri-add-line"></i>
                            </button>
                        @endcan
                    </div>
                </div>
                <div>
                    <label class="form-label">Prioridad <span class="text-error">*</span></label>
                    <select wire:model="data.priority" class="form-input">
                        @foreach (\App\Models\Problem::PRIORITIES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Estado <span class="text-error">*</span></label>
                    <select wire:model="data.status" class="form-input">
                        @foreach (\App\Models\Problem::STATUSES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Costo reparación</label>
                    <input type="number" step="0.01" min="0" wire:model="data.cost" class="form-input">
                    @error('data.cost') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Fecha de reporte <span class="text-error">*</span></label>
                    <input type="datetime-local" wire:model="data.reported_at" class="form-input">
                    @error('data.reported_at') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="form-label">Responsable (técnico)</label>
                <select wire:model="data.assigned_to" class="form-input">
                    <option value="">— Sin asignar —</option>
                    @foreach ($technicians as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Adjuntos --}}
            <div class="border border-border-soft rounded-lg p-4">
                <label class="form-label">Adjuntos (evidencias, cotizaciones…)</label>
                <input type="file" wire:model="files" multiple
                    class="block w-full text-body-sm text-on-surface-variant file:me-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary-fixed file:text-primary file:text-label-md hover:file:opacity-80">
                <div wire:loading wire:target="files" class="form-help">Cargando archivos…</div>
                @error('files.*') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                <button type="button" class="btn-ghost" wire:click="$set('open', false)">Cancelar</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save, files">
                    <span wire:loading.remove wire:target="save">Guardar</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </button>
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
                        <h3 class="text-title-md text-on-surface">Eliminar problema</h3>
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
