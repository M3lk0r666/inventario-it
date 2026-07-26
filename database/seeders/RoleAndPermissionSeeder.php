<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Matriz de permisos por módulo. Nombres en inglés (código),
     * la traducción se maneja en la UI.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = [
            'assets', 'employees', 'assignments', 'responsive_letters', 'consumables',
            'licenses', 'problems', 'suppliers', 'reminders', 'kb', 'catalogs', 'users',
        ];

        $permissions = [];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        // Permisos especiales
        $permissions = array_merge($permissions, [
            'assets.export',
            'assets.change_status',
            'responsive_letters.reprint',
            'responsive_letters.cancel',
            'consumables.move',   // registrar entradas/salidas
            'licenses.assign',    // asignar/liberar asientos
            'reports.view',
            'reports.export',
            'settings.view',
            'settings.edit',
            'activity.view', // histórico de cambios (activitylog)
        ]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ---- Roles ----
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        $inventoryAdmin = Role::firstOrCreate(['name' => 'Administrador de Inventario']);
        $inventoryAdmin->syncPermissions(
            collect($permissions)->reject(fn ($p) => str_starts_with($p, 'users.') || $p === 'settings.edit')->all()
        );

        $technician = Role::firstOrCreate(['name' => 'Técnico']);
        $technician->syncPermissions([
            'assets.view', 'assets.edit', 'assets.change_status',
            'employees.view',
            'assignments.view', 'assignments.create',
            'responsive_letters.view',
            'consumables.view', 'consumables.create', 'consumables.edit', 'consumables.move',
            'licenses.view', 'licenses.assign',
            'problems.view', 'problems.create', 'problems.edit',
            'suppliers.view',
            'reminders.view', 'reminders.create', 'reminders.edit',
            'kb.view', 'kb.create', 'kb.edit',
            'catalogs.view',
            'activity.view',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'Consulta']);
        $viewer->syncPermissions([
            'assets.view', 'employees.view', 'assignments.view', 'responsive_letters.view',
            'consumables.view', 'licenses.view', 'problems.view', 'suppliers.view',
            'reminders.view', 'kb.view', 'catalogs.view', 'reports.view',
        ]);
    }
}
