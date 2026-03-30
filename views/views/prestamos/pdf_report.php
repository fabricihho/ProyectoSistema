<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Préstamo #<?= $prestamo['id'] ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            position: relative; /* For absolute logo positioning */
        }
        .logo {
            position: absolute;
            top: -15px;
            right: 0;
            width: 60px; /* Small size as requested */
            height: auto;
        }
        .info-box {
            margin-bottom: 20px;
            border: 1px solid #000;
            padding: 10px;
            background: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 10px;
        }
        th {
            background-color: #e3e3e3;
            font-weight: bold;
        }
        .text-left {
             text-align: left;
        }
        @media print {
            .no-print {
                display: none;
            }
            body { 
                padding: 0; 
                -webkit-print-color-adjust: exact; 
            }
            th {
                background-color: #e3e3e3 !important;
            }
        }
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            border-top: 1px solid #000;
            width: 40%;
            text-align: center;
            padding-top: 5px;
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">🖨️ Imprimir</button>
        <button onclick="window.close()">✖ Cerrar</button>
    </div>

    <div class="header">
        <h2>ACTA DE PRÉSTAMO DE DOCUMENTOS</h2>
        <img src="/assets/img/logo-tamep.png" alt="Logo" class="logo">
        <p>Fecha: <?= date('d/m/Y', strtotime($prestamo['fecha_prestamo'])) ?> | ID Préstamo: <?= $prestamo['id'] ?></p>
    </div>

    <div class="info-box">
        <strong>SOLICITANTE:</strong> <?= htmlspecialchars($prestamo['usuario_nombre']) ?><br>
        <strong>FECHA DEVOLUCIÓN ESTIMADA:</strong> <?= date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])) ?><br>
        <strong>OBSERVACIONES:</strong> <?= htmlspecialchars($prestamo['observaciones'] ?? 'Ninguna') ?>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">N°</th>
                <th rowspan="2">GESTION</th>
                <th rowspan="2">NRO COMPROBANTE</th>
                <th colspan="3">UBICACIÓN</th>
                <th rowspan="2">TIPO DOCUMENTO</th>
                <th rowspan="2">OBSERVACIONES</th>
            </tr>
            <tr>
                <th>CONTENEDOR</th>
                <th>NRO</th>
                <th>UBICACIÓN</th>
                <th>TIPO</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $index => $doc): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($doc['gestion'] ?? '') ?></td>
                <td><?= htmlspecialchars($doc['nro_comprobante'] ?? '-') ?></td>
                <td><?= htmlspecialchars($doc['tipo_contenedor'] ?? '-') ?></td>
                <td><?= htmlspecialchars($doc['contenedor_numero'] ?? '-') ?></td>
                <td><?= htmlspecialchars($doc['ubicacion_fisica'] ?? '') ?></td>
                <td>FISICO</td>
                <td class="text-left"><?= htmlspecialchars($doc['tipo_documento'] ?? '') ?></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-box">
            Entrega Conforme<br>
            <strong>Archivo Central</strong>
        </div>
        <div class="signature-box">
            Recibe Conforme<br>
            <strong><?= htmlspecialchars($prestamo['usuario_nombre']) ?></strong>
        </div>
    </div>

</body>
</html>
