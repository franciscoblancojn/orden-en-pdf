<?php

/**
 * Descarga del PDF desde el detalle del pedido.
 *
 * Solo lectura: genera el PDF al momento y lo envía al navegador. No escribe nada en el
 * sitio salvo el archivo temporal de DOMPDF, que queda en el directorio temporal de PHP.
 */
class OEP_API_DOWNLOAD
{
    const ACTION = 'oep_download';

    public static function init()
    {
        add_action('admin_post_' . self::ACTION, [self::class, 'download']);
    }

    public static function download()
    {
        if (!current_user_can('edit_shop_orders')) {
            wp_die('No tienes permisos para descargar este pedido.', '', ['response' => 403]);
        }

        $order_id = absint($_GET['order_id'] ?? 0);
        check_admin_referer('oep_download_' . $order_id);

        $order = OEP_ORDER::get($order_id);
        if (!$order) {
            wp_die('No se encontró el pedido.', '', ['response' => 404]);
        }

        OEP_LOG_SERVICE::add('descarga', [
            'order_id' => $order->get_id(),
            'user_id' => get_current_user_id(),
        ]);

        OEP_PDF::stream($order);
    }
}

OEP_API_DOWNLOAD::init();
