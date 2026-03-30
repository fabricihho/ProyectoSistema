<?php
ob_start();
$title = 'Historial de Auditoría';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>📜 Historial de Auditoría</h2>
                <div class="header-actions" style="display: flex; gap: 10px;">
                    <form id="formReporte" action="/reportes/auditorias/exportar-seleccion" method="POST" target="_blank" style="display: inline;">
                        <input type="hidden" name="ids" id="ids_exportar">
                        <button type="button" class="btn btn-warning" onclick="generarReporte()" 
                                data-bs-toggle="tooltip" title="Generar reporte de las filas seleccionadas">
                            📑 Generar Reporte
                        </button>
                    </form>
                    <a href="/reportes" class="btn btn-secondary">Volver a Reportes</a>
                </div>
            </div>

            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" action="/reportes/auditorias" class="mb-5 p-4 bg-light rounded shadow-sm border border-light">
                    <style>
                        /* Custom Select Styling */
                        .form-select-custom, .form-control {
                            border: 1px solid #ced4da;
                            border-radius: 0.375rem;
                            padding: 0.5rem 0.75rem; /* Consistent padding */
                            height: 42px; /* Fixed height for alignment */
                        }
                        .form-select-custom {
                            background-color: #fff;
                            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
                            background-repeat: no-repeat;
                            background-position: right 0.75rem center;
                            background-size: 16px 12px;
                            -webkit-appearance: none;
                            -moz-appearance: none;
                            appearance: none;
                        }
                        .form-select-custom:focus, .form-control:focus {
                            border-color: #86b7fe;
                            outline: 0;
                            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
                        }
                        .btn-filter-group {
                            height: 42px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                    </style>
                    <div class="row g-3">
                        <!-- Fila 1 -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Módulo</label>
                            <select name="modulo" class="form-select form-select-custom">
                                <option value="">Todos</option>
                                <?php foreach ($modulos as $m): ?>
                                    <option value="<?= htmlspecialchars($m['modulo']) ?>"
                                        <?= $filters['modulo'] == $m['modulo'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['modulo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Acción</label>
                            <select name="accion" class="form-select form-select-custom">
                                <option value="">Todas</option>
                                <?php foreach ($acciones as $a): ?>
                                    <option value="<?= htmlspecialchars($a['accion']) ?>"
                                        <?= $filters['accion'] == $a['accion'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['accion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Usuario</label>
                            <select name="usuario_id" class="form-select form-select-custom">
                                <option value="">Todos</option>
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $filters['usuario_id'] == $u['id'] ? 'selected' : '' ?>
                                        >
                                        <?= htmlspecialchars($u['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3" style="text-align: center;">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1 btn-filter-group fw-bold">
                                    <i class="bi bi-search me-1"></i> Filtrar
                                </button>
                                <?php if (!empty(array_filter($filters))): ?>
                                    <a href="/reportes/auditorias" class="btn btn-outline-secondary flex-grow-1 btn-filter-group">
                                        <i class="bi bi-x-circle me-1"></i> Limpiar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        
                    </div>

                    <div class="row g-3 mt-2">
                        <!-- Fila 2 -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Fecha Desde</label>
                            <input type="date" name="fecha_desde" class="form-control"
                                value="<?= htmlspecialchars($filters['fecha_desde']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Fecha Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control"
                                value="<?= htmlspecialchars($filters['fecha_hasta']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Búsqueda</label>
                            <input type="text" name="search" class="form-control" placeholder="Detalles..."
                                value="<?= htmlspecialchars($filters['search']) ?>">
                        </div>
                        
                    </div>
                </form>
                <br>

                <!-- Tabla -->
                <?php
                $columns = [
                    [
                        'label' => 'Fecha',
                        'field' => 'fecha',
                        'sortable' => true,
                        'width' => '150px',
                        'formatter' => function($row) {
                            return date('d/m/Y H:i', strtotime($row['fecha']));
                        }
                    ],
                    [
                        'label' => 'Usuario',
                        'field' => 'usuario',
                        'sortable' => true,
                        'formatter' => function($row) {
                            return '<span class="fw-bold text-primary">' . htmlspecialchars($row['usuario_nombre'] ?? 'Sistema') . '</span>' .
                                   '<br><small class="text-muted">@' . htmlspecialchars($row['username'] ?? 'system') . '</small>';
                        }
                    ],
                    [
                        'label' => 'Módulo',
                        'field' => 'modulo',
                        'sortable' => true,
                        'formatter' => function($row) {
                            return '<span class="badge bg-info text-dark">' . htmlspecialchars($row['modulo']) . '</span>';
                        }
                    ],
                    [
                        'label' => 'Acción',
                        'field' => 'accion',
                        'sortable' => true,
                        'formatter' => function($row) {
                            $badgeClass = 'bg-secondary';
                            if (strpos($row['accion'], 'CREAR') !== false) $badgeClass = 'bg-success';
                            if (strpos($row['accion'], 'EDITAR') !== false) $badgeClass = 'bg-warning text-dark';
                            if (strpos($row['accion'], 'ELIMINAR') !== false) $badgeClass = 'bg-danger';
                            if ($row['accion'] == 'SEGURIDAD') $badgeClass = 'bg-dark';
                            return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($row['accion']) . '</span>';
                        }
                    ],
                    [
                        'label' => 'ID Reg.',
                        'field' => 'registro_id',
                        'sortable' => true,
                        'width' => '80px',
                        'formatter' => function($row) {
                            return htmlspecialchars($row['registro_id'] ?? '-');
                        }
                    ],
                    [
                        'label' => 'Detalles',
                        'field' => 'detalles',
                        'formatter' => function($row) {
                            return '<div style="max-width: 400px; word-wrap: break-word;"><small>' . htmlspecialchars($row['detalles']) . '</small></div>';
                        }
                    ]
                ];

                // Remapear datos para el componente
                $rows = $logs;
                $paginacion['page'] = $paginacion['current'];
                $paginacion['total'] = $paginacion['total_items'];
                $filtros = $filters; // ReportesController usa $filters, partial espera $filtros
                
                $modoLotes = true;
                $showPerPage = true;
                $checkboxId = 'checkAll';
                $batchCheckboxFormatter = function($row) {
                    return '<input type="checkbox" class="form-check-input check-item" value="' . $row['id'] . '" style="cursor: pointer;">';
                };

                include __DIR__ . '/../partials/table.php';
                ?>

                <div class="text-muted text-end mt-2">
                    <small>Total registros: <?= $paginacion['total_items'] ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Inicializar Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Lógica de Checkboxes
    document.getElementById('checkAll').addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('.check-item');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });

    // Validar y Enviar formulario de reporte
    function generarReporte() {
        var selected = [];
        var checkboxes = document.querySelectorAll('.check-item:checked');
        
        for (var checkbox of checkboxes) {
            selected.push(checkbox.value);
        }

        if (selected.length === 0) {
            alert('⚠️ Por favor seleccione al menos una fila para generar el reporte.');
            return;
        }

        document.getElementById('ids_exportar').value = JSON.stringify(selected);
        document.getElementById('formReporte').submit();
    }
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>