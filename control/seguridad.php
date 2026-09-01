<?php
session_start();

// Tiempo máximo de inactividad en segundos (30 minutos)
$tiempo_inactividad_maximo = 3600; // 3600 segundos = 60 minutos

// Verifica si el usuario está autenticado
if (!isset($_SESSION["autentificado"]) || $_SESSION["autentificado"] !== "SI") {
    // Si no está autenticado, redirige al login (index.php)
    header("Location: ../index.php");
    exit;  // Asegura que el código posterior no se ejecute
}

// Verifica si la última actividad del usuario fue hace más de 60 minutos
if (isset($_SESSION['ultimo_acceso']) && (time() - $_SESSION['ultimo_acceso']) > $tiempo_inactividad_maximo) {
    // Si ha pasado más de 60 minutos, destruye la sesión y redirige al login
    session_destroy();
    header("Location: ../control/logout.php");
    exit;
}

// Actualiza el tiempo del último acceso
$_SESSION['ultimo_acceso'] = time();
?>
