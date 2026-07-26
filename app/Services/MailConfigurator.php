<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

/**
 * Aplica la configuración de correo guardada en settings (SMTP de Office 365)
 * al mailer en tiempo de ejecución, para no depender del .env y poder
 * cambiarla desde Configuración (Fase 10).
 */
class MailConfigurator
{
    /** ¿Está el correo configurado y habilitado? */
    public static function isReady(): bool
    {
        return Setting::get('mail_enabled') === '1'
            && filled(Setting::get('mail_host'))
            && filled(Setting::get('mail_username'))
            && filled(Setting::get('mail_password'));
    }

    /** Vuelca los settings al config('mail') para el mailer SMTP. */
    public static function apply(): void
    {
        $fromAddress = Setting::get('mail_from_address') ?: Setting::get('mail_username');

        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => Setting::get('mail_host', 'smtp.office365.com'),
            'mail.mailers.smtp.port' => (int) Setting::get('mail_port', '587'),
            'mail.mailers.smtp.encryption' => Setting::get('mail_encryption', 'tls'),
            'mail.mailers.smtp.username' => Setting::get('mail_username'),
            'mail.mailers.smtp.password' => Setting::get('mail_password'),
            'mail.from.address' => $fromAddress,
            'mail.from.name' => Setting::get('mail_from_name', Setting::get('company_name', config('app.name'))),
        ]);
    }

    /** Destinatarios de las alertas (settings alert_recipients: separados por coma/; ). */
    public static function alertRecipients(): array
    {
        $raw = (string) Setting::get('alert_recipients', '');

        return collect(preg_split('/[,;\s]+/', $raw))
            ->map(fn ($e) => trim($e))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()->values()->all();
    }
}
