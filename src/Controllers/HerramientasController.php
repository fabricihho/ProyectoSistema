<?php
/**
 * Controlador de Herramientas
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

class HerramientasController extends BaseController
{
    /**
     * Mostrar vista de Control de Amarros
     */
    public function controlAmarros()
    {
        $this->requireAuth();
        
        $this->view('herramientas.control_amarros', [
            'user' => $this->getCurrentUser()
        ]);
    }

    /**
     * Juego de Varita Mágica (Solo para VIVI)
     */
    public function varitaMagica()
    {
        $this->requireAuth();
        $user = $this->getCurrentUser();
        
        // Verificar restricción de usuario (case insensitive)
        if (strtoupper($user['username']) !== 'VIVI') {
            \TAMEP\Core\Session::flash('error', 'No tienes permiso para ver esta magia ✨');
            $this->redirect('/inicio');
        }
        
        $this->view('herramientas.varita_magica', [
            'user' => $user
        ]);
    }

    private function getCurrentUser()
    {
        return \TAMEP\Core\Session::user();
    }
}
