<?php
ob_start();
$pageTitle = ($isNew ? 'Nueva' : 'Editar') . ' Unidad / Área';
$action    = $isNew ? '/configuracion/unidades/guardar' : '/configuracion/unidades/actualizar/' . $unidad['id'];
?>

<div class="card" style="max-width:500px;">
    <div class="card-header flex-between">
        <h2><?= $pageTitle ?></h2>
        <a href="/configuracion/unidades" class="btn btn-secondary">← Volver</a>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= $action ?>">

            <div class="form-group">
                <label for="nombre">Nombre <span class="text-danger">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control"
                       value="<?= htmlspecialchars($unidad['nombre'] ?? '') ?>" required maxlength="150">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="activo" value="1"
                        <?= (!isset($unidad) || $unidad === null || $unidad['activo']) ? 'checked' : '' ?>>
                    Activo
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $isNew ? 'Guardar' : 'Actualizar' ?>
                </button>
                <a href="/configuracion/unidades" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
?>
