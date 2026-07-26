<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Assignment;
use App\Models\Consumable;
use App\Models\License;
use App\Models\Problem;
use Illuminate\Support\Collection;

/**
 * Definición y ejecución de reportes filtrables. Cada reporte declara sus
 * columnas, filtros disponibles y cómo obtener las filas.
 */
class ReportService
{
    /** @return array<string,array> */
    public function reports(): array
    {
        return [
            'inventory' => [
                'label' => 'Inventario general',
                'description' => 'Todos los activos con su estado, ubicación y asignación.',
                'icon' => 'ri-computer-line',
                'filters' => ['type', 'status', 'location'],
                'columns' => ['Etiqueta', 'Nombre', 'Tipo', 'Marca/Modelo', 'Serie', 'Estado', 'Ubicación', 'Asignado a', 'Costo', 'Garantía'],
            ],
            'by-employee' => [
                'label' => 'Activos por empleado',
                'description' => 'Bienes actualmente asignados, por colaborador.',
                'icon' => 'ri-team-line',
                'filters' => ['employee'],
                'columns' => ['Empleado', 'Etiqueta', 'Activo', 'Tipo', 'Serie', 'Fecha entrega', 'Carta'],
            ],
            'assignment-history' => [
                'label' => 'Histórico de asignaciones',
                'description' => 'Entregas y devoluciones en un rango de fechas.',
                'icon' => 'ri-history-line',
                'filters' => ['employee', 'dates'],
                'columns' => ['Etiqueta', 'Activo', 'Empleado', 'Entrega', 'Devolución', 'Situación', 'Carta'],
            ],
            'repair-costs' => [
                'label' => 'Costos de reparación',
                'description' => 'Problemas con costo, por activo.',
                'icon' => 'ri-money-dollar-circle-line',
                'filters' => ['problem_status', 'dates'],
                'columns' => ['Etiqueta', 'Activo', 'Problema', 'Categoría', 'Estado', 'Costo', 'Reporte'],
            ],
            'licenses' => [
                'label' => 'Licencias',
                'description' => 'Software, asientos, expiración y renovación.',
                'icon' => 'ri-key-2-line',
                'filters' => [],
                'columns' => ['Software', 'Versión', 'Tipo', 'Asientos', 'En uso', 'Expiración', 'Renovación'],
            ],
            'consumables' => [
                'label' => 'Consumibles',
                'description' => 'Existencias y mínimos.',
                'icon' => 'ri-archive-line',
                'filters' => ['location'],
                'columns' => ['Nombre', 'Existencia', 'Mínimo', 'Unidad', 'Ubicación', 'Proveedor', 'Estado'],
            ],
        ];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->reports());
    }

    public function get(string $key): array
    {
        abort_unless($this->has($key), 404);

        return $this->reports()[$key];
    }

    /** Devuelve las filas (array de arrays alineados a 'columns') según filtros. */
    public function rows(string $key, array $filters = []): Collection
    {
        return match ($key) {
            'inventory' => $this->inventory($filters),
            'by-employee' => $this->byEmployee($filters),
            'assignment-history' => $this->assignmentHistory($filters),
            'repair-costs' => $this->repairCosts($filters),
            'licenses' => $this->licenses(),
            'consumables' => $this->consumables($filters),
            default => collect(),
        };
    }

    protected function inventory(array $f): Collection
    {
        return Asset::with(['type', 'model.manufacturer', 'status', 'location', 'currentAssignment.employee'])
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('asset_type_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('asset_status_id', $v))
            ->when($f['location'] ?? null, fn ($q, $v) => $q->where('location_id', $v))
            ->orderBy('asset_tag')->get()
            ->map(fn ($a) => [
                $a->asset_tag, $a->name, $a->type?->name,
                trim(($a->model?->manufacturer?->name ?? '').' '.($a->model?->name ?? '')),
                $a->serial_number, $a->status?->name, $a->location?->name,
                $a->currentAssignment?->employee?->name ?? '',
                $a->purchase_cost !== null ? number_format((float) $a->purchase_cost, 2) : '',
                $a->warranty_expires_at?->format('d/m/Y') ?? '',
            ]);
    }

    protected function byEmployee(array $f): Collection
    {
        return Assignment::with(['asset.type', 'employee', 'responsiveLetter'])
            ->whereNull('returned_at')
            ->when($f['employee'] ?? null, fn ($q, $v) => $q->where('employee_id', $v))
            ->get()
            ->sortBy(fn ($a) => $a->employee?->name)
            ->map(fn ($a) => [
                $a->employee?->name, $a->asset?->asset_tag, $a->asset?->name,
                $a->asset?->type?->name, $a->asset?->serial_number,
                $a->assigned_at?->format('d/m/Y'), $a->responsiveLetter?->folio ?? '',
            ])->values();
    }

    protected function assignmentHistory(array $f): Collection
    {
        return Assignment::with(['asset', 'employee', 'responsiveLetter'])
            ->when($f['employee'] ?? null, fn ($q, $v) => $q->where('employee_id', $v))
            ->when($f['date_from'] ?? null, fn ($q, $v) => $q->whereDate('assigned_at', '>=', $v))
            ->when($f['date_to'] ?? null, fn ($q, $v) => $q->whereDate('assigned_at', '<=', $v))
            ->orderByDesc('assigned_at')->get()
            ->map(fn ($a) => [
                $a->asset?->asset_tag, $a->asset?->name, $a->employee?->name,
                $a->assigned_at?->format('d/m/Y'), $a->returned_at?->format('d/m/Y') ?? '',
                $a->returned_at ? 'Devuelta' : 'Activa', $a->responsiveLetter?->folio ?? '',
            ]);
    }

    protected function repairCosts(array $f): Collection
    {
        return Problem::with(['asset', 'category'])
            ->whereNotNull('cost')
            ->when($f['problem_status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['date_from'] ?? null, fn ($q, $v) => $q->whereDate('reported_at', '>=', $v))
            ->when($f['date_to'] ?? null, fn ($q, $v) => $q->whereDate('reported_at', '<=', $v))
            ->orderByDesc('reported_at')->get()
            ->map(fn ($p) => [
                $p->asset?->asset_tag, $p->asset?->name, $p->title, $p->category?->name,
                Problem::STATUSES[$p->status] ?? $p->status,
                number_format((float) $p->cost, 2), $p->reported_at?->format('d/m/Y'),
            ]);
    }

    protected function licenses(): Collection
    {
        return License::with('type')->withCount(['assignments as used' => fn ($q) => $q->whereNull('released_at')])
            ->orderBy('software_name')->get()
            ->map(fn ($l) => [
                $l->software_name, $l->version, $l->type?->name, $l->seats, $l->used,
                $l->expires_at?->format('d/m/Y') ?? 'Perpetua',
                $l->renewal_date?->format('d/m/Y') ?? '',
            ]);
    }

    protected function consumables(array $f): Collection
    {
        return Consumable::with(['location', 'supplier'])
            ->when($f['location'] ?? null, fn ($q, $v) => $q->where('location_id', $v))
            ->orderBy('name')->get()
            ->map(fn ($c) => [
                $c->name, $c->stock, $c->min_stock, $c->unit,
                $c->location?->name ?? '', $c->supplier?->name ?? '',
                $c->stock <= $c->min_stock ? 'Stock bajo' : 'Suficiente',
            ]);
    }
}
