<?php
session_start();
require_once __DIR__.'/includes/icons.php';
$sesionActiva = isset($_SESSION['usuario_id']);
$destinoCuenta = $sesionActiva ? 'panel_moderno.php' : 'login_moderno.php';
$textoCuenta = $sesionActiva ? 'Ir a mi panel' : 'Iniciar sesión';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Clínica Vida Salud: atención médica cercana y sistema web para gestionar citas de forma rápida y segura.">
    <title>Clínica Vida Salud | Citas médicas</title>
    <link rel="icon" href="assets/images/isotipo-vida-salud.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/landing.css?v=20261107-brand">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<header class="site-header" id="inicio">
    <div class="container nav-wrap">
        <a class="brand" href="#inicio" aria-label="Clínica Vida Salud, inicio">
            <img class="brand-logo" src="assets/images/logo-clinica-vida-salud.svg" alt="Clínica Vida Salud, bienestar que te acompaña">
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" aria-label="Abrir menú"><span></span><span></span><span></span></button>
        <nav class="main-nav" id="main-nav" aria-label="Navegación principal">
            <a href="#nosotros">Nosotros</a><a href="#servicios">Servicios</a><a href="#como-funciona">Cómo funciona</a><a href="#contacto">Contacto</a>
            <a class="nav-login" href="<?= htmlspecialchars($destinoCuenta) ?>"><?= htmlspecialchars($textoCuenta) ?></a>
        </nav>
    </div>
</header>
<main id="contenido">
    <section class="hero" aria-labelledby="hero-title">
        <div class="container hero-grid">
            <div class="hero-copy">
                <span class="eyebrow">Atención médica organizada y cercana</span>
                <h1 id="hero-title">Tu salud merece una atención <em>simple y oportuna</em></h1>
                <p>Gestiona tus citas médicas en un solo lugar. Encuentra la especialidad que necesitas, revisa tu información y mantente conectado con tu atención.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="<?= htmlspecialchars($destinoCuenta) ?>"><?= $sesionActiva ? 'Abrir mi panel' : 'Ingresar y reservar cita' ?> <span aria-hidden="true">→</span></a>
                    <a class="button button-ghost" href="#servicios">Conocer servicios</a>
                </div>
                <ul class="trust-list"><li><span>✓</span>Citas organizadas</li><li><span>✓</span>Atención por especialidad</li><li><span>✓</span>Acceso seguro</li></ul>
            </div>
            <div class="hero-visual">
                <img src="assets/images/hero-clinica-vida-salud.png" alt="Doctora conversando cordialmente con una paciente en un consultorio moderno">
                <div class="availability-card"><span class="availability-icon" aria-hidden="true">♥</span><span><strong>Atención confiable</strong><small>Profesionales a tu servicio</small></span></div>
                <div class="appointment-card"><span aria-hidden="true">✓</span><div><strong>Cita gestionada</strong><small>Rápido, claro y desde cualquier dispositivo</small></div></div>
            </div>
        </div>
    </section>
    <section class="quick-strip" aria-label="Características de la clínica"><div class="container quick-grid">
        <div><strong>Atención integral</strong><span>Servicios pensados para cada paciente</span></div>
        <div><strong>Agenda digital</strong><span>Control de citas y horarios médicos</span></div>
        <div><strong>Equipo profesional</strong><span>Atención humana y especializada</span></div>
    </div></section>
    <section class="section about" id="nosotros"><div class="container about-grid">
        <div class="about-showcase"><div class="about-photo"><img src="assets/images/servicio-seguimiento.png" alt="Profesional de salud acompañando a una paciente"><span class="about-seal"><img src="assets/images/isotipo-vida-salud.svg" alt=""><strong>Atención con propósito</strong><small>Salud, cercanía y tecnología</small></span></div><div class="about-stat"><strong>4</strong><span>perfiles conectados<br>en una sola plataforma</span></div></div>
        <div class="about-copy"><div class="section-heading"><span class="eyebrow">Acerca de nosotros</span><h2>Cuidamos de ti en cada etapa</h2></div><p>En Clínica Vida Salud combinamos la atención humana con herramientas digitales que facilitan la organización de consultas. Pacientes, médicos, recepción y administración trabajan de forma coordinada.</p>
            <div class="about-points"><span><b><?= icono('pacientes') ?></b><i><strong>Atención centrada en el paciente</strong><small>Una experiencia clara desde la reserva hasta el seguimiento.</small></i></span><span><b><?= icono('agenda') ?></b><i><strong>Información siempre organizada</strong><small>Agenda, horarios y datos importantes en un mismo lugar.</small></i></span><span><b><?= icono('atendida') ?></b><i><strong>Seguimiento de cada cita</strong><small>Estados, comprobantes e historial para una atención continua.</small></i></span></div>
        </div>
    </div></section>
    <section class="section services" id="servicios"><div class="container">
        <div class="section-heading centered"><span class="eyebrow">Nuestros servicios</span><h2>Atención médica para tus necesidades</h2><p>Una experiencia sencilla para encontrar profesionales y organizar tu atención.</p></div>
        <div class="service-grid">
            <article class="service-card service-coral"><div class="service-media"><img src="assets/images/servicio-consulta.png" alt="Médica atendiendo a un paciente en consulta"><span class="service-icon" aria-hidden="true"><?= icono('medico') ?></span></div><div class="service-content"><span class="service-number">01</span><h3>Consulta médica</h3><p>Programa una cita según la especialidad y los horarios disponibles de cada profesional.</p></div></article>
            <article class="service-card service-blue"><div class="service-media"><img src="assets/images/servicio-especialidades.png" alt="Equipo de profesionales de distintas especialidades médicas"><span class="service-icon" aria-hidden="true"><?= icono('pacientes') ?></span></div><div class="service-content"><span class="service-number">02</span><h3>Especialidades</h3><p>Selecciona el área de atención y visualiza únicamente los médicos correspondientes.</p></div></article>
            <article class="service-card service-gold"><div class="service-media"><img src="assets/images/servicio-agenda.png" alt="Recepcionista organizando una cita médica"><span class="service-icon" aria-hidden="true"><?= icono('agenda') ?></span></div><div class="service-content"><span class="service-number">03</span><h3>Control de citas</h3><p>Consulta, reprograma o cancela citas de acuerdo con los permisos de tu cuenta.</p></div></article>
            <article class="service-card service-lilac"><div class="service-media"><img src="assets/images/servicio-seguimiento.png" alt="Médico revisando el seguimiento de una paciente"><span class="service-icon" aria-hidden="true"><?= icono('atendida') ?></span></div><div class="service-content"><span class="service-number">04</span><h3>Seguimiento</h3><p>Mantén la información y el historial de atención organizados para una mejor gestión.</p></div></article>
        </div>
    </div></section>
    <section class="section steps" id="como-funciona"><div class="container steps-grid">
        <div class="section-heading"><span class="eyebrow light">Tu cita en pocos pasos</span><h2>Un sistema creado para facilitar tu atención</h2><p>Las cuentas son habilitadas de forma segura por la clínica. Una vez activada, cada persona accede a las funciones de su rol.</p><a class="button button-light" href="<?= htmlspecialchars($destinoCuenta) ?>"><?= htmlspecialchars($textoCuenta) ?></a></div>
        <ol class="steps-list"><li><span>1</span><div><strong>Ingresa a tu cuenta</strong><p>Accede con las credenciales habilitadas por la clínica.</p></div></li><li><span>2</span><div><strong>Elige la atención</strong><p>Selecciona especialidad, profesional, fecha y hora disponible.</p></div></li><li><span>3</span><div><strong>Confirma tu cita</strong><p>Revisa la información y gestiona tu reserva desde el sistema.</p></div></li></ol>
    </div></section>
    <section class="section roles"><div class="container">
        <div class="section-heading centered"><span class="eyebrow">Una plataforma, cuatro experiencias</span><h2>Información adecuada para cada usuario</h2></div>
        <div class="role-grid"><article class="role-patient"><div class="role-visual"><span><?= icono('pacientes') ?></span><i></i><i></i></div><div class="role-content"><span class="role-label">Mi atención</span><h3>Paciente</h3><p>Solicita una cita paso a paso, revisa tus reservas y descarga tus comprobantes.</p><small>Reservas · Historial · Perfil</small></div></article><article class="role-doctor"><div class="role-visual"><span><?= icono('medico') ?></span><i></i><i></i></div><div class="role-content"><span class="role-label">Consulta médica</span><h3>Médico</h3><p>Organiza la agenda diaria y consulta la información necesaria de cada paciente.</p><small>Agenda · Pacientes · Atención</small></div></article><article class="role-reception"><div class="role-visual"><span><?= icono('agenda') ?></span><i></i><i></i></div><div class="role-content"><span class="role-label">Coordinación</span><h3>Recepción</h3><p>Gestiona reservas, disponibilidad, pacientes y el registro de pagos.</p><small>Citas · Caja · Disponibilidad</small></div></article><article class="role-admin"><div class="role-visual"><span><?= icono('reportes') ?></span><i></i><i></i></div><div class="role-content"><span class="role-label">Control general</span><h3>Administración</h3><p>Supervisa usuarios, médicos, horarios, reportes y la auditoría del sistema.</p><small>Indicadores · Reportes · Auditoría</small></div></article></div>
    </div></section>
    <section class="contact" id="contacto"><div class="container contact-card"><div><span class="eyebrow light">Estamos para ayudarte</span><h2>Da el primer paso para organizar tu atención</h2><p>Si ya tienes una cuenta, ingresa al sistema. Si todavía no la tienes, solicita en recepción la invitación para activarla.</p></div><a class="button button-light" href="<?= htmlspecialchars($destinoCuenta) ?>"><?= $sesionActiva ? 'Ir a mi panel' : 'Acceder al sistema' ?></a></div></section>
</main>
<footer class="site-footer"><div class="container footer-grid"><div class="brand footer-brand"><img class="footer-logo-mark" src="assets/images/isotipo-vida-salud.svg" alt=""><span><strong>Clínica Vida Salud</strong><small>Sistema web de gestión de citas médicas</small></span></div><p>Proyecto académico · <?= date('Y') ?></p></div></footer>
<script>
const toggle=document.querySelector('.nav-toggle'),nav=document.querySelector('.main-nav');
toggle.addEventListener('click',()=>{const open=toggle.getAttribute('aria-expanded')==='true';toggle.setAttribute('aria-expanded',String(!open));nav.classList.toggle('is-open',!open)});
nav.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>{toggle.setAttribute('aria-expanded','false');nav.classList.remove('is-open')}));
</script>
</body>
</html>
