<div>
    @php($available = $license->seats - $used)
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-headline-md text-on-surface">{{ $license->software_name }}</h2>
                @if ($license->version)<span class="text-body-md text-on-surface-variant">{{ $license->version }}</span>@endif
                @if ($license->isExpired())
                    <span class="chip-alert">Vencida</span>
                @elseif ($license->expires_at && $license->expires_at->lte(now()->addDays(60)))
                    <span class="chip-warning">Por vencer</span>
                @endif
            </div>
            <p class="mt-1 text-body-md text-on-surface-variant">
                {{ $license->type?->name }} @if ($license->supplier) · {{ $license->supplier->name }} @endif
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @can('licenses.assign')
                <button type="button" class="btn-primary" wire:click="openAssign({{ $license->id }})"
                    @if ($available <= 0) disabled @endif>
                    <i class="ri-user-add-line"></i> Asignar asiento
                </button>
            @endcan
            @can('licenses.edit')
                <button type="button" class="btn-ghost" wire:click="openRenew">
                    <i class="ri-refresh-line"></i> Renovar
                </button>
                <button type="button" class="btn-ghost" onclick="Livewire.dispatch('open-license-form', { id: {{ $license->id }} })">
                    <i class="ri-pencil-line"></i> Editar
                </button>
            @endcan
        </div>
    </div>

    {{-- Banner de alerta de renovación --}}
    @if ($license->needsRenewalAlert())
        @php($overdue = $license->renewalStatus() === 'overdue')
        <div class="mb-6 flex items-start gap-3 rounded-lg border p-4 {{ $overdue ? 'border-alert/40 bg-alert/10' : 'border-amber-300 bg-amber-50' }}">
            <i class="{{ $overdue ? 'ri-alarm-warning-line text-alert' : 'ri-time-line text-amber-600' }} text-xl shrink-0"></i>
            <div class="flex-1">
                <p class="text-body-md font-medium {{ $overdue ? 'text-on-error-container' : 'text-amber-800' }}">
                    {{ $overdue
                        ? 'La fecha de renovación ya venció ('.$license->renewal_date->format('d/m/Y').').'
                        : 'La renovación está próxima: '.$license->renewal_date->format('d/m/Y').' ('.$license->renewal_date->diffForHumans().').' }}
                </p>
                <p class="text-body-sm text-on-surface-variant">Registra la renovación para reiniciar el aviso.</p>
            </div>
            @can('licenses.edit')
                <button type="button" class="btn-primary shrink-0" wire:click="openRenew">
                    <i class="ri-refresh-line"></i> Renovar
                </button>
            @endcan
        </div>
    @endif

    {{-- Métricas --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-gutter mb-6">
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Asientos totales</span>
            <span class="mt-2 block text-display-lg text-on-surface">{{ $license->seats }}</span>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">En uso</span>
            <span class="mt-2 block text-display-lg text-on-surface">{{ $used }}</span>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Disponibles</span>
            <div class="mt-2 flex items-end justify-between">
                <span class="text-display-lg {{ $available <= 0 ? 'text-alert' : 'text-on-surface' }}">{{ $available }}</span>
                @if ($available <= 0) <span class="chip-alert">Agotada</span> @endif
            </div>
        </div>
        <div class="card p-5">
            <span class="text-label-md text-on-surface-variant uppercase tracking-wider">Expiración</span>
            <span class="mt-2 block text-title-md text-on-surface">{{ $license->expires_at?->format('d/m/Y') ?? 'Perpetua' }}</span>
            @if ($license->renewal_date)
                <span class="mt-1 block text-body-sm text-on-surface-variant">
                    Renovar: {{ $license->renewal_date->format('d/m/Y') }}
                    @unless ($license->alerts_enabled) <span class="text-outline">(alertas off)</span> @endunless
                </span>
            @endif
        </div>
    </div>

    {{-- Asignaciones de asientos --}}
    <div class="card">
        <div class="px-4 py-3 border-b border-border-soft">
            <h3 class="text-title-md text-on-surface">Asientos asignados</h3>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left">
                <thead class="bg-[#F9FAFB] border-b border-border-soft">
                    <tr>
                        @foreach (['Destinatario', 'Tipo', 'Asignado', 'Liberado', 'Situación', ''] as $th)
                            <th class="px-4 py-table-cell-padding text-label-md text-on-surface-variant uppercase tracking-wider">{{ $th }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-soft">
                    @forelse ($assignments as $a)
                        @php($isAsset = $a->assignable_type === \App\Models\Asset::class)
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-4 py-3 text-body-md text-on-surface font-medium">
                                @if ($isAsset)
                                    {{ $a->assignable?->asset_tag }} — {{ $a->assignable?->name }}
                                @else
                                    {{ $a->assignable?->name }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="chip-neutral">{{ $isAsset ? 'Equipo' : 'Empleado' }}</span>
                            </td>
                            <td class="px-4 py-3 text-body-md">{{ $a->assigned_at?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-body-md">{{ $a->released_at?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="{{ $a->released_at ? 'chip-neutral' : 'chip-success' }}">{{ $a->released_at ? 'Liberado' : 'En uso' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if (! $a->released_at)
                                    @can('licenses.assign')
                                        <button type="button" wire:click="confirmRelease({{ $a->id }})"
                                            class="p-1.5 text-outline rounded-lg hover:text-alert hover:bg-alert/10" title="Liberar asiento">
                                            <i class="ri-logout-box-r-line text-base"></i>
                                        </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-body-md text-on-surface-variant">Sin asientos asignados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal renovar --}}
    @if ($renewing)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('renewing', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface mb-1">Registrar renovación</h3>
                <p class="text-body-sm text-on-surface-variant mb-4">Se actualizan las fechas y se reactiva el aviso para el nuevo periodo.</p>
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Nueva fecha de renovación <span class="text-error">*</span></label>
                        <input type="date" wire:model="newRenewalDate" class="form-input">
                        @error('newRenewalDate') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Nueva expiración</label>
                        <input type="date" wire:model="newExpiresAt" class="form-input">
                        @error('newExpiresAt') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('renewing', false)">Cancelar</button>
                    <button type="button" class="btn-primary" wire:click="saveRenew" wire:loading.attr="disabled">Guardar renovación</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal asignar asiento --}}
    @if ($assigning)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('assigning', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface mb-4">Asignar asiento de licencia</h3>
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Asignar a</label>
                        <div class="flex gap-2">
                            <button type="button" wire:click="$set('target', 'asset')"
                                class="flex-1 px-3 py-2 rounded-lg border text-label-md {{ $target === 'asset' ? 'border-primary-container bg-primary-fixed/40 text-primary' : 'border-border-soft text-on-surface-variant' }}">
                                Equipo
                            </button>
                            <button type="button" wire:click="$set('target', 'employee')"
                                class="flex-1 px-3 py-2 rounded-lg border text-label-md {{ $target === 'employee' ? 'border-primary-container bg-primary-fixed/40 text-primary' : 'border-border-soft text-on-surface-variant' }}">
                                Empleado
                            </button>
                        </div>
                    </div>
                    <div wire:key="target-select-{{ $target }}">
                        <label class="form-label">{{ $target === 'asset' ? 'Equipo' : 'Empleado' }} <span class="text-error">*</span></label>
                        <x-searchable-select model="targetId" :options="$target === 'asset' ? $assets : $employees"
                            placeholder="— Seleccionar —" searchPlaceholder="Buscar…" />
                        @error('targetId') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Notas</label>
                        <textarea wire:model="assignNotes" rows="2" class="form-input"></textarea>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('assigning', false)">Cancelar</button>
                    <button type="button" class="btn-primary" wire:click="saveAssign" wire:loading.attr="disabled">Asignar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Confirmar liberación de asiento --}}
    @if ($confirmingReleaseId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('confirmingReleaseId', null)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-full bg-error-container flex items-center justify-center">
                        <i class="ri-logout-box-r-line text-on-error-container text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-title-md text-on-surface">Liberar asiento</h3>
                        <p class="mt-1 text-body-md text-on-surface-variant">
                            ¿Liberar este asiento de la licencia? El destinatario dejará de tenerlo asignado y el asiento quedará disponible.
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('confirmingReleaseId', null)">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="release" wire:loading.attr="disabled">Liberar</button>
                </div>
            </div>
        </div>
    @endif
</div>
