<?php

use franciscoblancojn\wordpress_utils\FWUPage;
use franciscoblancojn\wordpress_utils\FWURespond;

echo FWUPage::css();

$TAGS = [
    [
        'key' => 'pdf',
        'title' => 'PDF',
    ],
    [
        'key' => 'correos',
        'title' => 'Correos',
    ],
];
$keys = wp_list_pluck($TAGS, 'key');
$defaultTag = in_array($_GET['tab'] ?? '', $keys, true) ? $_GET['tab'] : $TAGS[0]['key']; // phpcs:ignore WordPress.Security.NonceVerification
$config = (new OEP_USE_DATA_CONFIG())->get();
$respond = get_transient('oep_respond');
$last_order = OEP_ORDER::latest();

?>
<div id="page-<?= OEP_KEY ?>" class="wrap oep-page">
    <h1>Orden en PDF <span class="oep-version">v<?= esc_html(OEP_get_version()) ?></span></h1>
    <?php
    if ($respond) {
        delete_transient('oep_respond');
        FWURespond::render($respond);
    }
    $logo_uri = OEP_LOGO::data_uri();
    if ('' === $logo_uri) {
        if ('' !== OEP_LOGO::source_path()) {
            echo '<p class="fwue-message">Se encontró un logo, pero no se pudo incrustar en el PDF. Sube un JPG o SVG, o habilita la extensión GD de PHP. Mientras tanto el PDF muestra el nombre del sitio.</p>';
        } elseif (!empty($config['logo_id'])) {
            echo '<p class="fwue-message">El logo elegido no está disponible en la biblioteca de medios. Vuelve a elegirlo en la pestaña <strong>PDF</strong>: mientras tanto el PDF muestra el nombre del sitio.</p>';
        } else {
            echo '<p class="fwue-message">No hay logo en el tema ni en el plugin: el PDF mostrará el nombre del sitio. Puedes subir un logo en la pestaña <strong>PDF</strong>.</p>';
        }
    }
    ?>
    <?php FWUPage::tabs($TAGS, $defaultTag); ?>
    <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>" class="oep-form">
        <input type="hidden" name="action" value="<?= esc_attr(OEP_API_SETTINGS::ACTION) ?>">
        <input type="hidden" name="oep_tab" value="<?= esc_attr($defaultTag) ?>" id="oep-tab">
        <?php wp_nonce_field(OEP_API_SETTINGS::ACTION); ?>
        <?php foreach ($TAGS as $value) : ?>
            <div class="tab-content <?= $value['key'] === $defaultTag ? 'nav-tab-active' : '' ?>" id="<?= esc_attr($value['key']) ?>">
                <?php require OEP_DIR . 'src/page/sections/' . $value['key'] . '.php'; ?>
            </div>
        <?php endforeach; ?>
        <?php submit_button('Guardar cambios'); ?>
    </form>
</div>
<?php

echo FWUPage::js(OEP_KEY);
