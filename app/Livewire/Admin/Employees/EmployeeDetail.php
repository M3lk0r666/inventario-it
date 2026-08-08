<?php

namespace App\Livewire\Admin\Employees;

use App\Models\Employee;
use App\Models\EmployeeAccount;
use App\Models\User;
use App\Services\PortalAccessService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Ficha del empleado con pestañas: datos, cuentas de acceso corporativas,
 * activos asignados (histórico), cartas responsivas y bitácora de cambios.
 */
class EmployeeDetail extends Component
{
    use AuthorizesRequests;

    public int $employeeId;

    public string $tab = 'info';

    // Cuenta de acceso (alta/edición inline)
    public bool $accountOpen = false;

    public ?int $accountId = null;

    public array $account = [];

    public ?int $confirmingAccountDeleteId = null;

    // Otorgar acceso al portal
    public bool $granting = false;

    public string $accessRole = '';

    public string $accessEmail = '';

    public bool $accessNotify = true;

    public bool $confirmingRevoke = false;

    public bool $confirmingResend = false;

    // Asignar licencia al empleado
    public bool $assigningLicense = false;

    public ?int $licenseToAssign = null;

    public string $licenseAssignNotes = '';

    public function mount(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    // ---- Asignar licencia al empleado ----
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
                ->where('assignable_type', \App\Models\Employee::class)
                ->where('assignable_id', $this->employeeId)
                ->whereNull('released_at')->exists();

            if ($exists) {
                return 'dup';
            }

            \App\Models\LicenseAssignment::create([
                'license_id' => $license->id,
                'assignable_type' => \App\Models\Employee::class,
                'assignable_id' => $this->employeeId,
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
            $this->addError('licenseToAssign', 'Este empleado ya tiene un asiento activo de esa licencia.');

            return;
        }

        $this->assigningLicense = false;
        $this->dispatch('toast', type: 'success', message: 'Licencia asignada al empleado.');
    }

    public function getEmployeeProperty(): Employee
    {
        return Employee::with([
            'department', 'location', 'manager', 'user.roles',
            'accounts',
            'assignments.asset.type', 'assignments.responsiveLetter',
            'responsiveLetters.items.type',
            'licenseAssignments.license',
        ])->findOrFail($this->employeeId);
    }

    /**
     * Bienes adicionales (llaves, controles, accesos...) en poder del empleado:
     * los amparados por cartas de entrega vigentes (no anuladas), descontando
     * los ya cubiertos por cartas de recepción.
     */
    public function getAdditionalItemsProperty(): \Illuminate\Support\Collection
    {
        $letters = $this->employee->responsiveLetters
            ->where('status', '!=', 'cancelled');

        $tally = [];
        foreach ($letters as $letter) {
            $sign = $letter->type === 'return' ? -1 : 1;
            foreach ($letter->items as $item) {
                $label = $item->type?->name ?? 'Bien adicional';
                $key = $label.'|'.trim((string) $item->value);
                $tally[$key] ??= ['label' => $label, 'value' => trim((string) $item->value), 'qty' => 0];
                $tally[$key]['qty'] += $sign;
            }
        }

        return collect($tally)->filter(fn ($i) => $i['qty'] > 0)->values();
    }

    // ---- Cuentas de acceso ----
    public function openAccount(?int $id = null): void
    {
        $this->authorize('employees.edit');
        $this->resetValidation();
        $this->accountId = $id;
        $this->account = [
            'account_type' => 'email', 'system_name' => null,
            'identifier' => null, 'status' => 'active', 'notes' => null,
        ];

        if ($id) {
            $acc = EmployeeAccount::where('employee_id', $this->employeeId)->findOrFail($id);
            foreach (array_keys($this->account) as $k) {
                $this->account[$k] = $acc->{$k};
            }
        }

        $this->accountOpen = true;
    }

    public function saveAccount(): void
    {
        $this->authorize('employees.edit');
        $data = $this->validate([
            'account.account_type' => ['required', 'in:email,domain,vpn,system'],
            'account.system_name' => ['nullable', 'string', 'max:255'],
            'account.identifier' => ['required', 'string', 'max:255'],
            'account.status' => ['required', 'in:active,suspended,revoked'],
            'account.notes' => ['nullable', 'string'],
        ], [], [
            'account.account_type' => 'tipo', 'account.identifier' => 'identificador', 'account.status' => 'estado',
        ])['account'];

        if ($this->accountId) {
            EmployeeAccount::where('employee_id', $this->employeeId)->findOrFail($this->accountId)->update($data);
        } else {
            $this->employee->accounts()->create($data);
        }

        $this->accountOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Cuenta de acceso guardada.');
    }

    public function confirmAccountDelete(int $id): void
    {
        $this->authorize('employees.edit');
        $this->confirmingAccountDeleteId = $id;
    }

    public function deleteAccount(): void
    {
        $this->authorize('employees.edit');
        EmployeeAccount::where('employee_id', $this->employeeId)->findOrFail($this->confirmingAccountDeleteId)->delete();
        $this->confirmingAccountDeleteId = null;
        $this->dispatch('toast', type: 'success', message: 'Cuenta eliminada.');
    }

    // ---- Acceso al portal ----
    public function openGrant(): void
    {
        $this->authorize('employees.edit');
        abort_unless(auth()->user()->can('users.create'), 403);

        $this->resetValidation();
        $this->accessRole = '';
        $this->accessEmail = $this->employee->email ?? '';
        $this->accessNotify = true;
        $this->granting = true;
    }

    public function grantAccess(PortalAccessService $portal): void
    {
        $this->authorize('employees.edit');
        abort_unless(auth()->user()->can('users.create'), 403);

        $employee = $this->employee;
        abort_if($employee->user_id, 422); // ya tiene acceso

        $this->validate([
            'accessEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'accessRole' => ['required', 'exists:roles,name'],
        ], [], ['accessEmail' => 'correo', 'accessRole' => 'rol']);

        $user = $portal->createUser($employee->name, $this->accessEmail, $this->accessRole);
        $employee->update(['user_id' => $user->id]);

        $this->granting = false;

        $message = 'Acceso al portal otorgado.';
        if ($this->accessNotify) {
            [$ok, $mailMsg] = $portal->sendWelcome($user, $this->accessRole);
            $message .= ' '.($ok ? 'Correo de acceso enviado.' : $mailMsg);
            $this->dispatch('toast', type: $ok ? 'success' : 'error', message: $message);

            return;
        }

        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function resendAccess(PortalAccessService $portal): void
    {
        $this->authorize('employees.edit');
        $user = $this->employee->user;
        abort_unless($user, 404);

        [$ok, $msg] = $portal->sendWelcome($user);
        $this->confirmingResend = false;
        $this->dispatch('toast', type: $ok ? 'success' : 'error', message: $msg);
    }

    public function confirmRevoke(): void
    {
        $this->authorize('employees.edit');
        abort_unless(auth()->user()->can('users.delete'), 403);
        $this->confirmingRevoke = true;
    }

    public function revokeAccess(PortalAccessService $portal): void
    {
        $this->authorize('employees.edit');
        abort_unless(auth()->user()->can('users.delete'), 403);

        $employee = $this->employee;
        $user = $employee->user;

        if ($user && ! $user->is_protected) {
            // Capturar datos antes de eliminar la cuenta para poder avisar.
            $name = $user->name;
            $email = $user->email;

            $employee->update(['user_id' => null]);
            $user->delete();
            $this->confirmingRevoke = false;
            $this->dispatch('toast', type: 'success', message: 'Acceso al portal revocado.');

            [$ok, $msg] = $portal->sendRevoked($name, $email);
            $this->dispatch('toast', type: $ok ? 'success' : 'error', message: $msg);

            return;
        }

        $this->confirmingRevoke = false;
        $this->dispatch('toast', type: 'error', message: 'No se pudo revocar (cuenta protegida o inexistente).');
    }

    #[On('employee-saved')]
    public function refreshAfterSave(): void
    {
        // Re-render tras editar datos del empleado.
    }

    public function render()
    {
        $employee = $this->employee;

        // Solo se consulta cuando el modal está abierto (evita trabajo por render).
        $availableLicenses = $this->assigningLicense
            ? \App\Models\License::withCount(['activeAssignments'])
                ->orderBy('software_name')->get()
                ->filter(fn ($l) => $l->seats - $l->active_assignments_count > 0)
                ->mapWithKeys(fn ($l) => [
                    $l->id => trim("{$l->software_name} {$l->version}").' · '.($l->seats - $l->active_assignments_count).' libre(s)',
                ])
            : collect();

        return view('livewire.admin.employees.employee-detail', [
            'employee' => $employee,
            'activities' => $employee->activities()->with('causer')->latest()->limit(50)->get(),
            'accountTypes' => EmployeeAccount::TYPES,
            'accountStatuses' => EmployeeAccount::STATUSES,
            'roles' => Role::orderBy('name')->pluck('name'),
            'availableLicenses' => $availableLicenses,
        ]);
    }
}
