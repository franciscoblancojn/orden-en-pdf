<?php

$logo_id = (int) $config['logo_id'];
$site_logo_id = (int) get_theme_mod('custom_logo');
$preview_id = $logo_id ? $logo_id : $site_logo_id;
$site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES, 'UTF-8');

?>
<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><label for="oep-logo-id">Logo</label></th>
        <td>
            <div class="oep-logo">
                <div class="oep-logo__preview" id="oep-logo-preview">
                    <?php if ($preview_id) : ?>
                        <?= wp_get_attachment_image($preview_id, 'medium', false, ['class' => 'oep-logo__img', 'alt' => '']) ?>
                    <?php else : ?>
                        <span class="oep-muted">Sin logo</span>
                    <?php endif; ?>
                </div>
                <div class="oep-logo__actions">
                    <input type="hidden" name="oep[logo_id]" id="oep-logo-id" value="<?= esc_attr($logo_id) ?>">
                    <button type="button" class="button" id="oep-logo-select">Elegir logo</button>
                    <button type="button" class="button-link" id="oep-logo-remove" <?= $logo_id ? '' : 'style="display:none"' ?>>Quitar (usar logo del sitio)</button>
                </div>
            </div>
            <p class="description">
                Si no eliges uno, se usa el logo del tema (Personalizar → Identidad del sitio).
                Si tampoco hay, se muestra el nombre del sitio en texto.
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="oep-logo-width">Ancho del logo</label></th>
        <td>
            <input type="number" id="oep-logo-width" name="oep[logo_width]" value="<?= esc_attr((int) $config['logo_width']) ?>" class="small-text" min="40" max="400" step="10">
            <span class="oep-muted">px (entre 40 y 400)</span>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="oep-company-name">Nombre de la empresa</label></th>
        <td>
            <input type="text" id="oep-company-name" name="oep[company_name]" value="<?= esc_attr($config['company_name']) ?>" class="regular-text" placeholder="<?= esc_attr($site_name) ?>">
            <p class="description">Si se deja vacío, se usa el nombre del sitio.</p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="oep-header-text">Datos de la cabecera</label></th>
        <td>
            <textarea id="oep-header-text" name="oep[header_text]" rows="3" class="large-text" placeholder="NIT 000.000.000-0&#10;Dirección&#10;Teléfono"><?= esc_textarea($config['header_text']) ?></textarea>
            <p class="description">Texto opcional debajo del logo (NIT, dirección, teléfono…). Admite varias líneas.</p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="oep-document-title">Título del documento</label></th>
        <td>
            <input type="text" id="oep-document-title" name="oep[document_title]" value="<?= esc_attr($config['document_title']) ?>" class="regular-text" placeholder="Pedido">
            <p class="description">Aparece arriba a la derecha, junto al número del pedido.</p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="oep-footer-text">Pie de página</label></th>
        <td>
            <textarea id="oep-footer-text" name="oep[footer_text]" rows="2" class="large-text" placeholder="Gracias por tu compra."><?= esc_textarea($config['footer_text']) ?></textarea>
            <p class="description">Se repite en todas las páginas, a la izquierda. El número de página va a la derecha.</p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="oep-paper">Tamaño del papel</label></th>
        <td>
            <select id="oep-paper" name="oep[paper]">
                <?php foreach (OEP_USE_DATA_CONFIG::PAPERS as $key => $label) : ?>
                    <option value="<?= esc_attr($key) ?>" <?= selected($config['paper'], $key, false) ?>><?= esc_html($label) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description">Orientación vertical.</p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="oep-accent-color">Color de acento</label></th>
        <td>
            <input type="text" id="oep-accent-color" name="oep[accent_color]" value="<?= esc_attr($config['accent_color']) ?>" class="oep-color" data-default-color="#1f2933">
            <p class="description">Se usa en la línea de la cabecera, la tabla de productos y los totales.</p>
        </td>
    </tr>
    <tr>
        <th scope="row">Contenido</th>
        <td>
            <label>
                <input type="checkbox" name="oep[show_customer_note]" value="1" <?= checked($config['show_customer_note'], 1, false) ?>>
                Incluir la nota que deja el cliente al finalizar la compra
            </label>
            <br>
            <label>
                <input type="checkbox" name="oep[show_notes]" value="1" <?= checked($config['show_notes'], 1, false) ?>>
                Incluir el historial de notas del pedido (internas y visibles para el cliente)
            </label>
            <p class="description">El PDF siempre incluye número, fecha, estado, pago, envío, cliente, direcciones, productos y totales.</p>
        </td>
    </tr>
    <?php if ($last_order) : ?>
        <tr>
            <th scope="row">Vista previa</th>
            <td>
                <a class="button" href="<?= esc_url(OEP_HOOK_ORDER::url($last_order)) ?>" target="_blank" rel="noopener">
                    Ver PDF del último pedido (#<?= esc_html($last_order->get_order_number()) ?>)
                </a>
                <p class="description">Abre el PDF con los ajustes guardados. Si cambias algo, guarda primero.</p>
            </td>
        </tr>
    <?php endif; ?>
</table>
