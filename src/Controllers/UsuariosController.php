<?php
/**
 * Controlador de Usuarios
 * Solo accesible para administradores
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\Usuario;
use TAMEP\Core\Session;
use TAMEP\Services\MailService;

class UsuariosController extends BaseController
{
    private $usuario;
    private $mailService;
    
    public function __construct()
    {
        parent::__construct();
        $this->usuario = new Usuario();
        $this->mailService = new MailService();
    }
    
    /**
     * Resetear contraseña de usuario (Solo Admin)
     */
    public function resetPassword($id)
    {
        $this->requireAuth();
        
        // Check Admin Role
        if (Session::user()['rol'] !== 'Administrador') {
            Session::flash('error', 'No tiene permisos para realizar esta acción');
            $this->redirect('/');
        }
        
        $user = $this->usuario->find($id);
        if (!$user) {
            Session::flash('error', 'Usuario no encontrado');
            $this->redirect('/admin/usuarios');
        }
        
        // Generate Random Password
        $newPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        
        // Update DB
        $data = [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ];
        
        if ($this->usuario->update($id, $data)) {
            // Send Email (Hardcoded recipient for testing as requested)
            // "enviar a un correo especifico... de momento urielm.cgutierrez001@gmail.com"
            $recipient = 'urielm.cgutierrez001@gmail.com';
            
            $sent = $this->mailService->sendPasswordReset($recipient, $user['username'], $newPassword);
            
            if ($sent) {
                Session::flash('success', "Contraseña reseteada correctamente. Se envió un correo a $recipient con la nueva clave.");
            } else {
                Session::flash('warning', "Contraseña reseteada, pero falló el envío del correo. (Revise logs)");
            }
        } else {
            Session::flash('error', 'Error al actualizar la contraseña en base de datos');
        }
        
        $this->redirect('/admin/usuarios');
    }
    
    /**
     * Verificar que el usuario sea administrador
     */
    private function requireAdmin()
    {
        $this->requireAuth();
        
        $user = Session::user();
        if ($user['rol'] !== 'Administrador') {
            Session::flash('error', 'No tiene permisos para acceder a esta sección');
            $this->redirect('/inicio');
        }
    }
    
    /**
     * Listar usuarios
     */
    public function index()
    {
        $this->requireAdmin();
        
        $usuarios = $this->usuario->all();
        
        $this->view('usuarios.index', [
            'usuarios' => $usuarios,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Mostrar formulario de creación
     */
    public function crear()
    {
        $this->requireAdmin();
        
        $this->view('usuarios.crear', [
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Guardar nuevo usuario
     */
    public function guardar()
    {
        $this->requireAdmin();
        
        // Validar datos requeridos
        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['nombre_completo']) || empty($_POST['rol'])) {
            Session::flash('error', 'Debe completar todos los campos obligatorios');
            $this->redirect('/admin/usuarios/crear');
        }
        
        // Verificar que el username no exista
        $existente = $this->usuario->findByUsername($_POST['username']);
        if ($existente) {
            Session::flash('error', 'El nombre de usuario ya existe');
            $this->redirect('/admin/usuarios/crear');
        }
        
        // Preparar datos
        $data = [
            'username' => $_POST['username'],
            'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'nombre_completo' => $_POST['nombre_completo'],
            'rol' => $_POST['rol'],
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        // Guardar
        $id = $this->usuario->create($data);
        
        if ($id) {
            Session::flash('success', 'Usuario creado exitosamente');
            $this->redirect('/admin/usuarios');
        } else {
            Session::flash('error', 'Error al crear el usuario');
            $this->redirect('/admin/usuarios/crear');
        }
    }
    
    /**
     * Mostrar formulario de edición
     */
    public function editar($id)
    {
        $this->requireAdmin();
        
        $usuario = $this->usuario->find($id);
        
        if (!$usuario) {
            Session::flash('error', 'Usuario no encontrado');
            $this->redirect('/admin/usuarios');
        }
        
        $this->view('usuarios.editar', [
            'usuario' => $usuario,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    /**
     * Actualizar usuario
     */
    public function actualizar($id)
    {
        $this->requireAdmin();
        
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
        
        $usuario = $this->usuario->find($id);
        
        if (!$usuario) {
            if ($isAjax) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
                exit;
            }
            Session::flash('error', 'Usuario no encontrado');
            $this->redirect('/admin/usuarios');
        }
        
        // Verificar que el username no exista en otro usuario
        if ($_POST['username'] !== $usuario['username']) {
            $existente = $this->usuario->findByUsername($_POST['username']);
            if ($existente) {
                if ($isAjax) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya existe']);
                    exit;
                }
                Session::flash('error', 'El nombre de usuario ya existe');
                $this->redirect('/admin/usuarios/editar/' . $id);
            }
        }
        
        // Preparar datos
        $data = [
            'username' => $_POST['username'],
            'nombre_completo' => $_POST['nombre_completo'],
            'rol' => $_POST['rol'],
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        // Actualizar contraseña solo si se proporcionó una nueva
        if (!empty($_POST['password'])) {
            $data['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        // Actualizar
        $success = $this->usuario->update($id, $data);
        
        if ($success) {
            if ($isAjax) {
                header('Content-Type: application/json');
                // Remove password hash from response
                unset($data['password_hash']);
                $data['id'] = $id; // Return ID
                echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente', 'user' => $data]);
                exit;
            }
            Session::flash('success', 'Usuario actualizado exitosamente');
            $this->redirect('/admin/usuarios');
        } else {
            if ($isAjax) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario']);
                exit;
            }
            Session::flash('error', 'Error al actualizar el usuario');
            $this->redirect('/admin/usuarios/editar/' . $id);
        }
    }
    
    /**
     * Eliminar usuario
     */
    public function eliminar($id)
    {
        $this->requireAdmin();
        
        $usuario = $this->usuario->find($id);
        
        if (!$usuario) {
            Session::flash('error', 'Usuario no encontrado');
            $this->redirect('/admin/usuarios');
        }
        
        // No permitir eliminar el usuario actual
        $currentUser = Session::user();
        if ($currentUser['id'] == $id) {
            Session::flash('error', 'No puede eliminar su propio usuario');
            $this->redirect('/admin/usuarios');
        }
        
        // Eliminar
        $success = $this->usuario->delete($id);
        
        if ($success) {
            Session::flash('success', 'Usuario eliminado exitosamente');
        } else {
            Session::flash('error', 'Error al eliminar el usuario');
        }
        
        $this->redirect('/admin/usuarios');
    }
    
    private function getCurrentUser()
    {
        return Session::user();
    }
}
