<?php
/**
 * Modelo RegistroDiario
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class RegistroDiario extends BaseModel
{
    protected $table = 'registro_diario';
    
    /**
     * Buscar con información del contenedor
     */
    public function findWithContenedor($id)
    {
        $sql = "SELECT rd.*, 
                       cf.tipo_contenedor, cf.numero AS contenedor_numero, 
                       cf.color, cf.bloque_nivel,
                       u.nombre AS ubicacion_nombre, u.descripcion AS ubicacion_descripcion
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
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
                       cf.tipo_contenedor, cf.numero AS contenedor_numero, 
                       cf.color, cf.bloque_nivel,
                       u.nombre AS ubicacion_nombre
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
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
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $where[] = "(rd.nro_comprobante = ? OR rd.codigo_abc = ? OR rd.observaciones LIKE ?)";
            $params[] = $search; // Exact match
            $params[] = $search; // Exact match
            $params[] = "%{$search}%"; // Partial match
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
            $where[] = "rd.estado_documento = ?";
            $params[] = $filters['estado_documento'];
        }
        
        if (!empty($filters['tipo_documento'])) {
            $where[] = "rd.tipo_documento = ?";
            $params[] = $filters['tipo_documento'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT rd.*, 
                       cf.tipo_contenedor, cf.numero as contenedor_numero,
                       u.nombre as ubicacion_nombre
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
                {$whereClause}
                ORDER BY rd.id DESC
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
            $where[] = "(rd.nro_comprobante = ? OR rd.codigo_abc = ? OR rd.observaciones LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = "%{$search}%";
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
            $where[] = "rd.estado_documento = ?";
            $params[] = $filters['estado_documento'];
        }
        
        if (!empty($filters['tipo_documento'])) {
            $where[] = "rd.tipo_documento = ?";
            $params[] = $filters['tipo_documento'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} rd 
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
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
                       cf.tipo_contenedor, cf.numero as contenedor_numero
                FROM {$this->table} rd
                LEFT JOIN contenedores_fisicos cf ON rd.contenedor_fisico_id = cf.id
                WHERE rd.estado_documento = 'DISPONIBLE'
                ORDER BY rd.gestion DESC, rd.nro_comprobante DESC
                LIMIT 100";
                
        return $this->db->fetchAll($sql);
    }
}
