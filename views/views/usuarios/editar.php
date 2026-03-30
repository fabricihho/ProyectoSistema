<?php 
ob_start(); 
$pageTitle = 'Editar Usuario';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>✏️ Editar Usuario</h2>
        <a href="/admin/usuarios" class="btn btn-secondary">← Volver al Listado</a>
    </div>
    
    <form method="POST" action="/admin/usuarios/actualizar/<?= $usuario['id'] ?>" class="user-form">
        <div class="form-row">
            <div class="form-group">
                <label for="username">Nombre de Usuario <span class="required">*</span></label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?= htmlspecialchars($usuario['username']) ?>" required 
                       pattern="[a-zA-Z0-9_]{3,20}" title="Solo letras, números y guión bajo (3-20 caracteres)">
            </div>
            
            <div class="form-group">
                <label for="nombre_completo">Nombre Completo <span class="required">*</span></label>
                <input type="text" id="nombre_completo" name="nombre_completo" class="form-control" 
                       value="<?= htmlspecialchars($usuario['nombre_completo']) ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="password">Nueva Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" 
                       minlength="6" autocomplete="new-password">
                <small>Dejar vacío para mantener la contraseña actual</small>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirmar Nueva Contraseña</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="rol">Rol <span class="required">*</span></label>
                <select id="rol" name="rol" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <option value="Administrador" <?= $usuario['rol'] === 'Administrador' ? 'selected' : '' ?>>👑 Administrador</option>
                    <option value="Usuario" <?= $usuario['rol'] === 'Usuario' ? 'selected' : '' ?>>👤 Usuario</option>
                    <option value="Consulta" <?= $usuario['rol'] === 'Consulta' ? 'selected' : '' ?>>👁️ Solo Consulta</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="activo" value="1" <?= $usuario['activo'] ? 'checked' : '' ?>>
                    Usuario Activo
                </label>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
            <a href="/admin/usuarios" class="btn btn-secondary">❌ Cancelar</a>
            <?php if ($usuario['id'] != $user['id']): ?>
                <button type="button" class="btn btn-danger" 
                        onclick="confirmarEliminacion(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['username']) ?>')">
                    🗑️ Eliminar Usuario
                </button>
            <?php endif; ?>
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

.user-form {
    padding: 20px;
}

small {
    color: #718096;
    font-size: 12px;
    margin-top: 4px;
}
</style>

<script>
document.querySelector('.user-form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirm').value;
    
    if (password || confirm) {
        if (password !== confirm) {
            e.preventDefault();
            alert('Las contraseñas no coinciden');
            return false;
        }
    }
});

function confirmarEliminacion(id, username) {
    if (confirm(`¿Está seguro que desea eliminar al usuario "${username}"?\n\nEsta acción no se puede deshacer.`)) {
        window.location.href = '/admin/usuarios/eliminar/' + id;
    }
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
