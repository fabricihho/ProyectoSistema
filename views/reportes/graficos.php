<?php
ob_start();
$title = 'Reportes Gráficos';
?>

<div class="reportes-header mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>📊 Estadísticas del Sistema</h1>
        <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir Gráficos</button>
    </div>

    <div class="stats-cards">
        <div class="stat-card stat-blue">
            <div class="stat-number">
                <?= number_format($stats['total_prestados']) ?>
            </div>
            <div class="stat-label">Documentos Prestados</div>
        </div>
        <div class="stat-card stat-red">
            <div class="stat-number">
                <?= number_format($stats['prestamos_vencidos']) ?>
            </div>
            <div class="stat-label">Préstamos Vencidos</div>
        </div>
        <div class="stat-card stat-yellow">
            <div class="stat-number">
                <?= number_format($stats['total_faltantes']) ?>
            </div>
            <div class="stat-label">Documentos Faltantes</div>
        </div>
        <div class="stat-card stat-gray">
            <div class="stat-number">
                <?= number_format($stats['total_anulados']) ?>
            </div>
            <div class="stat-label">Documentos Anulados</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
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
</div>

<style>
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        border-left: 4px solid #ccc;
    }

    .stat-card.stat-blue {
        border-left-color: #3182ce;
    }

    .stat-card.stat-red {
        border-left-color: #e53e3e;
    }

    .stat-card.stat-yellow {
        border-left-color: #d69e2e;
    }

    .stat-card.stat-gray {
        border-left-color: #718096;
    }

    .stat-number {
        font-size: 2em;
        font-weight: bold;
        color: #2d3748;
    }

    .stat-label {
        color: #718096;
        font-size: 0.9em;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
    }

    .chart-container {
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        /* box-shadow: 0 2px 4px rgba(0,0,0,0.05); */
    }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
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

        // Datos para Gráfico de Contenedores (Bar)
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