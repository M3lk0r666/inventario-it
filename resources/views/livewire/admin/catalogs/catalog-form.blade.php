<div>
    <x-slide-over model="open" :title="($editingId ? 'Editar ' : 'Crear ') . $def['singular']">
        <form wire:submit="save" class="space-y-4">
            @foreach ($def['fields'] as $field)
                <div wire:key="field-{{ $catalog }}-{{ $field['key'] }}">
                    @if ($field['type'] === 'checkbox')
                        <label class="inline-flex items-center gap-2 text-body-md text-on-surface">
                            <input type="checkbox" wire:model="data.{{ $field['key'] }}"
                                class="rounded border-border-soft text-primary-container focus:ring-primary-container">
                            {{ $field['label'] }}
                        </label>
                    @else
                        <label class="form-label">
                            {{ $field['label'] }}
                            @if (in_array('required', $field['rules']))
                                <span class="text-error">*</span>
                            @endif
                        </label>

                        @if ($field['type'] === 'text')
                            <input type="text" wire:model="data.{{ $field['key'] }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}" class="form-input">
                        @elseif ($field['type'] === 'textarea')
                            <textarea wire:model="data.{{ $field['key'] }}" rows="3" class="form-input"></textarea>
                        @elseif ($field['type'] === 'json')
                            <textarea wire:model="data.{{ $field['key'] }}" rows="8" spellcheck="false"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                class="form-input font-mono text-mono-sm"></textarea>
                        @elseif ($field['type'] === 'select')
                            <select wire:model="data.{{ $field['key'] }}" class="form-input">
                                <option value="">— Seleccionar —</option>
                                @foreach ($field['options'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @elseif ($field['type'] === 'select-catalog')
                            <div class="flex items-center gap-2">
                                <select wire:model="data.{{ $field['key'] }}" class="form-input flex-1">
                                    <option value="">— Seleccionar —</option>
                                    @foreach ($options[$field['key']] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @can('catalogs.create')
                                    <button type="button" title="Agregar {{ mb_strtolower($field['label']) }}"
                                        onclick="Livewire.dispatch('open-quick-create', { catalog: '{{ $field['catalog'] }}' })"
                                        class="shrink-0 p-2 text-primary-container border border-primary-container/40 hover:bg-primary-fixed/40 rounded-lg transition-colors">
                                        <i class="ri-add-line"></i>
                                    </button>
                                @endcan
                            </div>
                        @endif

                        @isset($field['help'])
                            <p class="form-help">{{ $field['help'] }}</p>
                        @endisset
                    @endif

                    @error('data.' . $field['key'])
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                <button type="button" class="btn-ghost" wire:click="$set('open', false)">Cancelar</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
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
                        <h3 class="text-title-md text-on-surface">Eliminar {{ mb_strtolower($def['singular']) }}</h3>
                        <p class="mt-1 text-body-md text-on-surface-variant">
                            ¿Seguro que deseas eliminar <span class="font-semibold text-on-surface">{{ $confirmingDeleteLabel }}</span>?
                            Esta acción puede revertirse desde la base de datos (borrado lógico).
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="cancelDelete">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="delete"
                        wire:loading.attr="disabled" wire:target="delete">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
