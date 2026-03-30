<?php
/**
 * Controlador Dashboard
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\Documento;
use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\Prestamo;

class DashboardController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        
        // Obtener estadísticas
        $documento = new Documento();
        $contenedorFisico = new ContenedorFisico();
        $prestamo = new Prestamo();
        
        $stats = [
            'total_documentos' => $documento->count(),
            'total_contenedores' => $contenedorFisico->count(),
            'total_libros' => $contenedorFisico->count("tipo_contenedor_id = (SELECT id FROM tipos_contenedor WHERE codigo = 'LIBRO')"),
            'total_amarros' => $contenedorFisico->count("tipo_contenedor_id = (SELECT id FROM tipos_contenedor WHERE codigo = 'AMARRO')"),
            'prestamos_activos' => $prestamo->count("estado = 'Prestado'"), // Asumiendo que esta columna no cambió
            // Stats for Charts
            'docs_prestados' => $documento->count("estado_documento_id = (SELECT id FROM estados WHERE nombre = 'PRESTADO')"),
            'docs_faltantes' => $documento->count("estado_documento_id = (SELECT id FROM estados WHERE nombre = 'FALTA')"),
            'docs_disponibles' => $documento->count("estado_documento_id = (SELECT id FROM estados WHERE nombre = 'DISPONIBLE')"),
            'docs_no_utilizados' => $documento->count("estado_documento_id = (SELECT id FROM estados WHERE nombre = 'NO UTILIZADO')"),
            'docs_anulados' => $documento->count("estado_documento_id = (SELECT id FROM estados WHERE nombre = 'ANULADO')"),
        ];
        
        $this->view('inicio.index', [
            'stats' => $stats,
            'user' => $this->getCurrentUser()
        ]);
    }
    
    private function getCurrentUser()
    {
        return \TAMEP\Core\Session::user();
    }
}
