@props([
    'companyName' => config('app.name'),
    'subtitle' => '',
    'supportEmail' => null,
    'width' => 640,
    'footnote' => null,
    'accent' => null,
])

@php($accent = $accent ?: \App\Support\MailTemplates::accentColor())
@php($footnote = $footnote ?: \App\Support\MailTemplates::footerText(['{empresa}' => $companyName]))

<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $subtitle ?: $companyName }}</title></head>
<body style="margin:0;padding:0;background:#eef0f4;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#191b23;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef0f4;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="{{ $width }}" cellpadding="0" cellspacing="0"
                    style="width:{{ $width }}px;max-width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(16,24,40,.10);">

                    {{-- Encabezado con distintivo --}}
                    <tr>
                        <td style="background:{{ $accent }};padding:26px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-right:14px;vertical-align:middle;">
                                        <div style="width:40px;height:40px;border:2px solid rgba(255,255,255,.9);border-radius:9px;">
                                            <div style="margin:8px 7px 0;height:3px;background:rgba(255,255,255,.95);border-radius:2px;"></div>
                                            <div style="margin:6px 7px 0;height:12px;border:2px solid rgba(255,255,255,.85);border-radius:2px;"></div>
                                        </div>
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <div style="font-size:22px;font-weight:700;color:#ffffff;line-height:1.1;">{{ $companyName }}</div>
                                    </td>
                                </tr>
                            </table>
                            @if ($subtitle)
                                <div style="margin-top:14px;font-size:18px;font-weight:600;color:#ffffff;">{{ $subtitle }}</div>
                            @endif
                        </td>
                    </tr>

                    {{-- Cuerpo (contenido propio de cada correo) --}}
                    <tr>
                        <td style="padding:28px 32px 8px;font-size:14px;line-height:1.65;color:#2b2f38;">
                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- Pie --}}
                    <tr>
                        <td style="background:#f5f6f8;padding:18px 32px;text-align:center;font-size:12px;color:#6b7280;line-height:1.7;">
                            {{ $footnote }}
                            @if ($supportEmail)
                                <br>Contacto TI: <a href="mailto:{{ $supportEmail }}" style="color:{{ $accent }};text-decoration:none;">{{ $supportEmail }}</a>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
