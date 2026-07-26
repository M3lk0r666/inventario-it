<?php

namespace App\Livewire\Admin\Employees;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class EmployeesTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('name', 'asc')
            ->setSearchDebounce(400)
            ->setPerPageAccepted([10, 25, 50])
            ->setEmptyMessage('Sin empleados registrados.');
    }

    public function builder(): Builder
    {
        return Employee::query()->with(['department', 'location'])
            ->withCount(['activeAssignments']);
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Departamento', 'departamento')
                ->options(['' => 'Todos'] + Department::orderBy('name')->pluck('name', 'id')->all())
                ->filter(fn (Builder $b, string $value) => $b->where('department_id', $value)),

            SelectFilter::make('Ubicación', 'ubicacion')
                ->options(['' => 'Todas'] + Location::orderBy('name')->pluck('name', 'id')->all())
                ->filter(fn (Builder $b, string $value) => $b->where('location_id', $value)),

            SelectFilter::make('Estado', 'estado')
                ->options(['' => 'Todos', 'active' => 'Activo', 'inactive' => 'Inactivo'])
                ->filter(fn (Builder $b, string $value) => $b->where('status', $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('No.', 'employee_number')
                ->sortable()->searchable()
                ->format(fn ($value, $row) => '<a href="'.route('admin.employees.show', $row->id).'" class="text-mono-sm font-mono text-primary hover:underline">'.e($value).'</a>')
                ->html(),

            Column::make('Nombre', 'name')
                ->sortable()->searchable()
                ->format(fn ($value, $row) => '<a href="'.route('admin.employees.show', $row->id).'" class="text-title-md text-on-surface hover:text-primary hover:underline">'.e($value).'</a>')
                ->html(),

            Column::make('Puesto', 'position')
                ->searchable()
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Departamento', 'department.name')
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Ubicación', 'location.name')
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Bienes')
                ->label(fn ($row) => '<span class="chip-neutral">'.$row->active_assignments_count.'</span>')
                ->html(),

            Column::make('Estado', 'status')
                ->format(fn ($value) => $value === 'active'
                    ? '<span class="chip-success">Activo</span>'
                    : '<span class="chip-neutral">Inactivo</span>')
                ->html(),

            Column::make('Acciones', 'id')
                ->format(fn ($value, $row) => view('admin.employees.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    #[On('employee-saved')]
    public function refreshAfterSave(): void
    {
        // Re-render.
    }
}
