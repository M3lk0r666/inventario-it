<?php

namespace App\Livewire\Admin\Assignments;

use App\Models\Assignment;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class AssignmentsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('assigned_at', 'desc')
            ->setSearchDebounce(400)
            ->setPerPageAccepted([10, 25, 50])
            // Con columnas relacionales, rappasoft no selecciona las FKs del
            // modelo base; se agregan explícitamente para armar los enlaces.
            ->setAdditionalSelects(['assignments.asset_id', 'assignments.responsive_letter_id'])
            ->setEmptyMessage('Sin asignaciones registradas.');
    }

    public function builder(): Builder
    {
        return Assignment::query()->with(['asset', 'employee', 'responsiveLetter', 'assignedBy']);
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Estado', 'estado')
                ->options(['' => 'Todas', 'active' => 'Activas', 'returned' => 'Devueltas'])
                ->filter(fn (Builder $b, string $value) => $value === 'active'
                    ? $b->whereNull('returned_at')
                    : $b->whereNotNull('returned_at')),

            SelectFilter::make('Empleado', 'empleado')
                ->options(['' => 'Todos'] + Employee::orderBy('name')->pluck('name', 'id')->all())
                ->filter(fn (Builder $b, string $value) => $b->where('assignments.employee_id', $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Etiqueta', 'asset.asset_tag')
                ->sortable()->searchable()
                ->format(fn ($value, $row) => '<a href="'.route('admin.assets.show', $row->asset_id).'" class="text-mono-sm font-mono text-primary hover:underline">'.e($value).'</a>')
                ->html(),

            Column::make('Activo', 'asset.name')
                ->searchable(),

            Column::make('Empleado', 'employee.name')
                ->sortable()->searchable(),

            Column::make('Entrega', 'assigned_at')
                ->sortable()
                ->format(fn ($value) => $value?->format('d/m/Y')),

            Column::make('Devolución', 'returned_at')
                ->sortable()
                ->format(fn ($value) => $value?->format('d/m/Y') ?? '—'),

            Column::make('Situación', 'id')
                ->format(fn ($value, $row) => $row->returned_at
                    ? '<span class="chip-neutral">Devuelta</span>'
                    : '<span class="chip-success">Activa</span>')
                ->html(),

            Column::make('Carta', 'responsiveLetter.folio')
                ->searchable()
                ->format(fn ($value, $row) => $value
                    ? '<a href="'.route('admin.letters.pdf', $row->responsive_letter_id).'" target="_blank" class="text-mono-sm font-mono text-primary hover:underline">'.e($value).'</a>'
                    : '—')
                ->html(),

            Column::make('Acciones', 'id')
                ->format(fn ($value, $row) => view('admin.assignments.partials.actions', [
                    'id' => $value,
                    'isActive' => $row->returned_at === null,
                ]))
                ->html(),
        ];
    }

    #[On('assignment-saved')]
    public function refreshAfterSave(): void
    {
        // Re-render al asignar/devolver.
    }
}
