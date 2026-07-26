<div>
    <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
        <div>
            <label class="form-label">Rol</label>
            <select wire:model.live="roleId" class="form-input !w-64">
                @foreach ($roles as $r)
                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        @can('users.edit')
            <div class="flex items-end gap-2">
                <div>
                    <label class="form-label">Nuevo rol</label>
                    <input type="text" wire:model="newRoleName" class="form-input !w-48" placeholder="Nombre del rol">
                    @error('newRoleName') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <button type="button" class="btn-ghost" wire:click="createRole"><i class="ri-add-line"></i> Crear rol</button>
            </div>
        @endcan
    </div>

    @if ($isSuperAdmin)
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-body-sm text-amber-800">
            <i class="ri-shield-star-line"></i>
            <span>El rol <strong>Super Admin</strong> tiene acceso total al sistema por diseño, sin importar esta matriz. Los cambios aquí no lo limitan.</span>
        </div>
    @endif

    {{-- Matriz módulo × acción --}}
    <div class="card overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-border-soft">
            <h3 class="text-title-md text-on-surface">Permisos por módulo</h3>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left">
                <thead class="bg-[#F9FAFB] border-b border-border-soft">
                    <tr>
                        <th class="px-4 py-table-cell-padding text-label-md text-on-surface-variant uppercase tracking-wider">Módulo</th>
                        @foreach (\App\Livewire\Admin\Roles\RoleManager::ACTIONS as $label)
                            <th class="px-4 py-table-cell-padding text-label-md text-on-surface-variant uppercase tracking-wider text-center">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-soft">
                    @foreach (\App\Livewire\Admin\Roles\RoleManager::MODULES as $modKey => $modLabel)
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-4 py-3 text-body-md text-on-surface font-medium">{{ $modLabel }}</td>
                            @foreach (array_keys(\App\Livewire\Admin\Roles\RoleManager::ACTIONS) as $action)
                                @php($permName = $modKey.'.'.$action)
                                @php($permId = $permsByName[$permName] ?? null)
                                <td class="px-4 py-3 text-center">
                                    @if ($permId)
                                        <input type="checkbox" wire:model="granted.{{ $permId }}"
                                            @disabled($isSuperAdmin)
                                            class="rounded border-border-soft text-primary-container focus:ring-primary-container">
                                    @else
                                        <span class="text-outline">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Permisos especiales --}}
    <div class="card p-4 mb-6">
        <h3 class="text-title-md text-on-surface mb-3">Permisos especiales</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach (\App\Livewire\Admin\Roles\RoleManager::SPECIAL as $permName => $label)
                @php($permId = $permsByName[$permName] ?? null)
                @if ($permId)
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-border-soft text-body-md cursor-pointer {{ ($granted[$permId] ?? false) ? 'bg-primary-fixed/30' : '' }}">
                        <input type="checkbox" wire:model="granted.{{ $permId }}"
                            @disabled($isSuperAdmin)
                            class="rounded border-border-soft text-primary-container focus:ring-primary-container">
                        {{ $label }}
                    </label>
                @endif
            @endforeach
        </div>
    </div>

    @can('users.edit')
        @unless ($isSuperAdmin)
            <div class="flex justify-end">
                <button type="button" class="btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                    <i class="ri-save-line"></i> Guardar permisos
                </button>
            </div>
        @endunless
    @endcan
</div>
