<?php

namespace App\Mail;

use App\Models\Setting;
use App\Support\MailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Aviso al usuario de que su acceso al portal fue revocado.
 */
class PortalAccessRevokedMail extends Mailable
{
    use Queueable;

    public function __construct(public string $userName) {}

    protected function vars(): array
    {
        return [
            '{empresa}' => Setting::get('company_name', config('app.name')),
            '{empleado}' => $this->userName,
        ];
    }

    public function envelope(): Envelope
    {
        $company = Setting::get('company_name', config('app.name'));
        $subject = MailTemplates::render('revoked', 'subject', $this->vars());

        return new Envelope(subject: "[{$company}] {$subject}");
    }

    public function content(): Content
    {
        $vars = $this->vars();

        return new Content(view: 'emails.portal-revoked', with: [
            'companyName' => Setting::get('company_name', config('app.name')),
            'supportEmail' => Setting::get('mail_from_address', config('mail.from.address')),
            'accent' => MailTemplates::accentColor(),
            'intro' => MailTemplates::render('revoked', 'intro', $vars),
            'note' => MailTemplates::render('revoked', 'note', $vars),
            'userName' => $this->userName,
        ]);
    }
}
