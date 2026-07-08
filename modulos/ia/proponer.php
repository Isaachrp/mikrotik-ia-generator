<?php

session_start();

require_once __DIR__ . '/openai.php';

if (!isset($_SESSION['codigo'])) {
    header('Location: ../../index.php?error=cod');
    exit;
}

$solicitud = trim($_POST['prompt'] ?? '');
$contextoRouter = trim($_POST['router_contexto'] ?? '');

if ($solicitud === '') {
    die('Solicitud vacia');
}

if ($contextoRouter === '') {
    die('No se recibio contexto del router.');
}

$respuestaIA = consultarPropuestaRouterIA($contextoRouter, $solicitud);
$propuesta = json_decode($respuestaIA, true);

function textoSeguro($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function renderLista($items)
{
    if (!is_array($items) || empty($items)) {
        echo '<div class="text-muted">Sin datos.</div>';
        return;
    }

    echo '<ul class="mb-0">';

    foreach ($items as $item) {
        echo '<li>' . textoSeguro($item) . '</li>';
    }

    echo '</ul>';
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propuesta IA MikroTik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container my-5">

    <div class="row justify-content-center">

        <div class="col-xl-10">

            <div class="card shadow">

                <div class="card-body p-4">

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">

                        <div>
                            <h2 class="mb-1">Propuesta generada por IA</h2>
                            <p class="text-muted mb-0">
                                Esta propuesta usa el diagnostico real del router como contexto. No se aplico ningun cambio automaticamente.
                            </p>
                        </div>

                        <div class="text-md-end">
                            <div class="small text-muted">Sesion</div>
                            <strong><?php echo textoSeguro($_SESSION['codigo']); ?></strong>
                        </div>

                    </div>

                    <?php if (!$propuesta || isset($propuesta['error'])) { ?>

                        <div class="alert alert-danger">
                            No fue posible interpretar la respuesta de la IA.
                        </div>

                        <pre class="bg-dark text-light p-3 rounded"><?php echo textoSeguro($respuestaIA); ?></pre>

                    <?php } else { ?>

                        <div class="alert alert-warning">
                            Revisa el script antes de aplicarlo en un router real. Este modulo solo propone, no ejecuta cambios.
                        </div>

                        <h5>Solicitud</h5>
                        <p><?php echo textoSeguro($solicitud); ?></p>

                        <h5>Resumen</h5>
                        <p><?php echo textoSeguro($propuesta['resumen'] ?? 'Sin resumen.'); ?></p>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="border rounded bg-white p-3 h-100">
                                    <h6>Tipo de accion</h6>
                                    <div><?php echo textoSeguro($propuesta['tipo_accion'] ?? 'No definido'); ?></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded bg-white p-3 h-100">
                                    <h6>Hallazgos</h6>
                                    <?php renderLista($propuesta['hallazgos'] ?? []); ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded bg-white p-3 h-100">
                                    <h6>Supuestos</h6>
                                    <?php renderLista($propuesta['supuestos'] ?? []); ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded bg-white p-3 h-100">
                                    <h6>Advertencias</h6>
                                    <?php renderLista($propuesta['advertencias'] ?? []); ?>
                                </div>
                            </div>

                        </div>

                        <h5 class="mt-4">Script propuesto</h5>
                        <pre class="bg-dark text-light p-3 rounded"><?php echo textoSeguro($propuesta['script'] ?? ''); ?></pre>

                        <h5>Pasos de validacion</h5>
                        <div class="border rounded bg-white p-3">
                            <?php renderLista($propuesta['pasos_validacion'] ?? []); ?>
                        </div>

                        <details class="mt-4">
                            <summary>Ver JSON completo de la propuesta</summary>
                            <pre class="bg-light border p-3 rounded mt-2"><?php echo textoSeguro(json_encode($propuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                        </details>

                    <?php } ?>

                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mt-4">

                        <a href="../diagnostico/leer_router.php" class="btn btn-outline-secondary">
                            Volver al diagnostico
                        </a>

                        <a href="../diagnostico/index.php" class="btn btn-outline-primary">
                            Diagnosticar otro router
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>
