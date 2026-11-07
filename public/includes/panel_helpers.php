<?php
require_once __DIR__ . '/../../src/Services/clinica.php';
function exigirRol(array $roles): int {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!isset($_SESSION['usuario_id'])) { header('Location: login_moderno.php'); exit; }
    $rolId=(int)($_SESSION['rol_id']??0);
    if(!in_array($rolId,$roles,true)){http_response_code(403);exit('No tienes permiso para acceder a esta vista.');}
    return $rolId;
}
function estadoBadge(string $estado): string { return match($estado){'confirmada'=>'success','solicitada'=>'warning','completada'=>'info','cancelada','no_asistio'=>'danger',default=>'secondary'}; }
function nombreEstado(string $estado): string { return ucfirst(str_replace('_',' ',$estado)); }

function obtenerMedicoSesion(PDO $pdo): ?array {
    $stmt = $pdo->prepare(
        "SELECT m.*,
                COALESCE(string_agg(DISTINCT e.nombre, ', ' ORDER BY e.nombre), '') AS especialidades
         FROM medicos m
         LEFT JOIN medicos_especialidades me ON me.medico_id = m.id
         LEFT JOIN especialidades e ON e.id = me.especialidad_id AND e.activo = TRUE
         WHERE m.usuario_id = :usuario_id AND m.activo = TRUE
         GROUP BY m.id"
    );
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    return $stmt->fetch() ?: null;
}

function exigirMedicoActivo(PDO $pdo): array {
    $medico = obtenerMedicoSesion($pdo);
    if (!$medico) {
        http_response_code(403);
        exit('Tu cuenta no tiene un perfil médico activo. Solicita la vinculación a administración.');
    }
    return $medico;
}

function obtenerPacienteSesion(PDO $pdo): ?array {
    $stmt = $pdo->prepare('SELECT * FROM pacientes WHERE usuario_id = :usuario_id AND activo = TRUE');
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    return $stmt->fetch() ?: null;
}

function exigirPacienteActivo(PDO $pdo): array {
    $paciente = obtenerPacienteSesion($pdo);
    if (!$paciente) {
        http_response_code(403);
        exit('Tu cuenta no tiene una ficha de paciente activa. Solicita la vinculación a administración.');
    }
    return $paciente;
}

function contextoAgenda(array $datos): string {
    $fecha = is_string($datos['fecha'] ?? null) && fechaClinicaValida($datos['fecha']) ? $datos['fecha'] : date('Y-m-d');
    $parametros = ['fecha' => $fecha];
    if (in_array($datos['estado'] ?? '', ['solicitada','confirmada','completada','cancelada','no_asistio'], true)) $parametros['estado'] = $datos['estado'];
    foreach (['medico_id','especialidad_id'] as $campo) {
        $valor = filter_var($datos[$campo] ?? null, FILTER_VALIDATE_INT);
        if ($valor > 0) $parametros[$campo] = $valor;
    }
    return http_build_query($parametros);
}
