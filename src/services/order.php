<?php

/**
 * Datos de un pedido listos para la plantilla del PDF.
 *
 * Todo se devuelve como texto plano (sin HTML de WooCommerce), para poder escaparlo
 * una sola vez en la plantilla.
 */
class OEP_ORDER
{
    /**
     * @param int $order_id
     * @return WC_Order|null
     */
    public static function get($order_id)
    {
        if (!function_exists('wc_get_order')) {
            return null;
        }
        $order = wc_get_order(absint($order_id));

        return $order instanceof WC_Order ? $order : null;
    }

    /**
     * Último pedido de la tienda (para la vista previa de los ajustes).
     *
     * @return WC_Order|null
     */
    public static function latest()
    {
        if (!function_exists('wc_get_orders')) {
            return null;
        }
        $orders = wc_get_orders([
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
        ]);
        if (!$orders) {
            return null;
        }
        $order = reset($orders);

        return $order instanceof WC_Order ? $order : null;
    }

    /**
     * Quita etiquetas y entidades del HTML que devuelve WooCommerce.
     */
    public static function plain($html)
    {
        $text = html_entity_decode(wp_strip_all_tags((string) $html), ENT_QUOTES, 'UTF-8');

        return trim(str_replace("\xC2\xA0", ' ', $text));
    }

    /**
     * Precio con el formato de la tienda, sin HTML.
     *
     * @param float         $amount
     * @param WC_Order|null $order
     */
    public static function money($amount, $order = null)
    {
        $args = [];
        if ($order instanceof WC_Order) {
            $args['currency'] = $order->get_currency();
        }

        return self::plain(wc_price($amount, $args));
    }

    /**
     * @param WC_Order $order
     */
    public static function date($order)
    {
        $date = $order->get_date_created();
        if (!$date) {
            return '';
        }

        return self::plain(wc_format_datetime($date));
    }

    /**
     * @param WC_Order $order
     */
    public static function status($order)
    {
        return self::plain(wc_get_order_status_name($order->get_status()));
    }

    /**
     * @param WC_Order $order
     */
    public static function payment($order)
    {
        $title = $order->get_payment_method_title();
        if ('' === $title) {
            $title = $order->get_payment_method();
        }

        return self::plain($title);
    }

    /**
     * Métodos de envío del pedido, separados por coma.
     *
     * @param WC_Order $order
     */
    public static function shipping_method($order)
    {
        $methods = [];
        foreach ($order->get_shipping_methods() as $method) {
            $name = self::plain($method->get_name());
            if ('' !== $name) {
                $methods[] = $name;
            }
        }

        return implode(', ', array_unique($methods));
    }

    /**
     * Nombre del departamento / estado a partir del código guardado en el pedido (ANT → Antioquia).
     *
     * Usa la lista de WooCommerce (WC()->countries->get_states()), que incluye los estados que
     * agregan otros plugins con el filtro woocommerce_states. WooCommerce guarda los de Colombia
     * como "CO-ANT"; algunos plugins usan "ANT": se prueban las dos formas. Si no se encuentra,
     * se devuelve el texto tal cual (pedidos con el departamento escrito a mano).
     *
     * @param string $country_code
     * @param string $state
     */
    public static function state_name($country_code, $state)
    {
        $state = self::plain($state);
        if ('' === $state || '' === $country_code || !function_exists('WC') || !WC() || empty(WC()->countries)) {
            return $state;
        }
        $states = WC()->countries->get_states($country_code);
        if (!is_array($states) || !$states) {
            return $state;
        }
        $prefix = $country_code . '-';
        $candidates = [$state, strtoupper($state)];
        if (0 === stripos($state, $prefix)) {
            $candidates[] = strtoupper(substr($state, strlen($prefix)));
        } else {
            $candidates[] = $prefix . strtoupper($state);
        }
        foreach ($candidates as $key) {
            if (isset($states[$key]) && '' !== (string) $states[$key]) {
                return self::plain($states[$key]);
            }
        }

        return $state;
    }

    /**
     * Dirección de facturación o de envío.
     *
     * @param WC_Order $order
     * @param string   $type billing|shipping
     */
    public static function address($order, $type)
    {
        $is_billing = 'billing' === $type;
        $get = function ($field) use ($order, $type) {
            return (string) $order->{'get_' . $type . '_' . $field}();
        };

        $name = $is_billing ? $order->get_formatted_billing_full_name() : $order->get_formatted_shipping_full_name();
        $country_code = $get('country');
        $countries = [];
        if (function_exists('WC') && WC() && isset(WC()->countries) && WC()->countries) {
            $countries = WC()->countries->get_countries();
        }
        $country = isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
        $state = self::state_name($country_code, $get('state'));

        $lines = [];
        $company = self::plain($get('company'));
        if ('' !== $company) {
            $lines[] = $company;
        }
        $address = trim(self::plain($get('address_1') . ' ' . $get('address_2')));
        if ('' !== $address) {
            $lines[] = $address;
        }
        // "Ciudad, Departamento Código postal" (p. ej. "Medellín, Antioquia 050001").
        $city = trim(implode(', ', array_filter([
            self::plain($get('city')),
            $state,
        ])) . ' ' . self::plain($get('postcode')));
        if ('' !== $city) {
            $lines[] = $city;
        }
        $country = self::plain($country);
        if ('' !== $country) {
            $lines[] = $country;
        }

        return [
            'name' => self::plain($name),
            'company' => $company,
            'lines' => $lines,
            'email' => $is_billing ? self::plain($get('email')) : '',
            'phone' => $is_billing ? self::plain($get('phone')) : '',
        ];
    }

    /**
     * @param WC_Order $order
     */
    public static function items($order)
    {
        $items = [];

        foreach ($order->get_items() as $item) {
            $qty = (int) $item->get_quantity();
            $subtotal = (float) $order->get_line_subtotal($item, false, true);
            $unit = $qty > 0 ? $subtotal / $qty : $subtotal;

            $meta = [];
            foreach ($item->get_formatted_meta_data('_') as $meta_item) {
                $label = self::plain($meta_item->display_key);
                $value = self::plain($meta_item->display_value);
                if ('' !== $label && '' !== $value) {
                    $meta[] = $label . ': ' . $value;
                }
            }

            $sku = '';
            $image = '';
            $product = $item->get_product();
            if ($product) {
                $sku = (string) $product->get_sku();
                $image = self::item_image($product);
            }

            $items[] = [
                'name' => self::plain($item->get_name()),
                'sku' => self::plain($sku),
                'qty' => $qty,
                'unit' => self::money($unit, $order),
                'total' => self::money($subtotal, $order),
                'meta' => $meta,
                'image' => $image,
            ];
        }

        return $items;
    }

    /**
     * Miniatura del producto o, si es una variación, la suya propia.
     *
     * @param WC_Product $product
     * @return string data URI, o '' si no hay imagen.
     */
    private static function item_image($product)
    {
        $image_id = 0;
        if ($product->is_type('variation')) {
            // `get_image_id()` de la variación puede devolver la del padre; se
            // prefiere la miniatura propia si existe.
            $image_id = (int) get_post_thumbnail_id($product->get_id());
        }
        if (!$image_id) {
            $image_id = (int) $product->get_image_id();
        }
        if (!$image_id) {
            return '';
        }

        $path = self::image_path($image_id);
        if ('' === $path) {
            return '';
        }

        return OEP_IMAGE::data_uri($path, 120);
    }

    /**
     * Archivo de la miniatura de WooCommerce (100 × 100) o, si no existe, la
     * imagen original.
     *
     * @param int $image_id
     * @return string
     */
    private static function image_path($image_id)
    {
        $path = (string) get_attached_file($image_id);
        if ('' === $path || !is_file($path)) {
            return '';
        }

        $meta = wp_get_attachment_metadata($image_id);
        foreach (['woocommerce_gallery_thumbnail', 'thumbnail'] as $size) {
            if (!empty($meta['sizes'][$size]['file'])) {
                $candidate = trailingslashit(dirname($path)) . $meta['sizes'][$size]['file'];
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return $path;
    }

    /**
     * Filas de totales propias de WooCommerce (subtotal, descuento, envío, impuestos, total…).
     *
     * @param WC_Order $order
     */
    public static function totals($order)
    {
        $rows = [];

        foreach ($order->get_order_item_totals() as $key => $row) {
            // El método de pago ya se muestra en «Datos del pedido».
            if ('payment_method' === $key) {
                continue;
            }
            $rows[] = [
                'key' => $key,
                'label' => self::plain($row['label'] ?? ''),
                'value' => self::plain($row['value'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * Notas del pedido (internas y visibles para el cliente).
     *
     * @param WC_Order $order
     */
    public static function notes($order)
    {
        if (!function_exists('wc_get_order_notes')) {
            return [];
        }
        // Se piden las 50 más recientes y se muestran en orden cronológico.
        $notes = wc_get_order_notes([
            'order_id' => $order->get_id(),
            'orderby' => 'date_created',
            'order' => 'DESC',
            'limit' => 50,
        ]);
        $notes = is_array($notes) ? array_reverse($notes) : [];
        $out = [];

        foreach ($notes as $note) {
            $out[] = [
                'date' => self::plain(wc_format_datetime($note->date_created)),
                'content' => self::plain($note->content),
                'customer' => !empty($note->customer_note),
            ];
        }

        return $out;
    }

    /**
     * Metadato del pedido, también si otro plugin lo guardó a la manera antigua.
     *
     * Con HPOS (tablas de pedidos de WooCommerce) $order->get_meta() solo lee la tabla de
     * metadatos de pedidos. Hay plugins que siguen guardando con update_post_meta() en la
     * entrada del pedido (wp_postmeta): si el pedido no lo tiene, se busca ahí.
     *
     * @param WC_Order $order
     * @param string   $key
     * @return mixed
     */
    public static function meta($order, $key)
    {
        $value = $order->get_meta($key, true);
        if (('' === $value || null === $value || false === $value) && $order->get_id()) {
            $value = get_post_meta($order->get_id(), $key, true);
        }

        /**
         * Permite resolver un campo guardado en otro lugar (p. ej. una opción de otro plugin).
         *
         * @param mixed    $value
         * @param string   $key
         * @param WC_Order $order
         */
        return apply_filters('oep_order_meta', $value, $key, $order);
    }

    /**
     * Documento del cliente (cédula, NIT…) a partir de los metadatos configurados.
     *
     * Se revisan todos los campos en orden y se devuelven los valores distintos, unidos con " / ":
     * el cliente puede haberlo escrito en cualquiera de los campos del checkout (o en los dos).
     *
     * @param WC_Order $order
     * @return array{label: string, value: string}
     */
    public static function document($order)
    {
        $config = (new OEP_USE_DATA_CONFIG())->get();
        $values = [];
        foreach (OEP_USE_DATA_CONFIG::document_fields($config['document_fields']) as $key) {
            $value = self::meta($order, $key);
            if (!is_scalar($value)) {
                continue;
            }
            $value = self::plain((string) $value);
            // Misma cédula escrita con puntos o espacios en el otro campo: no repetirla.
            $norm = preg_replace('/[\s.\-]/', '', $value);
            if ('' !== $value && !isset($values[$norm])) {
                $values[$norm] = $value;
            }
        }

        return [
            'label' => self::plain($config['document_label']),
            'value' => implode(' / ', $values),
        ];
    }

    /**
     * Todos los datos que usa la plantilla.
     *
     * @param WC_Order $order
     */
    public static function data($order)
    {
        $billing = self::address($order, 'billing');
        $shipping = self::address($order, 'shipping');

        return [
            'id' => $order->get_id(),
            'number' => (string) $order->get_order_number(),
            'date' => self::date($order),
            'status' => self::status($order),
            'payment' => self::payment($order),
            'transaction' => self::plain($order->get_transaction_id()),
            'shipping_method' => self::shipping_method($order),
            'customer_id' => (int) $order->get_customer_id(),
            'customer_note' => self::plain($order->get_customer_note()),
            'billing' => $billing,
            'document' => self::document($order),
            'shipping' => $shipping,
            'has_shipping' => (bool) $order->has_shipping_address(),
            'items' => self::items($order),
            'totals' => self::totals($order),
            'notes' => self::notes($order),
        ];
    }
}
