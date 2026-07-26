<?php

namespace App\Livewire\Admin\Letters;

use App\Models\ResponsiveLetter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class LettersTable extends DataTableComponent
{
    use AuthorizesRequests;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('folio', 'desc')
            ->setSearchDebounce(400)
            ->setPerPageAccepted([10, 25, 50])
            ->setAdditionalSelects(['responsive_letters.status', 'responsive_letters.type', 'responsive_letters.employee_id', 'responsive_letters.signed_document_path'])
            ->setEmptyMessage('Sin cartas responsivas generadas.');
    }

    public function builder(): Builder
    {
        return ResponsiveLetter::query()->with(['employee'])->withCount('assignments');
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Tipo', 'tipo')
                ->options(['' => 'Todos'] + ResponsiveLetter::TYPES)
                ->filter(fn (Builder $b, string $value) => $b->where('type', $value)),

            SelectFilter::make('Estado', 'estado')
                ->options(['' => 'Todas'] + ResponsiveLetter::STATUSES)
                ->filter(fn (Builder $b, string $value) => $b->where('status', $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Folio', 'folio')
                ->sortable()->searchable()
                ->format(fn ($value, $row) => '<a href="'.route('admin.letters.pdf', $row->id).'" target="_blank" class="text-mono-sm font-mono text-primary hover:underline">'.e($value).'</a>')
                ->html(),

            Column::make('Tipo', 'type')
                ->format(fn ($value) => $value === 'return'
                    ? '<span class="chip-warning">Recepción</span>'
                    : '<span class="chip-info">Entrega</span>')
                ->html(),

            Column::make('Empleado', 'employee.name')
                ->sortable()->searchable(),

            Column::make('Emisión', 'issued_at')
                ->sortable()
                ->format(fn ($value) => $value?->format('d/m/Y')),

            Column::make('Bienes', 'id')
                ->format(fn ($value, $row) => '<span class="chip-neutral">'.$row->assignments_count.'</span>')
                ->html(),

            Column::make('Estado', 'status')
                ->format(function ($value) {
                    $chip = match ($value) {
                        'signed' => 'chip-success',
                        'cancelled' => 'chip-alert',
                        default => 'chip-info',
                    };

                    return '<span class="'.$chip.'">'.e(ResponsiveLetter::STATUSES[$value] ?? $value).'</span>';
                })
                ->html(),

            Column::make('Acciones', 'id')
                ->format(fn ($value, $row) => view('admin.letters.partials.actions', [
                    'id' => $value,
                    'status' => $row->status,
                    'signed' => filled($row->signed_document_path),
                ]))
                ->html(),
        ];
    }

    #[On('assignment-saved')]
    #[On('letters-updated')]
    public function refreshAfterSave(): void
    {
        // Re-render al generar/firmar/anular cartas.
    }
}
