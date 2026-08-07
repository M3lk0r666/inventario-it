<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $letter->folio }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #191b23; padding: 22px 30px; }
    .header { width: 100%; margin-bottom: 14px; }
    .header td { vertical-align: middle; }
    .doc-title { font-size: 13px; font-weight: bold; text-align: right; color: #191b23; }
    .folio { font-size: 10px; text-align: right; color: #434654; margin-top: 2px; }
    .rule { border: none; border-top: 2px solid #003d9b; margin: 8px 0 14px; }
    .cancelled { position: fixed; top: 42%; left: 14%; font-size: 84px; color: rgba(186, 26, 26, 0.16); transform: rotate(-22deg); font-weight: bold; }
    .section-title { font-size: 11px; font-weight: bold; margin: 14px 0 6px; color: #191b23; border-left: 4px solid #003d9b; padding-left: 6px; }
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table td { padding: 3px 6px; font-size: 10px; }
    .info-label { color: #434654; font-weight: bold; width: 150px; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th { background: #D9D9D9; color: #191b23; padding: 5px 6px; font-size: 9px; text-align: left; border: 1px solid #b7b7b7; }
    table.grid td { border: 1px solid #c3c6d6; padding: 5px 6px; font-size: 9px; }
    .center { text-align: center; }
    .intro { margin: 12px 0; text-align: justify; line-height: 1.5; }
    .note { margin-top: 10px; font-size: 9px; font-style: italic; color: #434654; }
    .signatures { width: 100%; margin-top: 54px; }
    .signatures td { width: 50%; text-align: center; padding: 0 26px; }
    .sign-line { border-top: 1px solid #191b23; padding-top: 4px; font-size: 9px; }
    .footer { position: fixed; bottom: 12px; left: 30px; right: 30px; font-size: 8px; color: #737685; text-align: center; }
</style>
</head>
<body>
    @php($isReturn = $letter->type === 'return')

    @if ($letter->status === 'cancelled')
        <div class="cancelled">ANULADA</div>
    @endif

    <table class="header">
        <tr>
            <td style="width: 58%">
                @if ($logoPath)
                    <img src="{{ $logoPath }}" style="height: 40px;">
                @else
                    <span style="font-size: 16px; font-weight: bold; color: #003d9b;">{{ $companyName }}</span>
                @endif
            </td>
            <td style="width: 42%">
                <div class="doc-title">
                    {{ mb_strtoupper($docTitle ?? ($isReturn ? 'Carta de Entrega de Bienes' : 'Carta de Aceptación de Bienes')) }}
                </div>
                <div class="folio">Folio: <strong>{{ $letter->folio }}</strong></div>
                <div class="folio">Fecha: {{ $letter->issued_at?->format('d/M/Y') }}</div>
            </td>
        </tr>
    </table>
    <hr class="rule">

    <div class="section-title">Información del Colaborador</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Nombre Completo:</td>
            <td>{{ $letter->employee->name }}</td>
            <td class="info-label">No. de Empleado:</td>
            <td>{{ $letter->employee->employee_number }}</td>
        </tr>
        <tr>
            <td class="info-label">Departamento:</td>
            <td>{{ $letter->employee->department?->name ?? '—' }}</td>
            <td class="info-label">Puesto:</td>
            <td>{{ $letter->employee->position ?? '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">Ubicación:</td>
            <td>{{ $letter->employee->location?->name ?? '—' }}</td>
            <td class="info-label">{{ $isReturn ? 'Fecha de Separación:' : 'Fecha de Ingreso:' }}</td>
            <td>{{ $letter->issued_at?->format('d/M/Y') }}</td>
        </tr>
        <tr>
            <td class="info-label">Jefe Inmediato:</td>
            <td>{{ $letter->employee->manager?->name ?? '—' }}</td>
            <td class="info-label">Correo Corporativo:</td>
            <td>{{ $letter->employee->email ?? '—' }}</td>
        </tr>
    </table>

    @if (! $isReturn && ($letter->employee->emergency_contact_name || $letter->employee->emergency_contact_phone))
        <div class="section-title">Contacto de Emergencia</div>
        <table class="info-table">
            <tr>
                <td class="info-label">Nombre:</td>
                <td>{{ $letter->employee->emergency_contact_name ?? '—' }}</td>
                <td class="info-label">Parentesco:</td>
                <td>{{ $letter->employee->emergency_contact_relationship ?? '—' }}</td>
            </tr>
            <tr>
                <td class="info-label">Teléfono:</td>
                <td colspan="3">{{ $letter->employee->emergency_contact_phone ?? '—' }}</td>
            </tr>
        </table>
    @endif

    @if ($introText)
        <p class="intro">{!! nl2br($introText) !!}</p>
    @endif

    <div class="section-title">{{ $isReturn ? 'Equipo recibido' : 'Equipo / Accesorios entregados' }}</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 4%">#</th>
                <th style="width: 20%">Equipo / Accesorio</th>
                <th style="width: 27%">Descripción / Marca</th>
                <th style="width: 18%">Número de serie</th>
                <th style="width: 13%">Etiqueta</th>
                <th style="width: 18%">{{ $isReturn ? 'Estado devolución' : 'Fecha asignación' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assignments as $i => $assignment)
                @php($asset = $assignment->asset)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $asset->type?->name }}</td>
                    <td>{{ $asset->name }} @if($asset->model) — {{ trim(($asset->model->manufacturer?->name ?? '').' '.$asset->model->name) }} @endif</td>
                    <td>{{ $asset->serial_number ?? '—' }}</td>
                    <td>{{ $asset->asset_tag }}</td>
                    <td>{{ $isReturn ? ($assignment->condition_on_return ?? '—') : $assignment->assigned_at?->format('d/M/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center">Sin equipo registrado.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($letter->items->isNotEmpty())
        <div class="section-title">Adicionales</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 70%">Tipo</th>
                    <th style="width: 15%">Dato</th>
                    <th style="width: 15%" class="center">Entregado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($letter->items as $item)
                    <tr>
                        <td>{{ $item->type?->name }}</td>
                        <td>{{ $item->value ?? '—' }}</td>
                        <td class="center">Sí</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($noteText)
        <p class="note">{!! nl2br($noteText) !!}</p>
    @endif

    @if ($letter->notes)
        <p class="intro"><strong>Observaciones:</strong> {{ $letter->notes }}</p>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <div class="sign-line">
                    <strong>{{ $letter->employee->name }}</strong><br>
                    {{ $isReturn ? 'Entrega de conformidad' : 'Recibe de conformidad' }}
                </div>
            </td>
            <td>
                <div class="sign-line">
                    <strong>{{ $letter->createdBy?->name ?? '' }}</strong><br>
                    {{ $isReturn ? 'Recibe' : 'Entrega' }} — {{ $companyName }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $companyName }} · Documento {{ $letter->folio }} generado por {{ config('app.name') }} el {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
