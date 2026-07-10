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

function consultarRouter($API, $comando)
{
    $respuesta = $API->comm($comando);

    return is_array($respuesta) ? $respuesta : [];
}

$identidad = consultarRouter($API, '/system/identity/print');
$recurso = consultarRouter($API, '/system/resource/print');
$interfaces = consultarRouter($API, '/interface/print');
$direcciones = consultarRouter($API, '/ip/address/print');
$rutas = consultarRouter($API, '/ip/route/print');
$dns = consultarRouter($API, '/ip/dns/print');
$firewall = consultarRouter($API, '/ip/firewall/filter/print');
$nat = consultarRouter($API, '/ip/firewall/nat/print');
$mangle = consultarRouter($API, '/ip/firewall/mangle/print');
$tablasRuteo = consultarRouter($API, '/routing/table/print');
$servicios = consultarRouter($API, '/ip/service/print');
$paquetes = consultarRouter($API, '/system/package/print');
$bridges = consultarRouter($API, '/interface/bridge/print');
$bridgePorts = consultarRouter($API, '/interface/bridge/port/print');
$dhcp = consultarRouter($API, '/ip/dhcp-server/print');
$dhcpRedes = consultarRouter($API, '/ip/dhcp-server/network/print');
$pools = consultarRouter($API, '/ip/pool/print');
$hotspot = consultarRouter($API, '/ip/hotspot/print');
$hotspotPerfiles = consultarRouter($API, '/ip/hotspot/profile/print');
$hotspotUsuarios = consultarRouter($API, '/ip/hotspot/user/print');
$colasSimples = consultarRouter($API, '/queue/simple/print');
$pppSecrets = consultarRouter($API, '/ppp/secret/print');
$vecinos = consultarRouter($API, '/ip/neighbor/print');

$API->disconnect();

$_SESSION['diagnostico_router'] = [
    'ip' => $ip,
    'usuario' => $usuario,
    'puerto' => $puerto,
    'identidad' => $identidad,
    'recurso' => $recurso,
    'interfaces' => $interfaces,
    'direcciones' => $direcciones,
    'rutas' => $rutas,
    'dns' => $dns,
    'firewall' => $firewall,
    'nat' => $nat,
    'mangle' => $mangle,
    'tablas_ruteo' => $tablasRuteo,
    'servicios' => $servicios,
    'paquetes' => $paquetes,
    'bridges' => $bridges,
    'bridge_ports' => $bridgePorts,
    'dhcp' => $dhcp,
    'dhcp_redes' => $dhcpRedes,
    'pools' => $pools,
    'hotspot' => $hotspot,
    'hotspot_perfiles' => $hotspotPerfiles,
    'hotspot_usuarios' => $hotspotUsuarios,
    'colas_simples' => $colasSimples,
    'ppp_secrets' => $pppSecrets,
    'vecinos' => $vecinos,
];

header('Location: leer_router.php');
exit;
