@php
    $companyName = \App\Models\Setting::get('company_name', config('app.name'));
    $logo = \App\Models\Setting::get('company_logo');
    $modules = [
        ['icon' => 'ri-computer-line', 'title' => 'Activos', 'desc' => 'Registro de equipos con especificaciones, imágenes y trazabilidad completa.'],
        ['icon' => 'ri-user-received-line', 'title' => 'Asignaciones y cartas', 'desc' => 'Entrega y devolución de bienes con carta responsiva en PDF y folio.'],
        ['icon' => 'ri-archive-line', 'title' => 'Consumibles', 'desc' => 'Control de existencias con kardex de entradas y salidas.'],
        ['icon' => 'ri-key-2-line', 'title' => 'Licencias', 'desc' => 'Software, asientos, expiración y alertas de renovación.'],
        ['icon' => 'ri-error-warning-line', 'title' => 'Soporte', 'desc' => 'Problemas ligados a equipos, con seguimiento y costos de reparación.'],
        ['icon' => 'ri-team-line', 'title' => 'Empleados', 'desc' => 'Colaboradores, sus cuentas de acceso y bienes a su cargo.'],
        ['icon' => 'ri-bar-chart-2-line', 'title' => 'Reportes', 'desc' => 'Inventario, costos y asignaciones exportables a PDF y Excel.'],
        ['icon' => 'ri-book-open-line', 'title' => 'Base de conocimientos', 'desc' => 'Guías y procedimientos de TI, compartibles por correo.'],
    ];
    $accessUrl = auth()->check() ? url('/admin') : route('login');
    $accessLabel = auth()->check() ? 'Ir al portal' : 'Acceder al portal';
@endphp

<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $companyName }} — Inventario TI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/remix-icon/remixicon.css">
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-canvas text-on-surface">

    {{-- Barra superior --}}
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-border-soft">
        <div class="max-w-6xl mx-auto px-4 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if ($logo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logo) }}" alt="Logo" class="h-14 w-auto object-contain">
                @else
                    <span class="text-title-md font-bold text-primary">{{ $companyName }}</span>
                @endif
                <span class="hidden sm:block text-body-sm text-on-surface-variant">· Inventario TI</span>
            </div>
            <a href="{{ $accessUrl }}" class="btn-primary">
                <i class="ri-login-box-line"></i> {{ auth()->check() ? 'Ir al portal' : 'Acceder' }}
            </a>
        </div>
    </header>

    {{-- Hero --}}
    <section class="max-w-6xl mx-auto px-4 pt-16 pb-12 text-center">
        <span class="inline-flex items-center gap-1 chip-info mb-4">
            <i class="ri-database-2-line"></i> Control de bienes informáticos
        </span>
        <h1 class="text-display-lg sm:text-4xl font-bold text-on-surface max-w-3xl mx-auto leading-tight">
            Sistema de Inventario de TI de {{ $companyName }}
        </h1>
        <p class="mt-4 text-body-md sm:text-lg text-on-surface-variant max-w-2xl mx-auto">
            Plataforma centralizada para administrar los equipos de cómputo, licencias, consumibles y su
            asignación a los colaboradores, con cartas responsivas, soporte y trazabilidad completa de cada bien.
        </p>
        <div class="mt-8 flex items-center justify-center gap-3">
            <a href="{{ $accessUrl }}" class="btn-primary !px-6 !py-3">
                <i class="ri-login-box-line"></i> {{ $accessLabel }}
            </a>
            <a href="#modulos" class="btn-ghost !px-6 !py-3">Conocer módulos</a>
        </div>
    </section>

    {{-- Objetivo --}}
    <section class="max-w-6xl mx-auto px-4 pb-12">
        <div class="card p-6 sm:p-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <div class="w-10 h-10 rounded-lg bg-primary-fixed text-primary flex items-center justify-center mb-3"><i class="ri-focus-3-line text-xl"></i></div>
                <h3 class="text-title-md text-on-surface">Objetivo</h3>
                <p class="mt-1 text-body-md text-on-surface-variant">Tener una única fuente de verdad del inventario informático: qué hay, dónde está, quién lo tiene y en qué estado.</p>
            </div>
            <div>
                <div class="w-10 h-10 rounded-lg bg-primary-fixed text-primary flex items-center justify-center mb-3"><i class="ri-shield-check-line text-xl"></i></div>
                <h3 class="text-title-md text-on-surface">Control y evidencia</h3>
                <p class="mt-1 text-body-md text-on-surface-variant">Cartas responsivas firmadas, histórico de asignaciones y auditoría de cambios para respaldar cada movimiento.</p>
            </div>
            <div>
                <div class="w-10 h-10 rounded-lg bg-primary-fixed text-primary flex items-center justify-center mb-3"><i class="ri-notification-3-line text-xl"></i></div>
                <h3 class="text-title-md text-on-surface">Alertas oportunas</h3>
                <p class="mt-1 text-body-md text-on-surface-variant">Avisos de renovación de licencias, garantías por vencer y stock bajo, en el portal y por correo.</p>
            </div>
        </div>
    </section>

    {{-- Módulos --}}
    <section id="modulos" class="max-w-6xl mx-auto px-4 pb-16">
        <h2 class="text-headline-sm text-on-surface text-center mb-8">Módulos de la herramienta</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
            @foreach ($modules as $m)
                <div class="card p-5 hover:border-primary-container transition-colors">
                    <div class="w-11 h-11 rounded-lg bg-primary-fixed text-primary flex items-center justify-center mb-3">
                        <i class="{{ $m['icon'] }} text-2xl"></i>
                    </div>
                    <h3 class="text-title-md text-on-surface">{{ $m['title'] }}</h3>
                    <p class="mt-1 text-body-sm text-on-surface-variant">{{ $m['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA final --}}
    <section class="bg-primary-container">
        <div class="max-w-6xl mx-auto px-4 py-12 text-center">
            <h2 class="text-headline-sm text-white">¿Listo para gestionar tu inventario?</h2>
            <p class="mt-2 text-white/80 text-body-md">Ingresa con tu cuenta para comenzar.</p>
            <a href="{{ $accessUrl }}" class="mt-6 inline-flex items-center gap-2 px-6 py-3 bg-white text-primary-container rounded-lg text-label-md hover:opacity-90 transition-opacity">
                <i class="ri-login-box-line"></i> {{ $accessLabel }}
            </a>
        </div>
    </section>

    <footer class="bg-white border-t border-border-soft">
        <div class="max-w-6xl mx-auto px-4 py-6 text-center text-body-sm text-on-surface-variant">
            © {{ date('Y') }} {{ $companyName }} · Inventario TI
        </div>
    </footer>
</body>
</html>
