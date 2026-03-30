<?php
/**
 * Modelo User
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class User extends BaseModel
{
    protected $table = 'usuarios';
    
    /**
     * Buscar usuario por username
     */
    public function findByUsername($username)
    {
        return $this->whereOne('username = ?', [$username]);
    }
    
    /**
     * Verificar credenciales
     */
    public function authenticate($username, $password)
    {
        $user = $this->findByUsername($username);
        
        if (!$user || !$user['activo']) {
            return false;
        }
        
        // Verificar password (asumiendo password_hash de PHP)
        if (password_verify($password, $user['password_hash'])) {
            // Actualizar último acceso
            $this->update($user['id'], [
                'ultimo_acceso' => date('Y-m-d H:i:s')
            ]);
            
            return $user;
        }
        
        return false;
    }
    
    /**
     * Crear usuario con password hasheado
     */
    public function createUser($data)
    {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        
        return $this->create($data);
    }
}
