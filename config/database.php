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
    'host' => bocxko2tisgkawnthb4r-mysql.services.clever-cloud.com,
    'port' => getenv('MYSQL_ADDON_PORT') ?: 3306,
    'database' => bocxko2tisgkawnthb4r,
    'username' => uqkwnvea8ct8mk4n',
    'password' => 7RElxdgQv2fp4Pe7QcfR,
    'charset' => 'utf8mb4',

    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
