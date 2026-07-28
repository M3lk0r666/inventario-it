<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Configuración base de la plataforma. Se siembra SIEMPRE (también en
 * producción). No incluye datos de prueba. Usa firstOrCreate para no
 * pisar valores ya ajustados desde Configuración.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $cabText = \App\Services\ResponsiveLetterService::DEFAULT_TEXT['delivery'];
        $cebText = \App\Services\ResponsiveLetterService::DEFAULT_TEXT['return'];

        $defaults = [
            'company_name' => config('app.name'),
            'company_logo' => 'company-logo-default.png',
            // Carta de Aceptación de Bienes (cuando el empleado recibe) = tipo delivery
            'letter_delivery_prefix' => 'CAB',
            'letter_delivery_start' => '1',
            'letter_delivery_text' => $cabText,
            // Carta de Entrega de Bienes (cuando el empleado devuelve/egresa) = tipo return
            'letter_return_prefix' => 'CEB',
            'letter_return_start' => '1',
            'letter_return_text' => $cebText,
            'mail_enabled' => '0',
            'mail_host' => 'smtp.office365.com',
            'mail_port' => '587',
            'mail_encryption' => 'tls',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
