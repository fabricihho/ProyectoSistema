<?php 
ob_start(); 
$pageTitle = 'Configuración - Cambiar Contraseña';
?>

<div class="card">
    <div class="card-header">
        <h2>Cambiar Contraseña</h2>
    </div>
    
    <div class="card-body">
        <form action="/configuracion/password/actualizar" method="POST" class="form-grid" style="max-width: 600px; margin: 0 auto; display: block;">
            
            <div class="form-group">
                <label for="current_password">Contraseña Actual <span class="required">*</span></label>
                <div class="password-input-wrapper">
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('current_password')">👁️</button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password">Nueva Contraseña <span class="required">*</span></label>
                <div class="password-input-wrapper">
                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('new_password')">👁️</button>
                </div>
                <small class="form-text text-muted">Mínimo 6 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Nueva Contraseña <span class="required">*</span></label>
                <div class="password-input-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">👁️</button>
                </div>
            </div>
            
            <div class="form-actions" style="margin-top: 30px; text-align: right;">
                <a href="/dashboard" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
            </div>
        </form>
    </div>
</div>

<style>
.password-input-wrapper {
    position: relative;
    display: flex;
}

.password-input-wrapper input {
    width: 100%;
    padding-right: 40px;
}

.toggle-password {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.2em;
    padding: 5px;
    opacity: 0.6;
}

.toggle-password:hover {
    opacity: 1;
}

.form-group {
    margin-bottom: 20px;
}

.required {
    color: red;
}
</style>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
    } else {
        input.type = "password";
    }
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
