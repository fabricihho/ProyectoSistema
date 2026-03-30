<?php
/**
 * Modelo PrestamoHeader
 * Gestiona la cabecera de grupos de préstamos
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class PrestamoHeader extends BaseModel
{
    protected $table = 'prestamos_encabezados';
    protected $fillable = [
        'usuario_id',
        'unidad_area_id',
        'nombre_prestatario',
        'fecha_prestamo',
        'fecha_devolucion_esperada',
        'observaciones',
        'estado'
    ];

    /**
     * Obtener los detalles (documentos) de este préstamo
     */
    public function getDetalles($id)
    {
        $sql = "SELECT p.*, 
                       rd.nro_comprobante, rd.gestion, t.codigo as tipo_documento, rd.codigo_abc, 
                       e.nombre as estado_documento,
                       ea.nombre as estado_anterior, -- Nombre del estado original
                       tc.codigo as tipo_contenedor, cf.numero as contenedor_numero, cf.codigo_abc as contenedor_codigo_abc,
                       ub.nombre as ubicacion_fisica,
                       p.es_principal
                FROM prestamos p
                LEFT JOIN documentos rd ON p.documento_id = rd.id
                LEFT JOIN estados e ON rd.estado_documento_id = e.id
                LEFT JOIN estados ea ON p.estado_anterior_id = ea.id -- Join para estado anterior
                LEFT JOIN tipo_documento t ON rd.tipo_documento_id = t.id
                LEFT JOIN contenedores_fisicos cf ON p.contenedor_fisico_id = cf.id
                LEFT JOIN tipos_contenedor tc ON cf.tipo_contenedor_id = tc.id
                LEFT JOIN ubicaciones ub ON cf.ubicacion_id = ub.id
                WHERE p.encabezado_id = ?";

        return $this->db->fetchAll($sql, [$id]);
    }

    /**
     * Contar documentos en este préstamo
     */
    public function countDetalles($id)
    {
        $sql = "SELECT COUNT(*) as total FROM prestamos WHERE encabezado_id = ?";
        $result = $this->db->fetchOne($sql, [$id]);
        return $result['total'] ?? 0;
    }
}
