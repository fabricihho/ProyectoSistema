<?php 
ob_start(); 
$pageTitle = 'Crear Nuevo Documento';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>📝 Crear Nuevo Documento</h2>
        <a href="/catalogacion" class="btn btn-secondary">← Volver al Listado</a>
    </div>
    
    <form method="POST" action="/catalogacion/guardar" class="document-form">
        <div class="form-row">
            <div class="form-group">
                <label for="tipo_documento">Tipo de Documento <span class="required">*</span></label>
                <select id="tipo_documento" name="tipo_documento" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <option value="REGISTRO_DIARIO">📋 Registro Diario</option>
                    <option value="REGISTRO_INGRESO">💵 Registro Ingreso</option>
                    <option value="REGISTRO_CEPS">🏦 Registro CEPS</option>
                    <option value="PREVENTIVOS">📊 Preventivos</option>
                    <option value="ASIENTOS_MANUALES">✍️ Asientos Manuales</option>
                    <option value="DIARIOS_APERTURA">📂 Diarios de Apertura</option>
                    <option value="REGISTRO_TRASPASO">🔄 Registro Traspaso</option>
                    <option value="HOJA_RUTA_DIARIOS">🗺️ Hoja de Ruta - Diarios</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="gestion">Gestión <span class="required">*</span></label>
                <input type="number" id="gestion" name="gestion" class="form-control" 
                       value="<?= date('Y') ?>" min="2000" max="<?= date('Y') + 1 ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="nro_comprobante">Número de Comprobante <span class="required">*</span></label>
                <input type="text" id="nro_comprobante" name="nro_comprobante" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="codigo_abc">Código ABC</label>
                <input type="text" id="codigo_abc" name="codigo_abc" class="form-control">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="contenedor_fisico_id">Contenedor Físico (Libro/Amarro)</label>
                <select id="contenedor_fisico_id" name="contenedor_fisico_id" class="form-control">
                    <option value="">Sin asignar</option>
                    <?php foreach ($contenedores as $cont): ?>
                        <option value="<?= $cont['id'] ?>">
                            <?= htmlspecialchars($cont['tipo_contenedor']) ?> #<?= htmlspecialchars($cont['numero']) ?>
                            <?php if (!empty($cont['ubicacion_nombre'])): ?>
                                - <?= htmlspecialchars($cont['ubicacion_nombre']) ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="estado_documento">Estado del Documento <span class="required">*</span></label>
                <select id="estado_documento" name="estado_documento" class="form-control" required>
                    <option value="DISPONIBLE" selected>🟢 Disponible</option>
                    <option value="FALTA">🔴 Falta</option>
                    <option value="PRESTADO">🔵 Prestado</option>
                    <option value="NO UTILIZADO">🟡 No Utilizado</option>
                    <option value="ANULADO">🟣 Anulado</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="4"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar Documento</button>
            <a href="/catalogacion" class="btn btn-secondary">❌ Cancelar</a>
        </div>
    </form>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.required {
    color: #E53E3E;
    font-weight: bold;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #E2E8F0;
}

.document-form {
    padding: 20px;
}
</style>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
