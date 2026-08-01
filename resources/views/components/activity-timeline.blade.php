@props([
    'activities',
    'entity' => 'registro',
])

@php
    // Ícono por tipo de evento (todos en azul, según el diseño solicitado).
    $iconFor = fn ($desc) => match ($desc) {
        'created' => 'ri-add-line',
        'updated' => 'ri-edit-2-line',
        'deleted' => 'ri-delete-bin-line',
        default => 'ri-refresh-line',
    };

    // Agrupar por fecha (día) para los separadores tipo "línea de tiempo".
    $grouped = $activities->groupBy(fn ($a) => $a->created_at->locale('es')->isoFormat('D MMM YYYY'));
@endphp

<div class="relative">
    {{-- Línea vertical --}}
    <div class="absolute top-1 bottom-1 left-4 -translate-x-1/2 w-0.5 bg-border-soft"></div>

    @foreach ($grouped as $date => $items)
        {{-- Separador de fecha --}}
        <div class="relative z-10 mb-4">
            <span class="inline-block rounded-md bg-primary px-3 py-1 text-label-md font-semibold text-white shadow-sm">
                {{ ucfirst($date) }}
            </span>
        </div>

        <div class="mb-6 space-y-4">
            @foreach ($items as $activity)
                <div class="relative flex gap-4">
                    {{-- Ícono en la línea --}}
                    <div class="relative z-10 shrink-0">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-white ring-4 ring-canvas">
                            <i class="{{ $iconFor($activity->description) }}"></i>
                        </span>
                    </div>

                    {{-- Tarjeta del evento --}}
                    <div class="min-w-0 flex-1 rounded-lg border border-border-soft bg-white shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <p class="text-body-md text-on-surface">
                                <span class="font-semibold text-primary">{{ $activity->causer?->name ?? 'Sistema' }}</span>
                                @switch($activity->description)
                                    @case('created') creó el {{ $entity }} @break
                                    @case('updated') actualizó el {{ $entity }} @break
                                    @case('deleted') eliminó el {{ $entity }} @break
                                    @default {{ $activity->description }}
                                @endswitch
                            </p>
                            <span class="shrink-0 text-body-sm text-on-surface-variant">{{ $activity->created_at->format('H:i') }}</span>
                        </div>

                        @php($changes = collect($activity->properties['attributes'] ?? [])->except(['updated_at']))
                        @if (($changes->isNotEmpty() && $activity->description === 'updated') || isset($activity->properties['nota']))
                            <div class="space-y-1 border-t border-border-soft px-4 py-2.5">
                                @if ($changes->isNotEmpty() && $activity->description === 'updated')
                                    <p class="text-body-sm text-on-surface-variant">Campos: {{ $changes->keys()->implode(', ') }}</p>
                                @endif
                                @if (isset($activity->properties['nota']))
                                    <p class="text-body-sm italic text-on-surface-variant">"{{ $activity->properties['nota'] }}"</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
