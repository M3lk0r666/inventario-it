<?php

namespace App\Livewire\Admin\Problems;

use App\Models\Asset;
use App\Models\Problem;
use App\Models\User;
use App\Support\CatalogRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Alta/edición de problemas de soporte ligados a un activo.
 * Los cambios de estado ajustan automáticamente resolved_at / closed_at.
 */
class ProblemForm extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public bool $open = false;

    public ?int $editingId = null;

    public array $data = [];

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $files = [];

    public ?int $confirmingDeleteId = null;

    public string $confirmingDeleteLabel = '';

    #[On('open-problem-form')]
    public function openForm(?int $id = null, ?int $assetId = null): void
    {
        $this->authorize($id ? 'problems.edit' : 'problems.create');
        $this->resetValidation();
        $this->reset('files');

        $this->editingId = $id;
        $this->data = [
            'title' => null, 'description' => null, 'problem_category_id' => null,
            'asset_id' => $assetId, 'priority' => 'medium', 'status' => 'new',
            'cost' => null, 'assigned_to' => null,
            'reported_at' => now()->format('Y-m-d\TH:i'),
        ];

        if ($id) {
            $p = Problem::findOrFail($id);
            foreach (['title', 'description', 'problem_category_id', 'asset_id', 'priority', 'status', 'cost', 'assigned_to'] as $key) {
                $this->data[$key] = $p->{$key};
            }
            $this->data['reported_at'] = $p->reported_at?->format('Y-m-d\TH:i');
        }

        $this->open = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'problems.edit' : 'problems.create');

        $validated = $this->validate([
            'data.title' => ['required', 'string', 'max:255'],
            'data.description' => ['nullable', 'string'],
            'data.problem_category_id' => ['nullable', 'integer', 'exists:problem_categories,id'],
            'data.asset_id' => ['required', 'integer', 'exists:assets,id'],
            'data.priority' => ['required', 'in:low,medium,high,critical'],
            'data.status' => ['required', 'in:new,in_progress,resolved,closed'],
            'data.cost' => ['nullable', 'numeric', 'min:0'],
            'data.assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'data.reported_at' => ['required', 'date'],
            'files.*' => ['nullable', 'file', 'max:8192'],
        ], [], [
            'data.title' => 'título', 'data.asset_id' => 'activo', 'data.priority' => 'prioridad',
            'data.status' => 'estado', 'data.cost' => 'costo', 'data.assigned_to' => 'responsable',
            'data.reported_at' => 'fecha de reporte',
        ])['data'];

        // Sellos de tiempo automáticos según el estado
        $validated['resolved_at'] = in_array($validated['status'], ['resolved', 'closed'])
            ? ($this->editingId ? Problem::find($this->editingId)?->resolved_at ?? now() : now())
            : null;
        $validated['closed_at'] = $validated['status'] === 'closed'
            ? ($this->editingId ? Problem::find($this->editingId)?->closed_at ?? now() : now())
            : null;

        if ($this->editingId) {
            $problem = Problem::findOrFail($this->editingId);
            $problem->update($validated);
        } else {
            $validated['created_by'] = auth()->id();
            $problem = Problem::create($validated);
        }

        foreach ($this->files as $file) {
            $path = $file->store("problems/{$problem->id}", 'public');
            $problem->attachments()->create([
                'disk' => 'public',
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        $this->open = false;
        $this->reset('files');
        $this->dispatch('problem-saved');
        $this->dispatch('toast', type: 'success', message: 'Problema guardado correctamente.');
    }

    #[On('confirm-problem-delete')]
    public function confirmDelete(int $id): void
    {
        $this->authorize('problems.delete');
        $p = Problem::findOrFail($id);
        $this->confirmingDeleteId = $id;
        $this->confirmingDeleteLabel = $p->title;
    }

    public function delete(): void
    {
        $this->authorize('problems.delete');
        Problem::findOrFail($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('problem-saved');
        $this->dispatch('toast', type: 'success', message: 'Problema eliminado.');
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    #[On('quick-created')]
    public function onQuickCreated(string $catalog, int $id): void
    {
        if ($this->open && $catalog === 'categorias-de-problema') {
            $this->data['problem_category_id'] = $id;
        }
    }

    public function render()
    {
        return view('livewire.admin.problems.problem-form', [
            'categories' => CatalogRegistry::options('categorias-de-problema'),
            'assets' => Asset::orderBy('asset_tag')->get()->mapWithKeys(fn ($a) => [$a->id => "{$a->asset_tag} — {$a->name}"]),
            'technicians' => User::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
