<?php
/**
 * Autoloader PSR-4
 */

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function ($class) {
    // Namespace base del proyecto
    $prefix = 'TAMEP\\';
    
    // Directorio base para el namespace
    $base_dir = __DIR__ . '/../src/';
    
    // Verificar si la clase usa el namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Obtener el nombre relativo de la clase
    $relative_class = substr($class, $len);
    
    // Reemplazar el namespace prefix con el directorio base
    // y convertir los \ a /
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // Si el archivo existe, requerirlo
    if (file_exists($file)) {
        require $file;
    }
});
