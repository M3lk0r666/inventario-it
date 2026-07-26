<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Alertas de inventario TI</title></head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#191b23;">
    <div style="max-width:640px;margin:0 auto;padding:24px;">
        <div style="background:#003d9b;color:#fff;padding:16px 20px;border-radius:8px 8px 0 0;">
            <h1 style="margin:0;font-size:18px;">{{ $companyName }} — Alertas de inventario TI</h1>
            <p style="margin:4px 0 0;font-size:12px;opacity:.85;">{{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div style="background:#fff;border:1px solid #DFE1E6;border-top:0;border-radius:0 0 8px 8px;padding:20px;">

            @if ($licenseRenewals->isNotEmpty())
                <h2 style="font-size:15px;color:#003d9b;margin:0 0 8px;">Renovaciones de licencias ({{ $summary['license_renewals'] }})</h2>
                <table width="100%" cellpadding="6" style="border-collapse:collapse;font-size:13px;margin-bottom:20px;">
                    <tr style="background:#f3f3fd;text-align:left;"><th>Software</th><th>Renovar antes de</th></tr>
                    @foreach ($licenseRenewals as $lic)
                        <tr style="border-bottom:1px solid #eee;">
                            <td>{{ $lic->software_name }} {{ $lic->version }}</td>
                            <td>{{ $lic->renewal_date?->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @if ($warranties->isNotEmpty())
                <h2 style="font-size:15px;color:#003d9b;margin:0 0 8px;">Garantías por vencer ({{ $summary['warranties_expiring'] }})</h2>
                <table width="100%" cellpadding="6" style="border-collapse:collapse;font-size:13px;margin-bottom:20px;">
                    <tr style="background:#f3f3fd;text-align:left;"><th>Etiqueta</th><th>Equipo</th><th>Vence</th></tr>
                    @foreach ($warranties as $asset)
                        <tr style="border-bottom:1px solid #eee;">
                            <td>{{ $asset->asset_tag }}</td>
                            <td>{{ $asset->name }}</td>
                            <td>{{ $asset->warranty_expires_at?->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @if ($lowStock->isNotEmpty())
                <h2 style="font-size:15px;color:#003d9b;margin:0 0 8px;">Stock bajo ({{ $summary['low_stock'] }})</h2>
                <table width="100%" cellpadding="6" style="border-collapse:collapse;font-size:13px;margin-bottom:20px;">
                    <tr style="background:#f3f3fd;text-align:left;"><th>Consumible</th><th>Existencia / mínimo</th></tr>
                    @foreach ($lowStock as $cons)
                        <tr style="border-bottom:1px solid #eee;">
                            <td>{{ $cons->name }}</td>
                            <td>{{ $cons->stock }} / {{ $cons->min_stock }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            <p style="font-size:12px;color:#737685;margin-top:16px;">
                Ingresa al portal para más detalle: <a href="{{ $appUrl }}" style="color:#0052cc;">{{ $appUrl }}</a>
            </p>
        </div>
        <p style="text-align:center;font-size:11px;color:#737685;margin-top:12px;">
            Correo automático generado por {{ config('app.name') }}. No responder.
        </p>
    </div>
</body>
</html>
