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

<!-- SECCIÓN 1: Préstamos Activos -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h2>📤 Préstamos Activos</h2>
        <div class="header-actions">
            <button onclick="imprimirSeccion('prestamos')" class="btn btn-secondary">🖨️ Imprimir</button>
            <button onclick="exportarExcel('prestamos')" class="btn btn-success">📊 Excel</button>
        </div>
    </div>
    
    <div class="table-responsive" id="tabla-prestamos">
        <table class="table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Área/Rol</th>
                    <th>Documento</th>
                    <th>Gestión</th>
                    <th>Contenedor</th>
                    <th>Fecha Préstamo</th>
                    <th>Devolución Est.</th>
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
                                    <?= htmlspecialchars($pres['tipo_contenedor']) ?> #<?= htmlspecialchars($pres['contenedor_numero']) ?>
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
</div>

<!-- SECCIÓN 2: Documentos No Disponibles -->
<div class="card" style="margin-top: 30px;">
    <div class="card-header">
        <h2>⚠️ Documentos No Disponibles para Préstamo</h2>
        <div class="header-actions">
            <button onclick="imprimirSeccion('no-disponibles')" class="btn btn-secondary">🖨️ Imprimir</button>
            <button onclick="exportarExcel('no-disponibles')" class="btn btn-success">📊 Excel</button>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="filtros-rapidos" style="padding: 15px; border-bottom: 1px solid #e2e8f0;">
        <label style="margin-right: 15px;">
            <input type="checkbox" checked onchange="filtrarEstado('PRESTADO')"> 
            <span class="badge badge-prestado">Prestados</span>
        </label>
        <label style="margin-right: 15px;">
            <input type="checkbox" checked onchange="filtrarEstado('FALTA')"> 
            <span class="badge badge-falta">Faltantes</span>
        </label>
        <label>
            <input type="checkbox" checked onchange="filtrarEstado('ANULADO')"> 
            <span class="badge badge-anulado">Anulados</span>
        </label>
    </div>
    
    <div class="table-responsive" id="tabla-no-disponibles">
        <table class="table">
            <thead>
                <tr>
                    <th>Tipo Documento</th>
                    <th>Gestión</th>
                    <th>Nro Comprobante</th>
                    <th>Contenedor</th>
                    <th>Estado</th>
                    <th>Motivo/Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documentosNoDisponibles)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Todos los documentos están disponibles</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documentosNoDisponibles as $doc): ?>
                        <tr data-estado="<?= $doc['estado_documento'] ?>">
                            <td><?= htmlspecialchars($doc['tipo_documento'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($doc['gestion'] ?? 'N/A') ?></td>
                            <td><strong><?= htmlspecialchars($doc['nro_comprobante'] ?? 'N/A') ?></strong></td>
                            <td>
                                <?php if ($doc['tipo_contenedor']): ?>
                                    <?= htmlspecialchars($doc['tipo_contenedor']) ?> #<?= htmlspecialchars($doc['contenedor_numero']) ?>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($doc['estado_documento'] === 'PRESTADO'): ?>
                                    <span class="badge badge-prestado">🔵 Prestado</span>
                                <?php elseif ($doc['estado_documento'] === 'FALTA'): ?>
                                    <span class="badge badge-falta">🔴 Falta</span>
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
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-blue { border-left-color: #3182CE; }
.stat-red { border-left-color: #E53E3E; }
.stat-yellow { border-left-color: #FFD100; }
.stat-gray { border-left-color: #718096; }

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

.filtros-rapidos label {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

@media print {
    .btn, .header-actions, .filtros-rapidos {
        display: none !important;
    }
}
</style>

<script>
let estadosFiltro = {
    'PRESTADO': true,
    'FALTA': true,
    'ANULADO': true
};

function filtrarEstado(estado) {
    estadosFiltro[estado] = !estadosFiltro[estado];
    const rows = document.querySelectorAll('#tabla-no-disponibles tbody tr[data-estado]');
    
    rows.forEach(row => {
        const estadoRow = row.getAttribute('data-estado');
        row.style.display = estadosFiltro[estadoRow] ? '' : 'none';
    });
}

function imprimirSeccion(seccion) {
    const tabla = document.getElementById(`tabla-${seccion}`);
    const ventana = window.open('', '_blank');
    ventana.document.write('<html><head><title>Reporte TAMEP</title>');
    ventana.document.write('<style>table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background:#1B3C84;color:white;}</style>');
    ventana.document.write('</head><body>');
    ventana.document.write('<h1>Sistema TAMEP - Reporte</h1>');
    ventana.document.write(tabla.innerHTML);
    ventana.document.write('</body></html>');
    ventana.document.close();
    ventana.print();
}

function exportarExcel(seccion) {
    alert('Función de exportación a Excel en desarrollo.\nEn producción se usará PhpSpreadsheet.');
}
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
