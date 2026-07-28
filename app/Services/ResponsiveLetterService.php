<?php

namespace App\Services;

use App\Models\ResponsiveLetter;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Generación de cartas responsivas con folio consecutivo POR TIPO de carta:
 *  - delivery = Carta de Aceptación de Bienes (CAB), cuando el empleado recibe.
 *  - return   = Carta de Entrega de Bienes (CEB), cuando el empleado devuelve/egresa.
 *
 * El número de folio se DERIVA de los datos (máximo usado + 1 por prefijo y año),
 * por lo que siempre es consecutivo y nunca sobrescribe ni depende de un contador
 * manual editable. El prefijo y el texto sí son configurables por tipo.
 */
class ResponsiveLetterService
{
    /** Prefijo por defecto por tipo. */
    public const DEFAULT_PREFIX = [
        'delivery' => 'CAB',
        'return' => 'CEB',
    ];

    /** Etiqueta del tipo para títulos. */
    public const TYPE_LABEL = [
        'delivery' => 'Carta de Aceptación de Bienes',
        'return' => 'Carta de Entrega de Bienes',
    ];

    /**
     * Texto legal por defecto por tipo (fuente única de verdad).
     * Admite marcadores que se reemplazan al generar la carta (ver placeholders()).
     */
    public const DEFAULT_TEXT = [
        // ENTRADA — el colaborador RECIBE los bienes.
        'delivery' => "Por medio de la presente, {colaborador}, con número de empleado {no_empleado}, reconoce haber recibido de conformidad y a su entera satisfacción los bienes informáticos descritos en el presente documento, los cuales le son entregados para el desempeño de sus funciones laborales.\n"
            ."{colaborador} se compromete a utilizarlos de manera adecuada y con fines exclusivamente laborales, mantenerlos bajo su resguardo y custodia, conservarlos en buen estado y notificar oportunamente cualquier daño, pérdida, robo o incidencia relacionada con los mismos. Asimismo, se obliga a devolverlos cuando le sean requeridos por {empresa} o al término de la relación laboral, en las mismas condiciones en que los recibió, salvo el desgaste natural derivado de su uso.",
        // SALIDA — el colaborador ENTREGA los bienes al responsable del área/almacén.
        'return' => "Por medio de la presente se hace constar que {colaborador}, con número de empleado {no_empleado}, con motivo de la conclusión de su relación laboral o del cambio en su asignación, hace entrega de los bienes informáticos descritos en el presente documento al responsable del área o almacén, quien los recibe verificando su estado físico y funcionamiento al momento de la entrega.\n"
            ."Con la presente entrega, {colaborador} queda liberado(a) de la responsabilidad y custodia sobre dichos bienes. Cualquier faltante, daño o condición distinta al desgaste natural por uso quedará asentado en el apartado de observaciones de este documento.",
    ];

    /** Marcadores disponibles para los textos de carta: {marcador} => descripción. */
    public const PLACEHOLDERS = [
        '{colaborador}' => 'Nombre del colaborador',
        '{no_empleado}' => 'Número de empleado',
        '{puesto}' => 'Puesto',
        '{departamento}' => 'Departamento',
        '{empresa}' => 'Nombre de la empresa',
        '{fecha}' => 'Fecha de emisión',
        '{folio}' => 'Folio de la carta',
    ];

    public function prefixFor(string $type): string
    {
        return Setting::get("letter_{$type}_prefix", self::DEFAULT_PREFIX[$type] ?? 'CR');
    }

    /**
     * Siguiente folio para el tipo dado. Formato: {PREFIJO}-{AÑO}-{NNNN}.
     * Número = máximo existente (incluye eliminados) para ese prefijo+año + 1,
     * respetando el "folio inicial" configurado solo cuando aún no hay folios.
     */
    public function nextFolio(string $type = 'delivery'): string
    {
        $prefix = $this->prefixFor($type);
        $year = now()->year;
        $start = max(1, (int) Setting::get("letter_{$type}_start", '1'));
        $like = "{$prefix}-{$year}-%";

        $maxUsed = ResponsiveLetter::withTrashed()
            ->where('type', $type)
            ->where('folio', 'like', $like)
            ->pluck('folio')
            ->map(fn ($f) => (int) substr((string) strrchr($f, '-'), 1))
            ->max() ?? 0;

        $number = $maxUsed > 0 ? $maxUsed + 1 : $start;

        // Blindaje final por si algún folio ya existiera (colisión improbable).
        do {
            $folio = sprintf('%s-%d-%04d', $prefix, $year, $number);
            $number++;
        } while (ResponsiveLetter::withTrashed()->where('folio', $folio)->exists());

        return $folio;
    }

    /** Renderiza y guarda el PDF de la carta. Devuelve la ruta en storage. */
    public function generatePdf(ResponsiveLetter $letter): string
    {
        $letter->loadMissing([
            'employee.department', 'employee.location',
            'assignments.asset.type', 'assignments.asset.model.manufacturer',
            'returnedAssignments.asset.type', 'returnedAssignments.asset.model.manufacturer',
            'items.type', 'createdBy',
        ]);

        $type = $letter->type ?? 'delivery';

        $rawText = Setting::get("letter_{$type}_text", self::DEFAULT_TEXT[$type] ?? '');

        $pdf = Pdf::loadView('pdf.responsive-letter', [
            'letter' => $letter,
            'assignments' => $letter->documentAssignments,
            'companyName' => Setting::get('company_name', 'NETJER Networks'),
            'introText' => $this->renderPlaceholders($rawText, $letter),
            'docTitle' => self::TYPE_LABEL[$type] ?? 'Carta responsiva',
            'logoPath' => $this->logoPath(),
        ])->setPaper('letter');

        $path = "responsive_letters/{$letter->folio}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        if ($letter->pdf_path !== $path) {
            $letter->update(['pdf_path' => $path]);
        }

        return $path;
    }

    /** Reemplaza los marcadores {…} del texto por los datos reales de la carta. */
    protected function renderPlaceholders(string $text, ResponsiveLetter $letter): string
    {
        $e = $letter->employee;

        return strtr($text, [
            '{colaborador}' => $e?->name ?? '',
            '{no_empleado}' => $e?->employee_number ?? '',
            '{puesto}' => $e?->position ?? '',
            '{departamento}' => $e?->department?->name ?? '',
            '{empresa}' => Setting::get('company_name', config('app.name')),
            '{fecha}' => $letter->issued_at?->format('d/m/Y') ?? '',
            '{folio}' => $letter->folio,
        ]);
    }

    /** Ruta absoluta del logo: el configurado en settings o el de la empresa por defecto. */
    protected function logoPath(): ?string
    {
        if ($logo = Setting::get('company_logo')) {
            $absolute = Storage::disk('public')->path($logo);
            if (is_file($absolute)) {
                return $absolute;
            }
        }

        $default = Storage::disk('public')->path('company-logo-default.png');

        return is_file($default) ? $default : null;
    }

    /** Ruta absoluta del PDF, regenerándolo si no existe. */
    public function ensurePdf(ResponsiveLetter $letter): string
    {
        if (! $letter->pdf_path || ! Storage::disk('public')->exists($letter->pdf_path)) {
            $this->generatePdf($letter);
        }

        return Storage::disk('public')->path($letter->pdf_path);
    }
}
