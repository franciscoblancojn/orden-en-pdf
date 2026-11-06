<?php

use franciscoblancojn\wordpress_utils\FWUSystemLog;

/**
 * Registro tolerante.
 *
 * `FWUSystemLog` solo se declara si existen las funciones de wp-admin
 * (`add_options_page`). En el frontend se carga `wp-admin/includes/plugin.php` desde
 * index.php, pero si por algún motivo no está, el plugin no debe romper el envío de
 * correos ni la descarga del PDF por intentar escribir en el log.
 */
class OEP_LOG_SERVICE
{
    /**
     * @param string $type
     * @param array  $data
     */
    public static function add($type, $data = [])
    {
        if (!class_exists(FWUSystemLog::class)) {
            return;
        }

        FWUSystemLog::add(OEP_KEY, [
            'type' => (string) $type,
            'data' => (array) $data,
        ]);
    }
}
