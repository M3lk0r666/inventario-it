<?php

namespace App\Mail;

use App\Models\KbArticle;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class KbArticleMail extends Mailable
{
    use Queueable;

    public function __construct(
        public KbArticle $article,
        public string $senderName,
        public string $customMessage = '',
    ) {}

    public function envelope(): Envelope
    {
        $company = Setting::get('company_name', config('app.name'));

        return new Envelope(subject: "[{$company}] {$this->article->title}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.kb-article', with: [
            'companyName' => Setting::get('company_name', config('app.name')),
        ]);
    }
}
