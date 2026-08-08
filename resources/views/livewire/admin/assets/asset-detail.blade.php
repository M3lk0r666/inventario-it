@php($chip = $chipByColor[$asset->status?->color] ?? 'chip-neutral')
@php($sections = [
    'info' => ['label' => 'Información', 'icon' => 'ri-information-line', 'count' => null],
    'specs' => ['label' => 'Especificaciones', 'icon' => 'ri-cpu-line', 'count' => null],
    'assignments' => ['label' => 'Asignaciones', 'icon' => 'ri-user-received-line', 'count' => $asset->assignments->count()],
    'problems' => ['label' => 'Problemas', 'icon' => 'ri-error-warning-line', 'count' => $asset->problems->count()],
    'licenses' => ['label' => 'Licencias', 'icon' => 'ri-key-2-line', 'count' => $asset->licenseAssignments->count()],
    'attachments' => ['label' => 'Adjuntos', 'icon' => 'ri-attachment-2', 'count' => $asset->attachments->count()],
    'notes' => ['label' => 'Notas', 'icon' => 'ri-sticky-note-line', 'count' => $asset->deviceNotes->count()],
    'history' => ['label' => 'Histórico', 'icon' => 'ri-history-line', 'count' => $activities->count()],
])

<div>
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-headline-md text-on-surface">{{ $asset->name }}</h2>
                <span class="{{ $chip }}">{{ $asset->status?->name }}</span>
            </div>
            <p class="mt-1 text-body-md text-on-surface-variant">
                <span class="font-mono text-mono-sm text-primary">{{ $asset->asset_tag }}</span>
                · {{ $asset->type?->name }}
                @if ($asset->model)
                    · {{ trim(($asset->model->manufacturer?->name ?? '').' '.$asset->model->name) }}
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @can('assets.change_status')
                <button type="button" class="btn-ghost" wire:click="openChangeStatus">
                    <i class="ri-exchange-line"></i> Cambiar estado
                </button>
                @if ($asset->status?->slug !== 'baja')
                    <button type="button" class="btn-ghost !text-alert !border-alert/40 hover:!bg-alert/10" wire:click="confirmRetire">
                        <i class="ri-inbox-unarchive-line"></i> Dar de baja
                    </button>
                @endif
            @endcan
            @can('assets.edit')
                <button type="button" class="btn-primary"
                    onclick="Livewire.dispatch('open-asset-form', { id: {{ $asset->id }} })">
                    <i class="ri-pencil-line"></i> Editar
                </button>
            @endcan
        </div>
    </div>

    {{-- Layout GLPI: menú de secciones a la izquierda, contenido a la derecha --}}
    <div class="grid grid-cols-1 lg:grid-cols-[230px,1fr] gap-gutter items-start">

        {{-- Menú lateral de secciones --}}
        <nav class="card overflow-hidden lg:sticky lg:top-20">
            <ul class="divide-y divide-border-soft">
                @foreach ($sections as $key => $section)
                    <li>
                        <button type="button" wire:click="$set('tab', '{{ $key }}')"
                            class="w-full flex items-center gap-2 px-4 py-2.5 text-body-md border-l-4 transition-colors text-left
                                {{ $tab === $key
                                    ? 'border-primary-container bg-surface-container-low text-primary font-bold'
                                    : 'border-transparent text-on-surface-variant hover:bg-surface-container-low' }}">
                            <i class="{{ $section['icon'] }}"></i>
                            <span class="flex-1">{{ $section['label'] }}</span>
                            @if ($section['count'] !== null && $section['count'] > 0)
                                <span class="chip-neutral !px-2 !py-0.5">{{ $section['count'] }}</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Contenido de la sección --}}
        <div class="card p-6 min-h-[420px]">
            {{-- INFORMACIÓN --}}
            @if ($tab === 'info')
                @php($images = $asset->attachments->filter->isImage()->values())
                <div class="grid grid-cols-1 {{ $images->isNotEmpty() ? 'xl:grid-cols-[1fr,300px]' : '' }} gap-8 items-start"
                    @if ($images->isNotEmpty())
                        x-data='{
                            images: @json($images->map(fn ($i) => ["url" => $i->url(), "name" => $i->file_name])),
                            show: false,
                            index: 0,
                            open(i) { this.index = i; this.show = true },
                            next() { this.index = (this.index + 1) % this.images.length },
                            prev() { this.index = (this.index - 1 + this.images.length) % this.images.length },
                        }'
                        @keydown.escape.window="show = false"
                        @keydown.arrow-right.window="show && next()"
                        @keydown.arrow-left.window="show && prev()"
                    @endif>
                <div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-x-6 gap-y-4">
                    @foreach ([
                        'Etiqueta' => $asset->asset_tag,
                        'Número de serie' => $asset->serial_number,
                        'Tipo' => $asset->type?->name,
                        'Fabricante' => $asset->model?->manufacturer?->name,
                        'Modelo' => $asset->model?->name,
                        'Estado' => $asset->status?->name,
                        'Ubicación' => $asset->location?->name,
                        'Proveedor' => $asset->supplier?->name,
                        'Asignado a' => $asset->currentAssignment?->employee?->name,
                        'Fecha de compra' => $asset->purchase_date?->format('d/m/Y'),
                        'Costo de compra' => $asset->purchase_cost !== null ? '$'.number_format((float) $asset->purchase_cost, 2) : null,
                        'Garantía hasta' => $asset->warranty_expires_at?->format('d/m/Y'),
                    ] as $label => $value)
                        <div>
                            <dt class="text-label-md text-on-surface-variant uppercase tracking-wider">{{ $label }}</dt>
                            <dd class="mt-0.5 text-body-md text-on-surface">{{ $value ?? '—' }}</dd>
                        </div>
                    @endforeach
                    @if ($asset->warranty_expires_at)
                        <div>
                            <dt class="text-label-md text-on-surface-variant uppercase tracking-wider">Garantía</dt>
                            <dd class="mt-0.5">
                                @if ($asset->warranty_expires_at->isPast())
                                    <span class="chip-alert">Vencida</span>
                                @else
                                    <span class="chip-success">Vigente ({{ $asset->warranty_expires_at->diffForHumans() }})</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
                </div>

                {{-- Panel de imágenes (como GLPI) --}}
                @if ($images->isNotEmpty())
                    <div class="border border-border-soft rounded-lg p-4">
                        <p class="text-label-md text-on-surface-variant uppercase tracking-wider mb-3">Imágenes</p>
                        <button type="button" @click="open(0)" class="block w-full">
                            <img src="{{ $images->first()->url() }}" alt="{{ $images->first()->file_name }}"
                                class="w-full h-52 object-contain rounded-lg bg-white hover:opacity-90 transition-opacity">
                        </button>
                        @if ($images->count() > 1)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($images as $i => $image)
                                    <button type="button" @click="open({{ $i }})">
                                        <img src="{{ $image->url() }}" alt="{{ $image->file_name }}"
                                            class="w-14 h-14 object-cover rounded-lg border border-border-soft hover:border-primary-container transition-colors">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Visor / carrusel en modal --}}
                    <template x-teleport="body">
                        <div x-show="show" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center"
                            aria-modal="true" role="dialog">
                            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="show = false"></div>

                            <div class="relative max-w-4xl w-full mx-4" x-show="show" x-transition.opacity>
                                <img :src="images[index].url" :alt="images[index].name"
                                    class="max-h-[80vh] w-auto mx-auto rounded-lg shadow-xl bg-white object-contain">

                                <p class="mt-3 text-center text-white text-body-sm">
                                    <span x-text="images[index].name"></span>
                                    <span class="opacity-70" x-show="images.length > 1">
                                        (<span x-text="index + 1"></span> / <span x-text="images.length"></span>)
                                    </span>
                                </p>

                                <button type="button" @click="show = false"
                                    class="absolute -top-3 -end-3 w-9 h-9 bg-white rounded-full shadow flex items-center justify-center text-on-surface hover:text-alert">
                                    <i class="ri-close-line text-xl"></i>
                                </button>

                                <template x-if="images.length > 1">
                                    <div>
                                        <button type="button" @click="prev()"
                                            class="absolute start-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 rounded-full shadow flex items-center justify-center text-on-surface hover:text-primary">
                                            <i class="ri-arrow-left-s-line text-2xl"></i>
                                        </button>
                                        <button type="button" @click="next()"
                                            class="absolute end-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 rounded-full shadow flex items-center justify-center text-on-surface hover:text-primary">
                                            <i class="ri-arrow-right-s-line text-2xl"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                @endif
                </div>

            {{-- ESPECIFICACIONES --}}
            @elseif ($tab === 'specs')
                @php($specDefs = collect($asset->type?->spec_fields ?? []))
                @if (blank($asset->specs))
                    <p class="text-body-md text-on-surface-variant">Sin especificaciones registradas.
                        @can('assets.edit') Usa "Editar" para capturarlas. @endcan
                    </p>
                @else
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        @foreach ($asset->specs as $key => $value)
                            <div>
                                <dt class="text-label-md text-on-surface-variant uppercase tracking-wider">
                                    {{ $specDefs->firstWhere('key', $key)['label'] ?? $key }}
                                </dt>
                                <dd class="mt-0.5 text-body-md text-on-surface">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

            {{-- ASIGNACIONES --}}
            @elseif ($tab === 'assignments')
                @can('assignments.create')
                    @if (! $asset->currentAssignment && $asset->status?->is_assignable)
                        <div class="mb-4 flex justify-end">
                            <button type="button" class="btn-secondary"
                                onclick="Livewire.dispatch('open-assignment-form', { assetId: {{ $asset->id }} })">
                                <i class="ri-add-line"></i> Asignar este activo
                            </button>
                        </div>
                    @endif
                @endcan
                @if ($asset->assignments->isEmpty())
                    <p class="text-body-md text-on-surface-variant">Este activo nunca ha sido asignado.</p>
                @else
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left">
                            <thead class="bg-[#F9FAFB] border-b border-border-soft">
                                <tr>
                                    @foreach (['Empleado', 'Entrega', 'Devolución', 'Estado al entregar', 'Estado al devolver', 'Carta', 'Registró', ''] as $th)
                                        <th class="px-4 py-table-cell-padding text-label-md text-on-surface-variant uppercase tracking-wider">{{ $th }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-soft">
                                @foreach ($asset->assignments as $assignment)
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-3 text-body-md text-on-surface font-medium">
                                            {{ $assignment->employee?->name }}
                                            @if ($assignment->isActive())
                                                <span class="chip-success ms-1">Activa</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-body-md">{{ $assignment->assigned_at?->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-body-md">{{ $assignment->returned_at?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-body-md">{{ $assignment->condition_on_assign ?? '—' }}</td>
                                        <td class="px-4 py-3 text-body-md">{{ $assignment->condition_on_return ?? '—' }}</td>
                                        <td class="px-4 py-3 text-mono-sm font-mono">
                                            @if ($assignment->responsiveLetter)
                                                <a href="{{ route('admin.letters.pdf', $assignment->responsive_letter_id) }}"
                                                    target="_blank" class="text-primary hover:underline">
                                                    {{ $assignment->responsiveLetter->folio }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-body-sm text-on-surface-variant">{{ $assignment->assignedBy?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            @if ($assignment->isActive())
                                                @can('assignments.edit')
                                                    <button type="button" class="btn-icon" title="Registrar devolución"
                                                        onclick="Livewire.dispatch('open-return-form', { id: {{ $assignment->id }} })">
                                                        <i class="ri-arrow-go-back-line text-base"></i>
                                                    </button>
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            {{-- PROBLEMAS --}}
            @elseif ($tab === 'problems')
                @can('problems.create')
                    <div class="mb-4 flex justify-end">
                        <button type="button" class="btn-secondary"
                            onclick="Livewire.dispatch('open-problem-form', { id: null, assetId: {{ $asset->id }} })">
                            <i class="ri-add-line"></i> Reportar problema
                        </button>
                    </div>
                @endcan
                @if ($asset->problems->isEmpty())
                    <p class="text-body-md text-on-surface-variant">Sin problemas registrados para este activo.</p>
                @else
                    <div class="mb-4 flex items-center gap-2">
                        <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Costo acumulado de reparaciones:</span>
                        <span class="text-title-md text-on-surface">${{ number_format($asset->repairCost(), 2) }}</span>
                    </div>
                    <div class="divide-y divide-border-soft">
                        @foreach ($asset->problems->sortByDesc('reported_at') as $problem)
                            <div class="py-3 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <a href="{{ route('admin.problems.show', $problem->id) }}" class="text-body-md font-medium text-on-surface hover:text-primary hover:underline">{{ $problem->title }}</a>
                                    <p class="text-body-sm text-on-surface-variant">
                                        {{ $problem->category?->name ?? 'Sin categoría' }}
                                        · {{ $problem->reported_at?->format('d/m/Y') }}
                                        · Prioridad: {{ \App\Models\Problem::PRIORITIES[$problem->priority] ?? $problem->priority }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if ($problem->cost)
                                        <span class="text-body-sm text-on-surface-variant">${{ number_format((float) $problem->cost, 2) }}</span>
                                    @endif
                                    @php($statusChip = match ($problem->status) {
                                        'new' => 'chip-info', 'in_progress' => 'chip-warning',
                                        'resolved' => 'chip-success', 'closed' => 'chip-neutral', default => 'chip-neutral',
                                    })
                                    <span class="{{ $statusChip }}">{{ \App\Models\Problem::STATUSES[$problem->status] ?? $problem->status }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            {{-- LICENCIAS --}}
            @elseif ($tab === 'licenses')
                @can('licenses.assign')
                    <div class="mb-4 flex justify-end">
                        <button type="button" class="btn-secondary" wire:click="openAssignLicense">
                            <i class="ri-add-line"></i> Asignar licencia
                        </button>
                    </div>
                @endcan
                @php($licenseAssignments = $asset->licenseAssignments->sortByDesc('assigned_at'))
                @if ($licenseAssignments->isEmpty())
                    <p class="text-body-md text-on-surface-variant">Sin licencias instaladas en este equipo.</p>
                @else
                    <div class="divide-y divide-border-soft">
                        @foreach ($licenseAssignments as $la)
                            <div class="py-3 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-body-md font-medium text-on-surface">
                                        {{ $la->license?->software_name }} {{ $la->license?->version }}
                                    </p>
                                    <p class="text-body-sm text-on-surface-variant">
                                        Asignada: {{ $la->assigned_at?->format('d/m/Y') }}
                                        @if ($la->released_at) · Liberada: {{ $la->released_at->format('d/m/Y') }} @endif
                                    </p>
                                </div>
                                <span class="{{ $la->released_at ? 'chip-neutral' : 'chip-success' }}">
                                    {{ $la->released_at ? 'Liberada' : 'En uso' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

            {{-- ADJUNTOS --}}
            @elseif ($tab === 'attachments')
                @can('assets.edit')
                    <div class="mb-5 border border-dashed border-outline-variant rounded-lg p-4">
                        <label class="form-label">Agregar adjuntos (imágenes o documentos)</label>
                        <div class="flex flex-wrap items-center gap-3">
                            <input type="file" wire:model="files" multiple
                                class="block flex-1 min-w-[220px] text-body-sm text-on-surface-variant file:me-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary-fixed file:text-primary file:text-label-md hover:file:opacity-80">
                            <button type="button" class="btn-primary" wire:click="uploadFiles"
                                wire:loading.attr="disabled" wire:target="files, uploadFiles"
                                @if (empty($files)) disabled @endif>
                                <i class="ri-upload-2-line"></i> Subir
                            </button>
                        </div>
                        <div wire:loading wire:target="files" class="form-help">Cargando archivos…</div>
                        @error('files.*') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                @endcan

                @if ($asset->attachments->isEmpty())
                    <p class="text-body-md text-on-surface-variant">Sin adjuntos.</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4">
                        @foreach ($asset->attachments as $attachment)
                            <div class="relative group border border-border-soft rounded-lg overflow-hidden hover:border-primary-container transition-colors"
                                wire:key="det-att-{{ $attachment->id }}">
                                <a href="{{ $attachment->url() }}" target="_blank" class="block">
                                    @if ($attachment->isImage())
                                        <img src="{{ $attachment->url() }}" alt="{{ $attachment->file_name }}" class="w-full h-28 object-cover">
                                    @else
                                        <div class="w-full h-28 flex items-center justify-center bg-surface-container-low">
                                            <i class="ri-file-3-line text-3xl text-outline"></i>
                                        </div>
                                    @endif
                                    <p class="px-2 py-1.5 text-body-sm text-on-surface-variant truncate">{{ $attachment->file_name }}</p>
                                </a>
                                @can('assets.edit')
                                    <button type="button" wire:click="deleteAttachment({{ $attachment->id }})"
                                        wire:confirm="¿Eliminar este adjunto?"
                                        class="absolute top-1.5 end-1.5 w-6 h-6 bg-alert text-white rounded-full text-xs hidden group-hover:flex items-center justify-center">
                                        <i class="ri-close-line"></i>
                                    </button>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                @endif

            {{-- NOTAS --}}
            @elseif ($tab === 'notes')
                @can('assets.edit')
                    <div class="mb-5">
                        <label class="form-label">Agregar nota sobre este dispositivo</label>
                        <textarea wire:model="noteBody" rows="3" class="form-input"
                            placeholder="P.ej. La batería ya no retiene carga, mantener conectado al cargador…"></textarea>
                        @error('noteBody') <p class="form-error">{{ $message }}</p> @enderror
                        <div class="mt-2 flex justify-end">
                            <button type="button" class="btn-primary" wire:click="addNote"
                                wire:loading.attr="disabled" wire:target="addNote">
                                <i class="ri-add-line"></i> Agregar nota
                            </button>
                        </div>
                    </div>
                @endcan

                @if ($asset->deviceNotes->isEmpty())
                    <p class="text-body-md text-on-surface-variant">Sin notas para este dispositivo.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($asset->deviceNotes as $note)
                            <div class="border border-border-soft rounded-lg p-4 bg-surface-container-low/40 relative group"
                                wire:key="note-{{ $note->id }}">
                                <p class="text-body-md text-on-surface whitespace-pre-line">{{ $note->body }}</p>
                                <p class="mt-2 text-body-sm text-outline">
                                    {{ $note->user?->name ?? 'Sistema' }} · {{ $note->created_at->format('d/m/Y H:i') }}
                                </p>
                                @can('assets.edit')
                                    <button type="button" wire:click="deleteNote({{ $note->id }})"
                                        wire:confirm="¿Eliminar esta nota?"
                                        class="absolute top-2 end-2 p-1 text-outline hover:text-alert rounded hidden group-hover:block">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                @endif

            {{-- HISTÓRICO --}}
            @elseif ($tab === 'history')
                @if ($activities->isEmpty())
                    <p class="text-body-md text-on-surface-variant">Sin cambios registrados.</p>
                @else
                    <x-activity-timeline :activities="$activities" entity="activo" />
                @endif
            @endif
        </div>
    </div>

    {{-- Modal cambiar estado --}}
    @if ($changingStatus)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('changingStatus', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface mb-4">Cambiar estado del activo</h3>
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Nuevo estado <span class="text-error">*</span></label>
                        <select wire:model="newStatusId" class="form-input">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('newStatusId') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Nota (opcional)</label>
                        <textarea wire:model="statusNote" rows="2" class="form-input"
                            placeholder="Motivo del cambio, observaciones…"></textarea>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('changingStatus', false)">Cancelar</button>
                    <button type="button" class="btn-primary" wire:click="saveStatus" wire:loading.attr="disabled">Guardar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Confirmación de baja --}}
    @if ($confirmingRetire)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('confirmingRetire', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-full bg-error-container flex items-center justify-center">
                        <i class="ri-inbox-unarchive-line text-on-error-container text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-title-md text-on-surface">Dar de baja el activo</h3>
                        <p class="mt-1 text-body-md text-on-surface-variant">
                            El activo <span class="font-semibold text-on-surface">{{ $asset->asset_tag }}</span> pasará al estado
                            <span class="font-semibold">Baja</span>. Su histórico se conserva.
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('confirmingRetire', false)">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="retire" wire:loading.attr="disabled">Dar de baja</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal asignar licencia --}}
    @if ($assigningLicense)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('assigningLicense', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface mb-3">Asignar licencia al equipo</h3>
                @if ($availableLicenses->isEmpty())
                    <p class="text-body-md text-on-surface-variant">No hay licencias con asientos disponibles.</p>
                @else
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Licencia <span class="text-error">*</span></label>
                            <x-searchable-select model="licenseToAssign" :options="$availableLicenses"
                                placeholder="— Seleccionar licencia —" searchPlaceholder="Buscar por software…" />
                            @error('licenseToAssign') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Notas</label>
                            <textarea wire:model="licenseAssignNotes" rows="2" class="form-input"></textarea>
                        </div>
                    </div>
                @endif
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('assigningLicense', false)">Cancelar</button>
                    @unless ($availableLicenses->isEmpty())
                        <button type="button" class="btn-primary" wire:click="saveAssignLicense" wire:loading.attr="disabled">Asignar</button>
                    @endunless
                </div>
            </div>
        </div>
    @endif
</div>
