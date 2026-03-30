<?php
require __DIR__ . '/../src/Core/Database.php';

use TAMEP\Core\Database;

// Fix: Use Singleton pattern
$db = Database::getInstance();

$nro = '100006';
$gestion = '2024';

echo "DEBUGGING DOCUMENTO: $nro / $gestion\n\n";

// 1. Check ALL rows matching this number
$sql = "SELECT id, nro_comprobante, gestion, tipo_documento, estado_documento, contenedor_fisico_id 
        FROM registro_diario 
        WHERE nro_comprobante = ? AND gestion = ?";
$rows = $db->fetchAll($sql, [$nro, $gestion]);

echo "FOUND " . count($rows) . " ROWS IN registro_diario:\n";
foreach ($rows as $row) {
    print_r($row);
    echo "-------------------\n";
}

// 2. Simulate PrestamosController Query Logic
echo "\nSIMULATING PRESTAMOS QUERY MATCH:\n";
$sql2 = "SELECT rd.id, rd.estado_documento, cf.tipo_contenedor, cf.numero as contenedor_numero
        FROM registro_diario rd
        LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
        WHERE rd.estado_documento IN ('DISPONIBLE', 'NO UTILIZADO', 'ANULADO', 'FALTA', 'PRESTADO')
        AND rd.nro_comprobante = ? 
        AND rd.gestion = ?";
$rows2 = $db->fetchAll($sql2, [$nro, $gestion]);

foreach ($rows2 as $row) {
    echo "Direct Query Result State: [" . $row['estado_documento'] . "]\n";
}
