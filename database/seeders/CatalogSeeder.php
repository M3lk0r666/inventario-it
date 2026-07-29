<?php

namespace Database\Seeders;

use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\AssetType;
use App\Models\Department;
use App\Models\LicenseType;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\ProblemCategory;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Sistemas', 'Recursos Humanos', 'Finanzas', 'Dirección General', 'Operaciones', 'Ventas', 'Compras'] as $name) {
            Department::firstOrCreate(['name' => $name]);
        }

        foreach ([
            ['name' => 'Oficina Central - Piso 1', 'address' => 'Av. Reforma 100, CDMX'],
            ['name' => 'Oficina Central - Piso 2', 'address' => 'Av. Reforma 100, CDMX'],
            ['name' => 'Almacén de TI', 'address' => 'Av. Reforma 100, CDMX'],
            ['name' => 'Sucursal Norte', 'address' => 'Av. Universidad 500, Monterrey'],
            ['name' => 'Sucursal Sur', 'address' => 'Blvd. Atlixco 300, Puebla'],
        ] as $location) {
            Location::firstOrCreate(['name' => $location['name']], $location);
        }

        foreach (['Dell', 'HP', 'Lenovo', 'Apple', 'Acer', 'Epson', 'Brother', 'Logitech', 'Samsung', 'LG', 'Cisco', 'TP-Link'] as $name) {
            Manufacturer::firstOrCreate(['name' => $name]);
        }

        $computerSpecs = [
            ['key' => 'cpu', 'label' => 'Procesador', 'type' => 'text'],
            ['key' => 'ram', 'label' => 'Memoria RAM', 'type' => 'text'],
            ['key' => 'storage', 'label' => 'Almacenamiento', 'type' => 'text'],
            ['key' => 'os', 'label' => 'Sistema operativo', 'type' => 'text'],
        ];

        foreach ([
            ['name' => 'Computadora de escritorio', 'slug' => 'desktop', 'icon' => 'ri-computer-line', 'spec_fields' => $computerSpecs],
            ['name' => 'Laptop', 'slug' => 'laptop', 'icon' => 'ri-macbook-line', 'spec_fields' => $computerSpecs],
            ['name' => 'Servidor', 'slug' => 'server', 'icon' => 'ri-server-line', 'spec_fields' => $computerSpecs],
            ['name' => 'Monitor', 'slug' => 'monitor', 'icon' => 'ri-tv-2-line', 'spec_fields' => [
                ['key' => 'size', 'label' => 'Tamaño', 'type' => 'text'],
                ['key' => 'resolution', 'label' => 'Resolución', 'type' => 'text'],
                ['key' => 'connectors', 'label' => 'Conectores', 'type' => 'text'],
            ]],
            ['name' => 'Impresora', 'slug' => 'printer', 'icon' => 'ri-printer-line', 'spec_fields' => [
                ['key' => 'technology', 'label' => 'Tecnología', 'type' => 'text'],
                ['key' => 'connectivity', 'label' => 'Conectividad', 'type' => 'text'],
            ]],
            ['name' => 'Periférico', 'slug' => 'peripheral', 'icon' => 'ri-mouse-line', 'spec_fields' => null],
            ['name' => 'Equipo de red', 'slug' => 'network', 'icon' => 'ri-router-line', 'spec_fields' => [
                ['key' => 'ports', 'label' => 'Puertos', 'type' => 'text'],
                ['key' => 'management_ip', 'label' => 'IP de administración', 'type' => 'text'],
            ]],
            ['name' => 'Teléfono IP', 'slug' => 'ip-phone', 'icon' => 'ri-phone-line', 'spec_fields' => null],
            ['name' => 'Tableta', 'slug' => 'tablet', 'icon' => 'ri-tablet-line', 'spec_fields' => $computerSpecs],
        ] as $type) {
            AssetType::firstOrCreate(['slug' => $type['slug']], $type);
        }

        foreach ([
            ['name' => 'Operativo', 'slug' => 'operativo', 'color' => 'green', 'is_assignable' => true],
            ['name' => 'En resguardo', 'slug' => 'resguardo', 'color' => 'blue', 'is_assignable' => true],
            ['name' => 'Asignado', 'slug' => 'asignado', 'color' => 'indigo', 'is_assignable' => false],
            ['name' => 'En reparación', 'slug' => 'reparacion', 'color' => 'yellow', 'is_assignable' => false],
            ['name' => 'Baja', 'slug' => 'baja', 'color' => 'red', 'is_assignable' => false],
            ['name' => 'Extraviado', 'slug' => 'extraviado', 'color' => 'gray', 'is_assignable' => false],
        ] as $status) {
            AssetStatus::firstOrCreate(['slug' => $status['slug']], $status);
        }

        // NOTA: los Modelos y Proveedores NO se siembran aquí (producción).
        // Se crean desde el portal según cada empresa; los datos de ejemplo
        // viven en DemoSeeder (solo entornos de prueba).

        foreach (['Perpetua', 'Suscripción anual', 'Suscripción mensual', 'OEM', 'Licencia por volumen'] as $name) {
            LicenseType::firstOrCreate(['name' => $name]);
        }

        foreach (['Hardware', 'Software', 'Red', 'Periféricos', 'Impresión', 'Otro'] as $name) {
            ProblemCategory::firstOrCreate(['name' => $name]);
        }

        foreach ([
            ['name' => 'Llave de acceso', 'requires_value' => false],
            ['name' => 'Control de acceso vehicular', 'requires_value' => false],
            ['name' => 'Huella de acceso a oficina', 'requires_value' => false],
            ['name' => 'Llaves de oficina', 'requires_value' => false],
            ['name' => 'Extensión Zoom', 'requires_value' => true, 'value_label' => 'Extensión'],
            ['name' => 'Correo institucional', 'requires_value' => true, 'value_label' => 'Correo'],
            ['name' => 'Tarjeta de presentación digital', 'requires_value' => false],
        ] as $item) {
            \App\Models\AdditionalItemType::firstOrCreate(['name' => $item['name']], $item);
        }
    }
}
