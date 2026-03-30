<?php 
ob_start(); 
$pageTitle = 'Nuevo Préstamo - Selección Múltiple';
?>

<div class="card">
    <div class="card-header">
        <h2>➕ Nuevo Préstamo de Documentos</h2>
        <div class="header-actions">
            <a href="/prestamos" class="btn btn-secondary">← Ver Historial</a>
        </div>
    </div>
    
    <!-- Filtros de búsqueda -->
    <form method="GET" class="search-form" style="padding: 20px; border-bottom: 1px solid #E2E8F0;">
        <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="search">Búsqueda General</label>
                <input type="text" id="search" name="search" class="form-control" 
                       value="<?= htmlspecialchars($filtros['search'] ?? '') ?>" 
                       placeholder="Nro comprobante, código ABC...">
            </div>
            
            <div class="form-group">
                <label for="gestion">Gestión</label>
                <input type="number" id="gestion" name="gestion" class="form-control" 
                       value="<?= htmlspecialchars($filtros['gestion'] ?? '') ?>" 
                       min="2000" max="<?= date('Y') + 1 ?>">
            </div>
            
            <div class="form-group">
                <label for="tipo_documento">Tipo de Documento</label>
                <select id="tipo_documento" name="tipo_documento" class="form-control">
                    <option value="">Todos</option>
                    <option value="REGISTRO_DIARIO" <?= ($filtros['tipo_documento'] ?? '') === 'REGISTRO_DIARIO' ? 'selected' : '' ?>>📋 Registro Diario</option>
                    <option value="REGISTRO_INGRESO" <?= ($filtros['tipo_documento'] ?? '') === 'REGISTRO_INGRESO' ? 'selected' : '' ?>>💵 Registro Ingreso</option>
                    <option value="REGISTRO_CEPS" <?= ($filtros['tipo_documento'] ?? '') === 'REGISTRO_CEPS' ? 'selected' : '' ?>>🏦 Registro CEPS</option>
                    <option value="PREVENTIVOS" <?= ($filtros['tipo_documento'] ?? '') === 'PREVENTIVOS' ? 'selected' : '' ?>>📊 Preventivos</option>
                    <option value="ASIENTOS_MANUALES" <?= ($filtros['tipo_documento'] ?? '') === 'ASIENTOS_MANUALES' ? 'selected' : '' ?>>✍️ Asientos Manuales</option>
                    <option value="DIARIOS_APERTURA" <?= ($filtros['tipo_documento'] ?? '') === 'DIARIOS_APERTURA' ? 'selected' : '' ?>>📂 Diarios de Apertura</option>
                    <option value="REGISTRO_TRASPASO" <?= ($filtros['tipo_documento'] ?? '') === 'REGISTRO_TRASPASO' ? 'selected' : '' ?>>🔄 Registro Traspaso</option>
                    <option value="HOJA_RUTA_DIARIOS" <?= ($filtros['tipo_documento'] ?? '') === 'HOJA_RUTA_DIARIOS' ? 'selected' : '' ?>>🗺️ Hoja de Ruta - Diarios</option>
                </select>
            </div>
            
            <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <a href="/prestamos/nuevo" class="btn btn-secondary">🔄 Limpiar</a>
            </div>
        </div>
    </form>
    
    <!-- Tabla de documentos disponibles -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">
                        <input type="checkbox" id="checkAll" onclick="toggleTodos(this)" title="Seleccionar todos">
                    </th>
                    <th>Tipo Documento</th>
                    <th>Gestión</th>
                    <th>Nro Comprobante</th>
                    <th>Contenedor</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documentos)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay documentos disponibles</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documentos as $doc): ?>
                        <?php 
                            $estado = $doc['estado_documento'] ?? 'DISPONIBLE';
                            $esPrestable = in_array($estado, ['DISPONIBLE', 'NO UTILIZADO', 'ANULADO']);
                            
                            // Badge color logic
                            $badgeClass = 'badge-secondary';
                            $estadoIcon = '⚪';
                            
                            switch ($estado) {
                                case 'DISPONIBLE':
                                    $badgeClass = 'badge-disponible';
                                    $estadoIcon = '🟢';
                                    break;
                                case 'NO UTILIZADO':
                                    $badgeClass = 'badge-inutilizado'; // Assuming this class exists or uses generic yellow
                                    $estadoIcon = '🟡';
                                    break;
                                case 'ANULADO':
                                    $badgeClass = 'badge-anulado'; // Assuming purple/dark
                                    $estadoIcon = '🟣';
                                    break;
                                case 'FALTA':
                                    $badgeClass = 'badge-falta'; // Red
                                    $estadoIcon = '🔴';
                                    break;
                                case 'PRESTADO':
                                    $badgeClass = 'badge-prestado'; // Orange
                                    $estadoIcon = '🟠';
                                    break;
                            }
                            
                            // Inline styles for badges if classes are missing in this view
                            $badgeStyle = "";
                            if ($estado === 'NO UTILIZADO') $badgeStyle = "background-color: #ffc107; color: #333;";
                            elseif ($estado === 'ANULADO') $badgeStyle = "background-color: #6f42c1; color: white;";
                            elseif ($estado === 'FALTA') $badgeStyle = "background-color: #dc3545; color: white;";
                            elseif ($estado === 'PRESTADO') $badgeStyle = "background-color: #fd7e14; color: white;";
                            elseif ($estado === 'DISPONIBLE') $badgeStyle = "background-color: #28a745; color: white;";
                        ?>
                        <tr class="<?= !$esPrestable ? 'row-disabled' : '' ?>" style="<?= !$esPrestable ? 'opacity: 0.6; background: #f8f9fa;' : '' ?>">
                            <td>
                                <input type="checkbox" class="doc-checkbox" 
                                       value="<?= $doc['id'] ?>"
                                       data-tipo="<?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?>"
                                       data-gestion="<?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?>"
                                       data-comprobante="<?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?>"
                                       data-contenedor="<?= !empty($doc['contenedor_numero']) ? htmlspecialchars($doc['tipo_contenedor'] . ' #' . $doc['contenedor_numero']) : 'Sin asignar' ?>"
                                       <?= !$esPrestable ? 'disabled' : '' ?>>
                            </td>
                            <td><?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?></td>
                            <td>
                                <?php if (!empty($doc['tipo_contenedor'])): ?>
                                    <?= htmlspecialchars($doc['tipo_contenedor']) ?> #<?= htmlspecialchars($doc['contenedor_numero']) ?>
                                <?php else: ?>
                                    <span style="color: #999;">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="padding: 5px 10px; border-radius: 4px; font-size: 0.85em; font-weight: 500; <?= $badgeStyle ?>">
                                    <?= $estadoIcon ?> <?= ucfirst(strtolower(str_replace('_', ' ', $estado))) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Lista de documentos seleccionados -->
    <div id="documentos-seleccionados" style="display: none; padding: 20px; background: #f0f9ff; border-top: 2px solid #3182CE;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="color: #1B3C84; margin: 0;">📋 Documentos Seleccionados (<span id="selected-count">0</span>)</h3>
            <button type="button" class="btn btn-primary" onclick="procesarPrestamo()" id="btn-procesar">
                📤 Procesar Préstamo (<span id="count">0</span> docs)
            </button>
        </div>
        <div id="lista-documentos" style="display: grid; gap: 10px; margin-bottom: 15px;"></div>
        
        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #cbd5e0;">
            <div class="form-group">
                <label for="unidad_area_solicitante">Unidad/Área Solicitante <span class="required">*</span></label>
                <select id="unidad_area_solicitante" class="form-control">
                    <option value="">Seleccione...</option>
                    <?php foreach ($unidades as $ubi): ?>
                        <option value="<?= $ubi['id'] ?>"><?= htmlspecialchars($ubi['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="nombre_prestatario">Nombre Prestatario</label>
                <input type="text" id="nombre_prestatario" class="form-control" placeholder="Opcional...">
            </div>
            
            <div class="form-group">
                <label for="fecha_devolucion">Fecha de Devolución <span class="required">*</span></label>
                <input type="date" id="fecha_devolucion" class="form-control" min="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label for="observaciones_prestamo">Observaciones</label>
                <input type="text" id="observaciones_prestamo" class="form-control" placeholder="Motivo del préstamo...">
            </div>
        </div>
    </div>
</div>

<style>
.header-actions {
    display: flex;
    gap: 10px;
}

.required {
    color: #E53E3E;
}

.doc-item {
    background: white;
    padding: 10px;
    border-radius: 4px;
    border-left: 3px solid #3182CE;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.doc-item button {
    background: #E53E3E;
    color: white;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.doc-item button:hover {
    background: #C53030;
}
</style>

<script>
let documentosSeleccionados = [];

// Cargar selección del localStorage al iniciar
document.addEventListener('DOMContentLoaded', function() {
    // Cargar documentos seleccionados del localStorage
    const saved = localStorage.getItem('prestamo_seleccionados');
    if (saved) {
        try {
            documentosSeleccionados = JSON.parse(saved);
            // Marcar checkboxes de documentos que están en la página actual
            documentosSeleccionados.forEach(doc => {
                const checkbox = document.querySelector(`.doc-checkbox[value="${doc.id}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            actualizarSeleccion();
        } catch (e) {
            console.error('Error al cargar selección:', e);  
            localStorage.removeItem('prestamo_seleccionados');
        }
    }
    
    // Set default date (7 days from now)
    const fecha = document.getElementById('fecha_devolucion');
    const hoy = new Date();
    hoy.setDate(hoy.getDate() + 7);
    fecha.value = hoy.toISOString().split('T')[0];
});

function toggleTodos(checkbox) {
    const checkboxes = document.querySelectorAll('.doc-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    actualizarSeleccion();
}

// Escuchar cambios en checkboxes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('doc-checkbox')) {
        actualizarSeleccion();
    }
});

function actualizarSeleccion() {
    // Obtener selección actual de la página
    const seleccionPagina = [];
    document.querySelectorAll('.doc-checkbox').forEach(checkbox => {
        seleccionPagina.push({
            id: checkbox.value,
            checked: checkbox.checked,
            tipo: checkbox.dataset.tipo,
            gestion: checkbox.dataset.gestion,
            comprobante: checkbox.dataset.comprobante,
            contenedor: checkbox.dataset.contenedor
        });
    });
    
    // Eliminar documentos de esta página del array global
    const idsEnPagina = seleccionPagina.map(d => d.id);
    documentosSeleccionados = documentosSeleccionados.filter(d => !idsEnPagina.includes(d.id));
    
    // Agregar documentos marcados de esta página
    seleccionPagina.forEach(doc => {
        if (doc.checked) {
            documentosSeleccionados.push({
                id: doc.id,
                tipo: doc.tipo,
                gestion: doc.gestion,
                comprobante: doc.comprobante,
                contenedor: doc.contenedor
            });
        }
    });
    
    // Guardar en localStorage
    localStorage.setItem('prestamo_seleccionados', JSON.stringify(documentosSeleccionados));
    
    // Actualizar contador
    document.getElementById('count').textContent = documentosSeleccionados.length;
    document.getElementById('selected-count').textContent = documentosSeleccionados.length;
    
    // Mostrar/ocultar sección de seleccionados
    const seccion = document.getElementById('documentos-seleccionados');
    if (documentosSeleccionados.length > 0) {
        seccion.style.display = 'block';
        mostrarLista();
    } else {
        seccion.style.display = 'none';
    }
}

function mostrarLista() {
    const lista = document.getElementById('lista-documentos');
    lista.innerHTML = documentosSeleccionados.map((doc, index) => `
        <div class="doc-item">
            <div>
                <strong>${doc.tipo}</strong> - 
                Gestión ${doc.gestion} - 
                #${doc.comprobante} 
                <small style="color: #666;">(${doc.contenedor})</small>
            </div>
            <button onclick="quitarDocumento('${doc.id}')">✕ Quitar</button>
        </div>
    `).join('');
}

function quitarDocumento(docId) {
    // Remover del array
    documentosSeleccionados = documentosSeleccionados.filter(d => d.id !== docId);
    
    // Desmarcar checkbox si está en la página actual
    const checkbox = document.querySelector(`.doc-checkbox[value="${docId}"]`);
    if (checkbox) checkbox.checked = false;
    
    // Guardar y actualizar
    localStorage.setItem('prestamo_seleccionados', JSON.stringify(documentosSeleccionados));
    actualizarSeleccion();
}

function procesarPrestamo() {
    if (documentosSeleccionados.length === 0) {
        alert('⚠️ Debes seleccionar al menos un documento');
        return;
    }
    
    const unidad = document.getElementById('unidad_area_solicitante').value;
    const prestatario = document.getElementById('nombre_prestatario').value;
    const fecha = document.getElementById('fecha_devolucion').value;
    
    if (!unidad || !fecha) {
        alert('⚠️ Debes completar Unidad/Área indicando el solicitante y la Fecha de Devolución');
        return;
    }
    
    // Confirmar
    if (!confirm(`¿Confirmar préstamo de ${documentosSeleccionados.length} documento(s)?`)) {
        return;
    }
    
    // Crear formulario y enviar
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/prestamos/guardar-multiple';
    
    // Agregar datos
    form.innerHTML = `
        <input type="hidden" name="unidad_area_id" value="${unidad}">
        <input type="hidden" name="nombre_prestatario" value="${prestatario}">
        <input type="hidden" name="fecha_devolucion" value="${fecha}">
        <input type="hidden" name="observaciones" value="${document.getElementById('observaciones_prestamo').value}">
        <input type="hidden" name="documentos" value='${JSON.stringify(documentosSeleccionados.map(d => d.id))}'>
    `;
    
    document.body.appendChild(form);
    form.submit();
    
    // Limpiar localStorage después de enviar
    localStorage.removeItem('prestamo_seleccionados');
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
