<?php

/**
 * Configuración del plugin (opción OEP_CONFIG).
 */
class OEP_USE_DATA_CONFIG extends OEP_USE_DATA_BASE
{
    protected $KEY = OEP_CONFIG;

    const PAPERS = [
        'a4' => 'A4 (210 × 297 mm)',
        'letter' => 'Carta (216 × 279 mm)',
    ];

    const DEFAULTS = [
        // Logo: 0 = logo del sitio (Personalizar → Identidad del sitio).
        'logo_id' => 0,
        'logo_width' => 180,             // ancho en px dentro del PDF
        'company_name' => '',            // vacío = nombre del sitio
        'header_text' => '',             // NIT, dirección, teléfono…
        'footer_text' => '',             // pie de página
        'document_title' => 'Pedido',
        'paper' => 'a4',
        'accent_color' => '#1f2933',
        // Documento del cliente (cédula, NIT…): se muestra en la caja de Facturación.
        'document_label' => 'Cédula',
        // Metadatos del pedido donde puede estar, en orden (uno por línea). Se muestran todos los
        // valores distintos que tengan: el cliente puede haberlo escrito en uno u otro campo.
        //   _billing_id / billing_id: campo "billing_id" de facturación (WooCommerce o un editor de campos).
        //   _cedula / cedula: campo "cedula" que agregan algunos plugins de envío.
        'document_fields' => "_billing_id\nbilling_id\n_cedula\ncedula",
        // Contenido.
        'show_customer_note' => 1,
        'show_notes' => 0,
        // Correos del administrador a los que se adjunta el PDF.
        'attach_new_order' => 1,
        'attach_cancelled_order' => 0,
        'attach_failed_order' => 0,
    ];

    public function get()
    {
        return array_merge(self::DEFAULTS, $this->DATA);
    }

    public function set($data)
    {
        $this->DATA = $data;
        $this->save();
    }

    /**
     * Correos que WooCommerce envía al administrador y a los que se puede adjuntar.
     */
    public static function emails()
    {
        return [
            'new_order' => 'Nuevo pedido',
            'cancelled_order' => 'Pedido cancelado',
            'failed_order' => 'Pedido fallido',
        ];
    }

    /**
     * ¿Se adjunta el PDF a este correo?
     *
     * @param string $email_id ID del correo de WooCommerce (new_order, cancelled_order…).
     */
    public static function attachs_to($email_id)
    {
        $emails = self::emails();
        if (!isset($emails[$email_id])) {
            return false;
        }
        $config = (new self())->get();
        $key = 'attach_' . $email_id;

        return !empty($config[$key]);
    }

    private static function px($value, $default, $min, $max)
    {
        $value = absint($value);

        return $value >= $min && $value <= $max ? $value : $default;
    }

    /**
     * Lista de metadatos del documento, uno por línea. Solo letras, números, _ - / y .; los demás se descartan
     * (los campos adicionales del checkout por bloques se guardan como _wc_other/plugin/campo).
     *
     * @param string $value
     * @return string[]
     */
    public static function document_fields($value)
    {
        $keys = [];
        foreach (preg_split('/[\r\n,]+/', (string) $value) as $key) {
            $key = trim($key);
            // Nombres con otros caracteres se descartan (no se "limpian": podrían coincidir con otro campo).
            if (preg_match('/^[A-Za-z0-9_\-\/.]+$/', $key) && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        return array_slice($keys, 0, 20);
    }

    /**
     * Normaliza lo que llega del formulario de ajustes.
     */
    public static function sanitize($input)
    {
        $input = (array) $input;
        $d = self::DEFAULTS;
        $check = function ($key) use ($input) {
            return empty($input[$key]) ? 0 : 1;
        };
        $text = function ($key) use ($input, $d) {
            $value = sanitize_text_field($input[$key] ?? '');

            return '' === trim($value) ? $d[$key] : $value;
        };
        $textarea = function ($key) use ($input) {
            return sanitize_textarea_field($input[$key] ?? '');
        };

        $logo_id = absint($input['logo_id'] ?? 0);
        if ($logo_id && (!function_exists('wp_attachment_is_image') || !wp_attachment_is_image($logo_id))) {
            $logo_id = 0;
        }

        $paper = $input['paper'] ?? $d['paper'];
        if (!isset(self::PAPERS[$paper])) {
            $paper = $d['paper'];
        }

        $accent = sanitize_hex_color($input['accent_color'] ?? '');

        return [
            'logo_id' => $logo_id,
            'logo_width' => self::px($input['logo_width'] ?? $d['logo_width'], $d['logo_width'], 40, 400),
            'company_name' => $text('company_name'),
            'header_text' => $textarea('header_text'),
            'footer_text' => $textarea('footer_text'),
            'document_title' => $text('document_title'),
            'paper' => $paper,
            'accent_color' => $accent ? $accent : $d['accent_color'],
            'document_label' => sanitize_text_field($input['document_label'] ?? ''),
            'document_fields' => implode("\n", self::document_fields($input['document_fields'] ?? '')),
            'show_customer_note' => $check('show_customer_note'),
            'show_notes' => $check('show_notes'),
            'attach_new_order' => $check('attach_new_order'),
            'attach_cancelled_order' => $check('attach_cancelled_order'),
            'attach_failed_order' => $check('attach_failed_order'),
        ];
    }
}
