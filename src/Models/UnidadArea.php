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
    protected $fillable = ['nombre', 'activo'];

    public function getActive()
    {
        return $this->db->fetchAll("SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY nombre ASC");
    }

    public function all($limit = null, $offset = 0)
    {
        return $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY nombre ASC");
    }
}
