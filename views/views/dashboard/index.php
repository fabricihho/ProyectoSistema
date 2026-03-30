<?php 
ob_start(); 
// No definimos $pageTitle para controlarlo manualmente en la vista
?>

<!-- Welcome Section -->
<div class="mb-20">
    <h2 style="color: #1B3C84; font-size: 1.5em; margin-bottom: 10px;">Bienvenido al Sistema de Archivos TAMEP</h2>
    <div style="border-bottom: 1px solid #e2e8f0; margin-bottom: 15px;"></div>
    
    <div style="color: #4a5568;">
        <p style="margin-bottom: 5px;">Sistema de Gestión Documental y Control de Préstamos</p>
        <p style="margin-bottom: 5px;">Usuario: <strong style="color: #2d3748;"><?= isset($user['nombre_completo']) ? htmlspecialchars($user['nombre_completo']) : 'Usuario' ?></strong></p>
        <p style="margin-bottom: 5px;">Rol: <strong style="color: #2d3748;"><?= isset($user['rol']) ? htmlspecialchars($user['rol']) : 'N/A' ?></strong></p>
    </div>
</div>

<div class="main-header">
    <h1>Dashboard</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Documentos</h3>
        <div class="number"><?= isset($stats['total_documentos']) ? number_format($stats['total_documentos']) : '0' ?></div>
    </div>
    
    <div class="stat-card yellow">
        <h3>Total Contenedores</h3>
        <div class="number"><?= isset($stats['total_contenedores']) ? number_format($stats['total_contenedores']) : '0' ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Libros</h3>
        <div class="number"><?= isset($stats['total_libros']) ? number_format($stats['total_libros']) : '0' ?></div>
    </div>
    
    <div class="stat-card yellow">
        <h3>Amarros</h3>
        <div class="number"><?= isset($stats['total_amarros']) ? number_format($stats['total_amarros']) : '0' ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Préstamos Activos</h3>
        <div class="number"><?= isset($stats['prestamos_activos']) ? number_format($stats['prestamos_activos']) : '0' ?></div>
    </div>
</div>

<!-- Charts Section -->
<div class="card mt-20">
    <h3 style="color: #1B3C84; margin-bottom: 20px;">Estadísticas del Sistema</h3>
    <div class="charts-grid">
        <div class="chart-container">
            <h4 class="text-center">Estado de Documentos</h4>
            <div style="height: 300px; position: relative;">
                <canvas id="docStatusChart"></canvas>
            </div>
        </div>
        <div class="chart-container">
            <h4 class="text-center">Distribución de Contenedores</h4>
            <div style="height: 300px; position: relative;">
                <canvas id="containerTypeChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card mt-20">
    <h3 style="color: #1B3C84; margin-bottom: 15px;">Módulos Disponibles:</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <a href="/catalogacion" class="btn btn-primary module-btn">
                <span style="font-size: 2em; margin-bottom: 10px;">📚</span>
                <span>Catalogación y Búsqueda</span>
            </a>

            <!-- Préstamos con opciones -->
            <div class="btn btn-secondary module-btn has-options" onclick="toggleModuleOptions(this)">
                <span style="font-size: 2em; margin-bottom: 10px;">📤</span>
                <span>Control de Préstamos</span>
                <div class="module-options">
                    <a href="/prestamos">📋 Historial</a>
                    <a href="/prestamos/nuevo">➕ Nuevo Préstamo</a>
                </div>
            </div>

            <a href="/reportes" class="btn btn-primary module-btn">
                <span style="font-size: 2em; margin-bottom: 10px;">📊</span>
                <span>Reportes de Gestión</span>
            </a>
            
            <!-- Herramientas con opciones -->
            <div class="btn btn-secondary module-btn has-options" onclick="toggleModuleOptions(this)">
                <span style="font-size: 2em; margin-bottom: 10px;">🛠️</span>
                <span>Herramientas</span>
                <div class="module-options">
                    <a href="/herramientas/control-amarros">📦 Control Amarros</a>
                </div>
            </div>
            
            <!-- Configuración con opciones -->
            <div class="btn btn-primary module-btn has-options" style="background-color: #6f42c1; border-color: #6f42c1;" onclick="toggleModuleOptions(this)">
                <span style="font-size: 2em; margin-bottom: 10px;">🔧</span>
                <span>Configuración</span>
                <div class="module-options">
                    <a href="/configuracion/password">🔑 Cambiar Contraseña</a>
                </div>
            </div>

            <?php if (isset($user['rol']) && $user['rol'] === 'Administrador'): ?>
            <a href="/admin/usuarios" class="btn btn-secondary module-btn">
                <span style="font-size: 2em; margin-bottom: 10px;">👥</span>
                <span>Gestión de Usuarios</span>
            </a>
            <?php endif; ?>
    </div>
</div>

<style>
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 30px;
}
.chart-container {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.module-btn {
    text-align: center; 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    justify-content: center; 
    padding: 20px;
    position: relative;
    cursor: pointer;
    min-height: 140px;
}

.module-options {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    background: white;
    border: 1px solid #ddd;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 10;
    overflow: hidden;
}

.module-btn.active {
    border-radius: 8px 8px 0 0;
}

.module-btn.active .module-options {
    display: block;
}

.module-options a {
    display: block;
    padding: 10px;
    color: #333;
    text-decoration: none;
    border-bottom: 1px solid #eee;
    text-align: left;
    font-size: 0.9em;
}

.module-options a:last-child {
    border-bottom: none;
}

.module-options a:hover {
    background-color: #f5f7fa;
}
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleModuleOptions(element) {
    // Close other open modules
    document.querySelectorAll('.module-btn.has-options').forEach(btn => {
        if (btn !== element) {
            btn.classList.remove('active');
        }
    });
    
    // Toggle current
    element.classList.toggle('active');
}

document.addEventListener('DOMContentLoaded', function() {
    // Datos para Gráfico de Estado (Doughnut)
    const ctxStatus = document.getElementById('docStatusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Prestados', 'Faltantes', 'Disponibles', 'No Utilizados', 'Anulados'],
            datasets: [{
                data: [
                    <?= $stats['docs_prestados'] ?? 0 ?>, 
                    <?= $stats['docs_faltantes'] ?? 0 ?>, 
                    <?= $stats['docs_disponibles'] ?? 0 ?>,
                    <?= $stats['docs_no_utilizados'] ?? 0 ?>,
                    <?= $stats['docs_anulados'] ?? 0 ?>
                ],
                backgroundColor: [
                    '#3182ce', // Prestado (Blue)
                    '#e53e3e', // Falta (Red)
                    '#38a169', // Disponible (Green)
                    '#d69e2e', // No Utilizado (Yellow/Mustard)
                    '#805ad5'  // Anulado (Purple)
                ],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Datos para Gráfico de Contenedores (Bar) - Unchanged
    const ctxContainer = document.getElementById('containerTypeChart').getContext('2d');
    new Chart(ctxContainer, {
        type: 'bar',
        data: {
            labels: ['Libros', 'Amarros'],
            datasets: [{
                label: 'Cantidad Total',
                data: [
                    <?= $stats['total_libros'] ?? 0 ?>, 
                    <?= $stats['total_amarros'] ?? 0 ?>
                ],
                backgroundColor: [
                    '#1B3C84', 
                    '#FFD100'
                ],
                borderColor: [
                    '#1B3C84',
                    '#e6bc00'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
