<?php
ob_start();
$pageTitle = 'Detalle del Documento';

// Mapeo de tipos de documento para mostrar nombres legibles
$tiposDocumento = [
    'REGISTRO_DIARIO' => '📋 Registro Diario',
    'REGISTRO_INGRESO' => '💵 Registro Ingreso',
    'REGISTRO_CEPS' => '🏦 Registro CEPS',
    'PREVENTIVOS' => '📊 Preventivos',
    'ASIENTOS_MANUALES' => '✍️ Asientos Manuales',
    'DIARIOS_APERTURA' => '📂 Diarios de Apertura',
    'REGISTRO_TRASPASO' => '🔄 Registro Traspaso',
    'HOJA_RUTA_DIARIOS' => '🗺️ Hoja de Ruta - Diarios'
];

$tipoDocumentoTexto = $tiposDocumento[$documento['tipo_documento'] ?? 'REGISTRO_DIARIO'] ?? 'No especificado';

// Rebuild query string for Back button
$filterParams = isset($filters) ? array_filter($filters, function ($v) {
    return $v !== '';
}) : [];
$backUrl = '/catalogacion' . (!empty($filterParams) ? '?' . http_build_query($filterParams) : '');
?>

<div class="card">
    <div class="card-header">
        <h2>Información del Documento</h2>
        <a href="<?= $backUrl ?>" class="btn btn-secondary">← Volver al Listado</a>
    </div>

    <div class="detail-grid">
        <div class="detail-section">
            <h3>Datos del Comprobante</h3>
            <dl class="detail-list">
                <dt>Tipo de Documento:</dt>
                <dd><strong><?= $tipoDocumentoTexto ?></strong></dd>

                <dt>Gestión:</dt>
                <dd><?= htmlspecialchars($documento['gestion'] ?? 'N/A') ?></dd>

                <dt>Número de Comprobante:</dt>
                <dd><?= htmlspecialchars($documento['nro_comprobante'] ?? 'N/A') ?></dd>

                <dt>Código ABC:</dt>
                <dd><?= htmlspecialchars($documento['codigo_abc'] ?? 'N/A') ?></dd>

                <dt>Estado:</dt>
                <dd>
                    <?php
                    $estado = $documento['estado_documento'] ?? 'DISPONIBLE';
                    $badgeClass = '';
                    $icon = '';
                    switch ($estado) {
                        case 'DISPONIBLE':
                            $badgeClass = 'badge-disponible';
                            $icon = '🟢';
                            break;
                        case 'FALTA':
                            $badgeClass = 'badge-falta';
                            $icon = '🔴';
                            break;
                        case 'PRESTADO':
                            $badgeClass = 'badge-prestado';
                            $icon = '🔵';
                            break;
                        case 'ANULADO':
                            $badgeClass = 'badge-anulado';
                            $icon = '🟣';
                            break;
                    }
                    ?>
                    <span class="badge <?= $badgeClass ?>"><?= $icon ?> <?= htmlspecialchars($estado) ?></span>
                    <?php if ($estado === 'PRESTADO' && !empty($documento['prestamo_id'])): ?>
                        <a href="/prestamos/ver/<?= $documento['prestamo_id'] ?>?from_doc=<?= $documento['id'] ?>"
                            class="btn btn-sm" style="background: #17a2b8; color: white; margin-left: 10px;"
                            title="Ver préstamo activo">📋 Ver
                            Préstamo</a>
                    <?php endif; ?>
                </dd>

                <dt>Observaciones:</dt>
                <dd><?= !empty($documento['observaciones']) ? nl2br(htmlspecialchars($documento['observaciones'])) : '<em>Ninguna</em>' ?>
                </dd>
            </dl>
        </div>

        <div class="detail-section">
            <h3>Ubicación Física</h3>
            <dl class="detail-list">
                <?php if (!empty($documento['contenedor_fisico_id'])): ?>
                    <dt>Contenedor Físico:</dt>
                    <dd>
                        <?php if (!empty($documento['tipo_contenedor']) && !empty($documento['contenedor_numero'])): ?>
                            <?= htmlspecialchars($documento['tipo_contenedor']) ?>
                            #<?= htmlspecialchars($documento['contenedor_numero']) ?><?= !empty($documento['contenedor_codigo_abc']) ? '.' . htmlspecialchars($documento['contenedor_codigo_abc']) : '' ?>
                        <?php else: ?>
                            ID: <?= htmlspecialchars($documento['contenedor_fisico_id']) ?>
                        <?php endif; ?>
                    </dd>
                <?php endif; ?>

                <?php if (!empty($documento['color'])): ?>
                    <dt>Color:</dt>
                    <dd><?= htmlspecialchars($documento['color']) ?></dd>
                <?php endif; ?>

                <?php if (isset($documento['ubicacion_nombre'])): ?>
                    <dt>Ubicación:</dt>
                    <dd><?= htmlspecialchars($documento['ubicacion_nombre']) ?></dd>

                    <?php if (!empty($documento['bloque_nivel'])): ?>
                        <dt>Bloque/Nivel:</dt>
                        <dd><?= htmlspecialchars($documento['bloque_nivel']) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($documento['ubicacion_descripcion'])): ?>
                        <dt>Descripción Ubicación:</dt>
                        <dd><?= htmlspecialchars($documento['ubicacion_descripcion']) ?></dd>
                    <?php endif; ?>
                <?php else: ?>
                    <dt>Ubicación:</dt>
                    <dd>No asignada</dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <div class="detail-actions">
        <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir</button>
        <a href="<?= $backUrl ?>" class="btn btn-secondary">← Volver</a>
    </div>
</div>

<style>
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
    }

    .detail-section {
        background: #f5f7fa;
        padding: 20px;
        border-radius: 8px;
    }

    .detail-section h3 {
        color: #1B3C84;
        margin-bottom: 15px;
        font-size: 18px;
        border-bottom: 2px solid #FFD100;
        padding-bottom: 8px;
    }

    .detail-list {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 12px;
        align-items: start;
    }

    .detail-list dt {
        font-weight: 600;
        color: #333;
    }

    .detail-list dd {
        margin: 0;
        color: #666;
    }

    .detail-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        padding: 20px;
        border-top: 1px solid #ddd;
        margin-top: 20px;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        color: white;
        display: inline-block;
    }

    .badge-disponible {
        background: #28a745;
    }

    .badge-falta {
        background: #dc3545;
    }

    .badge-prestado {
        background: #17a2b8;
    }

    .badge-anulado {
        background: #6f42c1;
    }

    @media print {

        .btn,
        .detail-actions,
        .card-header a {
            display: none !important;
        }
    }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>