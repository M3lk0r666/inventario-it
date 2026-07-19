@php
    $links = [
        [
            'name' => 'Dashboard',
            'icon' => 'ri-dashboard-line',
            'href' => route('admin.dashboard'),
            'active' => request()->routeIs('admin.dashboard'),
        ],
        [
            'header' => 'Encabezado',
        ],
        [
            'name' => 'nombre1',
            'icon' => 'ri-building-2-line',
            'href' => '#',
            'active' => false,
        ],
        [
            'header' => 'Encabezado',
        ],
        [
            'name' => 'nombre2',
            'icon' => 'ri-file-list-3-line',
            'href' => '#',
            'active' => false,
        ],
        [
            'name' => 'Nombre3',
            'icon' => 'ri-upload-2-line',
            'href' => '#',
            'active' => false,
        ],
    ];
@endphp

<aside id="top-bar-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-full transition-transform -translate-x-full sm:translate-x-0"
    aria-label="Sidebar">
    <div class="h-full px-3 py-4 overflow-y-auto bg-neutral-primary-soft border-e border-default">
        <a href="/" class="flex items-center ps-2.5 mb-5">
            <img src="https://flowbite.com/docs/images/logo.svg" class="h-6 me-3" alt="Flowbite Logo" />
            <span class="self-center text-lg text-heading font-semibold whitespace-nowrap">Flowbite</span>
        </a>
        <ul class="space-y-2 font-medium">
            @foreach ($links as $link)
                <li>
                    @isset($link['header'])
                        <div class="px-2 py-2 text-xs font-semibold text-gray-500 uppercase">
                            {{ $link['header'] }}
                        </div>
                    @else
                        <a href="{{ $link['href'] }}"
                            class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group {{ $link['active'] ? 'bg-gray-200' : '' }}">
                            <span class="inline-flex justify-center items-center">
                                <i class="{{ $link['icon'] }}"></i>
                            </span>
                            <span class="ms-3">{{ $link['name'] }}</span>
                        </a>
                    @endisset
                </li>
            @endforeach
        </ul>
    </div>
</aside>
