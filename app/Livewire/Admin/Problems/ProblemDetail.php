<?php

namespace App\Livewire\Admin\Problems;

use App\Models\Problem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Detalle de problema: información, notas de seguimiento (línea de tiempo),
 * cambio rápido de estado, adjuntos e histórico de cambios.
 */
class ProblemDetail extends Component
{
    use AuthorizesRequests;

    public int $problemId;

    public string $tab = 'timeline';

    public string $noteBody = '';

    public function mount(int $problemId): void
    {
        $this->problemId = $problemId;
    }

    public function getProblemProperty(): Problem
    {
        return Problem::with([
            'asset.type', 'category', 'createdBy', 'assignedTo',
            'notes.user', 'attachments',
        ])->findOrFail($this->problemId);
    }

    public function addNote(): void
    {
        $this->authorize('problems.edit');
        $this->validate(
            ['noteBody' => ['required', 'string', 'min:2', 'max:5000']],
            [],
            ['noteBody' => 'nota'],
        );

        $this->problem->notes()->create([
            'user_id' => auth()->id(),
            'body' => $this->noteBody,
        ]);

        $this->reset('noteBody');
        $this->dispatch('toast', type: 'success', message: 'Nota agregada al seguimiento.');
    }

    public function deleteNote(int $noteId): void
    {
        $this->authorize('problems.edit');
        $this->problem->notes()->findOrFail($noteId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Nota eliminada.');
    }

    public function changeStatus(string $status): void
    {
        $this->authorize('problems.edit');
        abort_unless(array_key_exists($status, Problem::STATUSES), 422);

        $problem = $this->problem;
        $problem->update([
            'status' => $status,
            'resolved_at' => in_array($status, ['resolved', 'closed']) ? ($problem->resolved_at ?? now()) : null,
            'closed_at' => $status === 'closed' ? ($problem->closed_at ?? now()) : null,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Estado actualizado a '.Problem::STATUSES[$status].'.');
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $this->authorize('problems.edit');
        $attachment = $this->problem->attachments()->findOrFail($attachmentId);
        Storage::disk($attachment->disk)->delete($attachment->file_path);
        $attachment->delete();
        $this->dispatch('toast', type: 'success', message: 'Adjunto eliminado.');
    }

    #[On('problem-saved')]
    public function refreshAfterSave(): void
    {
        // Re-render tras editar.
    }

    public function render()
    {
        $problem = $this->problem;

        return view('livewire.admin.problems.problem-detail', [
            'problem' => $problem,
            'activities' => $problem->activities()->with('causer')->latest()->limit(50)->get(),
            'statusChip' => ProblemsTable::STATUS_CHIP,
            'priorityChip' => ProblemsTable::PRIORITY_CHIP,
        ]);
    }
}
