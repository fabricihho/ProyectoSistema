<?php
/**
 * Controlador de autenticación
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\User;
use TAMEP\Core\Session;

class AuthController extends BaseController
{
    private $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }
    
    /**
     * Mostrar formulario de login
     */
    public function showLogin()
    {
        if (Session::isAuthenticated()) {
            $this->redirect('/inicio');
        }
        
        $savedUsername = $_COOKIE['remember_user'] ?? '';
        $savedPassword = '';
        
        // Decrypt password if cookie exists
        if (isset($_COOKIE['remember_password'])) {
            $savedPassword = openssl_decrypt($_COOKIE['remember_password'], 'AES-128-ECB', 'TAMEP_ECO_KEY_2024');
        }
        
        $this->view('auth.login', [
            'csrf_token' => $this->csrf(),
            'error' => Session::flash('error'),
            'success' => Session::flash('success'),
            'saved_username' => $savedUsername,
            'saved_password' => $savedPassword
        ]);
    }
    
    /**
     * Procesar login
     */
    public function login()
    {
        $username = $this->input('username');
        $password = $this->input('password');
        
        if (!$username || !$password) {
            Session::flash('error', 'Usuario y contraseña son requeridos');
            $this->redirect('/login');
        }
        
        $user = $this->userModel->authenticate($username, $password);
        
        if ($user) {
            // Guardar en sesión
            Session::set('user_id', $user['id']);
            Session::set('user', [
                'id' => $user['id'],
                'username' => $user['username'],
                'nombre_completo' => $user['nombre_completo'],
                'rol' => $user['rol']
            ]);
            
            // Logic for "Remember Me" (Autofill Username & Password)
            if ($this->input('remember')) {
                // Save username
                setcookie('remember_user', $username, time() + (30 * 24 * 60 * 60), "/");
                
                // Save encrypted password (WARNING: Storing passwords in cookies has risks, using encryption helps)
                $encryptedPass = openssl_encrypt($password, 'AES-128-ECB', 'TAMEP_ECO_KEY_2024');
                setcookie('remember_password', $encryptedPass, time() + (30 * 24 * 60 * 60), "/");
            } else {
                // Remove cookies if unchecked
                if (isset($_COOKIE['remember_user'])) {
                    setcookie('remember_user', '', time() - 3600, "/");
                }
                if (isset($_COOKIE['remember_password'])) {
                    setcookie('remember_password', '', time() - 3600, "/");
                }
            }
            
            $this->redirect('/inicio');
        } else {
            Session::flash('error', 'Credenciales inválidas');
            $this->redirect('/login');
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout()
    {
        Session::destroy();
        $this->redirect('/login');
    }
}
