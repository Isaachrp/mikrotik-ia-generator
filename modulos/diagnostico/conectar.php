<?php

session_start();

if (!isset($_SESSION['codigo'])) {
    header('Location: ../../index.php?error=cod');
    exit;
}

require_once __DIR__ . '/api/routeros_api.class.php';

$ip = trim($_POST['ip'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';
$puerto = (int) ($_POST['puerto'] ?? 8728);

if ($puerto <= 0 || $puerto > 65535) {
    $puerto = 8728;
}

if ($ip === '' || $usuario === '') {
    $_SESSION['diagnostico_error'] = 'Debes indicar la IP del router y el usuario.';
    header('Location: index.php');
    exit;
}

$API = new RouterosAPI();
$API->debug = false;
$API->port = $puerto;
$API->timeout = 5;
$API->attempts = 1;
$API->delay = 1;

$conectado = $API->connect($ip, $usuario, $password);

if (!$conectado) {
    $_SESSION['diagnostico_error'] = 'No fue posible conectar con el router. Verifica IP, usuario, password y que el servicio API este activo en el puerto ' . $puerto . '.';
    header('Location: index.php');
    exit;
}

$identidad = $API->comm('/system/identity/print');
$recurso = $API->comm('/system/resource/print');

$API->disconnect();

$_SESSION['diagnostico_router'] = [
    'ip' => $ip,
    'usuario' => $usuario,
    'password' => $password,
    'puerto' => $puerto,
    'identidad' => $identidad,
    'recurso' => $recurso,
];

header('Location: leer_router.php');
exit;
