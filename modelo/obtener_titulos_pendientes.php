<?php
/**
 * modelo/obtener_titulos_pendientes.php
 * Devuelve los titulos pendientes de firma digital (JSON).
 *
 * Mejora: CACHE por oficina (TTL) para que la consulta pesada (~20-30s) se ejecute
 * solo la primera vez; las siguientes cargas y recargas son instantaneas.
 * Ademas garantiza JSON limpio (buffer de salida) y usa cache como respaldo ante errores.
 */
ob_start();                       // captura cualquier warning/aviso para no romper el JSON
error_reporting(0);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) session_start();
include("../vista/conexion.php");
include("../control/seguridad.php");

header('Content-Type: application/json; charset=utf-8');

$response = ['exito' => false, 'datos' => [], 'mensaje' => 'Error desconocido'];

// ---- Datos de entrada ----
$input  = json_decode(file_get_contents('php://input'), true);
$accion = isset($input['accion']) ? $input['accion'] : '';
$forzar = !empty($input['forzar']);   // permite refrescar ignorando la cache

// ---- Cache por oficina (SID de la cadena de conexion) ----
$sid = 'ZONAL';
if (!empty($_SESSION['db']) && preg_match('/SID\s*=\s*([A-Za-z0-9]+)/', $_SESSION['db'], $m)) {
    $sid = strtoupper($m[1]);
}
$cacheFile = __DIR__ . '/../data/titpend_' . $sid . '.json';
$ttl = 300;   // 5 minutos

// 1) Servir de cache si esta fresca
if (!$forzar && is_file($cacheFile) && (time() - filemtime($cacheFile) < $ttl)) {
    $cached = @file_get_contents($cacheFile);
    if ($cached !== false && $cached !== '') {
        ob_end_clean();
        echo $cached;
        exit;
    }
}

// 2) Consultar Oracle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'cargar_datos') {
    try {
        $conn = @oci_connect($_SESSION['usuario'], $_SESSION['password'], $_SESSION['db']);
        if (!$conn) {
            $error = oci_error();
            throw new Exception('Error de conexión: ' . $error['message']);
        }

        $param1 = ''; $param2 = ''; $param3 = ''; $vid = 501;
        $cursor = oci_new_cursor($conn);

        $sql = "
        BEGIN
            siar.pkg_plazos_de_atencion.sp_ejecuta_sql_zonal(
                bind_param1 => :bind_param1,
                bind_param2 => :bind_param2,
                bind_param3 => :bind_param3,
                vid => :vid,
                poc_cursor => :poc_cursor
            );
        END;
        ";
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) { $error = oci_error($conn); throw new Exception('Error al parsear SQL: ' . $error['message']); }

        oci_bind_by_name($stmt, ':bind_param1', $param1, -1);
        oci_bind_by_name($stmt, ':bind_param2', $param2, -1);
        oci_bind_by_name($stmt, ':bind_param3', $param3, -1);
        oci_bind_by_name($stmt, ':vid', $vid);
        oci_bind_by_name($stmt, ':poc_cursor', $cursor, -1, OCI_B_CURSOR);

        if (!oci_execute($stmt))   { $error = oci_error($stmt);   throw new Exception('Error al ejecutar procedimiento: ' . $error['message']); }
        if (!oci_execute($cursor)) { $error = oci_error($cursor); throw new Exception('Error al ejecutar cursor: ' . $error['message']); }

        $datos = [];
        while ($row = oci_fetch_assoc($cursor)) { $datos[] = $row; }

        oci_free_statement($stmt);
        oci_free_statement($cursor);
        oci_close($conn);

        $response['exito']   = true;
        $response['datos']   = $datos;
        $response['mensaje'] = empty($datos) ? 'No hay registros disponibles' : 'Datos cargados correctamente';

        // Guardar en cache SOLO si hubo exito
        $json = json_encode($response, JSON_UNESCAPED_UNICODE);
        @file_put_contents($cacheFile, $json, LOCK_EX);
        ob_end_clean();
        echo $json;
        exit;

    } catch (Exception $e) {
        // Respaldo: si hay cache (aunque vieja), servirla en vez de mostrar error
        if (is_file($cacheFile)) {
            $cached = @file_get_contents($cacheFile);
            if ($cached !== false && $cached !== '') { ob_end_clean(); echo $cached; exit; }
        }
        $response['mensaje'] = 'Error: ' . $e->getMessage();
    }
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
