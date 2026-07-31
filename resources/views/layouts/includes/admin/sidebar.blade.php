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

<aside id="top-bar-sidebar" x-data="sidebarFlyout()"
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
                                    @mouseenter="openFly($event, @js($item['name']), @js($item['children']))"
                                    @mouseleave="closeFly()"
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
                                <ul x-show="open" x-collapse x-cloak class="sidebar-submenu py-1 space-y-0.5 bg-surface-container-low/50">
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
                            <a href="{{ $item['href'] }}"
                                @mouseenter="openFly($event, @js($item['name']), [])"
                                @mouseleave="closeFly()"
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

    {{-- Botón colapsar / expandir --}}
    <div class="border-t border-border-soft p-2">
        <button type="button" x-data @click="$store.sidebar.toggle()" title="Colapsar / expandir menú"
            class="sidebar-item flex items-center w-full px-6 py-2.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low transition-colors">
            <span class="inline-flex justify-center items-center text-lg">
                <i class="ri-menu-fold-line" x-show="!$store.sidebar.collapsed"></i>
                <i class="ri-menu-unfold-line" x-show="$store.sidebar.collapsed" x-cloak></i>
            </span>
            <span class="sidebar-label ms-3 text-body-md">Colapsar menú</span>
        </button>
    </div>

    {{-- Flotante (solo en modo colapsado): etiqueta del ícono y su submenú --}}
    <div x-cloak x-show="fly.show" @mouseenter="cancelClose()" @mouseleave="closeFly()"
        :style="`top:${fly.top}px`"
        x-transition.opacity.duration.100ms
        class="fixed left-16 z-50 min-w-52 rounded-lg border border-border-soft bg-white shadow-[0_10px_25px_rgba(0,0,0,0.12)] py-1">
        <div class="px-4 pt-2 pb-1 text-label-md text-on-surface-variant uppercase tracking-wider" x-text="fly.label"></div>
        <template x-if="fly.children.length">
            <ul class="py-1">
                <template x-for="c in fly.children" :key="c.href">
                    <li>
                        <a :href="c.href" x-text="c.name"
                            :class="c.active ? 'text-primary font-bold' : 'text-on-surface-variant'"
                            class="block px-4 py-1.5 text-body-sm hover:bg-surface-container-low hover:text-primary"></a>
                    </li>
                </template>
            </ul>
        </template>
    </div>
</aside>
