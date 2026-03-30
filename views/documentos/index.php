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
                <button type="button" class="btn btn-warning" onclick="abrirModalEdicionLote()">✏️ Editar Lote</button>
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
                <input type="text" id="search" name="search" class="form-control"
                    placeholder="Nro (ej. 1-50), Código ABC, Observaciones..."
                    value="<?= htmlspecialchars($filtros['search']) ?>">
            </div>

            <div class="form-group">
                <label for="gestion">Gestión</label>
                <input type="text" id="gestion" name="gestion" class="form-control" placeholder="2023"
                    value="<?= htmlspecialchars($filtros['gestion']) ?>">
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
            <a href="/catalogacion?clean=1<?= $modoLotes ? '&modo_lotes=1' : '' ?>" class="btn btn-secondary">🧹 Limpiar
                Filtros</a>
        </div>
    </form>
</div>

<?php if ($modoLotes): ?>
    <div class="alert alert-info">
        <strong>Modo Lotes Activado:</strong> Selecciona los documentos que deseas incluir en el reporte haciendo clic en
        los checkboxes.
    </div>
<?php endif; ?>

<div class="card mt-20">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Resultados de Búsqueda</h3>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span>Cantidad de Filas:</span>
            <input type="number" id="perPageInput" value="<?= $paginacion['per_page'] ?? 20 ?>" min="1" max="200"
                style="width: 70px; padding: 5px; border-radius: 4px; border: 1px solid #ccc;"
                onchange="updatePerPage(this.value)" onkeypress="if(event.key === 'Enter') updatePerPage(this.value)">
            <span class="badge"><?= number_format($paginacion['total']) ?> documentos</span>
        </div>
    </div>

    <script>
        function updatePerPage(val) {
            val = parseInt(val);
            if (val < 1) val = 1;
            if (val > 200) val = 200;

            // Construct new URL properly maintaining existing params
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('per_page', val);
            urlParams.set('page', 1); // Reset to page 1
            window.location.search = urlParams.toString();
        }
    </script>

    <?php if (empty($documentos)): ?>
        <div class="alert alert-info">
            No se encontraron documentos con los criterios de búsqueda especificados.
        </div>
    <?php else: ?>
        <?php
        // Define Row Class Callback
        $rowClassCallback = function ($doc) {
            $estado = $doc['estado_documento'] ?? 'DISPONIBLE';
            switch ($estado) {
                case 'FALTA':
                    return 'row-falta';
                case 'PRESTADO':
                    return 'row-prestado';
                case 'ANULADO':
                    return 'row-anulado';
                case 'NO UTILIZADO':
                    return 'row-no-utilizado';
                case 'DISPONIBLE':
                    return 'row-disponible';
                default:
                    return '';
            }
        };

        // Define Batch Checkbox Formatter
        $batchCheckboxFormatter = function ($doc) {
            $estado = $doc['estado_documento'] ?? 'DISPONIBLE';
            $contenedor = !empty($doc['contenedor_numero']) ? htmlspecialchars($doc['tipo_contenedor'] . ' #' . $doc['contenedor_numero'] . (!empty($doc['contenedor_codigo_abc']) ? '.' . $doc['contenedor_codigo_abc'] : '')) : 'Sin asignar';
            $ubicacion = htmlspecialchars($doc['ubicacion_nombre'] ?? 'N/A');
            $gestion = htmlspecialchars($doc['gestion'] ?? '');
            $comprobante = htmlspecialchars($doc['nro_comprobante'] ?? '');

            return sprintf(
                '<input type="checkbox" class="doc-checkbox" value="%s" data-gestion="%s" data-comprobante="%s" data-estado="%s" data-contenedor="%s" data-ubicacion="%s">',
                $doc['id'],
                $gestion,
                $comprobante,
                $estado,
                $contenedor,
                $ubicacion
            );
        };

        // Define Columns
        $columns = [
            [
                'label' => 'Gestión',
                'field' => 'gestion',
                'sortable' => true
            ],
            [
                'label' => 'Tipo',
                'field' => 'tipo_documento',
                'sortable' => true,
                'formatter' => function ($doc) {
                    $tipo = $doc['tipo_documento'] ?? '';
                    $abrev = 'N/A';
                    switch ($tipo) {
                        case 'REGISTRO_DIARIO':
                            $abrev = 'DIA';
                            break;
                        case 'REGISTRO_INGRESO':
                            $abrev = 'ING';
                            break;
                        case 'REGISTRO_CEPS':
                            $abrev = 'CEPS';
                            break;
                        case 'PREVENTIVOS':
                            $abrev = 'PRE';
                            break;
                        case 'ASIENTOS_MANUALES':
                            $abrev = 'MAN';
                            break;
                        case 'DIARIOS_APERTURA':
                            $abrev = 'DAP';
                            break;
                        case 'REGISTRO_TRASPASO':
                            $abrev = 'TRA';
                            break;
                        case 'HOJA_RUTA_DIARIOS':
                            $abrev = 'HRD';
                            break;
                        default:
                            $abrev = substr($tipo, 0, 3);
                    }
                    return '<span class="badge" style="background-color: #6c757d;" title="' . htmlspecialchars($tipo) . '">' . htmlspecialchars($abrev) . '</span>';
                }
            ],
            [
                'label' => 'Nro Comprobante',
                'field' => 'nro_comprobante',
                'sortable' => true
            ],
            [
                'label' => 'Código ABC',
                'field' => 'codigo_abc',
                'sortable' => true
            ],
            [
                'label' => 'Contenedor',
                'field' => 'contenedor',
                'sortable' => true,
                'formatter' => function ($doc) {
                    if (!empty($doc['contenedor_numero'])) {
                        $abc = !empty($doc['contenedor_codigo_abc']) ? '.' . htmlspecialchars($doc['contenedor_codigo_abc']) : '';
                        return '<span class="badge badge-info">' . htmlspecialchars($doc['tipo_contenedor']) . ' #' . htmlspecialchars($doc['contenedor_numero']) . $abc . '</span>';
                    }
                    return 'Sin asignar';
                }
            ],
            [
                'label' => 'Ubicación',
                'field' => 'ubicacion_nombre',
                'sortable' => true, // Assuming the view handles sort by 'ubicacion' mapping to 'ubicacion_nombre' or join
                'sort_field_override' => 'ubicacion' // In case we need to specify the sort param name distinct from field
            ],
            [
                'label' => 'Estado',
                'field' => 'estado_documento',
                'sortable' => true,
                'formatter' => function ($doc) {
                    $estado = $doc['estado_documento'] ?? 'DISPONIBLE';
                    $badgeClass = '';
                    $icon = '';
                    switch ($estado) {
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
                    return '<span class="badge ' . $badgeClass . '">' . $icon . ' ' . htmlspecialchars($estado) . '</span>';
                }
            ],
            [
                'label' => 'Acciones',
                'formatter' => function ($doc, $append) {
                    $estado = $doc['estado_documento'] ?? 'DISPONIBLE';
                    $html = '<div style="display:flex; gap:5px;">';
                    $html .= '<a href="/catalogacion/ver/' . $doc['id'] . $append . '" class="btn btn-sm btn-primary">Ver</a>';
                    $html .= '<a href="/catalogacion/editar/' . $doc['id'] . $append . '" class="btn btn-sm btn-secondary">✏️ Editar</a>';
                    $html .= '<a href="/catalogacion/eliminar/' . $doc['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'¿Estás seguro de eliminar este documento?\')" title="Eliminar">🗑️</a>';

                    if ($estado === 'PRESTADO' && !empty($doc['prestamo_id'])) {
                        $html .= '<a href="/prestamos/ver/' . $doc['prestamo_id'] . '?from_doc=' . $doc['id'] . '" class="btn btn-sm" style="background: #17a2b8; color: white;" title="Ver préstamo activo">📋</a>';
                    }
                    $html .= '</div>';
                    return $html;
                }
            ]
        ];

        // Prepare data for the partial
        $rows = $documentos;

        // Include the reusable table component
        include __DIR__ . '/../partials/table.php';
        ?>
    <?php endif; ?>


</div>

<style>
    .search-form {
        padding: 20px;
    }

    .form-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }

    .form-group {
        flex: 1;
        min-width: 200px;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
        align-items: center;
    }

    .form-actions .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 16px;
        font-size: 14px;
        height: 38px;
        /* Force consistent height */
        box-sizing: border-box;
        text-decoration: none;
        line-height: normal;
        border: 1px solid transparent;
        /* Ensure border width is accounted for */
        cursor: pointer;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        padding: 20px;
    }

    .page-info {
        padding: 8px 16px;
    }

    .badge {
        background: #1B3C84;
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        display: inline-block;
    }

    .badge-info {
        background: #17a2b8;
    }

    .badge-disponible {
        background: #28a745;
    }

    /* Verde */
    .badge-falta {
        background: #dc3545;
    }

    /* Rojo */
    .badge-prestado {
        background: #007bff;
    }

    /* Azul */
    .badge-no-utilizado {
        background: #ffc107;
        color: #333;
    }

    /* Amarillo */
    .badge-anulado {
        background: #6f42c1;
    }

    /* Morado */
    .btn-sm {
        padding: 4px 12px;
        font-size: 13px;
    }

    .mt-20 {
        margin-top: 20px;
    }

    .alert-info {
        background: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
        padding: 15px;
        border-radius: 5px;
        margin: 20px;
    }

    /* Colores de filas según estado */
    .row-disponible {
        background-color: #f0fff0;
    }

    /* Verde muy claro */
    .row-falta {
        background-color: #ffe6e6;
        font-weight: 500;
    }

    /* Rojo claro */
    .row-prestado {
        background-color: #cce5ff;
    }

    /* Azul claro */
    .row-no-utilizado {
        background-color: #fff9e6;
    }

    /* Amarillo claro */
    .row-anulado {
        background-color: #f3e6ff;
    }

    /* Morado claro */

    .row-falta td {
        color: #721c24;
    }

    .header-actions {
        display: flex;
        gap: 10px;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .doc-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    /* Pagination Styles */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        /* Slightly larger gap between groups */
        padding: 25px 0;
        flex-wrap: wrap;
    }

    .pagination-numbers {
        display: flex;
        gap: 2px;
        /* Small gap between numbers */
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
        /* Slight radius */
        font-size: 14px;
        transition: all 0.2s;
        line-height: normal;
        /* Fix vertical alignment */
    }

    .page-num {
        border-radius: 2px;
        /* Squared numbers inside the group */
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
    function abrirModalEdicionLote() {
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

        // Reset inputs
        document.getElementById('estado_lote').value = '';
        if (typeof window.clearLoteContenedorSelection === 'function') {
            window.clearLoteContenedorSelection();
        }

        // Show modal
        document.getElementById('modalAsignacion').style.display = 'block';
    }

    // --- AJAX Container Search for Batch Edit ---
    // --- AJAX Container Search for Batch Edit ---
    // Global functions for onclick events
    window.loteSearchLogic = {
        debounceTimer: null
    };

    window.selectLoteContenedor = function (item) {
        const hidden = document.getElementById('lote_contenedor_id');
        const info = document.getElementById('lote_contenedor_info');
        const wrapper = document.getElementById('lote_search_wrapper');
        const search = document.getElementById('lote_contenedor_search');
        const results = document.getElementById('lote_contenedor_results');

        hidden.value = item.id;
        let text = `📦 [${item.tipo_documento_codigo || '?'}] ${item.gestion} ${item.tipo_contenedor} #${item.numero}`;
        if (item.codigo_abc) {
            text += `.${item.codigo_abc}`;
        }

        info.innerHTML = `
        <div style="background: #e6fffa; border: 1px solid #38b2ac; padding: 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
            <span style="color: #234e52; font-weight: bold;">${text}</span>
            <button type="button" class="btn btn-sm btn-danger" onclick="clearLoteContenedorSelection()">❌ Quitar</button>
        </div>
    `;
        info.style.display = 'block';
        wrapper.style.display = 'none';
        search.value = '';
        results.style.display = 'none';
    };

    window.clearLoteContenedorSelection = function () {
        document.getElementById('lote_contenedor_id').value = '';
        const info = document.getElementById('lote_contenedor_info');
        info.innerHTML = '';
        info.style.display = 'none';
        document.getElementById('lote_search_wrapper').style.display = 'flex';
        document.getElementById('lote_contenedor_search').focus();
    };

    document.addEventListener('DOMContentLoaded', function () {
        const loteSearchInput = document.getElementById('lote_contenedor_search');
        const loteResultsDiv = document.getElementById('lote_contenedor_results');

        if (loteSearchInput) {
            loteSearchInput.addEventListener('input', function () {
                const query = this.value.trim();
                clearTimeout(window.loteSearchLogic.debounceTimer);

                if (query.length < 1) {
                    loteResultsDiv.style.display = 'none';
                    return;
                }

                window.loteSearchLogic.debounceTimer = setTimeout(() => {
                    fetch(`/contenedores/api-buscar?q=${encodeURIComponent(query)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            loteResultsDiv.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(item => {
                                    const div = document.createElement('div');
                                    div.className = 'autocomplete-item';
                                    div.style.padding = '8px 12px';
                                    div.style.cursor = 'pointer';
                                    div.style.borderBottom = '1px solid #f0f0f0';
                                    div.style.color = '#333'; // Ensure visibility
                                    div.onmouseover = () => div.style.backgroundColor = '#f5f7fa';
                                    div.onmouseout = () => div.style.backgroundColor = 'white';

                                    div.textContent = `[${item.tipo_documento_codigo || '?'}] ${item.gestion} ${item.tipo_contenedor} #${item.numero}`;
                                    if (item.codigo_abc) {
                                        div.textContent += `.${item.codigo_abc}`;
                                    }

                                    // Call global function
                                    div.onclick = () => window.selectLoteContenedor(item);
                                    loteResultsDiv.appendChild(div);
                                });
                                loteResultsDiv.style.display = 'block';
                            } else {
                                loteResultsDiv.innerHTML = '<div style="padding:8px; color:#999;">No hay resultados</div>';
                                loteResultsDiv.style.display = 'block';
                            }
                        });
                }, 300);
            });

            // Hide on outside click
            document.addEventListener('click', function (e) {
                if (e.target !== loteSearchInput && !loteResultsDiv.contains(e.target)) {
                    loteResultsDiv.style.display = 'none';
                }
            });
        }
    });

    function cerrarModalAsignacion() {
        document.getElementById('modalAsignacion').style.display = 'none';
    }
</script>

<?php if ($modoLotes): ?>
<!-- Modal Edición Lote -->
<div id="modalAsignacion" class="modal"
    style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content"
        style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 50%; max-width: 500px; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #1B3C84;">Editar Lote de Documentos</h3>
            <span onclick="cerrarModalAsignacion()"
                style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        </div>

        <form action="/catalogacion/lote/actualizar" method="POST">
            <input type="hidden" name="ids" id="ids_lote">
            <input type="hidden" name="page" value="<?= $paginacion['page'] ?? 1 ?>">
            <input type="hidden" name="search" value="<?= htmlspecialchars($filtros['search'] ?? '') ?>">
            <input type="hidden" name="gestion" value="<?= htmlspecialchars($filtros['gestion'] ?? '') ?>">
            <input type="hidden" name="ubicacion_id" value="<?= htmlspecialchars($filtros['ubicacion_id'] ?? '') ?>">
            <input type="hidden" name="filter_estado_documento"
                value="<?= htmlspecialchars($filtros['estado_documento'] ?? '') ?>">
            <input type="hidden" name="tipo_documento"
                value="<?= htmlspecialchars($filtros['tipo_documento'] ?? '') ?>">

            <p style="margin-bottom: 15px;">Editando <strong><span id="count_seleccionados">0</span></strong> documentos
                seleccionados.</p>
            <div class="alert alert-info" style="font-size: 0.9em; padding: 10px; margin-bottom: 15px;">
                Nota: Solo se actualizarán los campos que seleccione. Deje en "-- Seleccione --" para mantener el valor
                actual.
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="lote_contenedor_search">Contenedor Físico (Buscar por Número o Gestión):</label>
                <div class="search-container-wrapper" id="lote_search_wrapper"
                    style="display:flex; gap:5px; position: relative;">
                    <input type="text" id="lote_contenedor_search" class="form-control"
                        placeholder="Escriba para buscar (ej. 2023, AMARRO...)" autocomplete="off" style="width: 100%;">

                    <button type="button" class="btn btn-success"
                        onclick="abrirModalCrearContenedor('lote_contenedor_search')"
                        title="Crear Nuevo Contenedor">➕</button>

                    <div id="lote_contenedor_results" class="autocomplete-results"
                        style="display:none; width: 100%; position: absolute; top: 100%; left: 0; background: white; border: 1px solid #ddd; z-index: 2000; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    </div>
                </div>

                <input type="hidden" id="lote_contenedor_id" name="contenedor_id">

                <div id="lote_contenedor_info" style="margin-top: 5px; display:none;"></div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="estado_lote">Estado:</label>
                <select name="estado_documento" id="estado_lote" class="form-control"
                    style="width: 100%; padding: 8px;">
                    <option value="">-- No cambiar --</option>
                    <option value="DISPONIBLE">🟢 Disponible</option>
                    <option value="FALTA">🔴 Falta</option>
                    <option value="PRESTADO">🔵 Prestado</option>
                    <option value="NO UTILIZADO">🟡 No Utilizado</option>
                    <option value="ANULADO">🟣 Anulado</option>
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
// Include Modal Partial
require __DIR__ . '/../layouts/modal_crear_contenedor.php';

$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>