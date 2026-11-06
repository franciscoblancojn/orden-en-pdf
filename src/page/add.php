<?php

add_action('admin_menu', function () {
    add_menu_page(
        'Orden en PDF',       // Título de la página
        'Orden en PDF',       // Nombre en el menú
        'manage_options',     // Permisos
        OEP_KEY,              // Slug
        'OEP_PAGE_VIEW',
        'dashicons-media-document',
        56                    // Debajo de Productos de WooCommerce
    );
});

function OEP_PAGE_VIEW()
{
    require_once OEP_DIR . 'src/page/page.php';
}

add_action('admin_enqueue_scripts', function ($hook) {
    if ('toplevel_page_' . OEP_KEY !== $hook) {
        return;
    }

    $css = OEP_DIR . 'src/css/admin.css';
    $js = OEP_DIR . 'src/js/admin.js';
    wp_enqueue_media();
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style('oep-admin', OEP_URL . 'src/css/admin.css', ['wp-color-picker'], filemtime($css));
    wp_enqueue_script('oep-admin', OEP_URL . 'src/js/admin.js', ['jquery', 'wp-color-picker'], filemtime($js), true);
    wp_localize_script('oep-admin', 'OEP_ADMIN', [
        'choose' => 'Elegir logo',
        'use' => 'Usar este logo',
        'siteLogo' => 'Logo del sitio',
    ]);
});
