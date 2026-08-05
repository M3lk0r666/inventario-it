<x-mail.shell :companyName="$companyName" subtitle="Notificación de bienes asignados" :supportEmail="$supportEmail" :accent="$accent">
    @if (! empty($toManager))
        <p style="font-size:15px;margin:0 0 14px;">Hola <strong>{{ $managerName }}</strong>,</p>
        <p style="margin:0 0 22px;">
            Te informamos, como jefe inmediato, que a tu colaborador <strong>{{ $employee->name }}</strong>
            se le asignaron los siguientes bienes informáticos, amparados por la carta responsiva con folio
            <strong style="color:{{ $accent }};">{{ $letter->folio }}</strong>, con fecha
            <strong>{{ $letter->issued_at?->format('d/m/Y') }}</strong>.
        </p>
    @else
        <p style="font-size:15px;margin:0 0 14px;">Hola <strong>{{ $employee->name }}</strong>,</p>
        <p style="margin:0 0 22px;">{!! nl2br(e($intro)) !!}</p>
    @endif

    {{-- Equipos --}}
    @if ($assignments->isNotEmpty())
        <div style="font-size:16px;font-weight:700;color:{{ $accent }};margin:0 0 12px;">Equipos asignados</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
            style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:24px;font-size:13px;">
            <tr style="background:#f3f4f6;color:#6b7280;text-transform:uppercase;font-size:11px;letter-spacing:.04em;">
                <th align="left" style="padding:11px 14px;font-weight:700;">Etiqueta</th>
                <th align="left" style="padding:11px 14px;font-weight:700;">Equipo</th>
                <th align="left" style="padding:11px 14px;font-weight:700;">Tipo</th>
                <th align="left" style="padding:11px 14px;font-weight:700;">No. de serie</th>
            </tr>
            @foreach ($assignments as $a)
                <tr style="background:{{ $loop->even ? '#fafbfc' : '#ffffff' }};border-top:1px solid #eef0f3;">
                    <td style="padding:12px 14px;font-family:'Courier New',monospace;color:{{ $accent }};">{{ $a->asset?->asset_tag }}</td>
                    <td style="padding:12px 14px;color:#1f2430;">{{ $a->asset?->name }}</td>
                    <td style="padding:12px 14px;color:#4b5563;">{{ $a->asset?->type?->name ?? '—' }}</td>
                    <td style="padding:12px 14px;font-family:'Courier New',monospace;color:#6b7280;">{{ $a->asset?->serial_number ?: '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- Bienes adicionales --}}
    @if ($items->isNotEmpty())
        <div style="font-size:16px;font-weight:700;color:{{ $accent }};margin:0 0 10px;">Bienes adicionales</div>
        <ul style="margin:0 0 24px;padding-left:20px;font-size:14px;line-height:1.8;color:#2b2f38;">
            @foreach ($items as $item)
                <li>{{ $item->type?->name ?? 'Bien adicional' }}@if ($item->value) — <strong>{{ $item->value }}</strong>@endif</li>
            @endforeach
        </ul>
    @endif

    {{-- Aviso de firma --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:6px;">
        <tr>
            <td style="background:#eef3fb;border-left:4px solid {{ $accent }};border-radius:8px;padding:14px 16px;font-size:13px;line-height:1.6;color:#33415c;">
                @if (! empty($toManager))
                    En breve se hará entrega de la <strong>carta responsiva (folio {{ $letter->folio }})</strong>
                    al colaborador para su <strong>firma</strong>. Este aviso es únicamente informativo.
                @else
                    {!! nl2br(e($note)) !!}
                @endif
            </td>
        </tr>
    </table>

    <hr style="border:none;border-top:1px solid #eef0f3;margin:22px 0 16px;">
    <p style="font-size:13px;color:#6b7280;line-height:1.6;margin:0 0 12px;">
        Si detectas alguna diferencia o tienes dudas, por favor contacta al área de TI.
    </p>
</x-mail.shell>
