<?php

/**
 * Logo del PDF: el elegido en los ajustes o, si no hay, el logo del sitio.
 *
 * DOMPDF no puede leer archivos fuera de su chroot y no conviene habilitar recursos
 * remotos: la imagen se incrusta como data URI. DOMPDF solo puede incrustar JPEG y SVG
 * sin la extensión GD; si falta GD, las demás imágenes se convierten a JPEG con Imagick
 * o con la API de imágenes de WordPress. Si no se puede convertir, el PDF usa el nombre
 * del sitio (nunca falla por el logo).
 */
class OEP_LOGO
{
    private static $cache = null;
    private static $source = '';
    private static $failed = false;

    public static function reset()
    {
        self::$cache = null;
        self::$source = '';
        self::$failed = false;
    }

    /**
     * Ruta del archivo que se intentó incrustar (vacía si no hay logo).
     *
     * @return string
     */
    public static function source_path()
    {
        self::data_uri();

        return self::$source;
    }

    /**
     * true si había un logo pero no se pudo incrustar (formato sin GD, archivo ilegible…).
     *
     * @return bool
     */
    public static function failed()
    {
        self::data_uri();

        return self::$failed;
    }

    /**
     * @return string data URI lista para el atributo src, o '' si no hay logo.
     */
    public static function data_uri()
    {
        if (null !== self::$cache) {
            return self::$cache;
        }

        $config = (new OEP_USE_DATA_CONFIG())->get();
        $path = '';
        if (!empty($config['logo_id'])) {
            $path = (string) get_attached_file(absint($config['logo_id']));
        }
        if ('' === $path || !is_file($path)) {
            $path = self::site_logo_path();
        }

        self::$source = $path;
        self::$cache = '' !== $path ? self::encode($path) : '';
        self::$failed = ('' !== $path && '' === self::$cache);

        return self::$cache;
    }

    /**
     * Logo configurado en el tema (Personalizar → Identidad del sitio) o ícono del sitio.
     */
    public static function site_logo_path()
    {
        $id = (int) get_theme_mod('custom_logo');
        if (!$id) {
            $id = (int) get_option('site_icon');
        }
        if (!$id) {
            return '';
        }
        $path = get_attached_file($id);

        return ($path && is_file($path)) ? $path : '';
    }

    private static function mime($path)
    {
        $info = @getimagesize($path); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        if (!empty($info['mime'])) {
            return (string) $info['mime'];
        }
        $filetype = wp_check_filetype($path);
        if (!empty($filetype['type'])) {
            return (string) $filetype['type'];
        }
        // getimagesize() no reconoce SVG.
        if ('svg' === strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            return 'image/svg+xml';
        }

        return '';
    }

    private static function gd()
    {
        return function_exists('imagecreatefrompng');
    }

    /**
     * `wp_tempnam()` está en wp-admin/includes/file.php; en el frontend (checkout,
     * correo de pedido nuevo) puede no estar cargado.
     *
     * @param string $prefix
     * @return string
     */
    private static function tempnam($prefix)
    {
        if (!function_exists('wp_tempnam')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        return (string) wp_tempnam($prefix);
    }

    /**
     * Redimensiona/convierte con la API de imágenes de WordPress.
     *
     * @return string ruta del archivo temporal, o '' si no se pudo.
     */
    private static function with_editor($path, $mime)
    {
        if (!function_exists('wp_get_image_editor')) {
            return '';
        }
        $editor = wp_get_image_editor($path);
        if (is_wp_error($editor) || !$editor->supports_mime_type($mime)) {
            return '';
        }
        $size = $editor->get_size();
        if (!empty($size['width']) && $size['width'] > 800) {
            $editor->resize(800, 800, false);
        }
        $tmp = self::tempnam('oep-logo-');
        if ('' === $tmp) {
            return '';
        }
        $saved = $editor->save($tmp, $mime);
        if (is_wp_error($saved) || empty($saved['path'])) {
            if (is_file($tmp)) {
                @unlink($tmp); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            }

            return '';
        }
        // El editor puede cambiar la extensión del archivo temporal.
        if ($saved['path'] !== $tmp && is_file($tmp)) {
            @unlink($tmp); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }

        return (string) $saved['path'];
    }

    /**
     * Convierte a JPEG (con fondo blanco si hay transparencia).
     *
     * @return string ruta del JPEG temporal, o '' si no se pudo.
     */
    private static function to_jpeg($path)
    {
        if (class_exists('\Imagick')) {
            $tmp = '';
            $image = null;
            try {
                $image = new \Imagick($path);
                $image->setImageBackgroundColor('white');
                if (defined('Imagick::ALPHACHANNEL_REMOVE')) {
                    $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                }
                $image->setImageFormat('jpeg');
                $image->setImageCompressionQuality(90);
                $tmp = self::tempnam('oep-logo-');
                if ('' !== $tmp && $image->writeImage($tmp)) {
                    $image->clear();

                    return $tmp;
                }
            } catch (\Throwable $e) {
                // Se intenta con la API de WordPress.
            }
            if ($image instanceof \Imagick) {
                $image->clear();
            }
            if ('' !== $tmp && is_file($tmp)) {
                @unlink($tmp); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            }
        }

        return self::with_editor($path, 'image/jpeg');
    }

    private static function encode($path)
    {
        $mime = self::mime($path);
        $size = (int) @filesize($path); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        $temp = '';
        $limit = 2 * 1024 * 1024;

        if ('image/jpeg' === $mime || 'image/svg+xml' === $mime) {
            // DOMPDF los incrusta sin GD. Solo se redimensiona un JPEG muy pesado.
            if ($size > $limit && 'image/jpeg' === $mime) {
                $converted = self::with_editor($path, 'image/jpeg');
                if ('' !== $converted) {
                    $temp = $path = $converted;
                    $mime = 'image/jpeg';
                }
                // Si no hay editor, se incrusta el JPEG original antes que quedarse sin logo.
            }
        } elseif (self::gd()) {
            // PNG/GIF/BMP/WebP: DOMPDF los soporta con GD. Se convierten a PNG si son grandes.
            if (!in_array($mime, ['image/png', 'image/gif', 'image/bmp', 'image/webp'], true) || $size > $limit) {
                $converted = self::with_editor($path, 'image/png');
                if ('' !== $converted) {
                    $temp = $path = $converted;
                    $mime = 'image/png';
                } elseif (!in_array($mime, ['image/png', 'image/gif', 'image/bmp', 'image/webp'], true)) {
                    return '';
                }
                // Si era un PNG/GIF válido y no se pudo convertir, se usa el original.
            }
        } else {
            // Sin GD, DOMPDF solo incrusta JPEG y SVG: PNG/GIF/WebP se pasan a JPEG.
            $converted = self::to_jpeg($path);
            if ('' === $converted) {
                return '';
            }
            $temp = $path = $converted;
            $mime = 'image/jpeg';
        }

        $data = @file_get_contents($path); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        if ('' !== $temp && is_file($temp)) {
            @unlink($temp); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }
        if (false === $data || '' === $data) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
