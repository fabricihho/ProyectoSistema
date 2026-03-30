<?php
ob_start();
$pageTitle = 'Detalle de Préstamo';
use TAMEP\Core\Session;
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>📋 Detalle del Préstamo #<?= $prestamo['id'] ?></h2>
        <div class="header-actions">
            <!-- Export Buttons -->
            <a href="/prestamos/exportar-pdf/<?= $prestamo['id'] ?>" target="_blank" class="btn btn-warning">
                📄 PDF
            </a>
            <a href="/prestamos/exportar-excel/<?= $prestamo['id'] ?>" target="_blank" class="btn btn-success">
                📊 Excel
            </a>

            <?php if ($prestamo['estado'] === 'En Proceso'): ?>
                <a href="/prestamos/procesar/<?= $prestamo['id'] ?>" class="btn btn-primary">
                    ⚙️ Procesar
                </a>
            <?php else: ?>
                <!-- Si ya está prestado (y no devuelto completamente), permitir verificar de nuevo (revertir a En Proceso) -->
                <?php if ($prestamo['estado'] !== 'Devuelto'): ?>
                    <a href="/prestamos/revertirProceso/<?= $prestamo['id'] ?>" class="btn btn-warning"
                        onclick="return confirm('¿Volver a estado En Proceso? Esto permitirá corregir la lista de documentos faltantes.');">
                        ↩ Verificar
                    </a>
                <?php endif; ?>
                <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                    ⚙️ Procesar
                </button>
            <?php endif; ?>

            <a href="/prestamos" class="btn btn-secondary">← Volver</a>
        </div>
    </div>

    <?php
    // Flash messages
    if (Session::has('success')) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 20px;">';
        echo htmlspecialchars(Session::flash('success'));
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        echo '<span aria-hidden="true">&times;</span></button></div>';
    }
    if (Session::has('error')) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-bottom: 20px;">';
        echo htmlspecialchars(Session::flash('error'));
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        echo '<span aria-hidden="true">&times;</span></button></div>';
    }

    // LIBRO Context Alert
    if (!empty($contextInfo) && $contextInfo['is_libro_context']): ?>
        <div class="alert alert-info" role="alert" style="margin: 20px; border-left: 4px solid #17a2b8;">
            <h5 style="margin-top: 0;">ℹ️ Información del Préstamo</h5>
            <p style="margin-bottom: 0;">
                <strong>Este documento NO fue solicitado directamente</strong>, pero está prestado porque forma parte
                del <strong><?= htmlspecialchars($contextInfo['tipo_contenedor']) ?>
                    #<?= $contextInfo['libro_num'] ?><?= !empty($contextInfo['contenedor_codigo_abc']) ? '.' . htmlspecialchars($contextInfo['contenedor_codigo_abc']) : '' ?></strong> que contiene el documento solicitado:
            </p>
            <p style="margin: 10px 0 0 0; padding: 10px; background: #e7f3ff; border-radius: 4px;">
                📄 <strong>Documento solicitado:</strong> <?= htmlspecialchars($contextInfo['doc_principal']) ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Group Information -->
    <div class="detail-grid">
        <div class="detail-section">
            <h3>Datos del Préstamo</h3>
            <dl class="detail-list">
                <dt>Unidad/Área Solicitante:</dt>
                <dd>
                    <strong><?= htmlspecialchars($prestamo['unidad_nombre'] ?? 'N/A') ?></strong>
                </dd>

                <dt>Prestatario:</dt>
                <dd>
                    <?= htmlspecialchars($prestamo['nombre_prestatario'] ?? 'No registrado') ?>
                </dd>

                <dt>Fecha de Préstamo:</dt>
                <dd><?= date('d/m/Y', strtotime($prestamo['fecha_prestamo'])) ?></dd>

                <dt>Fecha Devolución Est.:</dt>
                <dd>
                    <?= date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])) ?>
                    <?php if ($prestamo['estado'] === 'Prestado' && strtotime($prestamo['fecha_devolucion_esperada']) < time()): ?>
                        <br><span class="badge badge-falta">⚠️ VENCIDO</span>
                    <?php endif; ?>
                </dd>

                <dt>Total Documentos:</dt>
                <dd class="text-large"><?= count($detalles) ?></dd>

                <dt>Estado General:</dt>
                <dd>
                    <?php if ($prestamo['estado'] === 'Prestado'): ?>
                        <span class="badge badge-prestado">📤 Prestado</span>
                    <?php else: ?>
                        <span class="badge badge-disponible">✅ Devuelto</span>
                    <?php endif; ?>
                </dd>
            </dl>
        </div>

        <?php if (!empty($prestamo['observaciones'])): ?>
            <div class="detail-section">
                <h3>Observaciones</h3>
                <p><?= nl2br(htmlspecialchars($prestamo['observaciones'])) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Documents List - Split Sections -->

    <!-- Section 1: Prestados -->
    <?php if (!empty($prestados)): ?>
        <div class="documents-section" style="margin-top: 20px; padding: 20px;">
            <h3>📚 Documentos Prestados</h3>

            <form action="/prestamos/actualizarEstados" method="POST" id="form-devoluciones">
                <input type="hidden" name="encabezado_id" value="<?= $prestamo['id'] ?>">

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="check-all" onclick="toggleAll(this)">
                                </th>
                                <th>Documento</th>
                                <th>Contenedor</th>
                                <th>Ubicación</th>
                                <th>Estado Actual</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestados as $doc): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="devueltos[]" value="<?= $doc['id'] ?>" class="check-item"
                                            <?= $doc['estado'] === 'Devuelto' ? 'checked' : '' ?>>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?></strong>
                                        <?php if (isset($doc['es_principal']) && $doc['es_principal'] == 0): ?>
                                            <span class="badge"
                                                style="background-color: #8B4513; color: white; font-size: 0.7em; margin-left: 5px;">📚
                                                LIBRO</span>
                                        <?php endif; ?>
                                        <br>
                                        <small>
                                            Gestión: <?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?>
                                            | Nro: <?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($doc['tipo_contenedor']): ?>
                                            <?= htmlspecialchars($doc['tipo_contenedor']) ?>
                                            #<?= htmlspecialchars($doc['contenedor_numero']) ?><?= !empty($doc['contenedor_codigo_abc']) ? '.' . htmlspecialchars($doc['contenedor_codigo_abc']) : '' ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($doc['ubicacion_fisica'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <?php if ($doc['estado'] === 'Prestado'): ?>
                                            <span class="badge badge-prestado">📤 Prestado</span>
                                        <?php else: ?>
                                            <span class="badge badge-disponible">✅ Devuelto</span>
                                            <small class="d-block text-muted">
                                                <?= !empty($doc['fecha_devolucion_real']) ? date('d/m/Y', strtotime($doc['fecha_devolucion_real'])) : '' ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($doc['estado'] === 'Devuelto'): ?>
                                            <span class="text-success">✓</span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="detail-actions">
                    <button type="submit" name="action" value="devolver" class="btn btn-success">
                        ✓ Registrar Devolución
                    </button>
                    <button type="submit" name="action" value="revertir" class="btn btn-warning"
                        onclick="return confirm('¿Está seguro de revertir la devolución de los documentos NO seleccionados? Volverán a estado PRESTADO.');">
                        ↩ Revertir Devolución (No marcados)
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Section 2: No Prestados -->
    <?php if (!empty($noPrestados)): ?>
        <div class="documents-section" style="margin-top: 20px; padding: 20px; border-top: 4px solid #6c757d;">
            <h3>🚫 Documentos No Prestados / Faltantes</h3>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Documento</th>
                            <th>Contenedor</th>
                            <th>Ubicación</th>
                            <th>Estado en Préstamo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noPrestados as $doc): ?>
                            <tr>
                                <td class="text-muted">⏹</td>
                                <td>
                                    <strong><?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?></strong>
                                    <?php if (isset($doc['es_principal']) && $doc['es_principal'] == 0): ?>
                                        <span class="badge"
                                            style="background-color: #8B4513; color: white; font-size: 0.7em; margin-left: 5px;">📚
                                            LIBRO</span>
                                    <?php endif; ?>
                                    <br>
                                    <small>
                                        Gestión: <?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?>
                                        | Nro: <?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($doc['tipo_contenedor']): ?>
                                        <?= htmlspecialchars($doc['tipo_contenedor']) ?>
                                        #<?= htmlspecialchars($doc['contenedor_numero']) ?><?= !empty($doc['contenedor_codigo_abc']) ? '.' . htmlspecialchars($doc['contenedor_codigo_abc']) : '' ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($doc['ubicacion_fisica'] ?? 'N/A') ?></small>
                                </td>
                                <td>
                                    <?php if ($doc['estado'] === 'Falta'): ?>
                                        <span class="badge badge-falta" style="background-color: #dc3545; color: white;">❌
                                            FALTA</span>
                                    <?php elseif ($doc['estado'] === 'En Proceso'): ?>
                                        <span class="badge badge-warning" style="background-color: #ffc107; color: black;">⏳ En
                                            Proceso</span>
                                    <?php elseif ($doc['estado'] === 'No Prestado'): ?>
                                        <span class="badge badge-secondary" style="background-color: #6c757d; color: white;">🚫 No
                                            Prestado</span>
                                    <?php else: ?>
                                        <span class="badge"><?= htmlspecialchars($doc['estado']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted mt-2"><small>Estos documentos no fueron entregados. Si hay algun cambio, seleccione
                    "Verificar" arriba para volver a procesar el préstamo.</small></p>
        </div>
    <?php endif; ?>
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

    .detail-section h3,
    .documents-section h3 {
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

    .header-actions {
        display: flex;
        gap: 10px;
    }

    .text-large {
        font-size: 1.2em;
        font-weight: bold;
        color: #3182ce;
    }
</style>

<script>
    function toggleAll(source) {
        const checkboxes = document.querySelectorAll('.check-item');
        for (let i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = source.checked;
        }
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>