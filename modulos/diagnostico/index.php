<?php

session_start();

if (!isset($_SESSION['codigo'])) {
    header('Location: ../../index.php?error=cod');
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostico MikroTik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container my-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <div class="card shadow">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">

                        <div>
                            <h2 class="mb-1">Diagnostico MikroTik</h2>
                            <p class="text-muted mb-0">
                                Ingresa los datos de acceso para identificar el router.
                            </p>
                        </div>

                        <div class="text-end">
                            <div class="small text-muted">Sesion</div>
                            <strong><?php echo htmlspecialchars($_SESSION['codigo']); ?></strong>
                        </div>

                    </div>

                    <form method="POST" action="conectar.php" autocomplete="off">

                        <?php if (isset($_SESSION['diagnostico_error'])) { ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($_SESSION['diagnostico_error']); ?>
                            </div>
                            <?php unset($_SESSION['diagnostico_error']); ?>
                        <?php } ?>

                        <div class="mb-3">
                            <label class="form-label" for="ip">IP del router</label>
                            <input
                                type="text"
                                class="form-control"
                                id="ip"
                                name="ip"
                                placeholder="Ejemplo: 192.168.88.1"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="usuario">Usuario</label>
                            <input
                                type="text"
                                class="form-control"
                                id="usuario"
                                name="usuario"
                                placeholder="Ejemplo: admin"
                                required
                            >
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password">Contraseña</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                            >
                        </div>

                        <input type="hidden" name="puerto" value="8728">


                        <div class="d-flex justify-content-between gap-2">

                            <a href="../../index.php" class="btn btn-outline-secondary">
                                Volver
                            </a>

                            <button type="submit" class="btn btn-primary">
                                Conectar
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>
