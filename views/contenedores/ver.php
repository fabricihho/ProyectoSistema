<?php 
ob_start(); 
$pageTitle = 'Ver Contenedor';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 style="color: #1B3C84;">
            <?= htmlspecialchars($contenedor['tipo_contenedor']) ?> #<?= htmlspecialchars($contenedor['numero']) ?><?= !empty($contenedor['codigo_abc']) ? '.' . htmlspecialchars($contenedor['codigo_abc']) : '' ?> (<?= htmlspecialchars($contenedor['gestion']) ?>)
        </h2>
        <div class="header-actions">
            <a href="/contenedores/editar/<?= $contenedor['id'] ?>" class="btn btn-warning">✏️ Editar</a>
            <a href="/contenedores" class="btn btn-secondary">← Volver</a>
        </div>
    </div>
    
    <div class="card-body" style="padding: 20px;">
        <!-- Información General -->
        <h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #555;">Información General</h4>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div>
                <strong>Tipo Documento (Contenido):</strong>
                <p><?= !empty($contenedor['tipo_documento']) ? htmlspecialchars($contenedor['tipo_documento']) : '<span class="text-muted">N/A</span>' ?></p>
            </div>
            
            <div>
                <strong>Ubicación Física:</strong>
                <p>
                    <?= !empty($contenedor['ubicacion']) ? htmlspecialchars($contenedor['ubicacion']['nombre']) : '<span class="text-muted">Sin asignar</span>' ?>
                    <?= !empty($contenedor['bloque_nivel']) ? ' - ' . htmlspecialchars($contenedor['bloque_nivel']) : '' ?>
                </p>
            </div>
            
            <div>
                <strong>Color:</strong>
                <p><?= !empty($contenedor['color']) ? htmlspecialchars($contenedor['color']) : '<span class="text-muted">N/A</span>' ?></p>
            </div>
            
            <div>
                <strong>ID Sistema:</strong>
                <p>#<?= htmlspecialchars($contenedor['id']) ?></p>
            </div>
        </div>

        <!-- Documentos Contenidos -->
        <h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #555;">
            📂 Documentos Contenidos <span class="badge badge-info"><?= count($documentos) ?></span>
        </h4>

        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th style="position: sticky; top: 0; background-color: #1B3C84; color: white; z-index: 10; width: 20%;">Nro Comprobante</th>
                        <th style="position: sticky; top: 0; background-color: #1B3C84; color: white; z-index: 10; width: 25%;">Tipo</th>
                        <th style="position: sticky; top: 0; background-color: #1B3C84; color: white; z-index: 10; width: 15%;">Gestión</th>
                        <th style="position: sticky; top: 0; background-color: #1B3C84; color: white; z-index: 10; width: 40%;">Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documentos)): ?>
                        <tr><td colspan="4" class="text-center p-3">No hay documentos en este contenedor.</td></tr>
                    <?php else: ?>
                        <?php foreach ($documentos as $doc): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($doc['nro_comprobante']) ?></strong></td>
                                <td><?= htmlspecialchars($doc['tipo_documento']) ?></td>
                                <td><?= htmlspecialchars($doc['gestion']) ?></td>
                                <td><?= htmlspecialchars($doc['observaciones'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
