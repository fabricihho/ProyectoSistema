<?php
/**
 * WriteAccessMiddleware
 * Denies access to modification routes for Read-Only roles.
 * 
 * @package TAMEP\Middleware
 */

namespace TAMEP\Middleware;

use TAMEP\Core\Session;

class WriteAccessMiddleware
{
    public function handle()
    {
        // 1. Ensure Auth (should be covered by AuthMiddleware, but double check)
        if (!Session::isAuthenticated()) {
            http_response_code(401);
            exit('Unauthorized');
        }
        
        $user = Session::user();
        $role = $user['rol'] ?? 'Consulta'; // Default to safest role if missing
        
        // 2. Define Restricted Roles
        // Roles that CANNOT perform write operations
        $readOnlyRoles = ['Consulta'];
        
        if (in_array($role, $readOnlyRoles)) {
            // Log access attempt if needed
            // error_log("Security: Blocked write access for user {$user['username']} with role {$role}");
            
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Acceso Denegado: Su rol solo permite lectura.']);
            } else {
                // Determine redirect or error page
                // Ideally redirect to a safe page with flash message, but for Middleware exit is safer
                http_response_code(403);
                // Simple Error Page
                echo "
                <div style='font-family: sans-serif; text-align: center; padding: 50px;'>
                    <h1 style='color: #e53e3e;'>⚠️ Acceso Restringido</h1>
                    <p>Su rol (<strong>{$role}</strong>) no tiene permisos para realizar modificaciones en el sistema.</p>
                    <a href='javascript:history.back()' style='color: #3182ce;'>&larr; Volver</a>
                </div>
                ";
            }
            exit;
        }
        
        return true;
    }
    
    private function isAjax()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }
}
