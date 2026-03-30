<?php
ob_start();
$title = 'Reporte: Préstamos Activos';
?>

<div class="reportes-header mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>📤 Préstamos Activos</h1>
        <div class="actions">
            <button onclick="exportarSeleccion('pdf')" class="btn btn-secondary">📄 PDF Seleccionados</button>
            <button onclick="exportarSeleccion('excel')" class="btn btn-success">📊 Excel Seleccionados</button>
        </div>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="/reportes/prestamos" class="row g-3">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control"
                        placeholder="Buscar por usuario, documento, comprobante o caja..."
                        value="<?= htmlspecialchars($filtros['search'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="card">
    <div class="card-body p-0">
        <?php
        // Configuración de columnas para el componente de tabla
        $columns = [
            [
                'label' => 'Usuario',
                'field' => 'usuario',
                'sortable' => true,
                'formatter' => function ($row) {
                    return '<strong>' . htmlspecialchars($row['usuario_nombre'] ?? '') . '</strong><br>' .
                        '<small class="text-muted">(' . htmlspecialchars($row['username'] ?? '') . ')</small>';
                }
            ],
            [
                'label' => 'Área/Rol',
                'width' => '10%',
                'formatter' => function ($row) {
                    return '<span class="badge bg-secondary">N/A</span>';
                }
            ],
            [
                'label' => 'Documento',
                'field' => 'documento',
                'sortable' => true,
                'formatter' => function ($row) {
                    $html = htmlspecialchars($row['tipo_documento'] ?? '') . '<br>';
                    $html .= '<small>Nro: ' . htmlspecialchars($row['nro_comprobante'] ?? '');
                    if (!empty($row['codigo_abc'])) {
                        $html .= ' (' . htmlspecialchars($row['codigo_abc']) . ')';
                    }
                    $html .= '</small>';
                    return $html;
                }
            ],
            [
                'label' => 'Gestión',
                'field' => 'gestion',
                'sortable' => true,
                'width' => '5%',
                'formatter' => function ($row) {
                    return htmlspecialchars($row['gestion'] ?? '');
                }
            ],
            [
                'label' => 'Contenedor',
                'field' => 'contenedor',
                'sortable' => true,
                'formatter' => function ($row) {
                    $abc = !empty($row['contenedor_codigo_abc']) ? '.' . htmlspecialchars($row['contenedor_codigo_abc']) : '';
                    return htmlspecialchars($row['tipo_contenedor'] ?? '') . ' #' . htmlspecialchars($row['contenedor_numero'] ?? '') . $abc;
                }
            ],
            [
                'label' => 'Fecha Préstamo',
                'field' => 'fecha_prestamo',
                'sortable' => true,
                'formatter' => function ($row) {
                    return date('d/m/Y', strtotime($row['fecha_prestamo']));
                }
            ],
            [
                'label' => 'Devolución Est.',
                'field' => 'fecha_devolucion',
                'sortable' => true,
                'formatter' => function ($row) {
                    $html = date('d/m/Y', strtotime($row['fecha_devolucion_esperada'])) . '<br>';

                    if ($row['dias_restantes'] < 0) {
                        $html .= '<span class="badge bg-danger">⚠️ Vencido (' . abs($row['dias_restantes']) . ' días)</span>';
                    } elseif ($row['dias_restantes'] <= 3) {
                        $html .= '<span class="badge bg-warning text-dark">⚠️ Vence en ' . $row['dias_restantes'] . ' días</span>';
                    } else {
                        $html .= '<small class="text-success">' . $row['dias_restantes'] . ' días restantes</small>';
                    }
                    return $html;
                }
            ],
            [
                'label' => 'Acciones',
                'width' => '15%',
                'formatter' => function ($row) {
                    return '<div class="btn-group btn-group-sm">' .
                        '<a href="/prestamos/ver/' . $row['id'] . '" class="btn btn-primary" title="Ver Detalles">Ver</a>' .
                        '<a href="/prestamos/devolver/' . $row['id'] . '" class="btn btn-outline-success" title="Registrar Devolución">✓ Devolver</a>' .
                        '</div>';
                }
            ]
        ];

        // Preparar datos para el componente
        $paginacion['page'] = $paginacion['current']; // Remapear clave
        $rows = $prestamos; // Remapear filas
        
        // Variables para el modo lotes del componente tabla
        $modoLotes = true;
        $showPerPage = true;
        $batchCheckboxFormatter = function($row) {
            return '<input type="checkbox" name="selected_loans[]" value="' . $row['id'] . '" class="loan-checkbox">';
        };

        // Renderizar tabla reutilizable
        include __DIR__ . '/../partials/table.php';
        ?>
    </div>
</div>

<script>
    // Lógica para seleccionar todos (compatible con el componente)
    function toggleTodos(source) {
        document.querySelectorAll('.loan-checkbox').forEach(cb => cb.checked = source.checked);
    }

    function exportarSeleccion(tipo) {
        const selected = Array.from(document.querySelectorAll('.loan-checkbox:checked')).map(cb => cb.value);

        if (selected.length === 0) {
            if (!confirm('No ha seleccionado ningún préstamo. ¿Desea exportar TODO el listado actual filtrado?')) {
                return;
            }
            const search = "<?= htmlspecialchars($filtros['search'] ?? '') ?>";
            window.location.href = `/reportes/exportar-${tipo}?search=${encodeURIComponent(search)}`;
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/reportes/exportar-${tipo}-seleccion`;

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids';
        input.value = JSON.stringify(selected);
        form.appendChild(input);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>