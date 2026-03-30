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
        <div class="form-row"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="search">Búsqueda General</label>
                <input type="text" id="search" name="search" class="form-control"
                    value="<?= htmlspecialchars($filtros['search'] ?? '') ?>"
                    placeholder="Nro comprobante, código ABC...">
            </div>

            <div class="form-group">
                <label for="gestion">Gestión</label>
                <input type="number" id="gestion" name="gestion" class="form-control"
                    value="<?= htmlspecialchars($filtros['gestion'] ?? '') ?>" min="2000" max="<?= date('Y') + 1 ?>">
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

            <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <a href="/prestamos/nuevo" class="btn btn-secondary">🔄 Limpiar</a>
            </div>
        </div>
    </form>

    <!-- CSS Standarizado -->
    <style>
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            align-items: center;
        }

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

        .btn-primary.active {
            background: #1B3C84;
            border-color: #1B3C84;
            color: white;
            cursor: default;
            z-index: 1;
        }

        .page-num {
            border-radius: 2px;
            padding: 6px 12px;
        }
    </style>

    <!-- Header Resultados con Input Cantidad -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #eee; background: #f8f9fa;">
        <h3 style="margin: 0; font-size: 1.1em;">Resultados de Búsqueda</h3>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 0.9em; color: #666;">Cantidad de Filas:</span>
            <input type="number" id="perPageInput" value="<?= $paginacion['per_page'] ?? 20 ?>" min="1" max="200"
                style="width: 70px; padding: 5px; border-radius: 4px; border: 1px solid #ccc; font-size: 0.9em;"
                onchange="updatePerPage(this.value)" onkeypress="if(event.key === 'Enter') updatePerPage(this.value)">
            <span class="badge badge-info"><?= number_format($paginacion['total'] ?? 0) ?> documentos</span>
        </div>
    </div>

    <!-- Script para actualizar per_page -->
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

    <!-- Tabla de documentos disponibles -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">
                        <input type="checkbox" id="checkAll" onclick="toggleTodos(this)" title="Seleccionar todos">
                    </th>
                    <?php
                    // Helper ordenamiento
                    $currentSort = $_GET['sort'] ?? '';
                    $currentOrder = $_GET['order'] ?? '';

                    $makeSortLink = function ($col, $label) use ($filtros, $currentSort, $currentOrder) {
                        $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
                        $icon = '';
                        if ($currentSort === $col) {
                            $icon = $currentOrder === 'ASC' ? ' ▲' : ' ▼';
                        } else {
                            $icon = ' <span style="opacity:0.3; font-size: 0.8em">⇅</span>';
                        }
                        $params = array_merge($filtros, ['sort' => $col, 'order' => $newOrder, 'page' => 1]);
                        return '<a href="?' . http_build_query($params) . '" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">' . $label . $icon . '</a>';
                    };
                    ?>
                    <th><?= $makeSortLink('tipo_documento', 'Tipo Documento') ?></th>
                    <th><?= $makeSortLink('gestion', 'Gestión') ?></th>
                    <th><?= $makeSortLink('nro_comprobante', 'Nro Comprobante') ?></th>
                    <th>Contenedor</th>
                    <th><?= $makeSortLink('ubicacion', 'Ubicación') ?></th>
                    <th><?= $makeSortLink('estado', 'Estado') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documentos)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay documentos disponibles</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documentos as $doc): ?>
                        <?php
                        $estado = $doc['estado_documento'] ?? 'DISPONIBLE';
                        $esPrestable = in_array($estado, ['DISPONIBLE', 'NO UTILIZADO', 'ANULADO']);

                        // Map estado to CSS class
                        $badgeClass = 'badge';
                        switch ($estado) {
                            case 'DISPONIBLE':
                                $badgeClass .= ' badge-disponible';
                                break;
                            case 'NO UTILIZADO':
                                $badgeClass .= ' badge-no-utilizado';
                                break;
                            case 'ANULADO':
                                $badgeClass .= ' badge-anulado';
                                break;
                            case 'FALTA':
                                $badgeClass .= ' badge-falta';
                                break;
                            case 'PRESTADO':
                                $badgeClass .= ' badge-prestado';
                                break;
                            default:
                                $badgeClass .= ' badge-secondary';
                        }
                        ?>
                        <tr style="<?= !$esPrestable ? 'background: #fcfcfc;' : '' ?>">
                            <td>
                                <input type="checkbox" class="doc-checkbox" value="<?= $doc['id'] ?>"
                                    data-tipo="<?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?>"
                                    data-gestion="<?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?>"
                                    data-comprobante="<?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?>"
                                    data-contenedor="<?= !empty($doc['contenedor_numero']) ? htmlspecialchars($doc['tipo_contenedor'] . ' #' . $doc['contenedor_numero'] . (!empty($doc['contenedor_codigo_abc']) ? '.' . $doc['contenedor_codigo_abc'] : '')) : 'Sin asignar' ?>"
                                    data-ubicacion="<?= htmlspecialchars($doc['ubicacion_fisica'] ?? 'Sin ubicación') ?>">
                            </td>
                            <td><?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?><?= !empty($doc['codigo_abc']) ? '-' . htmlspecialchars($doc['codigo_abc']) : '' ?></td>
                            <td>
                                <?php if (!empty($doc['tipo_contenedor'])): ?>
                                    <?= htmlspecialchars($doc['tipo_contenedor']) ?>
                                    #<?= htmlspecialchars($doc['contenedor_numero']) ?><?= !empty($doc['contenedor_codigo_abc']) ? '.' . htmlspecialchars($doc['contenedor_codigo_abc']) : '' ?>
                                <?php else: ?>
                                    <span style="color: #999;">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?= htmlspecialchars($doc['ubicacion_fisica'] ?? 'Sin ubicación') ?></small>
                            </td>
                            <td>
                                <span class="<?= $badgeClass ?>">
                                    <?= ucfirst(strtolower(str_replace('_', ' ', $estado))) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación Google Style -->
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

            <!-- Primera -->
            <?php if ($current > 1): ?>
                <a href="?<?= http_build_query(array_merge($params, ['page' => 1])) ?>" class="btn btn-secondary">⇤ Primero</a>
            <?php endif; ?>

            <!-- Anterior -->
            <?php if ($current > 1): ?>
                <a href="?<?= http_build_query(array_merge($params, ['page' => $current - 1])) ?>" class="btn btn-warning">←
                    Anterior</a>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>← Anterior</button>
            <?php endif; ?>

            <!-- Números -->
            <div class="pagination-numbers">
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => $i])) ?>"
                        class="btn <?= $i == $current ? 'btn-primary active' : 'btn-light' ?> page-num">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>

            <!-- Siguiente -->
            <?php if ($current < $total_p): ?>
                <a href="?<?= http_build_query(array_merge($params, ['page' => $current + 1])) ?>"
                    class="btn btn-warning">Siguiente →</a>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>Siguiente →</button>
            <?php endif; ?>

            <!-- Última -->
            <?php if ($current < $total_p): ?>
                <a href="?<?= http_build_query(array_merge($params, ['page' => $total_p])) ?>" class="btn btn-secondary">Último
                    ⇥</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div id="documentos-seleccionados"
        style="display: none; padding: 20px; background: #f0f9ff; border-top: 2px solid #3182CE;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="color: #1B3C84; margin: 0;">📋 Documentos Seleccionados (<span id="selected-count">0</span>)</h3>

            <div style="display: flex; gap: 10px; align-items: center;">
                <label
                    style="display: flex; align-items: center; cursor: pointer; background: #EDF2F7; padding: 5px 10px; border-radius: 5px; border: 1px solid #CBD5E0;">
                    <input type="checkbox" id="check-historico" onchange="toggleHistorico()" style="margin-right: 8px;">
                    <span style="font-size: 0.9em; font-weight: 500;">📅 Registrar como Histórico / Pasado</span>
                </label>

                <button type="button" class="btn btn-primary" onclick="procesarPrestamo()" id="btn-procesar">
                    📤 Procesar Préstamo (<span id="count">0</span> docs)
                </button>
            </div>
        </div>
        <div id="lista-documentos" style="display: grid; gap: 10px; margin-bottom: 15px;"></div>

        <div class="form-row"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #cbd5e0;">
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
                <label for="fecha_prestamo">Fecha de Préstamo <span class="required" id="req-prestamo">*</span></label>
                <input type="date" id="fecha_prestamo" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label for="fecha_devolucion">Fecha de Devolución <span class="required"
                        id="req-devolucion">*</span></label>
                <input type="date" id="fecha_devolucion" class="form-control"
                    value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                <small class="text-muted" id="help-devolucion" style="display:none;">Opcional para históricos</small>
            </div>

            <div class="form-group" id="group-estado" style="display: none;">
                <label for="estado_inicial">Estado del Préstamo <span
                        style="color: #666; font-size: 0.9em;">(histórico)</span></label>
                <select id="estado_inicial" class="form-control" style="background-color: #f7fafc;">
                    <option value="Prestado">Prestado</option>
                    <option value="Devuelto">Devuelto</option>
                </select>
                <small class="text-muted">
                    <strong>Prestado:</strong> El documento está actualmente prestado<br>
                    <strong>Devuelto:</strong> El préstamo ya fue cerrado/devuelto
                </small>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="observaciones_prestamo">Observaciones</label>
                <input type="text" id="observaciones_prestamo" class="form-control"
                    placeholder="Motivo del préstamo...">
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
    document.addEventListener('DOMContentLoaded', function () {
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

        // Set default date (14 days from now)
        updateDefaultReturnDate();

        // Init state
        toggleHistorico();
    });

    function updateDefaultReturnDate() {
        const fechaDev = document.getElementById('fecha_devolucion');
        const fechaPrestamo = document.getElementById('fecha_prestamo');

        if (fechaPrestamo.value) {
            const baseDate = new Date(fechaPrestamo.value);
            baseDate.setDate(baseDate.getDate() + 14); // 2 Weeks default

            // Format YYYY-MM-DD
            const year = baseDate.getFullYear();
            const month = String(baseDate.getMonth() + 1).padStart(2, '0');
            const day = String(baseDate.getDate()).padStart(2, '0');

            // Use user supplied value if exists, otherwise set default
            // Actually, logic is: if user changes prestamo date, should we auto-update return date?
            // Maybe only if return date is empty or was default?
            // For simplicity, let's just set it on load. User can change it.
            // But if user changes 'Fecha Prestamo', we might want to suggest new return date.
            // Let's attach listener to fecha_prestamo
        }
    }

    // Update return date suggestion when loan date changes
    document.getElementById('fecha_prestamo').addEventListener('change', function () {
        const isHistorico = document.getElementById('check-historico').checked;
        if (!isHistorico) {
            const prestamoVal = this.value;
            if (prestamoVal) {
                const baseDate = new Date(prestamoVal + 'T12:00:00'); // T12 to avoid timezone issues
                baseDate.setDate(baseDate.getDate() + 14);
                const isoDate = baseDate.toISOString().split('T')[0];
                document.getElementById('fecha_devolucion').value = isoDate;
            }
        }
    });


    function toggleHistorico() {
        const isHistorico = document.getElementById('check-historico').checked;
        const groupEstado = document.getElementById('group-estado');
        const reqDevolucion = document.getElementById('req-devolucion');
        const helpDevolucion = document.getElementById('help-devolucion');
        const fechaDev = document.getElementById('fecha_devolucion');

        if (isHistorico) {
            groupEstado.style.display = 'block';
            reqDevolucion.style.display = 'none';
            helpDevolucion.style.display = 'block';
            fechaDev.removeAttribute('required');
            // Remove min date restriction for historical
            fechaDev.removeAttribute('min');
        } else {
            groupEstado.style.display = 'none';
            reqDevolucion.style.display = 'inline';
            helpDevolucion.style.display = 'none';
            fechaDev.setAttribute('required', 'true');
            fechaDev.setAttribute('min', new Date().toISOString().split('T')[0]);

            // Reset state to Prestado just in case
            document.getElementById('estado_inicial').value = 'Prestado';

            // Reset dates logic
            const prestamoInput = document.getElementById('fecha_prestamo');
            // If date is in past, maybe warn? No, just let it be.
        }
    }

    function toggleTodos(checkbox) {
        const checkboxes = document.querySelectorAll('.doc-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
        actualizarSeleccion();
    }

    // Escuchar cambios en checkboxes
    document.addEventListener('change', function (e) {
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
                contenedor: checkbox.dataset.contenedor,
                ubicacion: checkbox.dataset.ubicacion
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
                    contenedor: doc.contenedor,
                    ubicacion: doc.ubicacion
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
                <div style="font-size: 0.85em; color: #4a5568;">📍 ${doc.ubicacion}</div>
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
        const fechaPrestamo = document.getElementById('fecha_prestamo').value;
        const fechaDevolucion = document.getElementById('fecha_devolucion').value;
        const isHistorico = document.getElementById('check-historico').checked;
        const estado = document.getElementById('estado_inicial').value;

        if (!unidad) {
            alert('⚠️ Debes completar Unidad/Área indicando el solicitante');
            return;
        }

        if (!fechaPrestamo) {
            alert('⚠️ La Fecha de Préstamo es obligatoria');
            return;
        }

        if (!isHistorico && !fechaDevolucion) {
            alert('⚠️ La Fecha de Devolución es obligatoria en préstamos actuales');
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
        <input type="hidden" name="fecha_prestamo" value="${fechaPrestamo}">
        <input type="hidden" name="fecha_devolucion" value="${fechaDevolucion}">
        <input type="hidden" name="observaciones" value="${document.getElementById('observaciones_prestamo').value}">
        <input type="hidden" name="documentos" value='${JSON.stringify(documentosSeleccionados.map(d => d.id))}'>
        <input type="hidden" name="estado_inicial" value="${isHistorico ? estado : 'En Proceso'}">
        <input type="hidden" name="es_historico" value="${isHistorico ? '1' : '0'}">
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