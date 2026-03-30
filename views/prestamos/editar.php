<?php 
ob_start(); 
$pageTitle = 'Editar Préstamo #' . $prestamo['id'];
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>✏️ Editar Préstamo #<?= $prestamo['id'] ?></h2>
        <div class="header-actions" style="display: flex; gap: 10px;">
            <a href="/prestamos/pdf-edicion/<?= $prestamo['id'] ?>" class="btn btn-danger" target="_blank" title="PDF de Búsqueda (sin firmas)">
                📄 PDF Búsqueda
            </a>
            <a href="/prestamos" class="btn btn-secondary">Volver al Listado</a>
            <a href="/prestamos/ver/<?= $prestamo['id'] ?>" class="btn btn-primary">Ver Detalle</a>
        </div>
    </div>
    
    <!-- 1. Formulario de Edición de Encabezado -->
    <div class="edit-section" style="background: #f8f9fa; padding: 20px; border-bottom: 2px solid #e2e8f0;">
        <h3 style="color: #1B3C84; margin-bottom: 15px; border-bottom: 1px solid #cbd5e0; padding-bottom: 5px;">
            📝 Datos del Préstamo
        </h3>
        
        <form action="/prestamos/confirmarProceso" method="POST" id="form-prestamo">
            <input type="hidden" name="encabezado_id" value="<?= $prestamo['id'] ?>">
            <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="form-group">
                    <label for="unidad_area_id">Unidad/Área Solicitante <span class="required">*</span></label>
                    <select id="unidad_area_id" name="unidad_area_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($unidades as $ubi): ?>
                            <option value="<?= $ubi['id'] ?>" <?= $prestamo['unidad_area_id'] == $ubi['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ubi['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="nombre_prestatario">Nombre Prestatario</label>
                    <input type="text" id="nombre_prestatario" name="nombre_prestatario" class="form-control" 
                           value="<?= htmlspecialchars($prestamo['nombre_prestatario'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="fecha_prestamo">Fecha de Préstamo <span class="required">*</span></label>
                    <input type="date" id="fecha_prestamo" name="fecha_prestamo" class="form-control" 
                           value="<?= $prestamo['fecha_prestamo'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="fecha_devolucion_esperada">Fecha Devolución Est.</label>
                    <input type="date" id="fecha_devolucion_esperada" name="fecha_devolucion_esperada" class="form-control" 
                           value="<?= $prestamo['fecha_devolucion_esperada'] ?>">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="observaciones">Observaciones</label>
                    <input type="text" id="observaciones" name="observaciones" class="form-control" 
                           value="<?= htmlspecialchars($prestamo['observaciones'] ?? '') ?>">
                </div>
            </div>
            
        <!-- Deleted separate header save button -->
    </div>

    <!-- 2. Lista de Documentos Actuales -->
    <div class="edit-section" style="padding: 20px;">
        <h3 style="color: #1B3C84; margin-bottom: 15px; border-bottom: 1px solid #cbd5e0; padding-bottom: 5px; display: flex; justify-content: space-between;">
            <span>📚 Documentos en este Préstamo</span>
            <span class="badge badge-info"><?= count($detalles) ?> Docs</span>
        </h3>
        
        <?php if (empty($detalles)): ?>
            <div class="alert alert-warning">No hay documentos en este préstamo. Utilice el buscador abajo para agregar.</div>
        <?php else: ?>
                <!-- Table (Form opened above) -->
                <?php 
                // Detectar si es un préstamo nuevo o revertido
                $todosEnProceso = true;
                foreach ($detalles as $d) {
                    if ($d['estado'] !== 'En Proceso') {
                        $todosEnProceso = false;
                        break;
                    }
                }

                // Configurar Formateador de Checkbox para Lotes
                $batchCheckboxFormatter = function($doc) use ($todosEnProceso) {
                    if ($todosEnProceso) {
                        $isChecked = true;
                    } else {
                        $isChecked = ($doc['estado'] === 'Prestado');
                    }
                    
                    return sprintf(
                        '<input type="checkbox" name="documentos[]" value="%s" class="doc-checkbox" 
                               %s style="transform: scale(1.5); cursor: pointer;"
                               data-estado="%s"
                               data-label="%s">',
                        $doc['id'],
                        $isChecked ? 'checked' : '',
                        htmlspecialchars($doc['estado_anterior'] ?? $doc['estado_documento'] ?? ''),
                        htmlspecialchars(($doc['tipo_documento'] ?? '') . ' #' . ($doc['nro_comprobante'] ?? '') . (!empty($doc['contenedor_codigo_abc']) ? '.' . $doc['contenedor_codigo_abc'] : ''))
                    );
                };

                // Configurar Columnas
                $columnsDetalle = [
                    [
                        'label' => 'Documento',
                        'formatter' => function($doc) {
                            return '<strong>' . htmlspecialchars($doc['tipo_documento'] ?? 'N/A') . '</strong><br>' .
                                   '<small>' . htmlspecialchars($doc['gestion']) . ' | #' . htmlspecialchars($doc['nro_comprobante']) . '</small>';
                        }
                    ],
                    [
                        'label' => 'Contenedor',
                        'formatter' => function($doc) {
                            $abc = !empty($doc['contenedor_codigo_abc']) ? '.' . htmlspecialchars($doc['contenedor_codigo_abc']) : '';
                            return htmlspecialchars($doc['tipo_contenedor'] ?? '') . ' #' . htmlspecialchars($doc['contenedor_numero'] ?? '') . $abc;
                        }
                    ],
                    [
                        'label' => 'Ubicación',
                        'formatter' => function($doc) {
                            return '<small>' . htmlspecialchars($doc['ubicacion_fisica'] ?? '') . '</small>';
                        }
                    ],
                    [
                        'label' => 'Estado Documento',
                        'formatter' => function($doc) {
                             return '<span class="badge badge-secondary">' . htmlspecialchars($doc['estado_anterior'] ?? 'N/A') . '</span>';
                        }
                    ],
                    [
                        'label' => 'Estado Solicitud',
                        'formatter' => function($doc) {
                            if ($doc['estado'] === 'Prestado') {
                                return '<span class="badge badge-prestado">Prestado</span>';
                            } elseif ($doc['estado'] === 'Devuelto') {
                                return '<span class="badge badge-disponible">Devuelto</span>';
                            } else {
                                return '<span class="badge badge-info">' . $doc['estado'] . '</span>';
                            }
                        }
                    ],
                    [
                        'label' => 'Acciones',
                        'formatter' => function($doc) {
                            return '<a href="/prestamos/quitarDetalle/' . $doc['id'] . '" class="btn btn-danger btn-sm" 
                                           onclick="return confirm(\'¿Quitar este documento del préstamo? Volverá a estar DISPONIBLE.\');">
                                            ✕ Quitar
                                        </a>';
                        }
                    ]
                ];

                // --- LOGICA DE PAGINACION PARA DETALLES ---
                $d_perPage = isset($_GET['d_per_page']) ? (int)$_GET['d_per_page'] : 10;
                if ($d_perPage < 1) $d_perPage = 10;

                $totalDetalles = count($detalles);
                $d_totalPages = ceil($totalDetalles / $d_perPage);
                if ($d_totalPages < 1) $d_totalPages = 1;

                // Si 'd_page' está definido en URL, usarlo. Si no, ir a la ÚLTIMA página por defecto.
                if (isset($_GET['d_page'])) {
                    $d_page = (int)$_GET['d_page'];
                } else {
                    $d_page = $d_totalPages; // Default to last page
                }
                
                if ($d_page < 1) $d_page = 1;
                if ($d_page > $d_totalPages) $d_page = $d_totalPages;

                $d_offset = ($d_page - 1) * $d_perPage;
                
                $detallesPaginados = array_slice($detalles, $d_offset, $d_perPage);
                
                // Construir array de paginación para el partial
                $paginacionDetalles = [
                    'page' => $d_page,
                    'per_page' => $d_perPage,
                    'total' => $totalDetalles,
                    'total_pages' => $d_totalPages
                ];

                // Renderizar Tabla Detalles
                $rows = $detallesPaginados;
                $columns = $columnsDetalle;
                $modoLotes = true;
                
                // Configurar Params Partial
                $pageParamName = 'd_page';
                $perPageParamName = 'd_per_page';
                $showPerPage = true;

                // Backup global pagination (Search Table)
                $tempPaginacion = $paginacion ?? null;
                $paginacion = $paginacionDetalles; // Swap for Details Table

                include __DIR__ . '/../partials/table.php';
                
                // Restore logic for Search Table
                $paginacion = $tempPaginacion;
                $pageParamName = 'page';        // Reset to default
                $perPageParamName = 'per_page'; // Reset to default
                $showPerPage = true;            // Enable for search table too
                ?>

         <?php endif; ?>
         
            <!-- Action Buttons -->
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 2px solid #E2E8F0;">
                <a href="/prestamos" class="btn btn-secondary">← Cancelar Préstamo</a>
                <button type="submit" class="btn btn-primary">✓ Confirmar Préstamo</button>
            </div>
            <p class="text-muted"><small>* Se guardarán los cambios del encabezado y la confirmación de documentos.</small></p>
        </form> <!-- Close consolidated form -->
    </div>
    
    <script>
    function toggleTodos(source) {
        const checkboxes = document.querySelectorAll('.doc-checkbox');
        for(let i=0; i < checkboxes.length; i++) {
            checkboxes[i].checked = source.checked;
        }
    }

    document.getElementById('form-prestamo').addEventListener('submit', function(e) {
        const checkboxes = document.querySelectorAll('.doc-checkbox:checked');
        const irregulares = [];

        checkboxes.forEach(function(cb) {
            const estado = cb.getAttribute('data-estado');
            const label = cb.getAttribute('data-label');
            
            // Si el estado es FALTA o PRESTADO, lo agregamos a la lista
            if (estado === 'FALTA' || estado === 'PRESTADO') {
                irregulares.push(`- ${label} (Estado actual: ${estado})`);
            }
        });

        if (irregulares.length > 0) {
            const mensaje = "⚠️ ADVERTENCIA: Está a punto de prestar documentos con estado irregular:\n\n" + 
                            irregulares.join("\n") + 
                            "\n\n¿Desea continuar de todos modos?";
            
            if (!confirm(mensaje)) {
                e.preventDefault(); // Cancelar envío
            }
        }
    });
    </script>

    <!-- 3. Buscador para Agregar Nuevos Documentos -->
    <div class="edit-section search-section" style="background: #f0f4f8; padding: 20px; border-top: 2px solid #e2e8f0;">
        <h3 style="color: #2c5282; margin-bottom: 15px;">🔍 Buscar y Agregar Documentos</h3>
        
        <form method="GET" action="/prestamos/editar/<?= $prestamo['id'] ?>" class="search-form">
            <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px;">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($filtros['search']) ?>">
                </div>
                <div class="form-group">
                    <input type="number" name="gestion" class="form-control" placeholder="Gestión" value="<?= htmlspecialchars($filtros['gestion']) ?>">
                </div>
                <div class="form-group">
                    <select name="tipo_documento" class="form-control">
                        <option value="">-- Tipo --</option>
                        <?php foreach ($tiposDocumento as $td): ?>
                            <option value="<?= $td['codigo'] ?>" <?= $filtros['tipo_documento'] == $td['codigo'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($td['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Buscar</button>
                    <a href="/prestamos/editar/<?= $prestamo['id'] ?>" class="btn btn-secondary btn-block">Limpiar</a>
                </div>
            </div>
            
            <!-- Preserve pagination params if needed, mostly reset on new search -->
        </form>

        <div class="table-responsive" style="margin-top: 15px; background: white; padding: 10px; border-radius: 4px;">
            <?php
            // Define Row Class Callback
            $rowClassCallback = function ($doc) {
                $estado = $doc['estado_documento'] ?? 'DISPONIBLE';
                switch ($estado) {
                    case 'FALTA': return 'row-falta';
                    case 'PRESTADO': return 'row-prestado';
                    case 'ANULADO': return 'row-anulado';
                    case 'NO UTILIZADO': return 'row-no-utilizado';
                    case 'DISPONIBLE': return 'row-disponible';
                    default: return '';
                }
            };
            
            // Define Columns
            $columns = [
                [
                    'label' => 'Documento',
                    'field' => 'tipo_documento',
                    'sortable' => true
                ],
                [
                    'label' => 'Gestión',
                    'field' => 'gestion',
                    'sortable' => true
                ],
                [
                    'label' => 'Nro',
                    'field' => 'nro_comprobante',
                    'sortable' => true
                ],
                [
                    'label' => 'Contenedor',
                    'field' => 'contenedor',
                    'formatter' => function ($doc) {
                        if (!empty($doc['contenedor_numero'])) {
                            $abc = !empty($doc['contenedor_codigo_abc']) ? '.' . htmlspecialchars($doc['contenedor_codigo_abc']) : '';
                            return '<span class="badge badge-info">' . htmlspecialchars($doc['tipo_contenedor']) . ' #' . htmlspecialchars($doc['contenedor_numero']) . $abc . '</span>';
                        }
                        return 'Sin asignar';
                    }
                ],
                [
                    'label' => 'Estado',
                    'field' => 'estado_documento',
                    'formatter' => function ($doc) {
                         $est = $doc['estado_documento'];
                         $badgeClass = '';
                         $icon = '';
                         switch($est) {
                             case 'DISPONIBLE': $badgeClass = 'badge-disponible'; $icon = '🟢'; break;
                             case 'FALTA': $badgeClass = 'badge-falta'; $icon = '🔴'; break;
                             case 'PRESTADO': $badgeClass = 'badge-prestado'; $icon = '🔵'; break;
                             case 'ANULADO': $badgeClass = 'badge-anulado'; $icon = '🟣'; break;
                             case 'NO UTILIZADO': $badgeClass = 'badge-no-utilizado'; $icon = '🟡'; break;
                             default: $badgeClass = 'badge-secondary';
                         }
                         return '<span class="badge ' . $badgeClass . '">' . $icon . ' ' . $est . '</span>';
                    }
                ],
                [
                    'label' => 'Acción',
                    'formatter' => function ($doc) use ($prestamo, $filtros, $paginacion, $d_perPage) {
                        $encabezado_id = $prestamo['id'];
                        // Build hidden inputs for persistence
                        $hiddens = '';
                        $hiddens .= '<input type="hidden" name="search" value="' . htmlspecialchars($filtros['search']) . '">';
                        $hiddens .= '<input type="hidden" name="gestion" value="' . htmlspecialchars($filtros['gestion']) . '">';
                        $hiddens .= '<input type="hidden" name="tipo_documento" value="' . htmlspecialchars($filtros['tipo_documento']) . '">';
                        $hiddens .= '<input type="hidden" name="page" value="' . ($paginacion['page'] ?? 1) . '">';
                        
                        // New persistence params
                        $hiddens .= '<input type="hidden" name="per_page" value="' . ($paginacion['per_page'] ?? 10) . '">';
                        $hiddens .= '<input type="hidden" name="d_per_page" value="' . ($d_perPage ?? 10) . '">';
    
                        return '
                        <form action="/prestamos/agregarDetalle" method="POST" style="margin:0;">
                            <input type="hidden" name="encabezado_id" value="' . $encabezado_id . '">
                            <input type="hidden" name="documento_id" value="' . $doc['id'] . '">
                            ' . $hiddens . '
                            <button type="submit" class="btn btn-success btn-sm btn-agregar">➕ Agregar</button>
                        </form>';
                    }
                ]
            ];
            
            // Prepare rows for partial
            $rows = $documentosDisponibles;
            $modoLotes = false; // Disable batch checkboxes in this view
            
            include __DIR__ . '/../partials/table.php';
            ?>
        </div>
    </div>
</div>

<style>
.flex-between {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.required { color: red; }
.badge { background: #1B3C84; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; display: inline-block; }
.badge-info { background: #17a2b8; }
.badge-disponible { background: #28a745; } /* Verde */
.badge-falta { background: #dc3545; } /* Rojo */
.badge-prestado { background: #17a2b8; } /* Celeste */
.badge-no-utilizado { background: #ffc107; color: #333; } /* Amarillo */
.badge-anulado { background: #6f42c1; } /* Morado */
.table-sm td, .table-sm th { padding: 0.3rem; }

/* Botón Agregar Hover Amarillo */
.btn-agregar {
    transition: all 0.3s ease;
}
.btn-agregar:hover {
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
    color: #212529 !important;
}
</style>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
