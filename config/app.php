<?php
/**
 * Configuración General de la Aplicación
 * Sistema TAMEP - Gestión Documental
 */

return [
    'app_name' => 'Sistema TAMEP',
    'app_url' => '', // Vacío para rutas relativas correctas
    'base_path' => dirname(__DIR__),
    'timezone' => 'America/La_Paz',
    
    // Colores institucionales TAMEP
    'colors' => [
        'primary' => '#1B3C84',      // Azul TAMEP
        'secondary' => '#FFD100',    // Amarillo TAMEP
        'dark' => '#142D66',         // Azul oscuro
        'light' => '#F5F7FA',        // Gris claro
    ],
    
    // Configuración de sesión
    'session' => [
        'name' => 'TAMEP_SESSION',
        'lifetime' => 7200, // 2 horas
        'secure' => false,   // true en producción
        'httponly' => true,
    ],
    
    // Roles del sistema
    'roles' => [
        'admin' => 'Administrador',
        'usuario' => 'Usuario',
        'consulta' => 'Consulta',
    ],
];
