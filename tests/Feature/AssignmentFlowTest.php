<?php

namespace Tests\Feature;

use App\Livewire\Admin\Assignments\AssignmentForm;
use App\Livewire\Admin\Assignments\ReturnForm;
use App\Models\Asset;
use App\Models\AssetStatus;
use App\Models\Assignment;
use App\Models\Employee;
use Livewire\Livewire;

/**
 * Flujo crítico: asignación de un activo (con carta) y su devolución.
 */
class AssignmentFlowTest extends InventoryTestCase
{
    public function test_asignar_activo_genera_asignacion_carta_y_cambia_estado(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $employee = Employee::factory()->create(['status' => 'active']);
        $asset = Asset::factory()->create([
            'asset_status_id' => AssetStatus::where('slug', 'operativo')->value('id'),
        ]);

        Livewire::actingAs($admin)
            ->test(AssignmentForm::class)
            ->call('openForm')
            ->set('employeeId', $employee->id)
            ->call('addAsset', $asset->id)
            ->set('generateLetter', true)
            ->call('save')
            ->assertHasNoErrors();

        $assignment = Assignment::where('asset_id', $asset->id)->first();
        $this->assertNotNull($assignment);
        $this->assertNull($assignment->returned_at);
        $this->assertNotNull($assignment->responsive_letter_id);
        $this->assertEquals(
            AssetStatus::where('slug', 'asignado')->value('id'),
            $asset->fresh()->asset_status_id
        );
    }

    public function test_devolucion_marca_returned_y_cambia_estado(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $employee = Employee::factory()->create(['status' => 'active']);
        $asset = Asset::factory()->create([
            'asset_status_id' => AssetStatus::where('slug', 'asignado')->value('id'),
        ]);
        $assignment = Assignment::create([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'assigned_at' => now()->subDay(),
            'assigned_by' => $admin->id,
        ]);

        $resguardo = AssetStatus::where('slug', 'resguardo')->value('id');

        Livewire::actingAs($admin)
            ->test(ReturnForm::class)
            ->call('openForm', $assignment->id)
            ->set('returnedAt', now()->format('Y-m-d'))
            ->set('condition', 'Bueno')
            ->set('newStatusId', $resguardo)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNotNull($assignment->fresh()->returned_at);
        $this->assertEquals($resguardo, $asset->fresh()->asset_status_id);
    }
}
