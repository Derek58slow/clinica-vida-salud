<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$nombre = $_SESSION['nombre'] ?? 'Usuario';
$rolId = (int) ($_SESSION['rol_id'] ?? 0);

try {
    $pdo = conectarBD();
    
    // Obtener estadísticas
    $stats = [];
    
    // Total pacientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pacientes WHERE activo = TRUE");
    $stats['pacientes'] = $stmt->fetch()['total'] ?? 0;
    
    // Total médicos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM medicos WHERE activo = TRUE");
    $stats['medicos'] = $stmt->fetch()['total'] ?? 0;
    
    // Citas del día
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM citas WHERE DATE(inicio) = CURRENT_DATE AND estado IN ('solicitada', 'confirmada')");
    $stats['citas_hoy'] = $stmt->fetch()['total'] ?? 0;
    
    // Total citas programadas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM citas WHERE estado IN ('solicitada', 'confirmada') AND inicio >= CURRENT_DATE");
    $stats['citas_programadas'] = $stmt->fetch()['total'] ?? 0;
    
    // Citas atendidas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM citas WHERE estado = 'completada'");
    $stats['citas_atendidas'] = $stmt->fetch()['total'] ?? 0;
    
    // Citas canceladas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM citas WHERE estado = 'cancelada'");
    $stats['citas_canceladas'] = $stmt->fetch()['total'] ?? 0;
    
    // Próximas citas
    $stmt = $pdo->query("SELECT c.id, c.inicio, c.estado, p.nombres, p.apellidos, m.nombres as m_nombres, m.apellidos as m_apellidos, e.nombre as especialidad
                         FROM citas c
                         JOIN pacientes p ON p.id = c.paciente_id
                         JOIN medicos m ON m.id = c.medico_id
                         JOIN especialidades e ON e.id = c.especialidad_id
                         WHERE c.inicio >= CURRENT_TIMESTAMP AND c.estado IN ('solicitada', 'confirmada')
                         ORDER BY c.inicio ASC
                         LIMIT 5");
    $proximasCitas = $stmt->fetchAll();
    
} catch (Throwable $e) {
    error_log($e->getMessage());
    $stats = ['pacientes' => 0, 'medicos' => 0, 'citas_hoy' => 0, 'citas_programadas' => 0, 'citas_atendidas' => 0, 'citas_canceladas' => 0];
    $proximasCitas = [];
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('panel');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Pacientes Activos</div>
            <div class="stat-value"><?= number_format($stats['pacientes']) ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Médicos Activos</div>
            <div class="stat-value"><?= number_format($stats['medicos']) ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Citas del Día</div>
            <div class="stat-value"><?= number_format($stats['citas_hoy']) ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 11 12 14 22 4"></polyline>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Citas Atendidas</div>
            <div class="stat-value"><?= number_format($stats['citas_atendidas']) ?></div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Próximas Citas
            </h2>
            <a href="citas.php" class="btn btn-sm btn-secondary">Ver todas</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($proximasCitas)): ?>
                <div class="empty-state">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <div class="empty-state-title">No hay citas próximas</div>
                    <div class="empty-state-text">No hay citas programadas para hoy o en el futuro cercano</div>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Especialidad</th>
                                <th>Fecha y Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximasCitas as $cita): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cita['nombres'] . ' ' . $cita['apellidos'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td>Dr. <?= htmlspecialchars($cita['m_nombres'] . ' ' . $cita['m_apellidos'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($cita['especialidad'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($cita['inicio'])) ?></td>
                                    <td>
                                        <?php if ($cita['estado'] === 'confirmada'): ?>
                                            <span class="badge badge-success">Confirmada</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Solicitada</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"></path>
                </svg>
                Acceso Rápido
            </h2>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; gap: 10px;">
            <?php if (in_array($rolId, [1, 2], true)): ?>
                <a href="registrar_paciente_moderno.php" class="btn btn-primary" style="justify-content: flex-start;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    Nuevo Paciente
                </a>
                <a href="citas.php?accion=nueva" class="btn btn-secondary" style="justify-content: flex-start;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <line x1="12" y1="14" x2="12" y2="18"></line>
                        <line x1="10" y1="16" x2="14" y2="16"></line>
                    </svg>
                    Agendar Cita
                </a>
            <?php endif; ?>
            <a href="pacientes_moderno.php" class="btn btn-secondary" style="justify-content: flex-start;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                Buscar Paciente
            </a>
        </div>
    </div>
</div>

<?php
renderFooter();
?>
