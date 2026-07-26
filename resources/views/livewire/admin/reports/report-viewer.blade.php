<div>
    <div class="grid grid-cols-1 lg:grid-cols-[260px,1fr] gap-gutter items-start">
        {{-- Lista de reportes --}}
        <nav class="card p-2 lg:sticky lg:top-20">
            @foreach ($reports as $key => $r)
                <button type="button" wire:click="selectReport('{{ $key }}')"
                    class="w-full flex items-start gap-2 px-3 py-2 rounded-lg text-left transition-colors {{ $report === $key ? 'bg-primary-fixed/40 text-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                    <i class="{{ $r['icon'] }} text-lg mt-0.5"></i>
                    <span class="min-w-0">
                        <span class="block text-body-md {{ $report === $key ? 'font-medium' : '' }}">{{ $r['label'] }}</span>
                    </span>
                </button>
            @endforeach
        </nav>

        <div>
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-headline-sm text-on-surface">{{ $def['label'] }}</h2>
                    <p class="text-body-md text-on-surface-variant">{{ $def['description'] }}</p>
                </div>
                @can('reports.export')
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.reports.export', array_merge($exportParams, ['format' => 'csv'])) }}" class="btn-ghost">
                            <i class="ri-file-excel-2-line"></i> CSV
                        </a>
                        <a href="{{ route('admin.reports.export', array_merge($exportParams, ['format' => 'pdf'])) }}" target="_blank" class="btn-ghost">
                            <i class="ri-file-pdf-2-line"></i> PDF
                        </a>
                    </div>
                @endcan
            </div>

            {{-- Filtros --}}
            @if (! empty($def['filters']))
                <div class="card p-4 mb-4 flex flex-wrap items-end gap-3">
                    @if (in_array('type', $def['filters']))
                        <div>
                            <label class="form-label">Tipo</label>
                            <select wire:model.live="type" class="form-input !w-auto">
                                <option value="">Todos</option>
                                @foreach ($typeOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                    @if (in_array('status', $def['filters']))
                        <div>
                            <label class="form-label">Estado</label>
                            <select wire:model.live="status" class="form-input !w-auto">
                                <option value="">Todos</option>
                                @foreach ($statusOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                    @if (in_array('location', $def['filters']))
                        <div>
                            <label class="form-label">Ubicación</label>
                            <select wire:model.live="location" class="form-input !w-auto">
                                <option value="">Todas</option>
                                @foreach ($locationOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                    @if (in_array('employee', $def['filters']))
                        <div class="w-64" wire:key="report-employee-filter">
                            <label class="form-label">Empleado</label>
                            <x-searchable-select model="employee" :options="['' => 'Todos'] + $employeeOptions->all()"
                                placeholder="Todos" searchPlaceholder="Buscar empleado…" />
                        </div>
                    @endif
                    @if (in_array('problem_status', $def['filters']))
                        <div>
                            <label class="form-label">Estado del problema</label>
                            <select wire:model.live="problemStatus" class="form-input !w-auto">
                                <option value="">Todos</option>
                                @foreach ($problemStatuses as $k => $name)<option value="{{ $k }}">{{ $name }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                    @if (in_array('dates', $def['filters']))
                        <div>
                            <label class="form-label">Desde</label>
                            <input type="date" wire:model.live="date_from" class="form-input !w-auto">
                        </div>
                        <div>
                            <label class="form-label">Hasta</label>
                            <input type="date" wire:model.live="date_to" class="form-input !w-auto">
                        </div>
                    @endif
                </div>
            @endif

            {{-- Tabla --}}
            <div class="card overflow-hidden">
                <div class="px-4 py-3 border-b border-border-soft flex items-center justify-between">
                    <span class="text-body-md text-on-surface-variant">{{ $rows->count() }} registros</span>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left">
                        <thead class="bg-[#F9FAFB] border-b border-border-soft">
                            <tr>
                                @foreach ($def['columns'] as $col)
                                    <th class="px-4 py-table-cell-padding text-label-md text-on-surface-variant uppercase tracking-wider whitespace-nowrap">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-soft">
                            @forelse ($rows->take(200) as $row)
                                <tr class="hover:bg-surface-container-low transition-colors">
                                    @foreach ($row as $cell)
                                        <td class="px-4 py-3 text-body-md text-on-surface whitespace-nowrap">{{ $cell !== '' && $cell !== null ? $cell : '—' }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($def['columns']) }}" class="px-4 py-6 text-center text-body-md text-on-surface-variant">Sin datos para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($rows->count() > 200)
                    <div class="px-4 py-3 border-t border-border-soft text-body-sm text-on-surface-variant">
                        Mostrando 200 de {{ $rows->count() }}. Exporta a CSV/PDF para el listado completo.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
