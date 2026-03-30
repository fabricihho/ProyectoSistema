<?php
/**
 * Middleware de Rol
 * 
 * @package TAMEP\Middleware
 */

namespace TAMEP\Middleware;

use TAMEP\Core\Session;

class RoleMiddleware
{
    private $requiredRole;
    
    public function __construct($role = null)
    {
        $this->requiredRole = $role;
    }
    
    public function handle()
    {
        if (!Session::isAuthenticated()) {
            return false;
        }
        
        $user = Session::user();
        
        if ($user['rol'] !== $this->requiredRole && $user['rol'] !== 'Administrador') {
            http_response_code(403);
            echo "Acceso denegado. Rol insuficiente.";
            exit;
        }
        
        return true;
    }
}
