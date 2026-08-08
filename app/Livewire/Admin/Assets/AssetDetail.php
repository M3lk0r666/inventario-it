<?php

namespace App\Livewire\Admin\Assets;

use App\Models\Asset;
use App\Models\AssetStatus;
use App\Support\CatalogRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Detalle de activo con pestañas y acciones: cambiar estado y dar de baja.
 * La edición completa reutiliza el AssetForm (slide-over).
 */
class AssetDetail extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public int $assetId;

    public string $tab = 'info';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $files = [];

    public string $noteBody = '';

    // Cambio de estado
    public bool $changingStatus = false;

    public ?int $newStatusId = null;

    public string $statusNote = '';

    // Baja
    public bool $confirmingRetire = false;

    // Asignar licencia a este equipo
    public bool $assigningLicense = false;

    public ?int $licenseToAssign = null;

    public string $licenseAssignNotes = '';

    public function mount(int $assetId): void
    {
        $this->assetId = $assetId;
    }

    public function getAssetProperty(): Asset
    {
        return Asset::with([
            'type', 'model.manufacturer', 'status', 'location', 'supplier',
            'assignments.employee', 'assignments.responsiveLetter', 'assignments.assignedBy',
            'currentAssignment.employee',
            'problems.category', 'licenseAssignments.license', 'attachments',
            'deviceNotes.user',
        ])->findOrFail($this->assetId);
    }

    public function openChangeStatus(): void
    {
        $this->authorize('assets.change_status');
        $this->newStatusId = $this->asset->asset_status_id;
        $this->statusNote = '';
        $this->changingStatus = true;
    }

    public function saveStatus(): void
    {
        $this->authorize('assets.change_status');
        $this->validate(
            ['newStatusId' => ['required', 'integer', 'exists:asset_statuses,id']],
            [],
            ['newStatusId' => 'estado'],
        );

        $asset = $this->asset;
        $old = $asset->status?->name;
        $asset->update(['asset_status_id' => $this->newStatusId]);
        $new = AssetStatus::find($this->newStatusId)?->name;

        if (filled($this->statusNote)) {
            activity('Asset')
                ->performedOn($asset)
                ->causedBy(auth()->user())
                ->withProperties(['nota' => $this->statusNote])
                ->log("Cambio de estado: {$old} → {$new}");
        }

        $this->changingStatus = false;
        $this->dispatch('toast', type: 'success', message: "Estado actualizado a {$new}.");
    }

    public function confirmRetire(): void
    {
        $this->authorize('assets.change_status');
        $this->confirmingRetire = true;
    }

    public function retire(): void
    {
        $this->authorize('assets.change_status');
        $asset = $this->asset;

        if ($asset->currentAssignment) {
            $this->confirmingRetire = false;
            $this->dispatch('toast', type: 'error',
                message: 'No se puede dar de baja: el activo está asignado. Registra la devolución primero.');

            return;
        }

        $baja = AssetStatus::where('slug', 'baja')->first();
        if (! $baja) {
            $this->confirmingRetire = false;
            $this->dispatch('toast', type: 'error', message: 'No existe el estado "Baja" en el catálogo.');

            return;
        }

        $asset->update(['asset_status_id' => $baja->id]);
        activity('Asset')
            ->performedOn($asset)
            ->causedBy(auth()->user())
            ->log('Activo dado de baja');

        $this->confirmingRetire = false;
        $this->dispatch('toast', type: 'success', message: 'Activo dado de baja.');
    }

    /** Subir adjuntos directamente desde la sección Adjuntos. */
    public function uploadFiles(): void
    {
        $this->authorize('assets.edit');
        $this->validate(
            ['files.*' => ['file', 'max:8192']],
            [],
            ['files.*' => 'archivo'],
        );

        $asset = $this->asset;
        foreach ($this->files as $file) {
            $path = $file->store("assets/{$asset->id}", 'public');
            $asset->attachments()->create([
                'disk' => 'public',
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        $this->reset('files');
        $this->dispatch('toast', type: 'success', message: 'Adjunto(s) agregado(s).');
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $this->authorize('assets.edit');
        $attachment = $this->asset->attachments()->findOrFail($attachmentId);

        Storage::disk($attachment->disk)->delete($attachment->file_path);
        $attachment->delete();
        $this->dispatch('toast', type: 'success', message: 'Adjunto eliminado.');
    }

    /** Notas libres del dispositivo. */
    public function addNote(): void
    {
        $this->authorize('assets.edit');
        $this->validate(
            ['noteBody' => ['required', 'string', 'min:3', 'max:2000']],
            [],
            ['noteBody' => 'nota'],
        );

        $this->asset->deviceNotes()->create([
            'user_id' => auth()->id(),
            'body' => $this->noteBody,
        ]);

        $this->reset('noteBody');
        $this->dispatch('toast', type: 'success', message: 'Nota agregada.');
    }

    public function deleteNote(int $noteId): void
    {
        $this->authorize('assets.edit');
        $this->asset->deviceNotes()->findOrFail($noteId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Nota eliminada.');
    }

    #[On('asset-saved')]
    public function refreshAfterSave(): void
    {
        // Re-render tras editar en el slide-over.
    }

    // ---- Asignar licencia al equipo ----
    public function openAssignLicense(): void
    {
        $this->authorize('licenses.assign');
        $this->reset('licenseToAssign', 'licenseAssignNotes');
        $this->resetValidation();
        $this->assigningLicense = true;
    }

    public function saveAssignLicense(): void
    {
        $this->authorize('licenses.assign');
        $this->validate(
            ['licenseToAssign' => ['required', 'integer', 'exists:licenses,id']],
            [],
            ['licenseToAssign' => 'licencia'],
        );

        $result = \Illuminate\Support\Facades\DB::transaction(function () {
            $license = \App\Models\License::lockForUpdate()->findOrFail($this->licenseToAssign);

            if ($license->availableSeats() <= 0) {
                return 'full';
            }

            $exists = \App\Models\LicenseAssignment::where('license_id', $license->id)
                ->where('assignable_type', Asset::class)
                ->where('assignable_id', $this->assetId)
                ->whereNull('released_at')->exists();

            if ($exists) {
                return 'dup';
            }

            \App\Models\LicenseAssignment::create([
                'license_id' => $license->id,
                'assignable_type' => Asset::class,
                'assignable_id' => $this->assetId,
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
                'notes' => $this->licenseAssignNotes ?: null,
            ]);

            return true;
        });

        if ($result === 'full') {
            $this->dispatch('toast', type: 'error', message: 'Esa licencia no tiene asientos disponibles.');

            return;
        }
        if ($result === 'dup') {
            $this->addError('licenseToAssign', 'Este equipo ya tiene un asiento activo de esa licencia.');

            return;
        }

        $this->assigningLicense = false;
        $this->dispatch('toast', type: 'success', message: 'Licencia asignada al equipo.');
    }

    public function render()
    {
        $asset = $this->asset;

        // Licencias con asientos disponibles (solo al abrir el modal de asignación)
        $availableLicenses = $this->assigningLicense
            ? \App\Models\License::withCount(['activeAssignments'])
                ->orderBy('software_name')->get()
                ->filter(fn ($l) => $l->seats - $l->active_assignments_count > 0)
                ->mapWithKeys(fn ($l) => [
                    $l->id => trim("{$l->software_name} {$l->version}").' · '.($l->seats - $l->active_assignments_count).' libre(s)',
                ])
            : collect();

        return view('livewire.admin.assets.asset-detail', [
            'asset' => $asset,
            'statuses' => CatalogRegistry::options('estados-de-activo'),
            'activities' => $asset->activities()->with('causer')->latest()->limit(50)->get(),
            'chipByColor' => AssetsTable::CHIP_BY_COLOR,
            'availableLicenses' => $availableLicenses,
        ]);
    }
}
