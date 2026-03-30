<?php
ob_start();
$title = 'Reporte: Documentos No Disponibles';
?>

<div class="reportes-header mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>⚠️ Documentos No Disponibles</h1>
        <div class="actions">
            <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir Reporte</button>
        </div>
    </div>

    <!-- Filtros de Estado -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="/reportes/no-disponibles">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="d-flex align-items-center"
                        style="display: flex !important; flex-wrap: wrap; align-items: center;">
                        <label class="fw-bold mb-0 filter-label-main" style="white-space: nowrap;">Filtrar por Estado:</label>

                        <!-- Opción Prestados -->
                        <div class="filter-item">
                            <input class="form-check-input filter-checkbox" type="checkbox" name="states[]" value="PRESTADO"
                                id="chk_prestado" <?= in_array('PRESTADO', $filtros['states']) ? 'checked' : '' ?>
                                onchange="this.form.submit()">
                            <label class="badge badge-prestado cursor-pointer mb-0" for="chk_prestado">Prestados</label>
                        </div>

                        <!-- Opción Faltantes -->
                        <div class="filter-item">
                            <input class="form-check-input filter-checkbox" type="checkbox" name="states[]" value="FALTA"
                                id="chk_falta" <?= in_array('FALTA', $filtros['states']) ? 'checked' : '' ?>
                                onchange="this.form.submit()">
                            <label class="badge badge-falta cursor-pointer mb-0" for="chk_falta">Faltantes</label>
                        </div>

                        <!-- Opción No Utilizados -->
                        <div class="filter-item">
                            <input class="form-check-input filter-checkbox" type="checkbox" name="states[]" value="NO UTILIZADO"
                                id="chk_nout" <?= in_array('NO UTILIZADO', $filtros['states']) ? 'checked' : '' ?>
                                onchange="this.form.submit()">
                            <label class="badge badge-no-utilizado cursor-pointer mb-0" for="chk_nout">No
                                Utilizados</label>
                        </div>

                        <!-- Opción Anulados -->
                        <div class="filter-item">
                            <input class="form-check-input filter-checkbox" type="checkbox" name="states[]" value="ANULADO"
                                id="chk_anulado" <?= in_array('ANULADO', $filtros['states']) ? 'checked' : '' ?>
                                onchange="this.form.submit()"> <label class="badge badge-anulado cursor-pointer mb-0"
                                for="chk_anulado">Anulados</label>
                        </div>
                    </div>

                    <div class="ms-auto" style="display: none;">
                        <noscript><button type="submit"
                                class="btn btn-sm btn-outline-primary">Actualizar</button></noscript>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <!-- Header with Rows Selector -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #eee;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #1B3C84;">Resultados</h3>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span>Cantidad de Filas:</span>
                <input type="number" id="perPageInput" value="<?= $paginacion['per_page'] ?? 20 ?>" min="1" max="200"
                    style="width: 70px; padding: 5px; border-radius: 4px; border: 1px solid #ccc;"
                    onchange="updatePerPage(this.value)"
                    onkeypress="if(event.key === 'Enter') updatePerPage(this.value)">
                <span class="badge badge-info"><?= number_format($paginacion['total'] ?? count($documentos)) ?>
                    documentos</span>
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

        // Define Rows and Pagination mapping
        $rows = $documentos;
        // The partial expects $paginacion['page'], but report controller might differ.
        if (isset($paginacion) && isset($paginacion['current'])) {
            $paginacion['page'] = $paginacion['current'];
        }
        // Ensure per_page is set for the selector if not already
        if (!isset($paginacion['per_page'])) {
            $paginacion['per_page'] = $_GET['per_page'] ?? 20;
        }

        // Define Columns
        $columns = [
            [
                'label' => 'Tipo Documento',
                'field' => 'tipo', // sort key
                'sortable' => true,
                'formatter' => function ($doc) {
                    return htmlspecialchars($doc['tipo_documento'] ?? 'N/A');
                }
            ],
            [
                'label' => 'Gestión',
                'field' => 'gestion', // sort key
                'sortable' => true
            ],
            [
                'label' => 'Nro Comprobante',
                'field' => 'nro_comprobante', // sort key
                'sortable' => true,
                'formatter' => function ($doc) {
                    return '<strong>' . htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') . '</strong>';
                }
            ],
            [
                'label' => 'Contenedor',
                'field' => 'contenedor', // sort key
                'sortable' => true,
                'formatter' => function ($doc) {
                    if (!empty($doc['tipo_contenedor'])) {
                        $abc = !empty($doc['contenedor_codigo_abc']) ? '.' . htmlspecialchars($doc['contenedor_codigo_abc']) : '';
                        return htmlspecialchars($doc['tipo_contenedor']) . ' #' . htmlspecialchars($doc['contenedor_numero']) . $abc;
                    }
                    return '<span style="color: #999;">-</span>';
                }
            ],
            [
                'label' => 'Estado',
                'field' => 'estado', // sort key
                'sortable' => true,
                'formatter' => function ($doc) {
                    $estado = $doc['estado_documento'];
                    if ($estado === 'PRESTADO')
                        return '<span class="badge badge-prestado">🔵 Prestado</span>';
                    if ($estado === 'FALTA')
                        return '<span class="badge badge-falta">🔴 Falta</span>';
                    if ($estado === 'NO UTILIZADO')
                        return '<span class="badge badge-no-utilizado">🟡 No Utilizado</span>';
                    return '<span class="badge badge-anulado">🟣 Anulado</span>';
                }
            ],
            [
                'label' => 'Motivo/Usuario',
                'formatter' => function ($doc) {
                    if ($doc['estado_documento'] === 'PRESTADO' && !empty($doc['prestado_a_usuario'])) {
                        return 'Prestado a: <strong>' . htmlspecialchars($doc['prestado_a_usuario']) . '</strong>';
                    } elseif ($doc['estado_documento'] === 'FALTA') {
                        return '<span style="color: #E53E3E;">⚠️ Documento extraviado</span>';
                    }
                    return '<span style="color: #888;">Documento anulado</span>';
                }
            ],
            [
                'label' => 'Acciones',
                'formatter' => function ($doc) {
                    return '<a href="/catalogacion/ver/' . $doc['id'] . '" class="btn btn-sm btn-primary">Ver Detalle</a>';
                }
            ]
        ];

        $idField = 'id';

        // Include Component
        include __DIR__ . '/../partials/table.php';
        ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
<style>
    /* Pagination Styles */
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

    /* Force Filter Spacing */
    .filter-item {
        margin-right: 40px !important;
        display: flex !important;
        align-items: center !important;
    }
    
    .filter-checkbox {
        margin-right: 5px !important; /* Reduced to 5px for closer proximity */
        transform: scale(1.2); 
    }
    
    .filter-label-main {
        margin-right: 30px !important;
    }
</style>