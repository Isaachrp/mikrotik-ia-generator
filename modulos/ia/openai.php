<?php

function cargarEnvIA()
{
    static $cargado = false;

    if ($cargado) {
        return;
    }

    $archivoEnv = __DIR__ . '/.env';

    if (!file_exists($archivoEnv)) {
        $cargado = true;
        return;
    }

    $lineas = file($archivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        $linea = trim($linea);

        if ($linea === '' || strpos($linea, '#') === 0 || strpos($linea, '=') === false) {
            continue;
        }

        [$clave, $valor] = explode('=', $linea, 2);

        $clave = trim($clave);
        $valor = trim(trim($valor), "\"'");

        if ($clave !== '' && getenv($clave) === false) {
            putenv($clave . '=' . $valor);
            $_ENV[$clave] = $valor;
        }
    }

    $cargado = true;
}

function envIA($clave, $defecto = '')
{
    cargarEnvIA();

    $valor = getenv($clave);

    if ($valor === false || $valor === '') {
        return $defecto;
    }

    return $valor;
}

function consultarIAOR($mensaje)
{
    /*
    |--------------------------------------------------------------------------
    | CONFIGURACION
    |--------------------------------------------------------------------------
    */

    $apiKey = envIA('OPENROUTER_API_KEY');

    $url = envIA('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions');

    $modelo = envIA('OPENROUTER_MODEL', 'openrouter/free');

    $referer = envIA('OPENROUTER_HTTP_REFERER', 'http://localhost');

    $titulo = envIA('OPENROUTER_X_TITLE', 'MikroTik IA');

    if ($apiKey === '') {
        return json_encode([
            "error" => "No se encontro OPENROUTER_API_KEY en modulos/ia/.env"
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PROMPT SYSTEM
    |--------------------------------------------------------------------------
    */

    $system = '
Eres un experto en MikroTik y redes WISP.

Tu tarea es convertir solicitudes en lenguaje natural en JSON valido para una aplicacion PHP que genera scripts MikroTik usando aplicar.php.

REGLAS OBLIGATORIAS:

- Responde SOLO JSON valido.
- No expliques nada.
- No uses markdown.
- No uses bloques ```json.
- No inventes tipos ni campos.
- Usa solo los tipos que el modulo IA puede enviar correctamente a aplicar.php.
- Si faltan datos, usa valores seguros por defecto.

TIPOS PERMITIDOS:

- Hotspot
- Firewall
- Tickets
- Residenciales
- ECMP
- ECMPr
- PCC
- PCCr
- PCCrV7
- PBr
- PBrV7
- NTH
- NTHr

CAMPOS BASE OBLIGATORIOS:

- tipo
- lan
- lineas

REGLAS PARA HOTSPOT:

- Usa tipo "Hotspot".
- lineas debe ser 1.
- lan debe ser una IP tipo 172.16.X.1.
- Puedes incluir dhcp como true o false.
- Puedes incluir planes.
- Planes validos: Plan1Hora, Plan2Horas, Plan4Horas, Plan1Dia, Plan1Semana, Plan15Dias, Plan30Dias.
- Cada plan debe tener activo y velocidad.
- velocidad debe usar formato como "5M", "10M", "30M".

Ejemplo Hotspot:

{
  "tipo":"Hotspot",
  "lan":"172.16.10.1",
  "lineas":1,
  "dhcp":true,
  "planes":{
    "Plan1Hora":{
      "activo":true,
      "velocidad":"5M"
    },
    "Plan1Dia":{
      "activo":true,
      "velocidad":"10M"
    }
  }
}

REGLAS PARA FIREWALL:

- Usa tipo "Firewall".
- wan debe ser el nombre de la interfaz WAN, por ejemplo "ether1" o "WAN".
- lan debe ser una o varias redes LAN en formato CIDR.
- Si hay varias redes LAN, separalas con \n.
- puertos_tcp debe ser una cadena separada por comas, por ejemplo "8291,80,443".
- puertos_udp debe ser una cadena separada por comas, por ejemplo "53".
- lineas debe ser 1.

Ejemplo Firewall:

{
  "tipo":"Firewall",
  "lan":"192.168.10.0/24\n192.168.20.0/24",
  "lineas":1,
  "wan":"ether1",
  "puertos_tcp":"8291,80,443",
  "puertos_udp":"53"
}

REGLAS PARA TICKETS:

- Usa tipo "Tickets" cuando el usuario pida generar usuarios, claves, codigos, vouchers o tickets Hotspot.
- lineas debe ser 1.
- lan puede ser "172.16.1.1" si el usuario no indica LAN, porque aplicar.php no usa lan para Tickets.
- numero debe ser la cantidad de tickets a generar. Si no se indica, usa 20.
- prefijo es opcional. Si no se indica, usa "".
- long_usuario debe estar entre 4 y 10. Si no se indica, usa 4.
- long_password debe estar entre 4 y 10. Si no se indica, usa 4.
- perfil debe ser uno de estos: Plan1Hora, Plan2Horas, Plan4Horas, Plan1Dia, Plan1Semana, Plan15Dias, Plan30Dias.
- Si no se indica perfil, usa Plan1Hora.
- titulo debe ser el texto para imprimir en los tickets. Si no se indica, usa "Wifi por tiempo".
- contacto debe ser el telefono, WhatsApp, correo o texto de contacto del negocio. Si no se indica, usa "".
- Si el usuario pide que usuario y password sean iguales, usa "igual":true.

Ejemplo Tickets:

{
  "tipo":"Tickets",
  "lan":"172.16.1.1",
  "lineas":1,
  "numero":20,
  "prefijo":"WIFI",
  "long_usuario":4,
  "long_password":4,
  "igual":false,
  "perfil":"Plan1Hora",
  "titulo":"Wifi por tiempo",
  "contacto":"722 55 309 14"
}

REGLAS PARA RESIDENCIALES:

- Usa tipo "Residenciales" cuando el usuario pida crear un cliente residencial, plan residencial, cola simple o limitar velocidad a una IP de cliente.
- lineas debe ser 1.
- lan puede ser "172.16.1.1" si el usuario no indica LAN, porque aplicar.php no usa lan para Residenciales.
- ip_cliente debe ser la IP del cliente, por ejemplo "172.16.10.50".
- vel_subida debe ser la velocidad de subida, por ejemplo "1M", "5M", "10M".
- vel_bajada debe ser la velocidad de bajada, por ejemplo "5M", "10M", "20M".
- fecha_inicio debe usar formato YYYY-MM-DD. Si no se indica, usa la fecha actual.
- fecha_vencimiento debe usar formato YYYY-MM-DD. Si no se indica, usa la fecha actual.
- perfil debe ser uno de estos: Plan1Hora, Plan2Horas, Plan4Horas, Plan1Dia, Plan1Semana, Plan15Dias, Plan30Dias.
- Si no se indica perfil, usa Plan1Dia.
- contacto debe ser el telefono, WhatsApp, correo o texto de contacto. Si no se indica, usa "".

Ejemplo Residenciales:

{
  "tipo":"Residenciales",
  "lan":"172.16.1.1",
  "lineas":1,
  "ip_cliente":"172.16.10.50",
  "vel_subida":"5M",
  "vel_bajada":"20M",
  "fecha_inicio":"2026-06-27",
  "fecha_vencimiento":"2026-07-27",
  "perfil":"Plan30Dias",
  "contacto":"722 55 309 14"
}

REGLAS PARA BALANCEO:

- Tipos de balanceo validos: ECMP, ECMPr, PCC, PCCr, PCCrV7, PBr, PBrV7, NTH, NTHr.
- Usa "ECMP" cuando pidan balanceo ECMP, rutas iguales, balanceo simple por gateways o escriban "ecpm".
- Usa "ECMPr" cuando pidan ECMP con DNS recursivo, rutas recursivas o failover recursivo.
- Usa "PCC" cuando pidan balanceo PCC normal.
- Usa "PCCr" cuando pidan PCC recursivo para RouterOS v6.
- Usa "PCCrV7" cuando pidan PCC recursivo para RouterOS v7.
- Usa "PBr" cuando pidan PBR por rangos/listas de clientes para RouterOS v6.
- Usa "PBrV7" cuando pidan PBR por rangos/listas de clientes para RouterOS v7.
- Usa "NTH" cuando pidan balanceo NTH normal.
- Usa "NTHr" cuando pidan NTH recursivo o NTH con DNS recursivo.
- lineas debe ser igual a la cantidad de WANs y minimo 2.
- lan debe ser una IP tipo 172.16.X.1.
- Incluye wan como arreglo.
- Cada WAN debe incluir ip, gateway y velocidad.
- gateway debe ser IP completa, por ejemplo "192.168.1.1".
- ip puede ser la IP completa del MikroTik en esa WAN o solo el ultimo octeto. La aplicacion usara solo el ultimo octeto.
- Si no indican IP WAN del MikroTik, usa "2".
- velocidad debe usar formato de aplicar.php: "512k", "1M", "5M", "10M", "50M", "100M".
- Si no indican velocidad, usa "5M".
- Si piden DHCP para clientes, agrega "dhcp":true.

Ejemplo ECMP:

{
  "tipo":"ECMP",
  "lan":"172.16.10.1",
  "lineas":2,
  "dhcp":true,
  "wan":[
    {
      "ip":"192.168.1.2",
      "gateway":"192.168.1.1",
      "velocidad":"50M"
    },
    {
      "ip":"192.168.2.2",
      "gateway":"192.168.2.1",
      "velocidad":"100M"
    }
  ]
}

Ejemplo ECMPr:

{
  "tipo":"ECMPr",
  "lan":"172.16.20.1",
  "lineas":2,
  "wan":[
    {
      "ip":"2",
      "gateway":"192.168.1.1",
      "velocidad":"20M"
    },
    {
      "ip":"2",
      "gateway":"192.168.2.1",
      "velocidad":"20M"
    }
  ]
}
';

    /*
    |--------------------------------------------------------------------------
    | DATA
    |--------------------------------------------------------------------------
    */

    $data = [

        "model" => $modelo,

        "messages" => [

            [
                "role" => "system",
                "content" => $system
            ],

            [
                "role" => "user",
                "content" => $mensaje
            ]

        ],

        "temperature" => 0.1

    ];

    /*
    |--------------------------------------------------------------------------
    | CURL
    |--------------------------------------------------------------------------
    */

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [

        "Content-Type: application/json",

        "Authorization: Bearer " . $apiKey,

        "HTTP-Referer: " . $referer,

        "X-Title: " . $titulo

    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    /*
    |--------------------------------------------------------------------------
    | ERROR CURL
    |--------------------------------------------------------------------------
    */

    if(curl_errno($ch)){

        return json_encode([
            "error" => curl_error($ch)
        ]);
    }

    curl_close($ch);

    /*
    |--------------------------------------------------------------------------
    | DECODIFICAR
    |--------------------------------------------------------------------------
    */

    $json = json_decode($response, true);

    /*
    |--------------------------------------------------------------------------
    | DEBUG API
    |--------------------------------------------------------------------------
    */

    if(isset($json['error'])){

        return json_encode([
            "error" => $json['error']
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR RESPUESTA
    |--------------------------------------------------------------------------
    */

    if(!isset($json['choices'][0]['message']['content'])){

        return json_encode([
            "error" => "Respuesta inválida API",
            "response" => $json
        ]);
    }

    return trim($json['choices'][0]['message']['content']);
}

function consultarPropuestaRouterIA($contextoRouter, $solicitud)
{
    $apiKey = envIA('OPENROUTER_API_KEY');

    $url = envIA('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions');

    $modelo = envIA('OPENROUTER_MODEL', 'openrouter/free');

    $referer = envIA('OPENROUTER_HTTP_REFERER', 'http://localhost');

    $titulo = envIA('OPENROUTER_X_TITLE', 'MikroTik IA');

    if ($apiKey === '') {
        return json_encode([
            "error" => "No se encontro OPENROUTER_API_KEY en modulos/ia/.env"
        ]);
    }

    $system = '
Eres un experto en MikroTik, RouterOS y redes WISP.

Tu tarea es analizar el estado real de un router MikroTik y proponer una configuracion o script segun la solicitud del usuario.

REGLAS OBLIGATORIAS:

- No ejecutes nada.
- No digas que ya aplicaste cambios.
- No inventes datos que no esten en el contexto.
- Si falta informacion, indicalo en advertencias o supuestos.
- Toma en cuenta interfaces, bridges, IPs, DNS, rutas, tablas de ruteo v7, firewall filter, NAT, mangle, DHCP, Hotspot, colas, servicios, paquetes, PPP secrets y vecinos existentes.
- Evita proponer reglas duplicadas si el contexto muestra algo parecido.
- Para balanceos ECMP, ECMPr, PCC, PCCr, PCCrV7, PBr, PBrV7, NTH y NTHr revisa rutas, DNS, mangle, tablas de ruteo, NAT, gateways, version de RouterOS e interfaces antes de proponer.
- Para Firewall revisa servicios expuestos, reglas filter, NAT, listas o marcas existentes, interfaces WAN/LAN y redes locales.
- Para Hotspot revisa interfaces, bridges, DHCP, pools, perfiles, usuarios, DNS y NAT.
- Para Tickets revisa perfiles Hotspot existentes y advierte si el perfil solicitado no aparece.
- Para Residenciales revisa direcciones IP, DHCP, pools, colas simples existentes y evita duplicar colas para la misma IP.
- Si la accion depende de RouterOS v7, usa tablas de ruteo v7 cuando existan; si el router parece v6, advierte que se requiere adaptar comandos.
- No propongas comandos /system backup ni /export completos en el script, salvo que el usuario los pida explicitamente. Si se requiere respaldo, agregalo como recomendacion en pasos_validacion.
- La salida debe ser SOLO JSON valido.
- No uses markdown.
- No uses bloques ```json.

FORMATO OBLIGATORIO:

{
  "resumen":"explicacion breve de la propuesta",
  "tipo_accion":"categoria de la accion",
  "hallazgos":["dato relevante encontrado en el router"],
  "supuestos":["supuesto usado si falto informacion"],
  "advertencias":["riesgo o punto a revisar antes de aplicar"],
  "script":"script RouterOS propuesto, solo comandos, sin markdown",
  "pasos_validacion":["paso para revisar antes o despues de aplicar"]
}

Si no es seguro proponer script, deja "script" como cadena vacia y explica la razon en advertencias.
';

    $mensaje = "CONTEXTO DEL ROUTER:\n"
        . $contextoRouter
        . "\n\nSOLICITUD DEL USUARIO:\n"
        . $solicitud;

    $data = [
        "model" => $modelo,
        "messages" => [
            [
                "role" => "system",
                "content" => $system
            ],
            [
                "role" => "user",
                "content" => $mensaje
            ]
        ],
        "temperature" => 0.1
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey,
        "HTTP-Referer: " . $referer,
        "X-Title: " . $titulo
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return json_encode([
            "error" => curl_error($ch)
        ]);
    }

    curl_close($ch);

    $json = json_decode($response, true);

    if (isset($json['error'])) {
        return json_encode([
            "error" => $json['error']
        ]);
    }

    if (!isset($json['choices'][0]['message']['content'])) {
        return json_encode([
            "error" => "Respuesta invalida API",
            "response" => $json
        ]);
    }

    return trim($json['choices'][0]['message']['content']);
}
