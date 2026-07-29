<?php

namespace App\Support;

use App\Models\AdditionalItemType;
use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\AssetType;
use App\Models\Department;
use App\Models\KbCategory;
use App\Models\LicenseType;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\ProblemCategory;
use App\Models\Supplier;

/**
 * Definición central de los catálogos del sistema.
 *
 * Cada catálogo declara: modelo, columnas de tabla, campos de formulario
 * (con reglas de validación), campos únicos, generación de slug y
 * verificación de "en uso" para bloquear eliminaciones.
 *
 * Tipos de campo soportados por el formulario genérico:
 *  text | textarea | checkbox | select (options fijas) |
 *  select-catalog (FK a otro catálogo, con alta en línea) | json
 */
class CatalogRegistry
{
    public static function all(): array
    {
        return [
            'departamentos' => [
                'label' => 'Departamentos',
                'singular' => 'Departamento',
                'model' => Department::class,
                'with' => [],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                    ['label' => 'Clave', 'field' => 'code'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'quick' => true],
                    ['key' => 'code', 'label' => 'Clave interna', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:20']],
                ],
                'unique' => ['name'],
                'in_use' => fn ($m) => $m->employees()->exists(),
            ],
            'ubicaciones' => [
                'label' => 'Ubicaciones',
                'singular' => 'Ubicación',
                'model' => Location::class,
                'with' => [],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                    ['label' => 'Dirección', 'field' => 'address'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'quick' => true],
                    ['key' => 'address', 'label' => 'Dirección', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                    ['key' => 'notes', 'label' => 'Notas', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                ],
                'unique' => ['name'],
                'in_use' => fn ($m) => $m->assets()->exists() || $m->employees()->exists() || $m->consumables()->exists(),
            ],
            'fabricantes' => [
                'label' => 'Fabricantes',
                'singular' => 'Fabricante',
                'model' => Manufacturer::class,
                'with' => [],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'quick' => true],
                ],
                'unique' => ['name'],
                'in_use' => fn ($m) => $m->models()->exists(),
            ],
            'tipos-de-activo' => [
                'label' => 'Tipos de activo',
                'singular' => 'Tipo de activo',
                'model' => AssetType::class,
                'with' => [],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                    ['label' => 'Slug', 'field' => 'slug'],
                    ['label' => 'Icono', 'field' => 'icon', 'format' => 'icon'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'quick' => true],
                    ['key' => 'slug', 'label' => 'Slug', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'help' => 'Se genera automáticamente si se deja vacío.'],
                    ['key' => 'icon', 'label' => 'Icono (Remix Icon)', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:50'], 'placeholder' => 'ri-computer-line'],
                    ['key' => 'spec_fields', 'label' => 'Campos de especificaciones (JSON)', 'type' => 'json', 'rules' => ['nullable', 'json'], 'help' => 'Arreglo de objetos {key, label, type}. Define los campos dinámicos de la pestaña Especificaciones.'],
                ],
                'unique' => ['name', 'slug'],
                'slug' => ['field' => 'slug', 'from' => 'name'],
                'in_use' => fn ($m) => $m->assets()->exists() || $m->models()->exists(),
            ],
            'estados-de-activo' => [
                'label' => 'Estados de activo',
                'singular' => 'Estado de activo',
                'model' => AssetStatus::class,
                'with' => [],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                    ['label' => 'Color', 'field' => 'color', 'format' => 'badge'],
                    ['label' => 'Asignable', 'field' => 'is_assignable', 'format' => 'bool'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'quick' => true],
                    ['key' => 'slug', 'label' => 'Slug', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'help' => 'Se genera automáticamente si se deja vacío.'],
                    ['key' => 'color', 'label' => 'Color', 'type' => 'select', 'rules' => ['nullable', 'string'], 'options' => [
                        'green' => 'Verde', 'blue' => 'Azul', 'indigo' => 'Índigo',
                        'yellow' => 'Amarillo', 'red' => 'Rojo', 'gray' => 'Gris',
                    ]],
                    ['key' => 'is_assignable', 'label' => '¿Puede asignarse a empleados?', 'type' => 'checkbox', 'rules' => ['boolean']],
                ],
                'unique' => ['name', 'slug'],
                'slug' => ['field' => 'slug', 'from' => 'name'],
                'in_use' => fn ($m) => $m->assets()->exists(),
            ],
            'modelos' => [
                'label' => 'Modelos',
                'singular' => 'Modelo',
                'model' => AssetModel::class,
                'with' => ['manufacturer', 'type'],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                    ['label' => 'Fabricante', 'field' => 'manufacturer.name'],
                    ['label' => 'Tipo', 'field' => 'type.name'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    ['key' => 'manufacturer_id', 'label' => 'Fabricante', 'type' => 'select-catalog', 'catalog' => 'fabricantes', 'rules' => ['required', 'integer', 'exists:manufacturers,id']],
                    ['key' => 'asset_type_id', 'label' => 'Tipo de activo', 'type' => 'select-catalog', 'catalog' => 'tipos-de-activo', 'rules' => ['required', 'integer', 'exists:asset_types,id']],
                ],
                'unique' => [],
                // Un modelo no puede repetirse para el mismo fabricante.
                'unique_scoped' => ['field' => 'name', 'scope' => 'manufacturer_id'],
                'in_use' => fn ($m) => $m->assets()->exists(),
            ],
            'tipos-de-licencia' => [
                'label' => 'Tipos de licencia',
                'singular' => 'Tipo de licencia',
                'model' => LicenseType::class,
                'with' => [],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'quick' => true],
                ],
                'unique' => ['name'],
                'in_use' => fn ($m) => $m->licenses()->exists(),
            ],
            'categorias-de-problema' => [
                'label' => 'Categorías de problema',
                'singular' => 'Categoría de problema',
                'model' => ProblemCategory::class,
                'with' => [],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'quick' => true],
                ],
                'unique' => ['name'],
                'in_use' => fn ($m) => $m->problems()->exists(),
            ],
            'bienes-adicionales' => [
                'label' => 'Bienes adicionales',
                'singular' => 'Bien adicional',
                'model' => AdditionalItemType::class,
                'with' => [],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                    ['label' => 'Lleva dato', 'field' => 'requires_value', 'format' => 'bool'],
                    ['label' => 'Etiqueta del dato', 'field' => 'value_label'],
                    ['label' => 'Activo', 'field' => 'is_active', 'format' => 'bool'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'quick' => true],
                    ['key' => 'requires_value', 'label' => '¿Lleva un dato asociado? (extensión, correo…)', 'type' => 'checkbox', 'rules' => ['boolean']],
                    ['key' => 'value_label', 'label' => 'Etiqueta del dato', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100'], 'placeholder' => 'p.ej. Extensión, Correo', 'help' => 'Solo si lleva un dato asociado.'],
                    ['key' => 'is_active', 'label' => 'Disponible para asignar', 'type' => 'checkbox', 'rules' => ['boolean']],
                ],
                'unique' => ['name'],
                'in_use' => fn ($m) => $m->letterItems()->exists(),
            ],
            // Proveedores: solo para alta en línea ("+"); su módulo completo
            // (con datos de contacto) se construye en la Fase 8. 'menu' => false
            // lo oculta del submenú de Catálogos.
            'proveedores' => [
                'label' => 'Proveedores',
                'singular' => 'Proveedor',
                'model' => Supplier::class,
                'menu' => false,
                'with' => [],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'quick' => true],
                ],
                'unique' => ['name'],
                'in_use' => fn ($m) => $m->assets()->exists() || $m->licenses()->exists(),
            ],
            'listas-de-correo' => [
                'label' => 'Listas de correo',
                'singular' => 'Lista de correo',
                'model' => \App\Models\MailingList::class,
                'with' => [],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                    ['label' => 'Dirección', 'field' => 'email'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'quick' => true],
                    ['key' => 'email', 'label' => 'Dirección de la lista', 'type' => 'text', 'rules' => ['required', 'email', 'max:255'], 'quick' => true],
                ],
                'unique' => ['email'],
                'in_use' => fn ($m) => false,
            ],
            'categorias-kb' => [
                'label' => 'Categorías de KB',
                'singular' => 'Categoría de KB',
                'model' => KbCategory::class,
                'with' => [],
                'columns' => [
                    ['label' => 'Nombre', 'field' => 'name'],
                    ['label' => 'Slug', 'field' => 'slug'],
                ],
                'fields' => [
                    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'rules' => ['required', 'string', 'max:255'], 'quick' => true],
                    ['key' => 'slug', 'label' => 'Slug', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'help' => 'Se genera automáticamente si se deja vacío.'],
                ],
                'unique' => ['name', 'slug'],
                'slug' => ['field' => 'slug', 'from' => 'name'],
                'in_use' => fn ($m) => $m->articles()->exists(),
            ],
        ];
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, static::all());
    }

    /** Catálogos visibles en el submenú (excluye los marcados menu=false). */
    public static function menuItems(): array
    {
        return array_filter(static::all(), fn ($def) => $def['menu'] ?? true);
    }

    public static function get(string $key): array
    {
        abort_unless(static::has($key), 404);

        return static::all()[$key];
    }

    /** Opciones id => name de un catálogo (para selects). */
    public static function options(string $key): array
    {
        $def = static::get($key);

        return $def['model']::orderBy('name')->pluck('name', 'id')->all();
    }
}
