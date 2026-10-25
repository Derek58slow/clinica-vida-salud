<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rolId, [1, 2], true)) {
    http_response_code(403);
    exit('No tienes permiso para registrar pacientes.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('pacientes');
?>

<div style="margin-bottom: 24px; max-width: 900px; margin-left: auto; margin-right: auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700; color: var(--color-text); margin-bottom: 6px; letter-spacing: -0.02em;">Registrar nuevo paciente</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Completa la información para dar de alta un nuevo paciente</p>
        </div>
        <a href="pacientes_moderno.php" class="btn btn-secondary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Volver al listado
        </a>
    </div>
</div>

<div class="card" style="max-width: 900px; margin: 0 auto; box-shadow: var(--shadow-lg);">
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?php
                $errores = [
                    'datos' => 'Revisa los datos del formulario. Verifica que todos los campos obligatorios estén completos y sean válidos.',
                    'duplicado' => 'Ya existe un paciente con ese documento de identidad.',
                    'servidor' => 'No se pudo guardar el paciente. Inténtalo nuevamente.'
                ];
                echo htmlspecialchars($errores[$_GET['error']] ?? 'Error al guardar el paciente.');
                ?>
            </div>
        <?php endif; ?>

        <form action="guardar_paciente.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div style="margin-bottom: 32px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #EAF6F8 0%, #F0FDFA 100%); color: var(--color-primary); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h3 style="font-size: 17px; font-weight: 600; color: var(--color-text);">Información personal</h3>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Nombres</label>
                        <input type="text" name="nombres" class="form-input" style="height: 44px;" maxlength="120" required placeholder="Ingrese los nombres completos">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Apellidos</label>
                        <input type="text" name="apellidos" class="form-input" style="height: 44px;" maxlength="120" required placeholder="Ingrese los apellidos completos">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Documento de identidad</label>
                        <input type="text" name="documento_identidad" class="form-input" style="height: 44px;" maxlength="30" placeholder="Ej: 12345678-9">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-input" style="height: 44px;" max="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="form-group" style="max-width: 300px;">
                    <label class="form-label">Sexo</label>
                    <select name="sexo" class="form-select" style="height: 44px;">
                        <option value="">Seleccionar</option>
                        <option value="masculino">Masculino</option>
                        <option value="femenino">Femenino</option>
                        <option value="otro">Otro</option>
                        <option value="no_especificado">No especificado</option>
                    </select>
                </div>
            </div>
            
            <div style="padding-top: 28px; border-top: 1px solid var(--color-border);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #EAF6F8 0%, #F0FDFA 100%); color: var(--color-primary); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </div>
                    <h3 style="font-size: 17px; font-weight: 600; color: var(--color-text);">Datos de contacto</h3>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-input" style="height: 44px;" maxlength="30" placeholder="Ej: +503 0000-0000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="correo" class="form-input" style="height: 44px;" maxlength="150" placeholder="ejemplo@correo.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <textarea name="direccion" class="form-textarea" style="min-height: 90px;" rows="3" placeholder="Ingrese la dirección completa del paciente"></textarea>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 36px; padding-top: 28px; border-top: 1px solid var(--color-border); flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary" style="padding: 11px 26px; font-weight: 600;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Guardar paciente
                </button>
                <a href="pacientes_moderno.php" class="btn btn-secondary" style="padding: 11px 22px;">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
renderFooter();
?>
