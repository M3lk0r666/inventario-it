<div>
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <select wire:model.live="logName" class="form-input !w-auto">
            <option value="">Todos los módulos</option>
            @foreach ($logNames as $name)
                <option value="{{ $name }}">{{ $name }}</option>
            @endforeach
        </select>
        <select wire:model.live="event" class="form-input !w-auto">
            <option value="">Todos los eventos</option>
            <option value="created">Creación</option>
            <option value="updated">Actualización</option>
            <option value="deleted">Eliminación</option>
        </select>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left">
                <thead class="bg-[#F9FAFB] border-b border-border-soft">
                    <tr>
                        @foreach (['Fecha', 'Usuario', 'Módulo', 'Evento', 'Descripción'] as $th)
                            <th class="px-4 py-table-cell-padding text-label-md text-on-surface-variant uppercase tracking-wider">{{ $th }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-soft">
                    @forelse ($activities as $a)
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-4 py-3 text-body-sm text-on-surface-variant whitespace-nowrap">{{ $a->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-body-md">{{ $a->causer?->name ?? 'Sistema' }}</td>
                            <td class="px-4 py-3 text-body-md">{{ $a->log_name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @php($ec = match ($a->event) { 'created' => 'chip-success', 'updated' => 'chip-info', 'deleted' => 'chip-alert', default => 'chip-neutral' })
                                <span class="{{ $ec }}">{{ ['created' => 'Creación', 'updated' => 'Actualización', 'deleted' => 'Eliminación'][$a->event] ?? ($a->event ?? '—') }}</span>
                            </td>
                            <td class="px-4 py-3 text-body-md">{{ $a->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-body-md text-on-surface-variant">Sin registros de actividad.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-border-soft">{{ $activities->links() }}</div>
    </div>
</div>
