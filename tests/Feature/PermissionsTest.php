<?php

namespace Tests\Feature;

/**
 * Verifica que cada rol solo acceda a lo permitido.
 */
class PermissionsTest extends InventoryTestCase
{
    public function test_super_admin_accede_a_usuarios(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'))
            ->get('/admin/usuarios')->assertOk();
    }

    public function test_consulta_no_accede_a_usuarios(): void
    {
        $this->actingAs($this->userWithRole('Consulta'))
            ->get('/admin/usuarios')->assertForbidden();
    }

    public function test_consulta_ve_activos(): void
    {
        $this->actingAs($this->userWithRole('Consulta'))
            ->get('/admin/activos')->assertOk();
    }

    public function test_tecnico_no_accede_a_reportes(): void
    {
        $this->actingAs($this->userWithRole('Técnico'))
            ->get('/admin/reportes')->assertForbidden();
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }
}
