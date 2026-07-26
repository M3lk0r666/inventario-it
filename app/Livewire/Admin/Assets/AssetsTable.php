<?php

namespace App\Livewire\Admin\Assets;

use App\Models\Asset;
use App\Models\AssetStatus;
use App\Models\AssetType;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetsTable extends DataTableComponent
{
    use AuthorizesRequests;

    /** Mapa color de estado → clase de chip del design system. */
    public const CHIP_BY_COLOR = [
        'green' => 'chip-success',
        'red' => 'chip-alert',
        'yellow' => 'chip-warning',
        'blue' => 'chip-info',
        'indigo' => 'chip-info',
        'gray' => 'chip-neutral',
    ];

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('asset_tag', 'asc')
            ->setSearchDebounce(400)
            ->setPerPageAccepted([10, 25, 50])
            ->setEmptyMessage('Sin activos que coincidan con los filtros.');
    }

    public function builder(): Builder
    {
        return Asset::query()->with([
            'type', 'model.manufacturer', 'status', 'location', 'currentAssignment.employee',
        ]);
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Tipo', 'tipo')
                ->options(['' => 'Todos'] + AssetType::orderBy('name')->pluck('name', 'id')->all())
                ->filter(fn (Builder $b, string $value) => $b->where('asset_type_id', $value)),

            SelectFilter::make('Estado', 'estado')
                ->options(['' => 'Todos'] + AssetStatus::orderBy('name')->pluck('name', 'id')->all())
                ->filter(fn (Builder $b, string $value) => $b->where('asset_status_id', $value)),

            SelectFilter::make('Ubicación', 'ubicacion')
                ->options(['' => 'Todas'] + Location::orderBy('name')->pluck('name', 'id')->all())
                ->filter(fn (Builder $b, string $value) => $b->where('location_id', $value)),

            SelectFilter::make('Asignación', 'asignacion')
                ->options([
                    '' => 'Todos',
                    'assigned' => 'Asignados',
                    'available' => 'Sin asignar',
                ])
                ->filter(fn (Builder $b, string $value) => $value === 'assigned'
                    ? $b->whereHas('assignments', fn (Builder $q) => $q->whereNull('returned_at'))
                    : $b->whereDoesntHave('assignments', fn (Builder $q) => $q->whereNull('returned_at'))),

            SelectFilter::make('Asignado a', 'empleado')
                ->options(['' => 'Todos'] + Employee::orderBy('name')->pluck('name', 'id')->all())
                ->filter(fn (Builder $b, string $value) => $b->whereHas('assignments',
                    fn (Builder $q) => $q->whereNull('returned_at')->where('assignments.employee_id', $value))),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Etiqueta', 'asset_tag')
                ->sortable()->searchable()
                ->format(fn ($value, $row) => '<a href="'.route('admin.assets.show', $row->id).'" class="text-mono-sm font-mono text-primary hover:underline">'.e($value).'</a>')
                ->html(),

            Column::make('Nombre', 'name')
                ->sortable()->searchable()
                ->format(fn ($value, $row) => '<span class="text-title-md text-on-surface">'.e($value).'</span>')
                ->html(),

            Column::make('Tipo', 'type.name')
                ->sortable()->searchable(),

            Column::make('Marca', 'model.manufacturer.name')
                ->searchable()
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Modelo', 'model.name')
                ->sortable()->searchable()
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Serie', 'serial_number')
                ->sortable()->searchable()
                ->format(fn ($value) => '<span class="text-mono-sm font-mono">'.e($value ?? '—').'</span>')
                ->html(),

            Column::make('Estado', 'status.name')
                ->searchable()
                ->format(function ($value) {
                    $chip = self::CHIP_BY_COLOR[$this->statusColors()[$value] ?? null] ?? 'chip-neutral';

                    return '<span class="'.$chip.'">'.e($value ?? '—').'</span>';
                })
                ->html(),

            Column::make('Ubicación', 'location.name')
                ->sortable()->searchable()
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Asignado a', 'id')
                ->format(fn ($value, $row) => e($row->currentAssignment?->employee?->name ?? '—')),

            Column::make('Acciones', 'id')
                ->format(fn ($value, $row) => view('admin.assets.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    /** Mapa nombre de estado → color, para pintar el chip desde el valor del JOIN. */
    private ?array $statusColorsCache = null;

    private function statusColors(): array
    {
        return $this->statusColorsCache ??= AssetStatus::pluck('color', 'name')->all();
    }

    #[On('asset-saved')]
    public function refreshAfterSave(): void
    {
        // Re-render al guardar desde el formulario.
    }

    /** Exporta a CSV el listado con los filtros y búsqueda actuales. */
    #[On('export-assets')]
    public function exportCsv(): StreamedResponse
    {
        $this->authorize('assets.export');

        $query = $this->builder();

        if (filled($search = $this->getSearch())) {
            $query->where(fn (Builder $q) => $q
                ->where('asset_tag', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('serial_number', 'like', "%{$search}%"));
        }

        foreach ($this->getAppliedFiltersWithValues() as $key => $value) {
            if (blank($value)) {
                continue;
            }
            match ($key) {
                'tipo' => $query->where('asset_type_id', $value),
                'estado' => $query->where('asset_status_id', $value),
                'ubicacion' => $query->where('location_id', $value),
                default => null,
            };
        }

        $filename = 'activos_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
            fputcsv($out, ['Etiqueta', 'Nombre', 'Tipo', 'Fabricante', 'Modelo', 'Serie', 'Estado', 'Ubicación', 'Asignado a', 'Fecha compra', 'Costo', 'Garantía hasta']);

            $query->orderBy('asset_tag')->chunk(200, function ($assets) use ($out) {
                foreach ($assets as $asset) {
                    fputcsv($out, [
                        $asset->asset_tag,
                        $asset->name,
                        $asset->type?->name,
                        $asset->model?->manufacturer?->name,
                        $asset->model?->name,
                        $asset->serial_number,
                        $asset->status?->name,
                        $asset->location?->name,
                        $asset->currentAssignment?->employee?->name,
                        $asset->purchase_date?->format('Y-m-d'),
                        $asset->purchase_cost,
                        $asset->warranty_expires_at?->format('Y-m-d'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
