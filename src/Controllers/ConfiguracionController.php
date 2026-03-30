<?php

namespace TAMEP\Controllers;

use TAMEP\Models\Usuario;
use TAMEP\Models\TipoDocumento;
use TAMEP\Core\AuditLogger;
use TAMEP\Core\Session;

class ConfiguracionController extends BaseController
{
    private $usuario;
    private $tipoDocumento;

    public function __construct()
    {
        parent::__construct();
        $this->usuario = new Usuario();
        $this->tipoDocumento = new TipoDocumento();
    }

    /**
     * Show password change form
     */
    public function password()
    {
        $this->requireAuth();

        $this->view('configuracion.password', [
            'user' => Session::user()
        ]);
    }

    /**
     * Update password
     */
    public function updatePassword()
    {
        $this->requireAuth();

        $currentUser = Session::user();
        $userId = $currentUser['id'];

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            Session::flash('error', 'Todos los campos son obligatorios');
            $this->redirect('/configuracion/password');
        }

        if ($newPassword !== $confirmPassword) {
            Session::flash('error', 'La nueva contraseña y su confirmación no coinciden');
            $this->redirect('/configuracion/password');
        }

        if (strlen($newPassword) < 6) {
            Session::flash('error', 'La nueva contraseña debe tener al menos 6 caracteres');
            $this->redirect('/configuracion/password');
        }

        // Verify current password
        // Since we don't have a direct method in Usuario model to just check password, 
        // we'll fetch the user and check manually.
        $userRecord = $this->usuario->find($userId);

        if (!$userRecord || !password_verify($currentPassword, $userRecord['password_hash'])) {
            Session::flash('error', 'La contraseña actual es incorrecta');
            $this->redirect('/configuracion/password');
        }

        // Update password
        $data = [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ];

        if ($this->usuario->update($userId, $data)) {
            AuditLogger::log('SEGURIDAD', 'Usuarios', $userId, "Cambio de contraseña exitoso");
            Session::flash('success', 'Contraseña actualizada correctamente');
            $this->redirect('/configuracion/password');
        } else {
            Session::flash('error', 'Error al actualizar la contraseña');
            $this->redirect('/configuracion/password');
        }
    }

    /**
     * Listar tipos de documento
     */
    public function tipos()
    {
        $this->requireAuth();

        $tipos = $this->tipoDocumento->all();

        // Sort by order manually if needed, or rely on model
        usort($tipos, function ($a, $b) {
            return $a['orden'] <=> $b['orden'];
        });

        $this->view('configuracion.tipos.index', [
            'tipos' => $tipos,
            'user' => Session::user()
        ]);
    }

    /**
     * Mostrar formulario crear tipo
     */
    public function crearTipo()
    {
        $this->requireAuth();

        // Define default fields based on user request
        $defaultSchema = [
            'standard_fields' => [
                'estado_documento' => ['label' => 'Estado', 'visible' => true],
                'gestion' => ['label' => 'Gestión', 'visible' => true],
                'nro_comprobante' => ['label' => 'Nro. Comprobante', 'visible' => true],
                'codigo_abc' => ['label' => 'Código ABC', 'visible' => true],
                'contenedor_id' => ['label' => 'Nro. Libro/Amarro', 'visible' => true],
                // Derived fields (Info only)
                'info_bloque' => ['label' => 'Bloque/Nivel', 'visible' => true],
                'info_color' => ['label' => 'Libro Color', 'visible' => true],
                'info_ubicacion' => ['label' => 'Ubicación', 'visible' => true],
                'unidad_id' => ['label' => 'Unidad/Área Solicitante', 'visible' => true],
                'observaciones' => ['label' => 'Observaciones', 'visible' => true],
            ],
            'custom_fields' => [
                ['key' => 'fecha_prestamo', 'label' => 'Fecha de Préstamo', 'type' => 'date'],
                ['key' => 'fecha_devolucion', 'label' => 'Fecha de Devolución', 'type' => 'date']
            ]
        ];

        // Simulate an empty type with defaults
        $tipo = [
            'esquema' => $defaultSchema
        ];

        $this->view('configuracion.tipos.form', [
            'tipo' => $tipo,
            'isNew' => true,
            'standardKeys' => $defaultSchema['standard_fields'], // Keys are keys of standard_fields array
            'user' => Session::user()
        ]);
    }

    private $standardKeysDefinition = [
        'estado_documento' => 'Estado',
        'gestion' => 'Gestión',
        'nro_comprobante' => 'Nro. Comprobante',
        'codigo_abc' => 'Código ABC',
        'contenedor_id' => 'Nro. Libro/Amarro',
        'info_bloque' => 'Bloque/Nivel',
        'info_color' => 'Libro Color',
        'info_ubicacion' => 'Ubicación',
        'unidad_id' => 'Unidad/Área Solicitante',
        'observaciones' => 'Observaciones'
    ];

    /**
     * Helper to process unified schema from POST
     */
    private function processSchemafromPost()
    {
        // 1. Initialize Standard Fields as Invisible
        $standardFields = [];
        foreach ($this->standardKeysDefinition as $key => $label) {
            $standardFields[$key] = [
                'label' => $label,
                'visible' => false
            ];
        }

        $customFields = [];

        // 2. Process Submitted Fields
        if (!empty($_POST['field_keys'])) {
            $keys = $_POST['field_keys'];
            $labels = $_POST['field_labels'];
            $types = $_POST['field_types'];

            for ($i = 0; $i < count($keys); $i++) {
                $key = trim($keys[$i]);
                $label = trim($labels[$i]);
                $type = $types[$i];

                if (empty($key))
                    continue;

                if (array_key_exists($key, $this->standardKeysDefinition)) {
                    // It is a standard field
                    $standardFields[$key] = [
                        'label' => $label, // Allow user to rename label
                        'visible' => true
                    ];
                } else {
                    // It is a custom field
                    $customFields[] = [
                        'key' => $key,
                        'label' => $label,
                        'type' => $type
                    ];
                }
            }
        }

        return [
            'standard_fields' => $standardFields,
            'custom_fields' => $customFields
        ];
    }

    /**
     * Guardar tipo documento
     */
    public function guardarTipo()
    {
        $this->requireAuth();

        // Basic Validation
        if (empty($_POST['codigo']) || empty($_POST['nombre'])) {
            Session::flash('error', 'Código y Nombre son obligatorios');
            $this->redirect('/configuracion/tipos/crear');
        }

        // Schema Processing
        $esquema = $this->processSchemafromPost();

        $data = [
            'codigo' => strtoupper(trim($_POST['codigo'])), // Codigos en mayuscula
            'nombre' => trim($_POST['nombre']),
            'descripcion' => $_POST['descripcion'] ?? '',
            'orden' => $_POST['orden'] ?? 0,
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'esquema_atributos' => json_encode($esquema)
        ];

        try {
            if ($this->tipoDocumento->create($data)) {
                $id = $this->tipoDocumento->getDb()->lastInsertId();
                AuditLogger::log('CREAR', 'Configuracion', $id, "Se creó Tipo Documento: {$data['nombre']} ({$data['codigo']})");
                Session::flash('success', 'Tipo de documento creado exitosamente');
                $this->redirect('/configuracion/tipos');
            } else {
                Session::flash('error', 'Error al crear. Verifique que el código no exista.');
                $this->redirect('/configuracion/crearTipo');
            }
        } catch (\Exception $e) {
            Session::flash('error', 'Error: ' . $e->getMessage());
            $this->redirect('/configuracion/crearTipo');
        }
    }

    /**
     * Mostrar formulario editar tipo
     */
    public function editarTipo($id)
    {
        $this->requireAuth();

        $tipo = $this->tipoDocumento->find($id);

        if (!$tipo) {
            Session::flash('error', 'Tipo no encontrado');
            $this->redirect('/configuracion/tipos');
        }

        // Decode schema for view
        $esquema = json_decode($tipo['esquema_atributos'] ?? '{}', true);

        // Merge defaults logic similar to before, but just pass raw schema 
        // View will handle "Standard vs Custom" separation if needed, or we prepare a unified list here.
        // Let's prepare a unified "display_fields" list for the View to iterate easily.

        // Ensure standard fields structure existed
        $defaultStd = [];
        foreach ($this->standardKeysDefinition as $k => $l) {
            $defaultStd[$k] = ['label' => $l, 'visible' => true];
        }

        if (!isset($esquema['standard_fields'])) {
            $esquema['standard_fields'] = $defaultStd;
        } else {
            // Fill missing standard keys if any new ones were added
            foreach ($this->standardKeysDefinition as $k => $l) {
                if (!isset($esquema['standard_fields'][$k])) {
                    $esquema['standard_fields'][$k] = ['label' => $l, 'visible' => true];
                }
            }
        }

        $tipo['esquema'] = $esquema;

        $this->view('configuracion.tipos.form', [
            'tipo' => $tipo,
            'standardKeys' => $this->standardKeysDefinition,
            'user' => Session::user()
        ]);
    }

    /**
     * Actualizar tipo documento
     */
    public function actualizarTipo($id)
    {
        $this->requireAuth();

        $tipo = $this->tipoDocumento->find($id);
        if (!$tipo) {
            Session::flash('error', 'Tipo no encontrado');
            $this->redirect('/configuracion/tipos');
        }

        if (empty($_POST['codigo']) || empty($_POST['nombre'])) {
            Session::flash('error', 'Código y Nombre son obligatorios');
            $this->redirect('/configuracion/tipos/editar/' . $id);
        }

        // Schema Processing
        $esquema = $this->processSchemafromPost();

        $data = [
            'codigo' => strtoupper(trim($_POST['codigo'])),
            'nombre' => trim($_POST['nombre']),
            'descripcion' => $_POST['descripcion'] ?? '',
            'orden' => $_POST['orden'] ?? 0,
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'esquema_atributos' => json_encode($esquema)
        ];

        if ($this->tipoDocumento->update($id, $data)) {
            AuditLogger::log('EDITAR', 'Configuracion', $id, "Se actualizó Tipo Documento: {$data['nombre']} ({$data['codigo']})");
            Session::flash('success', 'Tipo de documento actualizado exitosamente');
            $this->redirect('/configuracion/tipos');
        } else {
            Session::flash('error', 'Error al actualizar');
            $this->redirect('/configuracion/tipos/editar/' . $id);
        }
    }

    /**
     * Eliminar tipo
     */
    public function eliminarTipo($id)
    {
        $this->requireAuth();

        // TODO: Check dependencies (foreign keys) first!
        // Assuming database will throw error if used

        if ($this->tipoDocumento->delete($id)) {
            AuditLogger::log('ELIMINAR', 'Configuracion', $id, "Se eliminó Tipo Documento ID $id");
            Session::flash('success', 'Tipo de documento eliminado');
        } else {
            Session::flash('error', 'Error al eliminar');
        }
        $this->redirect('/configuracion/tipos');
    }
}
