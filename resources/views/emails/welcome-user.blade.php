<x-mail.shell :companyName="$companyName" subtitle="Acceso al portal — Inventario TI" :supportEmail="$supportEmail" :accent="$accent"
    footnote="Correo automático. Si no esperabas este acceso, contacta al área de TI.">

    <p style="font-size:15px;margin:0 0 14px;">Hola <strong>{{ $user->name }}</strong>,</p>
    <p style="margin:0 0 18px;">{!! nl2br(e($intro)) !!}</p>

    <p style="text-align:center;margin:22px 0;">
        <a href="{{ $resetUrl }}"
            style="background:{{ $accent }};color:#ffffff;text-decoration:none;padding:13px 26px;border-radius:8px;font-weight:700;display:inline-block;font-size:14px;">
            Establecer mi contraseña
        </a>
    </p>

    {{-- Nota (vigencia del enlace) --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;">
        <tr>
            <td style="background:#fff7ed;border-left:4px solid #ea9a2e;border-radius:8px;padding:12px 14px;font-size:12px;line-height:1.6;color:#7b4a00;">
                ⚠ {!! nl2br(e($note)) !!}
            </td>
        </tr>
    </table>

    <p style="font-size:12px;color:#737685;line-height:1.6;margin:0 0 6px;">
        Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
        <span style="word-break:break-all;">{{ $resetUrl }}</span>
    </p>
    <p style="font-size:12px;color:#737685;margin:0 0 12px;">
        Portal: <a href="{{ $portalUrl }}" style="color:{{ $accent }};text-decoration:none;">{{ $portalUrl }}</a>
    </p>
</x-mail.shell>
