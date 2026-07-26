<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetStatus;
use App\Models\Assignment;
use App\Models\Consumable;
use App\Models\ConsumableMovement;
use App\Models\Employee;
use App\Models\EmployeeAccount;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\License;
use App\Models\LicenseAssignment;
use App\Models\Problem;
use App\Models\ProblemNote;
use App\Models\Reminder;
use App\Models\ResponsiveLetter;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos demo realistas para probar cada fase.
 * Solo debe correrse en entornos de desarrollo.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Asegura que exista la base (catálogos, roles, usuarios, config).
        // Es idempotente (firstOrCreate), así que puede correrse tras el seed base.
        $this->call(ProductionSeeder::class);

        $admin = User::where('email', 'admin@inventario.test')->first();
        $tech = User::where('email', 'tecnico@inventario.test')->first();

        // (La configuración base de la plataforma se siembra en SettingSeeder,
        //  que corre también en producción.)

        // --- Empleados y cuentas ---
        $employees = Employee::factory()->count(15)->create();
        foreach ($employees->take(10) as $employee) {
            EmployeeAccount::create([
                'employee_id' => $employee->id,
                'account_type' => 'email',
                'identifier' => Str::before($employee->email, '@').'@miempresa.mx',
                'status' => 'active',
            ]);
            EmployeeAccount::create([
                'employee_id' => $employee->id,
                'account_type' => 'domain',
                'identifier' => 'MIEMPRESA\\'.Str::slug(Str::words($employee->name, 2, ''), '.'),
                'status' => $employee->status === 'active' ? 'active' : 'revoked',
            ]);
        }

        // --- Activos ---
        $assets = Asset::factory()->count(40)->create();

        // --- Asignaciones: históricas (devueltas) y activas con carta ---
        $assignedStatus = AssetStatus::where('slug', 'asignado')->first();
        $activeEmployees = $employees->where('status', 'active')->values();
        $assignable = $assets->filter(fn ($a) => $a->status?->is_assignable)->values();
        $folio = 1;

        // Históricas: primeros 8 activos pasaron por otro empleado y fueron devueltos
        foreach ($assignable->take(8) as $asset) {
            Assignment::create([
                'asset_id' => $asset->id,
                'employee_id' => $activeEmployees->random()->id,
                'assigned_at' => now()->subMonths(rand(10, 20)),
                'returned_at' => now()->subMonths(rand(4, 9)),
                'condition_on_assign' => 'Bueno',
                'condition_on_return' => fake()->randomElement(['Bueno', 'Con detalles estéticos', 'Requiere revisión']),
                'assigned_by' => $admin?->id,
                'received_by' => $tech?->id,
            ]);
        }

        // Activas: 12 activos asignados hoy, con carta responsiva
        foreach ($assignable->take(12) as $asset) {
            $employee = $activeEmployees->random();

            $letter = ResponsiveLetter::create([
                'folio' => sprintf('CR-%s-%04d', now()->year, $folio++),
                'employee_id' => $employee->id,
                'issued_at' => now()->subMonths(rand(0, 3)),
                'status' => fake()->randomElement(['generated', 'signed']),
                'created_by' => $admin?->id,
            ]);

            Assignment::create([
                'asset_id' => $asset->id,
                'employee_id' => $employee->id,
                'responsive_letter_id' => $letter->id,
                'assigned_at' => $letter->issued_at,
                'condition_on_assign' => 'Bueno',
                'assigned_by' => $admin?->id,
            ]);

            if ($assignedStatus) {
                $asset->update(['asset_status_id' => $assignedStatus->id]);
            }
        }
        Setting::set('letter_next_number', (string) $folio);

        // --- Consumibles y movimientos ---
        $consumables = Consumable::factory()->count(10)->create();
        foreach ($consumables as $consumable) {
            $in = rand(10, 40);
            $out = rand(0, min(8, $in));
            ConsumableMovement::create([
                'consumable_id' => $consumable->id,
                'type' => 'in',
                'quantity' => $in,
                'user_id' => $admin?->id,
                'unit_cost' => fake()->randomFloat(2, 50, 1500),
                'moved_at' => now()->subMonths(2),
                'notes' => 'Compra inicial',
            ]);
            if ($out > 0) {
                ConsumableMovement::create([
                    'consumable_id' => $consumable->id,
                    'type' => 'out',
                    'quantity' => $out,
                    'employee_id' => $activeEmployees->random()->id,
                    'user_id' => $tech?->id,
                    'moved_at' => now()->subWeeks(rand(1, 6)),
                ]);
            }
            $consumable->update(['stock' => $in - $out]);
        }

        // --- Licencias y asignaciones ---
        $licenses = License::factory()->count(6)->create();
        foreach ($licenses as $license) {
            $seatsToUse = min($license->seats, rand(1, 5));
            for ($i = 0; $i < $seatsToUse; $i++) {
                $toAsset = (bool) rand(0, 1);
                LicenseAssignment::create([
                    'license_id' => $license->id,
                    'assignable_type' => $toAsset ? Asset::class : Employee::class,
                    'assignable_id' => $toAsset ? $assets->random()->id : $activeEmployees->random()->id,
                    'assigned_at' => now()->subMonths(rand(1, 12)),
                    'assigned_by' => $admin?->id,
                ]);
            }
        }

        // --- Problemas y notas ---
        $problems = Problem::factory()->count(12)->create();
        foreach ($problems->take(6) as $problem) {
            ProblemNote::create([
                'problem_id' => $problem->id,
                'user_id' => $tech?->id,
                'body' => '<p>Se realizó diagnóstico inicial. '.fake()->sentence(8).'</p>',
            ]);
        }

        // --- Recordatorios ---
        Reminder::create([
            'title' => 'Renovación de antivirus',
            'body' => 'Cotizar renovación anual con el proveedor.',
            'starts_at' => now()->addWeeks(2),
            'ends_at' => now()->addWeeks(3),
            'visibility' => 'public',
            'user_id' => $admin->id,
        ]);
        Reminder::create([
            'title' => 'Inventario físico semestral',
            'body' => 'Programar conteo físico en Almacén de TI.',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDays(3),
            'visibility' => 'public',
            'user_id' => $admin->id,
        ]);

        // --- Base de conocimientos ---
        $categories = collect(['Redes', 'Impresoras', 'Cuentas y accesos', 'Equipos de cómputo'])
            ->map(fn ($name) => KbCategory::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]));

        foreach ([
            ['Cómo conectar una impresora en red', 'Impresoras'],
            ['Alta de cuenta VPN para empleados', 'Cuentas y accesos'],
            ['Checklist de entrega de laptop', 'Equipos de cómputo'],
            ['Configuración de Wi-Fi corporativo', 'Redes'],
        ] as [$title, $categoryName]) {
            KbArticle::firstOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'kb_category_id' => $categories->firstWhere('name', $categoryName)->id,
                    'title' => $title,
                    'body' => '<h2>'.$title.'</h2><p>'.fake()->paragraph(4).'</p><ul><li>'.fake()->sentence().'</li><li>'.fake()->sentence().'</li></ul>',
                    'is_published' => true,
                    'user_id' => $admin?->id,
                ]
            );
        }
    }
}
