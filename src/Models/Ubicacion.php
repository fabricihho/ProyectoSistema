<?php
/**
 * Modelo Ubicacion
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class Ubicacion extends BaseModel
{
    protected $table = 'ubicaciones';
    protected $fillable = ['nombre', 'descripcion', 'activo'];
    
    /**
     * Obtener ubicaciones activas
     */
    public function getActive()
    {
        return $this->where('activo = 1', [], null, 'nombre ASC');
    }
}
