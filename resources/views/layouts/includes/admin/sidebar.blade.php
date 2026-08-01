@php
    /**
     * Menú del panel al estilo GLPI:
     *  - Encabezados con varios items => grupo colapsable (acordeón, uno abierto a la vez).
     *  - Encabezados con un solo item (o sin encabezado) => enlace suelto de nivel superior.
     *  - En modo colapsado, al pasar el cursor por un ícono aparece un flotante con el
     *    nombre del grupo y sus opciones (con ícono), resaltando al pasar el mouse.
     *
     * Cada item puede llevar:
     *  - 'can'      => permiso requerido (se oculta sin él)
     *  - 'route'    => nombre de ruta (si no existe, apunta a '#')
     *  - 'children' => submenú [['name','href','active'], ...] (p.ej. Catálogos)
     */
    $catalogChildren = collect(\App\Support\CatalogRegistry::menuItems())
        ->map(function ($def, $key) {
            $current = request()->route('catalog') ?? array_key_first(\App\Support\CatalogRegistry::menuItems());

            return [
                'name' => $def['label'],
                'can' => 'catalogs.view',
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
            'header' => 'Inventario', 'icon' => 'ri-stack-line',
            'items' => [
                ['name' => 'Activos', 'icon' => 'ri-computer-line', 'can' => 'assets.view', 'route' => 'admin.assets.index'],
                ['name' => 'Asignaciones', 'icon' => 'ri-user-received-line', 'can' => 'assignments.view', 'route' => 'admin.assignments.index'],
                ['name' => 'Cartas responsivas', 'icon' => 'ri-file-text-line', 'can' => 'responsive_letters.view', 'route' => 'admin.letters.index'],
                ['name' => 'Consumibles', 'icon' => 'ri-archive-line', 'can' => 'consumables.view', 'route' => 'admin.consumables.index'],
                ['name' => 'Licencias', 'icon' => 'ri-key-2-line', 'can' => 'licenses.view', 'route' => 'admin.licenses.index'],
            ],
        ],
        [
            'header' => 'Soporte', 'icon' => 'ri-customer-service-2-line',
            'items' => [
                ['name' => 'Problemas', 'icon' => 'ri-error-warning-line', 'can' => 'problems.view', 'route' => 'admin.problems.index'],
            ],
        ],
        [
            'header' => 'Gestión', 'icon' => 'ri-briefcase-4-line',
            'items' => [
                ['name' => 'Empleados', 'icon' => 'ri-team-line', 'can' => 'employees.view', 'route' => 'admin.employees.index'],
                ['name' => 'Proveedores', 'icon' => 'ri-truck-line', 'can' => 'suppliers.view', 'route' => 'admin.suppliers.index'],
            ],
        ],
        [
            'header' => 'Herramientas', 'icon' => 'ri-tools-line',
            'items' => [
                ['name' => 'Recordatorios', 'icon' => 'ri-alarm-line', 'can' => 'reminders.view', 'route' => 'admin.reminders.index'],
                ['name' => 'Base de conocimientos', 'icon' => 'ri-book-open-line', 'can' => 'kb.view', 'route' => 'admin.kb.index'],
            ],
        ],
        [
            'header' => 'Reportes', 'icon' => 'ri-bar-chart-2-line',
            'items' => [
                ['name' => 'Reportes', 'icon' => 'ri-bar-chart-2-line', 'can' => 'reports.view', 'route' => 'admin.reports.index'],
            ],
        ],
        [
            'header' => 'Catálogos', 'icon' => 'ri-list-settings-line',
            'items' => $catalogChildren,
        ],
        [
            'header' => 'Administración', 'icon' => 'ri-shield-keyhole-line',
            'items' => [
                ['name' => 'Usuarios', 'icon' => 'ri-user-settings-line', 'can' => 'users.view', 'route' => 'admin.users.index'],
                ['name' => 'Configuración', 'icon' => 'ri-settings-3-line', 'can' => 'settings.view', 'route' => 'admin.settings.index'],
                ['name' => 'Auditoría', 'icon' => 'ri-history-line', 'can' => 'activity.view', 'route' => 'admin.audit.index'],
            ],
        ],
    ];

    $user = auth()->user();

    // 1) Filtrar por permisos y resolver href/active de cada item
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
                // Conserva 'active' precalculado (p.ej. items de Catálogos); si no,
                // lo deriva de la ruta del módulo (incluye sub-rutas como *.show).
                $item['active'] = $item['active']
                    ?? (isset($item['route'])
                        && request()->routeIs(\Illuminate\Support\Str::beforeLast($item['route'], '.index').'*'));

                return $item;
            })
            ->values();

        return $section;
    })->filter(fn ($section) => $section['items']->isNotEmpty());

    // 2) Construir nodos de nivel superior: 'link' o 'group' (acordeón)
    $nodes = collect();
    foreach ($visibleSections as $section) {
        $items = $section['items'];
        $single = empty($section['header']) || ($items->count() === 1 && ! isset($items->first()['children']));

        if ($single) {
            foreach ($items as $it) {
                $nodes->push(['type' => 'link'] + $it);
            }
        } else {
            $nodes->push([
                'type' => 'group',
                'key' => \Illuminate\Support\Str::slug($section['header']),
                'name' => $section['header'],
                'icon' => $section['icon'] ?? 'ri-folder-3-line',
                'active' => $items->contains(fn ($i) => ! empty($i['active'])),
                'children' => $items->all(),
            ]);
        }
    }

    // Grupo abierto inicialmente = el que contiene la ruta actual
    $activeGroup = optional($nodes->first(fn ($n) => $n['type'] === 'group' && ! empty($n['active'])))['key'] ?? null;

    // Clases reutilizables
    $topActive = 'border-primary-container bg-surface-container-low text-primary font-bold';
    $topIdle = 'border-transparent text-on-surface-variant hover:bg-surface-container-low';
    $childActive = 'border-primary-container text-primary font-bold bg-surface-container-low/70';
    $childIdle = 'border-transparent text-on-surface-variant hover:text-primary hover:bg-surface-container-low/60';
@endphp

<aside id="top-bar-sidebar" x-data="sidebarFlyout()"
    :class="$store.sidebar.collapsed ? 'sidebar-collapsed' : ''"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-16 transition-all duration-200 -translate-x-full sm:translate-x-0 bg-white border-e border-border-soft flex flex-col"
    aria-label="Sidebar">
    <div class="flex-1 py-3 overflow-y-auto custom-scrollbar">
        <ul class="space-y-0.5" x-data="{ group: @js($activeGroup) }">
            @foreach ($nodes as $node)
                <li>
                    @if ($node['type'] === 'link')
                        {{-- Enlace suelto de nivel superior --}}
                        <a href="{{ $node['href'] }}"
                            @mouseenter="openFly($event, @js($node['name']), [])" @mouseleave="closeFly()"
                            class="sidebar-item flex items-center px-6 py-2.5 border-l-4 transition-colors {{ $node['active'] ? $topActive : $topIdle }}">
                            <span class="inline-flex justify-center items-center text-lg"><i class="{{ $node['icon'] }}"></i></span>
                            <span class="sidebar-label ms-3 text-body-md">{{ $node['name'] }}</span>
                        </a>
                    @else
                        {{-- Grupo colapsable (acordeón) --}}
                        @php($flyChildren = collect($node['children'])->map(fn ($c) => [
                            'name' => $c['name'], 'icon' => $c['icon'] ?? null,
                            'href' => $c['href'], 'active' => $c['active'] ?? false,
                            'children' => $c['children'] ?? null,
                        ])->all())
                        <button type="button"
                            @click="group = (group === '{{ $node['key'] }}' ? null : '{{ $node['key'] }}')"
                            @mouseenter="openFly($event, @js($node['name']), @js($flyChildren))" @mouseleave="closeFly()"
                            class="sidebar-item flex items-center w-full px-6 py-2.5 border-l-4 transition-colors {{ $node['active'] ? $topActive : $topIdle }}">
                            <span class="inline-flex justify-center items-center text-lg"><i class="{{ $node['icon'] }}"></i></span>
                            <span class="sidebar-label ms-3 flex-1 text-left text-body-md">{{ $node['name'] }}</span>
                            <i class="sidebar-chevron ri-arrow-down-s-line transition-transform" :class="group === '{{ $node['key'] }}' ? 'rotate-180' : ''"></i>
                        </button>
                        <ul x-show="group === '{{ $node['key'] }}'" x-collapse x-cloak
                            class="sidebar-submenu py-1 space-y-0.5 bg-surface-container-low/40">
                            @foreach ($node['children'] as $child)
                                <li>
                                    @if (isset($child['children']))
                                        {{-- Subnivel (Catálogos) --}}
                                        <div x-data="{ sub: @js($child['active']) }">
                                            <button type="button" @click="sub = !sub"
                                                class="flex items-center w-full ps-12 pe-4 py-1.5 text-body-sm border-l-4 transition-colors {{ $child['active'] ? $childActive : $childIdle }}">
                                                <i class="{{ $child['icon'] }} me-2 text-base"></i>
                                                <span class="flex-1 text-left">{{ $child['name'] }}</span>
                                                <i class="ri-arrow-down-s-line transition-transform" :class="sub ? 'rotate-180' : ''"></i>
                                            </button>
                                            <ul x-show="sub" x-collapse x-cloak class="py-0.5">
                                                @foreach ($child['children'] as $leaf)
                                                    <li>
                                                        <a href="{{ $leaf['href'] }}"
                                                            class="block ps-[4.5rem] pe-4 py-1 text-body-sm border-l-4 transition-colors {{ $leaf['active'] ? $childActive : $childIdle }}">
                                                            {{ $leaf['name'] }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <a href="{{ $child['href'] }}"
                                            class="flex items-center ps-12 pe-4 py-1.5 text-body-sm border-l-4 transition-colors {{ $child['active'] ? $childActive : $childIdle }}">
                                            @if (! empty($child['icon']))
                                                <i class="{{ $child['icon'] }} me-2 text-base"></i>
                                            @endif
                                            <span>{{ $child['name'] }}</span>
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
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

    {{-- Flotante (solo en modo colapsado): grupo + opciones con ícono --}}
    <div x-cloak x-show="fly.show" @mouseenter="cancelClose()" @mouseleave="closeFly()"
        :style="`top:${fly.top}px`"
        x-transition.opacity.duration.100ms
        class="fixed left-16 z-50 min-w-56 max-h-[80vh] overflow-y-auto custom-scrollbar rounded-lg border border-border-soft bg-white shadow-[0_10px_25px_rgba(0,0,0,0.12)] py-1">
        <div class="px-4 pt-2 pb-1 text-label-md text-on-surface-variant uppercase tracking-wider" x-text="fly.label"></div>
        <template x-if="fly.children.length">
            <ul class="py-1">
                <template x-for="c in fly.children" :key="c.name">
                    <li>
                        {{-- Opción con subniveles (Catálogos) --}}
                        <template x-if="c.children && c.children.length">
                            <div>
                                <div class="flex items-center gap-1.5 px-4 pt-2 pb-0.5 text-[10px] uppercase tracking-wider text-on-surface-variant">
                                    <i :class="c.icon"></i><span x-text="c.name"></span>
                                </div>
                                <template x-for="leaf in c.children" :key="leaf.href">
                                    <a :href="leaf.href" x-text="leaf.name"
                                        :class="leaf.active ? 'text-primary font-bold' : 'text-on-surface-variant'"
                                        class="block ps-9 pe-4 py-1 text-body-sm hover:bg-surface-container-low hover:text-primary"></a>
                                </template>
                            </div>
                        </template>
                        {{-- Opción simple --}}
                        <template x-if="!(c.children && c.children.length)">
                            <a :href="c.href"
                                :class="c.active ? 'text-primary font-bold' : 'text-on-surface-variant'"
                                class="flex items-center gap-2 px-4 py-1.5 text-body-sm hover:bg-surface-container-low hover:text-primary">
                                <i :class="c.icon" x-show="c.icon" class="text-base"></i><span x-text="c.name"></span>
                            </a>
                        </template>
                    </li>
                </template>
            </ul>
        </template>
    </div>
</aside>
