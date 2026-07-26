<div>
    {{-- Filtros --}}
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <div class="relative flex-1 min-w-[220px]">
            <i class="ri-search-line absolute start-3 top-1/2 -translate-y-1/2 text-outline"></i>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-input ps-9"
                placeholder="Buscar por empleado, número o folio…">
        </div>
        <select wire:model.live="typeFilter" class="form-input !w-auto">
            <option value="">Todos los tipos</option>
            <option value="delivery">Entrega</option>
            <option value="return">Recepción</option>
        </select>
        <select wire:model.live="statusFilter" class="form-input !w-auto">
            <option value="">Todos los estados</option>
            @foreach (\App\Models\ResponsiveLetter::STATUSES as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @if ($employees->isEmpty())
        <p class="text-body-md text-on-surface-variant py-8 text-center">Sin cartas responsivas que coincidan.</p>
    @else
        <div class="space-y-2">
            @foreach ($employees as $employee)
                <div class="border border-border-soft rounded-lg overflow-hidden" wire:key="emp-{{ $employee->id }}">
                    {{-- Cabecera del empleado --}}
                    <button type="button" wire:click="toggle({{ $employee->id }})"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 hover:bg-surface-container-low transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-9 h-9 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                                <i class="ri-user-line"></i>
                            </span>
                            <div class="text-left min-w-0">
                                <p class="text-body-md font-medium text-on-surface truncate">{{ $employee->name }}</p>
                                <p class="text-body-sm text-on-surface-variant truncate">
                                    {{ $employee->employee_number }}
                                    @if ($employee->department) · {{ $employee->department->name ?? '' }} @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="chip-neutral">{{ $employee->letters_count }} {{ \Illuminate\Support\Str::plural('carta', $employee->letters_count) }}</span>
                            <i class="ri-arrow-down-s-line text-xl text-outline transition-transform {{ $expanded === $employee->id ? 'rotate-180' : '' }}"></i>
                        </div>
                    </button>

                    {{-- Cartas del empleado --}}
                    @if ($expanded === $employee->id)
                        <div class="border-t border-border-soft overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left">
                                <thead class="bg-[#F9FAFB] border-b border-border-soft">
                                    <tr>
                                        @foreach (['Folio', 'Tipo', 'Fecha', 'Bienes', 'Estado', ''] as $th)
                                            <th class="px-4 py-table-cell-padding text-label-md text-on-surface-variant uppercase tracking-wider">{{ $th }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border-soft">
                                    @foreach ($letters as $letter)
                                        <tr class="hover:bg-surface-container-low transition-colors" wire:key="letter-{{ $letter->id }}">
                                            <td class="px-4 py-3">
                                                <a href="{{ route('admin.letters.pdf', $letter->id) }}" target="_blank"
                                                    class="text-mono-sm font-mono text-primary hover:underline">{{ $letter->folio }}</a>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if ($letter->type === 'return')
                                                    <span class="chip-warning">Recepción</span>
                                                @else
                                                    <span class="chip-info">Entrega</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-body-md">{{ $letter->issued_at?->format('d/m/Y') }}</td>
                                            <td class="px-4 py-3 text-body-md">
                                                {{ $letter->type === 'return' ? $letter->returned_assignments_count : $letter->assignments_count }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @php($chip = match ($letter->status) {
                                                    'signed' => 'chip-success', 'cancelled' => 'chip-alert', default => 'chip-info',
                                                })
                                                <span class="{{ $chip }}">{{ \App\Models\ResponsiveLetter::STATUSES[$letter->status] }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-end gap-1">
                                                    <a href="{{ route('admin.letters.pdf', $letter->id) }}" target="_blank" class="btn-icon" title="Descargar PDF">
                                                        <i class="ri-download-2-line text-base"></i>
                                                    </a>
                                                    @if ($letter->hasSignedDocument())
                                                        <a href="{{ route('admin.letters.signed', $letter->id) }}" target="_blank"
                                                            class="p-1.5 text-success rounded-lg hover:bg-success/10" title="Ver carta firmada (evidencia)">
                                                            <i class="ri-file-shield-2-line text-base"></i>
                                                        </a>
                                                    @endif
                                                    @can('responsive_letters.reprint')
                                                        <a href="{{ route('admin.letters.reprint', $letter->id) }}" target="_blank" class="btn-icon" title="Reimprimir">
                                                            <i class="ri-printer-line text-base"></i>
                                                        </a>
                                                    @endcan
                                                    @if ($letter->status !== 'cancelled')
                                                        @can('responsive_letters.edit')
                                                            <button type="button" onclick="Livewire.dispatch('sign-letter', { id: {{ $letter->id }} })"
                                                                class="p-1.5 text-outline rounded-lg hover:text-success hover:bg-success/10"
                                                                title="{{ $letter->hasSignedDocument() ? 'Reemplazar carta firmada' : 'Subir carta firmada' }}">
                                                                <i class="ri-quill-pen-line text-base"></i>
                                                            </button>
                                                        @endcan
                                                        @can('responsive_letters.cancel')
                                                            <button type="button" onclick="Livewire.dispatch('confirm-cancel-letter', { id: {{ $letter->id }} })"
                                                                class="p-1.5 text-outline rounded-lg hover:text-alert hover:bg-alert/10" title="Anular">
                                                                <i class="ri-forbid-2-line text-base"></i>
                                                            </button>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $employees->links() }}
        </div>
    @endif
</div>
