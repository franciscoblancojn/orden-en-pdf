<?php

/**
 * Guardar la configuración (formulario de la página del plugin).
 */
class OEP_API_SETTINGS
{
    const ACTION = 'oep_save_settings';

    public static function init()
    {
        add_action('admin_post_' . self::ACTION, [self::class, 'save']);
    }

    public static function save()
    {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para cambiar esta configuración.', '', ['response' => 403]);
        }
        check_admin_referer(self::ACTION);

        $config = OEP_USE_DATA_CONFIG::sanitize(wp_unslash($_POST['oep'] ?? []));
        (new OEP_USE_DATA_CONFIG())->set($config);
        OEP_LOGO::reset();

        OEP_LOG_SERVICE::add('guardar_ajustes', $config);
        set_transient('oep_respond', [
            'status' => 'ok',
            'message' => 'Configuración guardada.',
        ], 60);

        $tab = sanitize_key($_POST['oep_tab'] ?? '');
        wp_safe_redirect(add_query_arg(['page' => OEP_KEY, 'tab' => $tab], admin_url('admin.php')));
        exit;
    }
}

OEP_API_SETTINGS::init();
