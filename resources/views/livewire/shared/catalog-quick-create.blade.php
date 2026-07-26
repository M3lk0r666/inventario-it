<div>
    @if ($open && $def)
        <div class="fixed inset-0 z-[70] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('open', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-sm mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-title-md text-on-surface">Crear {{ $def['singular'] }}</h3>
                    <button type="button" class="p-1 text-outline hover:text-on-surface rounded-lg"
                        wire:click="$set('open', false)">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    @foreach ($fields as $field)
                        <div wire:key="quick-{{ $catalog }}-{{ $field['key'] }}">
                            <label class="form-label">
                                {{ $field['label'] }}
                                @if (in_array('required', $field['rules']))
                                    <span class="text-error">*</span>
                                @endif
                            </label>
                            <input type="text" wire:model="data.{{ $field['key'] }}" class="form-input">
                            @error('data.' . $field['key'])
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-ghost" wire:click="$set('open', false)">Cancelar</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
