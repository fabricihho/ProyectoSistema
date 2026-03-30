<?php 
ob_start(); 
$pageTitle = 'Nuevo Contenedor';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2>➕ Nuevo Contenedor</h2>
        <a href="/contenedores" class="btn btn-secondary">← Cancelar</a>
    </div>
    
    <form action="/contenedores/guardar" method="POST" style="padding: 20px;">
        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="tipo_contenedor">Tipo de Contenedor <span style="color:red">*</span></label>
                <select name="tipo_contenedor" id="tipo_contenedor" class="form-control" required>
                    <option value="AMARRO">Amarro</option>
                    <option value="LIBRO">Libro</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tipo_documento">Tipo de Documento (Contenido) <span style="color:red">*</span></label>
                <select name="tipo_documento" id="tipo_documento" class="form-control" required>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($tiposDocumento as $td): ?>
                        <option value="<?= $td['id'] ?>"><?= htmlspecialchars($td['nombre']) ?> (<?= htmlspecialchars($td['codigo']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="numero">Número <span style="color:red">*</span></label>
                <input type="number" name="numero" id="numero" class="form-control" required placeholder="Ej: 1, 15, 100...">
            </div>
            
            <div class="form-group">
                <label for="codigo_abc">Código ABC (Opcional)</label>
                <input type="text" name="codigo_abc" id="codigo_abc" class="form-control" placeholder="Ej: A-1">
            </div>
        </div>
        
        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="gestion">Gestión (Año)</label>
                <input type="number" name="gestion" id="gestion" class="form-control" value="<?= date('Y') ?>">
            </div>
            
            <div class="form-group">
                <label for="color">Color (Opcional)</label>
                <select name="color" id="color" class="form-control">
                    <option value="">-- Ninguno --</option>
                    <option value="ROJO">Rojo</option>
                    <option value="AZUL">Azul</option>
                    <option value="VERDE">Verde</option>
                    <option value="AMARILLO">Amarillo</option>
                    <option value="NEGRO">Negro</option>
                    <option value="BLANCO">Blanco</option>
                </select>
            </div>
        </div>
        
        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="ubicacion_id">Ubicación Física</label>
                <select name="ubicacion_id" id="ubicacion_id" class="form-control">
                    <option value="">-- Sin asignar --</option>
                    <?php foreach ($ubicaciones as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="bloque_nivel">Bloque / Nivel</label>
                <input type="text" name="bloque_nivel" id="bloque_nivel" class="form-control" placeholder="Ej: Estante A, Nivel 3">
            </div>
        </div>
        
        <div class="form-actions" style="margin-top: 30px; text-align: center;">
            <button type="submit" class="btn btn-primary">💾 Guardar Contenedor</button>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
