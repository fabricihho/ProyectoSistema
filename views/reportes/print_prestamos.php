<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Préstamos Seleccionados</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .footer { margin-top: 30px; font-size: 10px; text-align: right; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">Imprimir / Guardar como PDF</button>
        <button onclick="window.close()">Cerrar</button>
    </div>

    <div class="header">
        <h2>Reporte de Préstamos Seleccionados</h2>
        <p>Generado el: <?= date('d/m/Y H:i') ?></p>
        <p>Usuario: <?= \TAMEP\Core\Session::user()['nombre_completo'] ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Documento</th>
                <th>Comprobante</th>
                <th>Gestión</th>
                <th>Contenedor</th>
                <th>Fecha Préstamo</th>
                <th>Devolución Estimada</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($prestamos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['usuario_nombre']) ?></td>
                    <td><?= htmlspecialchars($p['tipo_documento']) ?></td>
                    <td><?= htmlspecialchars($p['nro_comprobante']) ?></td>
                    <td><?= htmlspecialchars($p['gestion']) ?></td>
                    <td><?= htmlspecialchars($p['tipo_contenedor']) ?> #<?= htmlspecialchars($p['contenedor_numero']) ?></td>
                    <td><?= date('d/m/Y', strtotime($p['fecha_prestamo'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($p['fecha_devolucion_esperada'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Sistema de Gestión de Archivos TAMEP
    </div>
</body>
</html>
