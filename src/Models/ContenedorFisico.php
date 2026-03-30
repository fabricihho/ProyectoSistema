<?php
/**
 * Modelo ContenedorFisico
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class ContenedorFisico extends BaseModel
{
    protected $table = 'contenedores_fisicos';
    
    /**
     * Buscar libros
     */
    public function getLibros($limit = null)
    {
        return $this->where("tipo_contenedor_id = (SELECT id FROM tipos_contenedor WHERE codigo = 'LIBRO')", [],  $limit);
    }
    
    /**
     * Buscar amarros
     */
    public function getAmarros($limit = null)
    {
        return $this->where("tipo_contenedor_id = (SELECT id FROM tipos_contenedor WHERE codigo = 'AMARRO')", [], $limit);
    }

    /**
     * Buscar contenedores con filtros
     */
    public function buscar($filtros = [])
    {
        $sql = "SELECT c.*, u.nombre as ubicacion_nombre, t.nombre as tipo_documento_nombre, t.codigo as tipo_documento_codigo, t.codigo as tipo_documento,
                       tc.codigo as tipo_contenedor
                FROM {$this->table} c 
                LEFT JOIN ubicaciones u ON c.ubicacion_id = u.id 
                LEFT JOIN tipo_documento t ON c.tipo_documento_id = t.id
                LEFT JOIN tipos_contenedor tc ON c.tipo_contenedor_id = tc.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['tipo_documento'])) {
            // Check if input is numeric (ID) or string (Code/Name)
            if (is_numeric($filtros['tipo_documento'])) {
                 $sql .= " AND c.tipo_documento_id = ?";
                 $params[] = $filtros['tipo_documento'];
            } else {
                 $sql .= " AND (t.nombre LIKE ? OR t.codigo LIKE ?)";
                 $params[] = '%' . $filtros['tipo_documento'] . '%';
                 $params[] = '%' . $filtros['tipo_documento'] . '%';
            }
        }
        
        if (!empty($filtros['numero'])) {
            $num = $filtros['numero'];
            if (preg_match('/^(\d+)-(\d+)$/', $num, $matches)) {
                $min = min((int)$matches[1], (int)$matches[2]);
                $max = max((int)$matches[1], (int)$matches[2]);
                $sql .= " AND CAST(c.numero AS UNSIGNED) BETWEEN ? AND ?";
                $params[] = $min;
                $params[] = $max;
            } else {
                $sql .= " AND c.numero = ?";
                $params[] = $num;
            }
        }
        
        if (!empty($filtros['gestion'])) {
            $sql .= " AND c.gestion = ?";
            $params[] = $filtros['gestion'];
        }
        
        if (!empty($filtros['tipo_contenedor'])) {
            $sql .= " AND tc.codigo = ?";
            $params[] = $filtros['tipo_contenedor'];
        }
        
        if (!empty($filtros['ubicacion_id'])) {
            $sql .= " AND c.ubicacion_id = ?";
            $params[] = $filtros['ubicacion_id'];
        }

        // Sorting
        $sort = $filtros['sort'] ?? '';
        $order = strtoupper($filtros['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';
        
        $orderBy = '';
        switch ($sort) {
            case 'tipo_c':
                $orderBy = "tc.codigo $order";
                break;
            case 'numero':
                // Natural sort logic for numbers depending if column is int or string
                // Assuming string based on previous experience, but usually these are numeric. 
                // Let's use CAST just in case to be safe if it's mixed or string.
                if ($order === 'ASC') {
                   $orderBy = "CAST(c.numero AS UNSIGNED) ASC, c.numero ASC";
                } else {
                   $orderBy = "CAST(c.numero AS UNSIGNED) DESC, c.numero DESC";
                }
                break;
            case 'gestion':
                $orderBy = "c.gestion $order";
                break;
            case 'tipo_d':
                 $orderBy = "t.codigo $order";
                 break;
            case 'ubicacion':
                $orderBy = "u.nombre $order";
                break;
            default:
                $orderBy = "c.id DESC";
                break;
        }
        
        $sql .= " ORDER BY $orderBy";
        
        // Pagination
        if (!empty($filtros['per_page']) && !empty($filtros['page'])) {
            $perPage = (int)$filtros['per_page'];
            $page = (int)$filtros['page'];
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT $perPage OFFSET $offset";
        }
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Contar resultados de búsqueda
     */
    public function contarBusqueda($filtros = [])
    {
        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} c 
                LEFT JOIN ubicaciones u ON c.ubicacion_id = u.id 
                LEFT JOIN tipo_documento t ON c.tipo_documento_id = t.id
                LEFT JOIN tipos_contenedor tc ON c.tipo_contenedor_id = tc.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['tipo_documento'])) {
            if (is_numeric($filtros['tipo_documento'])) {
                 $sql .= " AND c.tipo_documento_id = ?";
                 $params[] = $filtros['tipo_documento'];
            } else {
                 $sql .= " AND (t.nombre LIKE ? OR t.codigo LIKE ?)";
                 $params[] = '%' . $filtros['tipo_documento'] . '%';
                 $params[] = '%' . $filtros['tipo_documento'] . '%';
            }
        }
        
        if (!empty($filtros['numero'])) {
            $num = $filtros['numero'];
            if (preg_match('/^(\d+)-(\d+)$/', $num, $matches)) {
                $min = min((int)$matches[1], (int)$matches[2]);
                $max = max((int)$matches[1], (int)$matches[2]);
                $sql .= " AND CAST(c.numero AS UNSIGNED) BETWEEN ? AND ?";
                $params[] = $min;
                $params[] = $max;
            } else {
                $sql .= " AND c.numero = ?";
                $params[] = $num;
            }
        }
        
        if (!empty($filtros['gestion'])) {
            $sql .= " AND c.gestion = ?";
            $params[] = $filtros['gestion'];
        }
        
        if (!empty($filtros['tipo_contenedor'])) {
            $sql .= " AND tc.codigo = ?";
            $params[] = $filtros['tipo_contenedor'];
        }
        
        if (!empty($filtros['ubicacion_id'])) {
            $sql .= " AND c.ubicacion_id = ?";
            $params[] = $filtros['ubicacion_id'];
        }

        $result = $this->db->fetchOne($sql, $params);
        return $result['total'] ?? 0;
    }
    
    /**
     * Verificar si está disponible para préstamo
     */
    public function isDisponible($id)
    {
        $contenedor = $this->find($id);
        
        if (!$contenedor) {
            return false;
        }
        
        // Verificar si hay préstamos activos
        $sql = "SELECT COUNT(*) as total 
                FROM prestamos 
                WHERE contenedor_fisico_id = ? 
                AND estado = 'Prestado'";
        
        $result = $this->db->fetchOne($sql, [$id]);
        
        return $result['total'] == 0;
    }

    /**
     * Buscar contenedor por ID con relaciones
     */
    public function find($id)
    {
        $sql = "SELECT c.*, 
                       tc.codigo as tipo_contenedor,
                       t.codigo as tipo_documento_codigo, t.codigo as tipo_documento, t.nombre as tipo_documento_nombre,
                       u.nombre as ubicacion_nombre
                FROM {$this->table} c
                LEFT JOIN tipos_contenedor tc ON c.tipo_contenedor_id = tc.id
                LEFT JOIN tipo_documento t ON c.tipo_documento_id = t.id
                LEFT JOIN ubicaciones u ON c.ubicacion_id = u.id
                WHERE c.id = ?";
        
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Obtener documentos del contenedor
     */
    /**
     * Obtener documentos del contenedor
     */
    public function getDocumentos($id)
    {
        $sql = "SELECT d.id, t.codigo as tipo_documento, d.nro_comprobante, d.gestion, d.observaciones 
                FROM documentos d
                LEFT JOIN tipo_documento t ON d.tipo_documento_id = t.id
                WHERE d.contenedor_fisico_id = ? 
                ORDER BY d.gestion DESC, CAST(d.nro_comprobante AS UNSIGNED) ASC, d.nro_comprobante ASC";
        return $this->db->fetchAll($sql, [$id]);
    }

    /**
     * Obtener ID de tipo de contenedor por código
     */
    public function getTipoContenedorId($codigo)
    {
        $sql = "SELECT id FROM tipos_contenedor WHERE codigo = ?";
        $result = $this->db->fetchOne($sql, [$codigo]);
        return $result ? $result['id'] : null;
    }

    /**
     * Actualizar contenido (Remover documentos desmarcados)
     */
    /**
     * Actualizar contenido (Remover documentos desmarcados)
     */
    public function actualizarContenido($contenedorId, $idsMantener = [])
    {
        // Si no hay IDs para mantener, vaciar todo el contenedor
        if (empty($idsMantener)) {
            $sql = "UPDATE documentos SET contenedor_fisico_id = NULL WHERE contenedor_fisico_id = ?";
            return $this->db->query($sql, [$contenedorId]);
        }
        
        // Desvincular los que NO están en la lista de mantener
        // Crear placeholders para el array (e.g., ?, ?, ?)
        $placeholders = str_repeat('?,', count($idsMantener) - 1) . '?';
        
        $sql = "UPDATE documentos 
                SET contenedor_fisico_id = NULL 
                WHERE contenedor_fisico_id = ? 
                AND id NOT IN ($placeholders)";
        
        // Merge container ID with the list of IDs to keep
        $params = array_merge([$contenedorId], $idsMantener);
        
        return $this->db->query($sql, $params);
    }

    /**
     * Búsqueda rápida para Autocomplete
     */
    public function buscarRapida($term, $limit = 20)
    {
        $term = trim($term);
        
        // Split by spaces to allow searching "Year Number" or "Number Year"
        // e.g. "2025 10" -> matches rows with "2025" AND "10" in any of the allowed fields
        $terms = array_filter(explode(' ', $term), function($t) { return strlen($t) > 0; });
        
        $sql = "SELECT c.*, 
                       tc.codigo as tipo_contenedor,
                       t.codigo as tipo_documento_codigo,
                       u.nombre as ubicacion_nombre
                FROM {$this->table} c
                LEFT JOIN tipos_contenedor tc ON c.tipo_contenedor_id = tc.id
                LEFT JOIN tipo_documento t ON c.tipo_documento_id = t.id
                LEFT JOIN ubicaciones u ON c.ubicacion_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (empty($terms)) {
            // Fallback for empty search if called
            return []; 
        }

        foreach ($terms as $keyword) {
            $kwLike = "%{$keyword}%";
            
            // Build OR conditions for this term
            $orConditions = [];
            $orParams = [];

            // 1. Número: Always search (maybe refined to StartsWith?) - Keeping Contains for flexibility
            $orConditions[] = "c.numero LIKE ?";
            $orParams[] = $kwLike;

            // 2. Gestión: Only if not a short number (avoid "1" matching "2013")
            // Allow if exact match (unlikely for "1" vs "2013") OR length >= 3
            if (!is_numeric($keyword) || strlen($keyword) >= 3 || $keyword == $keyword /* Exact match logic handled separately usually but here we are in LIKE block */) {
                 // Actually, simpler: Don't fuzzy match year with 1 or 2 digits
                 if (!is_numeric($keyword) || strlen($keyword) >= 3) {
                     $orConditions[] = "c.gestion LIKE ?";
                     $orParams[] = $kwLike;
                 } else {
                     // For short numbers, ONLY match if EXACTLY equal to gestion (unlikely but safe)
                     $orConditions[] = "c.gestion = ?";
                     $orParams[] = $keyword;
                 }
            }

            // 3. Codes/Names: Always search
            $orConditions[] = "c.codigo_abc LIKE ?";
            $orParams[] = $kwLike;
            
            $orConditions[] = "tc.codigo LIKE ?";
            $orParams[] = $kwLike;
            
            $orConditions[] = "tc.nombre LIKE ?";
            $orParams[] = $kwLike;
            
            $orConditions[] = "t.codigo LIKE ?";
            $orParams[] = $kwLike;
            
            $orConditions[] = "t.nombre LIKE ?";
            $orParams[] = $kwLike;

            $sql .= " AND (" . implode(' OR ', $orConditions) . ")";
            $params = array_merge($params, $orParams);
        }
        
        // Keep order logic (Best Effort with full term for exact matches, else standard sort)
        $sql .= " ORDER BY 
                    (c.numero = ?) DESC, -- Prioridad 1: Coincidencia exacta de número
                    c.gestion DESC,      -- Prioridad 2: Años más recientes
                    CAST(c.numero AS UNSIGNED) ASC, -- Prioridad 3: Orden numérico natural (1, 2, 10...)
                    c.id DESC
                LIMIT ?";
        
        // Add sorting/limit params
        $params[] = $term; 
        $params[] = $limit;
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Verificar si existe un contenedor por número, gestión y tipo documento
     */
    public function existe($data)
    {
        $sql = "SELECT id FROM {$this->table} 
                WHERE numero = ? AND gestion = ? AND tipo_documento_id = ? 
                LIMIT 1";
        $result = $this->db->fetchOne($sql, [
            $data['numero'],
            $data['gestion'],
            $data['tipo_documento_id']
        ]);
        return $result ? $result['id'] : null;
    }
}
