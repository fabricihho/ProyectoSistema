<?php 
ob_start(); 
$pageTitle = 'Editar Contenedor';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2>✏️ Editar Contenedor #<?= htmlspecialchars($contenedor['id']) ?></h2>
        <a href="/contenedores" class="btn btn-secondary">← Cancelar</a>
    </div>
    
    <form action="/contenedores/actualizar/<?= $contenedor['id'] ?>" method="POST" style="padding: 20px;">
        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="tipo_contenedor">Tipo de Contenedor <span style="color:red">*</span></label>
                <select name="tipo_contenedor" id="tipo_contenedor" class="form-control" required>
                    <option value="AMARRO" <?= $contenedor['tipo_contenedor'] == 'AMARRO' ? 'selected' : '' ?>>Amarro</option>
                    <option value="LIBRO" <?= $contenedor['tipo_contenedor'] == 'LIBRO' ? 'selected' : '' ?>>Libro</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tipo_documento">Tipo de Documento (Contenido)</label>
                <input type="text" name="tipo_documento" id="tipo_documento" class="form-control" value="<?= htmlspecialchars($contenedor['tipo_documento'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="numero">Número <span style="color:red">*</span></label>
                <input type="number" name="numero" id="numero" class="form-control" required value="<?= htmlspecialchars($contenedor['numero']) ?>">
            </div>
        </div>
        
        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="gestion">Gestión (Año)</label>
                <input type="number" name="gestion" id="gestion" class="form-control" value="<?= htmlspecialchars($contenedor['gestion'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="color">Color (Opcional)</label>
                <select name="color" id="color" class="form-control">
                    <option value="" <?= empty($contenedor['color']) ? 'selected' : '' ?>>-- Ninguno --</option>
                    <option value="ROJO" <?= ($contenedor['color'] ?? '') == 'ROJO' ? 'selected' : '' ?>>Rojo</option>
                    <option value="AZUL" <?= ($contenedor['color'] ?? '') == 'AZUL' ? 'selected' : '' ?>>Azul</option>
                    <option value="VERDE" <?= ($contenedor['color'] ?? '') == 'VERDE' ? 'selected' : '' ?>>Verde</option>
                    <option value="AMARILLO" <?= ($contenedor['color'] ?? '') == 'AMARILLO' ? 'selected' : '' ?>>Amarillo</option>
                    <option value="NEGRO" <?= ($contenedor['color'] ?? '') == 'NEGRO' ? 'selected' : '' ?>>Negro</option>
                    <option value="BLANCO" <?= ($contenedor['color'] ?? '') == 'BLANCO' ? 'selected' : '' ?>>Blanco</option>
                </select>
            </div>
        </div>
        
        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="ubicacion_id">Ubicación Física</label>
                <select name="ubicacion_id" id="ubicacion_id" class="form-control">
                    <option value="">-- Sin asignar --</option>
                    <?php foreach ($ubicaciones as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $u['id'] == $contenedor['ubicacion_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="bloque_nivel">Bloque / Nivel</label>
                <input type="text" name="bloque_nivel" id="bloque_nivel" class="form-control" value="<?= htmlspecialchars($contenedor['bloque_nivel'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-actions" style="margin-top: 30px; text-align: center;">
            <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
