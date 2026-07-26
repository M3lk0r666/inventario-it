<?php

namespace App\Livewire\Admin\Suppliers;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class SuppliersTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('name', 'asc')
            ->setSearchDebounce(400)
            ->setPerPageAccepted([10, 25, 50])
            ->setEmptyMessage('Sin proveedores registrados.');
    }

    public function builder(): Builder
    {
        return Supplier::query()->withCount(['assets', 'licenses']);
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'name')
                ->sortable()->searchable()
                ->format(fn ($value, $row) => '<a href="'.route('admin.suppliers.show', $row->id).'" class="text-title-md text-on-surface hover:text-primary hover:underline">'.e($value).'</a>')
                ->html(),

            Column::make('RFC', 'rfc')
                ->searchable()
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Contacto', 'contact_name')
                ->searchable()
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Teléfono', 'phone')
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Correo', 'email')
                ->searchable()
                ->format(fn ($value) => $value ? '<a href="mailto:'.e($value).'" class="text-primary hover:underline">'.e($value).'</a>' : '—')
                ->html(),

            Column::make('Activos')
                ->label(fn ($row) => '<span class="chip-neutral">'.(int) $row->assets_count.'</span>')
                ->html(),

            Column::make('Acciones', 'id')
                ->format(fn ($value, $row) => view('admin.suppliers.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    #[On('supplier-saved')]
    public function refreshAfterSave(): void
    {
        // Re-render tras guardar.
    }
}
