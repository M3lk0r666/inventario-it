<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Plantillas de correo configurables desde el portal
 * (Administración → Plantillas de correo). Guarda overrides en la tabla
 * settings con la clave `mail_tpl_{correo}_{campo}`; si no hay override,
 * usa el texto por defecto. Solo se personalizan asunto, introducción y nota;
 * las tablas de datos (equipos, bienes) siguen generándose automáticamente.
 */
class MailTemplates
{
    /** Definición de cada correo editable. */
    public const TEMPLATES = [
        'assignment' => [
            'label' => 'Asignación de bienes',
            'description' => 'Aviso al empleado cuando se le asignan bienes (y copia al jefe inmediato).',
            'vars' => ['{empresa}', '{empleado}', '{jefe}', '{folio}', '{fecha}'],
            'defaults' => [
                'subject' => 'Bienes asignados — folio {folio}',
                'intro' => 'Te informamos que se te han asignado los siguientes bienes informáticos, amparados por la carta responsiva con folio {folio}, con fecha {fecha}.',
                'note' => 'En breve se te hará entrega de tu carta responsiva (folio {folio}) para su firma. Te pedimos revisar que los bienes listados sean correctos y conservar este aviso para tu referencia.',
            ],
        ],
        'reception' => [
            'label' => 'Recepción / devolución de bienes',
            'description' => 'Aviso cuando el empleado devuelve o se reciben bienes.',
            'vars' => ['{empresa}', '{empleado}', '{jefe}', '{folio}', '{fecha}'],
            'defaults' => [
                'subject' => 'Recepción de bienes registrada',
                'intro' => 'Te confirmamos que se registró la recepción (devolución) de los siguientes bienes con fecha {fecha}.',
                'note' => 'Con esto se libera tu responsabilidad sobre los bienes listados. Conserva este aviso para tu referencia.',
            ],
        ],
        'access' => [
            'label' => 'Acceso al portal',
            'description' => 'Correo de bienvenida con el enlace para establecer la contraseña.',
            'vars' => ['{empresa}', '{empleado}', '{correo}', '{rol}', '{portal}'],
            'defaults' => [
                'subject' => 'Acceso al portal',
                'intro' => 'Se te otorgó acceso al portal de Inventario TI con el rol {rol}. Tu usuario es {correo}. Para ingresar, primero establece tu contraseña con el botón de abajo.',
                'note' => 'Por seguridad, este enlace es válido únicamente durante las 24 horas posteriores a la recepción de este correo. Si expira, solicita un nuevo enlace al área de TI.',
            ],
        ],
        'revoked' => [
            'label' => 'Revocación de acceso',
            'description' => 'Aviso al usuario cuando se revoca su acceso al portal.',
            'vars' => ['{empresa}', '{empleado}'],
            'defaults' => [
                'subject' => 'Acceso al portal revocado',
                'intro' => 'Te informamos que tu acceso al portal de Inventario TI ha sido revocado. A partir de este momento tu cuenta ya no podrá iniciar sesión.',
                'note' => 'Si necesitas recuperar el acceso o consideras que se trata de un error, por favor contacta al área de TI.',
            ],
        ],
    ];

    public const FIELDS = ['subject', 'intro', 'note'];

    /** Texto configurado (o por defecto) de un campo de una plantilla. */
    public static function field(string $key, string $field): string
    {
        $default = self::TEMPLATES[$key]['defaults'][$field] ?? '';

        return (string) Setting::get("mail_tpl_{$key}_{$field}", $default);
    }

    /** Campo con los marcadores {…} sustituidos. */
    public static function render(string $key, string $field, array $vars): string
    {
        return strtr(self::field($key, $field), $vars);
    }

    /** Color de acento de los correos (encabezado, títulos, enlaces). */
    public static function accentColor(): string
    {
        return Setting::get('mail_accent_color', '#0b56c4') ?: '#0b56c4';
    }

    /** Texto del pie de los correos. */
    public static function footerText(array $vars = []): string
    {
        $t = Setting::get('mail_footer_text', 'Mensaje automático de {empresa}. Por favor no respondas a este correo.');

        return strtr($t, $vars);
    }
}
