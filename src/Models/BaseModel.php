<?php
/**
 * Clase BaseModel - Modelo base para todos los modelos
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

use TAMEP\Core\Database;

abstract class BaseModel
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener instancia de la base de datos
     */
    public function getDb()
    {
        return $this->db;
    }
    
    /**
     * Obtener todos los registros
     */
    public function all($limit = null, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($limit) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Buscar por ID
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Buscar por condición
     */
    public function where($conditions, $params = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions}";
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Buscar un solo registro
     */
    public function whereOne($conditions, $params = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions} LIMIT 1";
        return $this->db->fetchOne($sql, $params);
    }
    
    /**
     * Crear registro
     */
    public function create($data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $this->db->query($sql, array_values($data));
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar registro
     */
    public function update($id, $data)
    {
        $setClause = implode(', ', array_map(fn($key) => "{$key} = ?", array_keys($data)));
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
        
        $params = array_merge(array_values($data), [$id]);
        $this->db->query($sql, $params);
        
        return true;
    }
    
    /**
     * Eliminar registro
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $this->db->query($sql, [$id]);
        return true;
    }
    
    /**
     * Contar registros
     */
    public function count($conditions = '', $params = [])
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if ($conditions) {
            $sql .= " WHERE {$conditions}";
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return (int) $result['total'];
    }
}
