<?php
/**
 * Clase Session - Manejo de sesiones
 * 
 * @package TAMEP\Core
 */

namespace TAMEP\Core;

class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../config/app.php';
            
            session_name($config['session']['name']);
            session_set_cookie_params([
                'lifetime' => $config['session']['lifetime'],
                'path' => '/',
                'secure' => $config['session']['secure'],
                'httponly' => $config['session']['httponly'],
                'samesite' => 'Strict'
            ]);
            
            session_start();
        }
    }
    
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key)
    {
        unset($_SESSION[$key]);
    }
    
    public static function destroy()
    {
        session_destroy();
        $_SESSION = [];
    }
    
    public static function flash($key, $value = null)
    {
        if ($value === null) {
            $data = self::get($key);
            self::remove($key);
            return $data;
        }
        
        self::set($key, $value);
    }
    
    public static function isAuthenticated()
    {
        return self::has('user_id');
    }
    
    public static function user()
    {
        return self::get('user');
    }
    
    public static function hasRole($role)
    {
        $user = self::user();
        return $user && $user['rol'] === $role;
    }
}
