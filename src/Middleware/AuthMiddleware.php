<?php
/**
 * Middleware de Autenticación
 * 
 * @package TAMEP\Middleware
 */

namespace TAMEP\Middleware;

use TAMEP\Core\Session;
use TAMEP\Models\Usuario;

class AuthMiddleware
{
    public function handle()
    {
        // Security Fix: Prevent browser caching of protected pages
        // This ensures the back button doesn't show protected content after logout
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

        if (!Session::isAuthenticated()) {
            // Redirigir a login
            header('Location: /login');
            exit;
        }
        
        // Security Fix: Validate if user is still active in DB
        // This prevents access if the user was disabled while having an active session
        $sessionUser = Session::user();
        $userId = $sessionUser['id'] ?? null;
        
        if ($userId) {
            $usuarioModel = new Usuario();
            $user = $usuarioModel->find($userId);
            
            if (!$user || !$user['activo']) {
                // User is inactive or deleted -> Kill session
                Session::destroy();
                header('Location: /login?error=account_disabled');
                exit;
            }
        }
        
        return true;
    }
}
