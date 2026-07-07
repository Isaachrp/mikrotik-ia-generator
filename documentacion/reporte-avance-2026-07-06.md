# Reporte de avance - 6 de julio de 2026

## Proyecto

**MikroTik IA Generator**

## Objetivo del trabajo realizado

Durante la jornada se comenzo a redirigir el proyecto hacia un enfoque mas completo: no solo generar configuraciones MikroTik con ayuda de inteligencia artificial, sino tambien identificar primero el dispositivo real y obtener informacion basica de su estado.

Este cambio permite que, en etapas posteriores, la IA pueda proponer acciones con mayor contexto y no trabaje solamente a partir de una solicitud escrita por el usuario.

## Cambio de enfoque

El modulo inicial de IA ya permitia convertir una solicitud en lenguaje natural en parametros para generar scripts. Sin embargo, se identifico que el siguiente paso del proyecto debe ser conocer primero el router al que se desea aplicar una accion.

El flujo esperado empieza a cambiar de:

```text
Usuario escribe solicitud -> IA genera parametros -> Sistema genera script
```

a:

```text
Sistema identifica router -> Lee informacion actual -> Usuario solicita accion -> IA propone configuracion
```

Este nuevo enfoque ayuda a reducir errores porque la accion futura podra basarse en datos reales del equipo.

## Nuevo modulo de diagnostico

Se creo un nuevo modulo dentro de la estructura del proyecto:

```text
modulos/diagnostico
```

El objetivo de este modulo es conectarse a routers MikroTik mediante la API de RouterOS y obtener informacion inicial del dispositivo.

## Integracion de RouterOS API

Se agrego la clase:

```text
RouterOS PHP API class v1.6
```

Ubicada en:

```text
modulos/diagnostico/api/routeros_api.class.php
```

Esta clase permite establecer conexion con el router usando los datos de acceso del usuario.

## Archivos trabajados

### `index.php`

Se construyo la pantalla inicial del modulo de diagnostico.

Actualmente solicita solamente:

- IP del router.
- Usuario.
- Contrasena.

El formulario queda preparado para conectarse por API RouterOS usando el puerto `8728`.

### `conectar.php`

Se implemento la logica inicial de conexion con el router.

Este archivo:

- Recibe los datos enviados desde el formulario.
- Carga la clase `RouterosAPI`.
- Intenta conectar con el router.
- Muestra error si la conexion falla.
- Si la conexion es correcta, consulta informacion basica del dispositivo.
- Guarda los datos iniciales en sesion.
- Redirige a `leer_router.php`.

### `leer_router.php`

Se reemplazo el contenido de prueba por una pantalla funcional de resultado.

Actualmente muestra:

- IP del router.
- Identidad del router.
- Version de RouterOS.
- Modelo o board.
- CPU.
- Datos crudos obtenidos desde el router.

Esta pantalla funciona como primer resultado visible del diagnostico.

### `procesar.php`

El archivo existe dentro del modulo, pero aun queda pendiente definir su funcion final. Se contempla usarlo mas adelante para preparar la informacion del diagnostico y enviarla como contexto al modulo de IA.

## Organizacion del repositorio

Se reorganizo el repositorio de GitHub para que ya no contenga solo el modulo IA en la raiz.

La estructura actual quedo asi:

```text
modulos/
  ia/
  diagnostico/
```

Con esto, el proyecto queda mejor preparado para crecer en modulos separados.

## Repositorio remoto

Los cambios fueron subidos al repositorio:

```text
https://github.com/Isaachrp/mikrotik-ia-generator
```

Commit principal de la reorganizacion:

```text
788d309 Organize IA and diagnostic modules
```

## Limpieza local

Se elimino la carpeta anterior:

```text
C:\laragon\www\scripts\modulos
```

Y se definio continuar trabajando desde:

```text
C:\laragon\www\scripts\mikrotik-modulos
```

Esto permite que el trabajo actual coincida con la estructura versionada en GitHub.

## Validaciones realizadas

Se validaron los archivos PHP principales con `php -l` para confirmar que no tuvieran errores de sintaxis.

Archivos revisados:

- `modulos/diagnostico/index.php`
- `modulos/diagnostico/conectar.php`
- `modulos/diagnostico/leer_router.php`
- `modulos/diagnostico/api/routeros_api.class.php`
- Archivos principales del modulo `ia`

## Estado actual

El proyecto cuenta ahora con dos partes principales:

| Modulo | Estado |
| --- | --- |
| `modulos/ia` | Genera configuraciones MikroTik a partir de lenguaje natural. |
| `modulos/diagnostico` | Inicia conexion con router MikroTik y obtiene informacion basica. |

El modulo de diagnostico todavia esta en una primera etapa, pero ya establece la base para identificar el dispositivo antes de solicitar acciones con IA.

## Pendientes

Los siguientes pasos recomendados son:

1. Completar la lectura del router con mas informacion:
   - Interfaces.
   - Direcciones IP.
   - Rutas.
   - Firewall.
   - NAT.
   - DHCP.
   - Hotspot.
   - Colas simples.

2. Convertir la informacion obtenida en una estructura clara, por ejemplo JSON.

3. Enviar ese contexto al modulo IA.

4. Permitir que el usuario solicite una accion despues del diagnostico.

5. Hacer que la IA proponga una configuracion o script tomando en cuenta el estado real del router.

6. Agregar mejores mensajes de error para problemas de conexion o credenciales incorrectas.

## Conclusion

El trabajo realizado representa el inicio de una segunda etapa del proyecto. El sistema ya no se limita a generar scripts desde un prompt, sino que comienza a prepararse para analizar primero el router real.

Este avance es importante porque permite que, en el futuro, la IA pueda tomar decisiones mejor contextualizadas y generar configuraciones mas adecuadas para cada dispositivo.
