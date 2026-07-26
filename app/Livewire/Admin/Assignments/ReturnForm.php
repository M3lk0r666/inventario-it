<?php

namespace App\Livewire\Admin\Assignments;

use App\Models\AssetStatus;
use App\Models\Assignment;
use App\Support\CatalogRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Devolución de un activo asignado: fecha, estado físico de retorno,
 * nuevo estado operativo del activo y observaciones.
 */
class ReturnForm extends Component
{
    use AuthorizesRequests;

    public bool $open = false;

    public ?int $assignmentId = null;

    public string $returnedAt = '';

    public string $condition = 'Bueno';

    public ?int $newStatusId = null;

    public string $notes = '';

    #[On('open-return-form')]
    public function openForm(int $id): void
    {
        $this->authorize('assignments.edit');

        $assignment = Assignment::with('asset', 'employee')->findOrFail($id);
        abort_unless($assignment->isActive(), 400);

        $this->resetValidation();
        $this->assignmentId = $id;
        $this->returnedAt = now()->format('Y-m-d');
        $this->condition = 'Bueno';
        $this->newStatusId = AssetStatus::where('slug', 'resguardo')->value('id');
        $this->notes = '';
        $this->open = true;
    }

    public function save(): void
    {
        $this->authorize('assignments.edit');

        $assignment = Assignment::with('asset')->findOrFail($this->assignmentId);
        abort_unless($assignment->isActive(), 400);

        $this->validate([
            'returnedAt' => ['required', 'date', 'after_or_equal:'.$assignment->assigned_at->format('Y-m-d')],
            'condition' => ['required', 'string', 'max:100'],
            'newStatusId' => ['required', 'integer', 'exists:asset_statuses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'returnedAt.after_or_equal' => 'La devolución no puede ser anterior a la entrega.',
        ], [
            'returnedAt' => 'fecha de devolución',
            'condition' => 'estado físico',
            'newStatusId' => 'nuevo estado del activo',
        ]);

        $assignment->update([
            'returned_at' => $this->returnedAt,
            'condition_on_return' => $this->condition,
            'received_by' => auth()->id(),
            'notes' => trim($assignment->notes."\n".($this->notes ? 'Devolución: '.$this->notes : '')) ?: null,
        ]);

        $assignment->asset?->update(['asset_status_id' => $this->newStatusId]);

        $this->open = false;
        $this->dispatch('assignment-saved');
        $this->dispatch('asset-saved');
        $this->dispatch('toast', type: 'success',
            message: "Devolución registrada para {$assignment->asset?->asset_tag}.");
    }

    public function render()
    {
        $assignment = $this->assignmentId
            ? Assignment::with('asset', 'employee')->find($this->assignmentId)
            : null;

        return view('livewire.admin.assignments.return-form', [
            'assignment' => $assignment,
            'statuses' => CatalogRegistry::options('estados-de-activo'),
            'conditions' => AssignmentForm::CONDITIONS,
        ]);
    }
}
