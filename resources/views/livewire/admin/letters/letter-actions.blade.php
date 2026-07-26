<div>
    {{-- Confirmar anulación --}}
    @if ($cancelId)
        <div class="fixed inset-0 z-[70] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('cancelId', null)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-full bg-error-container flex items-center justify-center">
                        <i class="ri-forbid-2-line text-on-error-container text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-title-md text-on-surface">Anular carta responsiva</h3>
                        <p class="mt-1 text-body-md text-on-surface-variant">
                            La carta <span class="font-semibold text-on-surface font-mono text-mono-sm">{{ $cancelFolio }}</span>
                            quedará anulada y su PDF llevará la marca de <strong>ANULADA</strong>.
                            Las asignaciones no se modifican.
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('cancelId', null)">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="cancelLetter" wire:loading.attr="disabled">Anular</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Subir carta firmada (evidencia) --}}
    @if ($signId)
        <div class="fixed inset-0 z-[70] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('signId', null)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-title-md text-on-surface">Registrar carta firmada</h3>
                    <button type="button" class="p-1 text-outline hover:text-on-surface rounded-lg" wire:click="$set('signId', null)">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <p class="text-body-md text-on-surface-variant mb-4">
                    Sube el escaneo o foto de la carta <span class="font-mono text-mono-sm text-primary">{{ $signFolio }}</span>
                    firmada por el colaborador. Al subirla, la carta quedará marcada como <strong>Firmada</strong>.
                </p>

                @if ($signHasExisting)
                    <div class="mb-3 flex items-center gap-2 text-body-sm text-on-surface-variant bg-surface-container-low rounded-lg p-2">
                        <i class="ri-information-line"></i> Ya existe una evidencia; al subir una nueva la reemplazarás.
                    </div>
                @endif

                <input type="file" wire:model="signedFile" accept=".pdf,image/*"
                    class="block w-full text-body-sm text-on-surface-variant file:me-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary-fixed file:text-primary file:text-label-md hover:file:opacity-80">
                <div wire:loading wire:target="signedFile" class="form-help">Cargando archivo…</div>
                @error('signedFile') <p class="form-error">{{ $message }}</p> @enderror

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('signId', null)">Cancelar</button>
                    <button type="button" class="btn-primary" wire:click="saveSigned"
                        wire:loading.attr="disabled" wire:target="signedFile, saveSigned"
                        @if (! $signedFile) disabled @endif>
                        <i class="ri-quill-pen-line"></i> Marcar firmada
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
