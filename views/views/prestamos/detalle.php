<?php 
ob_start(); 
$pageTitle = 'Detalle de Préstamo';
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
            <a href="/prestamos" class="btn btn-secondary">← Volver</a>
        </div>
    </div>
    
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

    <!-- Documents List -->
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
                            <th>Estado Actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $doc): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="devueltos[]" value="<?= $doc['id'] ?>" class="check-item"
                                    <?= $doc['estado'] === 'Devuelto' ? 'checked' : '' ?>>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?></strong><br>
                                <small>
                                    Gestión: <?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?> 
                                    | Nro: <?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($doc['tipo_contenedor']): ?>
                                    <?= htmlspecialchars($doc['tipo_contenedor']) ?> #<?= htmlspecialchars($doc['contenedor_numero']) ?>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($doc['estado'] === 'Prestado'): ?>
                                    <span class="badge badge-prestado">📤 Prestado</span>
                                <?php else: ?>
                                    <span class="badge badge-disponible">✅ Devuelto</span>
                                    <small class="d-block text-muted">
                                        <?= date('d/m/Y', strtotime($doc['fecha_devolucion_real'])) ?>
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
                <button type="submit" name="action" value="revertir" class="btn btn-warning" onclick="return confirm('¿Está seguro de revertir la devolución de los documentos NO seleccionados? Volverán a estado PRESTADO.');">
                    ↩ Revertir Devolución (No marcados)
                </button>
            </div>
        </form>
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

.detail-section h3, .documents-section h3 {
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
    for(let i=0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
