<?php
ob_start();
$pageTitle = 'Reportes - Préstamos y Documentos';
?>

<div class="reportes-header">
    <h1>📊 Reportes del Sistema</h1>
    <div class="stats-cards">
        <div class="stat-card stat-blue">
            <div class="stat-number"><?= $stats['total_prestados'] ?></div>
            <div class="stat-label">Documentos Prestados</div>
        </div>
        <div class="stat-card stat-red">
            <div class="stat-number"><?= $stats['prestamos_vencidos'] ?></div>
            <div class="stat-label">Préstamos Vencidos</div>
        </div>
        <div class="stat-card stat-yellow">
            <div class="stat-number"><?= $stats['total_faltantes'] ?></div>
            <div class="stat-label">Documentos Faltantes</div>
        </div>
        <div class="stat-card stat-gray">
            <div class="stat-number"><?= $stats['total_anulados'] ?></div>
            <div class="stat-label">Documentos Anulados</div>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>📤 Préstamos Activos</h2>
        <div class="header-actions" style="display: flex; align-items: center; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 5px; margin-right: 15px;">
                <label for="per_page_active_input" style="margin:0; color: #4a5568; font-size: 0.95em;">Filas:</label>
                <input type="number" id="per_page_active_input" value="<?= $paginacionActive['per_page'] ?>" min="1"
                    max="500" class="form-control"
                    style="width: 70px; height: 30px; padding: 2px 8px; text-align: center;"
                    onchange="updatePerPageActive(this.value)">
            </div>
            <button onclick="imprimirSeccion('prestamos')" class="btn btn-secondary">📄 PDF</button>
            <button onclick="exportarExcel('prestamos')" class="btn btn-success">📊 Excel</button>
        </div>
    </div>

    <div class="table-responsive" id="tabla-prestamos">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <?php
                    // Helper para links de ordenamiento ACTIVO
                    $makeSortLinkActive = function ($col, $label) use ($filtros) {
                        $currentSort = $filtros['sort_active'] ?? 'fecha_devolucion_esperada';
                        $currentOrder = $filtros['order_active'] ?? 'ASC';
                        $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
                        $icon = '';
                        if ($currentSort === $col) {
                            $icon = $currentOrder === 'ASC' ? ' ▲' : ' ▼';
                        }

                        // Rebuild params maintaining other params (like tab 2 filters)
                        $params = $_GET;
                        $params['sort_active'] = $col;
                        $params['order_active'] = $newOrder;
                        $params['page_active'] = 1; // Reset page
                    
                        return '<a href="?' . http_build_query($params) . '" style="color: inherit; text-decoration: none;">' . $label . $icon . '</a>';
                    };
                    ?>
                    <th><?= $makeSortLinkActive('usuario', 'Usuario') ?></th>
                    <th><?= $makeSortLinkActive('area', 'Área/Rol') ?></th>
                    <th><?= $makeSortLinkActive('documento', 'Documento') ?></th>
                    <th><?= $makeSortLinkActive('gestion', 'Gestión') ?></th>
                    <th><?= $makeSortLinkActive('contenedor', 'Contenedor') ?></th>
                    <th><?= $makeSortLinkActive('fecha_prestamo', 'Fecha Préstamo') ?></th>
                    <th><?= $makeSortLinkActive('fecha_devolucion', 'Devolución Est.') ?></th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prestamosActivos)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No hay préstamos activos</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prestamosActivos as $pres):
                        $vencido = $pres['dias_restantes'] < 0;
                        $porVencer = $pres['dias_restantes'] >= 0 && $pres['dias_restantes'] <= 3;
                        ?>
                        <tr class="<?= $vencido ? 'row-vencido' : ($porVencer ? 'row-por-vencer' : '') ?>">
                            <td>
                                <strong><?= htmlspecialchars($pres['usuario_nombre']) ?></strong><br>
                                <small style="color: #666;">(<?= htmlspecialchars($pres['username']) ?>)</small>
                            </td>
                            <td>
                                <?php
                                // Extraer área del username o usar rol
                                $parts = explode('_', $pres['username']);
                                $area = count($parts) > 1 ? strtoupper(end($parts)) : 'N/A';
                                ?>
                                <span class="badge badge-info"><?= htmlspecialchars($area) ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($pres['tipo_documento'] ?? 'N/A') ?></strong><br>
                                <small>Nro: <?= htmlspecialchars($pres['nro_comprobante'] ?? 'N/A') ?></small>
                            </td>
                            <td><?= htmlspecialchars($pres['gestion'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($pres['tipo_contenedor']): ?>
                                    <?= htmlspecialchars($pres['tipo_contenedor']) ?>
                                    #<?= htmlspecialchars($pres['contenedor_numero']) ?>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($pres['fecha_prestamo'])) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($pres['fecha_devolucion_esperada'])) ?>
                                <?php if ($vencido): ?>
                                    <br><span class="badge badge-falta">⚠️ Vencido (<?= abs($pres['dias_restantes']) ?> días)</span>
                                <?php elseif ($porVencer): ?>
                                    <br><span class="badge badge-prestado">⏰ <?= $pres['dias_restantes'] ?> día(s)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/prestamos/ver/<?= $pres['id'] ?>" class="btn btn-sm btn-primary">Ver</a>
                                <a href="/prestamos/devolver/<?= $pres['id'] ?>" class="btn btn-sm btn-success"
                                    onclick="return confirm('¿Confirmar devolución?')">✓ Devolver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación Activos -->
    <?php if ($paginacionActive['total_pages'] > 1): ?>
        <div class="pagination-container" style="padding: 20px; display: flex; justify-content: center;">
            <div class="pagination">
                <?php
                $current = $paginacionActive['current'];
                $total = $paginacionActive['total_pages'];
                $max_visible = 10;
                $start = max(1, $current - floor($max_visible / 2));
                $end = min($total, $start + $max_visible - 1);
                if ($end - $start + 1 < $max_visible)
                    $start = max(1, $end - $max_visible + 1);

                // Rebuild params for pag links
                $params = $_GET;
                ?>

                <?php if ($current > 1): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page_active' => 1])) ?>"
                        class="btn btn-secondary">⇤</a>
                    <a href="?<?= http_build_query(array_merge($params, ['page_active' => $current - 1])) ?>"
                        class="btn btn-secondary">←</a>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page_active' => $i])) ?>"
                        class="btn <?= $i == $current ? 'btn-primary' : 'btn-light' ?> page-num">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($current < $total): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page_active' => $current + 1])) ?>"
                        class="btn btn-secondary">→</a>
                    <a href="?<?= http_build_query(array_merge($params, ['page_active' => $total])) ?>"
                        class="btn btn-secondary">⇥</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function updatePerPageActive(val) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('per_page_active', val);
        urlParams.set('page_active', 1);
        window.location.search = urlParams.toString();
    }
</script>

<!-- SECCIÓN 2: Documentos No Disponibles -->
<!-- SECCIÓN 2: Documentos No Disponibles -->
<div class="card" style="margin-top: 30px;">
    <div class="card-header">
        <h2>⚠️ Documentos No Disponibles para Préstamo</h2>
        <div class="header-actions">
            <a href="/reportes/auditorias" class="btn btn-secondary">
                📜 Historial de Auditoría
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                🖨️ Imprimir Reporte
            </button>
            <button onclick="imprimirSeccion('no-disponibles')" class="btn btn-secondary">📄 PDF</button>
            <button onclick="exportarExcel('no-disponibles')" class="btn btn-success">📊 Excel</button>
        </div>
    </div>

    <!-- Filtros Server-Side unificados -->
    <form method="GET" action="/reportes" id="filtrosReportes" class="filtros-rapidos"
        style="padding: 15px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($filtros['sort']) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($filtros['order']) ?>">

        <!-- Left: Checkboxes -->
        <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <strong>Filtrar por Estado:</strong>
            <label style="cursor: pointer; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 0;">
                <input type="checkbox" name="states[]" value="PRESTADO" <?= in_array('PRESTADO', $filtros['states']) ? 'checked' : '' ?> onchange="this.form.submit()">
                <span class="badge badge-prestado">Prestados</span>
            </label>
            <label style="cursor: pointer; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 0;">
                <input type="checkbox" name="states[]" value="FALTA" <?= in_array('FALTA', $filtros['states']) ? 'checked' : '' ?> onchange="this.form.submit()">
                <span class="badge badge-falta">Faltantes</span>
            </label>
            <label style="cursor: pointer; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 0;">
                <input type="checkbox" name="states[]" value="NO UTILIZADO" <?= in_array('NO UTILIZADO', $filtros['states']) ? 'checked' : '' ?> onchange="this.form.submit()">
                <span class="badge badge-warning">No Utilizados</span>
            </label>
            <label style="cursor: pointer; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 0;">
                <input type="checkbox" name="states[]" value="ANULADO" <?= in_array('ANULADO', $filtros['states']) ? 'checked' : '' ?> onchange="this.form.submit()">
                <span class="badge badge-anulado">Anulados</span>
            </label>
        </div>

        <!-- Right: Pagination Controls -->
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="color: #4a5568; font-weight: 600; font-size: 0.95em;">
                Total registros: <?= $paginacion['total'] ?>
            </span>
            <span style="color: #cbd5e0;">|</span>
            <div style="display: flex; align-items: center; gap: 5px;">
                <label for="per_page_input" style="margin:0; color: #4a5568; font-size: 0.95em;">Filas:</label>
                <input type="number" id="per_page_input" name="per_page" value="<?= $filtros['per_page'] ?>" min="1"
                    max="500" class="form-control"
                    style="width: 70px; height: 30px; padding: 2px 8px; text-align: center;"
                    onchange="this.form.submit()">
            </div>
        </div>
    </form>

    <div class="table-responsive" id="tabla-no-disponibles">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <?php
                    // Helper para links de ordenamiento
                    $makeSortLink = function ($col, $label) use ($filtros) {
                        $currentSort = $filtros['sort'];
                        $currentOrder = $filtros['order'];
                        $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
                        $icon = '';
                        if ($currentSort === $col) {
                            $icon = $currentOrder === 'ASC' ? ' ▲' : ' ▼';
                        }

                        // Rebuild params
                        $params = $_GET;
                        $params['sort'] = $col;
                        $params['order'] = $newOrder;
                        unset($params['page']); // Reset page
                    
                        return '<a href="?' . http_build_query($params) . '" style="color: inherit; text-decoration: none;">' . $label . $icon . '</a>';
                    };
                    ?>
                    <th><?= $makeSortLink('tipo', 'Tipo Documento') ?></th>
                    <th><?= $makeSortLink('gestion', 'Gestión') ?></th>
                    <th><?= $makeSortLink('nro_comprobante', 'Nro Comprobante') ?></th>
                    <th><?= $makeSortLink('contenedor', 'Contenedor') ?></th>
                    <th><?= $makeSortLink('estado', 'Estado') ?></th>
                    <th>Motivo/Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documentosNoDisponibles)): ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 30px;">
                            <?php if (empty($filtros['states'])): ?>
                                <strong style="color: #666;">Seleccione al menos un estado arriba y haga clic en "Actualizar
                                    Tabla" para ver resultados.</strong>
                            <?php else: ?>
                                No se encontraron documentos con los filtros seleccionados.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documentosNoDisponibles as $doc): ?>
                        <tr>
                            <td><?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?></td>
                            <td><strong><?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?></strong></td>
                            <td>
                                <?php if ($doc['tipo_contenedor']): ?>
                                    <?= htmlspecialchars($doc['tipo_contenedor']) ?>
                                    #<?= htmlspecialchars($doc['contenedor_numero']) ?>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($doc['estado_documento'] === 'PRESTADO'): ?>
                                    <span class="badge badge-prestado">🔵 Prestado</span>
                                <?php elseif ($doc['estado_documento'] === 'FALTA'): ?>
                                    <span class="badge badge-falta">🔴 Falta</span>
                                <?php elseif ($doc['estado_documento'] === 'NO UTILIZADO'): ?>
                                    <span class="badge badge-warning">🟡 No Utilizado</span>
                                <?php else: ?>
                                    <span class="badge badge-anulado">🟣 Anulado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($doc['estado_documento'] === 'PRESTADO' && $doc['prestado_a_usuario']): ?>
                                    Prestado a: <strong><?= htmlspecialchars($doc['prestado_a_usuario']) ?></strong>
                                <?php elseif ($doc['estado_documento'] === 'FALTA'): ?>
                                    <span style="color: #E53E3E;">⚠️ Documento extraviado</span>
                                <?php else: ?>
                                    <span style="color: #888;">Documento anulado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/catalogacion/ver/<?= $doc['id'] ?>" class="btn btn-sm btn-primary">Ver Detalle</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($paginacion['total_pages'] > 1): ?>
        <div class="pagination-container" style="padding: 20px; display: flex; justify-content: center;">
            <div class="pagination">
                <?php
                $current = $paginacion['page'];
                $total = $paginacion['total_pages'];
                $max_visible = 10;
                $start = max(1, $current - floor($max_visible / 2));
                $end = min($total, $start + $max_visible - 1);
                if ($end - $start + 1 < $max_visible)
                    $start = max(1, $end - $max_visible + 1);

                // Rebuild params for pag links
                $params = $_GET;
                ?>

                <?php if ($current > 1): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => 1])) ?>" class="btn btn-secondary">⇤</a>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => $current - 1])) ?>"
                        class="btn btn-secondary">←</a>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => $i])) ?>"
                        class="btn <?= $i == $current ? 'btn-primary' : 'btn-light' ?> page-num">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($current < $total): ?>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => $current + 1])) ?>"
                        class="btn btn-secondary">→</a>
                    <a href="?<?= http_build_query(array_merge($params, ['page' => $total])) ?>" class="btn btn-secondary">⇥</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .reportes-header {
        margin-bottom: 30px;
    }

    .reportes-header h1 {
        color: #1B3C84;
        margin-bottom: 20px;
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .stat-blue {
        border-left-color: #3182CE;
    }

    .stat-red {
        border-left-color: #E53E3E;
    }

    .stat-yellow {
        border-left-color: #FFD100;
    }

    .stat-gray {
        border-left-color: #718096;
    }

    .stat-number {
        font-size: 32px;
        font-weight: bold;
        color: #1B3C84;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
        margin-top: 5px;
    }

    .row-vencido {
        background-color: #fff5f5;
    }

    .row-vencido td {
        border-left: 3px solid #E53E3E;
    }

    .row-por-vencer {
        background-color: #fffbeb;
    }

    .badge-info {
        background: #3182CE;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    .filtros-rapidos {
        background: #f8f9fa;
    }

    .btn-light {
        background: white;
        border: 1px solid #ddd;
        color: #333;
    }

    .pagination {
        display: flex;
        gap: 5px;
    }

    @media print {

        .btn,
        .header-actions,
        .filtros-rapidos,
        .pagination-container {
            display: none !important;
        }
    }
</style>

<script>
    function imprimirSeccion(seccion) {
        const container = document.getElementById(`tabla-${seccion}`);
        // Crear un contenedor temporal para manipular la tabla sin afectar la vista actual
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = container.innerHTML; // Copiar contenido
        const table = tempDiv.querySelector('table');

        if (!table) return;

        // 1. Remover última columna (Acciones) de todas las filas
        table.querySelectorAll('tr').forEach(row => {
            if (row.cells.length > 0) {
                row.deleteCell(-1);
            }
        });

        // 2. Limpiar encabezados (quitar links de ordenamiento)
        table.querySelectorAll('th').forEach(th => {
            th.innerText = th.innerText.replace(/[▲▼]/g, '').trim();
        });

        const ventana = window.open('', '_blank');
        const logoUrl = window.location.origin + '/assets/img/logo-tamep.png';

        ventana.document.write(`
        <html>
        <head>
            <title>Reporte TAMEP</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 40px; }
                .header-container { position: relative; margin-bottom: 40px; text-align: center; border-bottom: 2px solid #1B3C84; padding-bottom: 20px; }
                .logo { position: absolute; top: -10px; right: 0; height: 60px; }
                h1 { color: #1B3C84; margin: 0 0 10px 0; font-size: 24px; text-transform: uppercase; }
                p.fecha { font-size: 12px; color: #666; margin: 0; }
                
                table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; }
                th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
                thead th { background-color: #1B3C84; color: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; -webkit-print-color-adjust: exact; }
                
                /* Badges text only for print or simplified style */
                .badge { font-weight: bold; padding: 2px 5px; border-radius: 3px; border: 1px solid #ccc; }
                .badge-prestado { background: #ebf8ff; color: #2b6cb0; border-color: #bee3f8; }
                .badge-falta { background: #fff5f5; color: #c53030; border-color: #feb2b2; }
                .badge-warning { background: #fffff0; color: #d69e2e; border-color: #faf089; }
                .badge-anulado { background: #edf2f7; color: #718096; border-color: #e2e8f0; }
            </style>
        </head>
        <body>
            <div class="header-container">
                <img src="${logoUrl}" class="logo" alt="Logo TAMEP">
                <h1>Sistema TAMEP - Reporte</h1>
                <p class="fecha">Generado el: ${new Date().toLocaleDateString()} a las ${new Date().toLocaleTimeString()}</p>
            </div>
            ${table.outerHTML}
        </body>
        </html>
    `);

        ventana.document.close();
        // Esperar un poco para asegurar que la imagen cargue
        setTimeout(() => {
            ventana.focus();
            ventana.print();
            // ventana.close(); // Opcional, a veces es mejor dejar que el usuario la cierre
        }, 500);
    }

    function exportarExcel(seccion) {
        alert('Función de exportación a Excel en desarrollo.');
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>