<?php

namespace App\Livewire\Admin\Assignments;

use App\Models\AdditionalItemType;
use App\Models\AssetStatus;
use App\Models\Assignment;
use App\Models\Employee;
use App\Models\ResponsiveLetter;
use App\Services\ResponsiveLetterService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Recepción de bienes (salida de un empleado): se eligen los activos
 * asignados a devolver, su estado de retorno y los bienes adicionales
 * recibidos. Genera la carta de recepción (PDF tipo "return").
 */
class ReceptionForm extends Component
{
    use AuthorizesRequests;

    public bool $open = false;

    public ?int $employeeId = null;

    public string $returnedAt = '';

    public ?int $newStatusId = null;

    public string $notes = '';

    public bool $generateLetter = true;

    /** @var array<int,bool> assignment_id => marcado */
    public array $selectedAssignments = [];

    /** @var array<int,string> assignment_id => estado físico de retorno */
    public array $conditions = [];

    /** @var array<int,bool> tipo_adicional_id => recibido */
    public array $additionalChecked = [];

    /** @var array<int,string> */
    public array $additionalValues = [];

    #[On('open-reception-form')]
    public function openForm(): void
    {
        $this->authorize('assignments.edit');
        $this->resetValidation();
        $this->reset('employeeId', 'notes', 'selectedAssignments', 'conditions', 'additionalChecked', 'additionalValues');
        $this->returnedAt = now()->format('Y-m-d');
        $this->newStatusId = AssetStatus::where('slug', 'resguardo')->value('id');
        $this->generateLetter = auth()->user()->can('responsive_letters.create');
        $this->open = true;
    }

    /** Al cambiar de empleado, precargar sus asignaciones activas. */
    public function updatedEmployeeId(): void
    {
        $this->selectedAssignments = [];
        $this->conditions = [];
        foreach ($this->activeAssignments() as $assignment) {
            $this->selectedAssignments[$assignment->id] = true;
            $this->conditions[$assignment->id] = 'Bueno';
        }
    }

    protected function activeAssignments()
    {
        if (! $this->employeeId) {
            return collect();
        }

        return Assignment::with('asset.type')
            ->where('employee_id', $this->employeeId)
            ->whereNull('returned_at')
            ->orderBy('assigned_at')
            ->get();
    }

    public function save(ResponsiveLetterService $letters): void
    {
        $this->authorize('assignments.edit');

        $this->validate([
            'employeeId' => ['required', 'integer', 'exists:employees,id'],
            'returnedAt' => ['required', 'date'],
            'newStatusId' => ['required', 'integer', 'exists:asset_statuses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'employeeId' => 'empleado',
            'returnedAt' => 'fecha de recepción',
            'newStatusId' => 'nuevo estado de los activos',
        ]);

        $chosen = collect($this->selectedAssignments)->filter()->keys()->all();
        if (empty($chosen)) {
            $this->addError('selectedAssignments', 'Selecciona al menos un activo a recibir.');

            return;
        }

        $generateLetter = $this->generateLetter && auth()->user()->can('responsive_letters.create');
        $selectedAdditional = $this->collectAdditional();

        $letter = DB::transaction(function () use ($letters, $chosen, $generateLetter, $selectedAdditional) {
            $letter = null;

            if ($generateLetter) {
                $letter = ResponsiveLetter::create([
                    'folio' => $letters->nextFolio('return'),
                    'type' => 'return',
                    'employee_id' => $this->employeeId,
                    'issued_at' => $this->returnedAt,
                    'status' => 'generated',
                    'created_by' => auth()->id(),
                    'notes' => $this->notes ?: null,
                ]);

                foreach ($selectedAdditional as $additional) {
                    $letter->items()->create([
                        'additional_item_type_id' => $additional['id'],
                        'value' => $additional['value'],
                    ]);
                }
            }

            foreach ($chosen as $assignmentId) {
                $assignment = Assignment::with('asset')
                    ->where('employee_id', $this->employeeId)
                    ->whereNull('returned_at')
                    ->find($assignmentId);

                if (! $assignment) {
                    continue;
                }

                $assignment->update([
                    'returned_at' => $this->returnedAt,
                    'condition_on_return' => $this->conditions[$assignmentId] ?? 'Bueno',
                    'received_by' => auth()->id(),
                    'return_letter_id' => $letter?->id,
                ]);

                $assignment->asset?->update(['asset_status_id' => $this->newStatusId]);
            }

            return $letter;
        });

        if ($letter) {
            $letters->generatePdf($letter);
        }

        $this->open = false;
        $this->dispatch('assignment-saved');
        $this->dispatch('asset-saved');

        if ($letter) {
            $this->dispatch('toast', type: 'success', message: "Recepción registrada. Carta {$letter->folio} generada.");
            $this->dispatch('open-url', url: route('admin.letters.pdf', $letter->id));
        } else {
            $this->dispatch('toast', type: 'success', message: 'Recepción registrada.');
        }
    }

    /** @return array<int,array{id:int,value:?string}> */
    protected function collectAdditional(): array
    {
        $result = [];
        foreach ($this->additionalChecked as $typeId => $checked) {
            if ($checked) {
                $result[] = [
                    'id' => (int) $typeId,
                    'value' => trim((string) ($this->additionalValues[$typeId] ?? '')) ?: null,
                ];
            }
        }

        return $result;
    }

    public function render()
    {
        return view('livewire.admin.assignments.reception-form', [
            'employees' => Employee::whereHas('assignments', fn ($q) => $q->whereNull('returned_at'))
                ->orderBy('name')->pluck('name', 'id'),
            'assignments' => $this->activeAssignments(),
            'statuses' => \App\Support\CatalogRegistry::options('estados-de-activo'),
            'conditionOptions' => AssignmentForm::CONDITIONS,
            'additionalTypes' => AdditionalItemType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
