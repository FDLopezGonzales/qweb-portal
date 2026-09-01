<?php
require_once __DIR__ . '/../control/config_env.php';

/**
 * Conexion a la base de la oficina indicada, con el usuario de consulta de solo
 * lectura. La identidad de la persona ya se verifico en validar_usuario.php
 * conectando con SU cuenta de Oracle; este usuario solo sirve para leer los
 * datos registrales que alimentan los reportes.
 */
function conectarBaseDeDatos($oficina)
{
    $connStr = qweb_connect_string($oficina);
    if ($connStr === '') {
        error_log('QWEB: oficina no configurada: ' . $oficina);
        throw new RuntimeException('Oficina no valida.');
    }

    $usuario = qweb_config('QWEB_DB_USER');
    $clave   = qweb_config('QWEB_DB_PASSWORD');
    if ($usuario === '' || $clave === '') {
        error_log('QWEB: faltan QWEB_DB_USER o QWEB_DB_PASSWORD');
        throw new RuntimeException('Servicio no disponible.');
    }

    $conn = @oci_connect($usuario, $clave, $connStr, 'AL32UTF8');
    if (!$conn) {
        // El detalle de Oracle va al log, no a la pantalla: el mensaje crudo
        // revela nombres de servicio, rutas y version de la base.
        $e = oci_error();
        error_log('QWEB: fallo de conexion a ' . $oficina . ': ' . ($e['message'] ?? ''));
        throw new RuntimeException('No se pudo conectar a la base de datos.');
    }

    return $conn;
}
