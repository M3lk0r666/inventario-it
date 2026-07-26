<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Acceso al portal</title></head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#191b23;">
    <div style="max-width:600px;margin:0 auto;padding:24px;">
        <div style="background:#003d9b;color:#fff;padding:16px 20px;border-radius:8px 8px 0 0;">
            <h1 style="margin:0;font-size:18px;">{{ $companyName }} — Inventario TI</h1>
        </div>
        <div style="background:#fff;border:1px solid #DFE1E6;border-top:0;border-radius:0 0 8px 8px;padding:24px;font-size:14px;line-height:1.6;">
            <p>Hola <strong>{{ $user->name }}</strong>,</p>
            <p>Se te otorgó acceso al portal de Inventario TI con el rol <strong>{{ $roleName }}</strong>.</p>
            <p><strong>Usuario:</strong> {{ $user->email }}</p>
            <p>Para ingresar, primero establece tu contraseña con el siguiente botón:</p>
            <p style="text-align:center;margin:24px 0;">
                <a href="{{ $resetUrl }}" style="background:#0052cc;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:bold;display:inline-block;">Establecer mi contraseña</a>
            </p>
            <p style="background:#fff4e5;border-left:4px solid #E87722;padding:10px 12px;font-size:12px;color:#7b4a00;">
                ⚠ Por seguridad, este enlace es válido únicamente durante las <strong>24 horas</strong> posteriores a la recepción de este correo. Si expira, solicita un nuevo enlace al área de TI.
            </p>
            <p style="font-size:12px;color:#737685;">Si el botón no funciona, copia y pega este enlace en tu navegador:<br>{{ $resetUrl }}</p>
            <p style="font-size:12px;color:#737685;">Portal: <a href="{{ $portalUrl }}" style="color:#0052cc;">{{ $portalUrl }}</a></p>
        </div>
        <p style="text-align:center;font-size:11px;color:#737685;margin-top:12px;">
            Correo automático de {{ config('app.name') }}. Si no esperabas este acceso, contacta al área de TI.
        </p>
    </div>
</body>
</html>
