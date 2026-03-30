<?php 
ob_start(); 
$pageTitle = 'Catalogación y Búsqueda de Documentos';
$modoLotes = isset($_GET['modo_lotes']) && $_GET['modo_lotes'] == '1';
?>

<div class="card">
    <div class="card-header">
        <h2>Búsqueda Avanzada</h2>
        <div class="header-actions">
            <a href="/catalogacion/crear" class="btn btn-primary">➕ Nuevo Documento</a>
            <?php if ($modoLotes): ?>
                <a href="/catalogacion" class="btn btn-secondary">← Modo Normal</a>
                <button type="button" class="btn btn-warning" onclick="abrirModalAsignacion()">📦 Asignar Contenedor</button>
                <button type="button" class="btn btn-success" onclick="procesarLote()">📋 Generar Reporte Lote</button>
            <?php else: ?>
                <a href="/catalogacion?modo_lotes=1" class="btn btn-warning">📦 Buscar por Lotes</a>
            <?php endif; ?>
        </div>
    </div>
    
    <form method="GET" action="/catalogacion" class="search-form">
        <?php if ($modoLotes): ?>
            <input type="hidden" name="modo_lotes" value="1">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label for="search">Búsqueda General</label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    class="form-control" 
                    placeholder="Nro Comprobante, Código ABC, Observaciones..."
                    value="<?= htmlspecialchars($filtros['search']) ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="gestion">Gestión</label>
                <input 
                    type="text" 
                    id="gestion" 
                    name="gestion" 
                    class="form-control"
                    placeholder="2023"
                    value="<?= htmlspecialchars($filtros['gestion']) ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="ubicacion_id">Ubicación</label>
                <select id="ubicacion_id" name="ubicacion_id" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($ubicaciones as $ub): ?>
                        <option value="<?= $ub['id'] ?>" <?= $filtros['ubicacion_id'] == $ub['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ub['nombre'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="estado_documento">Estado</label>
                <select id="estado_documento" name="estado_documento" class="form-control">
                    <option value="">Todos</option>
                    <option value="DISPONIBLE" <?= isset($_GET['estado_documento']) && $_GET['estado_documento'] === 'DISPONIBLE' ? 'selected' : '' ?>>🟢 Disponible</option>
                    <option value="FALTA" <?= isset($_GET['estado_documento']) && $_GET['estado_documento'] === 'FALTA' ? 'selected' : '' ?>>🔴 Falta</option>
                    <option value="PRESTADO" <?= isset($_GET['estado_documento']) && $_GET['estado_documento'] === 'PRESTADO' ? 'selected' : '' ?>>🔵 Prestado</option>
                    <option value="NO UTILIZADO" <?= isset($_GET['estado_documento']) && $_GET['estado_documento'] === 'NO UTILIZADO' ? 'selected' : '' ?>>🟡 No Utilizado</option>
                    <option value="ANULADO" <?= isset($_GET['estado_documento']) && $_GET['estado_documento'] === 'ANULADO' ? 'selected' : '' ?>>🟣 Anulado</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tipo_documento">Tipo de Documento</label>
                <select id="tipo_documento" name="tipo_documento" class="form-control">
                    <option value="">Todos</option>
                    <option value="REGISTRO_DIARIO" <?= isset($_GET['tipo_documento']) && $_GET['tipo_documento'] === 'REGISTRO_DIARIO' ? 'selected' : '' ?>>📋 Registro Diario</option>
                    <option value="REGISTRO_INGRESO" <?= isset($_GET['tipo_documento']) && $_GET['tipo_documento'] === 'REGISTRO_INGRESO' ? 'selected' : '' ?>>💵 Registro Ingreso</option>
                    <option value="REGISTRO_CEPS" <?= isset($_GET['tipo_documento']) && $_GET['tipo_documento'] === 'REGISTRO_CEPS' ? 'selected' : '' ?>>🏦 Registro CEPS</option>
                    <option value="PREVENTIVOS" <?= isset($_GET['tipo_documento']) && $_GET['tipo_documento'] === 'PREVENTIVOS' ? 'selected' : '' ?>>📊 Preventivos</option>
                    <option value="ASIENTOS_MANUALES" <?= isset($_GET['tipo_documento']) && $_GET['tipo_documento'] === 'ASIENTOS_MANUALES' ? 'selected' : '' ?>>✍️ Asientos Manuales</option>
                    <option value="DIARIOS_APERTURA" <?= isset($_GET['tipo_documento']) && $_GET['tipo_documento'] === 'DIARIOS_APERTURA' ? 'selected' : '' ?>>📂 Diarios de Apertura</option>
                    <option value="REGISTRO_TRASPASO" <?= isset($_GET['tipo_documento']) && $_GET['tipo_documento'] === 'REGISTRO_TRASPASO' ? 'selected' : '' ?>>🔄 Registro Traspaso</option>
                    <option value="HOJA_RUTA_DIARIOS" <?= isset($_GET['tipo_documento']) && $_GET['tipo_documento'] === 'HOJA_RUTA_DIARIOS' ? 'selected' : '' ?>>🗺️ Hoja de Ruta - Diarios</option>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">🔍 Buscar</button>
            <a href="/catalogacion<?= $modoLotes ? '?modo_lotes=1' : '' ?>" class="btn btn-secondary">🔄 Limpiar Filtros</a>
        </div>
    </form>
</div>

<?php if ($modoLotes): ?>
<div class="alert alert-info">
    <strong>Modo Lotes Activado:</strong> Selecciona los documentos que deseas incluir en el reporte haciendo clic en los checkboxes.
</div>
<?php endif; ?>

<div class="card mt-20">
    <div class="card-header">
        <h3>Resultados de Búsqueda</h3>
        <span class="badge"><?= number_format($paginacion['total']) ?> documentos</span>
    </div>
    
    <?php if (empty($documentos)): ?>
        <div class="alert alert-info">
            No se encontraron documentos con los criterios de búsqueda especificados.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <?php if ($modoLotes): ?>
                            <th style="width: 50px;">
                                <input type="checkbox" id="seleccionar-todos" onclick="toggleTodos(this)">
                            </th>
                        <?php endif; ?>
                        <th>Gestión</th>
                        <th>Tipo</th>
                        <th>Nro Comprobante</th>
                        <th>Código ABC</th>
                        <th>Contenedor</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos as $doc): 
                        $estado = $doc['estado_documento'] ?? 'DISPONIBLE';
                        $tipo = $doc['tipo_documento'] ?? '';
                        
                        // Abbreviate Type
                        $abrev = 'N/A';
                        switch($tipo) {
                            case 'REGISTRO_DIARIO': $abrev = 'DIA'; break;
                            case 'REGISTRO_INGRESO': $abrev = 'ING'; break;
                            case 'REGISTRO_CEPS': $abrev = 'CEPS'; break;
                            case 'PREVENTIVOS': $abrev = 'PRE'; break;
                            case 'ASIENTOS_MANUALES': $abrev = 'MAN'; break;
                            case 'DIARIOS_APERTURA': $abrev = 'DAP'; break;
                            case 'REGISTRO_TRASPASO': $abrev = 'TRA'; break;
                            case 'HOJA_RUTA_DIARIOS': $abrev = 'HRD'; break;
                            default: $abrev = substr($tipo, 0, 3); 
                        }

                        $rowClass = '';
                        switch($estado) {
                            case 'FALTA':
                                $rowClass = 'row-falta';
                                break;
                            case 'PRESTADO':
                                $rowClass = 'row-prestado';
                                break;
                            case 'ANULADO':
                                $rowClass = 'row-anulado';
                                break;
                            case 'NO UTILIZADO':
                                $rowClass = 'row-no-utilizado';
                                break;
                            case 'DISPONIBLE':
                                $rowClass = 'row-disponible';
                                break;
                        }
                    ?>
                        <tr class="<?= $rowClass ?>" data-doc-id="<?= $doc['id'] ?>">
                            <?php if ($modoLotes): ?>
                                <td>
                                    <input type="checkbox" class="doc-checkbox" value="<?= $doc['id'] ?>" 
                                           data-gestion="<?= htmlspecialchars($doc['gestion']) ?>"
                                           data-comprobante="<?= htmlspecialchars($doc['nro_comprobante']) ?>"
                                           data-estado="<?= htmlspecialchars($estado) ?>"
                                           data-contenedor="<?= !empty($doc['contenedor_numero']) ? htmlspecialchars($doc['tipo_contenedor'] . ' #' . $doc['contenedor_numero']) : 'Sin asignar' ?>"
                                           data-ubicacion="<?= htmlspecialchars($doc['ubicacion_nombre'] ?? 'N/A') ?>">
                                </td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?></td>
                            <td><span class="badge" style="background-color: #6c757d;" title="<?= htmlspecialchars($tipo) ?>"><?= htmlspecialchars($abrev) ?></span></td>
                            <td><?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($doc['codigo_abc'] ?? 'N/A') ?></td>
                            <td>
                                <?php if (!empty($doc['contenedor_numero'])): ?>
                                    <span class="badge badge-info"><?= htmlspecialchars($doc['tipo_contenedor']) ?> #<?= htmlspecialchars($doc['contenedor_numero']) ?></span>
                                <?php else: ?>
                                    Sin asignar
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($doc['ubicacion_nombre'] ?? 'N/A') ?></td>
                            <td>
                                <?php
                                $badgeClass = '';
                                $icon = '';
                                switch($estado) {
                                    case 'DISPONIBLE':
                                        $badgeClass = 'badge-disponible';
                                        $icon = '🟢';
                                        break;
                                    case 'FALTA':
                                        $badgeClass = 'badge-falta';
                                        $icon = '🔴';
                                        break;
                                    case 'PRESTADO':
                                        $badgeClass = 'badge-prestado';
                                        $icon = '🔵';
                                        break;
                                    case 'ANULADO':
                                        $badgeClass = 'badge-anulado';
                                        $icon = '🟣';
                                        break;
                                    case 'NO UTILIZADO':
                                        $badgeClass = 'badge-no-utilizado';
                                        $icon = '🟡';
                                        break;
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $icon ?> <?= htmlspecialchars($estado) ?></span>
                            </td>
                            <td>
                                <a href="/catalogacion/ver/<?= $doc['id'] ?>" class="btn btn-sm btn-primary">Ver</a>
                                <a href="/catalogacion/editar/<?= $doc['id'] ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
                                <a href="/catalogacion/eliminar/<?= $doc['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este documento?')" title="Eliminar">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($paginacion['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($paginacion['page'] > 1): ?>
                    <a href="?<?= http_build_query(array_merge($filtros, ['page' => $paginacion['page'] - 1, 'modo_lotes' => $modoLotes ? '1' : null])) ?>" class="btn btn-secondary">← Anterior</a>
                <?php endif; ?>
                
                <span class="page-info">
                    Página <?= $paginacion['page'] ?> de <?= $paginacion['total_pages'] ?>
                </span>
                
                <?php if ($paginacion['page'] < $paginacion['total_pages']): ?>
                    <a href="?<?= http_build_query(array_merge($filtros, ['page' => $paginacion['page'] + 1, 'modo_lotes' => $modoLotes ? '1' : null])) ?>" class="btn btn-secondary">Siguiente →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.search-form { padding: 20px; }
.form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 200px; }
.form-actions { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
.table-responsive { overflow-x: auto; }
.pagination { display: flex; justify-content: center; align-items: center; gap: 15px; padding: 20px; }
.page-info { padding: 8px 16px; }
.badge { background: #1B3C84; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; display: inline-block; }
.badge-info { background: #17a2b8; }
.badge-disponible { background: #28a745; } /* Verde */
.badge-falta { background: #dc3545; } /* Rojo */
.badge-prestado { background: #17a2b8; } /* Celeste */
.badge-no-utilizado { background: #ffc107; color: #333; } /* Amarillo */
.badge-anulado { background: #6f42c1; } /* Morado */
.btn-sm { padding: 4px 12px; font-size: 13px; }
.mt-20 { margin-top: 20px; }
.alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px; }

/* Colores de filas según estado */
.row-disponible { background-color: #f0fff0; } /* Verde muy claro */
.row-falta { background-color: #ffe6e6; font-weight: 500; } /* Rojo claro */
.row-prestado { background-color: #e6f7ff; } /* Celeste claro */
.row-no-utilizado { background-color: #fff9e6; } /* Amarillo claro */
.row-anulado { background-color: #f3e6ff; } /* Morado claro */

.row-falta td { color: #721c24; }

.header-actions { display: flex; gap: 10px; }
.card-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }

.doc-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

#seleccionar-todos {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
</style>

<script>
function toggleTodos(checkbox) {
    const checkboxes = document.querySelectorAll('.doc-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function procesarLote() {
    const seleccionados = [];
    document.querySelectorAll('.doc-checkbox:checked').forEach(checkbox => {
        seleccionados.push({
            id: checkbox.value,
            gestion: checkbox.dataset.gestion,
            comprobante: checkbox.dataset.comprobante,
            estado: checkbox.dataset.estado,
            contenedor: checkbox.dataset.contenedor,
            ubicacion: checkbox.dataset.ubicacion
        });
    });
    
    if (seleccionados.length === 0) {
        alert('⚠️ Debes seleccionar al menos un documento');
        return;
    }
    
    // Crear ventana de reporte
    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Reporte de Lote - ${seleccionados.length} Documentos</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h1 { color: #1B3C84; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background: #1B3C84; color: white; }
                .disponible { background: #d4edda; }
                .falta { background: #f8d7da; color: #721c24; font-weight: bold; }
                .prestado { background: #d1ecf1; }
                .no-utilizado { background: #fff3cd; }
                .anulado { background: #e2d9f3; }
                .header { display: flex; justify-content: space-between; align-items: center; }
                @media print {
                    button { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📋 Reporte de Lote - Sistema TAMEP</h1>
                <button onclick="window.print()">🖨️ Imprimir</button>
            </div>
            <p><strong>Fecha:</strong> ${new Date().toLocaleString('es-BO')}</p>
            <p><strong>Total documentos seleccionados:</strong> ${seleccionados.length}</p>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Gestión</th>
                        <th>Nro Comprobante</th>
                        <th>Amarro/Libro</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    ${seleccionados.map((doc, index) => `
                        <tr class="${doc.estado.toLowerCase()}">
                            <td>${index + 1}</td>
                            <td>${doc.gestion}</td>
                            <td>${doc.comprobante}</td>
                            <td>${doc.contenedor}</td>
                            <td>${doc.ubicacion}</td>
                            <td>${doc.estado}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </body>
        </html>
    `);
}
function abrirModalAsignacion() {
    const seleccionados = [];
    document.querySelectorAll('.doc-checkbox:checked').forEach(checkbox => {
        seleccionados.push(checkbox.value);
    });
    
    if (seleccionados.length === 0) {
        alert('⚠️ Debes seleccionar al menos un documento');
        return;
    }
    
    document.getElementById('ids_lote').value = JSON.stringify(seleccionados);
    document.getElementById('count_seleccionados').textContent = seleccionados.length;
    
    // Show modal
    document.getElementById('modalAsignacion').style.display = 'block';
}

function cerrarModalAsignacion() {
    document.getElementById('modalAsignacion').style.display = 'none';
}
</script>

<?php if ($modoLotes): ?>
<!-- Modal Asignación de Contenedor -->
<div id="modalAsignacion" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 50%; max-width: 500px; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #1B3C84;">Asignar Contenedor a Lote</h3>
            <span onclick="cerrarModalAsignacion()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        </div>
        
        <form action="/catalogacion/lote/actualizar-contenedor" method="POST">
            <input type="hidden" name="ids" id="ids_lote">
            
            <p>Se asignará el contenedor seleccionado a <strong><span id="count_seleccionados">0</span></strong> documentos.</p>
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="contenedor_lote">Seleccione Contenedor:</label>
                <select name="contenedor_id" id="contenedor_lote" class="form-control" required style="width: 100%; padding: 8px;">
                    <option value="">-- Seleccione --</option>
                    <?php if (isset($contenedores)): ?>
                        <?php foreach ($contenedores as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= $c['tipo_contenedor'] ?> #<?= $c['numero'] ?> (<?= $c['gestion'] ?? '-' ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div style="text-align: right;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalAsignacion()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
