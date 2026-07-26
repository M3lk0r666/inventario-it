<?php

namespace App\Livewire\Shared;

use App\Support\CatalogRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Alta "en línea" de catálogos (botón + junto a un select, estilo GLPI).
 * Modal ligero con los campos mínimos del catálogo; al guardar notifica
 * al formulario que lo invocó para seleccionar el nuevo registro.
 */
class CatalogQuickCreate extends Component
{
    use AuthorizesRequests;

    public bool $open = false;

    public string $catalog = '';

    public array $data = [];

    #[On('open-quick-create')]
    public function openModal(string $catalog): void
    {
        $this->authorize('catalogs.create');

        CatalogRegistry::get($catalog); // 404 si no existe
        $this->catalog = $catalog;
        $this->resetValidation();
        $this->data = [];

        foreach ($this->quickFields() as $field) {
            $this->data[$field['key']] = null;
        }

        $this->open = true;
    }

    public function save(): void
    {
        $this->authorize('catalogs.create');

        $def = CatalogRegistry::get($this->catalog);
        $validated = $this->validate($this->rules(), [], $this->validationAttributes())['data'];

        if (isset($def['slug'])) {
            $validated[$def['slug']['field']] = Str::slug($validated[$def['slug']['from']]);
        }

        $model = $def['model']::create($validated);

        $this->open = false;
        $this->dispatch('quick-created', catalog: $this->catalog, id: $model->id);
        $this->dispatch('catalog-saved');
        $this->dispatch('toast', type: 'success', message: $def['singular'].' creado y seleccionado.');
    }

    protected function quickFields(): array
    {
        $def = CatalogRegistry::get($this->catalog);

        return array_values(array_filter($def['fields'], fn ($f) => $f['quick'] ?? false));
    }

    protected function rules(): array
    {
        $def = CatalogRegistry::get($this->catalog);
        $table = (new $def['model'])->getTable();
        $rules = [];

        foreach ($this->quickFields() as $field) {
            $fieldRules = $field['rules'];
            if (in_array($field['key'], $def['unique'] ?? [])) {
                $fieldRules[] = Rule::unique($table, $field['key']);
            }
            $rules["data.{$field['key']}"] = $fieldRules;
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $attributes = [];
        foreach ($this->quickFields() as $field) {
            $attributes["data.{$field['key']}"] = mb_strtolower($field['label']);
        }

        return $attributes;
    }

    public function render()
    {
        return view('livewire.shared.catalog-quick-create', [
            'def' => $this->catalog !== '' ? CatalogRegistry::get($this->catalog) : null,
            'fields' => $this->catalog !== '' ? $this->quickFields() : [],
        ]);
    }
}
