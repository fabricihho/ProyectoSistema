<?php
/**
 * Audit Logger
 * Registra acciones del sistema en la base de datos
 */

namespace TAMEP\Core;

use TAMEP\Models\BaseModel; // Para acceder a la DB

class AuditLogger
{
    /**
     * Registra un evento de auditoría
     * 
     * @param string $accion (CREAR, EDITAR, ELIMINAR, LOGIN, etc.)
     * @param string $modulo (Documentos, Prestamos, Contenedores, etc.)
     * @param int|null $registro_id ID del objeto afectado
     * @param string|array|null $detalles Descripción o array de datos
     */
    public static function log($accion, $modulo, $registro_id = null, $detalles = null)
    {
        try {
            // Obtener usuario actual
            $user = Session::user();
            $usuario_id = $user['id'] ?? 1; // Fallback a 1 (admin/system) si no hay sesión o es CLI

            if (is_array($detalles)) {
                $detalles = json_encode($detalles, JSON_UNESCAPED_UNICODE);
            }

            // Usamos la instancia singleton
            $db = Database::getInstance();

            $sql = "INSERT INTO auditorias (usuario_id, fecha, accion, modulo, registro_id, detalles) 
                    VALUES (?, NOW(), ?, ?, ?, ?)";

            $db->query($sql, [
                $usuario_id,
                $accion,
                $modulo,
                $registro_id,
                $detalles
            ]);

        } catch (\Throwable $e) {
            // Fallo silencioso para no interrumpir el flujo principal, pero logueado a archivo
            error_log("AUDIT ERROR: " . $e->getMessage());
        }
    }
}
