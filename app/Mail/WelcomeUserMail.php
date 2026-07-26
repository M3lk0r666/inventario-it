<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Notifica a un usuario que se le otorgó acceso al portal, con el enlace
 * para establecer su contraseña.
 */
class WelcomeUserMail extends Mailable
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $roleName,
        public string $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        $company = Setting::get('company_name', config('app.name'));

        return new Envelope(subject: "[{$company}] Tu acceso al portal de Inventario TI");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.welcome-user', with: [
            'companyName' => Setting::get('company_name', config('app.name')),
            'portalUrl' => config('app.url'),
        ]);
    }
}
