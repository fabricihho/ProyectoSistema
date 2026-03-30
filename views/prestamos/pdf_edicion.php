<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Guía de Búsqueda - Préstamo #<?= $prestamo['id'] ?></title>
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
           position: relative;
        }

        .logo {
            position: absolute;
            top: -15px;
            right: 0;
            width: 60px;
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

        th,
        td {
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

        .contenedores-section {
            margin-top: 30px;
            border: 2px solid #000;
            padding: 15px;
            background: #fffacd;
        }

        .contenedores-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 10px;
            text-align: center;
            text-decoration: underline;
        }

        .total-box {
            margin-top: 10px;
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>

<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">🖨️ Imprimir</button>
        <button onclick="window.close()">✖ Cerrar</button>
    </div>

    <div class="header">
        <h2>GUÍA DE BÚSQUEDA DE DOCUMENTOS</h2>
        <img src="/assets/img/logo-tamep.png" alt="Logo" class="logo">
        <p>Préstamo #<?= $prestamo['id'] ?> - Fecha: <?= date('d/m/Y', strtotime($prestamo['fecha_prestamo'])) ?></p>
    </div>

    <div class="info-box">
        <strong>SOLICITANTE:</strong> <?= htmlspecialchars($prestamo['nombre_prestatario'] ?? '') ?>
        (<?= htmlspecialchars($prestamo['unidad_nombre'] ?? '') ?>)<br>
        <strong>FECHA DEVOLUCIÓN ESTIMADA:</strong>
        <?= date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])) ?><br>
        <strong>OBSERVACIONES:</strong> <?= htmlspecialchars($prestamo['observaciones'] ?? 'Ninguna') ?>
    </div>

    <?php
    // Filter to show only principal documents
    $detallesFiltrados = array_filter($detalles, function ($d) {
        return !isset($d['es_principal']) || $d['es_principal'] == 1;
    });
    ?>

    <h3 style="margin-top: 20px; margin-bottom: 10px;">📋 DOCUMENTOS SOLICITADOS</h3>

    <table>
        <thead>
            <tr>
                <th rowspan="2">N°</th>
                <th rowspan="2">GESTIÓN</th>
                <th rowspan="2">NRO COMPROBANTE</th>
                <th colspan="4">UBICACIÓN</th>
                <th rowspan="2">TIPO DOCUMENTO</th>
                <th rowspan="2">OBSERVACIONES</th>
            </tr>
            <tr>
                <th colspan="2">CONTENEDOR</th>
                <th>NRO</th>
                <th>UBICACIÓN</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $conteoTipos = [];
            $conteoA = 0;
            $conteoL = 0;

            foreach ($detallesFiltrados as $index => $doc):
                // Document Count by Type
                $docTipo = $doc['tipo_documento'] ?? 'OTROS';
                if (!isset($conteoTipos[$docTipo])) {
                    $conteoTipos[$docTipo] = 0;
                }
                $conteoTipos[$docTipo]++;

                // Container Logic
                $tipoContenedor = strtoupper($doc['tipo_contenedor'] ?? '');
                $isAmarro = (strpos($tipoContenedor, 'AMARRO') !== false);
                $isLibro = (strpos($tipoContenedor, 'LIBRO') !== false);

                if ($isAmarro)
                    $conteoA++;
                if ($isLibro)
                    $conteoL++;

                $estadoDoc = $doc['estado_documento'] ?? '';
                $obs = '';
                if (in_array($estadoDoc, ['FALTA', 'ANULADO', 'NO UTILIZADO'])) {
                    $obs = $estadoDoc;
                }
                ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($doc['gestion'] ?? '') ?></td>
                    <td><?= htmlspecialchars(($doc['nro_comprobante'] ?? '-') . (!empty($doc['codigo_abc']) ? '-' . $doc['codigo_abc'] : '')) ?>
                    </td>

                    <!-- Contenedor Split -->
                    <td style="width: 20px;"><?= $isAmarro ? 'A' : '' ?></td>
                    <td style="width: 20px;"><?= $isLibro ? 'L' : '' ?></td>

                    <td><?= htmlspecialchars(($doc['contenedor_numero'] ?? '-') . (!empty($doc['contenedor_codigo_abc']) ? '.' . $doc['contenedor_codigo_abc'] : '')) ?>
                    </td>
                    <td><?= htmlspecialchars($doc['ubicacion_fisica'] ?? '') ?></td>

                    <td class="text-left" style="font-size: 9px;"><?= htmlspecialchars($doc['tipo_documento'] ?? '') ?></td>
                    <td style="color: red; font-weight: bold;"><?= htmlspecialchars($obs) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f0f0f0;">
                <td colspan="3" style="text-align: right; font-weight: bold;">TOTALES:</td>
                <td style="font-weight: bold;"><?= $conteoA > 0 ? $conteoA : '' ?></td>
                <td style="font-weight: bold;"><?= $conteoL > 0 ? $conteoL : '' ?></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>

    <div class="total-box">
        <?php foreach ($conteoTipos as $tipo => $cantidad): ?>
            <div>TOTAL <?= htmlspecialchars($tipo) ?>: <?= $cantidad ?></div>
        <?php endforeach; ?>
    </div>

    <!-- SECCIÓN DE CONTENEDORES A BUSCAR -->
    <div class="contenedores-section">
        <div class="contenedores-title">📦 CONTENEDORES A BUSCAR (<?= count($contenedores) ?> contenedores)</div>
        
        <table style="margin-top: 10px;">
            <thead>
                <tr>
                    <th style="width: 30px;">N°</th>
                    <th>TIPO</th>
                    <th>NÚMERO</th>
                    <th>UBICACIÓN FÍSICA</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contenedores as $idx => $cont): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><?= htmlspecialchars($cont['tipo_contenedor'] ?? 'N/A') ?></td>
                        <td><strong><?= htmlspecialchars(($cont['contenedor_numero'] ?? '') . (!empty($cont['contenedor_codigo_abc']) ? '.' . $cont['contenedor_codigo_abc'] : '')) ?></strong></td>
                        <td class="text-left" style="padding-left: 10px;"><?= htmlspecialchars($cont['ubicacion_fisica'] ?? 'Sin ubicación') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top: 15px; font-size: 10px; font-style: italic;">
            <strong>Nota:</strong> Esta lista muestra todos los contenedores donde se encuentran los documentos solicitados.
            Use esta guía para localizar físicamente los documentos en el archivo.
        </p>
    </div>

</body>

</html>
