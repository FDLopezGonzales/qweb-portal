<?php
session_start();  // Iniciar la sesión para poder acceder a las variables de sesión
session_unset();  // Elimina todas las variables de sesión
session_destroy();  // Destruye la sesión

// Redirige al index.php
header("Location: ../index.php");
exit;  // Asegura que el código posterior no se ejecute
?>
