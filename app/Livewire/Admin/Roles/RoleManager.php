<?php

namespace App\Livewire\Admin\Roles;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Editor de matriz de permisos por rol (estilo GLPI): módulos × acciones
 * (ver/crear/editar/eliminar) + permisos especiales. El administrador
 * ajusta lo que cada rol puede hacer.
 */
class RoleManager extends Component
{
    use AuthorizesRequests;

    /** Módulos con acciones estándar. clave => etiqueta */
    public const MODULES = [
        'assets' => 'Activos',
        'employees' => 'Empleados',
        'assignments' => 'Asignaciones',
        'responsive_letters' => 'Cartas responsivas',
        'consumables' => 'Consumibles',
        'licenses' => 'Licencias',
        'problems' => 'Problemas',
        'suppliers' => 'Proveedores',
        'reminders' => 'Recordatorios',
        'kb' => 'Base de conocimientos',
        'catalogs' => 'Catálogos',
        'users' => 'Usuarios',
    ];

    public const ACTIONS = [
        'view' => 'Ver',
        'create' => 'Crear',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
    ];

    /** Permisos especiales: nombre => etiqueta */
    public const SPECIAL = [
        'assets.export' => 'Exportar activos',
        'assets.change_status' => 'Cambiar estado de activos',
        'responsive_letters.reprint' => 'Reimprimir cartas',
        'responsive_letters.cancel' => 'Anular cartas',
        'consumables.move' => 'Registrar movimientos de consumibles',
        'licenses.assign' => 'Asignar asientos de licencias',
        'reports.view' => 'Ver reportes',
        'reports.export' => 'Exportar reportes',
        'settings.view' => 'Ver configuración',
        'settings.edit' => 'Editar configuración',
        'activity.view' => 'Ver auditoría',
    ];

    #[Url]
    public ?int $roleId = null;

    /** @var array<int,bool> permission_id => otorgado */
    public array $granted = [];

    public string $newRoleName = '';

    public function mount(): void
    {
        $this->authorize('users.edit');
        $this->roleId ??= Role::orderBy('id')->value('id');
        $this->loadGranted();
    }

    public function updatedRoleId(): void
    {
        $this->loadGranted();
    }

    protected function loadGranted(): void
    {
        $this->granted = [];
        if ($this->roleId) {
            $ids = Role::findOrFail($this->roleId)->permissions->pluck('id');
            foreach ($ids as $id) {
                $this->granted[$id] = true;
            }
        }
    }

    public function save(): void
    {
        $this->authorize('users.edit');
        $role = Role::findOrFail($this->roleId);

        $ids = collect($this->granted)->filter()->keys()->all();
        $names = Permission::whereIn('id', $ids)->pluck('name')->all();
        $role->syncPermissions($names);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->dispatch('toast', type: 'success', message: "Permisos del rol {$role->name} actualizados.");
    }

    public function createRole(): void
    {
        $this->authorize('users.edit');
        $this->validate([
            'newRoleName' => ['required', 'string', 'max:100', 'unique:roles,name'],
        ], [], ['newRoleName' => 'nombre del rol']);

        $role = Role::create(['name' => $this->newRoleName, 'guard_name' => 'web']);
        $this->newRoleName = '';
        $this->roleId = $role->id;
        $this->loadGranted();
        $this->dispatch('toast', type: 'success', message: "Rol {$role->name} creado. Asigna sus permisos.");
    }

    public function render()
    {
        $permsByName = Permission::pluck('id', 'name'); // name => id
        $role = $this->roleId ? Role::find($this->roleId) : null;

        return view('livewire.admin.roles.role-manager', [
            'roles' => Role::orderBy('id')->get(),
            'role' => $role,
            'isSuperAdmin' => $role?->name === 'Super Admin',
            'permsByName' => $permsByName,
        ]);
    }
}
