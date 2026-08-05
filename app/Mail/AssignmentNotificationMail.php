<?php

namespace App\Mail;

use App\Models\ResponsiveLetter;
use App\Models\Setting;
use App\Support\MailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Aviso al empleado de los bienes que se le asignaron (versión "digerible"
 * de la carta responsiva). Le informa que en breve recibirá su carta para firma.
 */
class AssignmentNotificationMail extends Mailable
{
    use Queueable;

    public function __construct(public ResponsiveLetter $letter, public bool $toManager = false) {}

    protected function vars(): array
    {
        $e = $this->letter->employee;

        return [
            '{empresa}' => Setting::get('company_name', config('app.name')),
            '{empleado}' => $e?->name ?? '',
            '{jefe}' => $e?->manager?->name ?? '',
            '{folio}' => $this->letter->folio,
            '{fecha}' => $this->letter->issued_at?->format('d/m/Y') ?? '',
        ];
    }

    public function envelope(): Envelope
    {
        $company = Setting::get('company_name', config('app.name'));

        if ($this->toManager) {
            return new Envelope(subject: "[{$company}] Aviso de asignación a colaborador — folio {$this->letter->folio}");
        }

        $subject = MailTemplates::render('assignment', 'subject', $this->vars());

        return new Envelope(subject: "[{$company}] {$subject}");
    }

    public function content(): Content
    {
        $this->letter->loadMissing(['employee.manager', 'assignments.asset.type', 'items.type']);
        $vars = $this->vars();

        return new Content(view: 'emails.assignment', with: [
            'companyName' => Setting::get('company_name', config('app.name')),
            'supportEmail' => Setting::get('mail_from_address', config('mail.from.address')),
            'accent' => MailTemplates::accentColor(),
            'intro' => MailTemplates::render('assignment', 'intro', $vars),
            'note' => MailTemplates::render('assignment', 'note', $vars),
            'letter' => $this->letter,
            'employee' => $this->letter->employee,
            'assignments' => $this->letter->assignments,
            'items' => $this->letter->items,
            'toManager' => $this->toManager,
            'managerName' => $this->letter->employee?->manager?->name,
        ]);
    }
}
