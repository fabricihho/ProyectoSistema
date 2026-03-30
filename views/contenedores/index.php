<?php 
ob_start(); 
$pageTitle = 'Gestión de Contenedores';
?>

<div class="card">
    <div class="card-header">
        <h2>📦 Gestión de Contenedores (Amarros/Libros)</h2>
        <div class="header-actions">
            <a href="/catalogacion" class="btn btn-secondary">← Volver a Catalogación</a>
            <button type="button" class="btn btn-warning" id="btnUpdateLocation" disabled>📦 Cambiar Ubicación</button>
            <button type="button" class="btn btn-success" onclick="abrirModalNuevaUbicacion()">➕ Nueva Ubicación</button>
            <a href="/contenedores/crear" class="btn btn-primary">➕ Nuevo Contenedor</a>
        </div>
    </div>
    
    <div class="card-body">
        <form action="/contenedores" method="GET" class="search-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="numero">Número</label>
                    <input type="text" name="numero" id="numero" class="form-control" placeholder="Ej: 1 o 1-10" value="<?= htmlspecialchars($filtros['numero'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="gestion">Gestión</label>
                    <input type="number" name="gestion" id="gestion" class="form-control" placeholder="Año" value="<?= htmlspecialchars($filtros['gestion'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="ubicacion_id">Ubicación</label>
                    <select name="ubicacion_id" id="ubicacion_id" class="form-control">
                        <option value="">-- Todas --</option>
                        <?php foreach ($ubicaciones as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filtros['ubicacion_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tipo_contenedor">Tipo Cont.</label>
                    <select name="tipo_contenedor" id="tipo_contenedor" class="form-control">
                        <option value="">-- Todos --</option>
                        <option value="AMARRO" <?= ($filtros['tipo_contenedor'] ?? '') == 'AMARRO' ? 'selected' : '' ?>>📦 Amarro</option>
                        <option value="LIBRO" <?= ($filtros['tipo_contenedor'] ?? '') == 'LIBRO' ? 'selected' : '' ?>>📚 Libro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tipo_documento">Tipo Documento</label>
                    <select name="tipo_documento" id="tipo_documento" class="form-control">
                        <option value="">-- Todos --</option>
                        <?php foreach ($tiposDocumento as $td): ?>
                            <option value="<?= $td['codigo'] ?>" <?= ($filtros['tipo_documento'] ?? '') == $td['codigo'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($td['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <a href="/contenedores?clean=1" class="btn btn-secondary">🧹 Limpiar Filtros</a>
            </div>
            
            <!-- Per Page Control moved to Results Header -->
        </form>
    </div>
    
    <div class="card mt-20">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Resultados de Búsqueda</h3>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span>Cantidad de Filas:</span>
                <input type="number" id="perPageInput" value="<?= $paginacion['per_page'] ?? 20 ?>" min="1" max="200" 
                       style="width: 70px; padding: 5px; border-radius: 4px; border: 1px solid #ccc;"
                       onchange="updatePerPage(this.value)" onkeypress="if(event.key === 'Enter') updatePerPage(this.value)">
                <span class="badge"><?= number_format($paginacion['total'] ?? 0) ?> contenedores</span>
            </div>
        </div>

        <script>
        function updatePerPage(val) {
            val = parseInt(val);
            if (val < 1) val = 1;
            if (val > 200) val = 200;
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('per_page', val);
            urlParams.set('page', 1);
            window.location.search = urlParams.toString();
        }
        </script>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                        <?php
                        $currentSort = $_GET['sort'] ?? '';
                        $currentOrder = $_GET['order'] ?? '';
                        
                        $makeSortLink = function($col, $label) use ($filtros, $currentSort, $currentOrder) {
                            $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
                            $icon = '';
                            if ($currentSort === $col) {
                                $icon = $currentOrder === 'ASC' ? ' ▲' : ' ▼';
                            } else {
                                $icon = ' <span style="opacity:0.3; font-size: 0.8em">⇅</span>';
                            }
                            $params = $filtros;
                            $params['sort'] = $col;
                            $params['order'] = $newOrder;
                            $params['page'] = 1;
                            return '<a href="?' . http_build_query($params) . '" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">' . $label . $icon . '</a>';
                        };
                        ?>
                        <th><?= $makeSortLink('tipo_documento', 'Tipo Documento') ?></th>
                        <th><?= $makeSortLink('tipo_contenedor', 'Contenedor') ?></th>
                        <th><?= $makeSortLink('gestion', 'Gestión') ?></th>
                        <th><?= $makeSortLink('ubicacion', 'Ubicación') ?></th>
                        <th>Color</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contenedores)): ?>
                        <tr><td colspan="9" class="text-center">No hay contenedores registrados</td></tr>
                    <?php else: ?>
                        <?php foreach ($contenedores as $c): ?>
                            <tr>
                                 <td><input type="checkbox" class="select-item" value="<?= $c['id'] ?>"></td>
                                 <td><?= htmlspecialchars($c['tipo_documento_nombre'] ?? $c['tipo_documento_codigo'] ?? $c['tipo_documento'] ?? '-') ?></td>
                                 <td>
                                    <?php 
                                    $icon = ($c['tipo_contenedor'] == 'LIBRO' ? '📚 Libro' : '📦 Amarro');
                                    $badgeClass = ($c['tipo_contenedor'] == 'LIBRO' ? 'badge-libro' : 'badge-amarro');
                                    $abc = !empty($c['codigo_abc']) ? '.' . $c['codigo_abc'] : '';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $icon ?> #<?= htmlspecialchars($c['numero'] ?? '') ?><?= htmlspecialchars($abc) ?></span>
                                 </td>
                                 <td><?= htmlspecialchars($c['gestion'] ?? 'N/A') ?></td>
                                 <td><?= htmlspecialchars($c['ubicacion_nombre'] ?? 'Sin asignar') ?></td>
                                 <td>
                                    <?php if (!empty($c['color'])): ?>
                                        <span class="badge" style="background-color: #eee; color: <?= htmlspecialchars($c['color']) ?>; border: 1px solid #ccc;">
                                            <?= htmlspecialchars($c['color']) ?>
                                        </span>
                                    <?php endif; ?>
                                 </td>
                                 <td>
                                    <a href="/contenedores/ver/<?= $c['id'] ?>" class="btn btn-sm btn-primary" title="Ver Detalles">Ver</a>
                                    <a href="/contenedores/editar/<?= $c['id'] ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
                                    <a href="/contenedores/eliminar/<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este contenedor? Asegúrate de que no tenga documentos asociados.')">🗑️</a>
                                 </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if (($paginacion['total_pages'] ?? 0) > 1): ?>
            <div class="pagination">
                <?php 
                    $current = $paginacion['page'];
                    $total_p = $paginacion['total_pages'];
                    $max_visible = 10;
                    
                    $start = max(1, $current - floor($max_visible / 2));
                    $end = min($total_p, $start + $max_visible - 1);
                    
                    if ($end - $start + 1 < $max_visible) {
                        $start = max(1, $end - $max_visible + 1);
                    }
                    
                    $params = $filtros;
                ?>

                <!-- Botón Primero -->
                <?php if ($current > 1): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => 1])) ?>" class="btn btn-secondary">⇤ Primero</a>
                <?php endif; ?>

                <!-- Botón Anterior -->
                <?php if ($current > 1): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => $current - 1])) ?>" class="btn btn-warning">← Anterior</a>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled>← Anterior</button>
                <?php endif; ?>
                
                <div class="pagination-numbers">
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?<?= http_build_query(array_merge($params, ['page' => $i])) ?>" 
                           class="btn <?= $i == $current ? 'btn-primary active' : 'btn-light' ?> page-num">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
                
                <!-- Botón Siguiente -->
                <?php if ($current < $total_p): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => $current + 1])) ?>" class="btn btn-warning">Siguiente →</a>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled>Siguiente →</button>
                <?php endif; ?>

                <!-- Botón Último -->
                <?php if ($current < $total_p): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => $total_p])) ?>" class="btn btn-secondary">Último ⇥</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>


<style>
/* Custom Badges for Container Types */
.badge-amarro {
    background-color: #6f42c1;
    color: white;
    padding: 5px 12px;
    border-radius: 50px; /* Pill shape */
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.85em;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.badge-libro {
    background-color: #17a2b8;
    color: white;
    padding: 5px 12px;
    border-radius: 50px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.85em;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.table-responsive { overflow-x: auto; }

/* Table Styles to match Documentos */
.table thead th {
    background-color: #1B3C84;
    color: white;
    border-bottom: none;
    vertical-align: middle;
}
.table thead th a {
    color: white !important;
}
.table tbody tr:hover {
    background-color: rgba(27, 60, 132, 0.05);
}

/* Pagination Styles - Matching Documentos EXACTLY */
.pagination { 
    display: flex; 
    justify-content: center; 
    align-items: center; 
    gap: 8px; 
    padding: 25px 0; 
    flex-wrap: wrap; 
}
.pagination-numbers { 
    display: flex; 
    gap: 2px; 
    background: #fff;
    padding: 3px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}
.btn-light { 
    background: white; 
    border: none; 
    color: #007bff; 
    font-weight: 500;
}
.btn-light:hover {
    background-color: #e9ecef;
    color: #0056b3;
    text-decoration: none;
}
.btn-warning { 
    background: #ffc107; 
    color: #212529; 
    border: 1px solid #ffc107; 
    font-weight: 500;
}
.btn-warning:hover {
    background: #e0a800;
    border-color: #d39e00;
    color: #212529;
}
.btn-secondary {
    background: #6c757d;
    border: 1px solid #6c757d;
    color: white;
}
.btn-secondary:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
.pagination .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 38px;
    min-width: 38px;
    padding: 0 12px;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.2s;
    line-height: normal; 
}
.page-num { 
    border-radius: 2px; 
}
.btn-primary.active { 
    background: #1B3C84; 
    border-color: #1B3C84; 
    color: white; 
    cursor: default; 
    z-index: 1;
}
.btn-primary.active:focus {
    box-shadow: none;
}

/* Search Form Layout - Documentos Style */
.search-form { padding: 20px; }
.form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 200px; }
.form-actions { display: flex; gap: 10px; justify-content: center; margin-top: 20px; align-items: center; }
.form-actions .btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; height: 38px; }

</style>

<!-- Modal Cambio Ubicación Masiva -->
<div class="modal fade" id="locationModal" tabindex="-1" role="dialog" style="display: none; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1050;">
    <div class="modal-dialog" role="document" style="margin: 10% auto; max-width: 400px; background: white; border-radius: 5px; box-shadow: 0 3px 7px rgba(0,0,0,0.3);">
        <div class="modal-content">
            <div class="modal-header" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <h5 class="modal-title">Cambiar Ubicación (<span id="countSelected">0</span>)</h5>
                <button type="button" class="close" onclick="document.getElementById('locationModal').style.display='none'" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <form action="/contenedores/actualizar-ubicacion-masiva" method="POST" id="bulkLocationForm">
                <div class="modal-body" style="padding: 20px;">
                    <div class="form-group">
                        <label>Nueva Ubicación:</label>
                        <select name="ubicacion_id" class="form-control" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($ubicaciones as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="selectedIdsInputContainer"></div>
                </div>
                <div class="modal-footer" style="padding: 15px; border-top: 1px solid #eee; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('locationModal').style.display='none'">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.select-item');
    const btnUpdate = document.getElementById('btnUpdateLocation');
    const countSpan = document.getElementById('countSelected');
    const containerInputs = document.getElementById('selectedIdsInputContainer');
    const modal = document.getElementById('locationModal');
    
    // Toggle All
    if(selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateButtonState();
        });
    }
    
    // Toggle Individual
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateButtonState);
    });
    
    function updateButtonState() {
        const selected = document.querySelectorAll('.select-item:checked');
        if(btnUpdate) {
            btnUpdate.disabled = selected.length === 0;
            if (countSpan) countSpan.textContent = selected.length;
        }
    }
    
    // Open Modal logic
    if(btnUpdate) {
        btnUpdate.addEventListener('click', function() {
            if (this.disabled) return;
            
            containerInputs.innerHTML = '';
            const selected = document.querySelectorAll('.select-item:checked');
            
            selected.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                containerInputs.appendChild(input);
            });
            
            modal.style.display = 'block';
        });
    }
    
    // Close modal on outside click
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    });
});
</script>

<!-- Modal Nueva Ubicación -->
<div class="modal fade" id="newLocationModal" tabindex="-1" role="dialog" style="display: none; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1060;">
    <div class="modal-dialog" role="document" style="margin: 10% auto; max-width: 400px; background: white; border-radius: 5px; box-shadow: 0 3px 7px rgba(0,0,0,0.3);">
        <div class="modal-content">
            <div class="modal-header" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <h5 class="modal-title" style="margin: 0; color: #1B3C84;">➕ Crear Nueva Ubicación</h5>
                <button type="button" class="close" onclick="document.getElementById('newLocationModal').style.display='none'" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <form id="newLocationForm" onsubmit="submitNewLocation(event)">
                <div class="modal-body" style="padding: 20px;">
                    <div class="form-group">
                        <label>Nombre de la Ubicación:</label>
                        <input type="text" name="nombre" id="new_location_name" class="form-control" required placeholder="Ej: Estante A-1, Deposito 2...">
                    </div>
                </div>
                <div class="modal-footer" style="padding: 15px; border-top: 1px solid #eee; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('newLocationModal').style.display='none'">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Ubicación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalNuevaUbicacion() {
    document.getElementById('newLocationModal').style.display = 'block';
    setTimeout(() => document.getElementById('new_location_name').focus(), 100);
}

function submitNewLocation(e) {
    e.preventDefault();
    const name = document.getElementById('new_location_name').value.trim();
    if (!name) return;
    
    fetch('/contenedores/api-crear-ubicacion', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'nombre=' + encodeURIComponent(name)
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            alert('✅ Ubicación creada correctamente');
            location.reload(); 
        } else {
            alert('❌ Error: ' + (data.error || 'No se pudo crear'));
        }
    })
    .catch(err => alert('Error de red'));
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
