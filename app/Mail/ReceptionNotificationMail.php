<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\Setting;
use App\Support\MailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aviso al empleado de que se registró la recepción (devolución) de bienes.
 */
class ReceptionNotificationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public Employee $employee,
        public Collection $assignments,
        public Collection $items,
        public ?string $folio,
        public string $returnedAt,
        public bool $toManager = false,
        public ?string $managerName = null,
    ) {}

    protected function vars(): array
    {
        return [
            '{empresa}' => Setting::get('company_name', config('app.name')),
            '{empleado}' => $this->employee->name ?? '',
            '{jefe}' => $this->managerName ?? '',
            '{folio}' => $this->folio ?? '',
            '{fecha}' => Carbon::parse($this->returnedAt)->format('d/m/Y'),
        ];
    }

    public function envelope(): Envelope
    {
        $company = Setting::get('company_name', config('app.name'));
        $ref = $this->folio ? " — folio {$this->folio}" : '';

        if ($this->toManager) {
            return new Envelope(subject: "[{$company}] Aviso de recepción de bienes de colaborador{$ref}");
        }

        $subject = MailTemplates::render('reception', 'subject', $this->vars());

        return new Envelope(subject: "[{$company}] {$subject}{$ref}");
    }

    public function content(): Content
    {
        $vars = $this->vars();

        return new Content(view: 'emails.reception', with: [
            'companyName' => Setting::get('company_name', config('app.name')),
            'supportEmail' => Setting::get('mail_from_address', config('mail.from.address')),
            'accent' => MailTemplates::accentColor(),
            'intro' => MailTemplates::render('reception', 'intro', $vars),
            'note' => MailTemplates::render('reception', 'note', $vars),
        ]);
    }
}
