<?php

return [
    // --- CONEXIÓN SUPABASE (COPIA EN NUBE) ---
    /*
    'driver' => 'pgsql',
    'host' => 'aws-1-us-east-1.pooler.supabase.com',
    'port' => 6543,
    'database' => 'postgres',
    'username' => 'postgres.sdovwowdbuzjfwtgnfoa',
    'password' => 'pasantiatam123',
    'charset' => 'utf8',
    */
    
    // --- CONEXIÓN LOCAL (ACTIVA) ---
    'driver' => 'mysql',
    'host' => getenv('MYSQL_ADDON_HOST') ?: 'localhost',
    'port' => getenv('MYSQL_ADDON_PORT') ?: 3306,
    'database' => getenv('MYSQL_ADDON_DB') ?: 'tamep_archivos',
    'username' => getenv('MYSQL_ADDON_USER') ?: 'root',
    'password' => getenv('MYSQL_ADDON_PASSWORD') ?: '',
    'charset' => 'utf8mb4',

    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];