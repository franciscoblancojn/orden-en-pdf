<?php
/*
Plugin Name: Orden en PDF
Plugin URI: https://github.com/franciscoblancojn/orden-en-pdf
Description: Descarga el pedido en PDF desde el detalle del pedido y lo adjunta a los correos del administrador (nuevo, cancelado y fallido). El PDF carga el logo del sitio, los datos del cliente, los productos, los totales y las notas.
Version: 1.0.0
Author: franciscoblancojn
Author URI: https://franciscoblanco.vercel.app/
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: orden-en-pdf
Requires at least: 5.8
Requires PHP: 7.4
WC requires at least: 5.0
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('is_plugin_active'))
    require_once(ABSPATH . '/wp-admin/includes/plugin.php');

require_once __DIR__ . '/libs/autoload.php';

//OEP_
define("OEP_KEY", 'OEP');
define("OEP_FILE", __FILE__);
define("OEP_CONFIG", 'OEP_CONFIG');
define("OEP_TMP_DIR", 'orden-en-pdf-tmp');
define("OEP_REPOSITORY", 'franciscoblancojn/orden-en-pdf');
define("OEP_LOG", true);
define("OEP_LOG_KEY", "OEP_LOG");
define("OEP_LOG_COUNT", 100);
define("OEP_BASENAME", plugin_basename(__FILE__));
define("OEP_DIR", plugin_dir_path(__FILE__));
define("OEP_URL", plugin_dir_url(__FILE__));

function OEP_get_version()
{
    $plugin_data = get_plugin_data(__FILE__, false, false);
    return $plugin_data['Version'];
}

use franciscoblancojn\wordpress_utils\FWUUpdate;
use franciscoblancojn\wordpress_utils\FWUSystemLog;

if (is_admin()) {
    FWUUpdate::init([
        'basename' => OEP_BASENAME,
        'dir' => OEP_DIR,
        'file' => "index.php",
        'path_repository' => OEP_REPOSITORY,
        'branch' => 'master',
        'token_array_split' => [
            "g",
            "h",
            "p",
            "_",
            "G",
            "4",
            "W",
            "E",
            "W",
            "F",
            "p",
            "V",
            "U",
            "E",
            "F",
            "V",
            "x",
            "F",
            "U",
            "n",
            "b",
            "M",
            "k",
            "P",
            "R",
            "x",
            "o",
            "f",
            "t",
            "Y",
            "8",
            "z",
            "j",
            "t",
            "4",
            "E",
            "x",
            "b",
            "i",
            "9"
        ]
    ]);

    FWUSystemLog::init(OEP_KEY);
}

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    function OEP_Error_Install_o_Active()
    {
?>
        <div class="notice notice-error is-dismissible">
            <p>
                Orden en PDF requiere el plugin "WooCommerce" activo para funcionar.
            </p>
        </div>
<?php
    }
    add_action('admin_notices', 'OEP_Error_Install_o_Active');
} else {
    require_once OEP_DIR . 'src/_.php';
}
