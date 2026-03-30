<?php

namespace TAMEP\Controllers;

use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\Ubicacion;
use TAMEP\Models\TipoDocumento;
use TAMEP\Core\AuditLogger;

class ContenedoresController extends BaseController
{
    private $contenedorFisico;
    private $ubicacion;
    private $tipoDocumento;

    public function __construct()
    {
        parent::__construct();
        $this->contenedorFisico = new ContenedorFisico();
        $this->ubicacion = new Ubicacion();
        $this->tipoDocumento = new TipoDocumento();
    }

    public function index()
    {
        $this->requireAuth();

        // 1. Limpiar filtros explicitamente
        if (isset($_GET['clean'])) {
            unset($_SESSION['contenedores_filters']);
            $this->redirect('/contenedores');
            return;
        }

        // 2. Detectar nuevos filtros
        $hasFilters = isset($_GET['search']) || // Generic search param if added later
            isset($_GET['tipo_documento']) ||
            isset($_GET['numero']) ||
            isset($_GET['gestion']) ||
            isset($_GET['tipo_contenedor']) ||
            isset($_GET['ubicacion_id']) ||
            isset($_GET['sort']) ||
            isset($_GET['per_page']);

        if ($hasFilters) {
            $_SESSION['contenedores_filters'] = [
                'tipo_documento' => $_GET['tipo_documento'] ?? '',
                'numero' => $_GET['numero'] ?? '',
                'gestion' => $_GET['gestion'] ?? '',
                'tipo_contenedor' => $_GET['tipo_contenedor'] ?? '',
                'ubicacion_id' => $_GET['ubicacion_id'] ?? '',
                'sort' => $_GET['sort'] ?? '',
                'order' => $_GET['order'] ?? '',
                'per_page' => $_GET['per_page'] ?? 20
            ];
        } elseif (isset($_SESSION['contenedores_filters']) && empty($_GET['page'])) {
            // Restaurar sesión
            $params = http_build_query($_SESSION['contenedores_filters']);
            $this->redirect('/contenedores?' . $params);
            return;
        }

        // Params
        $tipo_documento = $_GET['tipo_documento'] ?? '';
        $numero = $_GET['numero'] ?? '';
        $gestion = $_GET['gestion'] ?? '';
        $tipo_contenedor = $_GET['tipo_contenedor'] ?? '';
        $ubicacion_id = $_GET['ubicacion_id'] ?? '';

        $sort = $_GET['sort'] ?? '';
        $order = $_GET['order'] ?? 'asc';
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        if ($perPage < 1)
            $perPage = 20;
        if ($perPage > 200)
            $perPage = 200;

        $filtros = [
            'tipo_documento' => $tipo_documento,
            'numero' => $numero,
            'gestion' => $gestion,
            'tipo_contenedor' => $tipo_contenedor,
            'ubicacion_id' => $ubicacion_id,
            'sort' => $sort,
            'order' => $order,
            'page' => $page,
            'per_page' => $perPage
        ];

        $contenedores = $this->contenedorFisico->buscar($filtros);
        $total = $this->contenedorFisico->contarBusqueda($filtros);

        $this->view('contenedores.index', [
            'contenedores' => $contenedores,
            'user' => $this->getCurrentUser(),
            'ubicaciones' => $this->ubicacion->getActive(),
            'tiposDocumento' => $this->tipoDocumento->getAllOrderedById(),
            'filtros' => $filtros,
            'paginacion' => [
                'current' => $page,
                'total' => $total,
                'per_page' => $perPage,
                'page' => $page,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
    }

    public function crear()
    {
        $this->requireAuth();
        $this->view('contenedores.crear', [
            'ubicaciones' => $this->ubicacion->getActive(),
            'tiposDocumento' => $this->tipoDocumento->getActive(),
            'user' => $this->getCurrentUser()
        ]);
    }

    public function guardar()
    {
        $this->requireAuth();

        $tipoId = $this->contenedorFisico->getTipoContenedorId($_POST['tipo_contenedor']);

        $data = [
            'tipo_contenedor_id' => $tipoId,
            'tipo_documento_id' => !empty($_POST['tipo_documento']) ? $_POST['tipo_documento'] : null,
            'numero' => $_POST['numero'],
            'ubicacion_id' => !empty($_POST['ubicacion_id']) ? $_POST['ubicacion_id'] : null,
            'codigo_abc' => $_POST['codigo_abc'] ?? null,
            'color' => $_POST['color'] ?? null,
            'bloque_nivel' => $_POST['bloque_nivel'] ?? null,
            'gestion' => $_POST['gestion'] ?? date('Y')
        ];

        if ($this->contenedorFisico->create($data)) {
            $id = $this->contenedorFisico->getDb()->lastInsertId();
            AuditLogger::log('CREAR', 'Contenedores', $id, "Se creó contenedor {$data['numero']} ({$_POST['tipo_contenedor']})");
            \TAMEP\Core\Session::flash('success', 'Contenedor creado exitosamente');
            $this->redirect('/contenedores');
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al crear contenedor');
            $this->redirect('/contenedores/crear');
        }
    }

    public function ver($id)
    {
        $this->requireAuth();
        $contenedor = $this->contenedorFisico->find($id);

        if (!$contenedor) {
            $this->redirect('/contenedores');
        }

        $documentos = $this->contenedorFisico->getDocumentos($id);

        // Cargar ubicación si existe
        if ($contenedor['ubicacion_id']) {
            $contenedor['ubicacion'] = $this->ubicacion->find($contenedor['ubicacion_id']);
        }

        $this->view('contenedores.ver', [
            'contenedor' => $contenedor,
            'documentos' => $documentos,
            'user' => $this->getCurrentUser()
        ]);
    }
    public function editar($id)
    {
        $this->requireAuth();
        $contenedor = $this->contenedorFisico->find($id);

        if (!$contenedor) {
            $this->redirect('/contenedores');
        }

        $documentos = $this->contenedorFisico->getDocumentos($id);

        $this->view('contenedores.editar', [
            'contenedor' => $contenedor,
            'ubicaciones' => $this->ubicacion->getActive(),
            'tiposDocumento' => $this->tipoDocumento->getActive(),
            'documentos' => $documentos,
            'user' => $this->getCurrentUser()
        ]);
    }

    public function actualizar($id)
    {
        $this->requireAuth();

        $tipoId = $this->contenedorFisico->getTipoContenedorId($_POST['tipo_contenedor']);

        $data = [
            'tipo_contenedor_id' => $tipoId,
            'tipo_documento_id' => !empty($_POST['tipo_documento']) ? $_POST['tipo_documento'] : null,
            'numero' => $_POST['numero'],
            'ubicacion_id' => !empty($_POST['ubicacion_id']) ? $_POST['ubicacion_id'] : null,
            'codigo_abc' => $_POST['codigo_abc'] ?? null,
            'color' => $_POST['color'] ?? null,
            'bloque_nivel' => $_POST['bloque_nivel'] ?? null,
            'gestion' => $_POST['gestion'] ?? null
        ];

        if ($this->contenedorFisico->update($id, $data)) {
            // Actualizar documentos contenidos (desvincular los desmarcados)
            $documentos_mantener = $_POST['documentos_ids'] ?? [];
            $this->contenedorFisico->actualizarContenido($id, $documentos_mantener);

            AuditLogger::log('EDITAR', 'Contenedores', $id, "Se actualizó contenedor {$data['numero']} ({$_POST['tipo_contenedor']})");
            \TAMEP\Core\Session::flash('success', 'Contenedor actualizado');
            $this->redirect('/contenedores');
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al actualizar');
            $this->redirect('/contenedores/editar/' . $id);
        }
    }

    public function actualizarUbicacionMasiva()
    {
        $this->requireAuth();

        $ids = $_POST['ids'] ?? [];
        $ubicacion_id = $_POST['ubicacion_id'] ?? null;

        if (empty($ids) || !$ubicacion_id) {
            \TAMEP\Core\Session::flash('error', 'Seleccione contenedores y una ubicación válida');
            $this->redirect('/contenedores');
            return;
        }

        $count = 0;
        foreach ($ids as $id) {
            if ($this->contenedorFisico->update($id, ['ubicacion_id' => $ubicacion_id])) {
                $count++;
            }
        }

        \TAMEP\Core\Session::flash('success', "Se actualizaron $count contenedores exitosamente.");
        AuditLogger::log('EDITAR_LOTE', 'Contenedores', null, "Se actualizó ubicación de $count contenedores.");
        $this->redirect('/contenedores');
    }

    public function eliminar($id)
    {
        $this->requireAuth();
        // Check availability logic if needed (isDisponible)

        if ($this->contenedorFisico->delete($id)) {
            AuditLogger::log('ELIMINAR', 'Contenedores', $id, "Se eliminó contenedor ID $id");
            \TAMEP\Core\Session::flash('success', 'Contenedor eliminado');
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al eliminar');
        }
        $this->redirect('/contenedores');
    }

    public function guardarRapido()
    {
        $this->requireAuth();

        // Handle JSON or Form data
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if (empty($input['tipo_contenedor']) || empty($input['numero'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
            exit;
        }

        $tipoId = $this->contenedorFisico->getTipoContenedorId($input['tipo_contenedor']);

        $data = [
            'tipo_contenedor_id' => $tipoId,
            'tipo_documento_id' => !empty($input['tipo_documento']) ? $input['tipo_documento'] : null,
            'numero' => $input['numero'],
            'codigo_abc' => !empty($input['codigo_abc']) ? $input['codigo_abc'] : null,
            'gestion' => $input['gestion'],
            'ubicacion_id' => !empty($input['ubicacion_id']) ? $input['ubicacion_id'] : null,
        ];

        // Check if container already exists
        $existingId = $this->contenedorFisico->existe($data);
        $force = isset($input['force']) && ($input['force'] === true || $input['force'] === 'true');

        if ($existingId && !$force) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'exists' => true, 
                'message' => 'Ese contenedor ya existe. ¿Desea crear uno nuevo de todos modos?'
            ]);
            exit;
        }

        try {
            $id = $this->contenedorFisico->create($data);

            if ($id) {
                // Fetch Code for Tipo Documento
                $tipoDocCodigo = '???';
                if (!empty($data['tipo_documento_id']) && $this->tipoDocumento) {
                    $td = $this->tipoDocumento->find($data['tipo_documento_id']);
                    if ($td) {
                        $tipoDocCodigo = $td['codigo'] ?? '???';
                    }
                }

                $abcPart = !empty($data['codigo_abc']) ? '.' . $data['codigo_abc'] : '';
                // Format: [DIA] 2024 AMARRO #3.ABC
                $displayText = "[{$tipoDocCodigo}] " . ($data['gestion'] ?? '') . " " . ($input['tipo_contenedor'] ?? '') . " #" . ($data['numero'] ?? '') . "{$abcPart}";

                AuditLogger::log('CREAR_RAPIDO', 'Contenedores', $id, "Creación rápida: $displayText");

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $id,
                        'text' => $displayText,
                        'ubicacion_id' => $data['ubicacion_id']
                    ]
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error al crear contenedor: La base de datos no devolvió un ID válido.']);
            }
        } catch (\Throwable $e) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            error_log("GUARDAR_RAPIDO FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            
            $message = 'Error crítico del servidor';
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = 'Ese contenedor ya existe.';
            } else {
                $message = 'Error Interno: ' . $e->getMessage() . " (" . basename($e->getFile()) . ":" . $e->getLine() . ")";
            }
            
            echo json_encode(['success' => false, 'message' => $message]);
        }
        exit;
    }

    /**
     * API para buscar contenedores (AJAX Autocomplete)
     */
    public function apiBuscar()
    {
        $this->requireAuth();

        $query = $_GET['q'] ?? '';

        if (strlen($query) < 1) {
            $this->json([]);
        }

        $resultados = $this->contenedorFisico->buscarRapida($query);

        $this->json($resultados);
    }

    public function apiCrearUbicacion()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            echo json_encode(['success' => false, 'error' => 'El nombre es obligatorio']);
            exit;
        }

        try {
            // Check if exists
            // Simple check: active locations with same name
            // Assuming DB has unique constraint or we don't care about dupes for now

            $id = $this->ubicacion->create([
                'nombre' => $nombre,
                'activo' => 1
            ]);

            if ($id) {
                echo json_encode(['success' => true, 'id' => $id, 'nombre' => $nombre]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al guardar en BD']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    private function getCurrentUser()
    {
        return \TAMEP\Core\Session::user();
    }
}
