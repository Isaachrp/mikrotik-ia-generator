<?php

session_start();

if (!isset($_SESSION['codigo'])) {
    header('Location: ../../index.php?error=cod');
    exit;
}

if (!isset($_SESSION['diagnostico_router'])) {
    $_SESSION['diagnostico_error'] = 'Primero debes conectar con un router.';
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/preparar_contexto.php';

$router = $_SESSION['diagnostico_router'];
$identidad = $router['identidad'][0] ?? [];
$recurso = $router['recurso'][0] ?? [];
$contextoIA = prepararContextoRouter($router);

function mostrarValor($valor, $defecto = 'No disponible')
{
    $valor = trim((string) $valor);

    return $valor !== '' ? htmlspecialchars($valor) : $defecto;
}

function contarRegistros($datos)
{
    return is_array($datos) ? count($datos) : 0;
}

function valorRegistro($registro, $campo)
{
    return mostrarValor($registro[$campo] ?? '');
}

function renderTabla($id, $titulo, $datos, $columnas)
{
    ?>
    <div class="accordion-item">
        <h2 class="accordion-header" id="heading-<?php echo htmlspecialchars($id); ?>">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo htmlspecialchars($id); ?>">
                <?php echo htmlspecialchars($titulo); ?> (<?php echo contarRegistros($datos); ?>)
            </button>
        </h2>

        <div id="collapse-<?php echo htmlspecialchars($id); ?>" class="accordion-collapse collapse" data-bs-parent="#diagnosticoAccordion">
            <div class="accordion-body">
                <?php if (empty($datos)) { ?>
                    <div class="text-muted">No se encontraron registros.</div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <?php foreach ($columnas as $campo => $etiqueta) { ?>
                                        <th><?php echo htmlspecialchars($etiqueta); ?></th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $registro) { ?>
                                    <tr>
                                        <?php foreach ($columnas as $campo => $etiqueta) { ?>
                                            <td><?php echo valorRegistro($registro, $campo); ?></td>
                                        <?php } ?>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php
}

$resumen = [
    'Interfaces' => contarRegistros($router['interfaces'] ?? []),
    'Direcciones IP' => contarRegistros($router['direcciones'] ?? []),
    'Rutas' => contarRegistros($router['rutas'] ?? []),
    'Firewall' => contarRegistros($router['firewall'] ?? []),
    'NAT' => contarRegistros($router['nat'] ?? []),
    'DHCP' => contarRegistros($router['dhcp'] ?? []),
    'Hotspot' => contarRegistros($router['hotspot'] ?? []),
    'Colas simples' => contarRegistros($router['colas_simples'] ?? []),
];

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado Diagnostico MikroTik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container my-5">

    <div class="row justify-content-center">

        <div class="col-xl-11">

            <div class="card shadow">

                <div class="card-body p-4">

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">

                        <div>
                            <h2 class="mb-1">Diagnostico del router</h2>
                            <p class="text-muted mb-0">
                                Informacion leida desde RouterOS por medio de la API.
                            </p>
                        </div>

                        <div class="text-md-end">
                            <div class="small text-muted">Sesion</div>
                            <strong><?php echo htmlspecialchars($_SESSION['codigo']); ?></strong>
                        </div>

                    </div>

                    <div class="row g-3 mb-4">

                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="small text-muted">IP del router</div>
                                <strong><?php echo mostrarValor($router['ip'] ?? ''); ?></strong>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="small text-muted">Identidad</div>
                                <strong><?php echo mostrarValor($identidad['name'] ?? ''); ?></strong>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="small text-muted">Version RouterOS</div>
                                <strong><?php echo mostrarValor($recurso['version'] ?? ''); ?></strong>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="small text-muted">Modelo / Board</div>
                                <strong><?php echo mostrarValor($recurso['board-name'] ?? ''); ?></strong>
                            </div>
                        </div>

                    </div>

                    <h5 class="mb-3">Resumen de lectura</h5>

                    <div class="row g-3 mb-4">
                        <?php foreach ($resumen as $titulo => $total) { ?>
                            <div class="col-6 col-md-3">
                                <div class="border rounded p-3 bg-white text-center h-100">
                                    <div class="h4 mb-0"><?php echo (int) $total; ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($titulo); ?></div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <h5 class="mb-3">Detalle por seccion</h5>

                    <div class="accordion" id="diagnosticoAccordion">

                        <?php
                        renderTabla('interfaces', 'Interfaces', $router['interfaces'] ?? [], [
                            'name' => 'Nombre',
                            'type' => 'Tipo',
                            'mac-address' => 'MAC',
                            'running' => 'Running',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('direcciones', 'Direcciones IP', $router['direcciones'] ?? [], [
                            'address' => 'Direccion',
                            'network' => 'Red',
                            'interface' => 'Interfaz',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('rutas', 'Rutas', $router['rutas'] ?? [], [
                            'dst-address' => 'Destino',
                            'gateway' => 'Gateway',
                            'distance' => 'Distancia',
                            'routing-table' => 'Tabla',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('firewall', 'Firewall filter', $router['firewall'] ?? [], [
                            'chain' => 'Chain',
                            'action' => 'Accion',
                            'protocol' => 'Protocolo',
                            'src-address' => 'Origen',
                            'dst-address' => 'Destino',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('nat', 'Firewall NAT', $router['nat'] ?? [], [
                            'chain' => 'Chain',
                            'action' => 'Accion',
                            'src-address' => 'Origen',
                            'dst-address' => 'Destino',
                            'out-interface' => 'Salida',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('dhcp', 'DHCP servers', $router['dhcp'] ?? [], [
                            'name' => 'Nombre',
                            'interface' => 'Interfaz',
                            'address-pool' => 'Pool',
                            'lease-time' => 'Lease',
                            'disabled' => 'Deshabilitado',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('dhcp-redes', 'DHCP networks', $router['dhcp_redes'] ?? [], [
                            'address' => 'Red',
                            'gateway' => 'Gateway',
                            'dns-server' => 'DNS',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('pools', 'Pools IP', $router['pools'] ?? [], [
                            'name' => 'Nombre',
                            'ranges' => 'Rangos',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('hotspot', 'Hotspot', $router['hotspot'] ?? [], [
                            'name' => 'Nombre',
                            'interface' => 'Interfaz',
                            'profile' => 'Perfil',
                            'disabled' => 'Deshabilitado',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('hotspot-perfiles', 'Hotspot profiles', $router['hotspot_perfiles'] ?? [], [
                            'name' => 'Nombre',
                            'hotspot-address' => 'Direccion',
                            'dns-name' => 'DNS name',
                            'html-directory' => 'HTML',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('hotspot-usuarios', 'Hotspot users', $router['hotspot_usuarios'] ?? [], [
                            'name' => 'Usuario',
                            'profile' => 'Perfil',
                            'uptime' => 'Uso',
                            'disabled' => 'Deshabilitado',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('colas', 'Colas simples', $router['colas_simples'] ?? [], [
                            'name' => 'Nombre',
                            'target' => 'Target',
                            'max-limit' => 'Max limit',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ]);
                        ?>

                    </div>

                    <h5 class="mt-4">Datos preparados para IA</h5>

                    <pre class="bg-dark text-light p-3 rounded small"><?php
echo htmlspecialchars($contextoIA);
                    ?></pre>

                    <div class="border rounded p-3 bg-white mt-4">

                        <h5>Solicitar accion con IA</h5>

                        <p class="text-muted">
                            La IA recibira el diagnostico anterior como contexto para generar una propuesta acorde a este router.
                        </p>

                        <form method="POST" action="../ia/proponer.php">

                            <input type="hidden" name="router_contexto" value="<?php echo htmlspecialchars($contextoIA, ENT_QUOTES, 'UTF-8'); ?>">

                            <div class="mb-3">
                                <label class="form-label" for="prompt">Que accion quieres realizar?</label>
                                <textarea
                                    class="form-control"
                                    id="prompt"
                                    name="prompt"
                                    rows="4"
                                    placeholder="Ejemplo: Crea un cliente residencial para la IP 172.16.10.50 con 5M de subida y 20M de bajada."
                                    required
                                ></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Generar propuesta con IA
                            </button>

                        </form>

                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mt-4">

                        <a href="index.php" class="btn btn-outline-secondary">
                            Probar otro router
                        </a>

                        <a href="../ia/index.php" class="btn btn-outline-primary">
                            Ir al generador IA sin contexto
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
