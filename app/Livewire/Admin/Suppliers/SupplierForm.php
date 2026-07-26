<?php

namespace App\Livewire\Admin\Suppliers;

use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class SupplierForm extends Component
{
    use AuthorizesRequests;

    public bool $open = false;

    public ?int $editingId = null;

    public array $data = [];

    public ?int $confirmingDeleteId = null;

    public string $confirmingDeleteLabel = '';

    #[On('open-supplier-form')]
    public function openForm(?int $id = null): void
    {
        $this->authorize($id ? 'suppliers.edit' : 'suppliers.create');
        $this->resetValidation();

        $this->editingId = $id;
        $this->data = [
            'name' => null, 'rfc' => null, 'contact_name' => null, 'email' => null,
            'phone' => null, 'website' => null, 'address' => null, 'notes' => null,
        ];

        if ($id) {
            $s = Supplier::findOrFail($id);
            foreach (array_keys($this->data) as $key) {
                $this->data[$key] = $s->{$key};
            }
        }

        $this->open = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'suppliers.edit' : 'suppliers.create');

        $this->validate([
            'data.name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($this->editingId)],
            'data.rfc' => ['nullable', 'string', 'max:20'],
            'data.contact_name' => ['nullable', 'string', 'max:255'],
            'data.email' => ['nullable', 'email', 'max:255'],
            'data.phone' => ['nullable', 'string', 'max:30'],
            'data.website' => ['nullable', 'string', 'max:255'],
            'data.address' => ['nullable', 'string'],
            'data.notes' => ['nullable', 'string'],
        ], [], [
            'data.name' => 'nombre', 'data.rfc' => 'RFC', 'data.contact_name' => 'contacto',
            'data.email' => 'correo', 'data.phone' => 'teléfono', 'data.website' => 'sitio web',
        ]);

        if ($this->editingId) {
            Supplier::findOrFail($this->editingId)->update($this->data);
        } else {
            Supplier::create($this->data);
        }

        $this->open = false;
        $this->dispatch('supplier-saved');
        $this->dispatch('toast', type: 'success', message: 'Proveedor guardado correctamente.');
    }

    #[On('confirm-supplier-delete')]
    public function confirmDelete(int $id): void
    {
        $this->authorize('suppliers.delete');
        $s = Supplier::withCount(['assets', 'licenses'])->findOrFail($id);
        $this->confirmingDeleteId = $id;
        $this->confirmingDeleteLabel = $s->name;
    }

    public function delete(): void
    {
        $this->authorize('suppliers.delete');
        $s = Supplier::withCount(['assets', 'licenses'])->findOrFail($this->confirmingDeleteId);

        if ($s->assets_count > 0 || $s->licenses_count > 0) {
            $this->confirmingDeleteId = null;
            $this->dispatch('toast', type: 'error',
                message: 'No se puede eliminar: el proveedor tiene activos o licencias asociados.');

            return;
        }

        $s->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('supplier-saved');
        $this->dispatch('toast', type: 'success', message: 'Proveedor eliminado.');
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.suppliers.supplier-form');
    }
}
