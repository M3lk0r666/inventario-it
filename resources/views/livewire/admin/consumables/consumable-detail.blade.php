<div>
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-headline-md text-on-surface">{{ $consumable->name }}</h2>
                @if ($consumable->isLowStock())
                    <span class="chip-alert">Stock bajo</span>
                @else
                    <span class="chip-success">Existencia suficiente</span>
                @endif
            </div>
            <p class="mt-1 text-body-md text-on-surface-variant">
                {{ $consumable->location?->name ?? 'Sin ubicación' }}
                @if ($consumable->supplier) · {{ $consumable->supplier->name }} @endif
            </p>
        </div>
        @can('consumables.edit')
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" class="btn-secondary" wire:click="openMove('in')">
                    <i class="ri-arrow-down-line"></i> Entrada
                </button>
                <button type="button" class="btn-primary" wire:click="openMove('out')">
                    <i class="ri-arrow-up-line"></i> Salida
                </button>
            </div>
        @endcan
    </div>

    {{-- Métricas --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-gutter mb-6">
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Existencia actual</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-display-lg text-on-surface">{{ $consumable->stock }}</span>
                <span class="text-body-md text-on-surface-variant">{{ $consumable->unit }}</span>
            </div>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Mínimo</span>
            <div class="mt-2"><span class="text-display-lg text-on-surface">{{ $consumable->min_stock }}</span></div>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Descripción</span>
            <p class="mt-2 text-body-md text-on-surface">{{ $consumable->description ?: '—' }}</p>
        </div>
    </div>

    {{-- Kardex --}}
    <div class="card">
        <div class="px-4 py-3 border-b border-border-soft">
            <h3 class="text-title-md text-on-surface">Kardex de movimientos</h3>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left">
                <thead class="bg-[#F9FAFB] border-b border-border-soft">
                    <tr>
                        @foreach (['Fecha', 'Tipo', 'Cantidad', 'Destinatario', 'Costo unit.', 'Registró', 'Notas'] as $th)
                            <th class="px-4 py-table-cell-padding text-label-md text-on-surface-variant uppercase tracking-wider">{{ $th }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-soft">
                    @forelse ($movements as $m)
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-4 py-3 text-body-md">{{ $m->moved_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                @if ($m->type === 'in')
                                    <span class="chip-success">Entrada</span>
                                @else
                                    <span class="chip-info">Salida</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-body-md font-medium">
                                {{ $m->type === 'in' ? '+' : '−' }}{{ $m->quantity }}
                            </td>
                            <td class="px-4 py-3 text-body-md">{{ $m->employee?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-body-md">{{ $m->unit_cost !== null ? '$'.number_format((float) $m->unit_cost, 2) : '—' }}</td>
                            <td class="px-4 py-3 text-body-sm text-on-surface-variant">{{ $m->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-body-sm text-on-surface-variant">{{ $m->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-body-md text-on-surface-variant">Sin movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movements->hasPages())
            <div class="px-4 py-3 border-t border-border-soft">{{ $movements->links() }}</div>
        @endif
    </div>

    {{-- Modal de movimiento --}}
    @if ($moving)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('moving', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface mb-4">
                    {{ $moveType === 'in' ? 'Registrar entrada' : 'Registrar salida' }}
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Cantidad <span class="text-error">*</span></label>
                        <input type="number" min="1" wire:model="quantity" class="form-input">
                        @error('quantity') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    @if ($moveType === 'out')
                        <div>
                            <label class="form-label">Destinatario (empleado)</label>
                            <x-searchable-select model="employeeId" :options="$employees"
                                placeholder="— Sin destinatario —" searchPlaceholder="Buscar por nombre…" />
                        </div>
                    @else
                        <div>
                            <label class="form-label">Costo unitario</label>
                            <input type="number" step="0.01" min="0" wire:model="unitCost" class="form-input">
                        </div>
                    @endif

                    <div>
                        <label class="form-label">Notas</label>
                        <textarea wire:model="moveNotes" rows="2" class="form-input"></textarea>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('moving', false)">Cancelar</button>
                    <button type="button" class="btn-primary" wire:click="saveMove" wire:loading.attr="disabled">Registrar</button>
                </div>
            </div>
        </div>
    @endif
</div>
