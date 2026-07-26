<?php

namespace App\Livewire\Admin\Consumables;

use App\Models\Consumable;
use App\Models\Employee;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Detalle de consumible con kardex (movimientos) y registro de entradas/salidas.
 * Cada movimiento ajusta el stock de forma atómica; una salida no puede
 * exceder la existencia disponible.
 */
class ConsumableDetail extends Component
{
    use AuthorizesRequests;

    public int $consumableId;

    // Registro de movimiento
    public bool $moving = false;

    public string $moveType = 'out';

    public int $quantity = 1;

    public ?int $employeeId = null;

    public ?float $unitCost = null;

    public string $moveNotes = '';

    public function mount(int $consumableId): void
    {
        $this->consumableId = $consumableId;
    }

    public function getConsumableProperty(): Consumable
    {
        return Consumable::with(['location', 'supplier'])->findOrFail($this->consumableId);
    }

    public function openMove(string $type): void
    {
        $this->authorize('consumables.edit');
        $this->reset('quantity', 'employeeId', 'unitCost', 'moveNotes');
        $this->resetValidation();
        $this->moveType = $type;
        $this->quantity = 1;
        $this->moving = true;
    }

    public function saveMove(): void
    {
        $this->authorize('consumables.edit');

        $this->validate([
            'moveType' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'employeeId' => ['nullable', 'integer', 'exists:employees,id'],
            'unitCost' => ['nullable', 'numeric', 'min:0'],
            'moveNotes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'quantity' => 'cantidad', 'employeeId' => 'destinatario', 'unitCost' => 'costo unitario',
        ]);

        $ok = DB::transaction(function () {
            $consumable = Consumable::lockForUpdate()->findOrFail($this->consumableId);

            if ($this->moveType === 'out' && $this->quantity > $consumable->stock) {
                return false;
            }

            $consumable->movements()->create([
                'type' => $this->moveType,
                'quantity' => $this->quantity,
                'employee_id' => $this->moveType === 'out' ? $this->employeeId : null,
                'user_id' => auth()->id(),
                'unit_cost' => $this->moveType === 'in' ? $this->unitCost : null,
                'moved_at' => now(),
                'notes' => $this->moveNotes ?: null,
            ]);

            $consumable->increment('stock', $this->moveType === 'in' ? $this->quantity : -$this->quantity);

            return true;
        });

        if (! $ok) {
            $this->addError('quantity', "No hay existencia suficiente (disponible: {$this->consumable->stock}).");

            return;
        }

        $this->moving = false;
        $this->dispatch('consumable-saved');
        $this->dispatch('toast', type: 'success',
            message: $this->moveType === 'in' ? 'Entrada registrada.' : 'Salida registrada.');
    }

    public function render()
    {
        $consumable = $this->consumable;

        return view('livewire.admin.consumables.consumable-detail', [
            'consumable' => $consumable,
            'movements' => $consumable->movements()->with(['employee', 'user'])->paginate(15),
            'employees' => Employee::where('status', 'active')->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
