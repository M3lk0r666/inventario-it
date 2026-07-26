<?php

namespace App\Console\Commands;

use App\Mail\AlertDigestMail;
use App\Services\AlertService;
use App\Services\MailConfigurator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Envía el digest de alertas (renovaciones, garantías, stock bajo) por correo
 * a los destinatarios configurados. Pensado para ejecutarse a diario vía cron.
 */
class SendAlertDigest extends Command
{
    protected $signature = 'alerts:digest {--force : Enviar aunque no haya alertas}';

    protected $description = 'Envía por correo el resumen de alertas de inventario';

    public function handle(AlertService $alerts): int
    {
        if (! MailConfigurator::isReady()) {
            $this->warn('Correo no configurado o deshabilitado (Configuración → Correo). No se envió.');

            return self::SUCCESS;
        }

        $summary = $alerts->summary();
        if (array_sum($summary) === 0 && ! $this->option('force')) {
            $this->info('Sin alertas pendientes. No se envió correo.');

            return self::SUCCESS;
        }

        $recipients = MailConfigurator::alertRecipients();
        if (empty($recipients)) {
            $this->warn('No hay destinatarios de alertas configurados (settings alert_recipients).');

            return self::SUCCESS;
        }

        MailConfigurator::apply();

        $mail = new AlertDigestMail(
            summary: $summary,
            licenseRenewals: $alerts->licenseRenewals(),
            warranties: $alerts->warrantiesExpiring(),
            lowStock: $alerts->lowStock(),
        );

        Mail::to($recipients)->send($mail);
        $this->info('Digest enviado a: '.implode(', ', $recipients));

        return self::SUCCESS;
    }
}
