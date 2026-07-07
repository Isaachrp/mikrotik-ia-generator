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

$router = $_SESSION['diagnostico_router'];
$identidad = $router['identidad'][0] ?? [];
$recurso = $router['recurso'][0] ?? [];

function mostrarValor($valor, $defecto = 'No disponible')
{
    $valor = trim((string) $valor);

    return $valor !== '' ? htmlspecialchars($valor) : $defecto;
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

        <div class="col-lg-9">

            <div class="card shadow">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">

                        <div>
                            <h2 class="mb-1">Router identificado</h2>
                            <p class="text-muted mb-0">
                                Se realizo la conexion inicial por API RouterOS.
                            </p>
                        </div>

                        <div class="text-end">
                            <div class="small text-muted">Sesion</div>
                            <strong><?php echo htmlspecialchars($_SESSION['codigo']); ?></strong>
                        </div>

                    </div>

                    <div class="row g-3 mb-4">

                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="small text-muted">IP del router</div>
                                <strong><?php echo mostrarValor($router['ip'] ?? ''); ?></strong>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="small text-muted">Identidad</div>
                                <strong><?php echo mostrarValor($identidad['name'] ?? ''); ?></strong>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="small text-muted">Version RouterOS</div>
                                <strong><?php echo mostrarValor($recurso['version'] ?? ''); ?></strong>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="small text-muted">Modelo / Board</div>
                                <strong><?php echo mostrarValor($recurso['board-name'] ?? ''); ?></strong>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="small text-muted">CPU</div>
                                <strong><?php echo mostrarValor($recurso['cpu'] ?? ''); ?></strong>
                            </div>
                        </div>

                    </div>

                    <h5>Datos crudos obtenidos</h5>

                    <pre class="bg-dark text-light p-3 rounded"><?php
echo htmlspecialchars(print_r([
    'identidad' => $identidad,
    'recurso' => $recurso,
], true));
                    ?></pre>

                    <div class="d-flex justify-content-between gap-2 mt-4">

                        <a href="index.php" class="btn btn-outline-secondary">
                            Probar otro router
                        </a>

                        <a href="../ia/index.php" class="btn btn-primary">
                            Continuar con IA
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>
