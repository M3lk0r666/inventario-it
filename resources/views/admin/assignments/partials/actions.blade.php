<div class="flex items-center justify-end gap-1">
    @if ($isActive)
        @can('assignments.edit')
            <button type="button" onclick="Livewire.dispatch('open-return-form', { id: {{ $id }} })"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-label-md text-primary-container border border-primary-container/40 rounded-lg hover:bg-primary-fixed/40 transition-colors">
                <i class="ri-arrow-go-back-line"></i> Devolución
            </button>
        @endcan
    @else
        <span class="text-body-sm text-outline">—</span>
    @endif
</div>
