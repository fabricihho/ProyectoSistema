<?php
/**
 * Controlador de Catalogación
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\Documento;
use TAMEP\Models\Ubicacion;
use TAMEP\Models\UnidadArea;
use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\TipoDocumento;
use TAMEP\Core\AuditLogger;
// use TAMEP\Models\HojaRuta; // Deprecated

class CatalogacionController extends BaseController
{
    private $documento;
    private $ubicacion;
    private $unidadArea;
    private $contenedorFisico;
    private $tipoDocumento;
    // private $hojaRuta;

    public function __construct()
    {
        parent::__construct();
        $this->documento = new Documento();
        $this->ubicacion = new Ubicacion();
        $this->unidadArea = new UnidadArea();
        $this->contenedorFisico = new ContenedorFisico();
        $this->tipoDocumento = new TipoDocumento();
        // $this->hojaRuta = new HojaRuta();
    }

    /**
     * Mostrar listado y búsqueda de documentos
     */
    public function index()
    {
        $this->requireAuth();

        // 1. Limpiar filtros si se solicita explícitamente y limpiar sesión
        if (isset($_GET['clean'])) {
            unset($_SESSION['catalogacion_filters']);
            $modoLotes = isset($_GET['modo_lotes']) ? '?modo_lotes=1' : '';
            $this->redirect('/catalogacion' . $modoLotes);
            return;
        }

        // 2. Detectar si hay nuevos filtros en $_GET (Búsqueda activa)
        // Verificamos si hay algún parámetro de búsqueda presente
        $hasFilters = isset($_GET['search']) || isset($_GET['gestion']) || isset($_GET['ubicacion_id']) ||
            isset($_GET['estado_documento']) || isset($_GET['tipo_documento']) ||
            isset($_GET['sort']) || isset($_GET['per_page']);

        if ($hasFilters) {
            // Guardar filtros en sesión
            $_SESSION['catalogacion_filters'] = [
                'search' => $_GET['search'] ?? '',
                'gestion' => $_GET['gestion'] ?? '',
                'ubicacion_id' => $_GET['ubicacion_id'] ?? '',
                'estado_documento' => $_GET['estado_documento'] ?? '',
                'tipo_documento' => $_GET['tipo_documento'] ?? '',
                'sort' => $_GET['sort'] ?? '',
                'order' => $_GET['order'] ?? '',
                'per_page' => $_GET['per_page'] ?? ($_SESSION['catalogacion_filters']['per_page'] ?? 20)
            ];
        }
        // 3. Si NO hay filtros en $_GET (acceso directo), pero existen en sesión -> Restaurar
        elseif (isset($_SESSION['catalogacion_filters']) && empty($_GET['page'])) {
            // Restaurar y redirigir
            $saved = $_SESSION['catalogacion_filters'];

            // Si mantenemos modo lotes
            if (isset($_GET['modo_lotes'])) {
                $saved['modo_lotes'] = $_GET['modo_lotes'];
            }

            $params = http_build_query($saved);
            $this->redirect('/catalogacion?' . $params);
            return;
        }

        // Obtener parámetros de búsqueda (ya sea de GET o vacíos si se limpió)
        $search = $_GET['search'] ?? '';
        $gestion = $_GET['gestion'] ?? '';
        $ubicacion_id = $_GET['ubicacion_id'] ?? '';
        $estado_documento = $_GET['estado_documento'] ?? '';
        $tipo_documento = $_GET['tipo_documento'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $order = $_GET['order'] ?? 'asc';
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

        // Per Page Logic
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        if ($perPage < 1)
            $perPage = 20; // Minimum limit
        if ($perPage > 200)
            $perPage = 200; // Maximum limit to prevent performance issues

        // Guardar per_page en sesión si cambia
        if (isset($_GET['per_page'])) {
            if (!isset($_SESSION['catalogacion_filters'])) {
                $_SESSION['catalogacion_filters'] = [];
            }
            $_SESSION['catalogacion_filters']['per_page'] = $perPage;
        } elseif (isset($_SESSION['catalogacion_filters']['per_page'])) {
            // Restaurar de sesión si no viene en GET
            $perPage = $_SESSION['catalogacion_filters']['per_page'];
        }

        // Realizar búsqueda - usar HojaRuta si es ese tipo
        if ($search || $gestion || $ubicacion_id || $estado_documento || $tipo_documento) {
            // Buscar en documentos (otros tipos)
            $documentos = $this->documento->buscarAvanzado([
                'search' => $search,
                'gestion' => $gestion,
                'ubicacion_id' => $ubicacion_id,
                'estado_documento' => $estado_documento,
                'tipo_documento' => $tipo_documento,
                'sort' => $sort,
                'order' => $order,
                'page' => $page,
                'per_page' => $perPage
            ]);

            $total = $this->documento->contarBusqueda([
                'search' => $search,
                'gestion' => $gestion,
                'ubicacion_id' => $ubicacion_id,
                'estado_documento' => $estado_documento,
                'tipo_documento' => $tipo_documento
            ]);
        } else {
            // Sin filtros, mostrar los más recientes usando buscarAvanzado sin filtros
            $documentos = $this->documento->buscarAvanzado([
                'page' => $page,
                'per_page' => $perPage,
                'sort' => $sort,
                'order' => $order
            ]);
            $total = $this->documento->count();
        }

        // Obtener datos para filtros
        $ubicaciones = $this->ubicacion->all();

        // Calcular paginación
        $totalPages = ceil($total / $perPage);

        $this->view('documentos.index', [
            'documentos' => $documentos,
            'ubicaciones' => $ubicaciones,
            'tiposDocumento' => $this->tipoDocumento->getAllOrderedById(),
            'filtros' => [
                'search' => $search,
                'gestion' => $gestion,
                'ubicacion_id' => $ubicacion_id,
                'estado_documento' => $estado_documento,
                'tipo_documento' => $tipo_documento,
                'sort' => $sort,
                'order' => $order,
                'per_page' => $perPage
            ],
            'paginacion' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ],
            'contenedores' => $this->contenedorFisico->buscar(),
            'user' => $this->getCurrentUser()
        ]);
    }

    /**
     * Ver detalle de un documento
     */
    public function ver($id)
    {
        $this->requireAuth();

        $documento = $this->documento->findWithContenedor($id);

        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }

        if (isset($documento['unidad_id']) && $documento['unidad_id']) {
            $documento['unidad'] = $this->unidadArea->find($documento['unidad_id']);
        }

        // Recuperar filtros para el botón volver
        $filters = [
            'page' => $_GET['page'] ?? 1,
            'search' => $_GET['search'] ?? '',
            'gestion' => $_GET['gestion'] ?? '',
            'ubicacion_id' => $_GET['ubicacion_id'] ?? '',
            'estado_documento' => $_GET['estado_documento'] ?? '',
            'tipo_documento' => $_GET['tipo_documento'] ?? '',
            'sort' => $_GET['sort'] ?? '',
            'order' => $_GET['order'] ?? ''
        ];

        $this->view('documentos.detalle', [
            'documento' => $documento,
            'user' => $this->getCurrentUser(),
            'filters' => $filters
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function crear()
    {
        $this->requireAuth();

        // Obtener contenedores para el select
        $contenedores = $this->contenedorFisico->buscar();

        $this->view('documentos.crear', [
            'contenedores' => $contenedores,
            'tiposDocumento' => $this->tipoDocumento->getAllOrderedById(),
            'user' => $this->getCurrentUser()
        ]);
    }

    /**
     * Guardar nuevo documento
     */
    /**
     * Guardar nuevo documento (Individual o Lote)
     */
    public function guardar()
    {
        $this->requireAuth();

        $modoLote = isset($_POST['modo_lote']) && $_POST['modo_lote'] == '1';

        // Validar datos comunes
        if (empty($_POST['tipo_documento']) || empty($_POST['gestion'])) {
            \TAMEP\Core\Session::flash('error', 'Tipo de documento y Gestión son obligatorios');
            $this->redirect('/catalogacion/crear');
        }

        // --- MODO LOTE ---
        if ($modoLote) {
            $desde = (int) ($_POST['nro_desde'] ?? 0);
            $hasta = (int) ($_POST['nro_hasta'] ?? 0);
            $documentNumbers = $_POST['document_numbers'] ?? [];

            // Validate: Either valid range OR valid list
            if (empty($documentNumbers) && ($desde <= 0 || $hasta <= 0 || $desde > $hasta)) {
                \TAMEP\Core\Session::flash('error', 'Rango de números inválido o lista vacía');
                $this->redirect('/catalogacion/crear');
            }

            // If explicit list provided (Main source of truth now)
            if (!empty($documentNumbers)) {
                $numbersToProcess = $documentNumbers;
            } else {
                $numbersToProcess = range($desde, $hasta);
            }

            $count = 0;

            // Checkbox arrays
            $batchExiste = $_POST['batch_existe'] ?? [];
            $batchAnulado = $_POST['batch_anulado'] ?? [];
            $batchNoUtil = $_POST['batch_no_util'] ?? [];

            // Preparar datos base
            $baseData = [
                'tipo_documento' => $_POST['tipo_documento'],
                'tipo_documento_id' => ($tipo = $this->tipoDocumento->findByCode($_POST['tipo_documento'])) ? $tipo['id'] : null,
                'gestion' => $_POST['gestion'],
                'codigo_abc' => $_POST['codigo_abc'] ?? null,
                'contenedor_fisico_id' => !empty($_POST['contenedor_fisico_id']) ? $_POST['contenedor_fisico_id'] : null,
                'observaciones' => $_POST['observaciones'] ?? null,
                'fecha_creacion' => date('Y-m-d H:i:s')
            ];

            foreach ($numbersToProcess as $i) {
                // $i is the comprobante number
                $docData = $baseData;
                $docData['nro_comprobante'] = $i;

                // Determinar estado basado en checkboxes
                $existe = isset($batchExiste[$i]);
                $anulado = isset($batchAnulado[$i]);
                $noUtil = isset($batchNoUtil[$i]);

                if (!$existe) {
                    $status = 'FALTA';
                } elseif ($anulado) {
                    $status = 'ANULADO';
                } elseif ($noUtil) {
                    $status = 'NO UTILIZADO';
                } else {
                    $status = 'DISPONIBLE';
                }

                $docData['estado_documento_id'] = $this->documento->getEstadoId($status);
                // Ensure legacy columns are removed from insert data
                unset($docData['tipo_documento']);
                unset($docData['estado_documento']);


                // Intentar crear (ignorar errores de duplicados contando solo éxitos)
                if ($this->documento->create($docData)) {
                    $count++;
                }
            }

            AuditLogger::log('CREAR_LOTE', 'Documentos', null, "Se crearon $count documentos en lote (Rango: $desde-$hasta o Lista)");
            \TAMEP\Core\Session::flash('success', "Se procesaron " . count($numbersToProcess) . " item(s). Se crearon $count documentos exitosamente.");
            $this->redirect('/catalogacion');
            return;
        }

        // --- MODO INDIVIDUAL (Original) ---
        if (empty($_POST['nro_comprobante'])) {
            \TAMEP\Core\Session::flash('error', 'Número de comprobante obligatorio');
            $this->redirect('/catalogacion/crear');
        }

        // Preparar datos
        $data = [
            // 'tipo_documento' => $_POST['tipo_documento'], // Legacy column removed
            'tipo_documento_id' => ($tipo = $this->tipoDocumento->findByCode($_POST['tipo_documento'])) ? $tipo['id'] : null,
            'gestion' => $_POST['gestion'],
            'nro_comprobante' => $_POST['nro_comprobante'],
            'codigo_abc' => $_POST['codigo_abc'] ?? null,
            'contenedor_fisico_id' => !empty($_POST['contenedor_fisico_id']) ? $_POST['contenedor_fisico_id'] : null,
            'estado_documento_id' => $this->documento->getEstadoId($_POST['estado_documento'] ?? 'DISPONIBLE'),
            'observaciones' => $_POST['observaciones'] ?? null,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];

        // Guardar
        $id = $this->documento->create($data);

        if ($id) {
            AuditLogger::log('CREAR', 'Documentos', $id, "Se creó documento (Gestión: {$_POST['gestion']}, Nro: {$_POST['nro_comprobante']})");
            \TAMEP\Core\Session::flash('success', 'Documento creado exitosamente');
            $this->redirect('/catalogacion/ver/' . $id);
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al crear el documento (posible duplicado)');
            $this->redirect('/catalogacion/crear');
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function editar($id)
    {
        $this->requireAuth();

        $documento = $this->documento->findWithContenedor($id);

        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }

        // Recuperar filtros de la URL para mantener el estado al volver
        $filters = [
            'page' => $_GET['page'] ?? 1,
            'search' => $_GET['search'] ?? '',
            'gestion' => $_GET['gestion'] ?? '',
            'ubicacion_id' => $_GET['ubicacion_id'] ?? '',
            'estado_documento' => $_GET['estado_documento'] ?? '',
            'tipo_documento' => $_GET['tipo_documento'] ?? '',
            'sort' => $_GET['sort'] ?? '',
            'order' => $_GET['order'] ?? ''
        ];

        // Obtener contenedores para el select (LIMITADO para inicial, búsqueda por AJAX)
        // Para editar, necesitamos asegurar que el contenedor actual esté en la lista o se cargue
        $contenedores = [];
        if (!empty($documento['contenedor_fisico_id'])) {
            $contenedores[] = $this->contenedorFisico->find($documento['contenedor_fisico_id']);
        }
        // $contenedores = $this->contenedorFisico->buscar(); // REMOVED: Too heavy for list

        $this->view('documentos.editar', [
            'documento' => $documento,
            'contenedores' => $contenedores, // Solo el actual, el resto por AJAX
            'ubicaciones' => $this->ubicacion->all(),
            'tiposDocumento' => $this->tipoDocumento->getAllOrderedById(),
            'user' => $this->getCurrentUser(),
            'filters' => $filters // Pass filters to view
        ]);
    }

    /**
     * Actualizar documento
     */
    public function actualizar($id)
    {
        $this->requireAuth();

        $documento = $this->documento->find($id);

        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }

        // Preparar datos
        $data = [
            // 'tipo_documento' => $_POST['tipo_documento'], // Legacy
            'tipo_documento_id' => ($tipo = $this->tipoDocumento->findByCode($_POST['tipo_documento'])) ? $tipo['id'] : null,
            'gestion' => $_POST['gestion'],
            'nro_comprobante' => $_POST['nro_comprobante'],
            'codigo_abc' => $_POST['codigo_abc'] ?? null,
            'contenedor_fisico_id' => !empty($_POST['contenedor_fisico_id']) ? $_POST['contenedor_fisico_id'] : null,
            'estado_documento_id' => $this->documento->getEstadoId($_POST['estado_documento'] ?? 'DISPONIBLE'),
            'observaciones' => $_POST['observaciones'] ?? null
        ];

        // Reconstruir URL con filtros (Restored)
        $filters = [
            'page' => $_POST['filter_page'] ?? 1,
            'search' => $_POST['filter_search'] ?? '',
            'gestion' => $_POST['filter_gestion'] ?? '',
            'ubicacion_id' => $_POST['filter_ubicacion_id'] ?? '',
            'estado_documento' => $_POST['filter_estado_documento'] ?? '',
            'tipo_documento' => $_POST['filter_tipo_documento'] ?? '',
            'sort' => $_POST['filter_sort'] ?? '',
            'order' => $_POST['filter_order'] ?? ''
        ];
        // Remove empty filters
        $filters = array_filter($filters, function ($value) {
            return $value !== '';
        });
        $queryString = http_build_query($filters);
        $redirectUrl = '/catalogacion' . ($queryString ? '?' . $queryString : '');

        // Actualizar
        try {
            $this->documento->update($id, $data);
            AuditLogger::log('EDITAR', 'Documentos', $id, "Se actualizó documento (Gestión: {$_POST['gestion']}, Nro: {$_POST['nro_comprobante']})");

            \TAMEP\Core\Session::flash('success', 'Documento actualizado exitosamente');
            $this->redirect($redirectUrl);

        } catch (\Exception $e) {
            error_log("Error actualizando documento: " . $e->getMessage());
            \TAMEP\Core\Session::flash('error', 'Error al guardar: ' . $e->getMessage());
            $this->redirect('/catalogacion/editar/' . $id . ($queryString ? '?' . $queryString : ''));
        }
    }

    /**
     * Eliminar documento
     */
    public function eliminar($id)
    {
        $this->requireAuth();

        $documento = $this->documento->find($id);

        if (!$documento) {
            \TAMEP\Core\Session::flash('error', 'Documento no encontrado');
            $this->redirect('/catalogacion');
        }

        // Eliminar
        $success = $this->documento->delete($id);

        if ($success) {
            AuditLogger::log('ELIMINAR', 'Documentos', $id, "Se eliminó documento ID $id");
            \TAMEP\Core\Session::flash('success', 'Documento eliminado exitosamente');
        } else {
            \TAMEP\Core\Session::flash('error', 'Error al eliminar el documento');
        }

        $this->redirect('/catalogacion');
    }

    /**
     * Actualizar contenedor de un lote de documentos
     */
    public function actualizarLote()
    {
        $this->requireAuth();

        $ids = $_POST['ids'] ?? [];
        $contenedor_id = isset($_POST['contenedor_id']) ? trim($_POST['contenedor_id']) : '';
        $estado_documento = isset($_POST['estado_documento']) ? trim($_POST['estado_documento']) : '';

        if (empty($ids)) {
            \TAMEP\Core\Session::flash('error', 'Debe seleccionar al menos un documento');
            $this->redirect('/catalogacion?modo_lotes=1');
            return;
        }

        // Validate at least one action is taken
        if (empty($contenedor_id) && empty($estado_documento)) {
            \TAMEP\Core\Session::flash('warning', 'No se seleccionó ninguna acción (Contenedor o Estado) para actualizar.');
            $this->redirect('/catalogacion?modo_lotes=1');
            return;
        }

        // Decodificar IDs si vienen como string JSON
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }

        // Build Update Array
        $updateData = [];
        if (!empty($contenedor_id)) {
            $updateData['contenedor_fisico_id'] = $contenedor_id;
        }
        if (!empty($estado_documento)) {
            $updateData['estado_documento_id'] = $this->documento->getEstadoId($estado_documento);
        }

        $count = 0;
        foreach ($ids as $id) {
            if ($this->documento->update($id, $updateData)) {
                $count++;
            }
        }

        $msg = "Se actualizaron $count documentos";
        if (count($updateData) > 0) {
            $changes = [];
            if (isset($updateData['contenedor_fisico_id']))
                $changes[] = "Contenedor";
            if (isset($updateData['estado_documento_id']))
                $changes[] = "Estado";
            $msg .= " (" . implode(', ', $changes) . ")";
        }

        // Reconstruct URL with filters
        $filters = [
            'page' => $_POST['page'] ?? 1,
            'search' => $_POST['search'] ?? '',
            'gestion' => $_POST['gestion'] ?? '',
            'ubicacion_id' => $_POST['ubicacion_id'] ?? '',
            'estado_documento' => $_POST['filter_estado_documento'] ?? '',
            'tipo_documento' => $_POST['tipo_documento'] ?? '',
            'modo_lotes' => '1'
        ];

        // Remove empty
        $filters = array_filter($filters, function ($v) {
            return $v !== '';
        });
        $queryString = http_build_query($filters);

        $filters = array_filter($filters, function ($v) {
            return $v !== ''; });
        $queryString = http_build_query($filters);

        AuditLogger::log('EDITAR_LOTE', 'Documentos', null, "Se actualizaron $count documentos en lote. Detalles: " . $msg);
        \TAMEP\Core\Session::flash('success', $msg);
        $this->redirect('/catalogacion?' . $queryString);
    }

    private function getCurrentUser()
    {
        return \TAMEP\Core\Session::user();
    }
}
