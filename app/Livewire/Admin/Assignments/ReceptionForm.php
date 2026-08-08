<?php

namespace App\Livewire\Admin\Assignments;

use App\Models\AdditionalItemType;
use App\Models\AssetStatus;
use App\Models\Assignment;
use App\Models\Employee;
use App\Mail\ReceptionNotificationMail;
use App\Models\ResponsiveLetter;
use App\Services\MailConfigurator;
use App\Services\ResponsiveLetterService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

    /** Ubicación destino de los activos al recibirlos (por defecto, Almacén). */
    public ?int $returnLocationId = null;

    public string $notes = '';

    public bool $generateLetter = true;

    public bool $notifyEmployee = true;

    public bool $notifyManager = true;

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
        // Ubicación de retorno por defecto: la que contenga "almac" (Almacén).
        $this->returnLocationId = \App\Models\Location::where('name', 'like', '%almac%')->value('id');
        $this->generateLetter = auth()->user()->can('responsive_letters.create');
        $this->notifyEmployee = true;
        $this->notifyManager = true;
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

                $assetUpdate = ['asset_status_id' => $this->newStatusId];
                if ($this->returnLocationId) {
                    $assetUpdate['location_id'] = $this->returnLocationId;
                }
                $assignment->asset?->update($assetUpdate);
            }

            return $letter;
        });

        if ($letter) {
            $letters->generatePdf($letter);
        }

        // Avisos por correo de la recepción
        $emailResult = null;
        $managerResult = null;
        if ($this->notifyEmployee) {
            $emailResult = $this->sendReceptionEmail($chosen, $letter, toManager: false);
        }
        if ($this->notifyManager) {
            $managerResult = $this->sendReceptionEmail($chosen, $letter, toManager: true);
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

        foreach ([$emailResult, $managerResult] as $result) {
            if ($result !== null) {
                [$ok, $msg] = $result;
                $this->dispatch('toast', type: $ok ? 'success' : 'error', message: $msg);
            }
        }
    }

    /**
     * Notifica la recepción de bienes por correo, al empleado o a su jefe inmediato.
     *
     * @param  int[]  $assignmentIds
     * @return array{0:bool,1:string}|null
     */
    protected function sendReceptionEmail(array $assignmentIds, ?ResponsiveLetter $letter, bool $toManager): ?array
    {
        $employee = Employee::with('manager')->find($this->employeeId);
        $recipient = $toManager ? $employee?->manager : $employee;

        if ($toManager && ! $recipient) {
            return null; // sin jefe inmediato: nada que notificar
        }
        if (blank($recipient?->email)) {
            $quien = $toManager ? 'el jefe inmediato' : 'el empleado';

            return [false, "No se notificó por correo: {$quien} no tiene correo registrado."];
        }
        if (! MailConfigurator::isReady()) {
            return $toManager ? null : [false, 'No se notificó por correo: el correo no está configurado (Configuración → Correo).'];
        }

        try {
            MailConfigurator::apply();
            $assignments = Assignment::with('asset.type')->whereIn('id', $assignmentIds)->get();
            $items = $letter ? $letter->items()->with('type')->get() : collect();

            Mail::to($recipient->email)->send(new ReceptionNotificationMail(
                $employee, $assignments, $items, $letter?->folio, $this->returnedAt,
                toManager: $toManager, managerName: $employee?->manager?->name
            ));

            return $toManager
                ? [true, "Copia de recepción enviada al jefe inmediato ({$recipient->name})."]
                : [true, "Aviso de recepción enviado a {$recipient->name} ({$recipient->email})."];
        } catch (\Throwable $e) {
            return [false, 'No se pudo enviar el aviso por correo: '.$e->getMessage()];
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
        $selectedEmployee = $this->employeeId ? Employee::with('manager')->find($this->employeeId) : null;

        return view('livewire.admin.assignments.reception-form', [
            'employees' => Employee::whereHas('assignments', fn ($q) => $q->whereNull('returned_at'))
                ->orderBy('name')->pluck('name', 'id'),
            'assignments' => $this->activeAssignments(),
            'statuses' => \App\Support\CatalogRegistry::options('estados-de-activo'),
            'locations' => \App\Support\CatalogRegistry::options('ubicaciones'),
            'conditionOptions' => AssignmentForm::CONDITIONS,
            'additionalTypes' => AdditionalItemType::where('is_active', true)->orderBy('name')->get(),
            'mailReady' => MailConfigurator::isReady(),
            'employeeEmail' => $selectedEmployee?->email,
            'manager' => $selectedEmployee?->manager,
        ]);
    }
}
