<?php

namespace App\Livewire\Admin\Licenses;

use App\Models\Asset;
use App\Models\Employee;
use App\Models\License;
use App\Models\LicenseAssignment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Detalle de licencia: asientos usados/disponibles y asignación a
 * equipos o empleados. No permite exceder los asientos contratados.
 */
class LicenseDetail extends Component
{
    use AuthorizesRequests;

    public int $licenseId;

    // Asignación
    public bool $assigning = false;

    public string $target = 'asset'; // asset | employee

    public ?int $targetId = null;

    public string $assignNotes = '';

    // Renovación
    public bool $renewing = false;

    public ?string $newRenewalDate = null;

    public ?string $newExpiresAt = null;

    public function mount(int $licenseId): void
    {
        $this->licenseId = $licenseId;
    }

    public function getLicenseProperty(): License
    {
        return License::with(['type', 'supplier'])->findOrFail($this->licenseId);
    }

    #[On('open-license-assign')]
    public function openAssign(int $id): void
    {
        if ($id !== $this->licenseId) {
            return;
        }
        $this->authorize('licenses.assign');
        $this->reset('targetId', 'assignNotes');
        $this->resetValidation();
        $this->target = 'asset';
        $this->assigning = true;
    }

    public function saveAssign(): void
    {
        $this->authorize('licenses.assign');

        $this->validate([
            'target' => ['required', 'in:asset,employee'],
            'targetId' => ['required', 'integer'],
            'assignNotes' => ['nullable', 'string', 'max:500'],
        ], [], ['targetId' => 'destinatario']);

        $type = $this->target === 'asset' ? Asset::class : Employee::class;
        abort_unless($type::whereKey($this->targetId)->exists(), 422);

        $ok = DB::transaction(function () use ($type) {
            $license = License::lockForUpdate()->findOrFail($this->licenseId);
            $used = $license->activeAssignments()->count();

            if ($used >= $license->seats) {
                return false;
            }

            // Evitar duplicar el mismo destinatario activo
            $exists = LicenseAssignment::where('license_id', $license->id)
                ->where('assignable_type', $type)
                ->where('assignable_id', $this->targetId)
                ->whereNull('released_at')
                ->exists();

            if ($exists) {
                return 'dup';
            }

            LicenseAssignment::create([
                'license_id' => $license->id,
                'assignable_type' => $type,
                'assignable_id' => $this->targetId,
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
                'notes' => $this->assignNotes ?: null,
            ]);

            return true;
        });

        if ($ok === false) {
            $this->dispatch('toast', type: 'error', message: 'No hay asientos disponibles.');

            return;
        }
        if ($ok === 'dup') {
            $this->addError('targetId', 'Ese destinatario ya tiene un asiento activo de esta licencia.');

            return;
        }

        $this->assigning = false;
        $this->dispatch('license-saved');
        $this->dispatch('toast', type: 'success', message: 'Asiento asignado.');
    }

    public function openRenew(): void
    {
        $this->authorize('licenses.edit');
        $license = $this->license;
        // Sugerir un año adelante desde la renovación actual (o hoy).
        $base = $license->renewal_date ?? now();
        $this->newRenewalDate = $base->copy()->addYear()->format('Y-m-d');
        $this->newExpiresAt = $license->expires_at?->copy()->addYear()->format('Y-m-d');
        $this->resetValidation();
        $this->renewing = true;
    }

    public function saveRenew(): void
    {
        $this->authorize('licenses.edit');
        $this->validate([
            'newRenewalDate' => ['required', 'date'],
            'newExpiresAt' => ['nullable', 'date'],
        ], [], ['newRenewalDate' => 'nueva fecha de renovación', 'newExpiresAt' => 'nueva expiración']);

        $license = $this->license;
        $old = $license->renewal_date?->format('d/m/Y') ?? '—';
        $license->update([
            'renewal_date' => $this->newRenewalDate,
            'expires_at' => $this->newExpiresAt ?: $license->expires_at,
            'alerts_enabled' => true,
        ]);

        activity('License')->performedOn($license)->causedBy(auth()->user())
            ->log("Renovación registrada: {$old} → ".\Illuminate\Support\Carbon::parse($this->newRenewalDate)->format('d/m/Y'));

        $this->renewing = false;
        $this->dispatch('license-saved');
        $this->dispatch('toast', type: 'success', message: 'Renovación registrada; la alerta se reinició al nuevo periodo.');
    }

    public function release(int $assignmentId): void
    {
        $this->authorize('licenses.assign');

        $assignment = LicenseAssignment::where('license_id', $this->licenseId)
            ->whereNull('released_at')
            ->findOrFail($assignmentId);

        $assignment->update(['released_at' => now()]);
        $this->dispatch('license-saved');
        $this->dispatch('toast', type: 'success', message: 'Asiento liberado.');
    }

    public function render()
    {
        $license = $this->license;

        $assignments = LicenseAssignment::with('assignable')
            ->where('license_id', $license->id)
            ->orderByRaw('released_at is null desc')
            ->orderByDesc('assigned_at')
            ->get();

        return view('livewire.admin.licenses.license-detail', [
            'license' => $license,
            'used' => $assignments->whereNull('released_at')->count(),
            'assignments' => $assignments,
            'assets' => Asset::orderBy('asset_tag')->get()->mapWithKeys(fn ($a) => [$a->id => "{$a->asset_tag} — {$a->name}"]),
            'employees' => Employee::where('status', 'active')->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
