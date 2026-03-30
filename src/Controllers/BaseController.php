<?php
/**
 * Clase BaseController - Controlador base
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Core\Session;

abstract class BaseController
{
    protected $config;
    
    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        Session::start();
    }
    
    /**
     * Renderizar vista
     */
    protected function view($view, $data = [])
    {
        extract($data);
        extract(['config' => $this->config]);
        
        $viewPath = __DIR__ . '/../../views/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            die("Vista no encontrada: {$view}");
        }
        
        require $viewPath;
    }
    
    /**
     * Redirigir
     */
    protected function redirect($path)
    {
        header("Location: {$this->config['app_url']}{$path}");
        exit;
    }
    
    /**
     * Retornar JSON
     */
    protected function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Verificar autenticación
     */
    protected function requireAuth()
    {
        if (!Session::isAuthenticated()) {
            $this->redirect('/login');
        }
    }
    
    /**
     * Verificar rol
     */
    protected function requireRole($role)
    {
        $this->requireAuth();
        
        if (!Session::hasRole($role)) {
            http_response_code(403);
            die("Acceso denegado");
        }
    }
    
    /**
     * Obtener datos POST
     */
    protected function input($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Validar CSRF token
     */
    protected function verifyCsrf()
    {
        $token = $this->input('_csrf_token');
        $sessionToken = Session::get('csrf_token');
        
        if (!$token || !$sessionToken || $token !== $sessionToken) {
            http_response_code(403);
            die("Token CSRF inválido");
        }
    }
    
    /**
     * Generar CSRF token
     */
    protected function csrf()
    {
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        
        return Session::get('csrf_token');
    }
}
