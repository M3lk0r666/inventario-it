<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Support\MailTemplates;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Configuración de las plantillas de los correos de aviso/notificación:
 * color de acento, texto del pie y, por cada correo, su asunto, introducción
 * y nota destacada (con variables {…}). Guarda overrides en `settings`.
 */
class EmailTemplatesManager extends Component
{
    use AuthorizesRequests;

    public string $accent = '';

    public string $footer = '';

    /** @var array<string,array<string,string>> key => [subject, intro, note] */
    public array $tpl = [];

    public function mount(): void
    {
        $this->authorize('settings.view');

        $this->accent = MailTemplates::accentColor();
        $this->footer = Setting::get('mail_footer_text', 'Mensaje automático de {empresa}. Por favor no respondas a este correo.');

        foreach (array_keys(MailTemplates::TEMPLATES) as $key) {
            foreach (MailTemplates::FIELDS as $field) {
                $this->tpl[$key][$field] = MailTemplates::field($key, $field);
            }
        }
    }

    public function save(): void
    {
        $this->authorize('settings.edit');

        $this->validate([
            'accent' => ['required', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'footer' => ['required', 'string', 'max:500'],
            'tpl.*.subject' => ['required', 'string', 'max:255'],
            'tpl.*.intro' => ['required', 'string', 'max:2000'],
            'tpl.*.note' => ['required', 'string', 'max:2000'],
        ], [], [
            'accent' => 'color de acento',
            'footer' => 'texto del pie',
        ]);

        Setting::set('mail_accent_color', $this->accent);
        Setting::set('mail_footer_text', $this->footer);

        foreach (array_keys(MailTemplates::TEMPLATES) as $key) {
            foreach (MailTemplates::FIELDS as $field) {
                Setting::set("mail_tpl_{$key}_{$field}", $this->tpl[$key][$field] ?? '');
            }
        }

        $this->dispatch('toast', type: 'success', message: 'Plantillas de correo guardadas.');
    }

    /** Restaura en el formulario los textos por defecto de un correo (no guarda). */
    public function restore(string $key): void
    {
        $defaults = MailTemplates::TEMPLATES[$key]['defaults'] ?? [];
        foreach (MailTemplates::FIELDS as $field) {
            $this->tpl[$key][$field] = $defaults[$field] ?? '';
        }
        $this->dispatch('toast', type: 'success', message: 'Textos restaurados (recuerda guardar).');
    }

    public function render()
    {
        return view('livewire.admin.settings.email-templates-manager', [
            'templates' => MailTemplates::TEMPLATES,
        ]);
    }
}
