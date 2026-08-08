<?php

namespace App\Livewire\Admin\Assets;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AssetType;
use App\Models\Attachment;
use App\Models\Supplier;
use App\Support\CatalogRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Alta/edición de activos en slide-over: campos generales,
 * specs dinámicos según el tipo y carga de imágenes (attachments).
 */
class AssetForm extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public bool $open = false;

    public ?int $editingId = null;

    public array $data = [];

    public array $specs = [];

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $photos = [];

    public ?int $confirmingDeleteId = null;

    public string $confirmingDeleteLabel = '';

    #[On('open-asset-form')]
    public function openForm(?int $id = null): void
    {
        $this->authorize($id ? 'assets.edit' : 'assets.create');
        $this->resetValidation();
        $this->reset('photos');

        $this->editingId = $id;
        $this->data = [
            'asset_tag' => null, 'name' => null, 'asset_type_id' => null, 'asset_model_id' => null,
            'serial_number' => null, 'asset_status_id' => null, 'location_id' => null,
            'supplier_id' => null, 'purchase_date' => null, 'purchase_cost' => null,
            'warranty_expires_at' => null,
        ];
        $this->specs = [];

        if ($id) {
            $asset = Asset::findOrFail($id);
            foreach (array_keys($this->data) as $key) {
                $this->data[$key] = $asset->{$key};
            }
            $this->data['purchase_date'] = $asset->purchase_date?->format('Y-m-d');
            $this->data['warranty_expires_at'] = $asset->warranty_expires_at?->format('Y-m-d');
            $this->specs = $asset->specs ?? [];
        } else {
            // Sugerir el consecutivo del último número de inventario registrado
            $this->data['asset_tag'] = $this->nextAssetTag();
        }

        $this->open = true;
    }

    /**
     * Calcula el siguiente número de inventario a partir del último registrado
     * (incluye eliminados lógicamente para no repetir folios), conservando
     * prefijo y ceros a la izquierda. P.ej. INV-00040 → INV-00041.
     */
    protected function nextAssetTag(): ?string
    {
        $last = Asset::withTrashed()->orderByDesc('id')->value('asset_tag');

        if (! $last || ! preg_match('/^(.*?)(\d+)$/', $last, $m)) {
            return 'INV-00001';
        }

        $prefix = $m[1];
        $width = strlen($m[2]);
        $n = (int) $m[2];

        do {
            $n++;
            $candidate = $prefix.str_pad((string) $n, $width, '0', STR_PAD_LEFT);
        } while (Asset::withTrashed()->where('asset_tag', $candidate)->exists());

        return $candidate;
    }

    /** Al cambiar el tipo: limpiar modelo incompatible y specs que no aplican. */
    public function updatedData($value, string $key): void
    {
        if ($key !== 'asset_type_id') {
            return;
        }

        if ($this->data['asset_model_id']) {
            $model = AssetModel::find($this->data['asset_model_id']);
            if ($model && (int) $model->asset_type_id !== (int) $value) {
                $this->data['asset_model_id'] = null;
            }
        }

        $validKeys = collect($this->specFields())->pluck('key')->all();
        $this->specs = array_intersect_key($this->specs, array_flip($validKeys));
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'assets.edit' : 'assets.create');

        $validated = $this->validate($this->rules(), [], $this->validationAttributes());

        $payload = $validated['data'];
        $payload['specs'] = collect($this->specs)
            ->filter(fn ($v) => filled($v))
            ->all() ?: null;

        try {
            if ($this->editingId) {
                $asset = Asset::findOrFail($this->editingId);
                $asset->update($payload);
            } else {
                $asset = Asset::create($payload);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Red de seguridad: p.ej. etiqueta de inventario duplicada.
            $this->addError('data.asset_tag', 'No se pudo guardar. Verifica que la etiqueta de inventario no esté repetida.');
            $this->dispatch('toast', type: 'error', message: 'No se pudo guardar el activo: posible dato duplicado.');

            return;
        }

        foreach ($this->photos as $photo) {
            $path = $photo->store("assets/{$asset->id}", 'public');
            $asset->attachments()->create([
                'disk' => 'public',
                'file_path' => $path,
                'file_name' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getMimeType(),
                'size' => $photo->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        $this->open = false;
        $this->reset('photos');
        $this->dispatch('asset-saved');
        $this->dispatch('toast', type: 'success', message: "Activo {$asset->asset_tag} guardado correctamente.");
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $this->authorize('assets.edit');

        $attachment = Attachment::where('attachable_type', Asset::class)
            ->where('attachable_id', $this->editingId)
            ->findOrFail($attachmentId);

        Storage::disk($attachment->disk)->delete($attachment->file_path);
        $attachment->delete();
        $this->dispatch('toast', type: 'success', message: 'Imagen eliminada.');
    }

    #[On('confirm-asset-delete')]
    public function confirmDelete(int $id): void
    {
        $this->authorize('assets.delete');
        $asset = Asset::findOrFail($id);

        $this->confirmingDeleteId = $id;
        $this->confirmingDeleteLabel = "{$asset->asset_tag} — {$asset->name}";
    }

    public function delete(): void
    {
        $this->authorize('assets.delete');
        $asset = Asset::with('currentAssignment')->findOrFail($this->confirmingDeleteId);

        if ($asset->currentAssignment) {
            $this->confirmingDeleteId = null;
            $this->dispatch('toast', type: 'error',
                message: 'No se puede eliminar: el activo tiene una asignación activa. Registra la devolución primero.');

            return;
        }

        $asset->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('asset-saved');
        $this->dispatch('toast', type: 'success', message: 'Activo eliminado (borrado lógico).');
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    /** Selección en línea desde quick-create de catálogos. */
    #[On('quick-created')]
    public function onQuickCreated(string $catalog, int $id): void
    {
        if (! $this->open) {
            return;
        }

        match ($catalog) {
            'tipos-de-activo' => $this->data['asset_type_id'] = $id,
            'estados-de-activo' => $this->data['asset_status_id'] = $id,
            'ubicaciones' => $this->data['location_id'] = $id,
            'proveedores' => $this->data['supplier_id'] = $id,
            default => null,
        };
    }

    /** Definición de campos de specs según el tipo elegido. */
    public function specFields(): array
    {
        if (blank($this->data['asset_type_id'] ?? null)) {
            return [];
        }

        return AssetType::find($this->data['asset_type_id'])?->spec_fields ?? [];
    }

    protected function rules(): array
    {
        return [
            'data.asset_tag' => ['required', 'string', 'max:50', Rule::unique('assets', 'asset_tag')->ignore($this->editingId)],
            'data.name' => ['required', 'string', 'max:255'],
            'data.asset_type_id' => ['required', 'integer', 'exists:asset_types,id'],
            'data.asset_model_id' => ['nullable', 'integer', 'exists:asset_models,id'],
            'data.serial_number' => ['nullable', 'string', 'max:255'],
            'data.asset_status_id' => ['required', 'integer', 'exists:asset_statuses,id'],
            'data.location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'data.supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'data.purchase_date' => ['nullable', 'date'],
            'data.purchase_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'data.warranty_expires_at' => ['nullable', 'date'],
            'specs.*' => ['nullable', 'string', 'max:255'],
            'photos.*' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'data.asset_tag' => 'etiqueta de inventario',
            'data.name' => 'nombre',
            'data.asset_type_id' => 'tipo de activo',
            'data.asset_model_id' => 'modelo',
            'data.serial_number' => 'número de serie',
            'data.asset_status_id' => 'estado',
            'data.location_id' => 'ubicación',
            'data.supplier_id' => 'proveedor',
            'data.purchase_date' => 'fecha de compra',
            'data.purchase_cost' => 'costo de compra',
            'data.warranty_expires_at' => 'garantía hasta',
            'photos.*' => 'imagen',
        ];
    }

    public function render()
    {
        $typeId = $this->data['asset_type_id'] ?? null;

        $models = AssetModel::with('manufacturer')
            ->when($typeId, fn ($q) => $q->where('asset_type_id', $typeId))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($m) => [$m->id => trim(($m->manufacturer?->name ?? '').' '.$m->name)]);

        $existingAttachments = $this->editingId
            ? Attachment::where('attachable_type', Asset::class)
                ->where('attachable_id', $this->editingId)
                ->where('mime_type', 'like', 'image/%')
                ->get()
            : collect();

        return view('livewire.admin.assets.asset-form', [
            'types' => CatalogRegistry::options('tipos-de-activo'),
            'statuses' => CatalogRegistry::options('estados-de-activo'),
            'locations' => CatalogRegistry::options('ubicaciones'),
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
            'models' => $models,
            'specFields' => $this->specFields(),
            'existingAttachments' => $existingAttachments,
        ]);
    }
}
