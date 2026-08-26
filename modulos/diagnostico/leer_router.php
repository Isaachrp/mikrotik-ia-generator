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
$validacionesRouter = obtenerValidacionesRouter($router, $identidad, $recurso);
$validacionesSeccion = validacionesPorSeccion($validacionesRouter);

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

function tieneValor($valor)
{
    return trim((string) $valor) !== '';
}

function obtenerValidacionesRouter($router, $identidad, $recurso)
{
    $dns = $router['dns'][0] ?? [];
    $advertencias = [];

    if (!tieneValor($identidad['name'] ?? '')) {
        $advertencias[] = [
            'nivel' => 'warning',
            'seccion' => 'Identidad',
            'mensaje' => 'No se pudo leer la identidad del router.',
        ];
    }

    if (!tieneValor($recurso['version'] ?? '')) {
        $advertencias[] = [
            'nivel' => 'warning',
            'seccion' => 'RouterOS',
            'mensaje' => 'No se pudo detectar la version de RouterOS.',
        ];
    }

    if (contarRegistros($router['interfaces'] ?? []) === 0) {
        $advertencias[] = [
            'nivel' => 'danger',
            'seccion' => 'Interfaces',
            'mensaje' => 'No se encontraron interfaces. La IA no podra identificar WAN, LAN o bridges con seguridad.',
        ];
    }

    if (contarRegistros($router['direcciones'] ?? []) === 0) {
        $advertencias[] = [
            'nivel' => 'danger',
            'seccion' => 'Direcciones IP',
            'mensaje' => 'No se encontraron direcciones IP. Las propuestas para LAN, clientes, Hotspot o rutas pueden quedar incompletas.',
        ];
    }

    if (contarRegistros($router['rutas'] ?? []) === 0) {
        $advertencias[] = [
            'nivel' => 'warning',
            'seccion' => 'Rutas',
            'mensaje' => 'No se encontraron rutas. Para balanceos ECMP, PCC, PBR o NTH se necesita revisar gateways y rutas existentes.',
        ];
    }

    if (empty($dns) || (!tieneValor($dns['servers'] ?? '') && !tieneValor($dns['dynamic-servers'] ?? ''))) {
        $advertencias[] = [
            'nivel' => 'warning',
            'seccion' => 'DNS',
            'mensaje' => 'No se detectaron servidores DNS. Esto es importante para configuraciones recursivas y Hotspot.',
        ];
    }

    if (contarRegistros($router['nat'] ?? []) === 0) {
        $advertencias[] = [
            'nivel' => 'warning',
            'seccion' => 'NAT',
            'mensaje' => 'No se encontraron reglas NAT. Es posible que falte masquerade para salida a Internet.',
        ];
    }

    if (contarRegistros($router['servicios'] ?? []) === 0) {
        $advertencias[] = [
            'nivel' => 'info',
            'seccion' => 'Servicios',
            'mensaje' => 'No se pudo leer la lista de servicios del router. Esto limita la revision de accesos administrativos.',
        ];
    }

    return $advertencias;
}

function validacionesPorSeccion($validaciones)
{
    $porSeccion = [];

    foreach ($validaciones as $validacion) {
        $porSeccion[$validacion['seccion']][] = $validacion;
    }

    return $porSeccion;
}

function renderAlertasValidacion($validaciones)
{
    if (empty($validaciones)) {
        ?>
        <div class="alert alert-success mb-4">
            No se detectaron faltantes importantes en la lectura inicial del router.
        </div>
        <?php
        return;
    }

    ?>
    <div class="alert alert-warning mb-4">
        <h6 class="alert-heading">Informacion importante pendiente de revisar</h6>
        <p class="mb-2">
            El diagnostico se completo, pero hay datos que pueden afectar la precision de las propuestas generadas por IA.
        </p>
        <ul class="mb-0">
            <?php foreach ($validaciones as $validacion) { ?>
                <li>
                    <strong><?php echo htmlspecialchars($validacion['seccion']); ?>:</strong>
                    <?php echo htmlspecialchars($validacion['mensaje']); ?>
                </li>
            <?php } ?>
        </ul>
    </div>
    <?php
}

function tieneValidacionCritica($validaciones)
{
    foreach ($validaciones as $validacion) {
        if (($validacion['nivel'] ?? '') === 'danger') {
            return true;
        }
    }

    return false;
}

function renderTabla($id, $titulo, $datos, $columnas, $validaciones = [])
{
    $nivel = $validaciones[0]['nivel'] ?? '';
    $claseBorde = $nivel === 'danger' ? 'border-danger' : ($nivel === 'warning' ? 'border-warning' : '');
    $badge = $nivel === 'danger' ? 'Falta dato critico' : ($nivel === 'warning' ? 'Revisar' : ($nivel === 'info' ? 'Aviso' : ''));
    $badgeClase = $nivel === 'danger' ? 'text-bg-danger' : ($nivel === 'warning' ? 'text-bg-warning' : 'text-bg-info');

    ?>
    <div class="col-6 col-md-3">
        <button
            class="border rounded p-3 bg-white text-center h-100 w-100 btn btn-link text-decoration-none text-dark <?php echo htmlspecialchars($claseBorde); ?>"
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#modal-<?php echo htmlspecialchars($id); ?>"
        >
            <div class="h4 mb-0"><?php echo contarRegistros($datos); ?></div>
            <div class="small text-muted"><?php echo htmlspecialchars($titulo); ?></div>
            <?php if ($badge !== '') { ?>
                <span class="badge <?php echo htmlspecialchars($badgeClase); ?> mt-2"><?php echo htmlspecialchars($badge); ?></span>
            <?php } ?>
        </button>
    </div>

    <div class="modal fade" id="modal-<?php echo htmlspecialchars($id); ?>" tabindex="-1" aria-labelledby="modal-title-<?php echo htmlspecialchars($id); ?>" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="modal-title-<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($titulo); ?></h5>
                        <div class="small text-muted"><?php echo contarRegistros($datos); ?> registros encontrados</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <?php if (!empty($validaciones)) { ?>
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">Puntos a revisar</h6>
                            <ul class="mb-0">
                                <?php foreach ($validaciones as $validacion) { ?>
                                    <li><?php echo htmlspecialchars($validacion['mensaje']); ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                    <?php } ?>

                    <?php if (empty($datos)) { ?>
                        <div class="text-muted">No se encontraron registros.</div>
                    <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
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

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

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

                    <?php renderAlertasValidacion($validacionesRouter); ?>

                    <h5 class="mb-3">Detalle por seccion</h5>

                    <div class="row g-3" id="detalleSecciones">

                        <?php
                        renderTabla('interfaces', 'Interfaces', $router['interfaces'] ?? [], [
                            'name' => 'Nombre',
                            'type' => 'Tipo',
                            'mac-address' => 'MAC',
                            'running' => 'Running',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ], $validacionesSeccion['Interfaces'] ?? []);

                        renderTabla('direcciones', 'Direcciones IP', $router['direcciones'] ?? [], [
                            'address' => 'Direccion',
                            'network' => 'Red',
                            'interface' => 'Interfaz',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ], $validacionesSeccion['Direcciones IP'] ?? []);

                        renderTabla('rutas', 'Rutas', $router['rutas'] ?? [], [
                            'dst-address' => 'Destino',
                            'gateway' => 'Gateway',
                            'distance' => 'Distancia',
                            'routing-table' => 'Tabla',
                            'scope' => 'Scope',
                            'target-scope' => 'Target scope',
                            'check-gateway' => 'Check',
                            'active' => 'Activa',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ], $validacionesSeccion['Rutas'] ?? []);

                        renderTabla('tablas-ruteo', 'Tablas de ruteo RouterOS v7', $router['tablas_ruteo'] ?? [], [
                            'name' => 'Nombre',
                            'fib' => 'FIB',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('dns', 'DNS', $router['dns'] ?? [], [
                            'servers' => 'Servidores',
                            'dynamic-servers' => 'Dinamicos',
                            'allow-remote-requests' => 'Remote requests',
                            'cache-size' => 'Cache',
                        ], $validacionesSeccion['DNS'] ?? []);

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
                            'protocol' => 'Protocolo',
                            'src-address' => 'Origen',
                            'dst-address' => 'Destino',
                            'in-interface' => 'Entrada',
                            'out-interface' => 'Salida',
                            'to-addresses' => 'To addresses',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ], $validacionesSeccion['NAT'] ?? []);

                        renderTabla('mangle', 'Firewall mangle', $router['mangle'] ?? [], [
                            'chain' => 'Chain',
                            'action' => 'Accion',
                            'in-interface' => 'Entrada',
                            'out-interface' => 'Salida',
                            'connection-mark' => 'Conn mark',
                            'new-connection-mark' => 'New conn mark',
                            'routing-mark' => 'Routing mark',
                            'new-routing-mark' => 'New routing mark',
                            'per-connection-classifier' => 'PCC',
                            'nth' => 'NTH',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('servicios', 'Servicios del router', $router['servicios'] ?? [], [
                            'name' => 'Nombre',
                            'port' => 'Puerto',
                            'address' => 'Address',
                            'disabled' => 'Deshabilitado',
                        ], $validacionesSeccion['Servicios'] ?? []);

                        renderTabla('paquetes', 'Paquetes RouterOS', $router['paquetes'] ?? [], [
                            'name' => 'Nombre',
                            'version' => 'Version',
                            'disabled' => 'Deshabilitado',
                        ]);

                        renderTabla('bridges', 'Bridge interfaces', $router['bridges'] ?? [], [
                            'name' => 'Nombre',
                            'protocol-mode' => 'Protocolo',
                            'vlan-filtering' => 'VLAN filtering',
                            'disabled' => 'Deshabilitado',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('bridge-ports', 'Bridge ports', $router['bridge_ports'] ?? [], [
                            'interface' => 'Interfaz',
                            'bridge' => 'Bridge',
                            'pvid' => 'PVID',
                            'disabled' => 'Deshabilitado',
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
                            'limit-at' => 'Limit at',
                            'parent' => 'Parent',
                            'disabled' => 'Deshabilitada',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('ppp-secrets', 'PPP secrets', $router['ppp_secrets'] ?? [], [
                            'name' => 'Nombre',
                            'service' => 'Servicio',
                            'profile' => 'Perfil',
                            'local-address' => 'Local',
                            'remote-address' => 'Remota',
                            'disabled' => 'Deshabilitado',
                            'comment' => 'Comentario',
                        ]);

                        renderTabla('vecinos', 'Vecinos IP', $router['vecinos'] ?? [], [
                            'interface' => 'Interfaz',
                            'address' => 'Address',
                            'mac-address' => 'MAC',
                            'identity' => 'Identidad',
                            'platform' => 'Plataforma',
                            'version' => 'Version',
                        ]);
                        ?>

                    </div>

                    <div class="border rounded p-3 bg-white mt-4">

                        <h5>Solicitar accion con IA</h5>

                        <p class="text-muted">
                            La IA recibira el diagnostico anterior como contexto para generar una propuesta acorde a este router.
                        </p>

                        <?php if (tieneValidacionCritica($validacionesRouter)) { ?>
                            <div class="alert alert-danger">
                                Faltan datos criticos del router. Puedes generar una propuesta, pero revisa las advertencias porque el resultado podria requerir ajustes manuales.
                            </div>
                        <?php } ?>

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
