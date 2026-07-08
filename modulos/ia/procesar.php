<?php

session_start();

require_once __DIR__ . '/openai.php';

/*
|--------------------------------------------------------------------------
| VALIDAR SESION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['codigo'])) {

    header('Location: index.php?error=cod');

    exit;
}

/*
|--------------------------------------------------------------------------
| RECIBIR PROMPT
|--------------------------------------------------------------------------
*/

$prompt = $_POST['prompt'] ?? '';
$routerContexto = $_POST['router_contexto'] ?? '';

if ($prompt == '') {

    die("Prompt vacío");
}

if ($routerContexto !== '') {

    $prompt = "CONTEXTO DEL ROUTER LEIDO POR EL MODULO DE DIAGNOSTICO:\n"
        . $routerContexto
        . "\n\nSOLICITUD DEL USUARIO:\n"
        . $prompt
        . "\n\nUsa el contexto para elegir parametros acordes al router. Responde solo con el JSON esperado por la aplicacion.";
}

/*
|--------------------------------------------------------------------------
| CONSULTAR IA
|--------------------------------------------------------------------------
*/

$respuestaIA = consultarIAOR($prompt);

/*
|--------------------------------------------------------------------------
| DECODIFICAR JSON
|--------------------------------------------------------------------------
*/

$data = json_decode($respuestaIA, true);

if (!$data) {

    echo "<h3>Error interpretando respuesta IA</h3>";

    echo "<pre>";
    print_r($respuestaIA);
    echo "</pre>";

    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR ERROR IA
|--------------------------------------------------------------------------
*/

if (isset($data['error'])) {

    echo "<h3>Error IA</h3>";

    echo "<pre>";
    print_r($data);
    echo "</pre>";

    exit;
}

/*
|--------------------------------------------------------------------------
| FUNCIONES DE APOYO
|--------------------------------------------------------------------------
*/

function obtenerUltimoOctetoWan($ip)
{
    $ip = trim((string) $ip);

    if ($ip === '') {
        return '2';
    }

    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $partes = explode('.', $ip);

        return end($partes);
    }

    if (is_numeric($ip)) {
        return $ip;
    }

    return '2';
}

function normalizarVelocidadWan($velocidad)
{
    $velocidad = strtoupper(trim((string) $velocidad));
    $velocidad = str_replace(' ', '', $velocidad);
    $velocidad = str_replace('MB', 'M', $velocidad);
    $velocidad = str_replace('KB', 'K', $velocidad);

    if ($velocidad === '') {
        return '5M';
    }

    if (is_numeric($velocidad)) {
        return $velocidad . 'M';
    }

    return $velocidad;
}

function cargarWansBalanceo($wans, $lineas)
{
    if (!is_array($wans)) {
        return;
    }

    for ($i = 1; $i <= $lineas; $i++) {
        $wan = $wans[$i - 1] ?? [];

        $_POST['ip' . $i] = obtenerUltimoOctetoWan($wan['ip'] ?? '');

        $_POST['gw' . $i] = $wan['gateway'] ?? ('192.168.' . $i . '.1');

        $_POST['vel' . $i] = normalizarVelocidadWan($wan['velocidad'] ?? '5M');
    }
}

function limitarEntero($valor, $minimo, $maximo, $defecto)
{
    $valor = (int) $valor;

    if ($valor < $minimo) {
        return $defecto;
    }

    if ($valor > $maximo) {
        return $maximo;
    }

    return $valor;
}

function normalizarPerfilTicket($perfil)
{
    $perfil = trim((string) $perfil);

    $perfilesValidos = [
        'Plan1Hora',
        'Plan2Horas',
        'Plan4Horas',
        'Plan1Dia',
        'Plan1Semana',
        'Plan15Dias',
        'Plan30Dias',
    ];

    if (in_array($perfil, $perfilesValidos, true)) {
        return $perfil;
    }

    return 'Plan1Hora';
}

/*
|--------------------------------------------------------------------------
| VARIABLES BASE
|--------------------------------------------------------------------------
*/

$_POST['aceptar'] = true;

$_POST['tipo'] = $data['tipo'] ?? 'Hotspot';

$_POST['lan'] = $data['lan'] ?? '172.16.1.1';

$_POST['lineas'] = isset($data['lineas']) ? (int) $data['lineas'] : 1;

/*
|--------------------------------------------------------------------------
| SWITCH POR TIPO
|--------------------------------------------------------------------------
*/

switch ($_POST['tipo']) {

    /*
    |--------------------------------------------------------------------------
    | HOTSPOT
    |--------------------------------------------------------------------------
    */

    case 'Hotspot':

        /*
        |--------------------------------------------------------------------------
        | DHCP
        |--------------------------------------------------------------------------
        */

        if (isset($data['dhcp']) && $data['dhcp']) {

            $_POST['dhcp'] = true;
        }

        /*
        |--------------------------------------------------------------------------
        | PLANES
        |--------------------------------------------------------------------------
        */

        if (isset($data['planes']) && is_array($data['planes'])) {

            $planes = $data['planes'];

            /*
            |--------------------------------------------------------------------------
            | PLAN 1 HORA
            |--------------------------------------------------------------------------
            */

            if (
                isset($planes['Plan1Hora']['activo']) &&
                $planes['Plan1Hora']['activo']
            ) {

                $_POST['Plan1Hora'] = true;

                $_POST['vPlan1Hora'] =
                    $planes['Plan1Hora']['velocidad'] ?? '5M';
            }

            /*
            |--------------------------------------------------------------------------
            | PLAN 2 HORAS
            |--------------------------------------------------------------------------
            */

            if (
                isset($planes['Plan2Horas']['activo']) &&
                $planes['Plan2Horas']['activo']
            ) {

                $_POST['Plan2Horas'] = true;

                $_POST['vPlan2Horas'] =
                    $planes['Plan2Horas']['velocidad'] ?? '5M';
            }

            /*
            |--------------------------------------------------------------------------
            | PLAN 4 HORAS
            |--------------------------------------------------------------------------
            */

            if (
                isset($planes['Plan4Horas']['activo']) &&
                $planes['Plan4Horas']['activo']
            ) {

                $_POST['Plan4Horas'] = true;

                $_POST['vPlan4Horas'] =
                    $planes['Plan4Horas']['velocidad'] ?? '5M';
            }

            /*
            |--------------------------------------------------------------------------
            | PLAN 1 DIA
            |--------------------------------------------------------------------------
            */

            if (
                isset($planes['Plan1Dia']['activo']) &&
                $planes['Plan1Dia']['activo']
            ) {

                $_POST['Plan1Dia'] = true;

                $_POST['vPlan1Dia'] =
                    $planes['Plan1Dia']['velocidad'] ?? '10M';
            }

            /*
            |--------------------------------------------------------------------------
            | PLAN 1 SEMANA
            |--------------------------------------------------------------------------
            */

            if (
                isset($planes['Plan1Semana']['activo']) &&
                $planes['Plan1Semana']['activo']
            ) {

                $_POST['Plan1Semana'] = true;

                $_POST['vPlan1Semana'] =
                    $planes['Plan1Semana']['velocidad'] ?? '10M';
            }

            /*
            |--------------------------------------------------------------------------
            | PLAN 15 DIAS
            |--------------------------------------------------------------------------
            */

            if (
                isset($planes['Plan15Dias']['activo']) &&
                $planes['Plan15Dias']['activo']
            ) {

                $_POST['Plan15Dias'] = true;

                $_POST['vPlan15Dias'] =
                    $planes['Plan15Dias']['velocidad'] ?? '20M';
            }

            /*
            |--------------------------------------------------------------------------
            | PLAN 30 DIAS
            |--------------------------------------------------------------------------
            */

            if (
                isset($planes['Plan30Dias']['activo']) &&
                $planes['Plan30Dias']['activo']
            ) {

                $_POST['Plan30Dias'] = true;

                $_POST['vPlan30Dias'] =
                    $planes['Plan30Dias']['velocidad'] ?? '30M';
            }
        }

    break;

    /*
    |--------------------------------------------------------------------------
    | RESIDENCIALES
    |--------------------------------------------------------------------------
    */

    case 'Residenciales':

        $_POST['lineas'] = 1;

        $_POST['ip_cliente'] =
            $data['ip_cliente'] ?? $data['ip'] ?? '';

        $_POST['vel_subida'] =
            normalizarVelocidadWan($data['vel_subida'] ?? $data['subida'] ?? '1M');

        $_POST['vel_bajada'] =
            normalizarVelocidadWan($data['vel_bajada'] ?? $data['bajada'] ?? '1M');

        $_POST['fecha_inicio'] =
            $data['fecha_inicio'] ?? date('Y-m-d');

        $_POST['fecha_vencimiento'] =
            $data['fecha_vencimiento'] ?? $data['fecha_fin'] ?? date('Y-m-d');

        $_POST['perfil'] =
            normalizarPerfilTicket($data['perfil'] ?? 'Plan1Dia');

        $_POST['contacto'] =
            $data['contacto'] ?? '';

    break;

    /*
    |--------------------------------------------------------------------------
    | TICKETS
    |--------------------------------------------------------------------------
    */

    case 'Tickets':

        $_POST['lineas'] = 1;

        $_POST['numero'] = limitarEntero($data['numero'] ?? 20, 1, 500, 20);

        $_POST['prefijo'] = $data['prefijo'] ?? '';

        $_POST['long_usuario'] =
            limitarEntero($data['long_usuario'] ?? 4, 4, 10, 4);

        $_POST['long_password'] =
            limitarEntero($data['long_password'] ?? 4, 4, 10, 4);

        $_POST['perfil'] =
            normalizarPerfilTicket($data['perfil'] ?? 'Plan1Hora');

        $_POST['titulo'] =
            $data['titulo'] ?? 'Wifi por tiempo';

        $_POST['contacto'] =
            $data['contacto'] ?? '';

        if (
            (isset($data['igual']) && $data['igual']) ||
            (isset($data['usuario_igual_password']) && $data['usuario_igual_password']) ||
            (isset($data['mismo_password']) && $data['mismo_password'])
        ) {

            $_POST['igual'] = true;
        }

    break;

    /*
    |--------------------------------------------------------------------------
    | FIREWALL
    |--------------------------------------------------------------------------
    */

    case 'Firewall':

        $_POST['wan'] = $data['wan'] ?? 'WAN';

        $_POST['lan'] = $data['lan'] ?? '192.168.10.0/24';

        $_POST['puertos_tcp'] = $data['puertos_tcp'] ?? '';

        $_POST['puertos_udp'] = $data['puertos_udp'] ?? '';

    break;

    /*
    |--------------------------------------------------------------------------
    | ECMP
    |--------------------------------------------------------------------------
    */

    case 'ECMP':

        if (
            isset($data['wan']) &&
            is_array($data['wan'])
        ) {

            $_POST['lineas'] = max(2, count($data['wan']));

            cargarWansBalanceo($data['wan'], $_POST['lineas']);
        } else {

            $_POST['lineas'] = max(2, $_POST['lineas']);

            cargarWansBalanceo([], $_POST['lineas']);
        }

        if (isset($data['dhcp']) && $data['dhcp']) {

            $_POST['dhcp'] = true;
        }

    break;

    /*
    |--------------------------------------------------------------------------
    | PCC
    |--------------------------------------------------------------------------
    */

    case 'PCC':

        if (
            isset($data['wan']) &&
            is_array($data['wan'])
        ) {

            $_POST['lineas'] = max(2, count($data['wan']));

            cargarWansBalanceo($data['wan'], $_POST['lineas']);
        } else {

            $_POST['lineas'] = max(2, $_POST['lineas']);

            cargarWansBalanceo([], $_POST['lineas']);
        }

        if (isset($data['dhcp']) && $data['dhcp']) {

            $_POST['dhcp'] = true;
        }

    break;

    /*
    |--------------------------------------------------------------------------
    | VARIANTES DE BALANCEO EXISTENTES EN aplicar.php
    |--------------------------------------------------------------------------
    */

    case 'ECMPr':

    case 'PCCr':

    case 'PCCrV7':

    case 'PBr':

    case 'PBrV7':

    case 'NTH':

    case 'NTHr':

        if (
            isset($data['wan']) &&
            is_array($data['wan'])
        ) {

            $_POST['lineas'] = max(2, count($data['wan']));

            cargarWansBalanceo($data['wan'], $_POST['lineas']);
        } else {

            $_POST['lineas'] = max(2, $_POST['lineas']);

            cargarWansBalanceo([], $_POST['lineas']);
        }

        if (isset($data['dhcp']) && $data['dhcp']) {

            $_POST['dhcp'] = true;
        }

    break;
}

/*
|--------------------------------------------------------------------------
| CAPTURAR SALIDA aplicar.php
|--------------------------------------------------------------------------
*/

ob_start();

$rutasAplicar = [
    __DIR__ . '/../../aplicar.php',
    __DIR__ . '/../../../aplicar.php',
];

$aplicarEncontrado = false;

foreach ($rutasAplicar as $rutaAplicar) {
    if (file_exists($rutaAplicar)) {
        require_once $rutaAplicar;
        $aplicarEncontrado = true;
        break;
    }
}

if (!$aplicarEncontrado) {
    echo '<h3>No se encontro aplicar.php</h3>';
}

$contenido = ob_get_clean();

/*
|--------------------------------------------------------------------------
| MOSTRAR RESULTADO
|--------------------------------------------------------------------------
*/

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Resultado IA MikroTik</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-4 mb-5">

    <div class="card shadow">

        <div class="card-body">

            <h2>Resultado generado por IA</h2>

            <?php if ($routerContexto !== '') { ?>
                <div class="alert alert-info mt-3">
                    La solicitud fue generada usando el contexto leido desde el modulo de diagnostico.
                </div>
            <?php } ?>

            <hr>

            <h4>JSON generado</h4>

            <pre>
<?php print_r($data); ?>
            </pre>

            <hr>

            <h4>Script MikroTik</h4>

            <div class="card p-3 bg-dark text-dark">

                <?php echo $contenido; ?>

            </div>

        </div>

    </div>

</div>

</body>

</html>
