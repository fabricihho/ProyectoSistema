<?php
namespace TAMEP\Models;

class Auditoria extends BaseModel
{
    protected $table = 'auditorias';
    protected $fillable = [
        'usuario_id',
        'fecha',
        'accion',
        'modulo',
        'registro_id',
        'detalles'
    ];

    public function getLogs($filters = [], $limit = 20, $offset = 0)
    {
        $where = [];
        $params = [];

        if (!empty($filters['usuario_id'])) {
            $where[] = "a.usuario_id = ?";
            $params[] = $filters['usuario_id'];
        }

        if (!empty($filters['modulo'])) {
            $where[] = "a.modulo = ?";
            $params[] = $filters['modulo'];
        }

        if (!empty($filters['accion'])) {
            $where[] = "a.accion = ?";
            $params[] = $filters['accion'];
        }

        if (!empty($filters['fecha_desde'])) {
            $where[] = "DATE(a.fecha) >= ?";
            $params[] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $where[] = "DATE(a.fecha) <= ?";
            $params[] = $filters['fecha_hasta'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(a.detalles LIKE ? OR u.nombre_completo LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT a.*, u.nombre_completo as usuario_nombre, u.username
                FROM {$this->table} a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                {$whereClause}
                ORDER BY a.fecha DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    public function countLogs($filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($filters['usuario_id'])) {
            $where[] = "a.usuario_id = ?";
            $params[] = $filters['usuario_id'];
        }

        if (!empty($filters['modulo'])) {
            $where[] = "a.modulo = ?";
            $params[] = $filters['modulo'];
        }

        if (!empty($filters['accion'])) {
            $where[] = "a.accion = ?";
            $params[] = $filters['accion'];
        }

        if (!empty($filters['fecha_desde'])) {
            $where[] = "DATE(a.fecha) >= ?";
            $params[] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $where[] = "DATE(a.fecha) <= ?";
            $params[] = $filters['fecha_hasta'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(a.detalles LIKE ? OR u.nombre_completo LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                {$whereClause}";

        $res = $this->db->fetchOne($sql, $params);
        return $res['total'] ?? 0;
    }

    public function getModulos()
    {
        $sql = "SELECT DISTINCT modulo FROM {$this->table} ORDER BY modulo";
        return $this->db->fetchAll($sql);
    }

    public function getAcciones()
    {
        $sql = "SELECT DISTINCT accion FROM {$this->table} ORDER BY accion";
        return $this->db->fetchAll($sql);
    }
}
