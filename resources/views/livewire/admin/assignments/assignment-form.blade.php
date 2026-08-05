<div>
    <x-slide-over model="open" title="Nueva asignación de bienes (entrega al colaborador)" width="max-w-xl" icon="ri-user-received-line">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="form-label">Empleado <span class="text-error">*</span></label>
                <x-searchable-select model="employeeId" :options="$employees"
                    placeholder="— Seleccionar empleado —" searchPlaceholder="Buscar por nombre…" />
                @error('employeeId') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Fecha de entrega <span class="text-error">*</span></label>
                    <input type="date" wire:model="assignedAt" class="form-input">
                    @error('assignedAt') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Estado físico al entregar <span class="text-error">*</span></label>
                    <select wire:model="condition" class="form-input">
                        @foreach ($conditions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('condition') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Selector de activos --}}
            <div class="border border-border-soft rounded-lg p-4">
                <label class="form-label">Activos a entregar <span class="text-error">*</span></label>

                @if ($selected->isNotEmpty())
                    <ul class="mb-3 divide-y divide-border-soft border border-border-soft rounded-lg overflow-hidden">
                        @foreach ($selected as $asset)
                            <li class="flex items-center justify-between gap-2 px-3 py-2 bg-surface-container-low/40"
                                wire:key="sel-{{ $asset->id }}">
                                <div class="min-w-0">
                                    <span class="text-mono-sm font-mono text-primary">{{ $asset->asset_tag }}</span>
                                    <span class="text-body-md text-on-surface ms-1">{{ $asset->name }}</span>
                                    <span class="text-body-sm text-on-surface-variant ms-1">({{ $asset->type?->name }})</span>
                                </div>
                                <button type="button" class="p-1 text-outline hover:text-alert" title="Quitar"
                                    wire:click="removeAsset({{ $asset->id }})">
                                    <i class="ri-close-line"></i>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="relative">
                    <i class="ri-search-line absolute start-3 top-1/2 -translate-y-1/2 text-outline"></i>
                    <input type="text" wire:model.live.debounce.300ms="assetSearch" class="form-input ps-9"
                        placeholder="Buscar disponible por etiqueta, nombre o serie…">
                </div>

                @if ($available->isNotEmpty())
                    <ul class="mt-2 divide-y divide-border-soft border border-border-soft rounded-lg overflow-hidden">
                        @foreach ($available as $asset)
                            <li wire:key="av-{{ $asset->id }}">
                                <button type="button" wire:click="addAsset({{ $asset->id }})"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-2 text-left hover:bg-surface-container-low transition-colors">
                                    <div class="min-w-0">
                                        <span class="text-mono-sm font-mono text-primary">{{ $asset->asset_tag }}</span>
                                        <span class="text-body-md text-on-surface ms-1">{{ $asset->name }}</span>
                                        <span class="text-body-sm text-on-surface-variant ms-1">({{ $asset->type?->name }})</span>
                                    </div>
                                    <i class="ri-add-line text-primary-container shrink-0"></i>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @elseif (filled($assetSearch))
                    <p class="form-help mt-2">Sin activos disponibles que coincidan.</p>
                @endif

                @error('selectedAssets') <p class="form-error mt-2">{{ $message }}</p> @enderror
            </div>

            {{-- Bienes adicionales (llaves, controles, accesos...) --}}
            @if ($additionalTypes->isNotEmpty())
                <div class="border border-border-soft rounded-lg p-4">
                    <label class="form-label">Bienes adicionales entregados</label>
                    <p class="text-body-sm text-on-surface-variant mb-3">Marca los que se entregan junto con el equipo.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($additionalTypes as $type)
                            <div wire:key="add-{{ $type->id }}"
                                class="flex items-start gap-2 p-2 rounded-lg {{ ($additionalChecked[$type->id] ?? false) ? 'bg-primary-fixed/30' : '' }}">
                                <input type="checkbox" wire:model.live="additionalChecked.{{ $type->id }}"
                                    id="add-{{ $type->id }}"
                                    class="mt-0.5 rounded border-border-soft text-primary-container focus:ring-primary-container">
                                <div class="flex-1 min-w-0">
                                    <label for="add-{{ $type->id }}" class="text-body-md text-on-surface cursor-pointer">{{ $type->name }}</label>
                                    @if ($type->requires_value && ($additionalChecked[$type->id] ?? false))
                                        <input type="text" wire:model="additionalValues.{{ $type->id }}"
                                            class="form-input mt-1 !py-1 text-body-sm"
                                            placeholder="{{ $type->value_label ?? 'Valor' }}">
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-body-sm text-on-surface-variant mt-2">
                        <i class="ri-information-line"></i> Si marcas bienes adicionales, se generará la carta responsiva.
                    </p>
                </div>
            @endif

            <div>
                <label class="form-label">Observaciones</label>
                <textarea wire:model="notes" rows="2" class="form-input"
                    placeholder="Condiciones de la entrega, accesorios incluidos…"></textarea>
            </div>

            @can('responsive_letters.create')
                <div class="space-y-2">
                    <label class="inline-flex items-center gap-2 text-body-md text-on-surface">
                        <input type="checkbox" wire:model="generateLetter"
                            class="rounded border-border-soft text-primary-container focus:ring-primary-container">
                        Generar carta responsiva (PDF con folio consecutivo)
                    </label>

                    <label class="flex items-start gap-2 text-body-md text-on-surface">
                        <input type="checkbox" wire:model="notifyEmployee"
                            class="mt-0.5 rounded border-border-soft text-primary-container focus:ring-primary-container">
                        <span>
                            Notificar al empleado por correo
                            <span class="block text-body-sm text-on-surface-variant">
                                Envía un aviso de los bienes asignados al correo del empleado (se le indicará que en breve recibirá su carta para firma).
                                @if (! $mailReady)
                                    <span class="text-alert">El correo no está configurado (Configuración → Correo).</span>
                                @elseif ($employeeId && blank($employeeEmail))
                                    <span class="text-alert">El empleado seleccionado no tiene correo registrado.</span>
                                @elseif ($employeeEmail)
                                    Se enviará a <span class="font-medium">{{ $employeeEmail }}</span>.
                                @endif
                            </span>
                        </span>
                    </label>

                    {{-- Notificación al jefe inmediato --}}
                    @if ($manager)
                        <div class="rounded-lg border border-border-soft bg-surface-container-low/40 p-3">
                            <div class="mb-1 text-body-sm text-on-surface-variant">
                                Jefe inmediato:
                                <span class="font-medium text-on-surface">{{ $manager->name }}</span>
                            </div>
                            <label class="flex items-start gap-2 text-body-md text-on-surface">
                                <input type="checkbox" wire:model="notifyManager"
                                    class="mt-0.5 rounded border-border-soft text-primary-container focus:ring-primary-container">
                                <span>
                                    Notificar al jefe inmediato
                                    <span class="block text-body-sm text-on-surface-variant">
                                        @if (blank($manager->email))
                                            <span class="text-alert">El jefe inmediato no tiene correo registrado.</span>
                                        @else
                                            Copia informativa a <span class="font-medium">{{ $manager->email }}</span>.
                                        @endif
                                    </span>
                                </span>
                            </label>
                        </div>
                    @endif
                </div>
            @endcan

            <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                <button type="button" class="btn-ghost" wire:click="$set('open', false)">Cancelar</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Registrar asignación</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </button>
            </div>
        </form>
    </x-slide-over>
</div>
