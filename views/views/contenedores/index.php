<?php 
ob_start(); 
$pageTitle = 'Gestión de Contenedores';
?>

<div class="card">
    <div class="card-header">
        <h2>📦 Gestión de Contenedores (Amarros/Libros)</h2>
        <div class="header-actions">
            <a href="/catalogacion" class="btn btn-secondary">← Volver a Catalogación</a>
            <a href="/contenedores/crear" class="btn btn-primary">➕ Nuevo Contenedor</a>
        </div>
    </div>
    
    <div class="card-body" style="background: #f8f9fa; padding: 20px; border-bottom: 1px solid #e3e6f0;">
        <form action="/contenedores" method="GET">
            <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: bold; color: #5a5c69; margin-bottom: 5px;">Tipo Documento</label>
                    <input type="text" name="tipo_documento" class="form-control form-control-sm" placeholder="Ej: REGISTRO..." value="<?= htmlspecialchars($filtros['tipo_documento'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: bold; color: #5a5c69; margin-bottom: 5px;">Número</label>
                    <input type="number" name="numero" class="form-control form-control-sm" placeholder="Ej: 1" value="<?= htmlspecialchars($filtros['numero'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: bold; color: #5a5c69; margin-bottom: 5px;">Gestión</label>
                    <input type="number" name="gestion" class="form-control form-control-sm" placeholder="Año" value="<?= htmlspecialchars($filtros['gestion'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: bold; color: #5a5c69; margin-bottom: 5px;">Ubicación</label>
                    <select name="ubicacion_id" class="form-control form-control-sm">
                        <option value="">-- Todas --</option>
                        <?php foreach ($ubicaciones as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filtros['ubicacion_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: bold; color: #5a5c69; margin-bottom: 5px;">Tipo Cont.</label>
                    <select name="tipo_contenedor" class="form-control form-control-sm">
                        <option value="">-- Todos --</option>
                        <option value="AMARRO" <?= ($filtros['tipo_contenedor'] ?? '') == 'AMARRO' ? 'selected' : '' ?>>📦 Amarro</option>
                        <option value="LIBRO" <?= ($filtros['tipo_contenedor'] ?? '') == 'LIBRO' ? 'selected' : '' ?>>📚 Libro</option>
                    </select>
                </div>
                <div style="display: flex; gap: 5px;">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex: 1;">🔍 Buscar</button>
                    <a href="/contenedores" class="btn btn-secondary btn-sm" style="flex: 1;">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tipo Documento</th>
                    <th>Tipo</th>
                    <th>Número</th>
                    <th>Gestión</th>
                    <th>Ubicación</th>
                    <th>Bloque/Nivel</th>
                    <th>Color</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contenedores)): ?>
                    <tr><td colspan="8" class="text-center">No hay contenedores registrados</td></tr>
                <?php else: ?>
                    <?php foreach ($contenedores as $c): ?>
                        <tr>
                             <td><?= htmlspecialchars($c['tipo_documento'] ?? '-') ?></td>
                             <td>
                                <?php if ($c['tipo_contenedor'] == 'LIBRO'): ?>
                                    <span class="badge" style="background: #17a2b8;">📚 Libro</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #6f42c1;">📦 Amarro</span>
                                <?php endif; ?>
                             </td>
                             <td><strong>#<?= htmlspecialchars($c['numero'] ?? '') ?></strong></td>
                             <td><?= htmlspecialchars($c['gestion'] ?? 'N/A') ?></td>
                             <td><?= htmlspecialchars($c['ubicacion_nombre'] ?? 'Sin asignar') ?></td>
                             <td><?= htmlspecialchars($c['bloque_nivel'] ?? '-') ?></td>
                             <td>
                                <?php if (!empty($c['color'])): ?>
                                    <span class="badge" style="background-color: #eee; color: <?= htmlspecialchars($c['color']) ?>; border: 1px solid #ccc;">
                                        <?= htmlspecialchars($c['color']) ?>
                                    </span>
                                <?php endif; ?>
                             </td>
                             <td>
                                <a href="/contenedores/editar/<?= $c['id'] ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
                                <a href="/contenedores/eliminar/<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este contenedor? Asegúrate de que no tenga documentos asociados.')">🗑️</a>
                             </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.badge { padding: 5px 10px; border-radius: 12px; color: white; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
.btn-sm { padding: 2px 8px; font-size: 12px; }
</style>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
