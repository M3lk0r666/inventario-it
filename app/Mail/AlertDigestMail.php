<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class AlertDigestMail extends Mailable
{
    use Queueable;

    public function __construct(
        public array $summary,
        public Collection $licenseRenewals,
        public Collection $warranties,
        public Collection $lowStock,
    ) {}

    public function envelope(): Envelope
    {
        $company = Setting::get('company_name', config('app.name'));

        return new Envelope(subject: "[{$company}] Alertas de inventario TI — ".now()->format('d/m/Y'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.alert-digest', with: [
            'companyName' => Setting::get('company_name', config('app.name')),
            'appUrl' => config('app.url'),
        ]);
    }
}
