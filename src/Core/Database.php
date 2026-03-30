<?php
/**
 * Clase Database - Manejo de conexión PDO
 * 
 * @package TAMEP\Core
 */

namespace TAMEP\Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;
    
    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        
        try {
            $driver = $config['driver'] ?? 'mysql';
            
            $dsn = sprintf(
                "%s:host=%s;port=%d;dbname=%s",
                $driver,
                $config['host'],
                $config['port'],
                $config['database']
            );
            
            // Charset handling differs slightly between drivers in DSN or options, 
            // but usually valid in DSN for both pgsql and mysql (though pgsql prefers options or client_encoding).
            // For simplicity in this migration, appending it works for MySQL. 
            // Postgres ignores it in DSN usually but doesn't error.
            if ($driver === 'mysql') {
                $dsn .= ";charset=" . $config['charset'];
            }

            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
            
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * Ejecutar query con parámetros preparados
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener una fila
     */
    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Obtener todas las filas
     */
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener el ID del último insert
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }
}
