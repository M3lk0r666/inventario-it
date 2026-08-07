<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ResponsiveLetter;
use App\Models\Setting;
use App\Services\ResponsiveLetterService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Configuración de las cartas responsivas: prefijo/folio inicial y texto
 * de las cartas de Aceptación (CAB) y de Entrega (CEB) de bienes.
 */
class LetterTemplatesManager extends Component
{
    use AuthorizesRequests;

    // Cartas de Aceptación de Bienes (CAB) — cuando el empleado recibe
    public string $cab_prefix = '';

    public string $cab_start = '';

    public string $cab_text = '';

    public string $cab_note = '';

    // Cartas de Entrega de Bienes (CEB) — cuando el empleado devuelve/egresa
    public string $ceb_prefix = '';

    public string $ceb_start = '';

    public string $ceb_text = '';

    public string $ceb_note = '';

    public function mount(): void
    {
        $this->authorize('settings.view');

        $this->cab_prefix = Setting::get('letter_delivery_prefix', 'CAB');
        $this->cab_start = Setting::get('letter_delivery_start', '1');
        $this->cab_text = Setting::get('letter_delivery_text', ResponsiveLetterService::DEFAULT_TEXT['delivery']);
        $this->cab_note = Setting::get('letter_delivery_note', ResponsiveLetterService::DEFAULT_NOTE['delivery']);
        $this->ceb_prefix = Setting::get('letter_return_prefix', 'CEB');
        $this->ceb_start = Setting::get('letter_return_start', '1');
        $this->ceb_text = Setting::get('letter_return_text', ResponsiveLetterService::DEFAULT_TEXT['return']);
        $this->ceb_note = Setting::get('letter_return_note', ResponsiveLetterService::DEFAULT_NOTE['return']);
    }

    public function save(): void
    {
        $this->authorize('settings.edit');
        $this->validate([
            'cab_prefix' => ['required', 'string', 'max:10'],
            'cab_start' => ['required', 'integer', 'min:1'],
            'cab_text' => ['nullable', 'string', 'max:3000'],
            'cab_note' => ['nullable', 'string', 'max:1000'],
            'ceb_prefix' => ['required', 'string', 'max:10'],
            'ceb_start' => ['required', 'integer', 'min:1'],
            'ceb_text' => ['nullable', 'string', 'max:3000'],
            'ceb_note' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'cab_prefix' => 'prefijo CAB', 'cab_start' => 'folio inicial CAB', 'cab_text' => 'texto CAB', 'cab_note' => 'nota CAB',
            'ceb_prefix' => 'prefijo CEB', 'ceb_start' => 'folio inicial CEB', 'ceb_text' => 'texto CEB', 'ceb_note' => 'nota CEB',
        ]);

        // Protección de consecutividad: el folio inicial no puede quedar por debajo
        // de lo ya emitido para ese prefijo+año (evita duplicar/sobrescribir folios).
        [$this->cab_start, $a1] = $this->clampStart('delivery', $this->cab_prefix, (int) $this->cab_start);
        [$this->ceb_start, $a2] = $this->clampStart('return', $this->ceb_prefix, (int) $this->ceb_start);
        $adjusted = $a1 || $a2;

        Setting::set('letter_delivery_prefix', $this->cab_prefix);
        Setting::set('letter_delivery_start', $this->cab_start);
        Setting::set('letter_delivery_text', $this->cab_text);
        Setting::set('letter_delivery_note', $this->cab_note);
        Setting::set('letter_return_prefix', $this->ceb_prefix);
        Setting::set('letter_return_start', $this->ceb_start);
        Setting::set('letter_return_text', $this->ceb_text);
        Setting::set('letter_return_note', $this->ceb_note);

        $this->dispatch('toast', type: 'success',
            message: 'Configuración de cartas guardada.'.($adjusted ? ' Se ajustó un folio inicial para conservar la consecutividad.' : ''));
    }

    /**
     * Ajusta el folio inicial para que no sea menor que el máximo ya emitido
     * (prefijo + año actual). Devuelve [valor, seAjustó].
     *
     * @return array{0:string,1:bool}
     */
    protected function clampStart(string $type, string $prefix, int $start): array
    {
        $year = now()->year;
        $maxUsed = ResponsiveLetter::withTrashed()
            ->where('type', $type)
            ->where('folio', 'like', "{$prefix}-{$year}-%")
            ->pluck('folio')
            ->map(fn ($f) => (int) substr((string) strrchr($f, '-'), 1))
            ->max() ?? 0;

        $min = $maxUsed + 1;
        if ($maxUsed > 0 && $start < $min) {
            return [(string) $min, true];
        }

        return [(string) max(1, $start), false];
    }

    public function render()
    {
        return view('livewire.admin.settings.letter-templates-manager');
    }
}
