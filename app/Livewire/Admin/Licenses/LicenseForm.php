<?php

namespace App\Livewire\Admin\Licenses;

use App\Models\License;
use App\Models\Supplier;
use App\Support\CatalogRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class LicenseForm extends Component
{
    use AuthorizesRequests;

    public bool $open = false;

    public ?int $editingId = null;

    public array $data = [];

    public ?int $confirmingDeleteId = null;

    public string $confirmingDeleteLabel = '';

    #[On('open-license-form')]
    public function openForm(?int $id = null): void
    {
        $this->authorize($id ? 'licenses.edit' : 'licenses.create');
        $this->resetValidation();

        $this->editingId = $id;
        $this->data = [
            'software_name' => null, 'version' => null, 'license_type_id' => null,
            'supplier_id' => null, 'seats' => 1, 'product_key' => null,
            'purchase_date' => null, 'cost' => null, 'expires_at' => null, 'notes' => null,
            'renewal_date' => null, 'alerts_enabled' => true, 'alert_days_before' => 30,
        ];

        if ($id) {
            $l = License::findOrFail($id);
            foreach (array_keys($this->data) as $key) {
                $this->data[$key] = $l->{$key};
            }
            $this->data['purchase_date'] = $l->purchase_date?->format('Y-m-d');
            $this->data['expires_at'] = $l->expires_at?->format('Y-m-d');
            $this->data['renewal_date'] = $l->renewal_date?->format('Y-m-d');
        }

        $this->open = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'licenses.edit' : 'licenses.create');

        $usedSeats = $this->editingId
            ? License::findOrFail($this->editingId)->activeAssignments()->count()
            : 0;

        $this->validate([
            'data.software_name' => ['required', 'string', 'max:255'],
            'data.version' => ['nullable', 'string', 'max:50'],
            'data.license_type_id' => ['required', 'integer', 'exists:license_types,id'],
            'data.supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'data.seats' => ['required', 'integer', 'min:'.max(1, $usedSeats)],
            'data.product_key' => ['nullable', 'string'],
            'data.purchase_date' => ['nullable', 'date'],
            'data.cost' => ['nullable', 'numeric', 'min:0'],
            'data.expires_at' => ['nullable', 'date'],
            'data.renewal_date' => ['nullable', 'date'],
            'data.alerts_enabled' => ['boolean'],
            'data.alert_days_before' => ['required', 'integer', 'min:1', 'max:365'],
            'data.notes' => ['nullable', 'string'],
        ], [
            'data.seats.min' => "Los asientos no pueden ser menos que los ya asignados ({$usedSeats}).",
        ], [
            'data.software_name' => 'software', 'data.license_type_id' => 'tipo',
            'data.supplier_id' => 'proveedor', 'data.seats' => 'asientos', 'data.expires_at' => 'expiración',
        ]);

        if ($this->editingId) {
            License::findOrFail($this->editingId)->update($this->data);
        } else {
            License::create($this->data);
        }

        $this->open = false;
        $this->dispatch('license-saved');
        $this->dispatch('toast', type: 'success', message: 'Licencia guardada correctamente.');
    }

    #[On('confirm-license-delete')]
    public function confirmDelete(int $id): void
    {
        $this->authorize('licenses.delete');
        $l = License::findOrFail($id);
        $this->confirmingDeleteId = $id;
        $this->confirmingDeleteLabel = $l->software_name;
    }

    public function delete(): void
    {
        $this->authorize('licenses.delete');
        License::findOrFail($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('license-saved');
        $this->dispatch('toast', type: 'success', message: 'Licencia eliminada.');
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    #[On('quick-created')]
    public function onQuickCreated(string $catalog, int $id): void
    {
        if ($this->open && $catalog === 'proveedores') {
            $this->data['supplier_id'] = $id;
        }
    }

    public function render()
    {
        return view('livewire.admin.licenses.license-form', [
            'types' => CatalogRegistry::options('tipos-de-licencia'),
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
