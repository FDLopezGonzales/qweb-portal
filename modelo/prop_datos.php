<?php
require_once __DIR__ . '/../control/config_env.php';
/**
 * modelo/prop_datos.php
 * Endpoint de datos (server-side) para DataTables del modulo PROP.
 * Consulta la base SQLite unificada (Pisco + Ica) generada desde los .DBF.
 *
 * Modos:
 *   (por defecto) listado paginado con filtros: oficina, registro, tomo, folio, ficha, nombre
 *   modo=enlace   devuelve la partida enlazada (Titular <-> Predio) para Propiedad Inmueble
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

$drawSesion = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
$tiempo_inactividad_maximo = 1800;
$sesionVencida = !isset($_SESSION["autentificado"]) || $_SESSION["autentificado"] !== "SI";
if (isset($_SESSION['ultimo_acceso']) && (time() - $_SESSION['ultimo_acceso']) > $tiempo_inactividad_maximo) {
    $sesionVencida = true;
    session_destroy();
}

if ($sesionVencida) {
    echo json_encode([
        "draw" => $drawSesion,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "sessionExpired" => true
    ]);
    exit;
}

$_SESSION['ultimo_acceso'] = time();

try {
    // El indice de partidas es un SQLite que se genera aparte desde Oracle; no
    // viaja con el codigo. Ver PROP_SQLITE_PATH en .env.example.
    $rutaIndice = qweb_config('PROP_SQLITE_PATH', __DIR__ . '/../data/prop.sqlite');
    $pdo = new PDO('sqlite:' . $rutaIndice);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(["error" => "No se pudo abrir la base de datos."]);
    exit;
}

$g = function ($k) { return isset($_GET[$k]) ? trim($_GET[$k]) : ''; };

// ---------- MODO ENLACE: Titular <-> Predio (Propiedad Inmueble) ----------
if ($g('modo') === 'enlace') {
    $oficina  = $g('oficina');
    $registro = $g('registro');
    $tomo = $g('tomo'); $folio = $g('folio'); $ficha = $g('ficha');
    $par = ($registro === 'PROP1') ? 'PROP2' : (($registro === 'PROP2') ? 'PROP1' : '');
    if ($par === '') { echo json_encode(["data" => []]); exit; }

    if ($ficha !== '') {
        $sql = "SELECT oficina,registro,tomo,folio,ficha,nombre,detalle,extra FROM partidas
                WHERE oficina=? AND registro=? AND ficha=?";
        $args = [$oficina, $par, $ficha];
    } else {
        $sql = "SELECT oficina,registro,tomo,folio,ficha,nombre,detalle,extra FROM partidas
                WHERE oficina=? AND registro=? AND tomo=? AND folio=?";
        $args = [$oficina, $par, $tomo, $folio];
    }
    $st = $pdo->prepare($sql); $st->execute($args);
    echo json_encode(["par" => $par, "data" => $st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- MODO LISTADO (DataTables server-side) ----------
$draw   = (int)($g('draw') ?: 1);
$start  = (int)($g('start') ?: 0);
$length = (int)($g('length') ?: 25);
if ($length <= 0 || $length > 500) $length = 25;

$oficina  = $g('oficina');
$registro = $g('registro');
$fTomo  = $g('tomo');
$fFolio = $g('folio');
$fFicha = $g('ficha');
$fNombre = $g('nombre');

// Sin ningun criterio -> no se devuelve nada (evita cargar TODO al limpiar).
if ($oficina === '' && $registro === '' && $fTomo === '' && $fFolio === '' && $fFicha === '' && $fNombre === '') {
    echo json_encode(["draw" => (int)($g('draw') ?: 1), "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []]);
    exit;
}

// WHERE base (oficina + registro) para recordsTotal
$whereBase = []; $paramsBase = [];
if ($oficina !== '')  { $whereBase[] = "oficina = ?";  $paramsBase[] = $oficina; }
if ($registro !== '') { $whereBase[] = "registro = ?"; $paramsBase[] = $registro; }

// WHERE completo (agrega filtros por campo)
$where = $whereBase; $params = $paramsBase;
// tomo/folio/ficha: comparacion tolerante a ceros a la izquierda ("19" == "019")
if ($fTomo !== '')  { $where[] = "ltrim(tomo,'0')  = ltrim(?, '0')"; $params[] = $fTomo; }
if ($fFolio !== '') { $where[] = "ltrim(folio,'0') = ltrim(?, '0')"; $params[] = $fFolio; }
if ($fFicha !== '') { $where[] = "ltrim(ficha,'0') = ltrim(?, '0')"; $params[] = $fFicha; }
// nombre: busca en nombre y detalle (apoderado, fundo, ubicacion, etc.)
// Se normaliza a MAYUSCULAS porque los datos se guardan en mayusculas (incluye tildes/N).
$nomU = ($fNombre !== '') ? mb_strtoupper($fNombre, 'UTF-8') : '';
if ($nomU !== '') { $where[] = "(nombre LIKE ? OR detalle LIKE ?)"; $params[] = "%$nomU%"; $params[] = "%$nomU%"; }

$wBase = $whereBase ? ("WHERE " . implode(" AND ", $whereBase)) : "";
$wFull = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$st = $pdo->prepare("SELECT COUNT(*) FROM partidas $wBase"); $st->execute($paramsBase);
$recordsTotal = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM partidas $wFull"); $st->execute($params);
$recordsFiltered = (int)$st->fetchColumn();

// Orden por RELEVANCIA cuando se busca por nombre:
//   0) el nombre empieza con el termino  (JUAN ...)
//   1) alguna palabra del nombre empieza con el termino  (PEREZ JUAN ...)
//   2) lo contiene en cualquier parte  (ABAD ... JUANA)
//   3) solo coincide en el detalle
$paramsData = $params;
if ($nomU !== '') {
    $orden = "ORDER BY CASE
                WHEN nombre LIKE ? THEN 0
                WHEN nombre LIKE ? THEN 1
                WHEN nombre LIKE ? THEN 2
                ELSE 3 END, nombre";
    array_push($paramsData, "$nomU%", "% $nomU%", "%$nomU%");
} else {
    $orden = "ORDER BY oficina, registro, nombre";
}

$st = $pdo->prepare("SELECT oficina, registro, tomo, folio, ficha, nombre, detalle, extra
                     FROM partidas $wFull
                     $orden
                     LIMIT $length OFFSET $start");
$st->execute($paramsData);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $recordsTotal,
    "recordsFiltered" => $recordsFiltered,
    "data" => $rows,
], JSON_UNESCAPED_UNICODE);
