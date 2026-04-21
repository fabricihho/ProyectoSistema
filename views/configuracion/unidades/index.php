<?php
ob_start();
$pageTitle = 'Gestión de Unidades / Áreas';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>Unidades / Áreas</h2>
        <a href="/configuracion/unidades/crear" class="btn btn-primary">
            <span class="icon">➕</span> Nueva Unidad
        </a>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($unidades)): ?>
                        <tr>
                            <td colspan="4" class="text-center">No hay unidades registradas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($unidades as $unidad): ?>
                        <tr>
                            <td><?= $unidad['id'] ?></td>
                            <td><?= htmlspecialchars($unidad['nombre']) ?></td>
                            <td>
                                <?php if ($unidad['activo']): ?>
                                    <span class="status-badge status-disponible">Activo</span>
                                <?php else: ?>
                                    <span class="status-badge status-anulado">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/configuracion/unidades/editar/<?= $unidad['id'] ?>" class="btn btn-sm btn-info" title="Editar">✏️</a>
                                <a href="/configuracion/unidades/eliminar/<?= $unidad['id'] ?>" class="btn btn-sm btn-danger" title="Eliminar"
                                   onclick="return confirm('¿Eliminar la unidad «<?= htmlspecialchars($unidad['nombre']) ?>»?');">🗑️</a>
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
