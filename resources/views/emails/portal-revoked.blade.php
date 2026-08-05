<x-mail.shell :companyName="$companyName" subtitle="Acceso al portal revocado" :supportEmail="$supportEmail" :accent="$accent"
    footnote="Correo automático. Si consideras que se trata de un error, contacta al área de TI.">

    <p style="font-size:15px;margin:0 0 14px;">Hola <strong>{{ $userName }}</strong>,</p>
    <p style="margin:0 0 18px;">{!! nl2br(e($intro)) !!}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;">
        <tr>
            <td style="background:#eef3fb;border-left:4px solid {{ $accent }};border-radius:8px;padding:14px 16px;font-size:13px;line-height:1.6;color:#33415c;">
                {!! nl2br(e($note)) !!}
            </td>
        </tr>
    </table>
</x-mail.shell>
