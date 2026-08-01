<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="inline-flex rounded-lg border border-border-soft overflow-hidden">
            @foreach (['upcoming' => 'Próximos', 'mine' => 'Míos', 'all' => 'Todos'] as $key => $label)
                <button type="button" wire:click="$set('filter', '{{ $key }}')"
                    class="px-3 py-2 text-label-md transition-colors {{ $filter === $key ? 'bg-primary-container text-white' : 'bg-white text-on-surface-variant hover:bg-surface-container-low' }} {{ ! $loop->first ? 'border-s border-border-soft' : '' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        @can('reminders.create')
            <button type="button" class="btn-primary" wire:click="openForm()">
                <i class="ri-add-line"></i> Nuevo recordatorio
            </button>
        @endcan
    </div>

    @if ($reminders->isEmpty())
        <div class="card p-10 text-center">
            <i class="ri-alarm-line text-4xl text-outline"></i>
            <p class="mt-2 text-body-md text-on-surface-variant">No hay recordatorios en esta vista.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-gutter">
            @foreach ($reminders as $reminder)
                @php($past = $reminder->ends_at && $reminder->ends_at->isPast())
                <div class="card p-5 flex flex-col {{ $past ? 'opacity-70' : '' }}" wire:key="rem-{{ $reminder->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-title-md text-on-surface">{{ $reminder->title }}</h3>
                        @if ($reminder->visibility === 'public')
                            <span class="chip-info shrink-0">Público</span>
                        @else
                            <span class="chip-neutral shrink-0">Privado</span>
                        @endif
                    </div>
                    @if ($reminder->body)
                        <p class="mt-2 text-body-md text-on-surface-variant flex-1">{{ $reminder->body }}</p>
                    @endif
                    <div class="mt-3 text-body-sm text-on-surface-variant">
                        <div class="flex items-center gap-1"><i class="ri-calendar-line"></i>
                            {{ $reminder->starts_at?->format('d/m/Y H:i') }}
                            @if ($reminder->ends_at) → {{ $reminder->ends_at->format('d/m/Y H:i') }} @endif
                        </div>
                        <div class="mt-1 flex items-center gap-1"><i class="ri-user-line"></i> {{ $reminder->user?->name }}</div>
                    </div>
                    @if ($reminder->user_id === auth()->id())
                        <div class="mt-3 pt-3 border-t border-border-soft flex justify-end gap-1">
                            @can('reminders.edit')
                                <button type="button" wire:click="openForm({{ $reminder->id }})" class="btn-icon" title="Editar">
                                    <i class="ri-pencil-line text-base"></i>
                                </button>
                            @endcan
                            @can('reminders.delete')
                                <button type="button" wire:click="confirmDelete({{ $reminder->id }})"
                                    class="p-1.5 text-outline rounded-lg hover:text-alert hover:bg-alert/10" title="Eliminar">
                                    <i class="ri-delete-bin-line text-base"></i>
                                </button>
                            @endcan
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $reminders->links() }}</div>
    @endif

    {{-- Formulario --}}
    <x-slide-over model="open" :title="$editingId ? 'Editar recordatorio' : 'Nuevo recordatorio'" icon="ri-alarm-line">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="form-label">Título <span class="text-error">*</span></label>
                <input type="text" wire:model="data.title" class="form-input">
                @error('data.title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Descripción</label>
                <textarea wire:model="data.body" rows="3" class="form-input"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Inicio <span class="text-error">*</span></label>
                    <input type="datetime-local" wire:model="data.starts_at" class="form-input">
                    @error('data.starts_at') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Fin</label>
                    <input type="datetime-local" wire:model="data.ends_at" class="form-input">
                    @error('data.ends_at') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="form-label">Visibilidad <span class="text-error">*</span></label>
                <select wire:model="data.visibility" class="form-input">
                    <option value="private">Privado (solo yo)</option>
                    <option value="public">Público (todos los usuarios)</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                <button type="button" class="btn-ghost" wire:click="$set('open', false)">Cancelar</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">Guardar</button>
            </div>
        </form>
    </x-slide-over>

    @if ($confirmingDeleteId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('confirmingDeleteId', null)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface">Eliminar recordatorio</h3>
                <p class="mt-1 text-body-md text-on-surface-variant">¿Seguro que deseas eliminarlo?</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('confirmingDeleteId', null)">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="delete" wire:loading.attr="disabled">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
