<?php

/**
 * Imágenes incrustadas en el PDF como data URI.
 *
 * DOMPDF no puede leer archivos fuera de su chroot y no conviene habilitar recursos
 * remotos. Sin GD, DOMPDF solo incrusta JPEG y SVG, así que PNG/GIF/WebP/BMP se
 * convierten a JPEG con Imagick o con la API de imágenes de WordPress. Si no se
 * puede convertir, la imagen se omite: el PDF sigue siendo válido.
 */
class OEP_IMAGE
{
    /**
     * @var array<string,string>
     */
    private static $cache = [];

    /**
     * @param string $path Ruta local.
     * @param int    $max  Lado máximo en píxeles (0 = solo limita archivos > 2 MB).
     * @return string data URI o '' si no se puede incrustar.
     */
    public static function data_uri($path, $max = 0)
    {
        $path = (string) $path;
        if ('' === $path || !is_file($path) || !is_readable($path)) {
            return '';
        }

        $key = $path . '|' . (int) $max . '|' . (string) @filemtime($path); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = self::encode($path, (int) $max);
        }

        return self::$cache[$key];
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
    private static function with_editor($path, $mime, $max)
    {
        if (!function_exists('wp_get_image_editor')) {
            return '';
        }
        $editor = wp_get_image_editor($path);
        if (is_wp_error($editor) || !$editor->supports_mime_type($mime)) {
            return '';
        }
        if ($max > 0) {
            $size = $editor->get_size();
            if (!empty($size['width']) && $size['width'] > $max) {
                $editor->resize($max, $max, false);
            }
        }
        $tmp = self::tempnam('oep-image-');
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
    private static function to_jpeg($path, $max)
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
                if ($max > 0 && $image->getImageWidth() > $max) {
                    $image->resizeImage($max, 0, \Imagick::FILTER_LANCZOS, 1);
                }
                $image->setImageFormat('jpeg');
                $image->setImageCompressionQuality(88);
                $tmp = self::tempnam('oep-image-');
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

        return self::with_editor($path, 'image/jpeg', $max);
    }

    private static function encode($path, $max)
    {
        $mime = self::mime($path);
        $size = (int) @filesize($path); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        $info = @getimagesize($path); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        $width = !empty($info[0]) ? (int) $info[0] : 0;
        $temp = '';
        $limit = 2 * 1024 * 1024;
        $supported = ['image/png', 'image/gif', 'image/bmp', 'image/webp'];

        if ('image/svg+xml' === $mime) {
            // DOMPDF lo incrusta sin GD.
        } elseif ('image/jpeg' === $mime) {
            // Solo se toca si hay que reducirla.
            if (($max > 0 && $width > $max) || $size > $limit) {
                $converted = self::with_editor($path, 'image/jpeg', $max > 0 ? $max : 800);
                if ('' !== $converted) {
                    $temp = $path = $converted;
                }
            }
        } elseif (self::gd()) {
            // PNG/GIF/BMP/WebP: DOMPDF los soporta con GD. Se convierten a PNG si
            // hay que reducir o el archivo es muy grande.
            if (!in_array($mime, $supported, true) || $size > $limit || ($max > 0 && $width > $max)) {
                $converted = self::with_editor($path, 'image/png', $max);
                if ('' !== $converted) {
                    $temp = $path = $converted;
                    $mime = 'image/png';
                } elseif (!in_array($mime, $supported, true)) {
                    return '';
                }
                // Si era un PNG/GIF válido y no se pudo convertir, se usa el original.
            }
        } else {
            // Sin GD, DOMPDF solo incrusta JPEG y SVG: PNG/GIF/WebP se pasan a JPEG.
            $converted = self::to_jpeg($path, $max);
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
