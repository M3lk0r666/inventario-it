<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

/**
 * Fija/restablece la contraseña de un usuario de forma interactiva y segura.
 *   php artisan user:password [correo]
 */
class SetUserPassword extends Command
{
    protected $signature = 'user:password {email? : Correo del usuario}';

    protected $description = 'Define o restablece la contraseña de un usuario';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Correo del usuario');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No existe un usuario con el correo {$email}.");

            return self::FAILURE;
        }

        $password = $this->secret('Nueva contraseña');
        $confirm = $this->secret('Confirmar contraseña');

        if ($password !== $confirm) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        $validator = Validator::make(['password' => $password], ['password' => ['required', Password::default()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $err) {
                $this->error($err);
            }

            return self::FAILURE;
        }

        $user->update(['password' => Hash::make($password)]);
        $this->info("Contraseña actualizada para {$user->name} ({$user->email}).");

        return self::SUCCESS;
    }
}
