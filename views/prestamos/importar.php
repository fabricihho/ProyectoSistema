<?php
$pageTitle = 'Importar Préstamo desde Excel';
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h2>📂 Importar Préstamo desde Excel</h2>
    </div>
    
    <div class="alert alert-info" style="background-color: #e3f2fd; color: #0c5460; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <strong>Instrucciones:</strong>
        <p>Suba un archivo Excel (.xlsx) con las siguientes columnas (las 3 primeras son obligatorias):</p>
        <ul style="margin-left: 20px;">
            <li>1. Tipo Documento</li>
            <li>2. GESTION</li>
            <li>3. NRO. DE COMPROBANTE DIARIO</li>
        </ul>
    </div>

    <form action="/prestamos/importar/procesar" method="post" enctype="multipart/form-data" class="mt-20">
        
        <div class="form-group mb-20">
            <label class="form-label" for="unidad_area_id">Unidad / Área Solicitante:</label>
            <select name="unidad_area_id" id="unidad_area_id" class="form-control" required>
                <option value="">-- Seleccione Unidad --</option>
                <?php foreach ($ubicaciones as $ub): ?>
                    <option value="<?= $ub['id'] ?>"><?= htmlspecialchars($ub['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group mb-20">
            <label class="form-label" for="nombre_prestatario">Nombre del Prestatario (Opcional):</label>
            <input type="text" name="nombre_prestatario" id="nombre_prestatario" class="form-control" placeholder="Quien recibe los documentos">
        </div>

        <div class="form-group mb-20">
            <label class="form-label" for="fecha_devolucion">Fecha Devolución Esperada:</label>
            <input type="date" name="fecha_devolucion" id="fecha_devolucion" class="form-control" required value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
        </div>

        <div class="form-group mb-20">
            <label class="form-label">Archivo Excel:</label>
            <div style="border: 2px dashed #cbd5e0; padding: 30px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s;" 
                 onclick="document.getElementById('excel_file').click()"
                 ondragover="event.preventDefault(); this.style.borderColor = '#4299e1'; this.style.backgroundColor = '#ebf8ff';"
                 ondragleave="this.style.borderColor = '#cbd5e0'; this.style.backgroundColor = 'transparent';"
                 ondrop="handleDrop(event)">
                
                <span style="font-size: 3em; display: block; margin-bottom: 10px;">📊</span>
                <p>Arrastre su archivo aquí o haga clic para seleccionar</p>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls" style="display: none;" onchange="updateFileName(this)" required>
                <p id="file-name" style="margin-top: 10px; font-weight: bold; color: #2b6cb0;"></p>
            </div>
        </div>

        <div class="form-actions">
            <a href="/prestamos" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">📥 Procesar Importación</button>
        </div>
    </form>
</div>

<script>
function updateFileName(input) {
    const fileName = input.files[0] ? input.files[0].name : '';
    document.getElementById('file-name').textContent = fileName;
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        document.getElementById('excel_file').files = files;
        updateFileName(document.getElementById('excel_file'));
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
