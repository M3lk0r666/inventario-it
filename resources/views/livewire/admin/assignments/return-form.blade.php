<div>
    @if ($open && $assignment)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('open', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-lg mx-4 p-6">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-title-md text-on-surface">Registrar devolución</h3>
                    <button type="button" class="p-1 text-outline hover:text-on-surface rounded-lg" wire:click="$set('open', false)">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <p class="text-body-sm text-on-surface-variant mb-4">
                    <span class="font-mono text-mono-sm text-primary">{{ $assignment->asset?->asset_tag }}</span>
                    {{ $assignment->asset?->name }} — asignado a
                    <span class="font-medium text-on-surface">{{ $assignment->employee?->name }}</span>
                    desde el {{ $assignment->assigned_at?->format('d/m/Y') }}
                </p>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Fecha de devolución <span class="text-error">*</span></label>
                            <input type="date" wire:model="returnedAt" class="form-input">
                            @error('returnedAt') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Estado físico al devolver <span class="text-error">*</span></label>
                            <select wire:model="condition" class="form-input">
                                @foreach ($conditions as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('condition') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Nuevo estado del activo <span class="text-error">*</span></label>
                        <select wire:model="newStatusId" class="form-input">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('newStatusId') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Observaciones de la devolución</label>
                        <textarea wire:model="notes" rows="2" class="form-input"
                            placeholder="Daños, faltantes, accesorios devueltos…"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-ghost" wire:click="$set('open', false)">Cancelar</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                            Registrar devolución
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
