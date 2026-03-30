<?php
/**
 * Modelo UnidadArea
 * 
 * @package TAMEP\Models
 */

namespace TAMEP\Models;

class UnidadArea extends BaseModel
{
    protected $table = 'unidades_areas';
    /**
     * Obtener unidades activas
     */
    public function getActive()
    {
        return $this->where('activo = 1');
    }
}
