<?php

use Dompdf\Dompdf;
use Dompdf\Image\Cache;
use Dompdf\Options;

/**
 * Generación del PDF del pedido con DOMPDF.
 *
 * El documento se arma en HTML/CSS con la plantilla src/templates/pdf.php. El logo se
 * incrusta como data URI, así que DOMPDF no necesita acceder a archivos ni a internet.
 */
class OEP_PDF
{
    /**
     * HTML completo del pedido.
     *
     * @param WC_Order $order
     * @param bool     $with_logo
     */
    public static function html($order, $with_logo = true)
    {
        $data = OEP_ORDER::data($order);
        $config = (new OEP_USE_DATA_CONFIG())->get();
        $logo = $with_logo ? OEP_LOGO::data_uri() : '';
        $site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES, 'UTF-8');
        $company = '' !== trim($config['company_name']) ? $config['company_name'] : $site_name;

        ob_start();
        include OEP_DIR . 'src/templates/pdf.php';

        return (string) ob_get_clean();
    }

    /**
     * @param WC_Order $order
     * @return Dompdf
     */
    public static function render($order)
    {
        $config = (new OEP_USE_DATA_CONFIG())->get();

        try {
            return self::build($order, $config, true);
        } catch (\Throwable $e) {
            // Un logo en un formato que DOMPDF no puede incrustar no debe dejar sin PDF.
            OEP_LOG_SERVICE::add('error_pdf', [
                'order_id' => $order->get_id(),
                'message' => $e->getMessage(),
            ]);
            Cache::clear();

            return self::build($order, $config, false);
        }
    }

    /**
     * @param WC_Order $order
     * @param array    $config
     * @param bool     $with_logo
     * @return Dompdf
     */
    private static function build($order, $config, $with_logo)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('defaultPaperSize', 'a4');
        $options->set('tempDir', get_temp_dir());
        $options->set('chroot', OEP_DIR);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(self::html($order, $with_logo), 'UTF-8');
        $dompdf->setPaper('letter' === $config['paper'] ? 'letter' : 'a4', 'portrait');
        $dompdf->render();
        self::footer($dompdf, $config);

        return $dompdf;
    }

    /**
     * PDF como cadena binaria.
     *
     * @param WC_Order $order
     */
    public static function output($order)
    {
        try {
            return (string) self::render($order)->output();
        } catch (\Throwable $e) {
            OEP_LOG_SERVICE::add('error_pdf', [
                'order_id' => $order->get_id(),
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Envía el PDF al navegador como descarga.
     *
     * @param WC_Order $order
     */
    public static function stream($order)
    {
        $pdf = self::output($order);
        if ('' === $pdf) {
            wp_die('No se pudo generar el PDF del pedido.', '', ['response' => 500]);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . self::filename($order) . '"');
        header('Content-Length: ' . strlen($pdf));
        header('X-Content-Type-Options: nosniff');

        echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Nombre de descarga: pedido-<número>.pdf
     *
     * @param WC_Order $order
     */
    public static function filename($order)
    {
        $number = (string) $order->get_order_number();
        $number = preg_replace('/[^A-Za-z0-9._-]+/', '-', $number);
        $number = trim($number, '-_.');
        if ('' === $number) {
            $number = (string) $order->get_id();
        }

        return 'pedido-' . $number . '.pdf';
    }

    /**
     * Escribe el PDF en un archivo temporal para adjuntarlo a un correo.
     *
     * @param WC_Order $order
     * @return string ruta del archivo, o '' si falló.
     */
    public static function temp_file($order)
    {
        $dir = self::uploads_dir();
        if ('' === $dir) {
            return '';
        }
        self::cleanup($dir);

        $pdf = self::output($order);
        if ('' === $pdf) {
            return '';
        }

        $name = preg_replace('/\.pdf$/', '', self::filename($order));
        $path = trailingslashit($dir) . $name . '-' . substr(md5(uniqid('', true)), 0, 6) . '.pdf';
        if (false === @file_put_contents($path, $pdf)) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return '';
        }

        return $path;
    }

    /**
     * Carpeta protegida dentro de uploads para los adjuntos temporales.
     */
    public static function uploads_dir()
    {
        $uploads = wp_upload_dir(null, false);
        if (empty($uploads['basedir'])) {
            return '';
        }
        $dir = trailingslashit($uploads['basedir']) . OEP_TMP_DIR;
        if (!wp_mkdir_p($dir)) {
            return '';
        }
        self::protect($dir);

        return $dir;
    }

    private static function protect($dir)
    {
        $index = trailingslashit($dir) . 'index.php';
        if (!is_file($index)) {
            @file_put_contents($index, "<?php\n// Silence is golden.\n"); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }
        $htaccess = trailingslashit($dir) . '.htaccess';
        if (!is_file($htaccess)) {
            $rules = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
                . "<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n";
            @file_put_contents($htaccess, $rules); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }
    }

    /**
     * Borra adjuntos viejos si un envío anterior no llegó a limpiarlos.
     */
    public static function cleanup($dir = '')
    {
        if ('' === $dir) {
            $dir = self::uploads_dir();
        }
        if ('' === $dir) {
            return;
        }
        $files = glob(trailingslashit($dir) . '*.pdf');
        if (!is_array($files)) {
            return;
        }
        $limit = time() - 6 * HOUR_IN_SECONDS;
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $limit) {
                @unlink($file); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            }
        }
    }

    /**
     * Pie de página en todas las páginas: texto configurado + «Página X de Y».
     */
    private static function footer($dompdf, $config)
    {
        $canvas = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font = $metrics->getFont('DejaVu Sans', 'normal');
        if (!$font) {
            return;
        }
        $size = 7.5;
        $y = $canvas->get_height() - 24;
        $margin = 36;
        $color = [0.45, 0.49, 0.53];

        $page = 'Página {PAGE_NUM} de {PAGE_COUNT}';
        $page_width = $metrics->getTextWidth($page, $font, $size);
        $page_x = $canvas->get_width() - $margin - $page_width;

        $left = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($config['footer_text'])));
        if ('' !== $left) {
            // Deja 12 px de separación con el número de página; si no cabe, se recorta.
            $max = $page_x - $margin - 12;
            $left = self::fit($left, $font, $size, $max, $metrics);
            if ('' !== $left) {
                $canvas->page_text($margin, $y, $left, $font, $size, $color);
            }
        }

        $canvas->page_text($page_x, $y, $page, $font, $size, $color);
    }

    /**
     * Recorta un texto para que quepa en un ancho dado, con puntos suspensivos.
     *
     * @param string $text
     * @param mixed  $font
     * @param float  $size
     * @param float  $max
     * @param mixed  $metrics
     */
    private static function fit($text, $font, $size, $max, $metrics)
    {
        if ($max <= 0) {
            return '';
        }
        if ($metrics->getTextWidth($text, $font, $size) <= $max) {
            return $text;
        }

        $suffix = '...';
        $max -= $metrics->getTextWidth($suffix, $font, $size);
        if ($max <= 0) {
            return '';
        }

        $length = mb_strlen($text, 'UTF-8');
        while ($length > 0 && $metrics->getTextWidth(mb_substr($text, 0, $length, 'UTF-8'), $font, $size) > $max) {
            $length--;
        }
        if ($length <= 0) {
            return '';
        }

        return rtrim(mb_substr($text, 0, $length, 'UTF-8')) . $suffix;
    }
}
