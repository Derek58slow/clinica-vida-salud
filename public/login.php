<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: panel_moderno.php');
    exit;
}

header('Location: login_moderno.php');
exit;
