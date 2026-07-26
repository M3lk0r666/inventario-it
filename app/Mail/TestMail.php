<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TestMail extends Mailable
{
    public function envelope(): Envelope
    {
        $company = Setting::get('company_name', config('app.name'));

        return new Envelope(subject: "[{$company}] Correo de prueba — Inventario TI");
    }

    public function content(): Content
    {
        return new Content(htmlString:
            '<div style="font-family:Arial,sans-serif;font-size:14px;color:#191b23;">'
            .'<h2 style="color:#003d9b;">Correo de prueba correcto ✔</h2>'
            .'<p>Si recibes este mensaje, la configuración SMTP de tu inventario TI funciona correctamente.</p>'
            .'<p style="color:#737685;font-size:12px;">Enviado el '.now()->format('d/m/Y H:i').'.</p>'
            .'</div>'
        );
    }
}
