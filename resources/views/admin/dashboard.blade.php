<x-admin-layout title="Dashboard" :breadcrumbs="[['name' => 'Dashboard']]">
    @php($chipColor = ['green' => '#10B981', 'blue' => '#0052cc', 'indigo' => '#4f46e5', 'yellow' => '#f59e0b', 'red' => '#E11D48', 'gray' => '#737685'])
    @php($totalAlerts = array_sum($alerts['summary']))

    <x-page-header title="Panel de control"
        :description="'Bienvenido, '.auth()->user()->name.'. Resumen del inventario y alertas.'" />

    {{-- Tarjetas principales --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-gutter mb-6">
        @foreach ([
            ['label' => 'Activos', 'value' => $cards['assets'], 'icon' => 'ri-computer-line', 'route' => 'admin.assets.index'],
            ['label' => 'Asignados', 'value' => $cards['assigned'], 'icon' => 'ri-user-received-line', 'route' => 'admin.assignments.index'],
            ['label' => 'Disponibles', 'value' => $cards['available'], 'icon' => 'ri-checkbox-circle-line', 'route' => 'admin.assets.index'],
            ['label' => 'En reparación', 'value' => $cards['repair'], 'icon' => 'ri-tools-line', 'route' => 'admin.assets.index'],
        ] as $c)
            <a href="{{ route($c['route']) }}" class="card p-5 hover:border-primary-container transition-colors">
                <div class="flex items-center justify-between">
                    <span class="text-label-md text-on-surface-variant uppercase tracking-wider">{{ $c['label'] }}</span>
                    <i class="{{ $c['icon'] }} text-xl text-primary-container"></i>
                </div>
                <span class="mt-3 block text-display-lg text-on-surface">{{ number_format($c['value']) }}</span>
            </a>
        @endforeach
    </div>

    {{-- Segunda fila: empleados, licencias, consumibles, problemas --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-gutter mb-6">
        @foreach ([
            ['label' => 'Empleados activos', 'value' => $cards['employees'], 'icon' => 'ri-team-line'],
            ['label' => 'Licencias', 'value' => $cards['licenses'], 'icon' => 'ri-key-2-line'],
            ['label' => 'Consumibles', 'value' => $cards['consumables'], 'icon' => 'ri-archive-line'],
            ['label' => 'Problemas abiertos', 'value' => $cards['open_problems'], 'icon' => 'ri-error-warning-line'],
        ] as $c)
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-label-md text-on-surface-variant uppercase tracking-wider">{{ $c['label'] }}</span>
                    <i class="{{ $c['icon'] }} text-lg text-on-surface-variant"></i>
                </div>
                <span class="mt-2 block text-headline-md text-on-surface">{{ number_format($c['value']) }}</span>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-gutter mb-6">
        {{-- Panel de alertas --}}
        <div class="card p-5 lg:col-span-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-title-md text-on-surface">Alertas</h3>
                @if ($totalAlerts > 0)
                    <span class="chip-alert">{{ $totalAlerts }}</span>
                @else
                    <span class="chip-success">Al día</span>
                @endif
            </div>

            @if ($totalAlerts === 0)
                <p class="text-body-md text-on-surface-variant">Sin alertas pendientes. Todo en orden.</p>
            @else
                <div class="space-y-4">
                    {{-- Renovaciones de licencias --}}
                    @if ($alerts['summary']['license_renewals'] > 0)
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <i class="ri-refresh-line text-amber-600"></i>
                                <span class="text-body-md font-medium text-on-surface">Renovaciones de licencias ({{ $alerts['summary']['license_renewals'] }})</span>
                            </div>
                            <ul class="ps-6 space-y-1">
                                @foreach ($alerts['license_renewals'] as $lic)
                                    <li class="text-body-sm">
                                        <a href="{{ route('admin.licenses.show', $lic->id) }}" class="text-primary hover:underline">{{ $lic->software_name }}</a>
                                        <span class="text-on-surface-variant">— {{ $lic->renewal_date?->format('d/m/Y') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Garantías --}}
                    @if ($alerts['summary']['warranties_expiring'] > 0)
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <i class="ri-shield-check-line text-amber-600"></i>
                                <span class="text-body-md font-medium text-on-surface">Garantías por vencer ({{ $alerts['summary']['warranties_expiring'] }})</span>
                            </div>
                            <ul class="ps-6 space-y-1">
                                @foreach ($alerts['warranties'] as $asset)
                                    <li class="text-body-sm">
                                        <a href="{{ route('admin.assets.show', $asset->id) }}" class="text-primary hover:underline">{{ $asset->asset_tag }}</a>
                                        <span class="text-on-surface-variant">— {{ $asset->warranty_expires_at?->format('d/m/Y') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Stock bajo --}}
                    @if ($alerts['summary']['low_stock'] > 0)
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <i class="ri-alert-line text-alert"></i>
                                <span class="text-body-md font-medium text-on-surface">Stock bajo ({{ $alerts['summary']['low_stock'] }})</span>
                            </div>
                            <ul class="ps-6 space-y-1">
                                @foreach ($alerts['low_stock'] as $cons)
                                    <li class="text-body-sm">
                                        <a href="{{ route('admin.consumables.show', $cons->id) }}" class="text-primary hover:underline">{{ $cons->name }}</a>
                                        <span class="text-on-surface-variant">— {{ $cons->stock }}/{{ $cons->min_stock }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Activos por estado --}}
        <div class="card p-5">
            <h3 class="text-title-md text-on-surface mb-4">Activos por estado</h3>
            @php($maxStatus = $byStatus->max('assets_count') ?: 1)
            <div class="space-y-3">
                @foreach ($byStatus as $st)
                    <div>
                        <div class="flex justify-between text-body-sm mb-1">
                            <span class="text-on-surface">{{ $st->name }}</span>
                            <span class="text-on-surface-variant">{{ $st->assets_count }}</span>
                        </div>
                        <div class="w-full h-2 bg-surface-container-highest rounded-full overflow-hidden">
                            <div class="h-full rounded-full" style="width: {{ round($st->assets_count / $maxStatus * 100) }}%; background: {{ $chipColor[$st->color] ?? '#0052cc' }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Activos por ubicación --}}
        <div class="card p-5">
            <h3 class="text-title-md text-on-surface mb-4">Activos por ubicación</h3>
            @php($maxLoc = $byLocation->max('assets_count') ?: 1)
            <div class="space-y-3">
                @forelse ($byLocation as $loc)
                    <div>
                        <div class="flex justify-between text-body-sm mb-1">
                            <span class="text-on-surface truncate">{{ $loc->name }}</span>
                            <span class="text-on-surface-variant">{{ $loc->assets_count }}</span>
                        </div>
                        <div class="w-full h-2 bg-surface-container-highest rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-primary-container" style="width: {{ round($loc->assets_count / $maxLoc * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-body-sm text-on-surface-variant">Sin datos.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Conteo por tipo (bento GLPI) --}}
    <div class="card p-5 mb-6">
        <h3 class="text-title-md text-on-surface mb-4">Inventario por tipo</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            @foreach ($byType as $type)
                <a href="{{ route('admin.assets.index') }}" class="border border-border-soft rounded-lg p-4 hover:border-primary-container transition-colors">
                    <i class="{{ $type->icon ?? 'ri-hard-drive-2-line' }} text-2xl text-primary-container"></i>
                    <div class="mt-2 text-headline-sm text-on-surface">{{ $type->assets_count }}</div>
                    <div class="text-body-sm text-on-surface-variant truncate">{{ $type->name }}</div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Últimos movimientos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-gutter">
        <div class="card">
            <div class="px-4 py-3 border-b border-border-soft flex items-center justify-between">
                <h3 class="text-title-md text-on-surface">Últimas asignaciones</h3>
                <a href="{{ route('admin.assignments.index') }}" class="text-body-sm text-primary hover:underline">Ver todas</a>
            </div>
            <div class="divide-y divide-border-soft">
                @forelse ($recentAssignments as $a)
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('admin.assets.show', $a->asset_id) }}" class="text-body-md text-on-surface hover:text-primary">
                                <span class="font-mono text-mono-sm text-primary">{{ $a->asset?->asset_tag }}</span> {{ $a->asset?->name }}
                            </a>
                            <p class="text-body-sm text-on-surface-variant">{{ $a->employee?->name }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-body-sm text-on-surface-variant">{{ $a->assigned_at?->format('d/m/Y') }}</span>
                            @if (is_null($a->returned_at)) <span class="chip-success block mt-1">Activa</span> @endif
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-body-md text-on-surface-variant">Sin asignaciones.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="px-4 py-3 border-b border-border-soft flex items-center justify-between">
                <h3 class="text-title-md text-on-surface">Últimos problemas</h3>
                <a href="{{ route('admin.problems.index') }}" class="text-body-sm text-primary hover:underline">Ver todos</a>
            </div>
            <div class="divide-y divide-border-soft">
                @forelse ($recentProblems as $p)
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('admin.problems.show', $p->id) }}" class="text-body-md text-on-surface hover:text-primary">{{ $p->title }}</a>
                            <p class="text-body-sm text-on-surface-variant font-mono text-mono-sm">{{ $p->asset?->asset_tag }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-body-sm text-on-surface-variant">{{ $p->reported_at?->format('d/m/Y') }}</span>
                            @php($sc = match ($p->status) { 'new' => 'chip-info', 'in_progress' => 'chip-warning', 'resolved' => 'chip-success', default => 'chip-neutral' })
                            <span class="{{ $sc }} block mt-1">{{ \App\Models\Problem::STATUSES[$p->status] }}</span>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-body-md text-on-surface-variant">Sin problemas.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
