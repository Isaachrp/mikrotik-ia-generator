<?php

function limpiarRegistrosRouter($registros, $campos)
{
    if (!is_array($registros)) {
        return [];
    }

    $limpios = [];

    foreach ($registros as $registro) {
        if (!is_array($registro)) {
            continue;
        }

        $item = [];

        foreach ($campos as $campo) {
            if (isset($registro[$campo]) && trim((string) $registro[$campo]) !== '') {
                $item[$campo] = $registro[$campo];
            }
        }

        if (!empty($item)) {
            $limpios[] = $item;
        }
    }

    return $limpios;
}

function prepararContextoRouter($router)
{
    $identidad = $router['identidad'][0] ?? [];
    $recurso = $router['recurso'][0] ?? [];

    $contexto = [
        'router' => [
            'ip' => $router['ip'] ?? '',
            'identidad' => $identidad['name'] ?? '',
            'version_routeros' => $recurso['version'] ?? '',
            'modelo' => $recurso['board-name'] ?? '',
            'cpu' => $recurso['cpu'] ?? '',
        ],
        'interfaces' => limpiarRegistrosRouter($router['interfaces'] ?? [], [
            'name',
            'type',
            'mac-address',
            'running',
            'disabled',
            'comment',
        ]),
        'direcciones_ip' => limpiarRegistrosRouter($router['direcciones'] ?? [], [
            'address',
            'network',
            'interface',
            'disabled',
            'comment',
        ]),
        'rutas' => limpiarRegistrosRouter($router['rutas'] ?? [], [
            'dst-address',
            'gateway',
            'distance',
            'routing-table',
            'disabled',
            'comment',
        ]),
        'firewall_filter' => limpiarRegistrosRouter($router['firewall'] ?? [], [
            'chain',
            'action',
            'protocol',
            'src-address',
            'dst-address',
            'in-interface',
            'out-interface',
            'disabled',
            'comment',
        ]),
        'firewall_nat' => limpiarRegistrosRouter($router['nat'] ?? [], [
            'chain',
            'action',
            'src-address',
            'dst-address',
            'in-interface',
            'out-interface',
            'to-addresses',
            'disabled',
            'comment',
        ]),
        'dhcp' => [
            'servidores' => limpiarRegistrosRouter($router['dhcp'] ?? [], [
                'name',
                'interface',
                'address-pool',
                'lease-time',
                'disabled',
                'comment',
            ]),
            'redes' => limpiarRegistrosRouter($router['dhcp_redes'] ?? [], [
                'address',
                'gateway',
                'dns-server',
                'comment',
            ]),
            'pools' => limpiarRegistrosRouter($router['pools'] ?? [], [
                'name',
                'ranges',
                'comment',
            ]),
        ],
        'hotspot' => [
            'servidores' => limpiarRegistrosRouter($router['hotspot'] ?? [], [
                'name',
                'interface',
                'profile',
                'disabled',
                'comment',
            ]),
            'perfiles' => limpiarRegistrosRouter($router['hotspot_perfiles'] ?? [], [
                'name',
                'hotspot-address',
                'dns-name',
                'html-directory',
                'comment',
            ]),
            'usuarios' => limpiarRegistrosRouter($router['hotspot_usuarios'] ?? [], [
                'name',
                'profile',
                'uptime',
                'disabled',
                'comment',
            ]),
        ],
        'colas_simples' => limpiarRegistrosRouter($router['colas_simples'] ?? [], [
            'name',
            'target',
            'max-limit',
            'disabled',
            'comment',
        ]),
    ];

    return json_encode($contexto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
