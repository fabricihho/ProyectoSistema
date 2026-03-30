<?php 
ob_start(); 
$pageTitle = 'Gestión de Usuarios';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>👥 Gestión de Usuarios</h2>
        <a href="/admin/usuarios/crear" class="btn btn-primary">➕ Nuevo Usuario</a>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Nombre Completo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay usuarios registrados</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usr): ?>
                        <tr id="row-usuario-<?= $usr['id'] ?>">
                            <td><?= $usr['id'] ?></td>
                            <td class="col-username"><strong><?= htmlspecialchars($usr['username']) ?></strong></td>
                            <td class="col-nombre"><?= htmlspecialchars($usr['nombre_completo']) ?></td>
                            <td class="col-rol">
                                <span class="badge <?= $usr['rol'] === 'Administrador' ? 'badge-admin' : 'badge-user' ?>">
                                    <?= htmlspecialchars($usr['rol']) ?>
                                </span>
                            </td>
                            <td class="col-activo">
                                <?php if ($usr['activo']): ?>
                                    <span class="badge badge-disponible">✓ Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-anulado">✗ Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="abrirModalEditar(this)" 
                                        data-id="<?= $usr['id'] ?>"
                                        data-username="<?= htmlspecialchars($usr['username']) ?>"
                                        data-nombre="<?= htmlspecialchars($usr['nombre_completo']) ?>"
                                        data-rol="<?= $usr['rol'] ?>"
                                        data-activo="<?= $usr['activo'] ?>"
                                        class="btn btn-sm btn-secondary">✏️ Editar</button>
                                
                                <button onclick="confirmarReset(<?= $usr['id'] ?>, '<?= htmlspecialchars($usr['username']) ?>')" 
                                        class="btn btn-sm btn-warning" title="Resetear contraseña y enviar por correo">🔑 Reset</button>
                                <?php if ($usr['id'] != $user['id']): ?>
                                    <button onclick="confirmarEliminacion(<?= $usr['id'] ?>, '<?= htmlspecialchars($usr['username']) ?>')" 
                                            class="btn btn-sm btn-danger">🗑️ Eliminar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.badge-admin {
    background: #1B3C84;
    color: white;
}

.badge-user {
    background: #17a2b8;
    color: white;
}
</style>

<script>
function confirmarEliminacion(id, username) {
    if (confirm(`¿Está seguro que desea eliminar al usuario "${username}"?\n\nEsta acción no se puede deshacer.`)) {
        window.location.href = '/admin/usuarios/eliminar/' + id;
    }
}

function confirmarReset(id, username) {
    if (confirm(`¿Resetear la contraseña del usuario "${username}"?\n\nSe generará una nueva contraseña y se enviará por correo.`)) {
        window.location.href = '/admin/usuarios/reset-password/' + id;
    }
}
</script>

<!-- Modal Editar Usuario -->
<div id="modalEditarUsuario" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>✏️ Editar Usuario</h3>
            <button type="button" class="close-modal" onclick="cerrarModalEditar()">×</button>
        </div>
        <form id="formEditarUsuario" onsubmit="actualizarUsuario(event)">
            <input type="hidden" id="edit_id" name="id">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" id="edit_username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" id="edit_nombre" name="nombre_completo" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Rol</label>
                    <select id="edit_rol" name="rol" class="form-control" required>
                        <option value="Administrador">👑 Administrador</option>
                        <option value="Usuario">👤 Usuario</option>
                        <option value="Consulta">👁️ Solo Consulta</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="margin-top: 25px;">
                        <input type="checkbox" id="edit_activo" name="activo" value="1"> Usuario Activo
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nueva Contraseña (Opcional)</label>
                    <input type="password" id="edit_password" name="password" class="form-control" placeholder="Dejar vacío para mantener">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalEditar()">Cancelar</button>
                <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Modal Styles Reuse or Define if missing */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 1000;
    justify-content: center; align-items: center;
}
.modal-content {
    background: white; padding: 25px; border-radius: 8px; width: 90%;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.modal-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
.close-modal { background: none; border: none; font-size: 24px; cursor: pointer; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
</style>

<script>
function abrirModalEditar(btn) {
    const id = btn.dataset.id;
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_username').value = btn.dataset.username;
    document.getElementById('edit_nombre').value = btn.dataset.nombre;
    document.getElementById('edit_rol').value = btn.dataset.rol;
    document.getElementById('edit_activo').checked = (btn.dataset.activo == "1");
    document.getElementById('edit_password').value = '';
    
    document.getElementById('modalEditarUsuario').style.display = 'flex';
}

function cerrarModalEditar() {
    document.getElementById('modalEditarUsuario').style.display = 'none';
}

async function actualizarUsuario(e) {
    e.preventDefault();
    const id = document.getElementById('edit_id').value;
    const form = document.getElementById('formEditarUsuario');
    const formData = new FormData(form);
    
    // Disable button
    const btnSubmit = form.querySelector('button[type="submit"]');
    const originalText = btnSubmit.innerText;
    btnSubmit.disabled = true;
    btnSubmit.innerText = "Guardando...";

    try {
        const response = await fetch(`/admin/usuarios/actualizar/${id}`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (err) {
            console.error("JSON Error:", text);
            throw new Error("Respuesta inválida del servidor");
        }
        
        if (data.success) {
            // Optimistic Update
            const row = document.getElementById(`row-usuario-${id}`);
            if (row) {
                row.querySelector('.col-username strong').innerText = formData.get('username');
                row.querySelector('.col-nombre').innerText = formData.get('nombre_completo');
                
                // Update Rol Badge
                const rolCell = row.querySelector('.col-rol');
                const rol = formData.get('rol');
                rolCell.innerHTML = `<span class="badge ${rol === 'Administrador' ? 'badge-admin' : 'badge-user'}">${rol}</span>`;
                
                // Update Badge Activo
                const activoCell = row.querySelector('.col-activo');
                const isActive = formData.get('activo') ? true : false;
                activoCell.innerHTML = isActive 
                    ? '<span class="badge badge-disponible">✓ Activo</span>' 
                    : '<span class="badge badge-anulado">✗ Inactivo</span>';
                
                // Update Button Data Attributes
                const btnEdit = row.querySelector('button[onclick^="abrirModalEditar"]');
                btnEdit.dataset.username = formData.get('username');
                btnEdit.dataset.nombre = formData.get('nombre_completo');
                btnEdit.dataset.rol = rol;
                btnEdit.dataset.activo = isActive ? "1" : "0";
            }
            
            cerrarModalEditar();
            // Optional: Show simplified toast instead of alert to be faster
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error(error);
        alert('Error de conexión o servidor');
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerText = originalText;
    }
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
