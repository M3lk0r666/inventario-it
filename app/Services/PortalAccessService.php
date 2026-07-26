<?php

namespace App\Services;

use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Alta y notificación de acceso al portal (usuarios), reutilizable desde
 * la gestión de usuarios y desde la ficha del empleado.
 */
class PortalAccessService
{
    /**
     * Crea una cuenta de usuario con rol y contraseña aleatoria (el usuario
     * la define con el enlace del correo). Devuelve el usuario.
     */
    public function createUser(string $name, string $email, string $role): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt(Str::password(40)),
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([$role]);

        return $user;
    }

    /**
     * Envía el correo de bienvenida con enlace (24 h) para establecer contraseña.
     * Devuelve [ok, mensaje].
     *
     * @return array{0:bool,1:string}
     */
    public function sendWelcome(User $user, ?string $roleName = null): array
    {
        if (blank($user->email)) {
            return [false, 'El usuario no tiene correo.'];
        }
        if (! MailConfigurator::isReady()) {
            return [false, 'El correo no está configurado (Configuración → Correo).'];
        }

        try {
            MailConfigurator::apply();
            $token = Password::broker()->createToken($user);
            $url = route('password.reset', ['token' => $token, 'email' => $user->email]);
            $role = $roleName ?? $user->getRoleNames()->first() ?? '—';

            Mail::to($user->email)->send(new WelcomeUserMail($user, $role, $url));

            return [true, "Correo de acceso enviado a {$user->email}."];
        } catch (\Throwable $e) {
            return [false, 'Falló el envío: '.$e->getMessage()];
        }
    }
}
