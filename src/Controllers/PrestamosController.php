<?php
/**
 * Controlador de Préstamos
 * Gestiona préstamos de documentos LIBRO/AMARRO
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\Prestamo;
use TAMEP\Models\Documento;
use TAMEP\Models\ContenedorFisico;
use TAMEP\Models\Usuario;
// use TAMEP\Models\HojaRuta;
use TAMEP\Models\PrestamoHeader;
use TAMEP\Models\Ubicacion; // Para ubicación física
use TAMEP\Core\AuditLogger;
use TAMEP\Models\UnidadArea;
use TAMEP\Models\TipoDocumento;
use TAMEP\Core\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PrestamosController extends BaseController
{
    private $documento;
    private $prestamo;
    private $prestamoHeader;
    private $contenedorFisico;
    private $usuario;
    // private $hojaRuta;
    private $ubicacion;
    private $unidadArea;
    private $tipoDocumento;

    public function __construct()
    {
        parent::__construct();
        $this->documento = new Documento();
        $this->prestamo = new Prestamo();
        $this->prestamoHeader = new PrestamoHeader();
        $this->contenedorFisico = new ContenedorFisico();
        $this->usuario = new Usuario();
        // $this->hojaRuta = new HojaRuta();
        $this->ubicacion = new Ubicacion();
        $this->unidadArea = new UnidadArea();
        $this->tipoDocumento = new TipoDocumento();
    }

    /**
     * Listar préstamos (Agrupados por cabecera)
     */
    public function index()
    {
        $this->requireAuth();

        // Filtros
        $estado = $_GET['estado'] ?? '';
        $usuario_id = $_GET['usuario_id'] ?? '';

        // Paginación y Orden
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        if ($perPage < 1)
            $perPage = 20;
        if ($perPage > 200)
            $perPage = 200;

        $sort = $_GET['sort'] ?? '';
        $order = strtoupper($_GET['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        // Construir query para encabezados
        $where = [];
        $params = [];

        if (!empty($estado)) {
            $where[] = "ph.estado = ?";
            $params[] = $estado;
        }

        if (!empty($usuario_id)) {
            $where[] = "ph.usuario_id = ?";
            $params[] = $usuario_id;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count Total
        $sqlCount = "SELECT COUNT(*) as total FROM prestamos_encabezados ph $whereClause";
        $resCount = $this->prestamoHeader->getDb()->fetchOne($sqlCount, $params);
        $totalHeaders = $resCount['total'] ?? 0;

        // Sorting
        $orderBy = "ph.fecha_prestamo DESC, ph.id DESC";

        if ($sort === 'unidad')
            $orderBy = "ub.nombre $order";
        if ($sort === 'fecha')
            $orderBy = "ph.fecha_prestamo $order";
        if ($sort === 'usuario')
            $orderBy = "u.nombre_completo $order";
        if ($sort === 'docs')
            $orderBy = "total_documentos $order";
        if ($sort === 'estado')
            $orderBy = "ph.estado $order";

        // Fetch headers with user info and item count
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT ph.*, 
                       u.nombre_completo as usuario_nombre,
                       ub.nombre as unidad_nombre,
                       ph.nombre_prestatario,
                       COUNT(CASE WHEN p.es_principal = 1 THEN 1 END) as total_documentos
                FROM prestamos_encabezados ph
                LEFT JOIN usuarios u ON ph.usuario_id = u.id
                LEFT JOIN unidades_areas ub ON ph.unidad_area_id = ub.id
                LEFT JOIN prestamos p ON ph.id = p.encabezado_id
                {$whereClause}
                GROUP BY ph.id
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";

        $prestamos = $this->prestamoHeader->getDb()->fetchAll($sql, $params);

        // Obtener usuarios para filtro
        $usuarios = $this->usuario->getActive();

        $this->view('prestamos.index', [
            'prestamos' => $prestamos,
            'usuarios' => $usuarios,
            'filtros' => [
                'estado' => $estado,
                'usuario_id' => $usuario_id,
                'sort' => $sort,
                'order' => $order,
                'per_page' => $perPage
            ],
            'paginacion' => [
                'current' => $page,
                'total' => $totalHeaders,
                'per_page' => $perPage,
                'page' => $page,
                'total_pages' => $totalHeaders > 0 ? ceil($totalHeaders / $perPage) : 1
            ],
            'user' => $this->getCurrentUser()
        ]);
    }

    /**
     * Mostrar formulario de nuevo préstamo
     */
    public function crear()
    {
        $this->requireAuth();

        // Obtener documentos disponibles
        $documentos = $this->documento->getAvailable();

        // Obtener unidades/áreas
        $unidades = $this->unidadArea->getActive();

        $this->view('prestamos.crear', [
            'documentos' => $documentos,
            'unidades' => $unidades,
            'tiposDocumento' => $this->tipoDocumento->getAllOrderedById(),
            'user' => $this->getCurrentUser()
        ]);
    }

    /**
     * Guardar nuevo préstamo
     */
    public function guardar()
    {
        $this->requireAuth();

        // Validar
        if (empty($_POST['documento_id']) || empty($_POST['unidad_area_id']) || empty($_POST['fecha_devolucion_esperada'])) {
            Session::flash('error', 'Debe completar todos los campos obligatorios');
            $this->redirect('/prestamos/crear');
        }

        // Verificar que el documento esté disponible para préstamo
        // Usamos findWithContenedor para obtener el tipo de contenedor (LIBRO, AMARRO, etc.)
        $documentoPrincipal = $this->documento->findWithContenedor($_POST['documento_id']);
        $estadosPermitidosNombres = ['DISPONIBLE', 'NO UTILIZADO', 'ANULADO'];

        if (!$documentoPrincipal || !in_array($documentoPrincipal['estado_documento'], $estadosPermitidosNombres)) {
            Session::flash('error', 'El documento no está disponible para préstamo (Estado: ' . ($documentoPrincipal['estado_documento'] ?? 'N/A') . ')');
            $this->redirect('/prestamos/crear');
        }

        // Crear Encabezado
        $headerData = [
            'usuario_id' => Session::user()['id'], // El usuario que REGISTRA el préstamo (Admin)
            'unidad_area_id' => $_POST['unidad_area_id'],
            'nombre_prestatario' => $_POST['nombre_prestatario'] ?? null,
            'fecha_prestamo' => date('Y-m-d'),
            'fecha_devolucion_esperada' => $_POST['fecha_devolucion_esperada'],
            'observaciones' => $_POST['observaciones'] ?? null,
            'estado' => 'En Proceso'
        ];

        $headerId = $this->prestamoHeader->create($headerData);

        if ($headerId) {
            $docsToLoan = [];

            // Lógica de "LIBRO": Si el contenedor es un Libro, prestamos TODOS los documentos contenidos
            if (!empty($documentoPrincipal['tipo_contenedor']) && strtoupper($documentoPrincipal['tipo_contenedor']) === 'LIBRO') {
                // Obtener IDs de estados permitidos para incluirlos en el lote
                $allowedIds = [];
                foreach ($estadosPermitidosNombres as $statusName) {
                    $sid = $this->documento->getEstadoId($statusName);
                    if ($sid)
                        $allowedIds[] = $sid;
                }

                if (!empty($allowedIds)) {
                    // Buscar todos los documentos en este contenedor que estén disponibles
                    $inQuery = implode(',', $allowedIds);
                    $sqlSiblings = "SELECT id, nro_comprobante FROM documentos 
                                    WHERE contenedor_fisico_id = ? 
                                    AND estado_documento_id IN ($inQuery)";

                    $siblings = $this->documento->getDb()->fetchAll($sqlSiblings, [$documentoPrincipal['contenedor_fisico_id']]);

                    foreach ($siblings as $sib) {
                        $docsToLoan[] = [
                            'id' => $sib['id'],
                            'is_principal' => ($sib['id'] == $documentoPrincipal['id']),
                            'nro' => $sib['nro_comprobante']
                        ];
                    }
                }
            }

            // Verificar si el principal está en la lista (si no era libro o falló algo, lo agregamos solo a él)
            $principalFound = false;
            foreach ($docsToLoan as $d) {
                if ($d['id'] == $documentoPrincipal['id'])
                    $principalFound = true;
            }

            if (!$principalFound) {
                $docsToLoan[] = [
                    'id' => $documentoPrincipal['id'],
                    'is_principal' => true,
                    'nro' => $documentoPrincipal['nro_comprobante']
                ];
            }

            $count = 0;
            $estadoPrestadoId = $this->documento->getEstadoId('PRESTADO');

            foreach ($docsToLoan as $item) {
                // Fetch full document data to get current state
                $docData = $this->documento->find($item['id']);

                $obs = $_POST['observaciones'] ?? '';

                // Nota automática para documentos "arrastrados" por el libro
                if (!$item['is_principal']) {
                    $prefix = $obs ? " | " : "";
                    $obs .= $prefix . "Prestamo por Libro (Solicitado: Doc #{$documentoPrincipal['nro_comprobante']})";
                }

                $data = [
                    'encabezado_id' => $headerId,
                    'documento_id' => $item['id'],
                    'contenedor_fisico_id' => $documentoPrincipal['contenedor_fisico_id'] ?? null,
                    'usuario_id' => $_POST['usuario_id'],
                    'fecha_prestamo' => date('Y-m-d'),
                    'fecha_devolucion_esperada' => $_POST['fecha_devolucion_esperada'],
                    'observaciones' => substr($obs, 0, 255),
                    'estado' => 'En Proceso',
                    'estado_anterior_id' => $docData['estado_documento_id'] ?? null,
                    'es_principal' => $item['is_principal'] ? 1 : 0
                ];

                if ($this->prestamo->create($data)) {
                    $this->documento->update($item['id'], ['estado_documento_id' => $estadoPrestadoId]);
                    $count++;
                }
            }

            // Replaced existing success/redirect logic
            AuditLogger::log('CREAR', 'Prestamos', $headerId, "Se creó solicitud de préstamo (Prestatario: {$_POST['nombre_prestatario']})");
            Session::flash('success', 'Préstamo iniciado. Agregue los documentos.');
            $this->redirect('/prestamos/editar/' . $headerId);

        } else {
            Session::flash('error', 'Error al registrar el préstamo');
            $this->redirect('/prestamos/crear');
        }
    }

    /**
     * Vista de nuevo préstamo con selección múltiple
     */
    public function nuevo()
    {
        $this->requireAuth();

        // 1. Limpiar filtros
        if (isset($_GET['clean'])) {
            unset($_SESSION['prestamos_nuevo_filters']);
            $this->redirect('/prestamos/nuevo');
            return;
        }

        // 2. Filtros
        $hasFilters = isset($_GET['search']) || isset($_GET['gestion']) || isset($_GET['tipo_documento']) ||
            isset($_GET['sort']) || isset($_GET['order']) || isset($_GET['per_page']);

        if ($hasFilters) {
            $_SESSION['prestamos_nuevo_filters'] = [
                'search' => $_GET['search'] ?? '',
                'gestion' => $_GET['gestion'] ?? '',
                'tipo_documento' => $_GET['tipo_documento'] ?? '',
                'sort' => $_GET['sort'] ?? '',
                'order' => $_GET['order'] ?? '',
                'per_page' => $_GET['per_page'] ?? 20
            ];
        } elseif (isset($_SESSION['prestamos_nuevo_filters']) && empty($_GET['page'])) {
            $params = http_build_query($_SESSION['prestamos_nuevo_filters']);
            $this->redirect('/prestamos/nuevo?' . $params);
            return;
        }

        $search = $_GET['search'] ?? '';
        $gestion = $_GET['gestion'] ?? '';
        $tipo_documento = $_GET['tipo_documento'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $order = $_GET['order'] ?? 'asc';
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        if ($perPage < 1)
            $perPage = 20;
        if ($perPage > 200)
            $perPage = 200;

        $documentos = [];
        $total = 0;

        // Solo buscar si hay filtros (optimización)
        if (!empty($search) || !empty($gestion) || !empty($tipo_documento)) {

            // Parametros comunes
            $limit = $perPage;
            $offset = ($page - 1) * $limit;
            $orderDir = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

            // Buscar según el tipo de documento
            if ($tipo_documento === 'HOJA_RUTA_DIARIOS') {
                $where = ["hr.activo = 1"];
                $params = [];

                if (!empty($search)) {
                    if (preg_match('/^(\d+)-(\d+)$/', $search, $matches)) {
                        $min = min((int) $matches[1], (int) $matches[2]);
                        $max = max((int) $matches[1], (int) $matches[2]);
                        $where[] = "CAST(hr.nro_comprobante_diario AS UNSIGNED) BETWEEN ? AND ?";
                        $params[] = $min;
                        $params[] = $max;
                    } else {
                        $where[] = "(hr.nro_comprobante_diario = ? OR hr.nro_hoja_ruta = ? OR hr.rubro LIKE ? OR hr.interesado LIKE ?)";
                        $params[] = $search;
                        $params[] = $search;
                        $params[] = "%$search%";
                        $params[] = "%$search%";
                    }
                }

                if (!empty($gestion)) {
                    $where[] = "hr.gestion = ?";
                    $params[] = $gestion;
                }

                $whereClause = 'WHERE ' . implode(' AND ', $where);

                // Sorting HR
                $orderBy = "hr.gestion DESC, hr.nro_comprobante_diario DESC";
                if ($sort === 'gestion')
                    $orderBy = "hr.gestion $orderDir";
                if ($sort === 'nro_comprobante')
                    $orderBy = "hr.nro_comprobante_diario $orderDir";
                if ($sort === 'ubicacion')
                    $orderBy = "ub.nombre $orderDir";

                // Count
                /* 
                 * HOJA DE RUTA LOGIC DISABLED - Model missing
                $documentos = $this->hojaRuta->getDb()->fetchAll($sql, $params);
                */
                $documentos = [];

            } else {
                // Documentos comunes
                $where = ["rd.estado_documento_id IN (SELECT id FROM estados WHERE nombre IN ('DISPONIBLE', 'NO UTILIZADO', 'ANULADO', 'FALTA', 'PRESTADO'))"];
                $params = [];

                if (!empty($search)) {
                    if (preg_match('/^(\d+)-(\d+)$/', $search, $matches)) {
                        $min = min((int) $matches[1], (int) $matches[2]);
                        $max = max((int) $matches[1], (int) $matches[2]);
                        $where[] = "CAST(rd.nro_comprobante AS UNSIGNED) BETWEEN ? AND ?";
                        $params[] = $min;
                        $params[] = $max;
                    } else {
                        $where[] = "(rd.nro_comprobante = ? OR rd.codigo_abc = ?)";
                        $params[] = $search;
                        $params[] = $search;
                    }
                }

                if (!empty($gestion)) {
                    $where[] = "rd.gestion = ?";
                    $params[] = $gestion;
                }

                if (!empty($tipo_documento)) {
                    $where[] = "t.codigo = ?";
                    $params[] = $tipo_documento;
                }

                $whereClause = 'WHERE ' . implode(' AND ', $where);

                // Sorting Docs
                $orderBy = "rd.gestion DESC, rd.nro_comprobante DESC";
                if ($sort === 'gestion')
                    $orderBy = "rd.gestion $orderDir";
                if ($sort === 'nro_comprobante') {
                    if ($orderDir === 'ASC')
                        $orderBy = "CAST(rd.nro_comprobante AS UNSIGNED) ASC, rd.nro_comprobante ASC";
                    else
                        $orderBy = "CAST(rd.nro_comprobante AS UNSIGNED) DESC, rd.nro_comprobante DESC";
                }
                if ($sort === 'ubicacion')
                    $orderBy = "ub.nombre $orderDir";
                if ($sort === 'estado')
                    $orderBy = "e.nombre $orderDir";

                // Count
                $sqlCount = "SELECT COUNT(*) as total FROM documentos rd 
                             LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id 
                             LEFT JOIN ubicaciones ub ON cf.ubicacion_id = ub.id 
                             LEFT JOIN tipo_documento t ON rd.tipo_documento_id = t.id
                             {$whereClause}";
                $resCount = $this->documento->getDb()->fetchOne($sqlCount, $params);
                $total = $resCount['total'] ?? 0;

                // Data
                $sql = "SELECT rd.*, tc.codigo as tipo_contenedor, cf.numero as contenedor_numero, cf.codigo_abc as contenedor_codigo_abc, ub.nombre as ubicacion_fisica,
                               e.nombre as estado_documento,
                               t.codigo as tipo_documento
                        FROM documentos rd
                        LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                        LEFT JOIN ubicaciones ub ON cf.ubicacion_id = ub.id
                        LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                        LEFT JOIN estados e ON rd.estado_documento_id = e.id
                        LEFT JOIN tipo_documento t ON rd.tipo_documento_id = t.id
                        {$whereClause}
                        ORDER BY {$orderBy}
                        LIMIT {$limit} OFFSET {$offset}";

                $documentos = $this->documento->getDb()->fetchAll($sql, $params);
            }
        }

        // Obtener unidades
        $unidades = $this->unidadArea->getActive();

        $this->view('prestamos.nuevo', [
            'documentos' => $documentos,
            'unidades' => $unidades,
            'tiposDocumento' => $this->tipoDocumento->getAllOrderedById(),
            'filtros' => [
                'search' => $search,
                'gestion' => $gestion,
                'tipo_documento' => $tipo_documento,
                'sort' => $sort,
                'order' => $order,
                'per_page' => $perPage
            ],
            'paginacion' => [
                'current' => $page,
                'total' => $total,
                'per_page' => $perPage,
                'page' => $page,
                'total_pages' => ceil($total / $perPage)
            ],
            'user' => $this->getCurrentUser()
        ]);
    }

    /**
     * Guardar préstamo múltiple (con cabecera)
     */
    public function guardarMultiple()
    {
        $this->requireAuth();

        // Validar
        if (empty($_POST['unidad_area_id']) || empty($_POST['documentos'])) {
            Session::flash('error', 'Debe completar todos los campos obligatorios');
            $this->redirect('/prestamos/nuevo');
        }

        $documentosIds = json_decode($_POST['documentos'], true);

        if (empty($documentosIds)) {
            Session::flash('error', 'Debe seleccionar al menos un documento');
            $this->redirect('/prestamos/nuevo');
        }

        // Datos del Formulario
        $fechaPrestamo = !empty($_POST['fecha_prestamo']) ? $_POST['fecha_prestamo'] : date('Y-m-d');
        $fechaDevolucion = !empty($_POST['fecha_devolucion']) ? $_POST['fecha_devolucion'] : null;
        $esHistorico = !empty($_POST['es_historico']) && $_POST['es_historico'] == '1';
        $estadoInicial = !empty($_POST['estado_inicial']) ? $_POST['estado_inicial'] : 'En Proceso';

        // Validación de fecha de devolución esperada (NO puede ser NULL en BD)
        if (empty($fechaDevolucion)) {
            if ($esHistorico) {
                // Para históricos sin fecha de devolución, usar fecha préstamo + 30 días
                // Esto permite registrar préstamos pasados sin fecha específica de devolución
                $fechaDevolucion = date('Y-m-d', strtotime($fechaPrestamo . ' +30 days'));
            } else {
                // Para préstamos normales, +14 días desde hoy
                $fechaDevolucion = date('Y-m-d', strtotime('+14 days'));
            }
        }

        // Crear Encabezado
        $headerData = [
            'usuario_id' => Session::user()['id'],
            'unidad_area_id' => $_POST['unidad_area_id'],
            'nombre_prestatario' => $_POST['nombre_prestatario'] ?? null,
            'fecha_prestamo' => $fechaPrestamo,
            'fecha_devolucion_esperada' => $fechaDevolucion,
            'observaciones' => $_POST['observaciones'] ?? null,
            'estado' => $estadoInicial // 'En Proceso', 'Prestado', or 'Devuelto'
        ];

        $headerId = $this->prestamoHeader->create($headerData);

        if (!$headerId) {
            Session::flash('error', 'Error al crear la cabecera del préstamo');
            $this->redirect('/prestamos/nuevo');
        }

        $exitosos = 0;
        $errores = 0;

        // LIBRO Logic: Expand document list to include all docs from LIBRO containers
        $docsToProcess = [];
        $processedContainers = []; // Track containers we've already expanded

        foreach ($documentosIds as $docId) {
            // Fetch document with its container type
            $sqlDoc = "SELECT d.id, d.estado_documento_id, d.contenedor_fisico_id, tc.codigo as tipo_contenedor
                       FROM documentos d
                       LEFT JOIN contenedores_fisicos cf ON d.contenedor_fisico_id = cf.id
                       LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                       WHERE d.id = ?";
            $documento = $this->documento->getDb()->fetchOne($sqlDoc, [$docId]);

            if (!$documento) {
                $errores++;
                continue;
            }

            // Check if this document is in a LIBRO container
            $tipoContenedor = strtoupper($documento['tipo_contenedor'] ?? '');
            $containerId = $documento['contenedor_fisico_id'];

            if (stripos($tipoContenedor, 'LIBRO') !== false && $containerId && !in_array($containerId, $processedContainers)) {
                // Mark this container as processed
                $processedContainers[] = $containerId;

                // Get ALL documents from this LIBRO
                $sqlSiblings = "SELECT id, estado_documento_id FROM documentos WHERE contenedor_fisico_id = ?";
                $siblings = $this->documento->getDb()->fetchAll($sqlSiblings, [$containerId]);

                foreach ($siblings as $sib) {
                    // Check if not already in list
                    $alreadyAdded = false;
                    foreach ($docsToProcess as $existing) {
                        if ($existing['id'] == $sib['id']) {
                            $alreadyAdded = true;
                            break;
                        }
                    }

                    if (!$alreadyAdded) {
                        $docsToProcess[] = [
                            'id' => $sib['id'],
                            'es_principal' => ($sib['id'] == $docId) ? 1 : 0,
                            'estado_anterior_id' => $sib['estado_documento_id'],
                            'contenedor_fisico_id' => $containerId
                        ];
                    }
                }
            } else {
                // Not a LIBRO, just add this document
                $alreadyAdded = false;
                foreach ($docsToProcess as $existing) {
                    if ($existing['id'] == $docId) {
                        $alreadyAdded = true;
                        break;
                    }
                }

                if (!$alreadyAdded) {
                    $docsToProcess[] = [
                        'id' => $docId,
                        'es_principal' => 1,
                        'estado_anterior_id' => $documento['estado_documento_id'],
                        'contenedor_fisico_id' => $containerId
                    ];
                }
            }
        }

        // Now process the expanded list
        foreach ($docsToProcess as $docData) {
            $docId = $docData['id'];

            // Determine detail state
            // If header is 'Devuelto', details should be 'Devuelto' too
            $detailState = $estadoInicial;
            $fechaDevReal = ($estadoInicial === 'Devuelto') ? ($fechaDevolucion ?? date('Y-m-d')) : null;

            // Crear préstamo detalle
            $data = [
                'encabezado_id' => $headerId,
                'documento_id' => $docId,
                'contenedor_fisico_id' => $docData['contenedor_fisico_id'],
                'usuario_id' => Session::user()['id'],
                'fecha_prestamo' => $fechaPrestamo,
                'fecha_devolucion_esperada' => $fechaDevolucion,
                'fecha_devolucion_real' => $fechaDevReal,
                'observaciones' => $_POST['observaciones'] ?? null,
                'estado' => $detailState,
                'estado_anterior_id' => $docData['estado_anterior_id'],
                'es_principal' => $docData['es_principal']
            ];

            $id = $this->prestamo->create($data);

            if ($id) {
                // Actualizar estado del documento
                // SOLO marcar como PRESTADO si el estado es 'Prestado' (préstamo confirmado/histórico)
                // Si es 'En Proceso', NO marcar hasta que se procese físicamente
                // Si es 'Devuelto', NO marcar (ya está devuelto)
                if ($detailState === 'Prestado') {
                    $estadoId = $this->documento->getEstadoId('PRESTADO');
                    $this->documento->update($docId, ['estado_documento_id' => $estadoId]);
                }

                $exitosos++;
            } else {
                $errores++;
            }
        }

        if ($exitosos > 0) {
            Session::flash('success', "Préstamo registrado: {$exitosos} documento(s). Estado: {$estadoInicial}");
        } else {
            Session::flash('error', 'No se pudo registrar ningún documento en el préstamo');
        }

        // Redirect logic
        // If 'En Proceso', go to processing
        // If 'Prestado' or 'Devuelto', go to View
        if ($estadoInicial === 'En Proceso') {
            $this->redirect('/prestamos/procesar/' . $headerId);
        } else {
            $this->redirect('/prestamos/ver/' . $headerId);
        }
    }

    /**
     * Ver detalle de grupo de préstamo
     */
    public function ver($id)
    {
        $this->requireAuth();

        // Check if user came from a non-principal document
        $from_doc_id = $_GET['from_doc'] ?? null;
        $contextInfo = null;

        // Get Header Info
        $sql = "SELECT ph.*, 
                       u.nombre_completo as usuario_nombre, u.username,
                       ub.nombre as unidad_nombre
                FROM prestamos_encabezados ph
                LEFT JOIN usuarios u ON ph.usuario_id = u.id
                LEFT JOIN unidades_areas ub ON ph.unidad_area_id = ub.id
                WHERE ph.id = ?";

        $prestamo = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);

        if (!$prestamo) {
            Session::flash('error', 'Préstamo no encontrado');
            $this->redirect('/prestamos');
        }

        // Get Details
        $detalles = $this->prestamoHeader->getDetalles($id);

        // If coming from a specific document, check if it's non-principal
        if ($from_doc_id) {
            foreach ($detalles as $det) {
                if ($det['documento_id'] == $from_doc_id && $det['es_principal'] == 0) {
                    // Find the principal document from the same LIBRO
                    $sqlPrincipal = "SELECT d2.nro_comprobante, td.nombre as tipo_documento, tc.codigo as tipo_contenedor, cf.numero as libro_num, cf.codigo_abc as contenedor_codigo_abc
                                     FROM prestamos p1
                                     JOIN documentos d1 ON p1.documento_id = d1.id
                                     JOIN documentos d2 ON d1.contenedor_fisico_id = d2.contenedor_fisico_id
                                     JOIN prestamos p2 ON d2.id = p2.documento_id
                                     JOIN contenedores_fisicos cf ON d1.contenedor_fisico_id = cf.id
                                     JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                                     LEFT JOIN tipo_documento td ON d2.tipo_documento_id = td.id
                                     WHERE p1.encabezado_id = ?
                                     AND p1.documento_id = ?
                                     AND p2.es_principal = 1
                                     AND p2.encabezado_id = ?
                                     LIMIT 1";
                    $principal = $this->prestamoHeader->getDb()->fetchOne($sqlPrincipal, [$id, $from_doc_id, $id]);

                    if ($principal) {
                        $contextInfo = [
                            'is_libro_context' => true,
                            'tipo_contenedor' => $principal['tipo_contenedor'],
                            'libro_num' => $principal['libro_num'],
                            'contenedor_codigo_abc' => $principal['contenedor_codigo_abc'],
                            'doc_principal' => $principal['tipo_documento'] . ' Nro ' . $principal['nro_comprobante']
                        ];
                    }
                    break;
                }
            }
        }

        // Filter to show only PRINCIPAL documents (directly requested, not LIBRO-pulled)
        $detalles = array_filter($detalles, function ($d) {
            return !isset($d['es_principal']) || $d['es_principal'] == 1;
        });

        // Split details into Prestados and No Prestados
        $prestados = [];
        $noPrestados = [];

        foreach ($detalles as $det) {
            if ($det['estado'] === 'Prestado' || $det['estado'] === 'Devuelto') {
                $prestados[] = $det;
            } else {
                // Includes 'No Prestado', 'Falta', 'En Proceso', etc.
                // Fetch current document state to show real status if needed
                // But $det should already have joined data.
                // Wait, getDetalles might need to join document status to be sure?
                // Usually getDetalles just gets prestamos table.
                // Let's assume it does or we can add it. 
                // For now, let's just group them.
                $noPrestados[] = $det;
            }
        }

        $this->view('prestamos.detalle', [
            'prestamo' => $prestamo,
            'detalles' => $detalles, // Keep full list for safety/pdf
            'prestados' => $prestados,
            'noPrestados' => $noPrestados,
            'user' => $this->getCurrentUser(),
            'contextInfo' => $contextInfo
        ]);
    }

    private function getCurrentUser()
    {
        return Session::user();
    }

    /**
     * Exportar a Excel (CSV)
     */
    public function exportarExcel($id)
    {
        $this->requireAuth();

        // Fetch Header and Details
        $sql = "SELECT ph.*, u.nombre_completo as usuario_nombre, ub.nombre as unidad_nombre 
                FROM prestamos_encabezados ph 
                LEFT JOIN usuarios u ON ph.usuario_id = u.id 
                LEFT JOIN unidades_areas ub ON ph.unidad_area_id = ub.id
                WHERE ph.id = ?";
        $prestamo = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);

        if (!$prestamo) {
            die("Préstamo no encontrado");
        }

        $detalles = $this->prestamoHeader->getDetalles($id);

        // Output headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="prestamo_' . $id . '.csv"');

        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel
        fputs($output, "\xEF\xBB\xBF");

        // Header Info
        fputcsv($output, ['REPORTE DE PRESTAMO', '#' . $id]);
        fputcsv($output, ['Solicitante (Unidad):', $prestamo['unidad_nombre'] ?? 'N/A']);
        fputcsv($output, ['Prestatario:', $prestamo['nombre_prestatario'] ?? 'N/A']);
        fputcsv($output, ['Registrado por:', $prestamo['usuario_nombre']]);
        fputcsv($output, ['Fecha Prestamo:', date('d/m/Y', strtotime($prestamo['fecha_prestamo']))]);
        fputcsv($output, ['Devolucion Esperada:', $prestamo['fecha_devolucion_esperada'] ? date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])) : 'N/A']);
        fputcsv($output, []); // Empty line

        // Columns
        fputcsv($output, ['Gestion', 'Nro Comprobante', 'Tipo Documento', 'Contenedor', 'Ubicacion', 'Estado']);


        foreach ($detalles as $doc) {
            fputcsv($output, [
                $doc['gestion'] ?? '',
                $doc['nro_comprobante'] ?? '',
                $doc['tipo_documento'] ?? '',
                ($doc['tipo_contenedor'] ?? '') . ' #' . ($doc['contenedor_numero'] ?? '') . (!empty($doc['contenedor_codigo_abc']) ? '.' . $doc['contenedor_codigo_abc'] : ''),
                $doc['ubicacion_fisica'] ?? '',
                $doc['estado']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Exportar a PDF (Vista de Impresión)
     */
    public function exportarPdf($id)
    {
        $this->requireAuth();

        $sql = "SELECT ph.*, u.nombre_completo as usuario_nombre, ub.nombre as unidad_nombre 
                FROM prestamos_encabezados ph 
                LEFT JOIN usuarios u ON ph.usuario_id = u.id 
                LEFT JOIN unidades_areas ub ON ph.unidad_area_id = ub.id
                WHERE ph.id = ?";
        $prestamo = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);

        if (!$prestamo) {
            die("Préstamo no encontrado");
        }

        $detalles = $this->prestamoHeader->getDetalles($id);

        require __DIR__ . '/../../views/prestamos/pdf_report.php';
        exit;
    }

    /**
     * PDF de Edición (Sin firmas, para búsqueda de documentos)
     */
    public function pdfEdicion($id)
    {
        $this->requireAuth();

        $sql = "SELECT ph.*, u.nombre_completo as usuario_nombre, ub.nombre as unidad_nombre 
                FROM prestamos_encabezados ph 
                LEFT JOIN usuarios u ON ph.usuario_id = u.id 
                LEFT JOIN unidades_areas ub ON ph.unidad_area_id = ub.id
                WHERE ph.id = ?";
        $prestamo = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);

        if (!$prestamo) {
            die("Préstamo no encontrado");
        }

        // Obtener detalles (documentos tickeados)
        $detalles = $this->prestamoHeader->getDetalles($id);

        // Obtener lista ÚNICA de contenedores con sus ubicaciones
        $sqlContenedores = "SELECT DISTINCT 
                                tc.codigo as tipo_contenedor,
                                cf.numero as contenedor_numero,
                                cf.codigo_abc as contenedor_codigo_abc,
                                u.nombre as ubicacion_fisica
                            FROM prestamos p
                            INNER JOIN documentos d ON p.documento_id = d.id
                            LEFT JOIN contenedores_fisicos cf ON d.contenedor_fisico_id = cf.id
                            LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                            LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
                            WHERE p.encabezado_id = ?
                              AND cf.id IS NOT NULL
                            ORDER BY tc.codigo, cf.numero";
        $contenedores = $this->prestamoHeader->getDb()->fetchAll($sqlContenedores, [$id]);

        require __DIR__ . '/../../views/prestamos/pdf_edicion.php';
        exit;
    }

    /**
     * Vista de Procesar Préstamo (Verificación Física)
     */
    /**
     * Vista de Procesar Préstamo -> Redirige a Editar
     */
    public function procesar($id)
    {
        $this->redirect('/prestamos/editar/' . $id);
    }

    /**
     * Confirmar Proceso (Guardar checkeos)
     */
    public function confirmarProceso()
    {
        $this->requireAuth();

        $encabezado_id = $_POST['encabezado_id'] ?? null;
        $seleccionados = $_POST['documentos'] ?? []; // IDs of Prestamo Details that are FOUND

        if (!$encabezado_id) {
            Session::flash('error', 'ID no válido');
            $this->redirect('/prestamos');
        }

        // 1. Actualizar Encabezado (Datos del formulario unificado)
        $headerData = [
            'unidad_area_id' => $_POST['unidad_area_id'] ?? null,
            'nombre_prestatario' => $_POST['nombre_prestatario'] ?? null,
            'fecha_prestamo' => $_POST['fecha_prestamo'] ?? date('Y-m-d'),
            'fecha_devolucion_esperada' => $_POST['fecha_devolucion_esperada'] ?? null,
            'observaciones' => $_POST['observaciones'] ?? null,
        ];

        // Solo actualizamos si tenemos datos (preventivo)
        if (!empty($headerData['unidad_area_id'])) {
            $this->prestamoHeader->update($encabezado_id, $headerData);
        }

        // Obtener todos los detalles originales para iterar
        $detalles = $this->prestamoHeader->getDetalles($encabezado_id);

        $changes = 0; // Inicializar contador de cambios

        // Variables para tracking de LIBROs procesados
        $librosQuitados = [];

        foreach ($detalles as $doc) {
            $is_found = in_array($doc['id'], $seleccionados);
            $currentDoc = $this->documento->find($doc['documento_id']);

            if ($is_found) {
                // DOCUMENTO ENCONTRADO -> SE PRESTA
                // Actualizar estado detalle a "Prestado"

                $this->prestamo->update($doc['id'], [
                    'estado' => 'Prestado',
                    'fecha_devolucion_real' => null // Limpiar devolución si se vuelve a prestar
                ]);
                $changes++;

                // Actualizar estado documento original
                // Buscar ID de estado "PRESTADO"
                $estadoId = $this->documento->getEstadoId('PRESTADO');
                $this->documento->update($doc['documento_id'], [
                    'estado_documento_id' => $estadoId
                ]);

                // Si este documento tiene contenedor LIBRO, marcar TODOS los docs del libro como Prestado
                if (!empty($currentDoc['contenedor_fisico_id'])) {
                    $sqlTipoContenedor = "SELECT tc.codigo 
                                         FROM contenedores_fisicos cf
                                         INNER JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                                         WHERE cf.id = ?";
                    $contenedor = $this->prestamoHeader->getDb()->fetchOne($sqlTipoContenedor, [$currentDoc['contenedor_fisico_id']]);


                    if ($contenedor && stripos($contenedor['codigo'], 'LIBRO') !== false) {

                        // Es un LIBRO -> Marcar TODOS los documentos secundarios como Prestado
                        // Lógica Auto-Healing: Si los documentos secundarios NO están en el préstamo (préstamos antiguos), AGREGARLOS.

                        $sqlSiblings = "SELECT id, estado_documento_id FROM documentos WHERE contenedor_fisico_id = ?";
                        $siblings = $this->prestamoHeader->getDb()->fetchAll($sqlSiblings, [$currentDoc['contenedor_fisico_id']]);


                        foreach ($siblings as $sib) {
                            if ($sib['id'] == $doc['documento_id'])
                                continue; // Saltar el documento principal que ya estamos procesando

                            // Verificar si existe en el préstamo
                            $sqlCheck = "SELECT id FROM prestamos WHERE encabezado_id = ? AND documento_id = ?";
                            $existing = $this->prestamoHeader->getDb()->fetchOne($sqlCheck, [$encabezado_id, $sib['id']]);

                            if ($existing) {
                                // YA EXISTE: Actualizar estado a Prestado
                                $this->prestamo->update($existing['id'], ['estado' => 'Prestado']);
                                $changes++;
                            } else {
                                // NO EXISTE: Agregarlo (Self-Healing)

                                try {
                                    $newDetail = [
                                        'encabezado_id' => $encabezado_id,
                                        'documento_id' => $sib['id'],
                                        'contenedor_fisico_id' => $currentDoc['contenedor_fisico_id'],
                                        'usuario_id' => Session::user()['id'] ?? 1, // Fallback a admin si falla sesión
                                        'fecha_prestamo' => $_POST['fecha_prestamo'] ?? date('Y-m-d H:i:s'),
                                        'fecha_devolucion_esperada' => $_POST['fecha_devolucion_esperada'] ?? null,
                                        'fecha_devolucion_real' => null,
                                        'observaciones' => null,
                                        'estado' => 'Prestado',
                                        'estado_anterior_id' => $sib['estado_documento_id'],
                                        'es_principal' => 0,
                                        'documento_tipo' => '' // Campo requerido en DB
                                    ];
                                    $this->prestamo->create($newDetail);
                                    $changes++;
                                } catch (\Throwable $e) {
                                    error_log("ERROR CRÍTICO CREATE PRESTAMO: " . $e->getMessage());
                                }
                            }

                            // Actualizar estado del documento físico a PRESTADO
                            $this->documento->update($sib['id'], [
                                'estado_documento_id' => $estadoId
                            ]);
                        }
                    }
                }


            } else {
                // DOCUMENTO NO SELECCIONADO -> NO fue encontrado/prestado
                // Debe quedarse en el préstamo pero con estado "En Proceso"
                // NO se elimina, solo se marca como no encontrado

                $estadoAnteriorId = $doc['estado_anterior_id'] ?? null;

                // Si el documento está en un LIBRO, marcar TODOS los documentos del libro como En Proceso
                if (!empty($currentDoc['contenedor_fisico_id'])) {
                    $contenedorId = $currentDoc['contenedor_fisico_id'];

                    // Verificar si ya procesamos este LIBRO
                    if (in_array($contenedorId, $librosQuitados)) {
                        continue; // Ya procesado, saltar
                    }

                    // Obtener info del contenedor para ver si es LIBRO
                    $sqlContenedor = "SELECT tc.codigo 
                                     FROM contenedores_fisicos cf
                                     INNER JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                                     WHERE cf.id = ?";
                    $contenedor = $this->prestamoHeader->getDb()->fetchOne($sqlContenedor, [$contenedorId]);

                    if ($contenedor && stripos($contenedor['codigo'], 'LIBRO') !== false) {
                        // Es un LIBRO.
                        // Solo si es el documento PRINCIPAL decidimos sobre todo el libro.
                        // Si es secundario y no está en seleccionados, es normal (no se muestra en checkbox).

                        if ($doc['es_principal'] == 0) {
                            continue;
                        }

                        // Es principal y NO está seleccionado -> Marcar TODO el libro como "En Proceso"
                        $librosQuitados[] = $contenedorId;

                        // Marcar este documento principal
                        $this->prestamo->update($doc['id'], ['estado' => 'En Proceso']);
                        if ($estadoAnteriorId) {
                            $this->documento->update($doc['documento_id'], [
                                'estado_documento_id' => $estadoAnteriorId
                            ]);
                        }

                        // Obtener y marcar todos los documentos secundarios del libro
                        $sqlDocsLibro = "SELECT p.id as prestamo_id, p.documento_id, p.estado_anterior_id
                                        FROM prestamos p
                                        INNER JOIN documentos d ON p.documento_id = d.id
                                        WHERE p.encabezado_id = ?
                                          AND d.contenedor_fisico_id = ?
                                          AND p.es_principal = 0"; // Solo secundarios
                        $docsSecundarios = $this->prestamoHeader->getDb()->fetchAll($sqlDocsLibro, [$encabezado_id, $contenedorId]);

                        foreach ($docsSecundarios as $docSec) {
                            // Actualizar detalle de préstamo a "En Proceso" (no encontrado)
                            $this->prestamo->update($docSec['prestamo_id'], ['estado' => 'En Proceso']);

                            // Restaurar estado anterior del documento
                            if ($docSec['estado_anterior_id']) {
                                $this->documento->update($docSec['documento_id'], [
                                    'estado_documento_id' => $docSec['estado_anterior_id']
                                ]);
                            }
                        }
                    } else {
                        // No es LIBRO - Solo marcar este documento como "En Proceso"
                        $this->prestamo->update($doc['id'], ['estado' => 'En Proceso']);

                        if ($estadoAnteriorId) {
                            $this->documento->update($doc['documento_id'], [
                                'estado_documento_id' => $estadoAnteriorId
                            ]);
                        }
                    }
                } else {
                    // No tiene contenedor - Solo marcar este documento como "En Proceso"
                    $this->prestamo->update($doc['id'], ['estado' => 'En Proceso']);

                    if ($estadoAnteriorId) {
                        $this->documento->update($doc['documento_id'], [
                            'estado_documento_id' => $estadoAnteriorId
                        ]);
                    }
                }
            }
        }


        // Recalcular estado del Encabezado
        $newDetalles = $this->prestamoHeader->getDetalles($encabezado_id);
        $allReturned = true;
        $anyPrestado = false;

        foreach ($newDetalles as $d) {
            if ($d['estado'] === 'Prestado') {
                $anyPrestado = true;
                $allReturned = false; // Si hay uno prestado, no están todos devueltos
            }
        }

        if ($allReturned) {
            $this->prestamoHeader->update($encabezado_id, ['estado' => 'Devuelto']);
        } elseif ($anyPrestado) {
            $this->prestamoHeader->update($encabezado_id, ['estado' => 'Prestado']);
        }

        AuditLogger::log('CONFIRMAR_PROCESO', 'Prestamos', $encabezado_id, "Se confirmó proceso de préstamo/devolución. Cambios: $changes item(s).");

        // Log específico si se liberó todo
        if ($allReturned && $changes > 0) {
            AuditLogger::log('DEVOLVER_TOTAL', 'Prestamos', $encabezado_id, "Préstamo finalizado (Todos devueltos)");
        }

        Session::flash('success', "Se actualizaron $changes documentos.");
        $this->redirect('/prestamos/ver/' . $encabezado_id);
    }

    /**
     * Revertir Estado a En Proceso
     */
    public function revertirProceso($id)
    {
        $this->requireAuth();

        // Verificar existencia y estado
        $sqlHeader = "SELECT * FROM prestamos_encabezados WHERE id = ?";
        $header = $this->prestamoHeader->getDb()->fetchOne($sqlHeader, [$id]);

        if (!$header) {
            Session::flash('error', 'Préstamo no encontrado');
            $this->redirect('/prestamos');
        }

        // Permitimos revertir si NO está Devuelto (es decir, Prestado o En Proceso)
        if ($header['estado'] !== 'Devuelto') {

            // Revertir solo el Encabezado a 'En Proceso'
            // NO modificamos los detalles - mantienen su estado (Prestado o En Proceso)
            // para que en la vista de edición se recupere correctamente qué documentos fueron confirmados
            $this->prestamoHeader->update($id, ['estado' => 'En Proceso']);

            Session::flash('success', 'Préstamo revertido a estado de verificación (En Proceso).');
        } else {
            Session::flash('warning', 'No se puede procesar un préstamo ya devuelto.');
        }

        // Redirigir a la vista de Editar para verificar de nuevo
        $this->redirect('/prestamos/editar/' . $id);
    }

    public function vistaImportar()
    {
        $this->requireAuth();
        $unidades = $this->unidadArea->getActive();
        $this->view('prestamos.importar', [
            'unidades' => $unidades,
            'user' => $this->getCurrentUser()
        ]);
    }

    public function procesarImportacion()
    {
        $this->requireAuth();

        if (empty($_FILES['excel_file']['name'])) {
            Session::flash('error', 'Debe seleccionar un archivo Excel');
            $this->redirect('/prestamos/importar');
        }

        try {
            $inputFileName = $_FILES['excel_file']['tmp_name'];
            $spreadsheet = IOFactory::load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Asumimos primera fila es encabezado
            // 0: Tipo Documento, 1: GESTION, 2: NRO. DE COMPROBANTE DIARIO

            $found_ids = [];
            $missing = [];

            // Skip header (row 0)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty($row[0]) && empty($row[1]) && empty($row[2]))
                    continue;

                $tipo = trim($row[0]);
                $gestion = intval($row[1]);
                $nro = trim($row[2]);

                $sql = "SELECT id, estado_documento, contenedor_fisico_id FROM documentos WHERE gestion = ? AND nro_comprobante = ?";
                $doc = $this->documento->getDb()->fetchOne($sql, [$gestion, $nro]);

                if ($doc) {
                    if ($doc['estado_documento'] === 'DISPONIBLE') {
                        $found_ids[] = $doc; // Save whole doc array to access contenedor_id
                    } else {
                        $missing[] = "Fila " . ($i + 1) . ": Documento encontrado pero NO disponible (Estado: {$doc['estado_documento']})";
                    }
                } else {
                    $missing[] = "Fila " . ($i + 1) . ": No encontrado [Gestion: $gestion, Nro: $nro]";
                }
            }

            if (empty($found_ids)) {
                Session::flash('error', 'No se encontraron documentos válidos para prestar en el archivo.');
                if (!empty($missing)) {
                    Session::flash('info', 'Errores: ' . implode('<br>', array_slice($missing, 0, 5)) . (count($missing) > 5 ? '...' : ''));
                }
                $this->redirect('/prestamos/importar');
            }

            $headerData = [
                'usuario_id' => Session::user()['id'],
                'unidad_area_id' => $_POST['unidad_area_id'],
                'nombre_prestatario' => $_POST['nombre_prestatario'] ?? null,
                'fecha_prestamo' => date('Y-m-d'),
                'fecha_devolucion_esperada' => $_POST['fecha_devolucion'],
                'observaciones' => "Importado desde Excel (" . $_FILES['excel_file']['name'] . ")",
                'estado' => 'Prestado'
            ];

            $headerId = $this->prestamoHeader->create($headerData);

            if (!$headerId) {
                throw new \Exception("Error al crear cabecera de préstamo");
            }

            $count = 0;
            foreach ($found_ids as $doc) {
                $docId = $doc['id'];
                $data = [
                    'encabezado_id' => $headerId,
                    'documento_id' => $docId,
                    'contenedor_fisico_id' => $doc['contenedor_fisico_id'] ?? null,
                    'usuario_id' => $_POST['unidad_area_id'],
                    'fecha_prestamo' => date('Y-m-d'),
                    'fecha_devolucion_esperada' => $_POST['fecha_devolucion'],
                    'estado' => 'Prestado'
                ];

                if ($this->prestamo->create($data)) {
                    $this->documento->update($docId, ['estado_documento_id' => $this->documento->getEstadoId('PRESTADO')]);
                    $count++;
                }
            }

            Session::flash('success', "Préstamo importado exitosamente! Se prestaron $count documentos.");
            $this->redirect('/prestamos');

        } catch (\Exception $e) {
            Session::flash('error', 'Error al procesar archivo: ' . $e->getMessage());
            $this->redirect('/prestamos/importar');
        }
    }

    /**
     * Actualizar estados de devolución (Bulk Update)
     */
    public function actualizarEstados()
    {
        $this->requireAuth();

        $encabezado_id = $_POST['encabezado_id'] ?? null;
        $devueltos = $_POST['devueltos'] ?? []; // IDs checked
        $action = $_POST['action'] ?? 'actualizar'; // devolver | revertir

        if (!$encabezado_id) {
            Session::flash('error', 'ID de préstamo no válido');
            $this->redirect('/prestamos');
        }

        // Get all details for this header
        $detalles = $this->prestamoHeader->getDetalles($encabezado_id);

        $changes = 0;

        foreach ($detalles as $doc) {
            $is_checked = in_array($doc['id'], $devueltos);
            $current_status = $doc['estado'];

            if ($action === 'devolver') {
                // Only process items that are CHECKED and currently PRESTADO
                if ($is_checked && $current_status === 'Prestado') {
                    // Check if this document is part of a LIBRO container
                    $sqlContainer = "SELECT d.contenedor_fisico_id, tc.codigo as tipo_contenedor
                                     FROM documentos d
                                     LEFT JOIN contenedores_fisicos cf ON d.contenedor_fisico_id = cf.id
                                     LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                                     WHERE d.id = ?";
                    $containerInfo = $this->documento->getDb()->fetchOne($sqlContainer, [$doc['documento_id']]);

                    $tipoContenedor = strtoupper($containerInfo['tipo_contenedor'] ?? '');
                    $containerId = $containerInfo['contenedor_fisico_id'];
                    $isLibro = ($tipoContenedor === 'LIBRO' && $containerId);

                    if ($isLibro) {
                        // Get ALL prestamo details for documents in this LIBRO that belong to this loan
                        $sqlLibroDocs = "SELECT p.id, p.documento_id, p.estado_anterior_id
                                         FROM prestamos p
                                         JOIN documentos d ON p.documento_id = d.id
                                         WHERE p.encabezado_id = ?
                                         AND d.contenedor_fisico_id = ?
                                         AND p.estado IN ('Prestado', 'En Proceso')";
                        $libroDetalles = $this->prestamoHeader->getDb()->fetchAll($sqlLibroDocs, [$encabezado_id, $containerId]);

                        // Return all documents from the LIBRO
                        foreach ($libroDetalles as $det) {
                            $this->prestamo->update($det['id'], [
                                'fecha_devolucion_real' => date('Y-m-d'),
                                'estado' => 'Devuelto'
                            ]);

                            // Restore previous state
                            $estadoId = !empty($det['estado_anterior_id'])
                                ? $det['estado_anterior_id']
                                : $this->documento->getEstadoId('DISPONIBLE');

                            $this->documento->update($det['documento_id'], ['estado_documento_id' => $estadoId]);
                            $changes++;
                        }
                    } else {
                        // Not a LIBRO, just return this single document
                        $this->prestamo->update($doc['id'], [
                            'fecha_devolucion_real' => date('Y-m-d'),
                            'estado' => 'Devuelto'
                        ]);

                        // Restore previous state if available, otherwise default to DISPONIBLE
                        $estadoId = !empty($doc['estado_anterior_id'])
                            ? $doc['estado_anterior_id']
                            : $this->documento->getEstadoId('DISPONIBLE');

                        $this->documento->update($doc['documento_id'], ['estado_documento_id' => $estadoId]);
                        $changes++;
                    }
                }
            } elseif ($action === 'revertir') {
                // Process items that are CURRENTLY DEVUELTO but usage UNCHECKED them
                // This means they want to "un-return" them
                if (!$is_checked && $current_status === 'Devuelto') {
                    $this->prestamo->update($doc['id'], [
                        'fecha_devolucion_real' => null,
                        'estado' => 'Prestado'
                    ]);
                    $this->documento->update($doc['documento_id'], ['estado_documento_id' => $this->documento->getEstadoId('PRESTADO')]);
                    $changes++;
                }
            }
        }

        // Update Header Status
        $newDetalles = $this->prestamoHeader->getDetalles($encabezado_id);
        $allReturned = true;
        if ($allReturned) {
            $this->prestamoHeader->update($encabezado_id, ['estado' => 'Devuelto']);
        } else {
            // Check if any is 'Prestado'
            $anyPrestado = false;
            foreach ($newDetalles as $d) {
                if ($d['estado'] === 'Prestado') {
                    $anyPrestado = true;
                    break;
                }
            }
            if ($anyPrestado) {
                $this->prestamoHeader->update($encabezado_id, ['estado' => 'Prestado']);
            }
        }

        AuditLogger::log('ACTUALIZAR_ESTADOS', 'Prestamos', $encabezado_id, "Se actualizaron estados de $changes item(s) (Devolución/Reversión).");

        // Log específico si se liberó todo
        if ($allReturned && $changes > 0) {
            AuditLogger::log('DEVOLVER_TOTAL', 'Prestamos', $encabezado_id, "Préstamo finalizado (Todos devueltos)");
        }

        Session::flash('success', "Se actualizaron $changes documentos.");
        $this->redirect('/prestamos/ver/' . $encabezado_id);
    }

    /**
     * Eliminar Préstamo (Encabezado y Detalles)
     */
    public function eliminar($id)
    {
        $this->requireAuth();

        // 1. Verificar existencia
        $sql = "SELECT * FROM prestamos_encabezados WHERE id = ?";
        $header = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);

        if (!$header) {
            Session::flash('error', 'Préstamo no encontrado');
            $this->redirect('/prestamos');
        }

        // 2. Liberar documentos (restaurar estados anteriores)
        $detalles = $this->prestamoHeader->getDetalles($id);
        foreach ($detalles as $doc) {
            // Restaurar el estado anterior del documento si existe
            if (!empty($doc['estado_anterior_id'])) {
                $this->documento->update($doc['documento_id'], [
                    'estado_documento_id' => $doc['estado_anterior_id']
                ]);
            } else {
                // Si no hay estado anterior, marcar como DISPONIBLE
                $disponibleId = $this->documento->getEstadoId('DISPONIBLE');
                if ($disponibleId) {
                    $this->documento->update($doc['documento_id'], [
                        'estado_documento_id' => $disponibleId
                    ]);
                }
            }
        }

        // 3. Eliminar detalles
        $this->prestamoHeader->getDb()->query("DELETE FROM prestamos WHERE encabezado_id = ?", [$id]);

        // 4. Eliminar encabezado
        $this->prestamoHeader->delete($id);

        AuditLogger::log('ELIMINAR', 'Prestamos', $id, "Se eliminó préstamo ID $id y se liberaron sus documentos.");
        Session::flash('success', 'Préstamo eliminado y documentos liberados.');
        $this->redirect('/prestamos');
    }

    /**
     * Agregar Documento a Préstamo (AJAX o POST)
     */
    public function agregarDetalle()
    {
        $this->requireAuth();

        $encabezado_id = $_POST['encabezado_id'] ?? null;
        $documento_id = $_POST['documento_id'] ?? null;

        if (!$encabezado_id || !$documento_id) {
            Session::flash('error', 'Datos incompletos');
            $this->redirect('/prestamos/editar/' . $encabezado_id); // Redirect to edit view
        }

        // Verificar documento (RELAXED CHECK per user request)
        $doc = $this->documento->find($documento_id);

        if (!$doc) {
            Session::flash('error', 'Documento no encontrado');
            $this->redirect('/prestamos/editar/' . $encabezado_id);
        }

        // Removed status check: "deberiamos poder adicionar documentos indistintamente de su estado"

        // Obtener header para fechas
        $header = $this->prestamoHeader->getDb()->fetchOne("SELECT * FROM prestamos_encabezados WHERE id = ?", [$encabezado_id]);

        // Validar si el documento ya está en este préstamo
        $existe = $this->prestamo->getDb()->fetchOne(
            "SELECT id FROM prestamos WHERE encabezado_id = ? AND documento_id = ?", 
            [$encabezado_id, $documento_id]
        );

        if ($existe) {
            Session::flash('error', 'El documento ya se encuentra en este préstamo.');
        } else {
            $data = [
                'encabezado_id' => $encabezado_id,
                'documento_id' => $documento_id,
                'contenedor_fisico_id' => $doc['contenedor_fisico_id'] ?? null,
                'usuario_id' => Session::user()['id'],
                'fecha_prestamo' => $header['fecha_prestamo'],
                'fecha_devolucion_esperada' => $header['fecha_devolucion_esperada'],
                'estado_anterior_id' => $doc['estado_documento_id'], // Store original state ID
                'estado' => 'En Proceso'
            ];
    
            if ($this->prestamo->create($data)) {
                Session::flash('success', 'Documento agregado');
            } else {
                Session::flash('error', 'Error al agregar documento');
            }
        }

        // Persist filters
        $filters = [
            'search' => $_POST['search'] ?? '',
            'gestion' => $_POST['gestion'] ?? '',
            'tipo_documento' => $_POST['tipo_documento'] ?? '',
            'page' => $_POST['page'] ?? 1,
            'per_page' => $_POST['per_page'] ?? 10,
            'd_per_page' => $_POST['d_per_page'] ?? 10
            // Note: 'd_page' is explicitly OMITTED to let the view default to the last page (newly added item)
        ];
        $queryString = http_build_query(array_filter($filters));
        
        $this->redirect('/prestamos/editar/' . $encabezado_id . ($queryString ? '?' . $queryString : ''));
    }

    /**
     * Quitar Documento de Préstamo
     */
    public function quitarDetalle($id)
    {
        $this->requireAuth();

        // Get detalle info with container info
        $sql = "SELECT p.*, d.contenedor_fisico_id, tc.codigo as tipo_contenedor
            FROM prestamos p
            LEFT JOIN documentos d ON p.documento_id = d.id
            LEFT JOIN contenedores_fisicos cf ON d.contenedor_fisico_id = cf.id
            LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
            WHERE p.id = ?";
        $detalle = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);

        if (!$detalle) {
            Session::flash('error', 'Detalle no encontrado');
            $this->redirect('/prestamos');
        }

        $encabezado_id = $detalle['encabezado_id'];
        $documentoId = $detalle['documento_id'];
        $containerId = $detalle['contenedor_fisico_id'];
        $tipoContenedor = strtoupper($detalle['tipo_contenedor'] ?? '');

        // Check if this is a LIBRO container
        $isLibro = ($tipoContenedor === 'LIBRO' && $containerId);

        // If LIBRO, get all documents from this container that are in THIS loan
        $docIdsToFree = [$documentoId];

        if ($isLibro) {
            // Get all prestamo detail IDs for documents in this LIBRO that belong to this loan
            $sqlLibroDocs = "SELECT p.id, p.documento_id, p.estado_anterior_id
                         FROM prestamos p
                         JOIN documentos d ON p.documento_id = d.id
                         WHERE p.encabezado_id = ?
                         AND d.contenedor_fisico_id = ?";
            $libroDetalles = $this->prestamoHeader->getDb()->fetchAll($sqlLibroDocs, [$encabezado_id, $containerId]);

            // Free all documents from the LIBRO
            foreach ($libroDetalles as $det) {
                // Restore original state for each document
                $estadoAnteriorId = $det['estado_anterior_id'] ?? $this->documento->getEstadoId('DISPONIBLE');
                $this->documento->update($det['documento_id'], ['estado_documento_id' => $estadoAnteriorId]);

                // Delete prestamo detail record
                $this->prestamoHeader->getDb()->query("DELETE FROM prestamos WHERE id = ?", [$det['id']]);
            }

            Session::flash('success', 'LIBRO completo removido del préstamo. Todos los documentos fueron liberados.');
        } else {
            // Not a LIBRO, just restore this single document
            $estadoAnteriorId = $detalle['estado_anterior_id'] ?? $this->documento->getEstadoId('DISPONIBLE');
            $this->documento->update($documentoId, ['estado_documento_id' => $estadoAnteriorId]);

            // Delete prestamo detail record
            $this->prestamoHeader->getDb()->query("DELETE FROM prestamos WHERE id = ?", [$id]);

            Session::flash('success', 'Documento eliminado del préstamo y estado restaurado');
        }

        $this->redirect('/prestamos/editar/' . $encabezado_id);
    }

    /**
     * Editar Préstamo (Header + Agregar/Quitar Documentos)
     */
    public function editar($id)
    {
        $this->requireAuth();

        // 1. Obtener Datos del Encabezado
        $sql = "SELECT ph.*, 
                       u.nombre_completo as usuario_nombre, 
                       ub.nombre as unidad_nombre 
                FROM prestamos_encabezados ph
                LEFT JOIN usuarios u ON ph.usuario_id = u.id
                LEFT JOIN unidades_areas ub ON ph.unidad_area_id = ub.id
                WHERE ph.id = ?";
        $prestamo = $this->prestamoHeader->getDb()->fetchOne($sql, [$id]);

        if (!$prestamo) {
            Session::flash('error', 'Préstamo no encontrado');
            $this->redirect('/prestamos');
        }

        // 2. Obtener Detalles Actuales
        $detalles = $this->prestamoHeader->getDetalles($id);

        // Filter to show only PRINCIPAL documents (directly requested, not LIBRO-pulled)
        $detalles = array_filter($detalles, function ($d) {
            return !isset($d['es_principal']) || $d['es_principal'] == 1;
        });

        // 3. Lógica de Búsqueda (Copiada de nuevo())
        // Filtros
        $filtros = [
            'search' => $_GET['search'] ?? '',
            'gestion' => $_GET['gestion'] ?? '',
            'tipo_documento' => $_GET['tipo_documento'] ?? '',
            'sort' => $_GET['sort'] ?? 'gestion',
            'order' => $_GET['order'] ?? 'DESC',
            'page' => $_GET['page'] ?? 1,
            'per_page' => $_GET['per_page'] ?? 20
        ];

        // Validar sort col
        $allowedSort = ['tipo_documento', 'gestion', 'nro_comprobante', 'ubicacion', 'estado'];
        if (!in_array($filtros['sort'], $allowedSort))
            $filtros['sort'] = 'gestion';

        $page = (int) $filtros['page'];
        $perPage = (int) $filtros['per_page'];
        $offset = ($page - 1) * $perPage;

        // Construir Query de Búsqueda
        $where = ["rd.estado_documento_id IN (SELECT id FROM estados WHERE nombre IN ('DISPONIBLE', 'NO UTILIZADO', 'ANULADO', 'FALTA'))"]; // Exclude PRESTADO unless it's in THIS loan (but logic separates them)
        $params = [];

        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            if (preg_match('/^(\d+)-(\d+)$/', $search, $matches)) {
                $min = min((int) $matches[1], (int) $matches[2]);
                $max = max((int) $matches[1], (int) $matches[2]);
                $where[] = "CAST(rd.nro_comprobante AS UNSIGNED) BETWEEN ? AND ?";
                $params[] = $min;
                $params[] = $max;
            } else {
                $where[] = "(rd.nro_comprobante = ? OR rd.codigo_abc = ?)";
                $params[] = $search;
                $params[] = $search;
            }
        }

        if (!empty($filtros['gestion'])) {
            $where[] = "rd.gestion = ?";
            $params[] = $filtros['gestion'];
        }

        if (!empty($filtros['tipo_documento'])) {
            $where[] = "t.codigo = ?";
            $params[] = $filtros['tipo_documento'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Sorting
        $orderBy = "rd.gestion DESC, rd.nro_comprobante DESC";
        $orderDir = $filtros['order'] === 'ASC' ? 'ASC' : 'DESC';
        if ($filtros['sort'] === 'gestion')
            $orderBy = "rd.gestion $orderDir";
        if ($filtros['sort'] === 'nro_comprobante') {
            if ($orderDir === 'ASC')
                $orderBy = "CAST(rd.nro_comprobante AS UNSIGNED) ASC, rd.nro_comprobante ASC";
            else
                $orderBy = "CAST(rd.nro_comprobante AS UNSIGNED) DESC, rd.nro_comprobante DESC";
        }
        if ($filtros['sort'] === 'ubicacion')
            $orderBy = "ub.nombre $orderDir";
        if ($filtros['sort'] === 'estado')
            $orderBy = "e.nombre $orderDir";

        // Execute Search ONLY if search is active or some filter is set
        $hasFilters = !empty($filtros['search']) || !empty($filtros['gestion']) || !empty($filtros['tipo_documento']);

        $documentosDisponibles = [];
        $total = 0;

        if ($hasFilters) {
            // Execute Count
            $sqlCount = "SELECT COUNT(*) as total FROM documentos rd 
                         LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id 
                         LEFT JOIN ubicaciones ub ON cf.ubicacion_id = ub.id 
                         LEFT JOIN tipo_documento t ON rd.tipo_documento_id = t.id
                         LEFT JOIN estados e ON rd.estado_documento_id = e.id
                         {$whereClause}";
            $resCount = $this->documento->getDb()->fetchOne($sqlCount, $params);
            $total = $resCount['total'] ?? 0;

            // Execute Search
            $sqlDocs = "SELECT rd.*, tc.codigo as tipo_contenedor, cf.numero as contenedor_numero, cf.codigo_abc as contenedor_codigo_abc, ub.nombre as ubicacion_fisica,
                               e.nombre as estado_documento,
                               t.codigo as tipo_documento
                        FROM documentos rd
                        LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                        LEFT JOIN ubicaciones ub ON cf.ubicacion_id = ub.id
                        LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                        LEFT JOIN estados e ON rd.estado_documento_id = e.id
                        LEFT JOIN tipo_documento t ON rd.tipo_documento_id = t.id
                        {$whereClause}
                        ORDER BY {$orderBy}
                        LIMIT {$perPage} OFFSET {$offset}";

            $documentosDisponibles = $this->documento->getDb()->fetchAll($sqlDocs, $params);
        }

        $this->view('prestamos.editar', [
            'prestamo' => $prestamo,
            'detalles' => $detalles,
            'documentosDisponibles' => $documentosDisponibles,
            'unidades' => $this->unidadArea->getActive(),
            'tiposDocumento' => $this->tipoDocumento->getAllOrderedById(),
            'filtros' => $filtros,
            'paginacion' => [
                'total' => $total,
                'per_page' => $perPage,
                'page' => $page,
                'total_pages' => ceil($total / $perPage)
            ],
            'user' => $this->getCurrentUser()
        ]);
    }

    /**
     * Actualizar Encabezado del Préstamo
     */
    public function actualizarEncabezado($id)
    {
        $this->requireAuth();

        $data = [
            'unidad_area_id' => $_POST['unidad_area_id'] ?? null,
            'nombre_prestatario' => $_POST['nombre_prestatario'] ?? null,
            'fecha_prestamo' => $_POST['fecha_prestamo'] ?? null,
            'fecha_devolucion_esperada' => $_POST['fecha_devolucion_esperada'] ?? null,
            'observaciones' => $_POST['observaciones'] ?? null
        ];

        // Validaciones básicas
        if (empty($data['unidad_area_id']) || empty($data['fecha_prestamo'])) {
            Session::flash('error', 'Unidad y Fecha Préstamo son obligatorios');
            $this->redirect('/prestamos/editar/' . $id);
        }

        if ($this->prestamoHeader->update($id, $data)) {
            // También actualizar fechas en los detalles si cambiaron en el header?
            // Generalmente sí, para mantener consistencia.
            $this->prestamoHeader->getDb()->query(
                "UPDATE prestamos SET fecha_prestamo = ?, fecha_devolucion_esperada = ? WHERE encabezado_id = ?",
                [$data['fecha_prestamo'], $data['fecha_devolucion_esperada'], $id]
            );

            Session::flash('success', 'Préstamo actualizado exitosamente');
        } else {
            Session::flash('error', 'Error al actualizar el préstamo');
        }

        $this->redirect('/prestamos/editar/' . $id);
    }
}
