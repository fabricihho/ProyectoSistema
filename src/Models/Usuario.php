<?php
/**
 * Modelo Usuario
 * Gestiona la tabla de usuarios del sistema
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class Usuario extends BaseModel
{
    protected $table = 'usuarios';
    protected $fillable = ['username', 'password_hash', 'nombre_completo', 'rol', 'activo'];
    
    /**
     * Buscar usuario por username
     */
    public function findByUsername($username)
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = ? LIMIT 1";
        return $this->db->fetchOne($sql, [$username]);
    }
    
    /**
     * Verificar credenciales de login
     */
    public function checkCredentials($username, $password)
    {
        $user = $this->findByUsername($username);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        return $user;
    }
    
    /**
     * Obtener usuarios activos
     */
    public function getActive()
    {
        $sql = "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY nombre_completo";
        return $this->db->fetchAll($sql);
    }
}
