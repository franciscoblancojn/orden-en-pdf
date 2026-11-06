<?php

/**
 * Adjunta el PDF a los correos que WooCommerce envía al administrador.
 *
 * `woocommerce_email_attachments` se ejecuta para todos los correos. Se descartan los
 * correos al cliente (`is_customer_email()`) y los tipos que no estén activados en los
 * ajustes. El archivo temporal se borra al terminar la petición.
 */
class OEP_HOOK_EMAIL
{
    private static $files = [];
    private static $cleanup_registered = false;

    public static function init()
    {
        add_filter('woocommerce_email_attachments', [self::class, 'attachments'], 10, 4);
    }

    /**
     * @param array         $attachments
     * @param string        $email_id
     * @param WC_Order|null $object
     * @param WC_Email|null $email
     */
    public static function attachments($attachments, $email_id, $object, $email = null)
    {
        if (!$object instanceof WC_Order) {
            return $attachments;
        }
        if (is_object($email) && method_exists($email, 'is_customer_email') && $email->is_customer_email()) {
            return $attachments;
        }
        if (!OEP_USE_DATA_CONFIG::attachs_to($email_id)) {
            return $attachments;
        }

        /**
         * Permite desactivar el adjunto para un pedido concreto.
         */
        if (!apply_filters('oep_attach_pdf', true, $email_id, $object, $email)) {
            return $attachments;
        }

        $file = OEP_PDF::temp_file($object);
        if ('' === $file) {
            OEP_LOG_SERVICE::add('error_adjunto', [
                'order_id' => $object->get_id(),
                'email' => $email_id,
            ]);

            return $attachments;
        }

        self::$files[] = $file;
        self::register_cleanup();
        $attachments[] = $file;

        OEP_LOG_SERVICE::add('adjunto', [
            'order_id' => $object->get_id(),
            'email' => $email_id,
            'file' => basename($file),
        ]);

        return $attachments;
    }

    private static function register_cleanup()
    {
        if (self::$cleanup_registered) {
            return;
        }
        self::$cleanup_registered = true;
        add_action('shutdown', [self::class, 'cleanup'], 99);
    }

    /**
     * Borra los PDFs temporales después de que PHPMailer los haya leído.
     */
    public static function cleanup()
    {
        foreach (self::$files as $file) {
            if (is_file($file)) {
                @unlink($file); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            }
        }
        self::$files = [];
    }
}

OEP_HOOK_EMAIL::init();
