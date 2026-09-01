<?php
/**
 * Configuracion leida del entorno. Ni las oficinas, ni sus bases de datos, ni
 * las credenciales del usuario de consulta viven en el codigo.
 *
 * En desarrollo se lee de un .env en la raiz; en el servidor conviene definir
 * las variables en la configuracion de Apache/PHP, para que no quede en disco
 * un archivo con datos de conexion.
 */

function qweb_cargar_env()
{
    static $cargado = false;
    if ($cargado) {
        return;
    }
    $cargado = true;

    $ruta = dirname(__DIR__) . '/.env';
    if (!is_readable($ruta)) {
        return;
    }

    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
            continue;
        }
        list($nombre, $valor) = explode('=', $linea, 2);
        $nombre = trim($nombre);
        // El entorno real manda sobre el archivo.
        if (getenv($nombre) === false) {
            putenv($nombre . '=' . trim(trim($valor), "\"'"));
        }
    }
}

function qweb_config($nombre, $defecto = '')
{
    qweb_cargar_env();
    $valor = getenv($nombre);
    return ($valor === false || $valor === '') ? $defecto : $valor;
}

/**
 * Arma el descriptor de conexion de Oracle a partir de HOST:PUERTO/SID.
 * Se define una variable por oficina, p. ej. QWEB_ORACLE_ICA=oracle:1521/OR1.
 */
function qweb_connect_string($oficina)
{
    $destino = qweb_config('QWEB_ORACLE_' . strtoupper($oficina));
    if ($destino === '') {
        return '';
    }

    // host:puerto/servicio
    if (!preg_match('#^([^:/]+):(\d+)/(.+)$#', $destino, $m)) {
        return '';
    }

    return '(DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)'
        . '(Host = ' . $m[1] . ')(Port = ' . $m[2] . '))) '
        . '(CONNECT_DATA = (SID = ' . $m[3] . ')))';
}

/** Oficinas configuradas, en orden. */
function qweb_oficinas_disponibles()
{
    $lista = qweb_config('QWEB_OFICINAS', 'ICA');
    return array_values(array_filter(array_map('trim', explode(',', $lista))));
}
