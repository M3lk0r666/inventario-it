<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // La página de bienvenida consulta la tabla settings; se necesita la BD migrada.
    use RefreshDatabase;

    /**
     * La página pública de bienvenida carga correctamente.
     */
    public function test_the_welcome_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Inventario TI', false);
    }
}
