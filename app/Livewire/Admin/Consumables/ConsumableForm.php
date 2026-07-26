<?php

namespace App\Livewire\Admin\Consumables;

use App\Models\Consumable;
use App\Models\Supplier;
use App\Support\CatalogRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Alta/edición de consumibles en slide-over. El stock inicial se captura
 * al crear (genera un movimiento de entrada); después se ajusta vía kardex.
 */
class ConsumableForm extends Component
{
    use AuthorizesRequests;

    public bool $open = false;

    public ?int $editingId = null;

    public array $data = [];

    public ?int $confirmingDeleteId = null;

    public string $confirmingDeleteLabel = '';

    #[On('open-consumable-form')]
    public function openForm(?int $id = null): void
    {
        $this->authorize($id ? 'consumables.edit' : 'consumables.create');
        $this->resetValidation();

        $this->editingId = $id;
        $this->data = [
            'name' => null, 'description' => null, 'unit' => 'pieza',
            'min_stock' => 0, 'location_id' => null, 'supplier_id' => null,
            'initial_stock' => 0,
        ];

        if ($id) {
            $c = Consumable::findOrFail($id);
            foreach (['name', 'description', 'unit', 'min_stock', 'location_id', 'supplier_id'] as $key) {
                $this->data[$key] = $c->{$key};
            }
        }

        $this->open = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'consumables.edit' : 'consumables.create');

        $rules = [
            'data.name' => ['required', 'string', 'max:255'],
            'data.description' => ['nullable', 'string'],
            'data.unit' => ['required', 'string', 'max:30'],
            'data.min_stock' => ['required', 'integer', 'min:0'],
            'data.location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'data.supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
        ];
        if (! $this->editingId) {
            $rules['data.initial_stock'] = ['required', 'integer', 'min:0'];
        }

        $this->validate($rules, [], [
            'data.name' => 'nombre', 'data.unit' => 'unidad', 'data.min_stock' => 'mínimo',
            'data.location_id' => 'ubicación', 'data.supplier_id' => 'proveedor',
            'data.initial_stock' => 'existencia inicial',
        ]);

        $payload = collect($this->data)->only(['name', 'description', 'unit', 'min_stock', 'location_id', 'supplier_id'])->all();

        if ($this->editingId) {
            Consumable::findOrFail($this->editingId)->update($payload);
        } else {
            $initial = (int) $this->data['initial_stock'];
            $payload['stock'] = $initial;
            $consumable = Consumable::create($payload);

            if ($initial > 0) {
                $consumable->movements()->create([
                    'type' => 'in',
                    'quantity' => $initial,
                    'user_id' => auth()->id(),
                    'moved_at' => now(),
                    'notes' => 'Existencia inicial',
                ]);
            }
        }

        $this->open = false;
        $this->dispatch('consumable-saved');
        $this->dispatch('toast', type: 'success', message: 'Consumible guardado correctamente.');
    }

    #[On('confirm-consumable-delete')]
    public function confirmDelete(int $id): void
    {
        $this->authorize('consumables.delete');
        $c = Consumable::findOrFail($id);
        $this->confirmingDeleteId = $id;
        $this->confirmingDeleteLabel = $c->name;
    }

    public function delete(): void
    {
        $this->authorize('consumables.delete');
        Consumable::findOrFail($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('consumable-saved');
        $this->dispatch('toast', type: 'success', message: 'Consumible eliminado.');
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
            'ubicaciones' => $this->data['location_id'] = $id,
            'proveedores' => $this->data['supplier_id'] = $id,
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.admin.consumables.consumable-form', [
            'locations' => CatalogRegistry::options('ubicaciones'),
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
