<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Mail\TestMail;
use App\Services\MailConfigurator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Configuración de la plataforma: datos de empresa, folios, textos de carta
 * y correo (SMTP Office 365). Guarda en la tabla settings.
 */
class ConfigManager extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $tab = 'company';

    // Empresa
    public string $company_name = '';

    public $logo = null; // subida temporal

    // Correo
    public bool $mail_enabled = false;

    public string $mail_host = '';

    public string $mail_port = '';

    public string $mail_encryption = 'tls';

    public string $mail_username = '';

    public string $mail_password = '';

    public string $mail_from_address = '';

    public string $mail_from_name = '';

    public string $alert_recipients = '';

    public string $testEmail = '';

    public function mount(): void
    {
        $this->authorize('settings.view');

        $this->company_name = Setting::get('company_name', '');

        $this->mail_enabled = Setting::get('mail_enabled') === '1';
        $this->mail_host = Setting::get('mail_host', 'smtp.office365.com');
        $this->mail_port = Setting::get('mail_port', '587');
        $this->mail_encryption = Setting::get('mail_encryption', 'tls');
        $this->mail_username = Setting::get('mail_username', '');
        $this->mail_password = Setting::get('mail_password', '');
        $this->mail_from_address = Setting::get('mail_from_address', '');
        $this->mail_from_name = Setting::get('mail_from_name', '');
        $this->alert_recipients = Setting::get('alert_recipients', '');
    }

    public function saveCompany(): void
    {
        $this->authorize('settings.edit');
        $this->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ], [], ['company_name' => 'nombre de la empresa', 'logo' => 'logo']);

        Setting::set('company_name', $this->company_name);

        if ($this->logo) {
            $old = Setting::get('company_logo');
            if ($old && $old !== 'company-logo-default.png') {
                Storage::disk('public')->delete($old);
            }
            $path = $this->logo->storeAs('branding', 'company-logo.'.$this->logo->getClientOriginalExtension(), 'public');
            Setting::set('company_logo', $path);
            $this->reset('logo');
        }

        $this->dispatch('toast', type: 'success', message: 'Datos de empresa guardados.');
    }

    public function saveMail(): void
    {
        $this->authorize('settings.edit');
        $this->validate([
            'mail_host' => ['required_if:mail_enabled,true', 'nullable', 'string', 'max:255'],
            'mail_port' => ['required_if:mail_enabled,true', 'nullable', 'integer'],
            'mail_encryption' => ['nullable', 'in:tls,ssl,'],
            'mail_username' => ['required_if:mail_enabled,true', 'nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'alert_recipients' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'mail_host' => 'servidor', 'mail_port' => 'puerto', 'mail_username' => 'usuario',
            'mail_from_address' => 'correo remitente',
        ]);

        Setting::set('mail_enabled', $this->mail_enabled ? '1' : '0');
        Setting::set('mail_host', $this->mail_host);
        Setting::set('mail_port', $this->mail_port);
        Setting::set('mail_encryption', $this->mail_encryption);
        Setting::set('mail_username', $this->mail_username);
        if (filled($this->mail_password)) {
            Setting::set('mail_password', $this->mail_password);
        }
        Setting::set('mail_from_address', $this->mail_from_address);
        Setting::set('mail_from_name', $this->mail_from_name);
        Setting::set('alert_recipients', $this->alert_recipients);

        $this->dispatch('toast', type: 'success', message: 'Configuración de correo guardada.');
    }

    public function sendTest(): void
    {
        $this->authorize('settings.edit');
        $this->validate(['testEmail' => ['required', 'email']], [], ['testEmail' => 'correo de prueba']);

        if (! MailConfigurator::isReady()) {
            $this->dispatch('toast', type: 'error', message: 'Guarda y habilita el correo antes de probar.');

            return;
        }

        try {
            MailConfigurator::apply();
            Mail::to($this->testEmail)->send(new TestMail());
            $this->dispatch('toast', type: 'success', message: 'Correo de prueba enviado a '.$this->testEmail);
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: 'Error al enviar: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.config-manager', [
            'currentLogo' => Setting::get('company_logo'),
        ]);
    }
}
