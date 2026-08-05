<?php

namespace App\Livewire\Admin\Assignments;

use App\Models\AdditionalItemType;
use App\Models\Asset;
use App\Models\AssetStatus;
use App\Models\Assignment;
use App\Models\Employee;
use App\Mail\AssignmentNotificationMail;
use App\Models\ResponsiveLetter;
use App\Services\MailConfigurator;
use App\Services\ResponsiveLetterService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Flujo de asignación (estilo GLPI): un empleado, uno o varios activos,
 * fecha, estado físico y observaciones. Genera carta responsiva en PDF.
 */
class AssignmentForm extends Component
{
    use AuthorizesRequests;

    public const CONDITIONS = ['Bueno', 'Con detalles estéticos', 'Requiere revisión', 'Dañado'];

    public bool $open = false;

    public ?int $employeeId = null;

    public string $assignedAt = '';

    public string $condition = 'Bueno';

    public string $notes = '';

    public bool $generateLetter = true;

    public bool $notifyEmployee = true;

    public bool $notifyManager = true;

    /** @var int[] IDs de activos seleccionados */
    public array $selectedAssets = [];

    public string $assetSearch = '';

    /** @var array<int,bool> tipo_adicional_id => marcado */
    public array $additionalChecked = [];

    /** @var array<int,string> tipo_adicional_id => valor (extensión, correo...) */
    public array $additionalValues = [];

    #[On('open-assignment-form')]
    public function openForm(?int $assetId = null): void
    {
        $this->authorize('assignments.create');
        $this->resetValidation();
        $this->reset('employeeId', 'notes', 'assetSearch', 'selectedAssets', 'additionalChecked', 'additionalValues');
        $this->assignedAt = now()->format('Y-m-d');
        $this->condition = 'Bueno';
        $this->generateLetter = auth()->user()->can('responsive_letters.create');
        $this->notifyEmployee = true;
        $this->notifyManager = true;

        if ($assetId && $this->isAvailable($assetId)) {
            $this->selectedAssets = [$assetId];
        }

        $this->open = true;
    }

    protected function isAvailable(int $assetId): bool
    {
        return Asset::whereKey($assetId)
            ->whereDoesntHave('currentAssignment')
            ->whereHas('status', fn ($q) => $q->where('is_assignable', true))
            ->exists();
    }

    public function addAsset(int $assetId): void
    {
        if (! in_array($assetId, $this->selectedAssets) && $this->isAvailable($assetId)) {
            $this->selectedAssets[] = $assetId;
        }
        $this->assetSearch = '';
    }

    public function removeAsset(int $assetId): void
    {
        $this->selectedAssets = array_values(array_diff($this->selectedAssets, [$assetId]));
    }

    public function save(ResponsiveLetterService $letters): void
    {
        $this->authorize('assignments.create');

        $this->validate([
            'employeeId' => ['required', 'integer', 'exists:employees,id'],
            'assignedAt' => ['required', 'date'],
            'condition' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'selectedAssets' => ['required', 'array', 'min:1'],
            'selectedAssets.*' => ['integer', 'exists:assets,id'],
        ], [
            'selectedAssets.required' => 'Selecciona al menos un activo.',
            'selectedAssets.min' => 'Selecciona al menos un activo.',
        ], [
            'employeeId' => 'empleado',
            'assignedAt' => 'fecha de entrega',
            'condition' => 'estado físico',
        ]);

        // Revalidar disponibilidad de todos los activos
        foreach ($this->selectedAssets as $assetId) {
            if (! $this->isAvailable($assetId)) {
                $tag = Asset::find($assetId)?->asset_tag ?? "#{$assetId}";
                $this->addError('selectedAssets', "El activo {$tag} ya no está disponible.");

                return;
            }
        }

        // Los bienes adicionales requieren una carta; si hay marcados, se fuerza generarla.
        $selectedAdditional = $this->collectAdditional();
        $generateLetter = ($this->generateLetter || $selectedAdditional !== [])
            && auth()->user()->can('responsive_letters.create');
        $assignedStatusId = AssetStatus::where('slug', 'asignado')->value('id');

        $letter = DB::transaction(function () use ($letters, $generateLetter, $assignedStatusId, $selectedAdditional) {
            $letter = null;

            if ($generateLetter) {
                $letter = ResponsiveLetter::create([
                    'folio' => $letters->nextFolio('delivery'),
                    'type' => 'delivery',
                    'employee_id' => $this->employeeId,
                    'issued_at' => $this->assignedAt,
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

            foreach ($this->selectedAssets as $assetId) {
                Assignment::create([
                    'asset_id' => $assetId,
                    'employee_id' => $this->employeeId,
                    'responsive_letter_id' => $letter?->id,
                    'assigned_at' => $this->assignedAt,
                    'condition_on_assign' => $this->condition,
                    'assigned_by' => auth()->id(),
                    'notes' => $this->notes ?: null,
                ]);

                if ($assignedStatusId) {
                    Asset::whereKey($assetId)->first()?->update(['asset_status_id' => $assignedStatusId]);
                }
            }

            return $letter;
        });

        if ($letter) {
            $letters->generatePdf($letter);
        }

        // Avisos por correo (si se solicitó y hay carta generada)
        $emailResult = null;
        $managerResult = null;
        if ($letter && $this->notifyEmployee) {
            $emailResult = $this->sendAssignmentEmail($letter);
        }
        if ($letter && $this->notifyManager) {
            $managerResult = $this->sendManagerAssignmentEmail($letter);
        }

        $this->open = false;
        $this->dispatch('assignment-saved');
        $this->dispatch('asset-saved');

        if ($letter) {
            $this->dispatch('toast', type: 'success', message: "Asignación registrada. Carta {$letter->folio} generada.");
            $this->dispatch('open-url', url: route('admin.letters.pdf', $letter->id));
        } else {
            $this->dispatch('toast', type: 'success', message: 'Asignación registrada.');
        }

        foreach ([$emailResult, $managerResult] as $result) {
            if ($result !== null) {
                [$ok, $msg] = $result;
                $this->dispatch('toast', type: $ok ? 'success' : 'error', message: $msg);
            }
        }
    }

    /**
     * Copia informativa al jefe inmediato del empleado.
     *
     * @return array{0:bool,1:string}|null
     */
    protected function sendManagerAssignmentEmail(ResponsiveLetter $letter): ?array
    {
        $manager = $letter->employee?->manager;

        if (! $manager) {
            return null; // sin jefe inmediato: nada que notificar
        }
        if (blank($manager->email)) {
            return [false, 'El jefe inmediato no tiene correo; no se le notificó.'];
        }
        if (! MailConfigurator::isReady()) {
            return null; // el correo no está configurado (ya se informa en el aviso al empleado)
        }

        try {
            MailConfigurator::apply();
            Mail::to($manager->email)->send(new AssignmentNotificationMail($letter, toManager: true));

            return [true, "Copia enviada al jefe inmediato ({$manager->name})."];
        } catch (\Throwable $e) {
            return [false, 'No se pudo notificar al jefe inmediato: '.$e->getMessage()];
        }
    }

    /**
     * Envía al empleado el aviso de bienes asignados (versión digerible de la
     * carta). Requiere que el empleado tenga correo y que el correo esté configurado.
     *
     * @return array{0:bool,1:string}
     */
    protected function sendAssignmentEmail(ResponsiveLetter $letter): array
    {
        $employee = $letter->employee;

        if (blank($employee?->email)) {
            return [false, 'No se notificó por correo: el empleado no tiene correo registrado.'];
        }
        if (! MailConfigurator::isReady()) {
            return [false, 'No se notificó por correo: el correo no está configurado (Configuración → Correo).'];
        }

        try {
            MailConfigurator::apply();
            Mail::to($employee->email)->send(new AssignmentNotificationMail($letter));

            return [true, "Aviso enviado al correo de {$employee->name} ({$employee->email})."];
        } catch (\Throwable $e) {
            return [false, 'No se pudo enviar el aviso por correo: '.$e->getMessage()];
        }
    }

    /** Bienes adicionales marcados, con su valor. @return array<int,array{id:int,value:?string}> */
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

        $available = Asset::with(['type', 'model.manufacturer'])
            ->whereDoesntHave('currentAssignment')
            ->whereHas('status', fn ($q) => $q->where('is_assignable', true))
            ->whereNotIn('id', $this->selectedAssets)
            ->when(filled($this->assetSearch), fn ($q) => $q->where(fn ($qq) => $qq
                ->where('asset_tag', 'like', "%{$this->assetSearch}%")
                ->orWhere('name', 'like', "%{$this->assetSearch}%")
                ->orWhere('serial_number', 'like', "%{$this->assetSearch}%")))
            ->orderBy('asset_tag')
            ->limit(8)
            ->get();

        return view('livewire.admin.assignments.assignment-form', [
            'employees' => Employee::where('status', 'active')->orderBy('name')->pluck('name', 'id'),
            'available' => $available,
            'selected' => Asset::with(['type'])->whereIn('id', $this->selectedAssets)->orderBy('asset_tag')->get(),
            'conditions' => self::CONDITIONS,
            'additionalTypes' => AdditionalItemType::where('is_active', true)->orderBy('name')->get(),
            'mailReady' => MailConfigurator::isReady(),
            'employeeEmail' => $selectedEmployee?->email,
            'manager' => $selectedEmployee?->manager,
        ]);
    }
}
