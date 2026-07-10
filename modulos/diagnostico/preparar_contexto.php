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
    $dns = $router['dns'][0] ?? [];

    $contexto = [
        'router' => [
            'ip' => $router['ip'] ?? '',
            'identidad' => $identidad['name'] ?? '',
            'version_routeros' => $recurso['version'] ?? '',
            'modelo' => $recurso['board-name'] ?? '',
            'cpu' => $recurso['cpu'] ?? '',
            'arquitectura' => $recurso['architecture-name'] ?? '',
            'uptime' => $recurso['uptime'] ?? '',
        ],
        'paquetes' => limpiarRegistrosRouter($router['paquetes'] ?? [], [
            'name',
            'version',
            'disabled',
        ]),
        'interfaces' => limpiarRegistrosRouter($router['interfaces'] ?? [], [
            'name',
            'type',
            'mac-address',
            'running',
            'disabled',
            'comment',
        ]),
        'bridges' => [
            'interfaces' => limpiarRegistrosRouter($router['bridges'] ?? [], [
                'name',
                'protocol-mode',
                'vlan-filtering',
                'disabled',
                'comment',
            ]),
            'puertos' => limpiarRegistrosRouter($router['bridge_ports'] ?? [], [
                'interface',
                'bridge',
                'pvid',
                'horizon',
                'disabled',
                'comment',
            ]),
        ],
        'direcciones_ip' => limpiarRegistrosRouter($router['direcciones'] ?? [], [
            'address',
            'network',
            'interface',
            'disabled',
            'comment',
        ]),
        'dns' => [
            'servers' => $dns['servers'] ?? '',
            'dynamic_servers' => $dns['dynamic-servers'] ?? '',
            'allow_remote_requests' => $dns['allow-remote-requests'] ?? '',
            'cache_size' => $dns['cache-size'] ?? '',
        ],
        'rutas' => limpiarRegistrosRouter($router['rutas'] ?? [], [
            'dst-address',
            'gateway',
            'distance',
            'routing-table',
            'scope',
            'target-scope',
            'check-gateway',
            'pref-src',
            'disabled',
            'active',
            'comment',
        ]),
        'tablas_ruteo_routeros_v7' => limpiarRegistrosRouter($router['tablas_ruteo'] ?? [], [
            'name',
            'fib',
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
            'src-address-list',
            'dst-address-list',
            'connection-state',
            'connection-nat-state',
            'src-port',
            'dst-port',
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
            'protocol',
            'src-address-list',
            'dst-address-list',
            'src-port',
            'dst-port',
            'to-addresses',
            'to-ports',
            'masquerade',
            'disabled',
            'comment',
        ]),
        'firewall_mangle' => limpiarRegistrosRouter($router['mangle'] ?? [], [
            'chain',
            'action',
            'protocol',
            'src-address',
            'dst-address',
            'in-interface',
            'out-interface',
            'src-address-list',
            'dst-address-list',
            'connection-mark',
            'new-connection-mark',
            'routing-mark',
            'new-routing-mark',
            'per-connection-classifier',
            'nth',
            'passthrough',
            'disabled',
            'comment',
        ]),
        'servicios_router' => limpiarRegistrosRouter($router['servicios'] ?? [], [
            'name',
            'port',
            'address',
            'disabled',
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
            'limit-at',
            'burst-limit',
            'burst-threshold',
            'burst-time',
            'parent',
            'disabled',
            'comment',
        ]),
        'ppp_secrets' => limpiarRegistrosRouter($router['ppp_secrets'] ?? [], [
            'name',
            'service',
            'profile',
            'local-address',
            'remote-address',
            'routes',
            'disabled',
            'comment',
        ]),
        'vecinos' => limpiarRegistrosRouter($router['vecinos'] ?? [], [
            'interface',
            'address',
            'mac-address',
            'identity',
            'platform',
            'version',
        ]),
        'tipos_accion_soportados' => [
            'Hotspot',
            'Firewall',
            'Tickets',
            'Residenciales',
            'ECMP',
            'ECMPr',
            'PCC',
            'PCCr',
            'PCCrV7',
            'PBr',
            'PBrV7',
            'NTH',
            'NTHr',
        ],
    ];

    return json_encode($contexto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
