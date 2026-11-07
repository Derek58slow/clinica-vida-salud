<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: login_moderno.php'); exit; }
$paneles = [1=>'panel_administrador.php',2=>'panel_recepcionista.php',3=>'panel_medico.php',4=>'panel_paciente.php'];
$rolId = (int)($_SESSION['rol_id'] ?? 0);
if (!isset($paneles[$rolId])) { session_unset(); session_destroy(); header('Location: login_moderno.php?error=rol'); exit; }
header('Location: '.$paneles[$rolId]);
exit;
