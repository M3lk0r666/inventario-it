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

        $models = [
            'Dell' => [['Latitude 5440', 'laptop'], ['OptiPlex 7010', 'desktop'], ['P2422H', 'monitor'], ['PowerEdge T150', 'server']],
            'HP' => [['EliteBook 840 G9', 'laptop'], ['ProDesk 400 G9', 'desktop'], ['LaserJet Pro M404dn', 'printer'], ['M24f', 'monitor']],
            'Lenovo' => [['ThinkPad L14 Gen 4', 'laptop'], ['ThinkCentre M70q', 'desktop']],
            'Apple' => [['MacBook Air M2', 'laptop'], ['iPad 10ª gen', 'tablet']],
            'Epson' => [['EcoTank L3250', 'printer']],
            'Brother' => [['HL-L2350DW', 'printer']],
            'Logitech' => [['MX Keys', 'peripheral'], ['M185', 'peripheral'], ['C920', 'peripheral']],
            'Samsung' => [['ViewFinity S6', 'monitor']],
            'LG' => [['24MK430H', 'monitor']],
            'Cisco' => [['Catalyst 1300-24T', 'network'], ['CP-8841', 'ip-phone']],
            'TP-Link' => [['Archer AX55', 'network']],
        ];

        foreach ($models as $manufacturerName => $items) {
            $manufacturer = Manufacturer::where('name', $manufacturerName)->first();
            foreach ($items as [$modelName, $typeSlug]) {
                AssetModel::firstOrCreate([
                    'name' => $modelName,
                    'manufacturer_id' => $manufacturer->id,
                ], [
                    'asset_type_id' => AssetType::where('slug', $typeSlug)->value('id'),
                ]);
            }
        }

        foreach ([
            ['name' => 'CompuMayor SA de CV', 'rfc' => 'CMA010203AB1', 'contact_name' => 'Laura Domínguez', 'email' => 'ventas@compumayor.mx', 'phone' => '5551234567'],
            ['name' => 'TecnoRed del Centro', 'rfc' => 'TRC050607CD2', 'contact_name' => 'Jorge Ramírez', 'email' => 'contacto@tecnored.mx', 'phone' => '5559876543'],
            ['name' => 'Soluciones Ofimáticas MX', 'rfc' => 'SOM080910EF3', 'contact_name' => 'Ana Castillo', 'email' => 'ana@ofimaticasmx.com', 'phone' => '8112345678'],
        ] as $supplier) {
            Supplier::firstOrCreate(['name' => $supplier['name']], $supplier);
        }

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
