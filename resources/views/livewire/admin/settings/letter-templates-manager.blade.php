<div>
    @php($readonly = ! auth()->user()->can('settings.edit'))

    <div class="card p-6 max-w-2xl">
        <p class="text-body-sm text-on-surface-variant mb-3">Cada tipo lleva su propio prefijo de folio, consecutivo y texto, para no mezclarse.</p>

        <div class="mb-5 rounded-lg border border-border-soft bg-surface-container-low/40 p-3">
            <p class="text-body-sm text-on-surface-variant mb-2">
                <i class="ri-magic-line text-primary"></i> Puedes usar estos marcadores en los textos; se reemplazan con los datos reales al generar la carta:
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Services\ResponsiveLetterService::PLACEHOLDERS as $tag => $desc)
                    <span class="inline-flex items-center gap-1 text-body-sm">
                        <code class="px-1.5 py-0.5 rounded bg-white border border-border-soft text-primary font-mono">{{ $tag }}</code>
                        <span class="text-on-surface-variant">{{ $desc }}</span>
                    </span>
                @endforeach
            </div>
        </div>

        <div class="space-y-6">
            {{-- CAB --}}
            <div class="border border-border-soft rounded-lg p-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="chip-info">CAB</span>
                    <h4 class="text-title-md text-on-surface">Cartas de Aceptación de Bienes</h4>
                </div>
                <p class="text-body-sm text-on-surface-variant mb-3">Se genera cuando el empleado <strong>recibe</strong> los bienes (asignación).</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Prefijo de folio</label>
                        <input type="text" wire:model="cab_prefix" class="form-input" @disabled($readonly)>
                        @error('cab_prefix') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Folio inicial</label>
                        <input type="number" min="1" wire:model="cab_start" class="form-input" @disabled($readonly)>
                        @error('cab_start') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="form-help">Formato: {{ $cab_prefix }}-{{ now()->year }}-{{ str_pad($cab_start ?: '1', 4, '0', STR_PAD_LEFT) }} · el número avanza automáticamente y de forma consecutiva.</p>
                <div class="mt-3">
                    <label class="form-label">Texto de la carta</label>
                    <textarea wire:model="cab_text" rows="5" class="form-input" @disabled($readonly)></textarea>
                    @error('cab_text') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- CEB --}}
            <div class="border border-border-soft rounded-lg p-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="chip-warning">CEB</span>
                    <h4 class="text-title-md text-on-surface">Cartas de Entrega de Bienes</h4>
                </div>
                <p class="text-body-sm text-on-surface-variant mb-3">Se genera cuando el empleado <strong>devuelve/egresa</strong> los bienes (recepción).</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Prefijo de folio</label>
                        <input type="text" wire:model="ceb_prefix" class="form-input" @disabled($readonly)>
                        @error('ceb_prefix') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Folio inicial</label>
                        <input type="number" min="1" wire:model="ceb_start" class="form-input" @disabled($readonly)>
                        @error('ceb_start') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="form-help">Formato: {{ $ceb_prefix }}-{{ now()->year }}-{{ str_pad($ceb_start ?: '1', 4, '0', STR_PAD_LEFT) }} · el número avanza automáticamente y de forma consecutiva.</p>
                <div class="mt-3">
                    <label class="form-label">Texto de la carta</label>
                    <textarea wire:model="ceb_text" rows="5" class="form-input" @disabled($readonly)></textarea>
                    @error('ceb_text') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            @unless ($readonly)
                <div class="flex justify-end border-t border-border-soft pt-4">
                    <button type="button" class="btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">Guardar</button>
                </div>
            @endunless
        </div>
    </div>
</div>
