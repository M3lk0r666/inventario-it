<div>
    <x-slide-over model="open" :title="$editingId ? 'Editar empleado' : 'Nuevo empleado'" width="max-w-xl" icon="ri-team-line">
        <form wire:submit="save" x-data="{ tab: 'main' }" class="space-y-4">
            {{-- Pestañas --}}
            <div class="flex gap-1 border-b border-border-soft">
                <button type="button" @click="tab = 'main'"
                    :class="tab === 'main' ? 'border-primary text-primary font-medium' : 'border-transparent text-on-surface-variant'"
                    class="px-3 py-2 -mb-px border-b-2 text-body-md transition-colors">Datos</button>
                <button type="button" @click="tab = 'emergency'"
                    :class="tab === 'emergency' ? 'border-primary text-primary font-medium' : 'border-transparent text-on-surface-variant'"
                    class="px-3 py-2 -mb-px border-b-2 text-body-md transition-colors">Contacto de emergencia</button>
            </div>

            {{-- DATOS --}}
            <div x-show="tab === 'main'" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Número de empleado <span class="text-error">*</span></label>
                    <input type="text" wire:model="data.employee_number" class="form-input font-mono">
                    @error('data.employee_number') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Estado <span class="text-error">*</span></label>
                    <select wire:model="data.status" class="form-input">
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="form-label">Nombre completo <span class="text-error">*</span></label>
                <input type="text" wire:model="data.name" class="form-input">
                @error('data.name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Puesto</label>
                    <input type="text" wire:model="data.position" class="form-input">
                </div>
                <div>
                    <label class="form-label">Departamento</label>
                    <div class="flex items-center gap-2">
                        <select wire:model="data.department_id" class="form-input flex-1">
                            <option value="">— Sin departamento —</option>
                            @foreach ($departments as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @can('catalogs.create')
                            <button type="button" class="shrink-0 p-2 text-primary-container border border-primary-container/40 hover:bg-primary-fixed/40 rounded-lg"
                                onclick="Livewire.dispatch('open-quick-create', { catalog: 'departamentos' })" title="Agregar departamento">
                                <i class="ri-add-line"></i>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

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
                    <label class="form-label">Teléfono</label>
                    <input type="text" wire:model="data.phone" class="form-input">
                </div>
            </div>

            <div>
                <label class="form-label">Jefe inmediato</label>
                <select wire:model="data.manager_id" class="form-input">
                    <option value="">— Sin jefe inmediato —</option>
                    @foreach ($managers as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="form-help">Se incluye en las cartas responsivas y puede recibir aviso de los movimientos de bienes.</p>
                @error('data.manager_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Correo</label>
                    <input type="email" wire:model="data.email" class="form-input">
                    @error('data.email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Extensión Zoom</label>
                    <input type="text" wire:model="data.zoom_extension" class="form-input" placeholder="Ej. 1024">
                    @error('data.zoom_extension') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-lg border border-border-soft bg-surface-container-low/40 px-3 py-2 text-body-sm text-on-surface-variant">
                <i class="ri-information-line mr-1"></i>
                El acceso al portal se otorga desde el detalle del empleado, en la sección <span class="font-medium">Acceso al portal</span>.
            </div>

            <div>
                <label class="form-label">Notas</label>
                <textarea wire:model="data.notes" rows="2" class="form-input"></textarea>
            </div>
            </div>{{-- /DATOS --}}

            {{-- CONTACTO DE EMERGENCIA --}}
            <div x-show="tab === 'emergency'" x-cloak class="space-y-4">
                <p class="text-body-sm text-on-surface-variant">
                    <i class="ri-first-aid-kit-line mr-1 text-primary"></i>
                    Persona a contactar en caso de emergencia. Todos los campos son opcionales.
                </p>
                <div>
                    <label class="form-label">Nombre completo</label>
                    <input type="text" wire:model="data.emergency_contact_name" class="form-input">
                    @error('data.emergency_contact_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Parentesco</label>
                        <input type="text" wire:model="data.emergency_contact_relationship" class="form-input" placeholder="Ej. Cónyuge, Padre, Hermano">
                        @error('data.emergency_contact_relationship') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Teléfono</label>
                        <input type="text" wire:model="data.emergency_contact_phone" class="form-input">
                        @error('data.emergency_contact_phone') <p class="form-error">{{ $message }}</p> @enderror
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
                <h3 class="text-title-md text-on-surface">Eliminar empleado</h3>
                <p class="mt-1 text-body-md text-on-surface-variant">¿Eliminar <span class="font-semibold text-on-surface">{{ $confirmingDeleteLabel }}</span>? Se conserva el histórico (borrado lógico).</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="cancelDelete">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="delete" wire:loading.attr="disabled">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
