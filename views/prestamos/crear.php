<?php 
ob_start(); 
$pageTitle = 'Nuevo Préstamo';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>➕ Registrar Nuevo Préstamo</h2>
        <a href="/prestamos" class="btn btn-secondary">← Volver al Listado</a>
    </div>
    
    <form method="POST" action="/prestamos/guardar" class="prestamo-form">
        <div class="form-row">
            <div class="form-group">
                <label for="documento_id">Documento a Prestar <span class="required">*</span></label>
                <select id="documento_id" name="documento_id" class="form-control" required onchange="updateInfo()">
                    <option value="">Seleccione un documento...</option>
                    <?php foreach ($documentos as $doc): ?>
                        <option value="<?= $doc['id'] ?>" 
                                data-tipo="<?= htmlspecialchars($doc['tipo_documento'] ?? '') ?>"
                                data-gestion="<?= htmlspecialchars($doc['gestion'] ?? '') ?>"
                                data-comprobante="<?= htmlspecialchars($doc['nro_comprobante'] ?? '') ?>"
                                data-contenedor="<?= htmlspecialchars($doc['tipo_contenedor'] ?? '') ?> #<?= htmlspecialchars($doc['contenedor_numero'] ?? '') ?>">
                            <?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?> - 
                            Gestión <?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?> - 
                            #<?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="doc-info" style="margin-top: 10px; padding: 10px; background: #f0f9ff; border-radius: 4px; display: none;"></div>
            </div>
            
            <div class="form-group">
                <label for="unidad_area_id">Unidad/Área Solicitante <span class="required">*</span></label>
                <select id="unidad_area_id" name="unidad_area_id" class="form-control" required>
                    <option value="">Seleccione una unidad o área...</option>
                    <?php foreach ($unidades as $ubi): ?>
                        <option value="<?= $ubi['id'] ?>">
                            <?= htmlspecialchars($ubi['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="nombre_prestatario">Nombre del Prestatario (Opcional)</label>
                <input type="text" id="nombre_prestatario" name="nombre_prestatario" class="form-control" 
                       placeholder="Nombre de la persona que retira el documento">
            </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="fecha_devolucion_esperada">Fecha Estimada de Devolución <span class="required">*</span></label>
                <input type="date" id="fecha_devolucion_esperada" name="fecha_devolucion_esperada" 
                       class="form-control" required min="<?= date('Y-m-d') ?>">
                <small>Fecha en que se espera la devolución del documento</small>
            </div>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="3" 
                      placeholder="Motivo del préstamo, condiciones especiales, etc."></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Registrar Préstamo</button>
            <a href="/prestamos" class="btn btn-secondary">❌ Cancelar</a>
        </div>
    </form>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.required {
    color: #E53E3E;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #E2E8F0;
}

.prestamo-form {
    padding: 20px;
}

small {
    color: #718096;
    font-size: 12px;
    margin-top: 4px;
}
</style>

<script>
function updateInfo() {
    const select = document.getElementById('documento_id');
    const info = document.getElementById('doc-info');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        info.style.display = 'block';
        info.innerHTML = `
            <strong>Información del Documento:</strong><br>
            📋 Tipo: ${option.dataset.tipo}<br>
            📅 Gestión: ${option.dataset.gestion}<br>
            🔢 Nro Comprobante: ${option.dataset.comprobante}<br>
            📦 Contenedor: ${option.dataset.contenedor}
        `;
    } else {
        info.style.display = 'none';
    }
}

// Set default date (7 days from now)
document.addEventListener('DOMContentLoaded', function() {
    const fecha = document.getElementById('fecha_devolucion_esperada');
    const hoy = new Date();
    hoy.setDate(hoy.getDate() + 7);
    fecha.value = hoy.toISOString().split('T')[0];
});
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
