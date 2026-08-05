<div class="space-y-6">
    <form wire:submit="save" class="space-y-6">
        {{-- Ajustes globales --}}
        <div class="card p-5">
            <h3 class="text-title-md text-on-surface mb-1">Apariencia general</h3>
            <p class="text-body-sm text-on-surface-variant mb-4">Se aplica a todos los correos de aviso.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Color de acento</label>
                    <div class="flex items-center gap-2">
                        <input type="color" wire:model.live="accent" class="h-10 w-14 rounded border border-border-soft cursor-pointer">
                        <input type="text" wire:model="accent" class="form-input font-mono flex-1" placeholder="#0b56c4">
                    </div>
                    @error('accent') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Texto del pie</label>
                    <input type="text" wire:model="footer" class="form-input" placeholder="Mensaje automático de {empresa}...">
                    @error('footer') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Plantillas por correo --}}
        @foreach ($templates as $key => $def)
            <div class="card p-5" wire:key="tpl-{{ $key }}">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <h3 class="text-title-md text-on-surface">{{ $def['label'] }}</h3>
                        <p class="text-body-sm text-on-surface-variant">{{ $def['description'] }}</p>
                    </div>
                    <button type="button" wire:click="restore('{{ $key }}')"
                        class="btn-ghost text-body-sm shrink-0" title="Restaurar textos por defecto">
                        <i class="ri-arrow-go-back-line"></i> Restaurar
                    </button>
                </div>

                <div class="mb-3 flex flex-wrap gap-1.5">
                    <span class="text-body-sm text-on-surface-variant mr-1">Variables:</span>
                    @foreach ($def['vars'] as $var)
                        <code class="chip chip-neutral !py-0.5 !px-2 text-mono-sm">{{ $var }}</code>
                    @endforeach
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="form-label">Asunto</label>
                        <input type="text" wire:model="tpl.{{ $key }}.subject" class="form-input">
                        <p class="form-help">Se antepone automáticamente [{{ '{empresa}' }}] al asunto.</p>
                        @error("tpl.{$key}.subject") <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Introducción</label>
                        <textarea wire:model="tpl.{{ $key }}.intro" rows="3" class="form-input"></textarea>
                        @error("tpl.{$key}.intro") <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Nota destacada</label>
                        <textarea wire:model="tpl.{{ $key }}.note" rows="3" class="form-input"></textarea>
                        @error("tpl.{$key}.note") <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        @endforeach

        @can('settings.edit')
            <div class="flex justify-end">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Guardar plantillas</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </button>
            </div>
        @endcan
    </form>
</div>
