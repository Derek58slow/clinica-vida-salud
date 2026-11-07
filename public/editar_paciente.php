<?php
require_once __DIR__.'/includes/panel_helpers.php';
exigirRol([1,2]);
header('Location: editar_paciente_moderno.php'.($_GET ? '?'.http_build_query($_GET) : ''));
exit;
