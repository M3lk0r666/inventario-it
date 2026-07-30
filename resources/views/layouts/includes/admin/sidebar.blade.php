@php
    /**
     * Menú del panel. Cada item puede llevar:
     *  - 'can'      => permiso requerido (se oculta sin él)
     *  - 'route'    => nombre de ruta (si no existe aún, apunta a '#')
     *  - 'children' => submenú desplegable [['name','href','active'], ...]
     * Las secciones se ocultan si el usuario no puede ver ningún item.
     */
    $catalogChildren = collect(\App\Support\CatalogRegistry::menuItems())
        ->map(function ($def, $key) {
            $current = request()->route('catalog') ?? array_key_first(\App\Support\CatalogRegistry::menuItems());

            return [
                'name' => $def['label'],
                'href' => route('admin.catalogs.index', $key),
                'active' => request()->routeIs('admin.catalogs.*') && $current === $key,
            ];
        })->values()->all();

    $sections = [
        [
            'header' => null,
            'items' => [
                ['name' => 'Dashboard', 'icon' => 'ri-dashboard-line', 'route' => 'admin.dashboard'],
            ],
        ],
        [
            'header' => 'Inventario',
            'items' => [
                ['name' => 'Activos', 'icon' => 'ri-computer-line', 'can' => 'assets.view', 'route' => 'admin.assets.index'],
                ['name' => 'Asignaciones', 'icon' => 'ri-user-received-line', 'can' => 'assignments.view', 'route' => 'admin.assignments.index'],
                ['name' => 'Cartas responsivas', 'icon' => 'ri-file-text-line', 'can' => 'responsive_letters.view', 'route' => 'admin.letters.index'],
                ['name' => 'Consumibles', 'icon' => 'ri-archive-line', 'can' => 'consumables.view', 'route' => 'admin.consumables.index'],
                ['name' => 'Licencias', 'icon' => 'ri-key-2-line', 'can' => 'licenses.view', 'route' => 'admin.licenses.index'],
            ],
        ],
        [
            'header' => 'Soporte',
            'items' => [
                ['name' => 'Problemas', 'icon' => 'ri-error-warning-line', 'can' => 'problems.view', 'route' => 'admin.problems.index'],
            ],
        ],
        [
            'header' => 'Gestión',
            'items' => [
                ['name' => 'Empleados', 'icon' => 'ri-team-line', 'can' => 'employees.view', 'route' => 'admin.employees.index'],
                ['name' => 'Proveedores', 'icon' => 'ri-truck-line', 'can' => 'suppliers.view', 'route' => 'admin.suppliers.index'],
            ],
        ],
        [
            'header' => 'Herramientas',
            'items' => [
                ['name' => 'Recordatorios', 'icon' => 'ri-alarm-line', 'can' => 'reminders.view', 'route' => 'admin.reminders.index'],
                ['name' => 'Base de conocimientos', 'icon' => 'ri-book-open-line', 'can' => 'kb.view', 'route' => 'admin.kb.index'],
            ],
        ],
        [
            'header' => 'Reportes',
            'items' => [
                ['name' => 'Reportes', 'icon' => 'ri-bar-chart-2-line', 'can' => 'reports.view', 'route' => 'admin.reports.index'],
            ],
        ],
        [
            'header' => 'Administración',
            'items' => [
                ['name' => 'Catálogos', 'icon' => 'ri-list-settings-line', 'can' => 'catalogs.view', 'children' => $catalogChildren],
                ['name' => 'Usuarios', 'icon' => 'ri-user-settings-line', 'can' => 'users.view', 'route' => 'admin.users.index'],
                ['name' => 'Configuración', 'icon' => 'ri-settings-3-line', 'can' => 'settings.view', 'route' => 'admin.settings.index'],
                ['name' => 'Auditoría', 'icon' => 'ri-history-line', 'can' => 'activity.view', 'route' => 'admin.audit.index'],
            ],
        ],
    ];

    $user = auth()->user();
    $visibleSections = collect($sections)->map(function ($section) use ($user) {
        $section['items'] = collect($section['items'])
            ->filter(fn ($item) => empty($item['can']) || $user->can($item['can']))
            ->map(function ($item) {
                if (isset($item['children'])) {
                    $item['active'] = collect($item['children'])->contains(fn ($c) => $c['active']);
                    $item['href'] = '#';

                    return $item;
                }

                $item['href'] = isset($item['route']) && \Illuminate\Support\Facades\Route::has($item['route'])
                    ? route($item['route'])
                    : ($item['href'] ?? '#');
                // Activo también en sub-rutas del módulo (p.ej. admin.assets.show)
                $item['active'] = isset($item['route'])
                    && request()->routeIs(\Illuminate\Support\Str::beforeLast($item['route'], '.index').'*');

                return $item;
            })
            ->values();

        return $section;
    })->filter(fn ($section) => $section['items']->isNotEmpty());
@endphp

<aside id="top-bar-sidebar" x-data
    :class="$store.sidebar.collapsed ? 'sidebar-collapsed' : ''"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-16 transition-all duration-200 -translate-x-full sm:translate-x-0 bg-white border-e border-border-soft flex flex-col"
    aria-label="Sidebar">
    <div class="flex-1 py-3 overflow-y-auto custom-scrollbar">
        <ul class="space-y-0.5">
            @foreach ($visibleSections as $section)
                @if ($section['header'])
                    <li>
                        <div class="sidebar-header px-6 pt-4 pb-1 text-label-md text-on-surface-variant uppercase tracking-wider">
                            {{ $section['header'] }}
                        </div>
                    </li>
                @endif
                @foreach ($section['items'] as $item)
                    <li>
                        @if (isset($item['children']))
                            {{-- Item con submenú desplegable --}}
                            <div x-data="{ open: @js($item['active']) }">
                                <button type="button" @click="open = !open"
                                    title="{{ $item['name'] }}"
                                    class="sidebar-item flex items-center w-full px-6 py-2.5 border-l-4 transition-colors group
                                        {{ $item['active']
                                            ? 'border-primary-container bg-surface-container-low text-primary font-bold'
                                            : 'border-transparent text-on-surface-variant hover:bg-surface-container-low' }}">
                                    <span class="inline-flex justify-center items-center text-lg">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </span>
                                    <span class="sidebar-label ms-3 flex-1 text-left text-body-md">{{ $item['name'] }}</span>
                                    <i class="sidebar-chevron ri-arrow-down-s-line transition-transform" :class="open ? 'rotate-180' : ''"></i>
                                </button>
                                <ul x-show="open && !$store.sidebar.collapsed" x-collapse x-cloak class="sidebar-submenu py-1 space-y-0.5 bg-surface-container-low/50">
                                    @foreach ($item['children'] as $child)
                                        <li>
                                            <a href="{{ $child['href'] }}"
                                                class="flex items-center ps-[3.25rem] pe-4 py-1.5 text-body-sm border-l-4 transition-colors
                                                    {{ $child['active']
                                                        ? 'border-primary-container text-primary font-bold'
                                                        : 'border-transparent text-on-surface-variant hover:text-primary' }}">
                                                {{ $child['name'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <a href="{{ $item['href'] }}" title="{{ $item['name'] }}"
                                class="sidebar-item flex items-center px-6 py-2.5 border-l-4 transition-colors group
                                    {{ $item['active']
                                        ? 'border-primary-container bg-surface-container-low text-primary font-bold'
                                        : 'border-transparent text-on-surface-variant hover:bg-surface-container-low' }}">
                                <span class="inline-flex justify-center items-center text-lg">
                                    <i class="{{ $item['icon'] }}"></i>
                                </span>
                                <span class="sidebar-label ms-3 text-body-md">{{ $item['name'] }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            @endforeach
        </ul>
    </div>

    {{-- Usuario actual --}}
    <div class="sidebar-item px-6 py-4 border-t border-border-soft">
        <div class="flex items-center">
            <img class="w-9 h-9 rounded-full object-cover shrink-0" src="{{ auth()->user()->profile_photo_url }}"
                alt="{{ auth()->user()->name }}" title="{{ auth()->user()->name }}" />
            <div class="sidebar-label ms-3 min-w-0">
                <p class="text-label-md text-on-surface truncate">{{ \Illuminate\Support\Str::words(auth()->user()->name, 2, '') }}</p>
                <p class="text-[10px] text-on-surface-variant truncate">
                    {{ auth()->user()->getRoleNames()->implode(', ') ?: 'Sin rol' }}
                </p>
            </div>
        </div>
    </div>
</aside>
