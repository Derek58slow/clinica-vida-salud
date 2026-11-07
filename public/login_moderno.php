<?php
session_start();
require_once __DIR__.'/includes/icons.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: panel_moderno.php');
    exit;
}

if (!isset($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$error = $_GET['error'] ?? '';
$intentos = (int) ($_SESSION['login_intentos'] ?? 0);
$restantes = max(0, 5 - $intentos);
$bloqueadoHasta = (int) ($_SESSION['login_bloqueado_hasta'] ?? 0);
$segundosBloqueo = max(0, $bloqueadoHasta - time());
$usuarioAnterior = $_SESSION['login_usuario_intento'] ?? '';

$mensajes = [
    '1' => 'Los datos ingresados no son correctos.',
    'credenciales' => 'Usuario o contraseña incorrectos. Verifica los datos e inténtalo nuevamente.',
    'bloqueado' => 'Se alcanzó el límite de intentos. El acceso está pausado durante 5 minutos para proteger la cuenta.',
    'sesion' => 'La sesión del formulario venció. Actualiza la página e inténtalo nuevamente.',
    'rol' => 'La cuenta no tiene un rol válido. Comunícate con administración.',
    'servidor' => 'No fue posible comprobar los datos en este momento. Inténtalo nuevamente en unos minutos.'
];
$mensajeError = $mensajes[$error] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Acceso seguro al sistema de citas médicas de Clínica Vida Salud.">
    <title>Acceso seguro | Clínica Vida Salud</title>
    <link rel="icon" href="assets/images/isotipo-vida-salud.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/modern.css?v=20261101-medico3">
    <style>
        body.login-page{min-height:100vh;padding:28px;background:radial-gradient(circle at 8% 12%,rgba(239,123,109,.18),transparent 28%),radial-gradient(circle at 94% 86%,rgba(138,115,201,.2),transparent 30%),linear-gradient(135deg,#e9f8f4 0%,#f5f8ff 48%,#fff8f2 100%);display:grid;place-items:center;color:#183c37}
        .login-shell{width:min(1080px,100%);min-height:650px;display:grid;grid-template-columns:1.08fr .92fr;overflow:hidden;border:1px solid rgba(255,255,255,.8);border-radius:30px;background:#fff;box-shadow:0 28px 80px rgba(10,75,69,.18)}
        .login-visual{position:relative;min-height:650px;overflow:hidden;background:#0b514b}
        .login-visual>img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
        .login-visual::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(7,63,59,.08),rgba(7,63,59,.86))}
        .visual-copy{position:absolute;z-index:2;left:42px;right:42px;bottom:42px;color:#fff}
        .visual-badge{display:inline-flex;align-items:center;gap:8px;margin-bottom:18px;padding:8px 12px;border:1px solid rgba(255,255,255,.3);border-radius:999px;background:rgba(255,255,255,.14);font-size:11px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;backdrop-filter:blur(8px)}
        .visual-copy h2{max-width:520px;font-family:Georgia,serif;font-size:42px;font-weight:500;line-height:1.08}
        .visual-copy p{max-width:490px;margin-top:14px;color:rgba(255,255,255,.78);font-size:14px}
        .visual-features{display:flex;flex-wrap:wrap;gap:9px;margin-top:22px}.visual-features span{padding:7px 11px;border-radius:10px;background:rgba(255,255,255,.14);font-size:11px;font-weight:600;backdrop-filter:blur(8px)}
        .login-panel{position:relative;min-width:0;padding:36px 46px 42px;display:flex;flex-direction:column;justify-content:flex-start;background:linear-gradient(180deg,#fff,#fbfdfd)}
        .back-home{align-self:flex-start;display:inline-flex;align-items:center;gap:9px;min-height:38px;margin-bottom:32px;padding:7px 11px;border:1px solid #e3eeeb;border-radius:10px;color:var(--color-primary-dark);font-size:12px;font-weight:700;text-decoration:none}.back-home:hover{color:var(--color-primary-light);background:#f2fbf8}
        .login-brand{display:flex;align-items:center;gap:13px;margin-bottom:30px}.login-logo{width:52px;height:52px;display:grid;place-items:center;color:#fff;border-radius:16px;background:linear-gradient(145deg,#116b63,#2fb7a8);box-shadow:0 10px 25px rgba(23,135,125,.25)}.login-brand strong,.login-brand small{display:block}.login-brand strong{font-size:17px}.login-brand small{color:#71827e;font-size:11px}
        .login-panel h1{font-family:Georgia,serif;font-size:38px;font-weight:500;line-height:1.1;color:#073f3b}.login-intro{margin:10px 0 24px;color:#667a75;font-size:14px}
        .login-alert{display:flex;align-items:flex-start;gap:11px;margin-bottom:20px;padding:13px 14px;border:1px solid #fecaca;border-radius:13px;background:#fff3f1;color:#9f2f2f;font-size:12px;line-height:1.45}.login-alert svg{flex:0 0 auto;margin-top:1px}.attempt-note{display:block;margin-top:5px;font-weight:700}
        .field-group{margin-bottom:16px}.field-label{display:block;margin-bottom:7px;color:#284b46;font-size:12px;font-weight:700}.field-wrap{position:relative}.field-icon{position:absolute;left:14px;top:50%;width:18px;height:18px;transform:translateY(-50%);color:#6b8b85;pointer-events:none}.login-input{width:100%;height:48px;padding:0 46px 0 43px;border:1px solid #d8e7e3;border-radius:13px;background:#f9fcfb;color:#1f3935;font:inherit;font-size:13px;transition:.2s}.login-input:focus{outline:none;border-color:#2fb7a8;background:#fff;box-shadow:0 0 0 4px rgba(47,183,168,.12)}.password-toggle{position:absolute;right:9px;top:50%;width:34px;height:34px;transform:translateY(-50%);border:0;border-radius:9px;background:transparent;color:#58736d;cursor:pointer}.password-toggle:hover{background:#e9f6f2}
        .login-button{width:100%;min-height:50px;margin-top:5px;justify-content:center;border-radius:13px;font-weight:700;box-shadow:0 12px 26px rgba(23,135,125,.22)}.login-button:disabled{cursor:not-allowed;opacity:.6}
        .security-box{display:grid;grid-template-columns:32px 1fr;gap:11px;margin-top:20px;padding:13px;border-radius:13px;background:linear-gradient(135deg,#edf8ff,#f4f0ff);color:#465d69;font-size:11px}.security-icon{display:grid;width:32px;height:32px;place-items:center;color:#fff;border-radius:10px;background:#4d8fd8}.security-box strong{display:block;margin-bottom:2px;color:#294657;font-size:12px}
        .login-rules{margin:16px 0 0;padding-top:15px;border-top:1px solid #edf1f0;color:#768782;font-size:10px;line-height:1.6}.login-rules b{color:#47615b}
        .login-brand{gap:16px;margin-bottom:28px}.login-logo{flex:0 0 56px;width:56px;height:56px}.login-logo .app-icon{width:34px;height:34px}.login-panel h1{margin-bottom:0}.login-intro{margin:14px 0 28px}.field-group{margin-bottom:22px}.field-label{margin-bottom:9px}.security-box{margin-top:28px;gap:14px;padding:16px}.login-rules{margin-top:22px;padding-top:20px;font-size:11px}.login-logo .icon-accent{stroke:#a4f4e2}
        @media(max-width:850px){body.login-page{padding:20px}.login-shell{grid-template-columns:1fr;max-width:560px}.login-visual{min-height:250px}.visual-copy{left:28px;right:28px;bottom:28px}.visual-copy h2{font-size:29px}.visual-copy p,.visual-features{display:none}.login-panel{padding:28px 34px 36px}.back-home{margin-bottom:28px}}
        @media(max-width:480px){body.login-page{padding:0;display:block;background:#fff}.login-shell{min-height:100vh;border:0;border-radius:0;box-shadow:none}.login-visual{min-height:220px}.visual-copy h2{font-size:27px}.login-panel{padding:26px 24px 36px}.login-panel h1{font-size:34px}.login-brand strong{font-size:16px}}
    </style>
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-visual" aria-label="Atención médica profesional">
        <img src="assets/images/servicio-especialidades.png" alt="Equipo de profesionales de Clínica Vida Salud">
        <div class="visual-copy">
            <span class="visual-badge">● Acceso al sistema de citas</span>
            <h2>Tu atención médica, organizada en un solo lugar</h2>
            <p>Accede según tu rol y gestiona la información que necesitas de forma clara, segura y desde cualquier dispositivo.</p>
            <div class="visual-features"><span>Agenda médica</span><span>Gestión de pacientes</span><span>Reservas seguras</span></div>
        </div>
    </section>
    <section class="login-panel">
        <a class="back-home" href="index.php"><?= icono('volver') ?> Volver a la portada</a>
        <div class="login-brand"><img class="login-logo" src="assets/images/isotipo-vida-salud.svg" alt=""><span><strong>Clínica Vida Salud</strong><small>Sistema de gestión de citas médicas</small></span></div>
        <h1>Bienvenido de nuevo</h1>
        <p class="login-intro">Ingresa con el correo o usuario que la clínica habilitó para ti.</p>

        <?php if ($mensajeError): ?>
            <div class="login-alert" role="alert">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div><?= htmlspecialchars($mensajeError) ?>
                    <?php if ($error === 'credenciales' && $restantes > 0): ?><span class="attempt-note">Te quedan <?= $restantes ?> intento<?= $restantes === 1 ? '' : 's' ?> antes de una pausa temporal.</span><?php endif; ?>
                    <?php if ($error === 'bloqueado' && $segundosBloqueo > 0): ?><span class="attempt-note">Tiempo aproximado restante: <?= max(1, (int) ceil($segundosBloqueo / 60)) ?> minuto(s).</span><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <form action="procesar_login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['login_csrf']) ?>">
            <div class="field-group"><label class="field-label" for="usuario">Correo electrónico o usuario</label><div class="field-wrap"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0116 0"/></svg><input id="usuario" class="login-input" type="text" name="usuario" value="<?= htmlspecialchars($usuarioAnterior) ?>" required minlength="3" maxlength="255" autocomplete="username" placeholder="Ej.: usuario@vidasalud.local"></div></div>
            <div class="field-group"><label class="field-label" for="contrasena">Contraseña</label><div class="field-wrap"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg><input id="contrasena" class="login-input" type="password" name="contrasena" required autocomplete="current-password" placeholder="Ingresa tu contraseña"><button class="password-toggle" type="button" aria-label="Mostrar contraseña" aria-pressed="false">◉</button></div></div>
            <button class="btn btn-primary login-button" type="submit" <?= $segundosBloqueo > 0 ? 'disabled' : '' ?>>Ingresar de forma segura <span aria-hidden="true">→</span></button>
        </form>
        <div class="security-box"><span class="security-icon" aria-hidden="true"><?= icono('escudo') ?></span><div><strong>Acceso protegido</strong>Por seguridad, después de 5 intentos incorrectos el acceso se pausa durante 5 minutos.</div></div>
        <p class="login-rules"><b>Importante:</b> utiliza únicamente tu cuenta personal. Si no tienes acceso, tu invitación venció o olvidaste tus datos, comunícate con administración o recepción. No compartas tu contraseña.</p>
    </section>
</main>
<script>
const password=document.getElementById('contrasena'),toggle=document.querySelector('.password-toggle');
toggle.addEventListener('click',()=>{const visible=password.type==='text';password.type=visible?'password':'text';toggle.setAttribute('aria-pressed',String(!visible));toggle.setAttribute('aria-label',visible?'Mostrar contraseña':'Ocultar contraseña');toggle.textContent=visible?'◉':'○'});
</script>
</body>
</html>
