<?php

namespace App\Livewire\Admin\Reminders;

use App\Models\Reminder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Recordatorios con rango de fechas y visibilidad (privado/público).
 * Cada quien ve los públicos y los suyos; edita/elimina solo los propios
 * (salvo permiso amplio).
 */
class RemindersManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $filter = 'upcoming'; // upcoming | mine | all

    public bool $open = false;

    public ?int $editingId = null;

    public array $data = [];

    public ?int $confirmingDeleteId = null;

    public function openForm(?int $id = null): void
    {
        $this->authorize($id ? 'reminders.edit' : 'reminders.create');
        $this->resetValidation();

        $this->editingId = $id;
        $this->data = [
            'title' => null, 'body' => null,
            'starts_at' => now()->format('Y-m-d\TH:i'),
            'ends_at' => null, 'visibility' => 'private',
        ];

        if ($id) {
            $r = Reminder::where('user_id', auth()->id())->findOrFail($id);
            $this->data = [
                'title' => $r->title,
                'body' => $r->body,
                'starts_at' => $r->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $r->ends_at?->format('Y-m-d\TH:i'),
                'visibility' => $r->visibility,
            ];
        }

        $this->open = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'reminders.edit' : 'reminders.create');

        $validated = $this->validate([
            'data.title' => ['required', 'string', 'max:255'],
            'data.body' => ['nullable', 'string'],
            'data.starts_at' => ['required', 'date'],
            'data.ends_at' => ['nullable', 'date', 'after_or_equal:data.starts_at'],
            'data.visibility' => ['required', 'in:private,public'],
        ], [
            'data.ends_at.after_or_equal' => 'La fecha fin no puede ser anterior al inicio.',
        ], [
            'data.title' => 'título', 'data.starts_at' => 'fecha de inicio',
            'data.ends_at' => 'fecha de fin', 'data.visibility' => 'visibilidad',
        ])['data'];

        if ($this->editingId) {
            Reminder::where('user_id', auth()->id())->findOrFail($this->editingId)->update($validated);
        } else {
            $validated['user_id'] = auth()->id();
            Reminder::create($validated);
        }

        $this->open = false;
        $this->dispatch('toast', type: 'success', message: 'Recordatorio guardado.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('reminders.delete');
        Reminder::where('user_id', auth()->id())->findOrFail($id);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $this->authorize('reminders.delete');
        Reminder::where('user_id', auth()->id())->findOrFail($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('toast', type: 'success', message: 'Recordatorio eliminado.');
    }

    public function render()
    {
        $user = auth()->user();

        $query = Reminder::with('user')->visibleTo($user);

        $query = match ($this->filter) {
            'mine' => $query->where('user_id', $user->id),
            'upcoming' => $query->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()->startOfDay()))
                ->orderBy('starts_at'),
            default => $query->orderByDesc('starts_at'),
        };

        if ($this->filter !== 'upcoming') {
            $query->orderByDesc('starts_at');
        }

        return view('livewire.admin.reminders.reminders-manager', [
            'reminders' => $query->paginate(12),
        ]);
    }
}
