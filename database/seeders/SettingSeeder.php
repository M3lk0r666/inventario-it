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
        $defaults = [
            'company_name' => config('app.name'),
            'company_logo' => 'company-logo-default.png',
            'letter_folio_prefix' => 'CR',
            'letter_next_number' => '1',
            'letter_intro_text' => 'Por medio de la presente hago constar que recibo de conformidad los bienes descritos a continuación, comprometiéndome a su buen uso, resguardo y devolución en las mismas condiciones.',
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
