<?php

session_start();
$cuenta = $_POST["usuario"];
$pwd = $_POST["password"];
$db = $_POST["db"];

// La clave se guarda en la sesion porque cada peticion vuelve a conectarse a
// Oracle con la cuenta de la propia persona (ver README, "Alcance y
// limitaciones"): sin un pool de conexiones no hay donde mantenerla viva.
$_SESSION['usuario'] = $cuenta;
$_SESSION['password'] = $pwd;
$_SESSION['db'] = $db;

// Intentar la conexión a la base de datos Oracle
$conn = @oci_connect($_SESSION['usuario'], $_SESSION['password'], $_SESSION['db'], 'AL32UTF8');

// Comprobar si la conexión falló
if (!$conn) {
    // Si la conexión falla, obtener el mensaje de error correctamente
    $error = oci_error();

    // Si el error es de cuenta bloqueada (ORA-28000)
    if (strpos($error['message'], 'ORA-28000') !== false) {
        $error_message = "La cuenta está bloqueada. Contacta al administrador.";
    } 
    // Si el error es por credenciales incorrectas (ORA-01017)
    elseif (strpos($error['message'], 'ORA-01017') !== false) {
        $error_message = "Usuario o contraseña incorrectos.";
    } 
    // Otro tipo de error
    else {
        $error_message = $error['message'];
    }

    // Redirigir a login con el mensaje de error
    header("Location: ../index.php?error=" . urlencode($error_message));
    exit();  // Asegura que el código posterior no se ejecute
} else {
    // Nota: el recurso de conexion NO se guarda en la sesion. Los recursos de
    // oci8 no se serializan, asi que hacerlo no conservaba nada y solo daba la
    // impresion de que si. Cada peticion abre su propia conexion.
    $_SESSION["autentificado"] = "SI";
    
    // Realizar la consulta para obtener los datos del usuario
    $usuariomain = strtoupper($_SESSION['usuario']);

    // El nombre del empleado puede estar en cualquiera de las cuatro oficinas,
    // por eso el UNION ALL sobre los enlaces de base de datos. El usuario va
    // como parametro ligado: es el unico valor variable de la consulta.
    $query = "SELECT NO_EMPL, AP_PATE_EMPL, AP_MATE_EMPL FROM orlcdba.tp_empl@DBL_1001_UNICA WHERE CO_USUA = :usuario
              UNION ALL
              SELECT NO_EMPL, AP_PATE_EMPL, AP_MATE_EMPL FROM orlcdba.tp_empl@DBL_1002_UNICA WHERE CO_USUA = :usuario
              UNION ALL
              SELECT NO_EMPL, AP_PATE_EMPL, AP_MATE_EMPL FROM orlcdba.tp_empl@DBL_1003_UNICA WHERE CO_USUA = :usuario
              UNION ALL
              SELECT NO_EMPL, AP_PATE_EMPL, AP_MATE_EMPL FROM orlcdba.tp_empl@DBL_1004_UNICA WHERE CO_USUA = :usuario";

    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':usuario', $usuariomain);
    oci_execute($stid);

    // Obtener el primer registro y guardarlo en la sesión
    $row = oci_fetch_array($stid, OCI_ASSOC);
    if ($row) {
        // Guardamos el nombre completo en la sesión
        $_SESSION['nombre'] = $row['NO_EMPL'] . ' ' . $row['AP_PATE_EMPL'] . ' ' . $row['AP_MATE_EMPL'];
    }

    // Redirigir a la página principal (main.php)
    header("Location: ../vista/main.php");
    exit;
}
?>
