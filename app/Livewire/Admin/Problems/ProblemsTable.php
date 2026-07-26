<?php

namespace App\Livewire\Admin\Problems;

use App\Models\Problem;
use App\Models\ProblemCategory;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ProblemsTable extends DataTableComponent
{
    public const STATUS_CHIP = [
        'new' => 'chip-info',
        'in_progress' => 'chip-warning',
        'resolved' => 'chip-success',
        'closed' => 'chip-neutral',
    ];

    public const PRIORITY_CHIP = [
        'low' => 'chip-neutral',
        'medium' => 'chip-info',
        'high' => 'chip-warning',
        'critical' => 'chip-alert',
    ];

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('reported_at', 'desc')
            ->setSearchDebounce(400)
            ->setPerPageAccepted([10, 25, 50])
            ->setAdditionalSelects(['problems.asset_id', 'problems.status', 'problems.priority'])
            ->setEmptyMessage('Sin problemas registrados.');
    }

    public function builder(): Builder
    {
        return Problem::query()->with(['asset', 'category', 'assignedTo']);
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Estado', 'estado')
                ->options(['' => 'Todos'] + Problem::STATUSES)
                ->filter(fn (Builder $b, string $value) => $b->where('status', $value)),

            SelectFilter::make('Prioridad', 'prioridad')
                ->options(['' => 'Todas'] + Problem::PRIORITIES)
                ->filter(fn (Builder $b, string $value) => $b->where('priority', $value)),

            SelectFilter::make('Categoría', 'categoria')
                ->options(['' => 'Todas'] + ProblemCategory::orderBy('name')->pluck('name', 'id')->all())
                ->filter(fn (Builder $b, string $value) => $b->where('problem_category_id', $value)),

            SelectFilter::make('Abiertos', 'abiertos')
                ->options(['' => 'Todos', 'open' => 'Solo abiertos', 'done' => 'Resueltos/Cerrados'])
                ->filter(fn (Builder $b, string $value) => $value === 'open'
                    ? $b->whereIn('status', ['new', 'in_progress'])
                    : $b->whereIn('status', ['resolved', 'closed'])),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Título', 'title')
                ->sortable()->searchable()
                ->format(fn ($value, $row) => '<a href="'.route('admin.problems.show', $row->id).'" class="text-title-md text-on-surface hover:text-primary hover:underline">'.e($value).'</a>')
                ->html(),

            Column::make('Activo', 'asset.asset_tag')
                ->sortable()->searchable()
                ->format(fn ($value, $row) => $row->asset
                    ? '<a href="'.route('admin.assets.show', $row->asset_id).'" class="text-mono-sm font-mono text-primary hover:underline">'.e($value).'</a>'
                    : '—')
                ->html(),

            Column::make('Categoría', 'category.name')
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Prioridad', 'priority')
                ->sortable()
                ->format(fn ($value) => '<span class="'.(self::PRIORITY_CHIP[$value] ?? 'chip-neutral').'">'.e(Problem::PRIORITIES[$value] ?? $value).'</span>')
                ->html(),

            Column::make('Estado', 'status')
                ->sortable()
                ->format(fn ($value) => '<span class="'.(self::STATUS_CHIP[$value] ?? 'chip-neutral').'">'.e(Problem::STATUSES[$value] ?? $value).'</span>')
                ->html(),

            Column::make('Costo', 'cost')
                ->sortable()
                ->format(fn ($value) => $value !== null ? '$'.number_format((float) $value, 2) : '—'),

            Column::make('Reporte', 'reported_at')
                ->sortable()
                ->format(fn ($value) => $value?->format('d/m/Y')),

            Column::make('Acciones', 'id')
                ->format(fn ($value, $row) => view('admin.problems.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    #[On('problem-saved')]
    public function refreshAfterSave(): void
    {
        // Re-render tras guardar.
    }
}
