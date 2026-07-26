<div>
    <x-slide-over model="open" title="Recepción de bienes (salida)" width="max-w-xl">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="form-label">Empleado <span class="text-error">*</span></label>
                <x-searchable-select model="employeeId" :options="$employees"
                    placeholder="— Seleccionar empleado con bienes asignados —" searchPlaceholder="Buscar por nombre…" />
                @error('employeeId') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Fecha de recepción <span class="text-error">*</span></label>
                    <input type="date" wire:model="returnedAt" class="form-input">
                    @error('returnedAt') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Nuevo estado de los activos <span class="text-error">*</span></label>
                    <select wire:model="newStatusId" class="form-input">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('newStatusId') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Activos asignados del empleado --}}
            <div class="border border-border-soft rounded-lg p-4">
                <label class="form-label">Activos a recibir <span class="text-error">*</span></label>
                @if (! $employeeId)
                    <p class="text-body-sm text-on-surface-variant">Selecciona un empleado para ver sus bienes asignados.</p>
                @elseif ($assignments->isEmpty())
                    <p class="text-body-sm text-on-surface-variant">Este empleado no tiene bienes asignados.</p>
                @else
                    <ul class="divide-y divide-border-soft border border-border-soft rounded-lg overflow-hidden">
                        @foreach ($assignments as $assignment)
                            <li class="p-3 {{ ($selectedAssignments[$assignment->id] ?? false) ? 'bg-primary-fixed/20' : '' }}"
                                wire:key="rec-{{ $assignment->id }}">
                                <div class="flex items-center justify-between gap-2">
                                    <label class="flex items-center gap-2 min-w-0 cursor-pointer">
                                        <input type="checkbox" wire:model.live="selectedAssignments.{{ $assignment->id }}"
                                            class="rounded border-border-soft text-primary-container focus:ring-primary-container">
                                        <span class="min-w-0">
                                            <span class="text-mono-sm font-mono text-primary">{{ $assignment->asset?->asset_tag }}</span>
                                            <span class="text-body-md text-on-surface ms-1">{{ $assignment->asset?->name }}</span>
                                            <span class="text-body-sm text-on-surface-variant ms-1">({{ $assignment->asset?->type?->name }})</span>
                                        </span>
                                    </label>
                                    @if ($selectedAssignments[$assignment->id] ?? false)
                                        <select wire:model="conditions.{{ $assignment->id }}" class="form-input !py-1 !w-auto text-body-sm shrink-0">
                                            @foreach ($conditionOptions as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @error('selectedAssignments') <p class="form-error mt-2">{{ $message }}</p> @enderror
            </div>

            {{-- Bienes adicionales recibidos --}}
            @if ($additionalTypes->isNotEmpty() && $employeeId)
                <div class="border border-border-soft rounded-lg p-4">
                    <label class="form-label">Bienes adicionales recibidos</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($additionalTypes as $type)
                            <div wire:key="rec-add-{{ $type->id }}"
                                class="flex items-start gap-2 p-2 rounded-lg {{ ($additionalChecked[$type->id] ?? false) ? 'bg-primary-fixed/30' : '' }}">
                                <input type="checkbox" wire:model.live="additionalChecked.{{ $type->id }}"
                                    id="rec-add-{{ $type->id }}"
                                    class="mt-0.5 rounded border-border-soft text-primary-container focus:ring-primary-container">
                                <div class="flex-1 min-w-0">
                                    <label for="rec-add-{{ $type->id }}" class="text-body-md text-on-surface cursor-pointer">{{ $type->name }}</label>
                                    @if ($type->requires_value && ($additionalChecked[$type->id] ?? false))
                                        <input type="text" wire:model="additionalValues.{{ $type->id }}"
                                            class="form-input mt-1 !py-1 text-body-sm" placeholder="{{ $type->value_label ?? 'Valor' }}">
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <label class="form-label">Observaciones</label>
                <textarea wire:model="notes" rows="2" class="form-input"
                    placeholder="Condiciones de la recepción, faltantes, daños…"></textarea>
            </div>

            @can('responsive_letters.create')
                <label class="inline-flex items-center gap-2 text-body-md text-on-surface">
                    <input type="checkbox" wire:model="generateLetter"
                        class="rounded border-border-soft text-primary-container focus:ring-primary-container">
                    Generar carta de recepción (PDF con folio consecutivo)
                </label>
            @endcan

            <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                <button type="button" class="btn-ghost" wire:click="$set('open', false)">Cancelar</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Registrar recepción</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </button>
            </div>
        </form>
    </x-slide-over>
</div>
