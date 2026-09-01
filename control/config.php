<?php
require_once __DIR__ . '/config_env.php';

$driver = "oci8";
$colorFondo = qweb_config('QWEB_COLOR_FONDO', '8cacc1');
$pais = "Peru";
$administrador = qweb_config('QWEB_CORREO_ADMIN', '');
$conn = 0;
