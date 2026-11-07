<?php

/**
 * Emojis en el PDF.
 *
 * DOMPDF (a través de Cpdf) solo maneja caracteres del plano básico de Unicode
 * (hasta U+FFFF): los emojis modernos, como U+1F3ED, no se pueden medir ni dibujar.
 * `OEPEmoji.ttf` es una versión de Noto Emoji con esos emojis remapeados al área de
 * uso privado (U+E000 en adelante), y `src/data/emoji-map.php` guarda la
 * correspondencia. Así DOMPDF ve un carácter normal y dibuja el glifo real.
 *
 * Cada emoji se envuelve en un `<span class="oep-emoji">` con la fuente OEP Emoji.
 * Si la fuente no se puede registrar (por ejemplo, uploads sin permiso de
 * escritura), los emojis se eliminan para no imprimir glifos rotos.
 */
class OEP_EMOJI
{
    const FAMILY = 'OEP Emoji';
    const FONT = 'assets/fonts/OEPEmoji.ttf';

    private static $ready = false;
    private static $map = null;
    private static $pattern = null;

    /**
     * FontMetrics solo se entera del directorio de fuentes al construirse. Se
     * configura desde OEP_PDF::build() antes de crear Dompdf.
     *
     * @return string directorio escribible, o '' si no hay.
     */
    public static function font_dir()
    {
        if (!function_exists('wp_upload_dir') || !function_exists('wp_mkdir_p')) {
            return '';
        }

        $uploads = wp_upload_dir(null, false);
        if (empty($uploads['basedir'])) {
            return '';
        }

        $dir = trailingslashit($uploads['basedir']) . 'orden-en-pdf-fonts';
        if (!wp_mkdir_p($dir)) {
            return '';
        }

        self::protect($dir);

        return $dir;
    }

    /**
     * Registra la fuente de emojis en la instancia de DOMPDF.
     *
     * @param \Dompdf\Dompdf $dompdf
     */
    public static function register($dompdf)
    {
        self::$ready = false;

        $font = OEP_DIR . self::FONT;
        if (!is_readable($font)) {
            return;
        }

        try {
            $ok = $dompdf->getFontMetrics()->registerFont([
                'family' => self::FAMILY,
                'weight' => 'normal',
                'style' => 'normal',
            ], 'file://' . str_replace('\\', '/', $font));
        } catch (\Throwable $e) {
            $ok = false;
        }

        self::$ready = (bool) $ok;
    }

    /**
     * @return bool
     */
    public static function ready()
    {
        return self::$ready;
    }

    /**
     * Escapa el texto y envuelve cada emoji en un span con la fuente OEP Emoji.
     *
     * @param string $text
     * @return string HTML seguro.
     */
    public static function wrap($text)
    {
        $text = esc_html((string) $text);
        if ('' === $text) {
            return '';
        }

        return (string) preg_replace_callback(self::pattern(), function ($match) {
            if (!self::$ready) {
                return '';
            }
            $pua = self::convert($match[0]);

            return '' === $pua ? '' : '<span class="oep-emoji">' . $pua . '</span>';
        }, $text);
    }

    /**
     * Divide un texto plano en segmentos de texto normal y de emojis.
     *
     * Se usa en el pie, que se dibuja con page_text() y no acepta HTML.
     *
     * @param string $text
     * @return array<int,array{text:string,emoji:bool}>
     */
    public static function segments($text)
    {
        $text = (string) $text;
        if ('' === $text) {
            return [];
        }

        $segments = [];
        $offset = 0;
        $matched = preg_match_all(self::pattern(), $text, $matches, PREG_OFFSET_CAPTURE);
        if (false === $matched || 0 === $matched) {
            return [['text' => $text, 'emoji' => false]];
        }

        foreach ($matches[0] as $match) {
            $cluster = $match[0];
            $position = $match[1];

            if ($position > $offset) {
                $segments[] = [
                    'text' => substr($text, $offset, $position - $offset),
                    'emoji' => false,
                ];
            }

            if (self::$ready) {
                $pua = self::convert($cluster);
                if ('' !== $pua) {
                    $segments[] = ['text' => $pua, 'emoji' => true];
                }
            }

            $offset = $position + strlen($cluster);
        }

        if ($offset < strlen($text)) {
            $segments[] = ['text' => substr($text, $offset), 'emoji' => false];
        }

        return $segments;
    }

    /**
     * Quita los emojis de un texto plano.
     *
     * @param string $text
     * @return string
     */
    public static function strip($text)
    {
        return (string) preg_replace(self::pattern(), '', (string) $text);
    }

    /**
     * Convierte un emoji (o una secuencia de emojis) a los caracteres del área de
     * uso privado que entiende OEPEmoji.ttf. Se descartan los selectores de
     * variación y los ZWJ, que DOMPDF no puede aplicar.
     *
     * @param string $cluster
     * @return string
     */
    private static function convert($cluster)
    {
        $map = self::map();
        if (empty($map)) {
            return '';
        }

        $out = '';
        $characters = preg_split('//u', $cluster, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($characters)) {
            return '';
        }

        foreach ($characters as $character) {
            $codepoint = mb_ord($character, 'UTF-8');
            if (0xFE0E === $codepoint || 0xFE0F === $codepoint || 0x200D === $codepoint) {
                continue;
            }
            if ($codepoint >= 0xE0020 && $codepoint <= 0xE007F) {
                continue;
            }
            if (isset($map[$codepoint])) {
                $out .= mb_chr($map[$codepoint], 'UTF-8');
            }
        }

        return $out;
    }

    /**
     * @return array<int,int>
     */
    private static function map()
    {
        if (null === self::$map) {
            self::$map = function_exists('oep_emoji_map') ? (array) oep_emoji_map() : [];
        }

        return self::$map;
    }

    /**
     * Un emoji puede ser un carácter, un carácter + selector de variación o una
     * secuencia con ZWJ. Se agrupan para no partir las secuencias.
     *
     * @return string
     */
    private static function pattern()
    {
        if (null !== self::$pattern) {
            return self::$pattern;
        }

        // Extended_Pictographic existe en PCRE2 con Unicode 11 o superior. Si el
        // servidor trae una versión más vieja se usa un rango equivalente.
        $base = '\p{Extended_Pictographic}';
        if (false === @preg_match('/' . $base . '/u', '🏭')) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
            $base = '[\x{00A9}\x{00AE}\x{2122}\x{2139}\x{2190}-\x{21FF}\x{2300}-\x{23FF}'
                . '\x{24C2}\x{25A0}-\x{25FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}'
                . '\x{1F000}-\x{1FAFF}]';
        }

        $suffix = '[\x{FE0F}\x{1F3FB}-\x{1F3FF}]';
        self::$pattern = '/(?:' . $base . '(?:' . $suffix . '|\x{200D}' . $base . $suffix . '?)*'
            . '|[\x{1F1E6}-\x{1F1FF}]{2}|[0-9#*]\x{FE0F}?\x{20E3})/u';

        return self::$pattern;
    }

    /**
     * Bloquea el acceso HTTP al directorio de fuentes.
     *
     * @param string $dir
     */
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
}
