<div>
    @php($sections = [
        'info' => ['label' => 'Datos', 'icon' => 'ri-user-line', 'count' => null],
        'access' => ['label' => 'Acceso al portal', 'icon' => 'ri-login-box-line', 'count' => $employee->user_id ? 1 : null],
        'accounts' => ['label' => 'Cuentas de acceso', 'icon' => 'ri-shield-keyhole-line', 'count' => $employee->accounts->count()],
        'assets' => ['label' => 'Activos asignados', 'icon' => 'ri-computer-line', 'count' => $employee->assignments->whereNull('returned_at')->count()],
        'letters' => ['label' => 'Cartas responsivas', 'icon' => 'ri-file-text-line', 'count' => $employee->responsiveLetters->count()],
        'history' => ['label' => 'Histórico', 'icon' => 'ri-history-line', 'count' => $activities->count()],
    ])

    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-headline-md text-on-surface">{{ $employee->name }}</h2>
                @if ($employee->status === 'active')
                    <span class="chip-success">Activo</span>
                @else
                    <span class="chip-neutral">Inactivo</span>
                @endif
            </div>
            <p class="mt-1 text-body-md text-on-surface-variant">
                <span class="font-mono text-mono-sm">{{ $employee->employee_number }}</span>
                @if ($employee->position) · {{ $employee->position }} @endif
                @if ($employee->department) · {{ $employee->department->name }} @endif
            </p>
        </div>
        @can('employees.edit')
            <button type="button" class="btn-primary" onclick="Livewire.dispatch('open-employee-form', { id: {{ $employee->id }} })">
                <i class="ri-pencil-line"></i> Editar
            </button>
        @endcan
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[230px,1fr] gap-gutter items-start">
        {{-- Menú de secciones --}}
        <nav class="card overflow-hidden lg:sticky lg:top-20">
            <ul class="divide-y divide-border-soft">
                @foreach ($sections as $key => $section)
                    <li>
                        <button type="button" wire:click="$set('tab', '{{ $key }}')"
                            class="w-full flex items-center gap-2 px-4 py-2.5 text-body-md border-l-4 transition-colors text-left
                                {{ $tab === $key ? 'border-primary-container bg-surface-container-low text-primary font-bold' : 'border-transparent text-on-surface-variant hover:bg-surface-container-low' }}">
                            <i class="{{ $section['icon'] }}"></i>
                            <span class="flex-1">{{ $section['label'] }}</span>
                            @if ($section['count'] !== null && $section['count'] > 0)
                                <span class="chip-neutral !px-2 !py-0.5">{{ $section['count'] }}</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="card p-6 min-h-[400px]">
            {{-- DATOS --}}
            @if ($tab === 'info')
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    @foreach ([
                        'Número de empleado' => $employee->employee_number,
                        'Nombre' => $employee->name,
                        'Puesto' => $employee->position,
                        'Departamento' => $employee->department?->name,
                        'Ubicación' => $employee->location?->name,
                        'Correo' => $employee->email,
                        'Teléfono' => $employee->phone,
                        'Cuenta de sistema' => $employee->user?->name,
                    ] as $label => $value)
                        <div>
                            <dt class="text-label-md text-on-surface-variant uppercase tracking-wider">{{ $label }}</dt>
                            <dd class="mt-0.5 text-body-md text-on-surface">{{ $value ?? '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($employee->notes)
                    <div class="mt-6 border-t border-border-soft pt-4">
                        <dt class="text-label-md text-on-surface-variant uppercase tracking-wider">Notas</dt>
                        <dd class="mt-1 text-body-md text-on-surface whitespace-pre-line">{{ $employee->notes }}</dd>
                    </div>
                @endif

            {{-- ACCESO AL PORTAL --}}
            @elseif ($tab === 'access')
                @if ($employee->user)
                    <div class="flex items-start gap-3 rounded-lg border border-border-soft p-4 mb-4">
                        <div class="w-10 h-10 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                            <i class="ri-login-box-line"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-body-md text-on-surface">
                                Este empleado <strong>tiene acceso al portal</strong>.
                            </p>
                            <p class="text-body-sm text-on-surface-variant">
                                Usuario: {{ $employee->user->email }} ·
                                Rol: {{ $employee->user->getRoleNames()->implode(', ') ?: '—' }}
                                @if ($employee->user->is_protected) · <span class="chip-info">Protegida</span> @endif
                            </p>
                        </div>
                    </div>
                    @can('employees.edit')
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn-ghost" wire:click="$set('confirmingResend', true)">
                                <i class="ri-mail-send-line"></i> Reenviar acceso
                            </button>
                            @can('users.delete')
                                @unless ($employee->user->is_protected)
                                    <button type="button" class="btn-ghost !text-alert !border-alert/40 hover:!bg-alert/10" wire:click="confirmRevoke">
                                        <i class="ri-logout-box-line"></i> Revocar acceso
                                    </button>
                                @endunless
                            @endcan
                        </div>
                    @endcan
                @else
                    <div class="text-center py-8">
                        <i class="ri-login-box-line text-4xl text-outline"></i>
                        <p class="mt-2 text-body-md text-on-surface-variant">Este empleado no tiene acceso al portal.</p>
                        @can('employees.edit')
                            @can('users.create')
                                <button type="button" class="btn-primary mt-4" wire:click="openGrant">
                                    <i class="ri-user-add-line"></i> Otorgar acceso al portal
                                </button>
                            @else
                                <p class="mt-2 text-body-sm text-on-surface-variant">No tienes permiso para crear usuarios.</p>
                            @endcan
                        @endcan
                    </div>
                @endif

            {{-- CUENTAS DE ACCESO --}}
            @elseif ($tab === 'accounts')
                @can('employees.edit')
                    <div class="mb-4 flex justify-end">
                        <button type="button" class="btn-secondary" wire:click="openAccount()">
                            <i class="ri-add-line"></i> Nueva cuenta
                        </button>
                    </div>
                @endcan
                @if ($employee->accounts->isEmpty())
                    <p class="text-body-md text-on-surface-variant">Sin cuentas de acceso corporativas registradas.</p>
                @else
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left">
                            <thead class="bg-[#F9FAFB] border-b border-border-soft">
                                <tr>
                                    @foreach (['Tipo', 'Sistema', 'Identificador', 'Estado', ''] as $th)
                                        <th class="px-4 py-table-cell-padding text-label-md text-on-surface-variant uppercase tracking-wider">{{ $th }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-soft">
                                @foreach ($employee->accounts as $acc)
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-3 text-body-md">{{ $accountTypes[$acc->account_type] ?? $acc->account_type }}</td>
                                        <td class="px-4 py-3 text-body-md">{{ $acc->system_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-body-md font-mono text-mono-sm">{{ $acc->identifier }}</td>
                                        <td class="px-4 py-3">
                                            @php($sc = match ($acc->status) { 'active' => 'chip-success', 'suspended' => 'chip-warning', default => 'chip-alert' })
                                            <span class="{{ $sc }}">{{ $accountStatuses[$acc->status] ?? $acc->status }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @can('employees.edit')
                                                <button type="button" wire:click="openAccount({{ $acc->id }})" class="btn-icon" title="Editar"><i class="ri-pencil-line"></i></button>
                                                <button type="button" wire:click="confirmAccountDelete({{ $acc->id }})" class="p-1.5 text-outline rounded-lg hover:text-alert hover:bg-alert/10" title="Eliminar"><i class="ri-delete-bin-line"></i></button>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            {{-- ACTIVOS ASIGNADOS --}}
            @elseif ($tab === 'assets')
                @if ($employee->assignments->isEmpty())
                    <p class="text-body-md text-on-surface-variant">Este empleado no tiene ni ha tenido bienes asignados.</p>
                @else
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left">
                            <thead class="bg-[#F9FAFB] border-b border-border-soft">
                                <tr>
                                    @foreach (['Etiqueta', 'Activo', 'Tipo', 'Entrega', 'Devolución', 'Situación', 'Carta'] as $th)
                                        <th class="px-4 py-table-cell-padding text-label-md text-on-surface-variant uppercase tracking-wider">{{ $th }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-soft">
                                @foreach ($employee->assignments->sortByDesc('assigned_at') as $a)
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.assets.show', $a->asset_id) }}" class="text-mono-sm font-mono text-primary hover:underline">{{ $a->asset?->asset_tag }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-body-md">{{ $a->asset?->name }}</td>
                                        <td class="px-4 py-3 text-body-md">{{ $a->asset?->type?->name }}</td>
                                        <td class="px-4 py-3 text-body-md">{{ $a->assigned_at?->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-body-md">{{ $a->returned_at?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="{{ $a->returned_at ? 'chip-neutral' : 'chip-success' }}">{{ $a->returned_at ? 'Devuelto' : 'Activo' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-mono-sm font-mono">
                                            @if ($a->responsiveLetter)
                                                <a href="{{ route('admin.letters.pdf', $a->responsive_letter_id) }}" target="_blank" class="text-primary hover:underline">{{ $a->responsiveLetter->folio }}</a>
                                            @else — @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            {{-- CARTAS --}}
            @elseif ($tab === 'letters')
                @if ($employee->responsiveLetters->isEmpty())
                    <p class="text-body-md text-on-surface-variant">Sin cartas responsivas.</p>
                @else
                    <div class="divide-y divide-border-soft">
                        @foreach ($employee->responsiveLetters->sortByDesc('issued_at') as $letter)
                            <div class="py-3 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <a href="{{ route('admin.letters.pdf', $letter->id) }}" target="_blank" class="text-mono-sm font-mono text-primary hover:underline">{{ $letter->folio }}</a>
                                    <span class="ms-2 text-body-sm text-on-surface-variant">{{ $letter->issued_at?->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($letter->type === 'return')
                                        <span class="chip-warning">Recepción</span>
                                    @else
                                        <span class="chip-info">Entrega</span>
                                    @endif
                                    @php($lc = match ($letter->status) { 'signed' => 'chip-success', 'cancelled' => 'chip-alert', default => 'chip-neutral' })
                                    <span class="{{ $lc }}">{{ \App\Models\ResponsiveLetter::STATUSES[$letter->status] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            {{-- HISTÓRICO --}}
            @elseif ($tab === 'history')
                @if ($activities->isEmpty())
                    <p class="text-body-md text-on-surface-variant">Sin cambios registrados.</p>
                @else
                    <ol class="relative border-s border-border-soft ms-2 space-y-4">
                        @foreach ($activities as $activity)
                            <li class="ms-5">
                                <span class="absolute -start-[5px] mt-1.5 w-2.5 h-2.5 rounded-full bg-primary-container"></span>
                                <p class="text-body-md text-on-surface">
                                    <span class="font-medium">{{ $activity->causer?->name ?? 'Sistema' }}</span>
                                    @switch($activity->description)
                                        @case('created') creó el empleado @break
                                        @case('updated') actualizó el empleado @break
                                        @default {{ $activity->description }}
                                    @endswitch
                                </p>
                                <p class="text-body-sm text-outline">{{ $activity->created_at->format('d/m/Y H:i') }}</p>
                            </li>
                        @endforeach
                    </ol>
                @endif
            @endif
        </div>
    </div>

    {{-- Modal cuenta de acceso --}}
    @if ($accountOpen)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('accountOpen', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface mb-4">{{ $accountId ? 'Editar cuenta' : 'Nueva cuenta de acceso' }}</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Tipo <span class="text-error">*</span></label>
                            <select wire:model="account.account_type" class="form-input">
                                @foreach ($accountTypes as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Estado <span class="text-error">*</span></label>
                            <select wire:model="account.status" class="form-input">
                                @foreach ($accountStatuses as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Sistema (si aplica)</label>
                        <input type="text" wire:model="account.system_name" class="form-input" placeholder="ERP, VPN corporativa…">
                    </div>
                    <div>
                        <label class="form-label">Identificador / usuario <span class="text-error">*</span></label>
                        <input type="text" wire:model="account.identifier" class="form-input">
                        @error('account.identifier') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Notas</label>
                        <textarea wire:model="account.notes" rows="2" class="form-input"></textarea>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('accountOpen', false)">Cancelar</button>
                    <button type="button" class="btn-primary" wire:click="saveAccount" wire:loading.attr="disabled">Guardar</button>
                </div>
            </div>
        </div>
    @endif

    @if ($confirmingAccountDeleteId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('confirmingAccountDeleteId', null)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface">Eliminar cuenta</h3>
                <p class="mt-1 text-body-md text-on-surface-variant">¿Eliminar esta cuenta de acceso?</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('confirmingAccountDeleteId', null)">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="deleteAccount" wire:loading.attr="disabled">Eliminar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal otorgar acceso al portal --}}
    @if ($granting)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('granting', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface mb-1">Otorgar acceso al portal</h3>
                <p class="text-body-sm text-on-surface-variant mb-4">Se creará una cuenta de usuario vinculada a <strong>{{ $employee->name }}</strong> con el rol elegido.</p>
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Correo (usuario de acceso) <span class="text-error">*</span></label>
                        <input type="email" wire:model="accessEmail" class="form-input">
                        @error('accessEmail') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Rol <span class="text-error">*</span></label>
                        <select wire:model="accessRole" class="form-input">
                            <option value="">— Seleccionar rol —</option>
                            @foreach ($roles as $r)
                                <option value="{{ $r }}">{{ $r }}</option>
                            @endforeach
                        </select>
                        @error('accessRole') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <label class="inline-flex items-center gap-2 text-body-md text-on-surface">
                        <input type="checkbox" wire:model="accessNotify" class="rounded border-border-soft text-primary-container focus:ring-primary-container">
                        Enviar correo de acceso (enlace de contraseña, válido 24 h)
                    </label>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('granting', false)">Cancelar</button>
                    <button type="button" class="btn-primary" wire:click="grantAccess" wire:loading.attr="disabled" wire:target="grantAccess">Otorgar acceso</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Confirmar reenviar acceso --}}
    @if ($confirmingResend)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('confirmingResend', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface">Reenviar acceso</h3>
                <p class="mt-1 text-body-md text-on-surface-variant">Se enviará un nuevo correo con un enlace para establecer la contraseña (válido 24 h) a <strong>{{ $employee->user?->email }}</strong>.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('confirmingResend', false)">Cancelar</button>
                    <button type="button" class="btn-primary" wire:click="resendAccess" wire:loading.attr="disabled" wire:target="resendAccess">Reenviar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Confirmar revocar acceso --}}
    @if ($confirmingRevoke)
        <div class="fixed inset-0 z-[60] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('confirmingRevoke', false)"></div>
            <div class="relative bg-white rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.08)] border border-border-soft w-full max-w-md mx-4 p-6">
                <h3 class="text-title-md text-on-surface">Revocar acceso al portal</h3>
                <p class="mt-1 text-body-md text-on-surface-variant">Se eliminará la cuenta de usuario de <strong>{{ $employee->name }}</strong>. El empleado y su histórico se conservan.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" wire:click="$set('confirmingRevoke', false)">Cancelar</button>
                    <button type="button" class="btn-danger" wire:click="revokeAccess" wire:loading.attr="disabled">Revocar</button>
                </div>
            </div>
        </div>
    @endif
</div>
