<?php
// Funciones auxiliares para el layout moderno
function getUserInitials($nombre) {
    if (empty($nombre)) return 'U';
    $parts = explode(' ', trim($nombre));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    return strtoupper(substr($nombre, 0, 2));
}

function getRoleName($rolId) {
    $roles = [
        1 => 'Administrador',
        2 => 'Recepcionista',
        3 => 'Médico',
        4 => 'Paciente'
    ];
    return $roles[$rolId] ?? 'Usuario';
}

function renderSidebar($activePage = '') {
    $rolId = $_SESSION['rol_id'] ?? 0;
    $nombre = $_SESSION['nombre'] ?? 'Usuario';
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clínica Vida Salud</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/modern.css?v=20261025-controls2">
    <script src="assets/js/ui-controls.js?v=20261025-controls2" defer></script>
</head>
<body class="app-layout">
    <div class="sidebar-overlay" data-sidebar-close aria-hidden="true"></div>
    <aside class="sidebar" id="app-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                </div>
                <span>Clínica Vida Salud</span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Principal</div>
                <a href="panel_moderno.php" class="nav-link <?= $activePage === 'panel' ? 'active' : '' ?>">
                    <svg class="nav-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Dashboard
                </a>
            </div>
            
            <?php if (in_array($rolId, [1, 2], true)): ?>
            <div class="nav-section">
                <div class="nav-section-title">Administración</div>
                <a href="pacientes_moderno.php" class="nav-link <?= $activePage === 'pacientes' ? 'active' : '' ?>">
                    <svg class="nav-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Pacientes
                </a>
                <a href="medicos_moderno.php" class="nav-link <?= $activePage === 'medicos' ? 'active' : '' ?>">
                    <svg class="nav-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Médicos
                </a>
                <a href="especialidades_moderno.php" class="nav-link <?= $activePage === 'especialidades' ? 'active' : '' ?>">
                    <svg class="nav-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                    Especialidades
                </a>
                <a href="horarios_moderno.php" class="nav-link <?= $activePage === 'horarios' ? 'active' : '' ?>">
                    <svg class="nav-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"></path>
                    </svg>
                    Horarios
                </a>
            </div>
            <?php endif; ?>
            
            <div class="nav-section">
                <div class="nav-section-title">Citas Médicas</div>
                <a href="citas_moderno.php" class="nav-link <?= $activePage === 'citas' ? 'active' : '' ?>">
                    <svg class="nav-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    Citas
                </a>
                <?php if (in_array($rolId, [1, 2, 3], true)): ?>
                <a href="agenda.php" class="nav-link <?= $activePage === 'agenda' ? 'active' : '' ?>">
                    <svg class="nav-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Agenda
                </a>
                <?php endif; ?>
                <?php if (in_array($rolId, [1, 2], true)): ?>
                <a href="reportes.php" class="nav-link <?= $activePage === 'reportes' ? 'active' : '' ?>">
                    <svg class="nav-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    Reportes
                </a>
                <?php endif; ?>
                <a href="notificaciones.php" class="nav-link">
                    <svg class="nav-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    Notificaciones
                </a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link" style="color: rgba(255,255,255,0.8);">
                <svg class="nav-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Cerrar Sesión
            </a>
        </div>
    </aside>
    
    <div class="main-content">
        <header class="header">
            <button class="menu-toggle" type="button" aria-label="Abrir menú de navegación" aria-controls="app-sidebar" aria-expanded="false">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <h1 class="header-title">
                <?php 
                switch($activePage) {
                    case 'panel': echo 'Dashboard'; break;
                    case 'pacientes': echo 'Gestión de Pacientes'; break;
                    case 'medicos': echo 'Gestión de Médicos'; break;
                    case 'especialidades': echo 'Gestión de Especialidades'; break;
                    case 'horarios': echo 'Gestión de Horarios'; break;
                    case 'citas': echo 'Citas Médicas'; break;
                    case 'agenda': echo 'Agenda Médica'; break;
                    case 'reportes': echo 'Reportes'; break;
                    default: echo 'Clínica Vida Salud'; break;
                }
                ?>
            </h1>
            <div class="header-user">
                <div class="header-user-info">
                    <div class="header-user-name"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="header-user-role"><?= getRoleName($rolId) ?></div>
                </div>
                <div class="header-user-avatar">
                    <?= getUserInitials($nombre) ?>
                </div>
            </div>
        </header>
        <main class="page-content">
<?php
}

function renderFooter() {
    ?>
        </main>
    </div>
    <script>
        (() => {
            const body = document.body;
            const toggle = document.querySelector('.menu-toggle');
            const overlay = document.querySelector('[data-sidebar-close]');
            const sidebar = document.getElementById('app-sidebar');

            if (!toggle || !overlay || !sidebar) return;

            const setMenu = (open) => {
                body.classList.toggle('sidebar-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
            };

            toggle.addEventListener('click', () => setMenu(!body.classList.contains('sidebar-open')));
            overlay.addEventListener('click', () => setMenu(false));
            sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMenu(false)));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setMenu(false);
            });
            window.addEventListener('resize', () => {
                if (window.innerWidth > 968) setMenu(false);
            });

            // En pantallas pequeñas cada celda muestra el nombre de su columna.
            // Esto permite presentar las tablas como tarjetas sin duplicar HTML.
            document.querySelectorAll('.table').forEach((table) => {
                const headers = Array.from(table.querySelectorAll('thead th')).map((header) =>
                    header.textContent.trim()
                );

                table.querySelectorAll('tbody tr').forEach((row) => {
                    Array.from(row.children).forEach((cell, index) => {
                        if (cell.tagName === 'TD' && headers[index] && !cell.hasAttribute('colspan')) {
                            cell.dataset.label = headers[index];
                        }
                    });
                });
            });
        })();
    </script>
</body>
</html>
<?php
}
