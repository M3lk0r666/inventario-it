<?php

namespace App\Livewire\Admin\Letters;

use App\Models\Employee;
use App\Models\ResponsiveLetter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cartas responsivas agrupadas por empleado (acordeones), para no perder
 * de vista al colaborador cuando tiene varias cartas en distintas fechas.
 */
class LettersByEmployee extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    /** Empleado expandido actualmente (null = ninguno). */
    public ?int $expanded = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggle(int $employeeId): void
    {
        $this->expanded = $this->expanded === $employeeId ? null : $employeeId;
    }

    #[On('letters-updated')]
    public function refreshLetters(): void
    {
        // Re-render tras firmar/anular desde el modal compartido.
    }

    public function render()
    {
        $letterQuery = ResponsiveLetter::query()
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->withCount([
                'assignments', 'returnedAssignments',
                // Activos de esta entrega que ya fueron devueltos (para permitir corrección solo si 0).
                'assignments as returned_count' => fn ($q) => $q->whereNotNull('returned_at'),
            ]);

        $employees = Employee::query()
            ->whereHas('responsiveLetters', fn ($q) => $q
                ->when($this->typeFilter, fn ($qq) => $qq->where('type', $this->typeFilter))
                ->when($this->statusFilter, fn ($qq) => $qq->where('status', $this->statusFilter)))
            ->when(filled($this->search), fn ($q) => $q->where(fn ($qq) => $qq
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('employee_number', 'like', "%{$this->search}%")
                ->orWhereHas('responsiveLetters', fn ($l) => $l->where('folio', 'like', "%{$this->search}%"))))
            ->withCount(['responsiveLetters as letters_count' => fn ($q) => $q
                ->when($this->typeFilter, fn ($qq) => $qq->where('type', $this->typeFilter))
                ->when($this->statusFilter, fn ($qq) => $qq->where('status', $this->statusFilter))])
            ->orderBy('name')
            ->paginate(15);

        $letters = collect();
        if ($this->expanded) {
            $letters = $letterQuery->clone()
                ->where('employee_id', $this->expanded)
                ->orderByDesc('issued_at')->orderByDesc('folio')
                ->get();
        }

        return view('livewire.admin.letters.letters-by-employee', [
            'employees' => $employees,
            'letters' => $letters,
        ]);
    }
}
