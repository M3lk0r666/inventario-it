@if (count($breadcrumbs))
    <nav class="mb-4" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center">
            <li class="flex items-center">
                <a href="{{ route('admin.dashboard') }}" class="text-body-sm text-on-surface-variant hover:text-primary">
                    <i class="ri-home-4-line"></i>
                </a>
            </li>
            @foreach ($breadcrumbs as $item)
                <li class="flex items-center">
                    <i class="ri-arrow-right-s-line text-outline mx-1"></i>
                    @isset($item['href'])
                        <a href="{{ $item['href'] }}" class="text-body-sm font-medium text-on-surface-variant hover:text-primary">
                            {{ $item['name'] }}
                        </a>
                    @else
                        <span class="text-body-sm font-medium text-on-surface">{{ $item['name'] }}</span>
                    @endisset
                </li>
            @endforeach
        </ol>
    </nav>
@endif
