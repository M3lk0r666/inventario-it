<?php

namespace App\Services;

use App\Models\ResponsiveLetter;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Generación de cartas responsivas: folio consecutivo (configurable en settings)
 * y PDF con dompdf, guardado en el disco público.
 */
class ResponsiveLetterService
{
    /** Obtiene el siguiente folio y avanza el consecutivo. P.ej. CR-2026-0013 */
    public function nextFolio(): string
    {
        $prefix = Setting::get('letter_folio_prefix', 'CR');
        $number = (int) Setting::get('letter_next_number', '1');

        do {
            $folio = sprintf('%s-%d-%04d', $prefix, now()->year, $number);
            $number++;
        } while (ResponsiveLetter::withTrashed()->where('folio', $folio)->exists());

        Setting::set('letter_next_number', (string) $number);

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

        $pdf = Pdf::loadView('pdf.responsive-letter', [
            'letter' => $letter,
            'assignments' => $letter->documentAssignments,
            'companyName' => Setting::get('company_name', 'NETJER Networks'),
            'introText' => Setting::get('letter_intro_text', ''),
            'logoPath' => $this->logoPath(),
        ])->setPaper('letter');

        $path = "responsive_letters/{$letter->folio}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        if ($letter->pdf_path !== $path) {
            $letter->update(['pdf_path' => $path]);
        }

        return $path;
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
