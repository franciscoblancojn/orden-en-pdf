<?php
/**
 * Plantilla del PDF del pedido.
 *
 * Variables disponibles (las prepara OEP_PDF::html()):
 *
 * @var WC_Order $order
 * @var array    $data    Datos de OEP_ORDER::data()
 * @var array    $config  Configuración del plugin
 * @var string   $logo    Data URI del logo ('' si no hay)
 * @var string   $company Nombre de la empresa o del sitio
 * @var string   $site_name
 */
$accent = $config['accent_color'] ? $config['accent_color'] : '#1f2933';
$billing = $data['billing'];
$shipping = $data['shipping'];
$title = trim($config['document_title']) !== '' ? $config['document_title'] : 'Pedido';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?= esc_html($title . ' ' . $data['number']) ?></title>
    <style>
        @page {
            margin: 26px 34px 48px 34px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9.5px;
            line-height: 1.45;
            color: #1f2933;
        }
        /* Los emojis se escriben en un área privada de OEPEmoji.ttf (ver OEP_EMOJI). */
        .oep-emoji {
            font-family: "OEP Emoji";
            font-weight: normal;
            font-style: normal;
        }
        h2 {
            margin: 16px 0 6px;
            font-size: 10.5px;
            font-weight: bold;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #52606d;
        }
        p {
            margin: 0 0 2px;
        }
        .header {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid <?= esc_attr($accent) ?>;
            margin-bottom: 4px;
        }
        .header td {
            vertical-align: middle;
            padding: 0 0 10px;
        }
        .header-left {
            width: 62%;
            text-align: left;
        }
        .header-right {
            width: 38%;
            text-align: right;
        }
        .logo {
            max-width: <?= (int) $config['logo_width'] ?>px;
            height: auto;
        }
        .brand {
            font-size: 19px;
            font-weight: bold;
            color: <?= esc_attr($accent) ?>;
        }
        .company-data {
            margin-top: 5px;
            font-size: 8.5px;
            line-height: 1.4;
            color: #52606d;
        }
        .doc-title {
            font-size: 9px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #7b8794;
        }
        .doc-number {
            margin-top: 1px;
            font-size: 20px;
            font-weight: bold;
            color: <?= esc_attr($accent) ?>;
        }
        .doc-date {
            margin-top: 2px;
            font-size: 8.5px;
            color: #52606d;
        }
        .cols {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-top: 10px;
        }
        .cols > tr > td {
            width: 50%;
            vertical-align: top;
        }
        .box {
            padding: 8px 10px;
            background: #f5f7fa;
            border-left: 3px solid <?= esc_attr($accent) ?>;
            page-break-inside: avoid;
        }
        .box__title {
            margin-bottom: 4px;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #7b8794;
        }
        .box__name {
            font-weight: bold;
            font-size: 10px;
        }
        .muted {
            color: #7b8794;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }
        .meta th {
            width: 18%;
            padding: 3px 8px 3px 0;
            text-align: left;
            vertical-align: top;
            font-weight: normal;
            color: #7b8794;
        }
        .meta td {
            width: 32%;
            padding: 3px 10px 3px 0;
            vertical-align: top;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }
        .items thead th {
            padding: 6px;
            background: <?= esc_attr($accent) ?>;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: .04em;
            text-align: left;
            text-transform: uppercase;
        }
        .items tbody td {
            padding: 6px;
            border-bottom: 1px solid #e4e7eb;
            vertical-align: top;
        }
        .items .num {
            width: 4%;
            text-align: center;
        }
        .items .qty {
            width: 8%;
            text-align: center;
        }
        .items .money {
            width: 15%;
            text-align: right;
            white-space: nowrap;
        }
        .item-name {
            font-weight: bold;
        }
        .item-meta {
            margin-top: 2px;
            font-size: 8px;
            color: #7b8794;
        }
        .empty {
            padding: 10px;
            text-align: center;
            color: #7b8794;
        }
        .totals-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            page-break-inside: avoid;
        }
        .totals-wrap .spacer {
            width: 55%;
        }
        .totals-wrap .totals-cell {
            width: 45%;
            vertical-align: top;
        }
        .totals {
            width: 100%;
            border-collapse: collapse;
        }
        .totals td {
            padding: 3px 0;
        }
        .totals .label {
            color: #52606d;
        }
        .totals .value {
            text-align: right;
            white-space: nowrap;
        }
        .totals .grand td {
            padding-top: 6px;
            border-top: 1px solid #cbd2d9;
            font-size: 12px;
            font-weight: bold;
            color: <?= esc_attr($accent) ?>;
        }
        .note {
            margin-top: 6px;
            padding: 7px 10px;
            background: #f5f7fa;
            border-left: 3px solid <?= esc_attr($accent) ?>;
            page-break-inside: avoid;
        }
        .note__title {
            margin-bottom: 2px;
            font-weight: bold;
        }
        .note__meta {
            margin-bottom: 2px;
            font-size: 8px;
            color: #7b8794;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="header-left">
                <?php if ('' !== $logo) : ?>
                    <img class="logo" src="<?= esc_attr($logo) ?>" alt="<?= esc_attr($company) ?>">
                <?php else : ?>
                    <div class="brand"><?= esc_html($company) ?></div>
                <?php endif; ?>
                <?php if ('' !== trim($config['header_text'])) : ?>
                    <div class="company-data"><?= nl2br(OEP_EMOJI::wrap($config['header_text'])) ?></div>
                <?php endif; ?>
            </td>
            <td class="header-right">
                <div class="doc-title"><?= esc_html($title) ?></div>
                <div class="doc-number">#<?= esc_html($data['number']) ?></div>
                <div class="doc-date"><?= esc_html($data['date']) ?></div>
            </td>
        </tr>
    </table>

    <table class="cols">
        <tr>
            <td>
                <div class="box">
                    <div class="box__title">Facturación</div>
                    <div class="box__name"><?= esc_html('' !== $billing['name'] ? $billing['name'] : '—') ?></div>
                    <?php foreach ($billing['lines'] as $line) : ?>
                        <p><?= esc_html($line) ?></p>
                    <?php endforeach; ?>
                    <?php if ('' !== $billing['email']) : ?>
                        <p><?= esc_html($billing['email']) ?></p>
                    <?php endif; ?>
                    <?php if ('' !== $billing['phone']) : ?>
                        <p><?= esc_html($billing['phone']) ?></p>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <div class="box">
                    <div class="box__title">Envío</div>
                    <?php if ($data['has_shipping'] && !empty($shipping['lines'])) : ?>
                        <div class="box__name"><?= esc_html('' !== $shipping['name'] ? $shipping['name'] : '—') ?></div>
                        <?php foreach ($shipping['lines'] as $line) : ?>
                            <p><?= esc_html($line) ?></p>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="muted">Igual que la facturación.</p>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <h2>Datos del pedido</h2>
    <table class="meta">
        <tr>
            <th>Pedido</th>
            <td>#<?= esc_html($data['number']) ?></td>
            <th>Estado</th>
            <td><?= esc_html($data['status']) ?></td>
        </tr>
        <tr>
            <th>Fecha</th>
            <td><?= esc_html($data['date']) ?></td>
            <th>Método de pago</th>
            <td><?= esc_html('' !== $data['payment'] ? $data['payment'] : '—') ?></td>
        </tr>
        <?php if ('' !== $data['transaction']) : ?>
            <tr>
                <th>Transacción</th>
                <td colspan="3"><?= esc_html($data['transaction']) ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <th>Envío</th>
            <td><?= esc_html('' !== $data['shipping_method'] ? $data['shipping_method'] : '—') ?></td>
            <th>Cliente</th>
            <td><?= $data['customer_id'] > 0 ? esc_html('#' . $data['customer_id']) : 'Invitado' ?></td>
        </tr>
    </table>

    <h2>Productos</h2>
    <table class="items">
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Producto</th>
                <th>SKU</th>
                <th class="qty">Cant.</th>
                <th class="money">Precio</th>
                <th class="money">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['items'])) : ?>
                <tr>
                    <td colspan="6" class="empty">El pedido no tiene productos.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($data['items'] as $index => $item) : ?>
                    <tr>
                        <td class="num"><?= (int) $index + 1 ?></td>
                        <td>
                            <span class="item-name"><?= esc_html($item['name']) ?></span>
                            <?php if (!empty($item['meta'])) : ?>
                                <div class="item-meta"><?= esc_html(implode(' · ', $item['meta'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= esc_html($item['sku']) ?></td>
                        <td class="qty"><?= (int) $item['qty'] ?></td>
                        <td class="money"><?= esc_html($item['unit']) ?></td>
                        <td class="money"><?= esc_html($item['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($data['totals'])) : ?>
        <table class="totals-wrap">
            <tr>
                <td class="spacer"></td>
                <td class="totals-cell">
                    <table class="totals">
                        <?php foreach ($data['totals'] as $row) : ?>
                            <tr class="<?= 'order_total' === $row['key'] ? 'grand' : '' ?>">
                                <td class="label"><?= esc_html($row['label']) ?></td>
                                <td class="value"><?= esc_html($row['value']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </td>
            </tr>
        </table>
    <?php endif; ?>

    <?php if ($config['show_customer_note'] && '' !== $data['customer_note']) : ?>
        <h2>Nota del cliente</h2>
        <div class="note"><?= nl2br(OEP_EMOJI::wrap($data['customer_note'])) ?></div>
    <?php endif; ?>

    <?php if ($config['show_notes'] && !empty($data['notes'])) : ?>
        <h2>Historial de notas</h2>
        <?php foreach ($data['notes'] as $note) : ?>
            <div class="note">
                <div class="note__meta">
                    <?= esc_html($note['date']) ?> · <?= $note['customer'] ? 'Visible para el cliente' : 'Nota interna' ?>
                </div>
                <div><?= nl2br(OEP_EMOJI::wrap($note['content'])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
