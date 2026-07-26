<?php

namespace App\Livewire\Admin\Consumables;

use App\Models\Consumable;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ConsumablesTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('name', 'asc')
            ->setSearchDebounce(400)
            ->setPerPageAccepted([10, 25, 50])
            ->setAdditionalSelects(['consumables.stock', 'consumables.min_stock'])
            ->setEmptyMessage('Sin consumibles registrados.');
    }

    public function builder(): Builder
    {
        return Consumable::query()->with(['location', 'supplier']);
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Ubicación', 'ubicacion')
                ->options(['' => 'Todas'] + Location::orderBy('name')->pluck('name', 'id')->all())
                ->filter(fn (Builder $b, string $value) => $b->where('location_id', $value)),

            SelectFilter::make('Existencia', 'existencia')
                ->options(['' => 'Todas', 'low' => 'Stock bajo', 'ok' => 'Suficiente'])
                ->filter(fn (Builder $b, string $value) => $value === 'low'
                    ? $b->whereColumn('stock', '<=', 'min_stock')
                    : $b->whereColumn('stock', '>', 'min_stock')),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'name')
                ->sortable()->searchable()
                ->format(fn ($value, $row) => '<a href="'.route('admin.consumables.show', $row->id).'" class="text-title-md text-on-surface hover:text-primary hover:underline">'.e($value).'</a>')
                ->html(),

            Column::make('Ubicación', 'location.name')
                ->sortable()->searchable()
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Proveedor', 'supplier.name')
                ->searchable()
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Unidad', 'unit')
                ->format(fn ($value) => e($value)),

            Column::make('Existencia', 'stock')
                ->sortable()
                ->format(function ($value, $row) {
                    $low = $row->stock <= $row->min_stock;
                    $chip = $low ? 'chip-alert' : 'chip-success';

                    return '<span class="'.$chip.'">'.(int) $value.'</span>'
                        .'<span class="text-body-sm text-on-surface-variant ms-1">/ mín '.(int) $row->min_stock.'</span>';
                })
                ->html(),

            Column::make('Acciones', 'id')
                ->format(fn ($value, $row) => view('admin.consumables.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    #[On('consumable-saved')]
    public function refreshAfterSave(): void
    {
        // Re-render tras guardar/mover.
    }
}
