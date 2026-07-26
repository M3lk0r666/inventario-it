<?php

namespace App\Livewire\Admin\Employees;

use App\Models\Employee;
use App\Models\User;
use App\Support\CatalogRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class EmployeeForm extends Component
{
    use AuthorizesRequests;

    public bool $open = false;

    public ?int $editingId = null;

    public array $data = [];

    public ?int $confirmingDeleteId = null;

    public string $confirmingDeleteLabel = '';

    #[On('open-employee-form')]
    public function openForm(?int $id = null): void
    {
        $this->authorize($id ? 'employees.edit' : 'employees.create');
        $this->resetValidation();

        $this->editingId = $id;
        $this->data = [
            'employee_number' => null, 'name' => null, 'position' => null,
            'department_id' => null, 'location_id' => null, 'email' => null,
            'phone' => null, 'status' => 'active', 'user_id' => null, 'notes' => null,
        ];

        if ($id) {
            $e = Employee::findOrFail($id);
            foreach (array_keys($this->data) as $key) {
                $this->data[$key] = $e->{$key};
            }
        } else {
            // Sugerir el consecutivo del último número de empleado registrado
            $this->data['employee_number'] = $this->nextEmployeeNumber();
        }

        $this->open = true;
    }

    /**
     * Siguiente número de empleado a partir del último registrado, conservando
     * prefijo y ceros a la izquierda (p.ej. EMP-0015 → EMP-0016). Verifica
     * unicidad incluyendo eliminados. Editable por el usuario.
     */
    protected function nextEmployeeNumber(): string
    {
        $last = Employee::withTrashed()->orderByDesc('id')->value('employee_number');

        if (! $last || ! preg_match('/^(.*?)(\d+)$/', $last, $m)) {
            return 'EMP-0001';
        }

        $prefix = $m[1];
        $width = strlen($m[2]);
        $n = (int) $m[2];

        do {
            $n++;
            $candidate = $prefix.str_pad((string) $n, $width, '0', STR_PAD_LEFT);
        } while (Employee::withTrashed()->where('employee_number', $candidate)->exists());

        return $candidate;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'employees.edit' : 'employees.create');

        $this->validate([
            'data.employee_number' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_number')->ignore($this->editingId)],
            'data.name' => ['required', 'string', 'max:255'],
            'data.position' => ['nullable', 'string', 'max:255'],
            'data.department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'data.location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'data.email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($this->editingId)],
            'data.phone' => ['nullable', 'string', 'max:30'],
            'data.status' => ['required', 'in:active,inactive'],
            'data.user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($this->editingId)],
            'data.notes' => ['nullable', 'string'],
        ], [], [
            'data.employee_number' => 'número de empleado', 'data.name' => 'nombre', 'data.position' => 'puesto',
            'data.department_id' => 'departamento', 'data.location_id' => 'ubicación', 'data.email' => 'correo',
            'data.phone' => 'teléfono', 'data.status' => 'estado', 'data.user_id' => 'cuenta de acceso',
        ]);

        if ($this->editingId) {
            Employee::findOrFail($this->editingId)->update($this->data);
        } else {
            Employee::create($this->data);
        }

        $this->open = false;
        $this->dispatch('employee-saved');
        $this->dispatch('toast', type: 'success', message: 'Empleado guardado correctamente.');
    }

    #[On('confirm-employee-delete')]
    public function confirmDelete(int $id): void
    {
        $this->authorize('employees.delete');
        $e = Employee::withCount('activeAssignments')->findOrFail($id);
        $this->confirmingDeleteId = $id;
        $this->confirmingDeleteLabel = $e->name;
    }

    public function delete(): void
    {
        $this->authorize('employees.delete');
        $e = Employee::withCount('activeAssignments')->findOrFail($this->confirmingDeleteId);

        if ($e->active_assignments_count > 0) {
            $this->confirmingDeleteId = null;
            $this->dispatch('toast', type: 'error',
                message: 'No se puede eliminar: el empleado tiene bienes asignados. Registra la devolución primero.');

            return;
        }

        $e->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('employee-saved');
        $this->dispatch('toast', type: 'success', message: 'Empleado eliminado.');
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    #[On('quick-created')]
    public function onQuickCreated(string $catalog, int $id): void
    {
        if (! $this->open) {
            return;
        }
        match ($catalog) {
            'departamentos' => $this->data['department_id'] = $id,
            'ubicaciones' => $this->data['location_id'] = $id,
            default => null,
        };
    }

    public function render()
    {
        // Usuarios sin empleado vinculado (o el actual)
        $linkedUserIds = Employee::whereNotNull('user_id')
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->pluck('user_id');

        return view('livewire.admin.employees.employee-form', [
            'departments' => CatalogRegistry::options('departamentos'),
            'locations' => CatalogRegistry::options('ubicaciones'),
            'users' => User::whereNotIn('id', $linkedUserIds)->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
