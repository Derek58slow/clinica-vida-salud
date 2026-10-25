<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);
$nombre = $_SESSION['nombre'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Clínica Vida Salud</title>
    <link rel="stylesheet" href="assets/css/modern.css">
    <style>
        body {
            background: linear-gradient(135deg, #EAF6F8 0%, #F5F8FA 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(23, 107, 120, 0.15);
            overflow: hidden;
            border: 1px solid rgba(23, 107, 120, 0.1);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
            padding: 40px 32px;
            text-align: center;
            color: white;
        }
        
        .login-logo {
            width: 70px;
            height: 70px;
            background: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: var(--color-primary);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .login-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            opacity: 0.95;
            font-size: 14px;
        }
        
        .login-body {
            padding: 32px;
        }
        
        .login-footer {
            text-align: center;
            padding: 20px 32px 32px;
            color: var(--color-text-light);
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                </div>
                <h1 class="login-title">Clínica Vida Salud</h1>
                <p class="login-subtitle">Sistema de Gestión de Citas Médicas</p>
            </div>
            <div class="login-body">
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error" style="margin-bottom: 24px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        Usuario o contraseña incorrectos
                    </div>
                <?php endif; ?>
                
                <form action="procesar_login.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="usuario" class="form-input" required autocomplete="username" placeholder="Ingrese su nombre de usuario">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="contrasena" class="form-input" required autocomplete="current-password" placeholder="Ingrese su contraseña">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        Iniciar Sesión
                    </button>
                </form>
            </div>
            <div class="login-footer">
                © <?= date('Y') ?> Clínica Vida Salud. Todos los derechos reservados.
            </div>
        </div>
    </div>
</body>
</html>
