<?php
/**
 * Modelo Hoja de Ruta
 * Gestiona la tabla de registro_hojas_ruta
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class HojaRuta extends BaseModel
{
    protected $table = 'registro_hojas_ruta';
    protected $fillable = [
        'estado_perdido',
        'gestion',
        'nro_comprobante_diario',
        'conam',
        'nro_hoja_ruta',
        'gestion_hr',
        'rubro',
        'interesado',
        'contenedor_fisico_id',
        'lugar_archivo',
        'observaciones',
        'activo'
    ];
    
    /**
     * Búsqueda avanzada de hojas de ruta
     */
    public function buscarAvanzado($filters = [])
    {
        $where = [];
        $params = [];
        
        // Búsqueda general
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $where[] = "(hr.nro_comprobante_diario = ? OR hr.nro_hoja_ruta = ? OR hr.rubro LIKE ? OR hr.interesado LIKE ? OR hr.conam LIKE ?)";
            $params[] = $search; // Exact
            $params[] = $search; // Exact
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if (!empty($filters['gestion'])) {
            $where[] = "hr.gestion = ?";
            $params[] = $filters['gestion'];
        }
        
        if (!empty($filters['ubicacion_id'])) {
            $where[] = "cf.ubicacion_id = ?";
            $params[] = $filters['ubicacion_id'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) . ' AND hr.activo = 1' : 'WHERE hr.activo = 1';
        
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT hr.*, 
                       hr.nro_hoja_ruta as nro_comprobante,
                       cf.tipo_contenedor, 
                       cf.numero as contenedor_numero,
                       u.nombre as ubicacion_nombre,
                       'HOJA_RUTA_DIARIOS' as tipo_documento,
                       'DISPONIBLE' as estado_documento
                FROM {$this->table} hr
                LEFT JOIN contenedores_fisicos cf ON hr.contenedor_fisico_id = cf.id
                LEFT JOIN ubicaciones u ON cf.ubicacion_id = u.id
                {$whereClause}
                ORDER BY hr.id DESC
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
            $where[] = "(hr.nro_comprobante_diario = ? OR hr.nro_hoja_ruta = ? OR hr.rubro LIKE ? OR hr.interesado LIKE ? OR hr.conam LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if (!empty($filters['gestion'])) {
            $where[] = "hr.gestion = ?";
            $params[] = $filters['gestion'];
        }
        
        if (!empty($filters['ubicacion_id'])) {
            $where[] = "cf.ubicacion_id = ?";
            $params[] = $filters['ubicacion_id'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) . ' AND hr.activo = 1' : 'WHERE hr.activo = 1';
        
        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} hr
                LEFT JOIN contenedores_fisicos cf ON hr.contenedor_fisico_id = cf.id
                {$whereClause}";
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'] ?? 0;
    }
}
