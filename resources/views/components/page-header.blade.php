@props(['title', 'description' => null])

{{-- Encabezado estándar de página: título + descripción + acciones a la derecha --}}
<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-start justify-between gap-3']) }}>
    <div>
        <h2 class="text-headline-md text-on-surface">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 text-body-md text-on-surface-variant">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
    @endisset
</div>
