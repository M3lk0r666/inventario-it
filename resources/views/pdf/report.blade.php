<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #191b23; padding: 18px 22px; }
    .head { border-bottom: 2px solid #003d9b; padding-bottom: 8px; margin-bottom: 12px; }
    .company { font-size: 13px; font-weight: bold; color: #003d9b; }
    .title { font-size: 12px; font-weight: bold; margin-top: 2px; }
    .meta { font-size: 8px; color: #737685; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #003d9b; color: #fff; padding: 4px 5px; font-size: 8px; text-align: left; }
    td { border: 1px solid #c3c6d6; padding: 4px 5px; font-size: 8px; }
    tr:nth-child(even) td { background: #f7f7fb; }
    .footer { margin-top: 10px; font-size: 7px; color: #737685; text-align: center; }
</style>
</head>
<body>
    <div class="head">
        <div class="company">{{ $companyName }}</div>
        <div class="title">Reporte: {{ $def['label'] }}</div>
        <div class="meta">{{ $def['description'] }} · Generado el {{ now()->format('d/m/Y H:i') }} · {{ $rows->count() }} registros</div>
    </div>
    <table>
        <thead>
            <tr>@foreach ($def['columns'] as $col)<th>{{ $col }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>@foreach ($row as $cell)<td>{{ $cell !== '' && $cell !== null ? $cell : '—' }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($def['columns']) }}" style="text-align:center;">Sin datos.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">{{ config('app.name') }} — Reporte {{ $def['label'] }}</div>
</body>
</html>
