<?php

namespace App\Livewire\Admin\Licenses;

use App\Models\License;
use App\Models\LicenseType;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class LicensesTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('software_name', 'asc')
            ->setSearchDebounce(400)
            ->setPerPageAccepted([10, 25, 50])
            ->setAdditionalSelects(['licenses.seats', 'licenses.expires_at', 'licenses.renewal_date', 'licenses.alerts_enabled', 'licenses.alert_days_before'])
            ->setEmptyMessage('Sin licencias registradas.');
    }

    public function builder(): Builder
    {
        return License::query()->with(['type', 'supplier'])->withCount([
            'assignments as used_seats' => fn ($q) => $q->whereNull('released_at'),
        ]);
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Tipo', 'tipo')
                ->options(['' => 'Todos'] + LicenseType::orderBy('name')->pluck('name', 'id')->all())
                ->filter(fn (Builder $b, string $value) => $b->where('license_type_id', $value)),

            SelectFilter::make('Vigencia', 'vigencia')
                ->options(['' => 'Todas', 'expiring' => 'Por vencer (60 días)', 'expired' => 'Vencidas', 'perpetual' => 'Perpetuas'])
                ->filter(fn (Builder $b, string $value) => match ($value) {
                    'expiring' => $b->whereNotNull('expires_at')->whereBetween('expires_at', [now(), now()->addDays(60)]),
                    'expired' => $b->whereNotNull('expires_at')->where('expires_at', '<', now()),
                    'perpetual' => $b->whereNull('expires_at'),
                    default => $b,
                }),

            SelectFilter::make('Renovación', 'renovacion')
                ->options(['' => 'Todas', 'alert' => 'Con alerta activa'])
                ->filter(fn (Builder $b, string $value) => $value === 'alert' ? $b->needingRenewal() : $b),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Software', 'software_name')
                ->sortable()->searchable()
                ->format(fn ($value, $row) => '<a href="'.route('admin.licenses.show', $row->id).'" class="text-title-md text-on-surface hover:text-primary hover:underline">'.e($value).'</a>'
                    .($row->version ? '<span class="text-body-sm text-on-surface-variant ms-1">'.e($row->version).'</span>' : ''))
                ->html(),

            Column::make('Tipo', 'type.name')
                ->sortable()
                ->format(fn ($value) => e($value ?? '—')),

            Column::make('Expiración', 'expires_at')
                ->sortable()
                ->format(function ($value, $row) {
                    if (! $row->expires_at) {
                        return '<span class="text-body-sm text-on-surface-variant">Perpetua</span>';
                    }
                    $d = $row->expires_at;
                    if ($d->isPast()) {
                        return '<span class="chip-alert">'.$d->format('d/m/Y').'</span>';
                    }
                    if ($d->lte(now()->addDays(60))) {
                        return '<span class="chip-warning">'.$d->format('d/m/Y').'</span>';
                    }

                    return '<span class="text-body-md">'.$d->format('d/m/Y').'</span>';
                })
                ->html(),

            Column::make('Renovación', 'renewal_date')
                ->sortable()
                ->format(function ($value, $row) {
                    if (! $row->renewal_date) {
                        return '<span class="text-body-sm text-on-surface-variant">—</span>';
                    }
                    $label = $row->renewal_date->format('d/m/Y');
                    return match ($row->renewalStatus()) {
                        'overdue' => '<span class="chip-alert" title="Renovación vencida">'.$label.'</span>',
                        'upcoming' => '<span class="chip-warning" title="Renovación próxima">'.$label.'</span>',
                        'none' => '<span class="text-body-sm text-on-surface-variant" title="Alertas desactivadas">'.$label.'</span>',
                        default => '<span class="text-body-md">'.$label.'</span>',
                    };
                })
                ->html(),

            Column::make('Asientos', 'seats')
                ->format(function ($value, $row) {
                    $used = $row->used_seats;
                    $total = $row->seats;
                    $pct = $total > 0 ? min(100, round($used / $total * 100)) : 0;
                    $barColor = $used >= $total ? 'bg-alert' : ($pct >= 80 ? 'bg-amber-500' : 'bg-primary-container');

                    return '<div class="w-40">'
                        .'<div class="flex justify-between text-body-sm mb-0.5"><span>'.$used.' / '.$total.'</span><span class="text-on-surface-variant">'.$pct.'%</span></div>'
                        .'<div class="w-full h-1.5 bg-surface-container-highest rounded-full overflow-hidden"><div class="h-full '.$barColor.'" style="width:'.$pct.'%"></div></div>'
                        .'</div>';
                })
                ->html(),

            Column::make('Acciones', 'id')
                ->format(fn ($value, $row) => view('admin.licenses.partials.actions', [
                    'id' => $value,
                    'hasSeats' => $row->used_seats < $row->seats,
                ]))
                ->html(),
        ];
    }

    #[On('license-saved')]
    public function refreshAfterSave(): void
    {
        // Re-render tras guardar/asignar.
    }
}
