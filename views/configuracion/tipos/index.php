<?php 
ob_start();
$pageTitle = 'Gestión de Tipos de Documento'; 
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>Tipos de Documento</h2>
        <a href="/configuracion/tipos/crear" class="btn btn-primary">
            <span class="icon">➕</span> Nuevo Tipo
        </a>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tipos)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay tipos de documento registrados</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tipos as $tipo): ?>
                        <tr>
                            <td><?= $tipo['orden'] ?></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($tipo['codigo']) ?></span></td>
                            <td><?= htmlspecialchars($tipo['nombre']) ?></td>
                            <td><?= htmlspecialchars($tipo['descripcion']) ?></td>
                            <td>
                                <?php if ($tipo['activo']): ?>
                                    <span class="status-badge status-disponible">Activo</span>
                                <?php else: ?>
                                    <span class="status-badge status-anulado">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/configuracion/tipos/editar/<?= $tipo['id'] ?>" class="btn btn-sm btn-info" title="Editar">
                                    ✏️
                                </a>
                                <a href="/configuracion/tipos/eliminar/<?= $tipo['id'] ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de que desea eliminar este tipo de documento?');">
                                    🗑️
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
?>
