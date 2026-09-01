<?php
require_once __DIR__ . '/config_env.php';

// Descriptores de conexion de cada oficina, armados desde el entorno.
// Ver QWEB_OFICINAS y QWEB_ORACLE_<OFICINA> en .env.example.
$oficinas = [];
foreach (qweb_oficinas_disponibles() as $nombreOficina) {
    $descriptor = qweb_connect_string($nombreOficina);
    if ($descriptor !== '') {
        $oficinas[$nombreOficina] = $descriptor;
    }
}
