<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Consumable;
use App\Models\License;
use Illuminate\Support\Collection;

/**
 * Motor de alertas del sistema: renovaciones/expiración de licencias,
 * garantías por vencer y stock bajo. Alimenta el dashboard y el digest por correo.
 */
class AlertService
{
    public function __construct(
        public int $warrantyDays = 60,
        public int $licenseDays = 60,
    ) {}

    /** Licencias con alerta de renovación activa (según su configuración). */
    public function licenseRenewals(): Collection
    {
        return License::with('type')->needingRenewal()->orderBy('renewal_date')->get();
    }

    /** Licencias que expiran pronto (por vencer). */
    public function licensesExpiring(): Collection
    {
        return License::whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($this->licenseDays)])
            ->orderBy('expires_at')->get();
    }

    /** Licencias ya vencidas. */
    public function licensesExpired(): Collection
    {
        return License::whereNotNull('expires_at')->where('expires_at', '<', now())
            ->orderBy('expires_at')->get();
    }

    /** Garantías por vencer (activos no dados de baja). */
    public function warrantiesExpiring(): Collection
    {
        return Asset::with(['type', 'location'])
            ->whereNotNull('warranty_expires_at')
            ->whereBetween('warranty_expires_at', [now(), now()->addDays($this->warrantyDays)])
            ->whereHas('status', fn ($q) => $q->where('slug', '!=', 'baja'))
            ->orderBy('warranty_expires_at')->get();
    }

    /** Consumibles con stock bajo (≤ mínimo). */
    public function lowStock(): Collection
    {
        return Consumable::with('location')
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('name')->get();
    }

    /** Resumen de conteos para tarjetas/badges. */
    public function summary(): array
    {
        return [
            'license_renewals' => License::needingRenewal()->count(),
            'licenses_expiring' => License::whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays($this->licenseDays)])->count(),
            'warranties_expiring' => Asset::whereNotNull('warranty_expires_at')
                ->whereBetween('warranty_expires_at', [now(), now()->addDays($this->warrantyDays)])
                ->whereHas('status', fn ($q) => $q->where('slug', '!=', 'baja'))->count(),
            'low_stock' => Consumable::whereColumn('stock', '<=', 'min_stock')->count(),
        ];
    }

    public function totalAlerts(): int
    {
        return array_sum($this->summary());
    }
}
