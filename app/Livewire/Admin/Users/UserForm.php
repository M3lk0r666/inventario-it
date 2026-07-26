<?php

namespace App\Livewire\Admin\Users;

use App\Mail\WelcomeUserMail;
use App\Models\User;
use App\Services\MailConfigurator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserForm extends Component
{
    use AuthorizesRequests;

    public bool $open = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public ?string $role = null;

    public bool $notify = true;

    public ?int $confirmingDeleteId = null;

    public string $confirmingDeleteLabel = '';

    public ?int $confirmingResendId = null;

    public string $confirmingResendLabel = '';

    #[On('open-user-form')]
    public function openForm(?int $id = null): void
    {
        $this->authorize($id ? 'users.edit' : 'users.create');
        $this->resetValidation();
        $this->reset('name', 'email', 'password', 'role');
        $this->notify = true;

        $this->editingId = $id;
        if ($id) {
            $user = User::with('roles')->findOrFail($id);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->roles->first()?->name;
        }

        $this->open = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'users.edit' : 'users.create');

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId ? 'nullable' : 'required', 'nullable', Password::default()],
            'role' => ['required', 'exists:roles,name'],
        ], [], [
            'name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña', 'role' => 'rol',
        ]);

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->name = $this->name;
            $user->email = $this->email;
            if (filled($this->password)) {
                $user->password = Hash::make($this->password);
            }
            $user->save();
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'email_verified_at' => now(),
            ]);
        }

        $user->syncRoles([$this->role]);

        // Correo de bienvenida con enlace para establecer contraseña (solo al crear)
        $notified = false;
        if (! $this->editingId && $this->notify && filled($user->email)) {
            $notified = $this->sendWelcome($user, $this->role);
        }

        $this->open = false;
        $this->dispatch('user-saved');
        $this->dispatch('toast', type: 'success',
            message: 'Usuario guardado correctamente.'.($notified ? ' Se envió el correo de acceso.' : ''));
    }

    /** Envía el correo de bienvenida con enlace de contraseña. Devuelve si se envió. */
    protected function sendWelcome(User $user, ?string $roleName): bool
    {
        if (! MailConfigurator::isReady()) {
            $this->dispatch('toast', type: 'error', message: 'El correo no está configurado (Configuración → Correo).');

            return false;
        }

        try {
            MailConfigurator::apply();
            $token = PasswordBroker::broker()->createToken($user);
            $url = route('password.reset', ['token' => $token, 'email' => $user->email]);

            Mail::to($user->email)->send(new WelcomeUserMail($user, $roleName ?? '—', $url));

            return true;
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: 'Falló el envío: '.$e->getMessage());

            return false;
        }
    }

    /** Confirma el reenvío (modal Tailwind). */
    #[On('resend-user-access')]
    public function confirmResend(int $id): void
    {
        $this->authorize('users.edit');
        $user = User::findOrFail($id);
        $this->confirmingResendId = $id;
        $this->confirmingResendLabel = $user->email ?? $user->name;
    }

    /** Reenvía el correo de acceso (nuevo enlace de contraseña). */
    public function resendAccess(): void
    {
        $this->authorize('users.edit');
        $user = User::with('roles')->findOrFail($this->confirmingResendId);

        if (blank($user->email)) {
            $this->confirmingResendId = null;
            $this->dispatch('toast', type: 'error', message: 'El usuario no tiene correo.');

            return;
        }

        if ($this->sendWelcome($user, $user->roles->first()?->name)) {
            $this->dispatch('toast', type: 'success', message: "Acceso reenviado a {$user->email}.");
        }
        $this->confirmingResendId = null;
    }

    #[On('confirm-user-delete')]
    public function confirmDelete(int $id): void
    {
        $this->authorize('users.delete');
        $user = User::findOrFail($id);

        if ($guard = $this->deleteGuardMessage($user)) {
            $this->dispatch('toast', type: 'error', message: $guard);

            return;
        }

        $this->confirmingDeleteId = $id;
        $this->confirmingDeleteLabel = $user->name;
    }

    public function delete(): void
    {
        $this->authorize('users.delete');
        $user = User::findOrFail($this->confirmingDeleteId);

        if ($guard = $this->deleteGuardMessage($user)) {
            $this->confirmingDeleteId = null;
            $this->dispatch('toast', type: 'error', message: $guard);

            return;
        }

        // Desligar del empleado si estaba vinculado (no borrar el empleado)
        $user->employee()->update(['user_id' => null]);
        $user->delete();

        $this->confirmingDeleteId = null;
        $this->dispatch('user-saved');
        $this->dispatch('toast', type: 'success', message: 'Usuario eliminado.');
    }

    /** Devuelve un mensaje si el usuario NO puede eliminarse, o null si sí. */
    protected function deleteGuardMessage(User $user): ?string
    {
        if ($user->id === auth()->id()) {
            return 'No puedes eliminar tu propia cuenta.';
        }
        if ($user->is_protected) {
            return 'Esta cuenta está protegida (acceso de contingencia) y no puede eliminarse.';
        }
        if ($user->hasRole('Super Admin') && User::role('Super Admin')->count() <= 1) {
            return 'No puedes eliminar al único Super Admin del sistema.';
        }

        return null;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.users.user-form', [
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }
}
