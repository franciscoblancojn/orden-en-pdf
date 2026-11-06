<?php

/**
 * Detalle del pedido en el admin: caja «Orden en PDF» con el botón de descarga.
 *
 * Funciona con el almacenamiento clásico de pedidos y con HPOS (High-Performance
 * Order Storage). En HPOS la caja recibe un WC_Order; en el clásico, un WP_Post.
 */
class OEP_HOOK_ORDER
{
    public static function init()
    {
        add_action('before_woocommerce_init', [self::class, 'declare_compatibility']);
        if (did_action('before_woocommerce_init')) {
            self::declare_compatibility();
        }
        add_action('add_meta_boxes', [self::class, 'meta_boxes']);
    }

    public static function declare_compatibility()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', OEP_FILE, true);
        }
    }

    public static function meta_boxes()
    {
        $screens = ['shop_order'];
        if (function_exists('wc_get_page_screen_id')) {
            $screens[] = wc_get_page_screen_id('shop-order');
        }

        foreach (array_unique(array_filter($screens)) as $screen) {
            add_meta_box(
                'oep-order-pdf',
                'Orden en PDF',
                [self::class, 'render'],
                $screen,
                'side',
                'high'
            );
        }
    }

    /**
     * @param WP_Post|WC_Order $post
     */
    public static function render($post)
    {
        $order = self::order($post);
        if (!$order) {
            echo '<p>No se encontró el pedido.</p>';
            return;
        }
        if (!current_user_can('edit_shop_orders')) {
            echo '<p>No tienes permisos para descargar este pedido.</p>';
            return;
        }

        $config = (new OEP_USE_DATA_CONFIG())->get();
        $emails = [];
        foreach (OEP_USE_DATA_CONFIG::emails() as $id => $label) {
            if (!empty($config['attach_' . $id])) {
                $emails[] = strtolower($label);
            }
        }
        ?>
        <p>
            <a class="button button-primary" href="<?= esc_url(self::url($order)) ?>">
                Descargar PDF
            </a>
        </p>
        <p class="description">
            Incluye el logo, los datos del cliente, los productos y los totales.
            <?php if (!empty($emails)) : ?>
                Se adjunta al correo del administrador: <?= esc_html(implode(', ', $emails)) ?>.
            <?php else : ?>
                Ahora mismo no se adjunta a ningún correo del administrador.
            <?php endif; ?>
        </p>
        <?php if (current_user_can('manage_options')) : ?>
            <p>
                <a href="<?= esc_url(add_query_arg(['page' => OEP_KEY], admin_url('admin.php'))) ?>">
                    Ajustes del PDF
                </a>
            </p>
        <?php endif; ?>
        <?php
    }

    /**
     * URL firmada de descarga.
     *
     * @param WC_Order $order
     */
    public static function url($order)
    {
        $id = $order->get_id();

        return wp_nonce_url(
            add_query_arg([
                'action' => 'oep_download',
                'order_id' => $id,
            ], admin_url('admin-post.php')),
            'oep_download_' . $id
        );
    }

    /**
     * @param WP_Post|WC_Order|null $post
     * @return WC_Order|null
     */
    private static function order($post)
    {
        if ($post instanceof WC_Order) {
            return $post;
        }
        if ($post instanceof WP_Post) {
            return OEP_ORDER::get($post->ID);
        }

        return null;
    }
}

OEP_HOOK_ORDER::init();
