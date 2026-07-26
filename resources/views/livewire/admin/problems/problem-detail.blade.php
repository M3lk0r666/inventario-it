<div>
    @php($sChip = $statusChip[$problem->status] ?? 'chip-neutral')
    @php($pChip = $priorityChip[$problem->priority] ?? 'chip-neutral')

    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-headline-md text-on-surface">{{ $problem->title }}</h2>
                <span class="{{ $sChip }}">{{ \App\Models\Problem::STATUSES[$problem->status] }}</span>
                <span class="{{ $pChip }}">{{ \App\Models\Problem::PRIORITIES[$problem->priority] }}</span>
            </div>
            <p class="mt-1 text-body-md text-on-surface-variant">
                @if ($problem->asset)
                    <a href="{{ route('admin.assets.show', $problem->asset_id) }}" class="text-primary hover:underline">
                        <span class="font-mono text-mono-sm">{{ $problem->asset->asset_tag }}</span> — {{ $problem->asset->name }}
                    </a>
                @endif
                · {{ $problem->category?->name ?? 'Sin categoría' }}
                · Reportado {{ $problem->reported_at?->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @can('problems.edit')
                {{-- Cambios de estado rápidos --}}
                <div class="flex items-center gap-1">
                    @foreach (\App\Models\Problem::STATUSES as $key => $label)
                        @if ($key !== $problem->status)
                            <button type="button" wire:click="changeStatus('{{ $key }}')"
                                class="px-2.5 py-1.5 text-label-md rounded-lg border border-border-soft text-on-surface-variant hover:bg-surface-container-low transition-colors">
                                {{ $label }}
                            </button>
                        @endif
                    @endforeach
                </div>
                <button type="button" class="btn-primary" onclick="Livewire.dispatch('open-problem-form', { id: {{ $problem->id }} })">
                    <i class="ri-pencil-line"></i> Editar
                </button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr,320px] gap-gutter items-start">
        {{-- Columna principal: descripción + seguimiento --}}
        <div class="space-y-6">
            {{-- Descripción --}}
            <div class="card p-6">
                <h3 class="text-label-md text-on-surface-variant uppercase tracking-wider mb-2">Descripción</h3>
                <p class="text-body-md text-on-surface whitespace-pre-line">{{ $problem->description ?: 'Sin descripción.' }}</p>
            </div>

            {{-- Pestañas --}}
            <div class="card">
                <div class="border-b border-border-soft px-4">
                    <nav class="flex gap-1 -mb-px">
                        @foreach (['timeline' => 'Seguimiento', 'attachments' => 'Adjuntos ('.$problem->attachments->count().')', 'history' => 'Histórico'] as $key => $label)
                            <button type="button" wire:click="$set('tab', '{{ $key }}')"
                                class="px-3 py-3 text-body-md font-medium border-b-2 transition-colors
                                    {{ $tab === $key ? 'border-primary-container text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                <div class="p-6">
                    {{-- SEGUIMIENTO --}}
                    @if ($tab === 'timeline')
                        @can('problems.edit')
                            <div class="mb-5">
                                <textarea wire:model="noteBody" rows="3" class="form-input"
                                    placeholder="Agregar nota de seguimiento (diagnóstico, avance, resolución…)"></textarea>
                                @error('noteBody') <p class="form-error">{{ $message }}</p> @enderror
                                <div class="mt-2 flex justify-end">
                                    <button type="button" class="btn-primary" wire:click="addNote" wire:loading.attr="disabled" wire:target="addNote">
                                        <i class="ri-chat-1-line"></i> Agregar nota
                                    </button>
                                </div>
                            </div>
                        @endcan

                        @if ($problem->notes->isEmpty())
                            <p class="text-body-md text-on-surface-variant">Sin notas de seguimiento.</p>
                        @else
                            <ol class="relative border-s border-border-soft ms-2 space-y-4">
                                @foreach ($problem->notes as $note)
                                    <li class="ms-5" wire:key="pnote-{{ $note->id }}">
                                        <span class="absolute -start-[5px] mt-1.5 w-2.5 h-2.5 rounded-full bg-primary-container"></span>
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1">
                                                <div class="text-body-md text-on-surface prose-sm max-w-none">{!! nl2br(e($note->body)) !!}</div>
                                                <p class="mt-1 text-body-sm text-outline">
                                                    {{ $note->user?->name ?? 'Sistema' }} · {{ $note->created_at->format('d/m/Y H:i') }}
                                                </p>
                                            </div>
                                            @can('problems.edit')
                                                <button type="button" wire:click="deleteNote({{ $note->id }})"
                                                    wire:confirm="¿Eliminar esta nota?"
                                                    class="p-1 text-outline hover:text-alert rounded">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif

                    {{-- ADJUNTOS --}}
                    @elseif ($tab === 'attachments')
                        @if ($problem->attachments->isEmpty())
                            <p class="text-body-md text-on-surface-variant">Sin adjuntos. Puedes agregarlos desde "Editar".</p>
                        @else
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                @foreach ($problem->attachments as $attachment)
                                    <div class="relative group border border-border-soft rounded-lg overflow-hidden hover:border-primary-container transition-colors"
                                        wire:key="patt-{{ $attachment->id }}">
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
                                        @can('problems.edit')
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

                    {{-- HISTÓRICO --}}
                    @elseif ($tab === 'history')
                        @if ($activities->isEmpty())
                            <p class="text-body-md text-on-surface-variant">Sin cambios registrados.</p>
                        @else
                            <ol class="relative border-s border-border-soft ms-2 space-y-4">
                                @foreach ($activities as $activity)
                                    <li class="ms-5">
                                        <span class="absolute -start-[5px] mt-1.5 w-2.5 h-2.5 rounded-full bg-primary-container"></span>
                                        <p class="text-body-md text-on-surface">
                                            <span class="font-medium">{{ $activity->causer?->name ?? 'Sistema' }}</span>
                                            @switch($activity->description)
                                                @case('created') creó el problema @break
                                                @case('updated') actualizó el problema @break
                                                @default {{ $activity->description }}
                                            @endswitch
                                        </p>
                                        @php($changes = collect($activity->properties['attributes'] ?? [])->except(['updated_at']))
                                        @if ($changes->isNotEmpty() && $activity->description === 'updated')
                                            <p class="text-body-sm text-on-surface-variant">Campos: {{ $changes->keys()->implode(', ') }}</p>
                                        @endif
                                        <p class="text-body-sm text-outline">{{ $activity->created_at->format('d/m/Y H:i') }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Columna lateral: datos --}}
        <div class="card p-5">
            <h3 class="text-label-md text-on-surface-variant uppercase tracking-wider mb-3">Detalles</h3>
            <dl class="space-y-3">
                @foreach ([
                    'Estado' => \App\Models\Problem::STATUSES[$problem->status],
                    'Prioridad' => \App\Models\Problem::PRIORITIES[$problem->priority],
                    'Categoría' => $problem->category?->name ?? '—',
                    'Costo de reparación' => $problem->cost !== null ? '$'.number_format((float) $problem->cost, 2) : '—',
                    'Reportado' => $problem->reported_at?->format('d/m/Y H:i'),
                    'Resuelto' => $problem->resolved_at?->format('d/m/Y H:i') ?? '—',
                    'Cerrado' => $problem->closed_at?->format('d/m/Y H:i') ?? '—',
                    'Creado por' => $problem->createdBy?->name ?? '—',
                    'Responsable' => $problem->assignedTo?->name ?? 'Sin asignar',
                ] as $label => $value)
                    <div class="flex justify-between gap-2">
                        <dt class="text-body-sm text-on-surface-variant">{{ $label }}</dt>
                        <dd class="text-body-md text-on-surface text-right">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</div>
