<?php
ob_start();
$pageTitle = ($isNew ? 'Nueva' : 'Editar') . ' Ubicación';
$action    = $isNew ? '/configuracion/ubicaciones/guardar' : '/configuracion/ubicaciones/actualizar/' . $ubicacion['id'];
?>

<div class="card" style="max-width:600px;">
    <div class="card-header flex-between">
        <h2><?= $pageTitle ?></h2>
        <a href="/configuracion/ubicaciones" class="btn btn-secondary">← Volver</a>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= $action ?>">
            <?php if (!$isNew): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>

            <div class="form-group">
                <label for="nombre">Nombre <span class="text-danger">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control"
                       value="<?= htmlspecialchars($ubicacion['nombre'] ?? '') ?>" required maxlength="150">
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3" maxlength="300"><?= htmlspecialchars($ubicacion['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="activo" value="1"
                        <?= (!isset($ubicacion) || $ubicacion === null || $ubicacion['activo']) ? 'checked' : '' ?>>
                    Activo
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $isNew ? 'Guardar' : 'Actualizar' ?>
                </button>
                <a href="/configuracion/ubicaciones" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
?>
