<?php
/**
 * Punto de entrada raíz - Sistema TAMEP
 * Redirige a public/index.php
 */

// Cambiar al directorio public
chdir(__DIR__ . '/public');

// Incluir el index.php del public
require __DIR__ . '/public/index.php';
