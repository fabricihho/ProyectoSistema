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

        .signatures-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .section-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
            text-decoration: underline;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            /* Space for signature */
        }

        .signature-box {
            border-top: 1px solid #000;
            width: 40%;
            text-align: center;
            padding-top: 5px;
        }

        .observaciones-generales {
            margin-top: 20px;
            border: 1px solid #000;
            padding: 10px;
            min-height: 50px;
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
        <h2>ACTA DE PRÉSTAMO DE DOCUMENTOS</h2>
        <img src="/assets/img/logo-tamep.png" alt="Logo" class="logo">
        <p>Fecha: <?= date('d/m/Y', strtotime($prestamo['fecha_prestamo'])) ?></p>
    </div>

    <div class="info-box">
        <strong>SOLICITANTE:</strong> <?= htmlspecialchars($prestamo['nombre_prestatario'] ?? '') ?>
        (<?= htmlspecialchars($prestamo['unidad_nombre'] ?? '') ?>)<br>
        <strong>FECHA DEVOLUCIÓN ESTIMADA:</strong>
        <?= date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])) ?><br>
        <strong>OBSERVACIONES (SOLICITUD):</strong> <?= htmlspecialchars($prestamo['observaciones'] ?? 'Ninguna') ?>
    </div>

    <?php
    // Filter to show only principal documents (directly requested, not LIBRO-pulled)
    $detallesFiltrados = array_filter($detalles, function ($d) {
        return !isset($d['es_principal']) || $d['es_principal'] == 1;
    });
    ?>

    <table>
        <thead>
            <tr>
                <th rowspan="2">N°</th>
                <th rowspan="2">GESTION</th>
                <th rowspan="2">NRO COMPROBANTE</th>
                <th colspan="4">UBICACIÓN</th>
                <th rowspan="2">TIPO DOCUMENTO</th>
                <th rowspan="2" style="font-size: 14px; width: 30px;">☑</th>
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
            $conteoTiposEntregados = [];
            
            // Totales Amarros y Libros
            $conteoA_Sol = 0;
            $conteoA_Ent = 0;
            $conteoL_Sol = 0;
            $conteoL_Ent = 0;
            
            $totalSolicitados = count($detallesFiltrados);
            $totalEntregados = 0;

            foreach ($detallesFiltrados as $index => $doc):
                $docTipo = $doc['tipo_documento'] ?? 'OTROS';
                
                // Initialize counts
                if (!isset($conteoTipos[$docTipo])) $conteoTipos[$docTipo] = 0;
                if (!isset($conteoTiposEntregados[$docTipo])) $conteoTiposEntregados[$docTipo] = 0;

                $conteoTipos[$docTipo]++;

                // Restore variable definition
                $estadoDoc = $doc['estado_documento'] ?? '';
                
                // Determine if delivered (Must match "Prestados" logic from Controller)
                $estadoPrestamo = $doc['estado'] ?? ''; 
                $isEntregado = in_array($estadoPrestamo, ['Prestado', 'Devuelto']);

                if ($isEntregado) {
                    $totalEntregados++;
                    $conteoTiposEntregados[$docTipo]++;
                }

                // Container Logic
                $tipoContenedor = strtoupper($doc['tipo_contenedor'] ?? '');
                $isAmarro = (strpos($tipoContenedor, 'AMARRO') !== false);
                $isLibro = (strpos($tipoContenedor, 'LIBRO') !== false);

                if ($isAmarro) {
                    $conteoA_Sol++;
                    if ($isEntregado) $conteoA_Ent++;
                }
                if ($isLibro) {
                    $conteoL_Sol++;
                    if ($isEntregado) $conteoL_Ent++;
                }

                $obs = '';
                // Only show relevant negative states in observations
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
                    <td><?= htmlspecialchars($doc['ubicacion_nombre'] ?? $doc['ubicacion_fisica'] ?? '') ?></td>

                    <td class="text-left" style="font-size: 9px;"><?= htmlspecialchars($doc['tipo_documento'] ?? '') ?></td>
                    <td></td>
                    <td style="color: red; font-weight: bold;"><?= htmlspecialchars($obs) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f0f0f0;">
                <td colspan="3" style="text-align: right; font-weight: bold; border-bottom: none;">TOTALES SOLICITADOS: <span style="margin-left:5px"><?= $totalSolicitados ?></span></td>
                <td style="font-weight: bold; vertical-align: middle;"><?= $conteoA_Sol > 0 ? $conteoA_Sol : '' ?></td>
                <td style="font-weight: bold; vertical-align: middle;"><?= $conteoL_Sol > 0 ? $conteoL_Sol : '' ?></td>
                <td colspan="5"></td>
            </tr>
             <tr style="background-color: #f0f0f0;">
                <td colspan="3" style="text-align: right; font-weight: bold; border-top: none;">TOTALES ENTREGADOS: <span style="margin-left:5px"><?= $totalEntregados ?></span></td>
                <td style="font-weight: bold; vertical-align: middle;"><?= $conteoA_Ent > 0 ? $conteoA_Ent : '' ?></td>
                <td style="font-weight: bold; vertical-align: middle;"><?= $conteoL_Ent > 0 ? $conteoL_Ent : '' ?></td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
    </table>

    <div class="total-box">
        <?php foreach ($conteoTiposEntregados as $tipo => $cantidad): ?>
            <div>TOTAL <?= htmlspecialchars($tipo) ?> ENTREGADOS: <?= $cantidad ?></div>
        <?php endforeach; ?>
    </div>

    <!-- Primera Sección: Entrega -->
    <div class="signatures-section">
        <div class="section-title">ENTREGA DE DOCUMENTOS PRESTADOS</div>
        <div class="signatures">
            <div class="signature-box">
                Entrega Conforme<br>
                <strong><?= htmlspecialchars($prestamo['usuario_nombre'] ?? 'Archivo Central') ?></strong>
            </div>
            <div class="signature-box">
                Recibe Conforme<br>
                <strong><?= htmlspecialchars($prestamo['nombre_prestatario'] ?? '') ?></strong>
            </div>
        </div>

        <div class="observaciones-generales">
            <strong>Observaciones de Documentos (Entrega):</strong><br>
        </div>
    </div>

    <!-- Segunda Sección: Devolución -->
    <div class="signatures-section" style="margin-top: 60px;">
        <div class="section-title">DEVOLUCIÓN DE DOCUMENTOS</div>
        <div class="signatures">
            <div class="signature-box">
                Recibe Conforme (Devolución)<br>
                <strong><?= htmlspecialchars($prestamo['usuario_nombre'] ?? 'Archivo Central') ?></strong>
            </div>
            <div class="signature-box">
                Entrega Conforme (Devolución)<br>
                <strong><?= htmlspecialchars($prestamo['nombre_prestatario'] ?? '') ?></strong>
            </div>
        </div>

        <div class="observaciones-generales">
            <strong>Observaciones de Documentos (Devolución):</strong><br>
        </div>
    </div>

</body>

</html>