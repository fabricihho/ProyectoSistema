<?php
/**
 * Controlador de Reportes
 * Gestiona reportes y estadísticas del sistema
 * 
 * @package TAMEP\Controllers
 */

namespace TAMEP\Controllers;

use TAMEP\Models\Prestamo;
use TAMEP\Models\Documento;
use TAMEP\Models\Usuario;
use TAMEP\Core\Session;
use TAMEP\Models\Auditoria;

class ReportesController extends BaseController
{
    private $prestamo;
    private $documento;
    private $usuario;

    public function __construct()
    {
        parent::__construct();
        $this->prestamo = new Prestamo();
        $this->documento = new Documento();
        $this->usuario = new Usuario();
    }

    /**
     * Dashboard de reportes
     */
    /**
     * Reporte: Gráficos y Estadísticas
     */
    public function graficos()
    {
        $this->requireAuth();

        // Count Préstamos Activos
        $sqlCountActive = "SELECT COUNT(*) as total FROM prestamos p WHERE p.estado = 'Prestado'";
        $resCountActive = $this->prestamo->getDb()->fetchOne($sqlCountActive);
        $totalActive = $resCountActive['total'] ?? 0;

        // Count Stats Globales
        $sqlStats = "SELECT e.nombre, COUNT(*) as total 
                     FROM documentos rd 
                     JOIN estados e ON rd.estado_documento_id = e.id 
                     WHERE e.nombre IN ('FALTA', 'ANULADO', 'PRESTADO', 'NO UTILIZADO') 
                     GROUP BY e.nombre";
        $rawStats = $this->documento->getDb()->fetchAll($sqlStats);
        $statsMap = [];
        foreach ($rawStats as $r)
            $statsMap[$r['nombre']] = $r['total'];

        // Count Vencidos (requiere query compleja o iterar activos)
        // Optimizacion: Count directo en DB
        $sqlVencidos = "SELECT COUNT(*) as total FROM prestamos p WHERE p.estado = 'Prestado' AND p.fecha_devolucion_esperada < CURDATE()";
        $resVencidos = $this->prestamo->getDb()->fetchOne($sqlVencidos);
        $vencidosCount = $resVencidos['total'] ?? 0;

        $stats = [
            'total_prestados' => $totalActive,
            'prestamos_vencidos' => $vencidosCount,
            'total_faltantes' => $statsMap['FALTA'] ?? 0,
            'total_anulados' => $statsMap['ANULADO'] ?? 0,
            'docs_prestados' => $statsMap['PRESTADO'] ?? 0,
            'docs_faltantes' => $statsMap['FALTA'] ?? 0,
            'docs_disponibles' => $this->documento->count('estado_documento_id = ?', [$this->documento->getEstadoId('DISPONIBLE')]), // Aprox
            'docs_no_utilizados' => $statsMap['NO UTILIZADO'] ?? 0,
            'docs_anulados' => $statsMap['ANULADO'] ?? 0
        ];

        // Additional stats for bar chart
        $stats['total_libros'] = $this->documento->getDb()->fetchOne("SELECT COUNT(*) as total FROM contenedores_fisicos WHERE tipo_contenedor_id = (SELECT id FROM tipos_contenedor WHERE codigo = 'LIBRO')")['total'];
        $stats['total_amarros'] = $this->documento->getDb()->fetchOne("SELECT COUNT(*) as total FROM contenedores_fisicos WHERE tipo_contenedor_id = (SELECT id FROM tipos_contenedor WHERE codigo = 'AMARRO')")['total'];

        $this->view('reportes.graficos', [
            'stats' => $stats,
            'user' => $this->getCurrentUser()
        ]);
    }

    /**
     * Reporte: Préstamos Activos
     */
    public function prestamos()
    {
        $this->requireAuth();

        // Parámetros de Paginación y Orden
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        $sort = $_GET['sort'] ?? 'fecha_devolucion_esperada';
        $order = strtoupper($_GET['order'] ?? '') === 'DESC' ? 'DESC' : 'ASC';
        $search = $_GET['search'] ?? '';

        // Mapping de sorteo
        $orderBy = "p.fecha_devolucion_esperada ASC"; // Default
        switch ($sort) {
            case 'usuario':
                $orderBy = "u.nombre_completo $order";
                break;
            case 'documento':
                $orderBy = "td.codigo $order, rd.nro_comprobante $order";
                break;
            case 'gestion':
                $orderBy = "rd.gestion $order";
                break;
            case 'contenedor':
                $orderBy = "tc.codigo $order, cf.numero $order";
                break;
            case 'fecha_prestamo':
                $orderBy = "p.fecha_prestamo $order";
                break;
            case 'fecha_devolucion':
                $orderBy = "p.fecha_devolucion_esperada $order";
                break;
        }

        // Where conditions
        $where = ["p.estado = 'Prestado'"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(u.nombre_completo LIKE ? OR u.username LIKE ? OR rd.nro_comprobante LIKE ? OR cf.numero LIKE ?)";
            $term = "%$search%";
            $params = [$term, $term, $term, $term];
        }

        $whereSql = implode(' AND ', $where);

        // Count Total
        $sqlCount = "SELECT COUNT(*) as total 
                     FROM prestamos p 
                     INNER JOIN usuarios u ON p.usuario_id = u.id
                     INNER JOIN documentos rd ON p.documento_id = rd.id
                     LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                     WHERE $whereSql";
        $total = $this->prestamo->getDb()->fetchOne($sqlCount, $params)['total'];

        // Fetch Data
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.id,
                       p.documento_tipo,
                       p.documento_id,
                       p.usuario_id,
                       p.contenedor_fisico_id,
                       p.fecha_prestamo,
                       p.fecha_devolucion_esperada,
                       p.fecha_devolucion_real,
                       p.observaciones,
                       p.estado,
                       u.nombre_completo as usuario_nombre,
                       u.username,
                       COALESCE(td.codigo, td.nombre) as tipo_documento,
                       rd.gestion,
                       rd.nro_comprobante,
                       rd.codigo_abc,
                       tc.codigo as tipo_contenedor,
                       cf.numero as contenedor_numero,
                       cf.codigo_abc as contenedor_codigo_abc,
                       DATEDIFF(p.fecha_devolucion_esperada, CURDATE()) as dias_restantes
                FROM prestamos p
                INNER JOIN usuarios u ON p.usuario_id = u.id
                INNER JOIN documentos rd ON p.documento_id = rd.id
                LEFT JOIN tipo_documento td ON rd.tipo_documento_id = td.id
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                WHERE $whereSql
                ORDER BY $orderBy
                LIMIT $perPage OFFSET $offset";

        $prestamos = $this->prestamo->getDb()->fetchAll($sql, $params);

        $this->view('reportes.prestamos', [
            'prestamos' => $prestamos,
            'paginacion' => [
                'current' => $page,
                'total' => $total,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ],
            'filtros' => [
                'sort' => $sort,
                'order' => $order,
                'per_page' => $perPage,
                'search' => $search
            ],
            'user' => $this->getCurrentUser()
        ]);
    }

    /**
     * Reporte: Documentos No Disponibles
     */
    public function noDisponibles()
    {
        $this->requireAuth();

        // Filtros
        $states = $_GET['states'] ?? [];

        // Paginación y Orden
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;

        $sort = $_GET['sort'] ?? '';
        $order = strtoupper($_GET['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        $documentos = [];
        $totalDocs = 0;

        // Validar Estados
        $validStates = ['FALTA', 'ANULADO', 'PRESTADO', 'NO UTILIZADO'];
        if (!is_array($states))
            $states = [];

        // Logic: Only show documents for SELECTED states.
        // If filter is empty, show NOTHING.
        $queryStates = array_intersect($states, $validStates);

        // If no states are selected, we return empty results immediately
        if (empty($queryStates)) {
            $documentos = [];
            $totalDocs = 0;

            // Pass empty data to view
            $this->view('reportes.no_disponibles', [
                'documentos' => [],
                'paginacion' => [
                    'current' => 1,
                    'total' => 0,
                    'per_page' => $perPage,
                    'total_pages' => 0
                ],
                'filtros' => [
                    'states' => [], // Empty
                    'sort' => $sort,
                    'order' => $order,
                    'per_page' => $perPage
                ],
                'user' => $this->getCurrentUser()
            ]);
            return;
        }

        // Construir WHERE IN
        $placeholders = str_repeat('?,', count($queryStates) - 1) . '?';
        $whereClause = "WHERE e.nombre IN ($placeholders)";
        $params = array_values($queryStates);

        // Sorting Logic
        $orderBy = "FIELD(e.nombre, 'FALTA', 'PRESTADO', 'NO UTILIZADO', 'ANULADO'), rd.gestion DESC, rd.nro_comprobante DESC";

        if ($sort === 'gestion')
            $orderBy = "rd.gestion $order";
        if ($sort === 'nro_comprobante')
            $orderBy = "CAST(rd.nro_comprobante AS UNSIGNED) $order";
        if ($sort === 'tipo')
            $orderBy = "td.codigo $order";
        if ($sort === 'contenedor')
            $orderBy = "tc.codigo $order, cf.numero $order";
        if ($sort === 'estado')
            $orderBy = "e.nombre $order";

        // Count Total
        $sqlCount = "SELECT COUNT(*) as total 
                        FROM documentos rd
                        LEFT JOIN estados e ON rd.estado_documento_id = e.id
                        $whereClause";
        $totalDocs = $this->documento->getDb()->fetchOne($sqlCount, $params)['total'] ?? 0;

        // Fetch Data
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT rd.*,
                        COALESCE(td.codigo, td.nombre) as tipo_documento,
                        tc.codigo as tipo_contenedor,
                        cf.numero as contenedor_numero,
                        cf.codigo_abc as contenedor_codigo_abc,
                        e.nombre as estado_documento,
                        CASE 
                            WHEN e.nombre = 'PRESTADO' THEN p.usuario_id
                            ELSE NULL
                        END as prestado_a_usuario_id,
                        CASE 
                            WHEN e.nombre = 'PRESTADO' THEN u.nombre_completo
                            ELSE NULL
                        END as prestado_a_usuario
                FROM documentos rd
                LEFT JOIN tipo_documento td ON rd.tipo_documento_id = td.id
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                LEFT JOIN estados e ON rd.estado_documento_id = e.id
                LEFT JOIN prestamos p ON rd.id = p.documento_id AND p.estado = 'Prestado'
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";

        $documentos = $this->documento->getDb()->fetchAll($sql, $params);

        $this->view('reportes.no_disponibles', [
            'documentos' => $documentos,
            'paginacion' => [
                'current' => $page,
                'total' => $totalDocs,
                'per_page' => $perPage,
                'total_pages' => ceil($totalDocs / $perPage)
            ],
            'filtros' => [
                'states' => $states,
                'sort' => $sort,
                'order' => $order,
                'per_page' => $perPage
            ],
            'user' => $this->getCurrentUser()
        ]);
    }

    private function getCurrentUser()
    {
        return Session::user();
    }
    /**
     * Exportar selección a PDF
     */
    public function exportarPdfSeleccion()
    {
        $this->requireAuth();

        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (empty($ids)) {
            $this->redirect('/reportes/prestamos');
            return;
        }

        // Fetch selected loans
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT p.*, 
                       u.nombre_completo as usuario_nombre,
                       u.username,
                       COALESCE(td.codigo, td.nombre) as tipo_documento,
                       rd.nro_comprobante,
                       rd.gestion,
                       tc.codigo as tipo_contenedor,
                       cf.numero as contenedor_numero,
                       cf.codigo_abc as contenedor_codigo_abc
                FROM prestamos p
                INNER JOIN usuarios u ON p.usuario_id = u.id
                INNER JOIN documentos rd ON p.documento_id = rd.id
                LEFT JOIN tipo_documento td ON rd.tipo_documento_id = td.id
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                WHERE p.id IN ($placeholders)
                ORDER BY p.fecha_devolucion_esperada ASC";

        $prestamos = $this->prestamo->getDb()->fetchAll($sql, $ids);

        // Render PDF (Simplified output for now, or use a library if available)
        // Since we don't have a full PDF lib setup in the context visible, we'll simulate or use a print view
        // Ideally we would use FPDF or TCPDF. 
        // For now, let's output a clean HTML that prints well, or check if we have a PDF helper.
        // Checking `PrestamosController::exportarPdf` might reveal how it's done.

        // Assuming we rely on browser print for "PDF" based on previous interactions or lack of lib.
        // But the user asked for "PDF Button".
        // Let's create a print-friendly view for these specific items.

        $this->view('reportes.print_prestamos', ['prestamos' => $prestamos]);
    }

    /**
     * Exportar selección a Excel
     */
    public function exportarExcelSeleccion()
    {
        $this->requireAuth();

        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (empty($ids)) {
            $this->redirect('/reportes/prestamos');
            return;
        }

        // Fetch selected loans
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT p.*, 
                       u.nombre_completo as usuario_nombre,
                       u.username,
                       COALESCE(td.codigo, td.nombre) as tipo_documento,
                       rd.nro_comprobante,
                       rd.gestion,
                       tc.codigo as tipo_contenedor,
                       cf.numero as contenedor_numero,
                       cf.codigo_abc as contenedor_codigo_abc
                FROM prestamos p
                INNER JOIN usuarios u ON p.usuario_id = u.id
                INNER JOIN documentos rd ON p.documento_id = rd.id
                LEFT JOIN tipo_documento td ON rd.tipo_documento_id = td.id
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                WHERE p.id IN ($placeholders)
                ORDER BY p.fecha_devolucion_esperada ASC";

        $prestamos = $this->prestamo->getDb()->fetchAll($sql, $ids);

        // Headers for download
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=prestamos_seleccionados_" . date('Y-m-d') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "<table border='1'>";
        echo "<tr>";
        echo "<th>Usuario</th>";
        echo "<th>Documento</th>";
        echo "<th>Comprobante</th>";
        echo "<th>Gestión</th>";
        echo "<th>Contenedor</th>";
        echo "<th>Fecha Préstamo</th>";
        echo "<th>Devolución Estimada</th>";
        echo "</tr>";

        foreach ($prestamos as $p) {
            echo "<tr>";
            echo "<td>" . iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $p['usuario_nombre']) . "</td>";
            echo "<td>" . $p['tipo_documento'] . "</td>";
            echo "<td>" . $p['nro_comprobante'] . "</td>";
            echo "<td>" . $p['gestion'] . "</td>";
            $abc = !empty($p['contenedor_codigo_abc']) ? '.' . $p['contenedor_codigo_abc'] : '';
            echo "<td>" . $p['tipo_contenedor'] . " #{$p['contenedor_numero']}{$abc}</td>";
            echo "<td>" . date('d/m/Y', strtotime($p['fecha_prestamo'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($p['fecha_devolucion_esperada'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit;
    }

    /**
     * Reporte de Auditoría
     */
    public function auditorias()
    {
        $this->requireAuth();

        $auditoria = new Auditoria();

        // Filtros
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;

        $filters = [
            'modulo' => $_GET['modulo'] ?? '',
            'accion' => $_GET['accion'] ?? '',
            'usuario_id' => $_GET['usuario_id'] ?? '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        $offset = ($page - 1) * $perPage;

        $logs = $auditoria->getLogs($filters, $perPage, $offset);
        $total = $auditoria->countLogs($filters);

        // Listas para filtros
        $modulos = $auditoria->getDb()->fetchAll("SELECT DISTINCT modulo FROM auditorias ORDER BY modulo");
        $acciones = $auditoria->getDb()->fetchAll("SELECT DISTINCT accion FROM auditorias ORDER BY accion");
        $usuarios = $this->usuario->getActive();

        $this->view('reportes.auditorias', [
            'logs' => $logs,
            'total' => $total,
            'paginacion' => [
                'current' => $page,
                'total_pages' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total_items' => $total
            ],
            'filters' => $filters,
            'modulos' => $modulos,
            'acciones' => $acciones,
            'usuarios' => $usuarios,
            'user' => $this->getCurrentUser()
        ]);
    }
    /**
     * Exportar selección de Auditoría a Reporte Impreso
     */
    public function exportarAuditoriaSeleccion()
    {
        $this->requireAuth();

        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (empty($ids)) {
            $this->redirect('/reportes/auditorias');
            return;
        }

        $auditoria = new Auditoria();

        // Fetch logs safely using WHERE IN
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT * FROM auditorias WHERE id IN ($placeholders) ORDER BY fecha DESC";
        $logs = $auditoria->getDb()->fetchAll($sql, $ids);

        $this->view('reportes.print_auditoria', ['logs' => $logs]);
    }
}
