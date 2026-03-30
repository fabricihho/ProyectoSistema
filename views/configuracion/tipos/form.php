<?php 
ob_start();

// Determine if we are creating or editing based on presence of ID
// Controller passes 'isNew' => true/false as well, but checking ID is safer for $tipo usage
$isNewMode = isset($isNew) && $isNew;
$isEditing = !$isNewMode && isset($tipo) && !empty($tipo['id']);

$pageTitle = $isEditing ? 'Editar Tipo de Documento' : 'Nuevo Tipo de Documento'; 
$action = $isEditing ? '/configuracion/tipos/actualizar/' . $tipo['id'] : '/configuracion/tipos/guardar';

// Helper to check standard field visibility
$isFieldHidden = function($key) use ($tipo) {
    if (!$tipo || !isset($tipo['esquema']['standard_fields'][$key])) return false;
    return $tipo['esquema']['standard_fields'][$key]['visible'] === false;
};
?>

<div class="card">
    <div class="card-header">
        <h2><?= $pageTitle ?></h2>
    </div>
    <div class="card-body">
        <form action="<?= $action ?>" method="POST">
            
            <!-- Basic Info -->
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="codigo">Código Único (Ej: FACTURA, RECIBO)</label>
                    <input type="text" id="codigo" name="codigo" class="form-control" required 
                           value="<?= htmlspecialchars($tipo['codigo'] ?? '') ?>" 
                           <?= $isEditing ? 'readonly' : '' ?>>
                    <?php if($isEditing): ?>
                        <small class="text-muted">El código no se puede cambiar una vez creado.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 form-group">
                    <label for="nombre">Nombre Visible</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required
                           value="<?= htmlspecialchars($tipo['nombre'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="2"><?= htmlspecialchars($tipo['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="orden">Orden de visualización</label>
                    <input type="number" id="orden" name="orden" class="form-control" 
                           value="<?= $tipo['orden'] ?? 0 ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label>Estado</label>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="activo" name="activo" 
                               <?= (!isset($tipo['activo']) || $tipo['activo']) ? 'checked' : '' ?>>
                        <label for="activo">Activo</label>
                    </div>
                </div>
            </div>

            <hr>

            <!-- Adición de Campos (Unified) -->
            <div class="card" style="padding: 0; border: 1px solid #e2e8f0;">
                <div class="card-header" style="background: #f8f9fa; border-bottom: 1px solid #e2e8f0; padding: 10px 15px;">
                    <h3 style="font-size: 16px; margin: 0; color: #2d3748;">Adición de Campos</h3>
                    <small class="text-muted">Configure los campos que tendrá este tipo de documento (Estándar y Personalizados).</small>
                </div>
                <div class="card-body" style="padding: 15px;">
                    <div id="dynamic-fields-container">
                        <!-- Header -->
                        <div class="dynamic-row-header row mb-2" style="font-size: 0.9em; color: #718096; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                             <div class="col-md-4"><strong>Etiqueta (Label)</strong></div>
                             <div class="col-md-4"><strong>Nombre Interno (Key)</strong></div>
                             <div class="col-md-3"><strong>Tipo de Dato</strong></div>
                             <div class="col-md-1"></div>
                        </div>

                        <?php 
                        // 1. Prepare Unified List of Active Fields
                        $activeFields = [];
                        
                        // Add Visible Standard Fields
                        $stdFields = $tipo['esquema']['standard_fields'] ?? [];
                        foreach($stdFields as $key => $config) {
                            if ($config['visible']) {
                                $activeFields[] = [
                                    'key' => $key,
                                    'label' => $config['label'],
                                    'type' => 'text', // Standard fields usually behave as text/display
                                    'is_standard' => true
                                ];
                            }
                        }

                        // Add Custom Fields
                        $customFields = $tipo['esquema']['custom_fields'] ?? [];
                        foreach($customFields as $field) {
                            $activeFields[] = [
                                'key' => $field['key'],
                                'label' => $field['label'],
                                'type' => $field['type'] ?? 'text',
                                'is_standard' => false
                            ];
                        }
                        
                        // Render List
                        foreach($activeFields as $field): 
                        ?>
                        <div class="dynamic-row row mb-2">
                            <div class="col-md-4">
                                <input type="text" name="field_labels[]" class="form-control" placeholder="Ej: Monto Total" value="<?= htmlspecialchars($field['label']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="field_keys[]" class="form-control" value="<?= htmlspecialchars($field['key']) ?>" required <?= $field['is_standard'] ? 'readonly style="background-color: #e9ecef;"' : '' ?>>
                            </div>
                            <div class="col-md-3">
                                <select name="field_types[]" class="form-control">
                                    <option value="text" <?= $field['type']=='text'?'selected':'' ?>>Texto</option>
                                    <option value="number" <?= $field['type']=='number'?'selected':'' ?>>Número</option>
                                    <option value="date" <?= $field['type']=='date'?'selected':'' ?>>Fecha</option>
                                    <option value="textarea" <?= $field['type']=='textarea'?'selected':'' ?>>Texto Largo</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger btn-sm remove-row" title="Eliminar Campo">❌</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Add Button with Dropdown -->
                    <div style="margin-top: 15px; position: relative; display: inline-block;">
                        <button type="button" id="add-field-btn" class="btn btn-secondary" onclick="toggleAddMenu()">
                            <span class="icon">➕</span> Agregar Campo...
                        </button>
                        <div id="add-field-menu" style="display: none; position: absolute; top: 100%; left: 0; background: white; border: 1px solid #ccc; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 100; min-width: 250px;">
                            <a href="javascript:void(0)" onclick="addCustomField()" style="display: block; padding: 10px; text-decoration: none; color: #333; border-bottom: 1px solid #eee;">
                                <strong>✏️ Campo Personalizado</strong>
                            </a>
                            <div style="padding: 5px 10px; font-size: 11px; color: #999; background: #f9f9f9;">CAMPOS ESTÁNDAR</div>
                            <!-- Standard Fields Options -->
                             <?php
                             // Available Standard Keys (from Controller)
                             $availableStd = $standardKeys ?? []; // $standardKeysDefinition passed from controller
                             foreach($availableStd as $k => $l):
                             ?>
                             <a href="javascript:void(0)" class="std-option" data-key="<?= $k ?>" data-label="<?= $l ?>" onclick="addStandardField('<?= $k ?>', '<?= $l ?>')" style="display: block; padding: 8px 10px; text-decoration: none; color: #333;">
                                 <?= $l ?>
                             </a>
                             <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visibility Config (Optional Future Implementation) -->
            <!-- 
            <hr>
            <h4>Campos Estándar (Visibilidad)</h4>
            -->

            <div class="form-actions mt-4">
                <a href="/configuracion/tipos" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Configuración</button>
            </div>
        </form>
    </div>
</div>

<template id="field-row-template">
    <div class="dynamic-row row mb-2">
        <div class="col-md-4">
            <input type="text" name="field_labels[]" class="form-control" placeholder="Etiqueta" required>
        </div>
        <div class="col-md-4">
            <input type="text" name="field_keys[]" class="form-control" placeholder="clave_interna" required>
        </div>
        <div class="col-md-3">
            <select name="field_types[]" class="form-control">
                <option value="text">Texto</option>
                <option value="number">Número</option>
                <option value="date">Fecha</option>
                <option value="textarea">Texto Largo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-danger btn-sm remove-row">❌</button>
        </div>
    </div>
</template>

<script>
function toggleAddMenu() {
    const menu = document.getElementById('add-field-menu');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    updateStandardOptionsVisibility();
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('add-field-menu');
    const btn = document.getElementById('add-field-btn');
    if (!menu.contains(event.target) && !btn.contains(event.target)) {
        menu.style.display = 'none';
    }
});

function addCustomField() {
    const container = document.getElementById('dynamic-fields-container');
    const template = document.getElementById('field-row-template');
    const clone = template.content.cloneNode(true);
    container.appendChild(clone);
    document.getElementById('add-field-menu').style.display = 'none';
}

function addStandardField(key, label) {
    // Check if already exists
    const existing = document.querySelector(`input[name="field_keys[]"][value="${key}"]`);
    if (existing) {
        alert('Este campo ya está en la lista.');
        return;
    }

    const container = document.getElementById('dynamic-fields-container');
    const template = document.getElementById('field-row-template');
    const clone = template.content.cloneNode(true);
    
    // Fill values
    const labelInput = clone.querySelector('input[name="field_labels[]"]');
    const keyInput = clone.querySelector('input[name="field_keys[]"]');
    const typeSelect = clone.querySelector('select[name="field_types[]"]');
    
    labelInput.value = label;
    keyInput.value = key;
    keyInput.readOnly = true;
    keyInput.style.backgroundColor = '#e9ecef';
    
    container.appendChild(clone);
    document.getElementById('add-field-menu').style.display = 'none';
    updateStandardOptionsVisibility();
}

function updateStandardOptionsVisibility() {
    // Collect all current keys
    const currentKeys = Array.from(document.querySelectorAll('input[name="field_keys[]"]')).map(input => input.value);
    
    // Hide options in menu if they are already in list
    document.querySelectorAll('.std-option').forEach(opt => {
        if (currentKeys.includes(opt.dataset.key)) {
            opt.style.display = 'none';
        } else {
            opt.style.display = 'block';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('dynamic-fields-container');

    // Remove Row (Event Delegation)
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.dynamic-row').remove();
            updateStandardOptionsVisibility(); // Just in case a standard field was removed, make it available again
        }
    });

    // Auto-generate key from label for NEW Custom fields
    container.addEventListener('input', function(e) {
        if (e.target.name === 'field_labels[]') {
            const row = e.target.closest('.dynamic-row');
            const keyInput = row.querySelector('input[name="field_keys[]"]');
            
            // Only auto-fill if key is NOT readonly (Custom) and matches pattern
            if (!keyInput.readOnly && (keyInput.value === '' || keyInput.dataset.autofilled === 'true')) {
                const slug = e.target.value.toLowerCase()
                    .replace(/[^\w ]+/g, '')
                    .replace(/ +/g, '_');
                keyInput.value = slug;
                keyInput.dataset.autofilled = 'true';
            }
        }
        // If user manually edits key, stop auto-filling
        if (e.target.name === 'field_keys[]') {
            e.target.dataset.autofilled = 'false';
        }
    });
});
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
?>
