<?php
ob_start();
$pageTitle = 'Gestión de Ubicaciones';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>Ubicaciones</h2>
        <a href="/configuracion/ubicaciones/crear" class="btn btn-primary">
            <span class="icon">➕</span> Nueva Ubicación
        </a>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ubicaciones)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay ubicaciones registradas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ubicaciones as $ubicacion): ?>
                        <tr>
                            <td><?= $ubicacion['id'] ?></td>
                            <td><?= htmlspecialchars($ubicacion['nombre']) ?></td>
                            <td><?= htmlspecialchars($ubicacion['descripcion'] ?? '') ?></td>
                            <td>
                                <?php if ($ubicacion['activo']): ?>
                                    <span class="status-badge status-disponible">Activo</span>
                                <?php else: ?>
                                    <span class="status-badge status-anulado">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/configuracion/ubicaciones/editar/<?= $ubicacion['id'] ?>" class="btn btn-sm btn-info" title="Editar">✏️</a>
                                <a href="/configuracion/ubicaciones/eliminar/<?= $ubicacion['id'] ?>" class="btn btn-sm btn-danger" title="Eliminar"
                                   onclick="return confirm('¿Eliminar la ubicación «<?= htmlspecialchars($ubicacion['nombre']) ?>»?');">🗑️</a>
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
