<div>
    <div class="grid grid-cols-1 lg:grid-cols-[260px,1fr] gap-gutter items-start">
        {{-- Categorías --}}
        <nav class="card p-2 lg:sticky lg:top-20">
            <button type="button" wire:click="$set('categoryFilter', null); $set('readingId', null)"
                class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-body-md text-left transition-colors {{ is_null($categoryFilter) ? 'bg-primary-fixed/40 text-primary font-medium' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                <span>Todas</span>
            </button>
            @foreach ($categories as $cat)
                <button type="button" wire:click="$set('categoryFilter', {{ $cat->id }}); $set('readingId', null)"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-body-md text-left transition-colors {{ $categoryFilter === $cat->id ? 'bg-primary-fixed/40 text-primary font-medium' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                    <span class="truncate">{{ $cat->name }}</span>
                    <span class="chip-neutral !px-2 !py-0.5 shrink-0">{{ $cat->articles_count }}</span>
                </button>
            @endforeach
        </nav>

        <div>
            {{-- LECTURA --}}
            @if ($reading)
                <div class="card p-6">
                    <button type="button" wire:click="backToList" class="text-body-sm text-primary hover:underline mb-4 inline-flex items-center gap-1">
                        <i class="ri-arrow-left-line"></i> Volver a la lista
                    </button>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="chip-info">{{ $reading->category?->name }}</span>
                            <h1 class="mt-2 text-headline-md text-on-surface">{{ $reading->title }}</h1>
                            <p class="mt-1 text-body-sm text-on-surface-variant">
                                {{ $reading->author?->name ?? 'Sistema' }} · {{ $reading->updated_at->format('d/m/Y') }} · {{ $reading->views }} vistas
                                @unless ($reading->is_published) <span class="chip-warning ms-1">Borrador</span> @endunless
                            </p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" wire:click="openShare({{ $reading->id }})" class="btn-secondary">
                                <i class="ri-mail-send-line"></i> Compartir por correo
                            </button>
                            @can('kb.edit')
                                <a href="{{ route('admin.kb.edit', $reading->id) }}" class="btn-icon" title="Editar">
                                    <i class="ri-pencil-line text-base"></i>
                                </a>
                            @endcan
                            @can('kb.delete')
                                <button type="button" wire:click="confirmDelete({{ $reading->id }})"
                                    class="p-1.5 text-outline rounded-lg hover:text-alert hover:bg-alert/10" title="Eliminar">
                                    <i class="ri-delete-bin-line text-base"></i>
                                </button>
                            @endcan
                        </div>
                    </div>
                    <div class="trix-content mt-5 max-w-none !min-h-0 !p-0">{!! $reading->body !!}</div>
                </div>
            @else
                {{-- LISTA --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="relative flex-1 min-w-[220px]">
                        <i class="ri-search-line absolute start-3 top-1/2 -translate-y-1/2 text-outline"></i>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-input ps-9" placeholder="Buscar artículos…">
                    </div>
                    @can('kb.create')
                        <a href="{{ route('admin.kb.create', $categoryFilter ? ['category' => $categoryFilter] : []) }}" class="btn-primary">
                            <i class="ri-add-line"></i> Nuevo artículo
                        </a>
                    @endcan
                </div>

                @if ($articles->isEmpty())
                    <div class="card p-10 text-center">
                        <i class="ri-book-open-line text-4xl text-outline"></i>
                        <p class="mt-2 text-body-md text-on-surface-variant">Sin artículos.</p>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($articles as $article)
                            <button type="button" wire:click="read({{ $article->id }})"
                                class="card w-full text-left p-4 hover:border-primary-container transition-colors" wire:key="kb-{{ $article->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="text-title-md text-on-surface">{{ $article->title }}</h3>
                                        <p class="mt-1 text-body-sm text-on-surface-variant">
                                            {{ $article->category?->name }} · {{ $article->author?->name ?? 'Sistema' }} · {{ $article->updated_at->format('d/m/Y') }}
                                            @unless ($article->is_published) <span class="chip-warning ms-1">Borrador</span> @endunless
                                        </p>
                                        <p class="mt-1 text-body-md text-on-surface-variant line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($article->body), 160) }}</p>
                                    </div>
                                    <i class="ri-arrow-right-s-line text-xl text-outline shrink-0"></i>
                                </div>
                            </button>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $articles->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    @if ($confirmingDeleteId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('confirmingDeleteId', null)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface">Eliminar artículo</h3>
                <p class="mt-1 text-body-md text-on-surface-variant">¿Seguro que deseas eliminarlo?</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('confirmingDeleteId', null)">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="delete" wire:loading.attr="disabled">Eliminar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Compartir por correo --}}
    <x-slide-over model="sharing" title="Compartir artículo por correo" width="max-w-lg">
        @unless ($mailReady)
            <div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-body-sm text-amber-800">
                <i class="ri-error-warning-line"></i>
                <span>El correo aún no está configurado. Se habilitará en Configuración (Fase 10). Puedes preparar los destinatarios, pero el envío fallará hasta configurarlo.</span>
            </div>
        @endunless

        <div class="space-y-4">
            {{-- Listas de distribución --}}
            <div>
                <label class="form-label">Listas de distribución</label>
                @if ($mailingLists->isEmpty())
                    <p class="text-body-sm text-on-surface-variant">
                        No hay listas. Créalas en Catálogos → Listas de correo.
                    </p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($mailingLists as $list)
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-border-soft text-body-md cursor-pointer {{ in_array($list->id, $shareLists) ? 'bg-primary-fixed/30' : '' }}">
                                <input type="checkbox" wire:model.live="shareLists" value="{{ $list->id }}"
                                    class="rounded border-border-soft text-primary-container focus:ring-primary-container">
                                <span class="min-w-0">
                                    <span class="block truncate">{{ $list->name }}</span>
                                    <span class="block text-body-sm text-on-surface-variant truncate">{{ $list->email }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Empleados --}}
            <div>
                <label class="form-label">Empleados (con correo)</label>
                @if ($selectedEmployees->isNotEmpty())
                    <div class="flex flex-wrap gap-1 mb-2">
                        @foreach ($selectedEmployees as $emp)
                            <span class="inline-flex items-center gap-1 chip-info" wire:key="shemp-{{ $emp->id }}">
                                {{ $emp->name }}
                                <button type="button" wire:click="removeEmployee({{ $emp->id }})" class="hover:text-alert"><i class="ri-close-line"></i></button>
                            </span>
                        @endforeach
                    </div>
                @endif
                <div class="relative">
                    <i class="ri-search-line absolute start-3 top-1/2 -translate-y-1/2 text-outline"></i>
                    <input type="text" wire:model.live.debounce.300ms="employeeSearch" class="form-input ps-9" placeholder="Buscar empleado…">
                </div>
                @if ($availableEmployees->isNotEmpty())
                    <ul class="mt-1 border border-border-soft rounded-lg divide-y divide-border-soft overflow-hidden">
                        @foreach ($availableEmployees as $emp)
                            <li>
                                <button type="button" wire:click="addEmployee({{ $emp->id }})"
                                    class="w-full flex items-center justify-between px-3 py-2 text-left hover:bg-surface-container-low">
                                    <span>{{ $emp->name }} <span class="text-body-sm text-on-surface-variant">{{ $emp->email }}</span></span>
                                    <i class="ri-add-line text-primary-container"></i>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Correos libres --}}
            <div>
                <label class="form-label">Otros correos (separados por coma)</label>
                <textarea wire:model="shareEmails" rows="2" class="form-input" placeholder="alguien@dominio.com, otro@dominio.com"></textarea>
                @error('shareEmails') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            {{-- Mensaje --}}
            <div>
                <label class="form-label">Mensaje (opcional)</label>
                <textarea wire:model="shareMessage" rows="2" class="form-input" placeholder="Nota para los destinatarios…"></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-border-soft pt-4 mt-4">
            <button type="button" class="btn-ghost" wire:click="$set('sharing', false)">Cancelar</button>
            <button type="button" class="btn-primary" wire:click="sendShare" wire:loading.attr="disabled" wire:target="sendShare">
                <span wire:loading.remove wire:target="sendShare"><i class="ri-mail-send-line"></i> Enviar</span>
                <span wire:loading wire:target="sendShare">Enviando…</span>
            </button>
        </div>
    </x-slide-over>
</div>
