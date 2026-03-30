<?php

namespace TAMEP\Models;

class TipoDocumento extends BaseModel
{
    protected $table = 'tipo_documento';
    
    protected $fillable = [
        'codigo',
        'nombre', 
        'descripcion',
        'activo',
        'orden',
        'esquema_atributos'
    ];
    
    /**
     * Get active document types sorted by order
     */
    public function getActive()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY orden ASC"
        );
    }

    /**
     * Get all document types
     */
    /**
     * Get all document types sorted by name
     */
    public function getAll()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY nombre ASC"
        );
    }

    /**
     * Get all document types sorted by ID
     */
    public function getAllOrderedById()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY id ASC"
        );
    }
    
    /**
     * Get schema fields for a specific type
     */
    public function getEsquema($codigo)
    {
        $type = $this->db->fetchOne(
            "SELECT esquema_atributos FROM {$this->table} WHERE codigo = ?",
            [$codigo]
        );
        
        if ($type && !empty($type['esquema_atributos'])) {
            return json_decode($type['esquema_atributos'], true);
        }
        
        return null;
    }

    /**
     * Find type by code
     */
    public function findByCode($codigo)
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE codigo = ?",
            [$codigo]
        );
    }
}
