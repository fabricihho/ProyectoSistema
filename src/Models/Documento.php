<?php
/**
 * Modelo RegistroDiario
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class Documento extends BaseModel
{
    protected $table = 'documentos';

    /**
     * Buscar con información del contenedor
     */
    public function findWithContenedor($id)
    {
        $sql = "SELECT rd.*, 
                       tc.codigo AS tipo_contenedor, cf.numero AS contenedor_numero, cf.codigo_abc AS contenedor_codigo_abc, 
                       cf.color, cf.bloque_nivel,
                       u.nombre AS ubicacion_nombre, u.descripcion AS ubicacion_descripcion,
                       e.nombre AS estado_documento,
                       t.codigo AS tipo_documento,
                       p.id as prestamo_detalle_id,
                       p.encabezado_id as prestamo_id,
                       ph.nombre_prestatario,
                       ph.fecha_prestamo,
                       ph.fecha_devolucion_esperada,
                       ua.nombre as unidad_prestamo
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
                LEFT JOIN estados e ON rd.estado_documento_id = e.id
                LEFT JOIN tipo_documento t ON rd.tipo_documento_id = t.id
                LEFT JOIN prestamos p ON rd.id = p.documento_id AND p.estado IN ('Prestado', 'En Proceso')
                LEFT JOIN prestamos_encabezados ph ON p.encabezado_id = ph.id
                LEFT JOIN unidades_areas ua ON ph.unidad_area_id = ua.id
                WHERE rd.id = ?";

        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Búsqueda avanzada
     */
    public function search($filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($filters['gestion'])) {
            $where[] = "rd.gestion = ?";
            $params[] = $filters['gestion'];
        }

        if (!empty($filters['nro_comprobante'])) {
            $where[] = "rd.nro_comprobante LIKE ?";
            $params[] = "%{$filters['nro_comprobante']}%";
        }

        if (!empty($filters['contenedor'])) {
            $where[] = "cf.numero LIKE ?";
            $params[] = "%{$filters['contenedor']}%";
        }

        if (!empty($filters['ubicacion_id'])) {
            $where[] = "cf.ubicacion_id = ?";
            $params[] = $filters['ubicacion_id'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT rd.*, 
                       tc.codigo AS tipo_contenedor, cf.numero AS contenedor_numero, cf.codigo_abc AS contenedor_codigo_abc,
                       cf.color, cf.bloque_nivel,
                       u.nombre AS ubicacion_nombre,
                       e.nombre AS estado_documento,
                       t.codigo AS tipo_documento
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
                LEFT JOIN estados e ON rd.estado_documento_id = e.id
                LEFT JOIN tipo_documento t ON rd.tipo_documento_id = t.id
                {$whereClause}
                ORDER BY rd.gestion DESC, rd.nro_comprobante ASC";

        return $this->db->fetchAll($sql, $params);
    }
    /**
     * Búsqueda avanzada con paginación
     */
    public function buscarAvanzado($filters = [])
    {
        $where = [];
        $params = [];

        // Búsqueda general en múltiples campos
        // Búsqueda general en múltiples campos
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            // Verificar si es una búsqueda de rango (ej. "1-50")
            if (preg_match('/^(\d+)-(\d+)$/', $search, $matches)) {
                $min = $matches[1];
                $max = $matches[2];

                // Asegurar orden correcto
                if ($min > $max) {
                    $temp = $min;
                    $min = $max;
                    $max = $temp;
                }

                $where[] = "CAST(rd.nro_comprobante AS UNSIGNED) BETWEEN ? AND ?";
                $params[] = $min;
                $params[] = $max;
            } else {
                // Búsqueda normal
                $where[] = "(
                    rd.nro_comprobante = ? 
                    OR rd.codigo_abc = ? 
                    OR rd.observaciones LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.rubro')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.interesado')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.conam')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.nro_comprobante_diario')) = ?
                )";
                $params[] = $search; // Exact match nro_comprobante
                $params[] = $search; // Exact match codigo_abc
                $params[] = "%{$search}%"; // Partial match observaciones
                $params[] = "%{$search}%"; // Partial match rubro
                $params[] = "%{$search}%"; // Partial match interesado
                $params[] = "%{$search}%"; // Partial match conam
                $params[] = $search;       // Exact match nro_comprobante_diario
            }
        }

        if (!empty($filters['gestion'])) {
            $where[] = "rd.gestion = ?";
            $params[] = $filters['gestion'];
        }

        if (!empty($filters['ubicacion_id'])) {
            $where[] = "cf.ubicacion_id = ?";
            $params[] = $filters['ubicacion_id'];
        }

        if (!empty($filters['estado_documento'])) {
            if ($filters['estado_documento'] === 'FALTA') {
                // Caso especial: Irregularidades (Falta y Prestado)
                $where[] = "(e.nombre = 'FALTA' OR (e.nombre = 'PRESTADO' AND rd.observaciones LIKE '%FALTA%'))";
            } else {
                $where[] = "e.nombre = ?";
                $params[] = $filters['estado_documento'];
            }
        }

        if (!empty($filters['tipo_documento'])) {
            // If numeric, assume ID
            if (is_numeric($filters['tipo_documento'])) {
                $where[] = "rd.tipo_documento_id = ?";
                $params[] = $filters['tipo_documento'];
            } else {
                // If string, assume exact code match
                $where[] = "(t.nombre LIKE ? OR t.codigo LIKE ?)";
                $params[] = '%' . $filters['tipo_documento'] . '%';
                $params[] = '%' . $filters['tipo_documento'] . '%';
            }
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT rd.*, 
                       tc.codigo AS tipo_contenedor, cf.numero as contenedor_numero, cf.codigo_abc AS contenedor_codigo_abc,
                       u.nombre as ubicacion_nombre,
                       t.nombre as tipo_documento_nombre, t.codigo as tipo_documento,
                       e.nombre as estado_documento,
                       p.id as prestamo_detalle_id,
                       p.encabezado_id as prestamo_id,
                       ph.nombre_prestatario,
                       ph.fecha_prestamo,
                       ph.fecha_devolucion_esperada,
                       ua.nombre as unidad_prestamo
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
                LEFT JOIN tipo_documento t ON rd.tipo_documento_id = t.id
                LEFT JOIN estados e ON rd.estado_documento_id = e.id
                LEFT JOIN prestamos p ON rd.id = p.documento_id AND p.estado IN ('Prestado', 'En Proceso')
                LEFT JOIN prestamos_encabezados ph ON p.encabezado_id = ph.id
                LEFT JOIN unidades_areas ua ON ph.unidad_area_id = ua.id
                {$whereClause}";

        // Sorting Logic
        $sort = $filters['sort'] ?? '';
        $order = strtoupper($filters['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        $orderBy = '';
        switch ($sort) {
            case 'gestion':
                $orderBy = "rd.gestion $order";
                break;
            case 'tipo':
                $orderBy = "t.codigo $order";
                break;
            case 'nro_comprobante':
                // Natural sort attempt: length then value, or CAST
                if ($order === 'ASC') {
                    $orderBy = "CAST(rd.nro_comprobante AS UNSIGNED) ASC, rd.nro_comprobante ASC";
                } else {
                    $orderBy = "CAST(rd.nro_comprobante AS UNSIGNED) DESC, rd.nro_comprobante DESC";
                }
                break;
            case 'codigo_abc':
                $orderBy = "rd.codigo_abc $order";
                break;
            case 'contenedor':
                $orderBy = "tc.codigo $order, cf.numero $order";
                break;
            case 'ubicacion':
                $orderBy = "u.nombre $order";
                break;
            case 'estado':
                $orderBy = "e.nombre $order";
                break;
            default:
                // Default sort
                $orderBy = "rd.gestion DESC, rd.nro_comprobante ASC";
                break;
        }

        $sql .= " ORDER BY $orderBy
                  LIMIT {$perPage} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }
    /**
     * Contar resultados de búsqueda
     */
    public function contarBusqueda($filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            // Verificar si es una búsqueda de rango (ej. "1-50")
            if (preg_match('/^(\d+)-(\d+)$/', $search, $matches)) {
                $min = $matches[1];
                $max = $matches[2];

                if ($min > $max) {
                    $temp = $min;
                    $min = $max;
                    $max = $temp;
                }

                $where[] = "CAST(rd.nro_comprobante AS UNSIGNED) BETWEEN ? AND ?";
                $params[] = $min;
                $params[] = $max;
            } else {
                $where[] = "(
                    rd.nro_comprobante = ? 
                    OR rd.codigo_abc = ? 
                    OR rd.observaciones LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.rubro')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.interesado')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.conam')) LIKE ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(rd.atributos_extra, '$.nro_comprobante_diario')) = ?
                )";
                $params[] = $search;
                $params[] = $search;
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = $search;
            }
        }

        if (!empty($filters['gestion'])) {
            $where[] = "rd.gestion = ?";
            $params[] = $filters['gestion'];
        }

        if (!empty($filters['ubicacion_id'])) {
            $where[] = "cf.ubicacion_id = ?";
            $params[] = $filters['ubicacion_id'];
        }

        if (!empty($filters['estado_documento'])) {
            if ($filters['estado_documento'] === 'FALTA') {
                // Caso especial: Irregularidades (Falta y Prestado)
                $where[] = "(e.nombre = 'FALTA' OR (e.nombre = 'PRESTADO' AND rd.observaciones LIKE '%FALTA%'))";
            } else {
                $where[] = "e.nombre = ?";
                $params[] = $filters['estado_documento'];
            }
        }

        if (!empty($filters['tipo_documento'])) {
            $where[] = "(t.nombre LIKE ? OR t.codigo LIKE ?)";
            $params[] = '%' . $filters['tipo_documento'] . '%';
            $params[] = '%' . $filters['tipo_documento'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} rd 
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                LEFT JOIN tipo_documento t ON rd.tipo_documento_id = t.id
                LEFT JOIN estados e ON rd.estado_documento_id = e.id
                {$whereClause}";
        $result = $this->db->fetchOne($sql, $params);

        return $result['total'] ?? 0;
    }

    /**
     * Obtener documentos disponibles para préstamo
     */
    public function getAvailable()
    {
        $sql = "SELECT rd.*, 
                       tc.codigo AS tipo_contenedor, cf.numero as contenedor_numero, cf.codigo_abc AS contenedor_codigo_abc,
                       e.nombre AS estado_documento
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                LEFT JOIN estados e ON rd.estado_documento_id = e.id
                WHERE e.nombre = 'DISPONIBLE'
                ORDER BY rd.gestion DESC, rd.nro_comprobante DESC
                LIMIT 100";

        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener ID de estado por nombre
     */
    public function getEstadoId($nombre)
    {
        $sql = "SELECT id FROM estados WHERE nombre = ?";
        $result = $this->db->fetchOne($sql, [$nombre]);
        return $result ? $result['id'] : null;
    }
}
