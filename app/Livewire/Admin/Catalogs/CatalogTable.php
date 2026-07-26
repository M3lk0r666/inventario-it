<?php

namespace App\Livewire\Admin\Catalogs;

use App\Support\CatalogRegistry;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * Tabla genérica de catálogos (rappasoft/laravel-livewire-tables).
 * Se configura con la clave del catálogo definida en CatalogRegistry.
 */
class CatalogTable extends DataTableComponent
{
    public string $catalog;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('name', 'asc')
            ->setSearchDebounce(400)
            ->setPerPageAccepted([10, 25, 50])
            ->setEmptyMessage('Sin registros. Crea el primero con el botón "Nuevo".');
    }

    public function builder(): Builder
    {
        $def = CatalogRegistry::get($this->catalog);

        return $def['model']::query()->with($def['with']);
    }

    public function columns(): array
    {
        $def = CatalogRegistry::get($this->catalog);
        $columns = [];

        foreach ($def['columns'] as $col) {
            $isRelation = str_contains($col['field'], '.');
            $column = Column::make($col['label'], $col['field']);

            if (! $isRelation) {
                $column->sortable()->searchable();
            }

            $column = match ($col['format'] ?? null) {
                'bool' => $column->format(fn ($value) => $value
                    ? '<span class="chip-success">Sí</span>'
                    : '<span class="chip-neutral">No</span>')->html(),
                'badge' => $column->format(fn ($value) => $value
                    ? '<span class="chip bg-'.e($value).'-100 text-'.e($value).'-800">'.e($value).'</span>'
                    : '')->html(),
                'icon' => $column->format(fn ($value) => $value
                    ? '<i class="'.e($value).' text-lg"></i> <span class="text-body-sm text-on-surface-variant">'.e($value).'</span>'
                    : '')->html(),
                default => $column,
            };

            $columns[] = $column;
        }

        $columns[] = Column::make('Acciones', 'id')
            ->format(fn ($value, $row) => view('admin.catalogs.partials.actions', [
                'id' => $value,
                'catalog' => $this->catalog,
            ]))
            ->html();

        return $columns;
    }

    #[On('catalog-saved')]
    public function refreshAfterSave(): void
    {
        // El evento fuerza el re-render de la tabla.
    }
}
