<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class UsersTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('name', 'asc')
            ->setSearchDebounce(400)
            ->setPerPageAccepted([10, 25, 50])
            ->setEmptyMessage('Sin usuarios.');
    }

    public function builder(): Builder
    {
        return User::query()->with('roles');
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'name')
                ->sortable()->searchable(),

            Column::make('Correo', 'email')
                ->sortable()->searchable(),

            Column::make('Roles')
                ->label(fn ($row) => $row->roles->isEmpty()
                    ? '<span class="chip-neutral">Sin rol</span>'
                    : $row->roles->map(fn ($r) => '<span class="chip-info">'.e($r->name).'</span>')->implode(' '))
                ->html(),

            Column::make('Cuenta', 'is_protected')
                ->format(fn ($value) => $value
                    ? '<span class="chip-info" title="Acceso de contingencia, no eliminable"><i class="ri-lock-2-line"></i> Protegida</span>'
                    : '<span class="text-body-sm text-on-surface-variant">—</span>')
                ->html(),

            Column::make('Acciones', 'id')
                ->format(fn ($value, $row) => view('admin.users.partials.actions', [
                    'id' => $value,
                    'protected' => (bool) $row->is_protected,
                    'isSelf' => $row->id === auth()->id(),
                ]))
                ->html(),
        ];
    }

    #[On('user-saved')]
    public function refreshAfterSave(): void
    {
        // Re-render.
    }
}
