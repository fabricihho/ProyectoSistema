<?php
// views/reportes/print_auditoria.php
$fechaImpresion = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Auditoría</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1B3C84;
            padding-bottom: 10px;
        }

        .header h1 {
            color: #1B3C84;
            margin: 0;
            font-size: 24px;
        }

        .meta {
            margin-bottom: 15px;
            font-size: 11px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            color: #1B3C84;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .detalles {
            font-family: monospace;
            font-size: 11px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()"
            style="padding: 8px 16px; background: #1B3C84; color: white; border: none; border-radius: 4px; cursor: pointer;">🖨️
            Imprimir</button>
        <button onclick="window.close()"
            style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px;">Cerrar</button>
    </div>

    <div class="header">
        <h1>Reporte de Auditoría y Seguimiento</h1>
        <div class="meta">Sistema de Gestión de Archivos TAMEP</div>
    </div>

    <div class="meta">
        <strong>Fecha de Reporte:</strong>
        <?= $fechaImpresion ?><br>
        <strong>Total Registros:</strong>
        <?= count($logs) ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Fecha</th>
                <th style="width: 15%;">Usuario</th>
                <th style="width: 10%;">Módulo</th>
                <th style="width: 10%;">Acción</th>
                <th style="width: 5%;">ID</th>
                <th style="width: 45%;">Detalles</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <?= date('d/m/Y H:i', strtotime($log['fecha'])) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($log['usuario_nombre'] ?? 'Sistema') ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($log['modulo']) ?>
                    </td>
                    <td><span style="font-weight: bold;">
                            <?= htmlspecialchars($log['accion']) ?>
                        </span></td>
                    <td>
                        <?= htmlspecialchars($log['registro_id']) ?>
                    </td>
                    <td class="detalles">
                        <?= htmlspecialchars($log['detalles']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div
        style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 10px; color: #999; text-align: center;">
        Generado automáticamente por el Sistema de Gestión de Documentos TAMEP
    </div>

    <script>
        // Auto-print on load if desired, but button is safer
        // window.onload = function() { window.print(); }
    </script>
</body>

</html>