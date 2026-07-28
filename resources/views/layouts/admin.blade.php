@props([
    'title' => config('app.name', 'Laravel'),
    'breadcrumbs' => [],
])

<div>
    <!-- Simplicity is an acquired taste. - Katharine Gerould -->
</div>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Wire UI -->
    <wireui:scripts />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Line Awesome -->
    <link rel= "stylesheet" href= "/assets/lineawesome/css/line-awesome.min.css">
    <!-- Remix Icon -->
    <link rel= "stylesheet" href= "/assets/remix-icon/remixicon.css">


    <!-- Styles -->
    @livewireStyles
</head>

<body class="font-sans antialiased bg-canvas text-on-surface">
    <x-banner />

    @include('layouts.includes.admin.navigation')

    @include('layouts.includes.admin.sidebar')

    <div class="p-4 sm:ml-64">
        <div class="mt-14">
            @include('layouts.includes.admin.breadcrumbs')
        </div>
        {{ $slot }}
    </div>


    @stack('modals')

    {{-- Alta en línea de catálogos, disponible en todo el panel --}}
    <livewire:shared.catalog-quick-create />

    {{-- Notificaciones tipo toast --}}
    <div x-data="toastManager" class="fixed top-20 right-4 z-[100] space-y-2 w-80" x-cloak>
        <template x-for="t in toasts" :key="t.id">
            <div class="flex items-start gap-2 p-3 rounded-lg shadow-lg text-sm text-white"
                :class="t.type === 'error' ? 'bg-red-600' : 'bg-gray-900'"
                x-transition>
                <i :class="t.type === 'error' ? 'ri-error-warning-line' : 'ri-checkbox-circle-line'" class="text-lg shrink-0"></i>
                <span x-text="t.message" class="flex-1"></span>
                <button type="button" @click="toasts = toasts.filter(x => x.id !== t.id)" class="shrink-0 opacity-70 hover:opacity-100">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        </template>
    </div>

    @livewireScripts

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('toastManager', () => ({
                toasts: [],
                push(type, message) {
                    const id = Date.now() + Math.random();
                    this.toasts.push({ id, type, message });
                    setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 4500);
                },
                init() {
                    @if (session('toast'))
                        this.push('success', @js(session('toast')));
                    @endif
                    Livewire.on('toast', ({ type = 'success', message = '' }) => this.push(type, message));
                    // Abrir documentos generados (p.ej. carta responsiva PDF)
                    Livewire.on('open-url', ({ url }) => window.open(url, '_blank'));
                }
            }));
        });
    </script>
</body>

</html>
