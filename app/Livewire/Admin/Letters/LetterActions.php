<?php

namespace App\Livewire\Admin\Letters;

use App\Models\AssetStatus;
use App\Models\ResponsiveLetter;
use App\Services\ResponsiveLetterService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Modales compartidos para acciones de cartas: anular (confirmación Tailwind)
 * y subir la carta firmada como evidencia (marca la carta como "firmada").
 * Ambas vistas de cartas (agrupada y listado) despachan aquí.
 */
class LetterActions extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    // Anular
    public ?int $cancelId = null;

    public string $cancelFolio = '';

    // Subir firmada
    public ?int $signId = null;

    public string $signFolio = '';

    public bool $signHasExisting = false;

    public $signedFile = null;

    #[On('confirm-cancel-letter')]
    public function confirmCancel(int $id): void
    {
        $this->authorize('responsive_letters.cancel');
        $letter = ResponsiveLetter::findOrFail($id);
        $this->cancelId = $id;
        $this->cancelFolio = $letter->folio;
    }

    public function cancelLetter(ResponsiveLetterService $service): void
    {
        $this->authorize('responsive_letters.cancel');
        $letter = ResponsiveLetter::findOrFail($this->cancelId);

        $freed = 0;
        if ($letter->status !== 'cancelled') {
            $freed = DB::transaction(function () use ($letter) {
                $letter->update(['status' => 'cancelled']);

                // Al anular una carta de ENTREGA se deshace la asignación:
                // se liberan los activos aún en poder del colaborador.
                if ($letter->type !== 'delivery') {
                    return 0;
                }

                $freeStatusId = AssetStatus::where('is_assignable', true)->orderBy('id')->value('id');
                $active = $letter->assignments()->whereNull('returned_at')->with('asset')->get();

                foreach ($active as $assignment) {
                    if ($freeStatusId) {
                        $assignment->asset?->update(['asset_status_id' => $freeStatusId]);
                    }
                    $assignment->delete();
                }

                return $active->count();
            });

            $service->generatePdf($letter);
        }

        $this->cancelId = null;
        $this->dispatch('letters-updated');
        $this->dispatch('asset-saved');
        $msg = "Carta {$letter->folio} anulada.".($freed > 0 ? " Se liberaron {$freed} activo(s)." : '');
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    #[On('sign-letter')]
    public function openSign(int $id): void
    {
        $this->authorize('responsive_letters.edit');
        $letter = ResponsiveLetter::findOrFail($id);
        $this->resetValidation();
        $this->reset('signedFile');
        $this->signId = $id;
        $this->signFolio = $letter->folio;
        $this->signHasExisting = $letter->hasSignedDocument();
    }

    public function saveSigned(): void
    {
        $this->authorize('responsive_letters.edit');
        $this->validate(
            ['signedFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192']],
            [],
            ['signedFile' => 'documento firmado'],
        );

        $letter = ResponsiveLetter::findOrFail($this->signId);

        // Reemplazar evidencia previa si existía
        if ($letter->signed_document_path) {
            Storage::disk('public')->delete($letter->signed_document_path);
        }

        $ext = $this->signedFile->getClientOriginalExtension();
        $path = $this->signedFile->storeAs('responsive_letters/signed', "{$letter->folio}-firmada.{$ext}", 'public');

        $letter->update([
            'signed_document_path' => $path,
            'signed_at' => now(),
            'signed_by' => auth()->id(),
            'status' => 'signed',
        ]);

        $this->signId = null;
        $this->reset('signedFile');
        $this->dispatch('letters-updated');
        $this->dispatch('toast', type: 'success', message: "Carta {$letter->folio} marcada como firmada con evidencia.");
    }

    public function render()
    {
        return view('livewire.admin.letters.letter-actions');
    }
}
