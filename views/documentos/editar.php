<?php 
ob_start(); 
$pageTitle = 'Editar Documento';

// Rebuild query string for Links
$filterParams = array_filter($filters, function($v) { return $v !== ''; });
$queryString = !empty($filterParams) ? '?' . http_build_query($filterParams) : '';
$cancelUrl = '/catalogacion' . $queryString;
$detailUrl = '/catalogacion/ver/' . $documento['id'] . $queryString;
?>

<div class="card">
    <div class="card-header flex-between">
        <h2>✏️ Editar Documento</h2>
        <div>
            <a href="<?= $cancelUrl ?>" class="btn btn-primary" style="margin-right: 10px;">📋 Volver al Listado</a>
            <a href="<?= $detailUrl ?>" class="btn btn-secondary">← Volver al Detalle</a>
        </div>
    </div>
    
    <form method="POST" action="/catalogacion/actualizar/<?= $documento['id'] ?>" class="document-form">
        <!-- Hidden Inputs for Filters Persistence -->
        <?php foreach ($filters as $key => $val): ?>
            <input type="hidden" name="filter_<?= $key ?>" value="<?= htmlspecialchars($val) ?>">
        <?php endforeach; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="tipo_documento">Tipo de Documento <span class="required">*</span></label>
                <select id="tipo_documento" name="tipo_documento" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($tiposDocumento as $td): ?>
                        <option value="<?= $td['codigo'] ?>" <?= ($documento['tipo_documento'] ?? '') === $td['codigo'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($td['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="gestion">Gestión <span class="required">*</span></label>
                <input type="number" id="gestion" name="gestion" class="form-control" 
                       value="<?= htmlspecialchars($documento['gestion'] ?? '') ?>" 
                       min="2000" max="<?= date('Y') + 1 ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="nro_comprobante">Número de Comprobante <span class="required">*</span></label>
                <input type="text" id="nro_comprobante" name="nro_comprobante" class="form-control" 
                       value="<?= htmlspecialchars($documento['nro_comprobante'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="codigo_abc">Código ABC</label>
                <input type="text" id="codigo_abc" name="codigo_abc" class="form-control" 
                       value="<?= htmlspecialchars($documento['codigo_abc'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-row">
            <!-- Location Filter Removed: Not needed with AJAX search -->
            <!-- Container Selection AJAX -->
            <div class="form-group" style="flex: 2;">
                <label for="contenedor_search">Contenedor Físico (Buscar por Número o Gestión)</label>
                
                <!-- Wrapper for Input + Plus Button (Hidden when selected) -->
                <div class="search-container-wrapper" style="display:flex; gap:5px; position: relative;">
                    <input type="text" id="contenedor_search" class="form-control" 
                           placeholder="Escriba para buscar (ej. 2023, AMARRO...)" 
                           autocomplete="off">
                           
                    <button type="button" class="btn btn-success" onclick="abrirModalCrearContenedor('contenedor_search')" title="Crear Nuevo Contenedor">➕</button>
                    
                    <div id="contenedor_results" class="autocomplete-results" style="display:none;"></div>
                </div>

                <input type="hidden" id="contenedor_fisico_id" name="contenedor_fisico_id" value="<?= $documento['contenedor_fisico_id'] ?? '' ?>">
                
                <!-- Info Div (Shown as Card when selected) -->
                <div id="contenedor_selected_info" style="margin-top: 5px;">
                    <?php if (!empty($contenedores) && !empty($documento['contenedor_fisico_id'])): 
                        $curr = $contenedores[0]; 
                    ?>
                        Seleccionado: [<?= $curr['tipo_documento_codigo'] ?>] <?= $curr['gestion'] ?> <?= $curr['tipo_contenedor'] ?> #<?= $curr['numero'] ?><?= !empty($curr['codigo_abc']) ? '.' . $curr['codigo_abc'] : '' ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="estado_documento">Estado del Documento <span class="required">*</span></label>
                <select id="estado_documento" name="estado_documento" class="form-control" required>
                    <option value="DISPONIBLE" <?= ($documento['estado_documento'] ?? '') === 'DISPONIBLE' ? 'selected' : '' ?>>🟢 Disponible</option>
                    <option value="FALTA" <?= ($documento['estado_documento'] ?? '') === 'FALTA' ? 'selected' : '' ?>>🔴 Falta</option>
                    <option value="PRESTADO" <?= ($documento['estado_documento'] ?? '') === 'PRESTADO' ? 'selected' : '' ?>>🔵 Prestado</option>
                    <option value="NO UTILIZADO" <?= ($documento['estado_documento'] ?? '') === 'NO UTILIZADO' ? 'selected' : '' ?>>🟡 No Utilizado</option>
                    <option value="ANULADO" <?= ($documento['estado_documento'] ?? '') === 'ANULADO' ? 'selected' : '' ?>>🟣 Anulado</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="4"><?= htmlspecialchars($documento['observaciones'] ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
            <a href="<?= $cancelUrl ?>" class="btn btn-secondary">❌ Cancelar</a>
            <button type="button" class="btn btn-danger" onclick="confirmarEliminacion()">🗑️ Eliminar Documento</button>
        </div>
    </form>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.required {
    color: #E53E3E;
    font-weight: bold;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #E2E8F0;
}

.document-form {
    padding: 20px;
}

/* Autocomplete Styles */
.autocomplete-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 0 0 4px 4px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.autocomplete-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.autocomplete-item:hover {
    background-color: #f5f7fa;
}

.autocomplete-item:last-child {
    border-bottom: none;
}
</style>

<script>
function confirmarEliminacion() {
    if (confirm('¿Está seguro que desea eliminar este documento?\n\nEsta acción no se puede deshacer.')) {
        window.location.href = '/catalogacion/eliminar/<?= $documento['id'] ?>';
    }
}

// --- Autocomplete Logic ---
const searchInput = document.getElementById('contenedor_search');
const resultsDiv = document.getElementById('contenedor_results');
const hiddenIdInput = document.getElementById('contenedor_fisico_id');
const infoDiv = document.getElementById('contenedor_selected_info'); // This will now hold the "Card"
const searchGroup = document.getElementById('contenedor_search_group'); // We need to wrap input+buttons to toggle visibility
let debounceTimer;

// Wrap input and buttons if not already wrapped (or just rely on existing structure if modifying HTML)
// Actually, let's just control visibility of the input vs the info div.

searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    
    clearTimeout(debounceTimer);
    
    if (query.length < 1) {
        resultsDiv.style.display = 'none';
        return;
    }
    
    debounceTimer = setTimeout(() => {
        fetch(`/contenedores/api-buscar?q=${encodeURIComponent(query)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                resultsDiv.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
    // Format: [CODE] 2024 AMARRO #2 (ABC)
                        div.textContent = `[${item.tipo_documento_codigo || '?'}] ${item.gestion} ${item.tipo_contenedor} #${item.numero}`;
                        if (item.codigo_abc) {
                            div.textContent += ` (${item.codigo_abc})`;
                        }
                        if (item.ubicacion_nombre) {
                            div.textContent += ` - ${item.ubicacion_nombre}`;
                        }
                        div.onclick = () => selectContenedor(item);
                        resultsDiv.appendChild(div);
                    });
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.innerHTML = '<div class="autocomplete-item" style="color:#999; cursor:default;">No se encontraron resultados</div>';
                    resultsDiv.style.display = 'block';
                }
            })
            .catch(err => {
                console.error('Error fetching containers:', err);
                resultsDiv.style.display = 'none';
            });
    }, 300);
});

// Hide results when clicking outside
document.addEventListener('click', function(e) {
    if (e.target !== searchInput && e.target !== resultsDiv && !resultsDiv.contains(e.target)) {
        resultsDiv.style.display = 'none';
    }
});

function selectContenedor(item) {
    hiddenIdInput.value = item.id;
    
    // Update Info Display (Card Style)
    let text = `📦 [${item.tipo_documento_codigo || '?'}] ${item.gestion} ${item.tipo_contenedor} #${item.numero}`;
    if (item.codigo_abc) {
        text += `.${item.codigo_abc}`;
    }
    
    // HTML for the Selected State
    const selectedHtml = `
        <div style="background: #e6fffa; border: 1px solid #38b2ac; padding: 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
            <span style="color: #234e52; font-weight: bold;">${text}</span>
            <button type="button" class="btn btn-sm btn-danger" onclick="clearContenedorSelection()" title="Quitar / Cambiar">❌ Quitar</button>
        </div>
    `;
    
    infoDiv.innerHTML = selectedHtml;
    infoDiv.style.display = 'block';
    
    // Hide Search Input
    document.querySelector('.search-container-wrapper').style.display = 'none';
    
    searchInput.value = ''; // Clear search text
    resultsDiv.style.display = 'none';
}

function clearContenedorSelection() {
    hiddenIdInput.value = '';
    
    // Hide Info Display
    infoDiv.style.display = 'none';
    infoDiv.innerHTML = '';
    
    // Show Search Input
    document.querySelector('.search-container-wrapper').style.display = 'flex';
    searchInput.focus();
}

// Initialize state
document.addEventListener('DOMContentLoaded', function() {
    const currentId = hiddenIdInput.value;
    if (currentId) {
        // We have an initial value. logic to hide search and show card is needed.
        // We already have the text in the server-side rendered infoDiv, but we need to structure it.
        // For simplicity, let's parse the server-rendered text if possible, or just hide the search and show the remove button.
        
        // Let's rely on the PHP rendering to set the initial state correctly? 
        // Or cleaner: Trigger 'selectContenedor' with data scraped from DOM?
        // Actually, PHP renders the "Seleccionado: ..." text.
        // Let's hide the search box if PHP rendered a selection.
        
        // This is a bit tricky without full data object on load.
        // Let's just adjust the visibility based on whether infoDiv has content.
        const hasSelection = infoDiv.innerText.trim().length > 0 && !infoDiv.innerText.includes('Sin contenedor');

        if (hasSelection) {
             const text = infoDiv.innerText.replace('Seleccionado: ', '').trim();
             // Rebuild the nice card
             infoDiv.innerHTML = `
                <div style="background: #e6fffa; border: 1px solid #38b2ac; padding: 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #234e52; font-weight: bold;">📦 ${text}</span>
                    <button type="button" class="btn btn-sm btn-danger" onclick="clearContenedorSelection()" title="Quitar / Cambiar">❌ Quitar</button>
                </div>
            `;
            document.querySelector('.search-container-wrapper').style.display = 'none';
        } else {
            infoDiv.style.display = 'none';
            document.querySelector('.search-container-wrapper').style.display = 'flex';
        }
    } else {
        infoDiv.style.display = 'none';
        document.querySelector('.search-container-wrapper').style.display = 'flex';
    }
});
</script>

<?php 
// Include Modal Partial
require __DIR__ . '/../layouts/modal_crear_contenedor.php';

$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
