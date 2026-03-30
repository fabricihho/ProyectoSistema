
<!-- Modal de Creación Rápida de Contenedor -->
<div id="modalCrearContenedor" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; align-items:center; justify-content:center;">
    <div style="background:white; padding:25px; border-radius:8px; width:500px; max-width:90%; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">
            <h3 style="margin:0; color:#1B3C84;">➕ Nuevo Contenedor</h3>
            <button type="button" onclick="cerrarModalCrearContenedor()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>

        <form id="formCrearContenedorRapido">
            <!-- Hidden input to store who called us -->
            <input type="hidden" id="targetSelectId" value="">

            <!-- New Field: Tipo Documento -->
            <div class="form-group" style="margin-bottom:15px;">
                <label>Tipo de Documento (Para etiqueta DIA/RI/etc) <span style="color:red">*</span></label>
                <select name="tipo_documento" id="quick_tipo_documento" class="form-control" required style="width:100%; padding:8px;">
                    <option value="">Seleccione...</option>
                    <?php if(isset($tiposDocumento)): ?>
                        <?php foreach($tiposDocumento as $td): ?>
                            <option value="<?= $td['id'] ?>"><?= htmlspecialchars($td['nombre']) ?> (<?= htmlspecialchars($td['codigo']) ?>)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Tipo de Contenedor <span style="color:red">*</span></label>
                <select name="tipo_contenedor" class="form-control" required style="width:100%; padding:8px;">
                    <option value="AMARRO">Amarro</option>
                    <option value="LIBRO">Libro</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Número <span style="color:red">*</span></label>
                <input type="number" name="numero" class="form-control" required placeholder="Ej: 1" style="width:100%; padding:8px; box-sizing:border-box;">
            </div>

            <!-- New Field: Codigo ABC -->
            <div class="form-group" style="margin-bottom:15px;">
                <label>Código ABC</label>
                <input type="text" name="codigo_abc" class="form-control" placeholder="Opcional" style="width:100%; padding:8px; box-sizing:border-box;">
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Gestión (Año)</label>
                <input type="number" name="gestion" class="form-control" value="<?= date('Y') ?>" style="width:100%; padding:8px; box-sizing:border-box;">
            </div>
            
            <div class="form-group" style="margin-bottom:20px;">
                <label>Ubicación Física</label>
                <!-- We need to fetch locations or pass them. For simplify, we'll try to clone from parent or just use input -->
                <!-- Ideally, this partial should receive $ubicaciones. If not available, we show a simplified input or fetch -->
                <select name="ubicacion_id" id="quick_ubicacion_id" class="form-control" style="width:100%; padding:8px;">
                    <option value="">-- Sin asignar --</option>
                    <?php if(isset($ubicaciones)): ?>
                        <?php foreach($ubicaciones as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalCrearContenedor()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarContenedorRapido()">💾 Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
let targetSelectElementId = null;

function abrirModalCrearContenedor(targetId) {
    targetSelectElementId = targetId;
    document.getElementById('targetSelectId').value = targetId;
    document.getElementById('modalCrearContenedor').style.display = 'flex';
    
    // --- Pre-population Logic ---
    // 1. Gestion (Year)
    const mainGestion = document.getElementById('gestion');
    const modalGestion = document.querySelector('#formCrearContenedorRapido input[name="gestion"]');
    if (mainGestion && modalGestion && mainGestion.value) {
        modalGestion.value = mainGestion.value;
    }

    // 2. Tipo Documento
    // Main form uses CODE (e.g. 'DIA'), Modal uses ID. We need to match.
    const mainTipo = document.getElementById('tipo_documento'); // Select with Code
    const modalTipo = document.getElementById('quick_tipo_documento'); // Select with ID
    
    if (mainTipo && modalTipo && mainTipo.value) {
        const code = mainTipo.value; // e.g. "DIA"
        // Find option in modal that contains this code
        // Modal options format: "Name (CODE)"
        for (let i = 0; i < modalTipo.options.length; i++) {
            const opt = modalTipo.options[i];
            if (opt.text.includes('(' + code + ')')) {
                modalTipo.selectedIndex = i;
                break;
            }
        }
    }
}

function cerrarModalCrearContenedor() {
    document.getElementById('modalCrearContenedor').style.display = 'none';
    document.getElementById('formCrearContenedorRapido').reset();
}

function guardarContenedorRapido() {
    const form = document.getElementById('formCrearContenedorRapido');
    if (!form.reportValidity()) return;

    const data = {
        tipo_documento: form.tipo_documento.value,
        tipo_contenedor: form.tipo_contenedor.value,
        numero: form.numero.value,
        codigo_abc: form.codigo_abc.value,
        gestion: form.gestion.value,
        ubicacion_id: form.ubicacion_id.value
    };

    // Disable button
    const btn = form.querySelector('.btn-primary');
    const originalText = btn.innerText;
    btn.innerText = 'Guardando...';
    btn.disabled = true;

    fetch('/contenedores/guardar-rapido', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(async response => {
        if (!response.ok) {
            const text = await response.text();
            throw new Error(`Server Error (${response.status}): ${text.substring(0, 100)}...`);
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            // Success! Now update the UI.
            
            // Check if we are dealing with the Autocomplete Search Input
            if (targetSelectElementId === 'contenedor_search' && typeof selectContenedor === 'function') {
                // We are in the "Edit Document" view with Autocomplete
                // Construct item object expected by selectContenedor
                const newItem = {
                    id: result.data.id,
                    tipo_documento_codigo: '???', // We might not have this back from API perfectly or need to guess
                    gestion: data.gestion,
                    tipo_contenedor: data.tipo_contenedor,
                    numero: data.numero,
                    ubicacion_nombre: '' // We don't have name easily, but that's ok
                };
                
                // Try to get Code from modal select text to be nicer
                const modalTipo = document.getElementById('quick_tipo_documento');
                if (modalTipo.selectedIndex >= 0) {
                     const text = modalTipo.options[modalTipo.selectedIndex].text;
                     const match = text.match(/\(([^)]+)\)/);
                     if (match) newItem.tipo_documento_codigo = match[1];
                }

                // Update the UI
                selectContenedor(newItem);
                
                // Also update the select location filter logic if needed? No, autocomplete ignores filter logic visually
                
            } else {
                // Fallback for standard Select elements (e.g. Create View if not updated yet)
                const select = document.getElementById(targetSelectElementId);
                if (select && select.tagName === 'SELECT') {
                    const option = new Option(result.data.text, result.data.id);
                    option.setAttribute('data-ubicacion', result.data.ubicacion_id || '');
                    select.add(option, select.options[1]); // Add top after "Sin asignar"
                    select.value = result.data.id;
                    const event = new Event('change');
                    select.dispatchEvent(event);
                }
            }

            cerrarModalCrearContenedor();
            alert('Contenedor creado y asignado exitosamente');
        } else if (result.exists) {
            // Special Case: Duplicate confirmation
            if (confirm(result.message)) {
                // Retry with force
                data.force = true;
                fetch('/contenedores/guardar-rapido', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data)
                })
                .then(async response => {
                    if (!response.ok) {
                        const text = await response.text();
                        throw new Error(`Server Error (${response.status}): ${text.substring(0, 100)}...`);
                    }
                    return response.json();
                })
                .then(retryResult => {
                    if (retryResult.success) {
                        // Success on retry! Same UI update logic
                        if (targetSelectElementId === 'contenedor_search' && typeof selectContenedor === 'function') {
                            const newItem = {
                                id: retryResult.data.id,
                                tipo_documento_codigo: '???',
                                gestion: data.gestion,
                                tipo_contenedor: data.tipo_contenedor,
                                numero: data.numero,
                                ubicacion_nombre: ''
                            };
                            
                            const modalTipo = document.getElementById('quick_tipo_documento');
                            if (modalTipo.selectedIndex >= 0) {
                                 const text = modalTipo.options[modalTipo.selectedIndex].text;
                                 const match = text.match(/\(([^)]+)\)/);
                                 if (match) newItem.tipo_documento_codigo = match[1];
                            }
                            selectContenedor(newItem);
                        } else {
                            const select = document.getElementById(targetSelectElementId);
                            if (select && select.tagName === 'SELECT') {
                                const option = new Option(retryResult.data.text, retryResult.data.id);
                                option.setAttribute('data-ubicacion', retryResult.data.ubicacion_id || '');
                                select.add(option, select.options[1]);
                                select.value = retryResult.data.id;
                                const event = new Event('change');
                                select.dispatchEvent(event);
                            }
                        }
                        cerrarModalCrearContenedor();
                        alert('Contenedor creado y asignado exitosamente (duplicado)');
                    } else {
                        alert('Error: ' + retryResult.message);
                    }
                })
                .catch(error => { console.error('Error:', error); alert('Error: ' + error.message); });
            }
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    })
    .finally(() => {
        btn.innerText = originalText;
        btn.disabled = false;
    });
}
</script>
