<?php

$can_send = function_exists('WC') && WC()->mailer();
$mailer = $can_send ? WC()->mailer() : null;
$available = $mailer ? $mailer->get_emails() : [];

?>
<table class="form-table" role="presentation">
    <tr>
        <th scope="row">Correos del administrador</th>
        <td>
            <fieldset>
                <legend class="screen-reader-text">Correos a los que se adjunta el PDF</legend>
                <?php foreach (OEP_USE_DATA_CONFIG::emails() as $id => $label) : ?>
                    <?php
                    $exists = isset($available[$id]);
                    $enabled = !empty($config['attach_' . $id]);
                    ?>
                    <p>
                        <label>
                            <input type="checkbox" name="oep[attach_<?= esc_attr($id) ?>]" value="1" <?= checked($enabled, true, false) ?>>
                            <?= esc_html($label) ?>
                        </label>
                        <?php if (!$exists) : ?>
                            <span class="oep-muted">(no disponible en esta tienda)</span>
                        <?php endif; ?>
                    </p>
                <?php endforeach; ?>
            </fieldset>
            <p class="description">
                El PDF se adjunta solo a estos correos, que WooCommerce envía al administrador.
                Los correos que recibe el cliente <strong>nunca</strong> llevan el adjunto.
            </p>
            <p class="description">
                El PDF se genera en el momento del envío. Si el correo está desactivado en
                WooCommerce → Ajustes → Correos, no se envía nada y tampoco se adjunta.
            </p>
        </td>
    </tr>
</table>
