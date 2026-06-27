<?php

session_start();

if (!isset($_SESSION['codigo'])) {
    header('Location: ../index.php?error=cod');
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Generador IA MikroTik</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="row justify-content-center">

        <div class="col-md-10">

            <div class="card shadow">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <h2 class="mb-0">
                            Generador IA MikroTik
                        </h2>

                        <div>
                            Sesión:
                            <strong>
                                <?php echo $_SESSION['codigo']; ?>
                            </strong>
                        </div>

                    </div>

                    <form method="POST" action="procesar.php">

                        <div class="mb-3">

                            <label class="form-label">

                                Describe la configuración que necesitas

                            </label>

                            <textarea
                                name="prompt"
                                class="form-control"
                                rows="8"
                                placeholder="Ejemplo:
Quiero un hotspot para la red 172.16.10.1

O:

Necesito PCC con 2 WAN para balanceo de carga"
                                required
                            ></textarea>

                        </div>

                        <button type="submit" class="btn btn-primary">

                            Generar Configuración

                        </button>

                    </form>

                    <hr class="mt-4">

                    <h5>Ejemplos de prompts</h5>

                    <ul>

                        <li>
                            Hotspot para red 172.16.10.1
                        </li>

                        <li>
                            PCC con 2 ISP para balanceo
                        </li>

                        <li>
                            ECMP con 3 enlaces de internet
                        </li>

                        <li>
                            Firewall básico para oficina
                        </li>

                    </ul>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>