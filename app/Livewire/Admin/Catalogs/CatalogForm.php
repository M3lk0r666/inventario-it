<?php

namespace App\Livewire\Admin\Catalogs;

use App\Support\CatalogRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Formulario genérico de catálogos en slide-over (alta/edición)
 * + confirmación de borrado. Patrón base de CRUDs del sistema.
 */
class CatalogForm extends Component
{
    use AuthorizesRequests;

    public string $catalog;

    public bool $open = false;

    public ?int $editingId = null;

    public array $data = [];

    public ?int $confirmingDeleteId = null;

    public string $confirmingDeleteLabel = '';

    #[On('open-catalog-form')]
    public function openForm(string $catalog, ?int $id = null): void
    {
        if ($catalog !== $this->catalog) {
            return;
        }

        $this->authorize($id ? 'catalogs.edit' : 'catalogs.create');
        $this->resetValidation();

        $def = CatalogRegistry::get($this->catalog);
        $this->editingId = $id;
        $this->data = [];

        foreach ($def['fields'] as $field) {
            $this->data[$field['key']] = $field['type'] === 'checkbox' ? false : null;
        }

        if ($id) {
            $model = $def['model']::findOrFail($id);
            foreach ($def['fields'] as $field) {
                $value = $model->{$field['key']};
                $this->data[$field['key']] = $field['type'] === 'json' && $value !== null
                    ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $value;
            }
        }

        $this->open = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'catalogs.edit' : 'catalogs.create');

        $def = CatalogRegistry::get($this->catalog);
        $validated = $this->validate($this->rules(), [], $this->validationAttributes())['data'];

        // Slug automático si aplica y quedó vacío
        if (isset($def['slug']) && blank($validated[$def['slug']['field']] ?? null)) {
            $validated[$def['slug']['field']] = Str::slug($validated[$def['slug']['from']]);
        }

        // Normalización por tipo de campo
        foreach ($def['fields'] as $field) {
            if ($field['type'] === 'json') {
                $validated[$field['key']] = filled($validated[$field['key']] ?? null)
                    ? json_decode($validated[$field['key']], true)
                    : null;
            }
            if ($field['type'] === 'checkbox') {
                $validated[$field['key']] = (bool) ($validated[$field['key']] ?? false);
            }
        }

        try {
            if ($this->editingId) {
                $def['model']::findOrFail($this->editingId)->update($validated);
            } else {
                $def['model']::create($validated);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Red de seguridad ante restricciones de BD (p.ej. duplicados).
            $this->dispatch('toast', type: 'error',
                message: 'No se pudo guardar: el registro podría estar duplicado o tener datos no válidos.');

            return;
        }

        $this->open = false;
        $this->dispatch('catalog-saved');
        $this->dispatch('toast', type: 'success', message: $def['singular'].' guardado correctamente.');
    }

    #[On('confirm-catalog-delete')]
    public function confirmDelete(string $catalog, int $id): void
    {
        if ($catalog !== $this->catalog) {
            return;
        }

        $this->authorize('catalogs.delete');
        $def = CatalogRegistry::get($this->catalog);
        $model = $def['model']::findOrFail($id);

        $this->confirmingDeleteId = $id;
        $this->confirmingDeleteLabel = $model->name ?? "#{$id}";
    }

    public function delete(): void
    {
        $this->authorize('catalogs.delete');

        $def = CatalogRegistry::get($this->catalog);
        $model = $def['model']::findOrFail($this->confirmingDeleteId);

        if (($def['in_use'])($model)) {
            $this->confirmingDeleteId = null;
            $this->dispatch('toast', type: 'error',
                message: 'No se puede eliminar: el registro está en uso por otros módulos.');

            return;
        }

        $model->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('catalog-saved');
        $this->dispatch('toast', type: 'success', message: $def['singular'].' eliminado.');
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    /** Al crear un registro en línea (quick-create), seleccionarlo en el campo correspondiente. */
    #[On('quick-created')]
    public function onQuickCreated(string $catalog, int $id): void
    {
        if (! $this->open) {
            return;
        }

        $def = CatalogRegistry::get($this->catalog);
        foreach ($def['fields'] as $field) {
            if ($field['type'] === 'select-catalog' && $field['catalog'] === $catalog) {
                $this->data[$field['key']] = $id;
            }
        }
    }

    protected function rules(): array
    {
        $def = CatalogRegistry::get($this->catalog);
        $table = (new $def['model'])->getTable();
        $rules = [];

        foreach ($def['fields'] as $field) {
            $fieldRules = $field['rules'];

            if (in_array($field['key'], $def['unique'] ?? [])) {
                $fieldRules[] = Rule::unique($table, $field['key'])->ignore($this->editingId);
            }

            // Unicidad compuesta (p.ej. modelo único por fabricante).
            if (isset($def['unique_scoped']) && $def['unique_scoped']['field'] === $field['key']) {
                $scope = $def['unique_scoped']['scope'];
                $fieldRules[] = Rule::unique($table, $field['key'])
                    ->where($scope, $this->data[$scope] ?? null)
                    ->ignore($this->editingId);
            }

            $rules["data.{$field['key']}"] = $fieldRules;
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $def = CatalogRegistry::get($this->catalog);
        $attributes = [];

        foreach ($def['fields'] as $field) {
            $attributes["data.{$field['key']}"] = mb_strtolower($field['label']);
        }

        return $attributes;
    }

    public function render()
    {
        $def = CatalogRegistry::get($this->catalog);
        $options = [];

        foreach ($def['fields'] as $field) {
            if ($field['type'] === 'select-catalog') {
                $options[$field['key']] = CatalogRegistry::options($field['catalog']);
            }
        }

        return view('livewire.admin.catalogs.catalog-form', [
            'def' => $def,
            'options' => $options,
        ]);
    }
}
