<?php
/**
 * Sidebar - Barra lateral de navegación
 */
$user = $user ?? \TAMEP\Core\Session::user();
?>

<!-- Mobile Toggle Button -->
<button id="mobileToggle" class="mobile-toggle">
    <span></span>
    <span></span>
    <span></span>
</button>

<div class="sidebar" id="sidebar">
    <!-- Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle" title="Ocultar/Mostrar menú">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="sidebar-header">
        <img src="/assets/img/logo-tamep.png" alt="TAMEP" class="sidebar-logo">
        <h1>Sistema de Gestion de Archivos</h1>
        <div class="user-info">
            <?= htmlspecialchars($user['nombre_completo']) ?><br>
            <small><?= htmlspecialchars($user['rol']) ?></small>
        </div>
    </div>

    <nav>
        <ul>
            <li>
                <a href="/inicio" class="sidebar-link">
                    <span class="icon">🏠</span>
                    <span>Inicio</span>
                </a>
            </li>

            <li>
                <details <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/catalogacion') || str_contains($_SERVER['REQUEST_URI'] ?? '', '/contenedores')) ? 'open' : '' ?>>
                    <summary class="sidebar-link" style="cursor: pointer;">
                        <span class="icon">📂</span>
                        <span>Documentos</span>
                    </summary>
                    <ul class="sidebar-submenu">
                        <li><a href="/catalogacion">📄 Buscar Documentos</a></li>
                        <li><a href="/contenedores">📦 Buscar Contenedores</a></li>
                    </ul>
                </details>
            </li>

            <li>
                <details <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/prestamos') ? 'open' : '' ?>>
                    <summary class="sidebar-link" style="cursor: pointer;">
                        <span class="icon">📤</span>
                        <span>Préstamos</span>
                    </summary>
                    <ul class="sidebar-submenu">
                        <li><a href="/prestamos">📋 Historial</a></li>
                        <li><a href="/prestamos/nuevo">➕ Nuevo Préstamo</a></li>
                        <li><a href="/prestamos/importar">📊 Importar Excel</a></li>
                    </ul>
                </details>
            </li>

            <li>
                <details <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/reportes') ? 'open' : '' ?>>
                    <summary class="sidebar-link" style="cursor: pointer;">
                        <span class="icon">📊</span>
                        <span>Reportes</span>
                    </summary>
                    <ul class="sidebar-submenu">
                        <li><a href="/reportes/graficos">📈 Gráficos</a></li>
                        <li><a href="/reportes/prestamos">📤 Préstamos Activos</a></li>
                        <li><a href="/reportes/no-disponibles">⚠️ No Disponibles</a></li>
                        <li><a href="/reportes/auditorias">📜 Historial Auditoría</a></li>
                    </ul>
                </details>
            </li>

            <?php if ($user['rol'] === 'Administrador'): ?>
                <li>
                    <a href="/admin/usuarios" class="sidebar-link">
                        <span class="icon">👥</span>
                        <span>Usuarios</span>
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <details <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/herramientas') ? 'open' : '' ?>>
                    <summary class="sidebar-link" style="cursor: pointer;">
                        <span class="icon">🛠️</span>
                        <span>Herramientas</span>
                    </summary>
                    <ul class="sidebar-submenu">
                        <li><a href="/herramientas/control-amarros">📦 Control Amarros</a></li>
                        <?php if (strtoupper($user['username'] ?? '') === 'VIVI'): ?>
                            <li><a href="/herramientas/varita-magica">✨ Varita Mágica</a></li>
                        <?php endif; ?>
                    </ul>
                </details>
            </li>
            <li>
                <details <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/configuracion') ? 'open' : '' ?>>
                    <summary class="sidebar-link" style="cursor: pointer;">
                        <span class="icon">🔧</span>
                        <span>Configuración</span>
                    </summary>
                    <ul class="sidebar-submenu">
                        <li><a href="/configuracion/tipos">📄 Tipos de Documento</a></li>
                        <li><a href="/configuracion/ubicaciones">📍 Ubicaciones</a></li>
                        <li><a href="/configuracion/unidades">🏢 Unidades / Áreas</a></li>
                        <li><a href="/configuracion/password">🔑 Cambiar Contraseña</a></li>
                    </ul>
                </details>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="/logout" class="sidebar-link">
                <span class="icon">🚪</span>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </nav>
</div>

<!-- Overlay for Mobile -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<script>
    // Sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const mobileBtn = document.getElementById('mobileToggle');
        const overlay = document.getElementById('sidebarOverlay');

        // Desktop Collapse Logic
        // Only apply collapsed state on desktop initially
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed && window.innerWidth > 768) {
            sidebar.classList.add('collapsed');
        }

        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        // Mobile Open Logic
        function toggleMobile() {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            mobileBtn.classList.toggle('active');
        }

        if (mobileBtn) {
            mobileBtn.addEventListener('click', toggleMobile);
        }

        if (overlay) {
            overlay.addEventListener('click', toggleMobile);
        }

        // --- Persistir Scroll del Sidebar ---
        const sidebarScrollKey = 'sidebarScrollPos';

        // Restaurar scroll
        const savedScroll = localStorage.getItem(sidebarScrollKey);
        if (savedScroll) {
            sidebar.scrollTop = savedScroll;
        }

        // Guardar scroll antes de salir
        window.addEventListener('beforeunload', function () {
            localStorage.setItem(sidebarScrollKey, sidebar.scrollTop);
        });
    });
</script>