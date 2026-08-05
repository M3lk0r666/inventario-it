<x-mail.shell :companyName="$companyName" subtitle="Base de conocimientos TI" :supportEmail="$supportEmail" :width="820"
    :footnote="'Enviado por '.$senderName.' desde '.$companyName.'. Si necesitas apoyo, contacta al área de TI.'">

    <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0 0 16px;line-height:1.25;">{{ $article->title }}</h1>

    @if ($customMessage)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;">
            <tr>
                <td style="background:#eef3fb;border-left:4px solid {{ $accent ?? '#0b56c4' }};border-radius:8px;padding:14px 16px;font-size:14px;line-height:1.6;color:#33415c;">
                    {{ $customMessage }}
                    <div style="margin-top:8px;font-size:12px;color:#6b7280;">— {{ $senderName }}</div>
                </td>
            </tr>
        </table>
    @endif

    {{-- Contenido del artículo (HTML enriquecido) --}}
    <div style="font-size:15px;line-height:1.7;color:#2b2f38;">
        {!! $article->body !!}
    </div>
</x-mail.shell>
