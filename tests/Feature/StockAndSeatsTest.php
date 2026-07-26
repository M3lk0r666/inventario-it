<?php

namespace Tests\Feature;

use App\Livewire\Admin\Consumables\ConsumableDetail;
use App\Livewire\Admin\Licenses\LicenseDetail;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\License;
use Livewire\Livewire;

/**
 * Reglas de negocio: no exceder el stock de un consumible ni los asientos
 * de una licencia.
 */
class StockAndSeatsTest extends InventoryTestCase
{
    public function test_salida_no_puede_exceder_stock(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $consumable = Consumable::factory()->create(['stock' => 3, 'min_stock' => 1]);

        Livewire::actingAs($admin)
            ->test(ConsumableDetail::class, ['consumableId' => $consumable->id])
            ->call('openMove', 'out')
            ->set('quantity', 10)
            ->call('saveMove')
            ->assertHasErrors('quantity');

        $this->assertEquals(3, $consumable->fresh()->stock);
    }

    public function test_salida_valida_descuenta_stock(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $consumable = Consumable::factory()->create(['stock' => 5, 'min_stock' => 1]);

        Livewire::actingAs($admin)
            ->test(ConsumableDetail::class, ['consumableId' => $consumable->id])
            ->call('openMove', 'out')
            ->set('quantity', 2)
            ->call('saveMove')
            ->assertHasNoErrors();

        $this->assertEquals(3, $consumable->fresh()->stock);
    }

    public function test_no_se_pueden_exceder_asientos_de_licencia(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $license = License::factory()->create(['seats' => 1]);
        $asset1 = Asset::factory()->create();
        $asset2 = Asset::factory()->create();

        $component = Livewire::actingAs($admin)
            ->test(LicenseDetail::class, ['licenseId' => $license->id]);

        // Primer asiento: ok
        $component->call('openAssign', $license->id)
            ->set('target', 'asset')->set('targetId', $asset1->id)
            ->call('saveAssign')->assertHasNoErrors();

        $this->assertEquals(1, $license->activeAssignments()->count());

        // Segundo asiento: sin cupo, no debe crear otra asignación activa
        $component->call('openAssign', $license->id)
            ->set('target', 'asset')->set('targetId', $asset2->id)
            ->call('saveAssign');

        $this->assertEquals(1, $license->fresh()->activeAssignments()->count());
    }
}
