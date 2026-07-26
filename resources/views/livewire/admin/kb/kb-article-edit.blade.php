<form wire:submit="save">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-headline-md text-on-surface">{{ $articleId ? 'Editar artículo' : 'Nuevo artículo' }}</h2>
            <p class="mt-1 text-body-md text-on-surface-variant">Base de conocimientos del área de TI.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.kb.index') }}" class="btn-ghost">Cancelar</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save"><i class="ri-save-line"></i> Guardar</span>
                <span wire:loading wire:target="save">Guardando…</span>
            </button>
        </div>
    </div>

    <div class="card p-6 space-y-5">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr,280px] gap-5">
            <div>
                <label class="form-label">Título <span class="text-error">*</span></label>
                <input type="text" wire:model="title" class="form-input" placeholder="Título del artículo">
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Categoría <span class="text-error">*</span></label>
                <div class="flex items-center gap-2">
                    <select wire:model="kb_category_id" class="form-input flex-1">
                        <option value="">— Seleccionar —</option>
                        @foreach ($categoryOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @can('catalogs.create')
                        <button type="button" class="shrink-0 p-2 text-primary-container border border-primary-container/40 hover:bg-primary-fixed/40 rounded-lg"
                            onclick="Livewire.dispatch('open-quick-create', { catalog: 'categorias-kb' })" title="Agregar categoría">
                            <i class="ri-add-line"></i>
                        </button>
                    @endcan
                </div>
                @error('kb_category_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="form-label">Contenido <span class="text-error">*</span></label>
            <x-rich-text model="body" />
            @error('body') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <label class="inline-flex items-center gap-2 text-body-md text-on-surface">
            <input type="checkbox" wire:model="is_published"
                class="rounded border-border-soft text-primary-container focus:ring-primary-container">
            Publicado (visible para todos). Desmárcalo para guardarlo como borrador.
        </label>
    </div>
</form>
