<?php
/**
 * Modelo Prestamo
 * Gestiona la tabla de préstamos
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class Prestamo extends BaseModel
{
    protected $table = 'prestamos';
    protected $fillable = [
        'documento_tipo',
        'encabezado_id',
        'documento_id', 
        'contenedor_fisico_id', 
        'fecha_devolucion_esperada',
        'fecha_devolucion_real',
        'observaciones',
        'estado',
        'estado_anterior_id'
    ];
    
    /**
     * Obtener préstamos activos (sin devolver)
     */
    public function getActivos()
    {
        $sql = "SELECT p.*, u.nombre_completo as usuario_nombre
                FROM {$this->table} p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.estado = 'Prestado'
                ORDER BY p.fecha_devolucion_esperada ASC";
                
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Obtener préstamos vencidos
     */
    public function getVencidos()
    {
        $sql = "SELECT p.*, u.nombre_completo as usuario_nombre
                FROM {$this->table} p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.estado = 'Prestado' 
                AND p.fecha_devolucion_esperada < CURDATE()
                ORDER BY p.fecha_devolucion_esperada ASC";
                
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Contar préstamos por estado
     */
    public function countByEstado($estado)
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE estado = ?";
        $result = $this->db->fetchOne($sql, [$estado]);
        return $result['total'] ?? 0;
    }
}
