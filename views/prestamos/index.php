<?php
ob_start();
$pageTitle = 'Gestión de Préstamos';
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>📤 Gestión de Préstamos</h2>
        <div class="header-actions">
            <a href="/prestamos/importar" class="btn btn-secondary">📊 Importar Excel</a>
            <a href="/prestamos/crear" class="btn btn-primary">➕ Nuevo Préstamo</a>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="search-form" style="padding: 20px; border-bottom: 1px solid #E2E8F0;">
        <!-- Preserve Sort/Order on Filter Change -->
        <input type="hidden" name="sort" value="<?= htmlspecialchars($filtros['sort']) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($filtros['order']) ?>">

        <div class="form-row"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="estado">Estado</label>
                <select id="estado" name="estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="En Proceso" <?= $filtros['estado'] === 'En Proceso' ? 'selected' : '' ?>>⚠️ Por Procesar
                    </option>
                    <option value="Prestado" <?= $filtros['estado'] === 'Prestado' ? 'selected' : '' ?>>📤 Prestado
                    </option>
                    <option value="Devuelto" <?= $filtros['estado'] === 'Devuelto' ? 'selected' : '' ?>>✅ Devuelto</option>
                </select>
            </div>

            <div class="form-group">
                <label for="usuario_id">Usuario</label>
                <select id="usuario_id" name="usuario_id" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $usr): ?>
                        <option value="<?= $usr['id'] ?>" <?= $filtros['usuario_id'] == $usr['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($usr['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>



            <div class="form-group" style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="margin-right: 10px;">🔍 Buscar</button>
                <a href="/prestamos" class="btn btn-secondary">🔄 Limpiar</a>
            </div>
        </div>
    </form>

    <!-- Tabla de préstamos -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <h3>Resultados</h3>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span>Cantidad de Filas:</span>
            <input type="number" id="perPageInput" value="<?= $filtros['per_page'] ?? 20 ?>" min="1" max="200"
                style="width: 70px; padding: 5px; border-radius: 4px; border: 1px solid #ccc;"
                onchange="updatePerPage(this.value)" onkeypress="if(event.key === 'Enter') updatePerPage(this.value)">
            <span class="badge badge-info"><?= number_format($paginacion['total']) ?> préstamos</span>
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

    <?php
    // Define Row Class Callback
    $rowClassCallback = function ($pres) {
        $vencido = ($pres['estado'] === 'Prestado' && strtotime($pres['fecha_devolucion_esperada']) < time());
        return $vencido ? 'row-vencido' : '';
    };

    // Define Columns
    $columns = [
        [
            'label' => 'Unidad/Área',
            'field' => 'unidad', // sort field
            'sortable' => true,
            'width' => '25%',
            'formatter' => function ($pres) {
                return '<div class="font-weight-bold" style="font-size: 1.05em; color: #2d3748;">' .
                    htmlspecialchars($pres['unidad_nombre'] ?? 'N/A') .
                    '</div>' .
                    '<div class="text-muted small">' .
                    '<i class="icon-user"></i> Prestatario: ' . htmlspecialchars($pres['nombre_prestatario'] ?? 'N/A') .
                    '</div>';
            }
        ],
        [
            'label' => 'Fechas (P/D)',
            'field' => 'fecha', // sort field
            'sortable' => true,
            'width' => '20%',
            'formatter' => function ($pres) {
                $vencido = ($pres['estado'] === 'Prestado' && strtotime($pres['fecha_devolucion_esperada']) < time());
                $html = '<div style="font-size: 0.9em; color: #4a5568;">' .
                    '<strong>P:</strong> ' . date('d/m/Y', strtotime($pres['fecha_prestamo'])) .
                    '</div>' .
                    '<div style="font-size: 0.9em; color: #4a5568;">' .
                    '<strong>D:</strong> ' . date('d/m/Y', strtotime($pres['fecha_devolucion_esperada'])) .
                    '</div>';
                if ($vencido) {
                    $html .= '<span class="badge badge-falta" style="font-size: 0.7em;">⚠️ Vencido</span>';
                }
                return $html;
            }
        ],
        [
            'label' => 'Registrado por',
            'field' => 'usuario', // sort field
            'sortable' => true,
            'width' => '20%',
            'formatter' => function ($pres) {
                return '<div class="font-weight-bold" style="font-size: 1.05em; color: #2d3748;">' .
                    '<i class="icon-user-check" style="font-size: 0.9em; opacity: 0.7;"></i> ' .
                    htmlspecialchars($pres['usuario_nombre'] ?? 'Sistema') .
                    '</div>';
            }
        ],
        [
            'label' => 'Docs',
            'field' => 'docs', // sort field
            'sortable' => true,
            'width' => '10%',
            'formatter' => function ($pres) {
                return '<div style="line-height: 1.2; color: #4a5568; text-align: center;">' .
                    '<span style="font-size: 1.2em; font-weight: bold; display: block;">' . $pres['total_documentos'] . '</span>' .
                    '<span style="font-size: 0.85em;">docs</span>' .
                    '</div>';
            }
        ],
        [
            'label' => 'Estado',
            'field' => 'estado', // sort field
            'sortable' => true,
            'width' => '15%',
            'formatter' => function ($pres) {
                if ($pres['estado'] === 'En Proceso') {
                    return '<span class="badge badge-warning" style="font-weight: 500; letter-spacing: 0.5px;">⚠️ Por Procesar</span>';
                } elseif ($pres['estado'] === 'Prestado') {
                    return '<span class="badge badge-prestado" style="font-weight: 500; letter-spacing: 0.5px;">📥 Prestado</span>';
                } else {
                    return '<span class="badge badge-disponible" style="font-weight: 500; letter-spacing: 0.5px;">✅ Devuelto</span>';
                }
            }
        ],
        [
            'label' => 'Acciones',
            'width' => '10%',
            'formatter' => function ($pres) {
                $html = '<div style="display: flex; gap: 5px; justify-content: center;">';

                // Ver
                $html .= '<a href="/prestamos/ver/' . $pres['id'] . '" class="btn btn-sm btn-primary" title="Ver Detalle">Ver</a>';

                // Editar
                if ($pres['estado'] == 'En Proceso' || $pres['estado'] == 'Prestado') {
                    $html .= '<a href="/prestamos/procesar/' . $pres['id'] . '" class="btn btn-sm btn-edit-custom" title="Editar">✏️ Editar</a>';
                } else {
                    $html .= '<button class="btn btn-sm btn-edit-custom" disabled style="opacity: 0.5; cursor: not-allowed;" title="Ya procesado">✏️ Editar</button>';
                }

                // Eliminar
                $html .= '<button onclick="confirmarEliminacion(' . $pres['id'] . ')" class="btn btn-sm btn-danger" title="Eliminar">🗑️</button>';

                // PDF
                $html .= '<a href="/prestamos/exportar-pdf/' . $pres['id'] . '" target="_blank" class="btn btn-sm btn-outline-secondary" title="PDF">📄 PDF</a>';

                $html .= '</div>';
                return $html;
            }
        ]
    ];

    // Prepare data
    $rows = $prestamos; // Variable name used in component is 'rows'
    $idField = 'id';

    // Include Component
    include __DIR__ . '/../partials/table.php';
    ?>
</div>



<style>
    /* Custom Button Styles for Prestamos */
    .btn-edit-custom {
        background-color: #6c757d;
        /* Plomo/Gris default */
        border-color: #6c757d;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-edit-custom:hover:not(:disabled) {
        background-color: #ffc107;
        /* Amarillo on hover */
        border-color: #ffc107;
        color: #212529;
        /* Texto oscuro */
    }

    /* Fix flex alignment for buttons */
    .btn-sm {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 32px;
        /* Fixed height for consistency */
        padding: 0 10px;
        vertical-align: middle;
        line-height: normal;
        /* Override bootstrap */
    }

    .row-vencido {
        background-color: #fff5f5;
    }

    .row-vencido td {
        border-left: 3px solid #E53E3E;
    }
</style>

<script>
    function confirmarDevolucion(id) {
        if (confirm('¿Confirmar la devolución de este documento?\n\nSe actualizará el estado del documento a DISPONIBLE.')) {
            window.location.href = '/prestamos/devolver/' + id;
        }
    }

    function confirmarEliminacion(id) {
        if (confirm('¿Está seguro de eliminar este préstamo?\n\nSe eliminará el registro y los documentos se liberarán (volverán a DISPONIBLE).')) {
            window.location.href = '/prestamos/eliminar/' + id;
        }
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>