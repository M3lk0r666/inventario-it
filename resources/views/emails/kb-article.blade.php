<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>{{ $article->title }}</title></head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#191b23;">
    <div style="max-width:680px;margin:0 auto;padding:24px;">
        <div style="background:#003d9b;color:#fff;padding:16px 20px;border-radius:8px 8px 0 0;">
            <p style="margin:0;font-size:12px;opacity:.85;">{{ $companyName }} · Base de conocimientos TI</p>
            <h1 style="margin:6px 0 0;font-size:20px;">{{ $article->title }}</h1>
        </div>
        <div style="background:#fff;border:1px solid #DFE1E6;border-top:0;border-radius:0 0 8px 8px;padding:24px;">
            @if ($customMessage)
                <div style="background:#f3f3fd;border-left:4px solid #0052cc;padding:12px 14px;margin-bottom:20px;font-size:14px;">
                    {{ $customMessage }}
                    <div style="margin-top:6px;font-size:12px;color:#737685;">— {{ $senderName }}</div>
                </div>
            @endif

            <div style="font-size:14px;line-height:1.6;">
                {!! $article->body !!}
            </div>
        </div>
        <p style="text-align:center;font-size:11px;color:#737685;margin-top:12px;">
            Enviado por {{ $senderName }} desde {{ config('app.name') }}. Si necesitas apoyo, contacta al área de TI.
        </p>
    </div>
</body>
</html>
