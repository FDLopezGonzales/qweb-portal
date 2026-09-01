<?php
session_start();
setlocale(LC_ALL, 'esp');
include("../control/seguridad.php");

// Manejar consulta AJAX para partida
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax']) && $_POST['ajax'] == 'consultar_partida') {
    $fin = $_POST['fin'];
    
    // Conexión a la base de datos Oracle
    $conn = @oci_connect($_SESSION['usuario'], $_SESSION['password'], $_SESSION['db'], 'AL32UTF8');
    
    if (!$conn) {
        echo json_encode(['encontrado' => false, 'nu_part' => 'ERROR DE CONEXIÓN']);
        exit;
    }
    
    // Consulta Oracle como especificaste
    $query = "SELECT t.*, rowid FROM orlcdba.ta_part_rgst_inmb t WHERE LTRIM(nu_orig_part, '0') = :fin";
    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':fin', $fin);
    
    if (oci_execute($stid)) {
        $row = oci_fetch_assoc($stid);
        
        if ($row) {
            echo json_encode([
                'encontrado' => true, 
                'nu_part' => $row['NU_PART']
            ]);
        } else {
            echo json_encode([
                'encontrado' => false, 
                'nu_part' => 'NO ENCONTRADO'
            ]);
        }
    } else {
        echo json_encode([
            'encontrado' => false, 
            'nu_part' => 'ERROR EN CONSULTA'
        ]);
    }
    
    oci_close($conn);
    exit;
}

// Verificar si los datos del formulario fueron enviados
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['ajax'])) {

    $_SESSION['busind_form_data'] = array(
        'pic_ficant' => isset($_POST['pic_ficant']) ? $_POST['pic_ficant'] : '',
        'pic_ficha' => isset($_POST['pic_ficha']) ? $_POST['pic_ficha'] : '',
        'pic_apellido' => isset($_POST['pic_apellido']) ? $_POST['pic_apellido'] : '',
        'pic_nombres' => isset($_POST['pic_nombres']) ? $_POST['pic_nombres'] : '',
        'pic_descrip' => isset($_POST['pic_descrip']) ? $_POST['pic_descrip'] : '',
        'pic_cod_catast' => isset($_POST['pic_cod_catast']) ? $_POST['pic_cod_catast'] : '',
        'pic_tomo1' => isset($_POST['pic_tomo1']) ? $_POST['pic_tomo1'] : '',
        'pic_folio1' => isset($_POST['pic_folio1']) ? $_POST['pic_folio1'] : '',
        'pic_departamen' => isset($_POST['pic_departamen']) ? $_POST['pic_departamen'] : '',
        'pic_provincia' => isset($_POST['pic_provincia']) ? $_POST['pic_provincia'] : '',
        'pic_distrito' => isset($_POST['pic_distrito']) ? $_POST['pic_distrito'] : ''
    );
// Recibir los parámetros enviados desde el formulario
$pic_ficant = isset($_POST['pic_ficant']) ? strtoupper($_POST['pic_ficant']) : NULL;
$pic_ficha = isset($_POST['pic_ficha']) ? strtoupper($_POST['pic_ficha']) : NULL;
$pic_apellido = isset($_POST['pic_apellido']) ? '%' . strtoupper($_POST['pic_apellido']) . '%' : NULL;
$pic_nombres = isset($_POST['pic_nombres']) ? '%' . strtoupper($_POST['pic_nombres']) . '%' : NULL;
$pic_descrip = isset($_POST['pic_descrip']) ? strtoupper('%' . $_POST['pic_descrip'] . '%') : NULL;
$pic_cod_catast = isset($_POST['pic_cod_catast']) ? strtoupper($_POST['pic_cod_catast']) : NULL;
$pic_tomo1 = isset($_POST['pic_tomo1']) ? strtoupper($_POST['pic_tomo1']) : NULL;
$pic_folio1 = isset($_POST['pic_folio1']) ? strtoupper($_POST['pic_folio1']) : NULL;
$pic_departamen = isset($_POST['pic_departamen']) ? strtoupper($_POST['pic_departamen']) : NULL;
$pic_provincia = isset($_POST['pic_provincia']) ? strtoupper($_POST['pic_provincia']) : NULL;
$pic_distrito = isset($_POST['pic_distrito']) ? strtoupper($_POST['pic_distrito']) : NULL;

// PROCESAMIENTO ESPECIAL PARA FICHAS - SOLO CEROS A LA IZQUIERDA
if (!empty($pic_ficant)) {
    // Remover cualquier carácter no numérico
    $pic_ficant = preg_replace('/[^0-9]/', '', $pic_ficant);
    $pic_ficant = str_pad($pic_ficant, 6, '0', STR_PAD_LEFT);
} else {
    $pic_ficant = NULL;
}

if (!empty($pic_ficha)) {
    // Remover cualquier carácter no numérico y la E si existe
    $pic_ficha = preg_replace('/[^0-9]/', '', $pic_ficha);
    // Agregar E y ceros a la izquierda hasta completar E + 6 dígitos
    $pic_ficha = str_pad($pic_ficha, 6, '0', STR_PAD_LEFT);
} else {
    $pic_ficha = NULL;
}

// Verificar si al menos uno de los campos tiene valor
if (empty($pic_ficant) && empty($pic_ficha) && empty($pic_apellido) && empty($pic_nombres) && empty($pic_descrip) && empty($pic_cod_catast) && empty($pic_tomo1) && empty($pic_folio1) && empty($pic_departamen) && empty($pic_provincia) && empty($pic_distrito)) {
    die("Error: Debe ingresar al menos un parámetro para realizar la búsqueda.");
}

// Conexión a la base de datos Oracle
$conn = @oci_connect($_SESSION['usuario'], $_SESSION['password'], $_SESSION['db'], 'AL32UTF8');
if (!$conn) {
    die('Error al conectar a la base de datos: ' . oci_error());
}

// Ejecutar el procedimiento almacenado sp_web_busind con los parámetros
$query = "begin zrxi.sp_web_busind(:pic_ficant, :pic_ficha, :pic_apellido, :pic_nombres, :pic_descrip, :pic_cod_catast, :pic_tomo1, :pic_folio1, :pic_departamen, :pic_provincia, :pic_distrito); end;";
$stid = oci_parse($conn, $query);

// Asociar los parámetros con tamaño adecuado
oci_bind_by_name($stid, ':pic_ficant', $pic_ficant, 255);
oci_bind_by_name($stid, ':pic_ficha', $pic_ficha, 255);
oci_bind_by_name($stid, ':pic_apellido', $pic_apellido, 255);
oci_bind_by_name($stid, ':pic_nombres', $pic_nombres, 255);
oci_bind_by_name($stid, ':pic_descrip', $pic_descrip, 255);
oci_bind_by_name($stid, ':pic_cod_catast', $pic_cod_catast, 255);
oci_bind_by_name($stid, ':pic_tomo1', $pic_tomo1, 255);
oci_bind_by_name($stid, ':pic_folio1', $pic_folio1, 255);
oci_bind_by_name($stid, ':pic_departamen', $pic_departamen, 255);
oci_bind_by_name($stid, ':pic_provincia', $pic_provincia, 255);
oci_bind_by_name($stid, ':pic_distrito', $pic_distrito, 255);

// Ejecutar el procedimiento
oci_execute($stid);

// Primera consulta para contar el número total de resultados
$count_query = "SELECT COUNT(*) AS total FROM ZRXI.TA_BUSIND_WEB_TMP";
$stid_count = oci_parse($conn, $count_query);
oci_execute($stid_count);
$row_count = oci_fetch_assoc($stid_count);
$total_results = $row_count['TOTAL'];

// Definir la fecha y hora
date_default_timezone_set('America/Lima');
$dias = array("Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado");
$meses = array(1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril", 5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto", 9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre");

$fecha = date('j');
$mes = $meses[date('n')];
$año = date('Y'); 

// Si hay más de 100 resultados, mostrar mensaje con estilo consistente
if ($total_results > 100) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="../assets/img/Logo2.png">
<title>Resultados de la búsqueda BUS-IND</title>
<link rel="stylesheet" type="text/css" href="../assets/estilos/css/Bootstrap/bootstrap.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

<style>
    :root {
        --sunarp-gray: #505F6F;
        --sunarp-green: #8EBC45;
        --sunarp-red: #FF3E19;
        --sunarp-yellow: #F1A400;
        --sunarp-turquesa: #1AA6A4;
    }

    * {
        box-sizing: border-box;
    }

    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        overflow-x: hidden;
    }

    body {
        background: linear-gradient(135deg, rgba(26, 166, 164, 0.8) 0%, rgba(142, 188, 69, 0.8) 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
        position: relative;
    }

    /* Fondo animado */
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        z-index: -1;
        animation: float 20s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(1deg); }
    }

    /* CONTENEDOR PRINCIPAL CORREGIDO */
    .page-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        min-height: 100vh;
        padding: 15px;
        gap: 1rem;
    }

    /* Top Bar with Logout */
    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        max-width: 1200px;
        margin-bottom: 1rem;
    }

    .back-button, .logout-button {
        border: none;
        border-radius: 50px;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: white;
    }

    .back-button {
        background: linear-gradient(135deg, var(--sunarp-gray), #3d4a57);
        box-shadow: 0 6px 20px rgba(80, 95, 111, 0.3);
    }

    .back-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(80, 95, 111, 0.4);
        color: white;
        text-decoration: none;
    }

    .logout-button {
        background: linear-gradient(135deg, var(--sunarp-red), #ff6b47);
        box-shadow: 0 6px 20px rgba(255, 62, 25, 0.3);
    }

    .logout-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 62, 25, 0.4);
        color: white;
        text-decoration: none;
    }

    /* Header Section */
    .header-section {
        text-align: center;
        margin-bottom: 1rem;
    }

    .logo-container {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 25px;
        padding: 1.5rem;
        display: inline-block;
        margin-bottom: 1.5rem;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .logo-container:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
    }

    .logo-container img {
        width: 80px;
        height: auto;
        filter: brightness(0) invert(1);
    }

    .main-title {
        color: white;
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1rem;
        font-weight: 400;
    }

    /* CONTENEDOR DE TABLA CORREGIDO */
    .contenedor_tabla {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 1200px;
        overflow: hidden;
        margin: 0 auto;
    }

    .results-header {
        background: linear-gradient(135deg, var(--sunarp-turquesa) 0%, var(--sunarp-green) 100%);
        color: white;
        padding: 1.5rem 2rem;
        margin: 0;
        border-radius: 15px 15px 0 0;
    }

    .results-header h2 {
        margin: 0;
        font-weight: 600;
        font-size: 1.4rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .results-content {
        padding: 2rem;
    }

    .info-section {
        background: rgba(26, 166, 164, 0.05);
        border: 1px solid rgba(26, 166, 164, 0.2);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
    }

    .info-section p {
        margin: 0;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .user-info {
        color: var(--sunarp-red);
        font-weight: 600;
    }

    .date-info {
        color: var(--sunarp-turquesa);
    }

    .time-info {
        color: var(--sunarp-green);
    }

    .inff {
        background: rgba(142, 188, 69, 0.1);
        border: 1px solid var(--sunarp-green);
        color: var(--sunarp-green);
        padding: 0.8rem;
        border-radius: 8px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        text-align: center;
    }

    .warning-container {
        background: linear-gradient(135deg, rgba(241, 164, 0, 0.1) 0%, rgba(255, 62, 25, 0.05) 100%);
        border: 2px solid var(--sunarp-yellow);
        border-radius: 12px;
        padding: 2rem;
        margin: 1.5rem 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .warning-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--sunarp-yellow) 0%, var(--sunarp-red) 100%);
    }

    .warning-icon {
        font-size: 3rem;
        color: var(--sunarp-yellow);
        margin-bottom: 1rem;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .warning-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--sunarp-gray);
        margin-bottom: 0.5rem;
    }

    .warning-message {
        font-size: 1rem;
        color: var(--sunarp-gray);
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }

    .results-count {
        background: rgba(255, 62, 25, 0.1);
        color: var(--sunarp-red);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 700;
        font-size: 1.1rem;
        display: inline-block;
        margin-bottom: 1rem;
    }

    .action-buttons {
        padding: 1rem 2rem 2rem 2rem;
        text-align: center;
        border-top: 1px solid rgba(80, 95, 111, 0.1);
        margin-top: 1rem;
    }

    .btn-outline-danger {
        border: 2px solid var(--sunarp-red) !important;
        color: var(--sunarp-red) !important;
        background: transparent !important;
        border-radius: 25px !important;
        padding: 0.6rem 2rem !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
        font-size: 1rem !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
    }

    .btn-outline-danger:hover {
        background: var(--sunarp-red) !important;
        color: white !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 5px 15px rgba(255, 62, 25, 0.3) !important;
    }

    .suggestion-box {
        background: rgba(142, 188, 69, 0.05);
        border: 1px solid rgba(142, 188, 69, 0.3);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .suggestion-title {
        font-weight: 600;
        color: var(--sunarp-green);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .suggestion-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .suggestion-list li {
        padding: 0.3rem 0;
        color: var(--sunarp-gray);
        font-size: 0.9rem;
    }

    .suggestion-list li::before {
        content: '✓';
        color: var(--sunarp-green);
        font-weight: bold;
        margin-right: 0.5rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-wrapper {
            padding: 10px;
        }
        
        .contenedor_tabla {
            max-width: 100%;
        }
        
        .results-content {
            padding: 1.5rem;
        }
        
        .warning-container {
            padding: 1.5rem;
        }
        
        .warning-icon {
            font-size: 2.5rem;
        }
        
        .warning-title {
            font-size: 1.1rem;
        }
        
        .info-section {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .top-bar {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .main-title {
            font-size: 1.5rem;
        }
    }
</style>
</head>
<body>

<div class="page-wrapper">
    <!-- Top Bar -->
    <div class="top-bar">
        <a href="../vista/bus_ind.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Volver a Consultar</span>
        </a>
        <a href="../control/logout.php" class="logout-button">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <div class="logo-container">
            <img src="../assets/img/Logo2.png" alt="SUNARP Logo">
        </div>
        <h1 class="main-title">CONSULTA BUS-IND</h1>
        <p class="subtitle">Sistema de Consulta de Bienes Inmuebles</p>
    </div>

    <div class="contenedor_tabla">
        <div class="results-header">
            <h2><i class="fas fa-search"></i> Resultados de la Búsqueda BUS-IND</h2>
        </div>
        
        <div class="results-content">
            <center><p class="inff">INFORMACIÓN HISTÓRICA CON FINES REFERENCIALES</p></center>
            
            <div class="info-section">
                <p class="user-info">USUARIO: <?php echo $_SESSION['nombre']; ?></p>
                <p>Fecha de Consulta: <span class="date-info"><?php echo $dias[date('w')] . " $fecha de $mes del $año"; ?></span></p>
                <p>Hora de Consulta: <span class="time-info"><?php echo Date(" h:i:s a"); ?></span></p>
            </div>

            <div class="warning-container">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>

                <div class="warning-title">Demasiados Resultados Encontrados</div>
                
                <div class="results-count">
                    <i class="fas fa-list-ol"></i> <?php echo number_format($total_results); ?> registros encontrados
                </div>
                
                <div class="warning-message">
                    Se encontraron más de 100 resultados. Para obtener una búsqueda más precisa y visualizar los datos, 
                    por favor refine su búsqueda agregando más criterios específicos.
                </div>

                <div class="suggestion-box">
                    <div class="suggestion-title">
                        <i class="fas fa-lightbulb"></i> Sugerencias para refinar la búsqueda:
                    </div>
                    <ul class="suggestion-list">
                        <li>Agregue más caracteres al nombre o apellido</li>
                        <li>Especifique el código catastral completo</li>
                        <li>Incluya información de ubicación (departamento, provincia, distrito)</li>
                        <li>Combine múltiples criterios de búsqueda</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="../vista/bus_ind.php" class="btn btn-outline-danger">
                <i class="fas fa-arrow-left"></i>Volver a Buscar
            </a>
        </div>
    </div>
</div>

</body>
</html>

<?php
} else {
    // Si hay 100 o menos resultados, mostramos los resultados como antes
    $query = "SELECT * FROM ZRXI.TA_BUSIND_WEB_TMP WHERE ROWNUM <= 100";
    $stid = oci_parse($conn, $query);
    oci_execute($stid);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="../assets/img/Logo2.png">
<title>Resultados de la búsqueda BUS-IND</title>
<link rel="stylesheet" type="text/css" href="../assets/estilos/css/Bootstrap/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="../assets/estilos/css/Datatables/dataTables.bootstrap5.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

<style>
    :root {
        --sunarp-gray: #505F6F;
        --sunarp-green: #8EBC45;
        --sunarp-red: #FF3E19;
        --sunarp-yellow: #F1A400;
        --sunarp-turquesa: #1AA6A4;
    }

    * {
        box-sizing: border-box;
    }

    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        overflow-x: auto;
    }

    body {
        background: linear-gradient(135deg, rgba(26, 166, 164, 0.8) 0%, rgba(142, 188, 69, 0.8) 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
        position: relative;
    }

    /* Fondo animado */
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        z-index: -1;
        animation: float 20s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(1deg); }
    }

    /* CONTENEDOR PRINCIPAL CORREGIDO */
    .page-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        min-height: 100vh;
        padding: 15px;
        gap: 1rem;
    }

    /* Top Bar */
    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        max-width: 1400px;
        margin-bottom: 1rem;
    }

    .back-button, .logout-button {
        border: none;
        border-radius: 50px;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: white;
    }

    .back-button {
        background: linear-gradient(135deg, var(--sunarp-gray), #3d4a57);
        box-shadow: 0 6px 20px rgba(80, 95, 111, 0.3);
    }

    .back-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(80, 95, 111, 0.4);
        color: white;
        text-decoration: none;
    }

    .logout-button {
        background: linear-gradient(135deg, var(--sunarp-red), #ff6b47);
        box-shadow: 0 6px 20px rgba255, 62, 25, 0.3);
    }

    .logout-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 62, 25, 0.4);
        color: white;
        text-decoration: none;
    }

    /* Header Section */
    .header-section {
        text-align: center;
        margin-bottom: 1rem;
    }

    .logo-container {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 25px;
        padding: 1.5rem;
        display: inline-block;
        margin-bottom: 1.5rem;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .logo-container:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
    }

    .logo-container img {
        width: 80px;
        height: auto;
        filter: brightness(0) invert(1);
    }

    .main-title {
        color: white;
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1rem;
        font-weight: 400;
    }

    /* CONTENEDOR DE TABLA COMPLETAMENTE CORREGIDO */
    .contenedor_tabla {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        width: fit-content;
        max-width: 95vw;
        min-width: 1200px;
        overflow: visible;
        margin: 0 auto;
        display: block;
        position: relative;
    }

    .results-header {
        background: linear-gradient(135deg, var(--sunarp-turquesa) 0%, var(--sunarp-green) 100%);
        color: white;
        padding: 1rem 2rem;
        margin: 0;
        border-radius: 15px 15px 0 0;
        width: 100%;
    }

    .results-header h2 {
        margin: 0;
        font-weight: 600;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .results-content {
        padding: 1.5rem;
        width: 100%;
    }

    .info-section {
        background: rgba(26, 166, 164, 0.05);
        border: 1px solid rgba(26, 166, 164, 0.2);
        border-radius: 8px;
        padding: 0.8rem;
        margin-bottom: 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
    }

    .info-section p {
        margin: 0;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .user-info {
        color: var(--sunarp-red);
        font-weight: 600;
    }

    .date-info {
        color: var(--sunarp-turquesa);
    }

    .time-info {
        color: var(--sunarp-green);
    }

    .inff {
        background: rgba(142, 188, 69, 0.1);
        border: 1px solid var(--sunarp-green);
        color: var(--sunarp-green);
        padding: 0.5rem;
        border-radius: 6px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        text-align: center;
    }

    /* TABLA CON ESTILOS APLICADOS INMEDIATAMENTE Y TEXTO MÁS GRANDE */
    .table-wrapper {
        width: 100%;
        overflow: visible;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin: 1rem 0;
        opacity: 1;
        visibility: visible;
    }

    /* TABLA CON ANCHOS FIJOS DESDE EL INICIO Y TEXTO MÁS GRANDE */
    #example {
        width: 1600px !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        margin: 0 !important;
        font-size: 0.9rem !important; /* AUMENTADO DE 0.75rem A 0.9rem */
        table-layout: fixed !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    /* ANCHOS EXACTOS PARA 16 COLUMNAS - TOTAL 1600px */
    #example th:nth-child(1), #example td:nth-child(1) { width: 40px !important; min-width: 40px !important; max-width: 40px !important; }
    #example th:nth-child(2), #example td:nth-child(2) { width: 60px !important; min-width: 60px !important; max-width: 60px !important; }
    #example th:nth-child(3), #example td:nth-child(3) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }
    #example th:nth-child(4), #example td:nth-child(4) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }
    #example th:nth-child(5), #example td:nth-child(5) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }
    #example th:nth-child(6), #example td:nth-child(6) { width: 160px !important; min-width: 160px !important; max-width: 160px !important; }
    #example th:nth-child(7), #example td:nth-child(7) { width: 140px !important; min-width: 140px !important; max-width: 140px !important; }
    #example th:nth-child(8), #example td:nth-child(8) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }
    #example th:nth-child(9), #example td:nth-child(9) { width: 50px !important; min-width: 50px !important; max-width: 50px !important; }
    #example th:nth-child(10), #example td:nth-child(10) { width: 50px !important; min-width: 50px !important; max-width: 50px !important; }
    #example th:nth-child(11), #example td:nth-child(11) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }
    #example th:nth-child(12), #example td:nth-child(12) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }
    #example th:nth-child(13), #example td:nth-child(13) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }
    #example th:nth-child(14), #example td:nth-child(14) { width: 70px !important; min-width: 70px !important; max-width: 70px !important; }
    #example th:nth-child(15), #example td:nth-child(15) { width: 50px !important; min-width: 50px !important; max-width: 50px !important; }
    #example th:nth-child(16), #example td:nth-child(16) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }

    /* CABECERA CON ESTILOS APLICADOS INMEDIATAMENTE Y TEXTO MÁS GRANDE */
    #example thead th {
        background: linear-gradient(135deg, var(--sunarp-gray) 0%, #3d4a57 100%) !important;
        color: white !important;
        font-weight: 600 !important;
        padding: 1rem 0.4rem !important; /* AUMENTADO DE 0.6rem A 1rem */
        border: 1px solid rgba(255,255,255,0.1) !important;
        text-align: center !important;
        font-size: 0.75rem !important; /* AUMENTADO DE 0.6rem A 0.75rem */
        text-transform: uppercase !important;
        letter-spacing: 0.2px !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 10 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        box-sizing: border-box !important;
        line-height: 1.3 !important; /* AUMENTADO DE 1.1 A 1.3 */
        opacity: 1 !important;
        visibility: visible !important;
        height: 55px !important; /* ALTURA FIJA PARA CABECERAS */
    }

    /* CELDAS DEL CUERPO CON TEXTO MÁS GRANDE Y ALTURA AUMENTADA */
    #example tbody td {
        padding: 0.8rem 0.4rem !important; /* AUMENTADO DE 0.5rem A 0.8rem */
        border: 1px solid rgba(80, 95, 111, 0.1) !important;
        vertical-align: middle !important;
        font-size: 0.85rem !important; /* AUMENTADO DE 0.7rem A 0.85rem */
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        text-align: center !important;
        box-sizing: border-box !important;
        opacity: 1 !important;
        visibility: visible !important;
        line-height: 1.4 !important; /* AUMENTADO PARA MEJOR ESPACIADO */
        min-height: 50px !important; /* ALTURA MÍNIMA AUMENTADA PARA CELDAS */
        height: 50px !important; /* ALTURA FIJA PARA CELDAS */
    }

    #example tbody tr {
        transition: all 0.2s ease !important;
        cursor: pointer !important;
        opacity: 1 !important;
        visibility: visible !important;
        height: 50px !important; /* ALTURA FIJA PARA FILAS */
    }

    #example tbody tr:hover {
        background: linear-gradient(135deg, rgba(142, 188, 69, 0.08) 0%, rgba(26, 166, 164, 0.05) 100%) !important;
    }

    #example tbody tr:nth-child(even) {
        background: rgba(248, 249, 250, 0.6) !important;
    }

    #example tbody tr:nth-child(even):hover {
        background: linear-gradient(135deg, rgba(142, 188, 69, 0.08) 0%, rgba(26, 166, 164, 0.05) 100%) !important;
    }

    /* BADGES MÁS GRANDES Y LEGIBLES */
    .badge-ficha {
        background: var(--sunarp-green);
        color: white;
        padding: 0.35rem 0.6rem !important; /* AUMENTADO */
        border-radius: 12px !important; /* AUMENTADO */
        font-size: 0.75rem !important; /* AUMENTADO DE 0.6rem A 0.75rem */
        font-weight: 600;
        display: inline-block;
        white-space: nowrap;
        line-height: 1.2 !important;
    }

    .badge-ficha.electronica {
        background: var(--sunarp-turquesa);
    }

    .badge-area {
        background: var(--sunarp-yellow);
        color: white;
        padding: 0.3rem 0.5rem !important; /* AUMENTADO */
        border-radius: 10px !important; /* AUMENTADO */
        font-size: 0.75rem !important; /* AUMENTADO DE 0.6rem A 0.75rem */
        font-weight: 600;
        white-space: nowrap;
        line-height: 1.2 !important;
    }

    /* DataTables CON ESTILOS APLICADOS INMEDIATAMENTE */
    .dataTables_wrapper {
        padding: 0 !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
        opacity: 1 !important;
        visibility: visible !important;
    }

    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 2px solid rgba(80, 95, 111, 0.2) !important;
        border-radius: 6px !important;
        padding: 0.4rem 0.6rem !important;
        font-size: 0.85rem !important;
    }

    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: var(--sunarp-green) !important;
        outline: none !important;
    }

    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        margin: 1rem 0 !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    /* Botones */
    .btn-outline-danger {
        border: 2px solid var(--sunarp-red) !important;
        color: var(--sunarp-red) !important;
        background: transparent !important;
        border-radius: 20px !important;
        padding: 0.4rem 1.2rem !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
        font-size: 0.9rem !important;
    }

    .btn-outline-danger:hover {
        background: var(--sunarp-red) !important;
        color: white !important;
        transform: translateY(-1px) !important;
    }

    .btn-outline-primary {
        border: 2px solid var(--sunarp-turquesa) !important;
        color: var(--sunarp-turquesa) !important;
        background: transparent !important;
        border-radius: 20px !important;
        padding: 0.4rem 1.2rem !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
        font-size: 0.9rem !important;
    }

    .btn-outline-primary:hover {
        background: var(--sunarp-turquesa) !important;
        color: white !important;
        transform: translateY(-1px) !important;
    }

    /* MODAL MEJORADO */
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }

    .modal-header {
        background: linear-gradient(135deg, var(--sunarp-turquesa) 0%, var(--sunarp-green) 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        border-bottom: none;
        padding: 1.2rem 1.8rem;
    }

    .modal-title {
        font-weight: 600;
        font-size: 1.3rem;
    }

    .modal-body {
        padding: 2rem;
        max-height: 70vh;
        overflow-y: auto;
    }

    .details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.2rem;
        margin-bottom: 1.2rem;
    }

    .details-grid.full-width {
        grid-template-columns: 1fr;
    }

    .detail-item {
        background: rgba(248, 249, 250, 0.8);
        border: 1px solid rgba(80, 95, 111, 0.1);
        border-radius: 10px;
        padding: 1.2rem;
        transition: all 0.3s ease;
    }

    .detail-item:hover {
        background: rgba(26, 166, 164, 0.05);
        border-color: var(--sunarp-turquesa);
    }

    .detail-label {
        font-weight: 600;
        color: var(--sunarp-gray);
        font-size: 1rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .detail-value {
        color: #333;
        font-size: 1.1rem;
        word-break: break-word;
        font-weight: 500;
        line-height: 1.4;
    }

    .detail-value.highlight {
        color: var(--sunarp-turquesa);
        font-weight: 600;
        font-size: 1.2rem;
    }

    .detail-item.fin-item {
        background: rgba(241, 164, 0, 0.1);
        border: 2px solid var(--sunarp-yellow);
    }

    .detail-item.fin-item:hover {
        background: rgba(241, 164, 0, 0.15);
        border-color: var(--sunarp-yellow);
    }

    .detail-item.fin-item .detail-value {
        color: #B8860B;
        font-weight: 700;
        font-size: 1.3rem;
    }

    .detail-item.partida-item.encontrada {
        background: rgba(142, 188, 69, 0.1);
        border: 2px solid var(--sunarp-green);
    }

    .detail-item.partida-item.encontrada:hover {
        background: rgba(142, 188, 69, 0.15);
        border-color: var(--sunarp-green);
    }

    .detail-item.partida-item.encontrada .detail-value {
        color: var(--sunarp-green);
        font-weight: 700;
        font-size: 1.3rem;
    }

    .detail-item.partida-item.no-encontrada {
        background: rgba(255, 62, 25, 0.1);
        border: 2px solid var(--sunarp-red);
    }

    .detail-item.partida-item.no-encontrada:hover {
        background: rgba(255, 62, 25, 0.15);
        border-color: var(--sunarp-red);
    }

    .detail-item.partida-item.no-encontrada .detail-value {
        color: var(--sunarp-red);
        font-weight: 700;
        font-size: 1.3rem;
    }

    .fin-calculation {
        font-size: 0.8rem;
        color: #666;
        margin-top: 0.3rem;
        font-style: italic;
    }

    .oracle-info {
        font-size: 0.8rem;
        color: #666;
        margin-top: 0.3rem;
        font-style: italic;
    }

    .modal-footer {
        border-top: none;
        padding: 1.2rem 1.8rem 1.8rem;
    }

    /* BOTONES DE ACCIÓN */
    .action-buttons {
        padding: 1rem 1.5rem;
        text-align: right;
        border-top: 1px solid rgba(80, 95, 111, 0.1);
        margin-top: 0;
    }

    /* Responsive */
    @media (max-width: 1400px) {
        .contenedor_tabla {
            min-width: auto;
            width: 95vw;
        }
        
        #example {
            width: 100% !important;
            font-size: 0.8rem !important; /* MANTENIDO MÁS GRANDE */
        }
        
        #example th, #example td {
            padding: 0.6rem 0.2rem !important; /* MANTENIDO MÁS GRANDE */
            font-size: 0.75rem !important; /* MANTENIDO MÁS GRANDE */
        }
    }

    @media (max-width: 768px) {
        .page-wrapper {
            padding: 10px;
        }
        
        .details-grid {
            grid-template-columns: 1fr;
        }
        
        .contenedor_tabla {
            width: 100%;
            min-width: auto;
        }
        
        #example {
            font-size: 0.75rem !important; /* MANTENIDO MÁS GRANDE */
        }
        
        #example th, #example td {
            padding: 0.5rem 0.15rem !important; /* MANTENIDO MÁS GRANDE */
            font-size: 0.7rem !important; /* MANTENIDO MÁS GRANDE */
        }

        .top-bar {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .main-title {
            font-size: 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .detail-item {
            padding: 1rem;
        }

        .detail-label {
            font-size: 0.9rem;
        }

        .detail-value {
            font-size: 1rem;
        }

        .detail-value.highlight {
            font-size: 1.1rem;
        }
    }

    /* PREVENIR PARPADEO INICIAL */
    .contenedor_tabla,
    #example,
    .dataTables_wrapper {
        opacity: 1 !important;
        visibility: visible !important;
    }
</style>
</head>
<body>

<div class="page-wrapper">
    <!-- Top Bar -->
    <div class="top-bar">
        <a href="../vista/bus_ind.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Volver a Consultar</span>
        </a>
        <a href="../control/logout.php" class="logout-button">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <div class="logo-container">
            <img src="../assets/img/Logo2.png" alt="SUNARP Logo">
        </div>
        <h1 class="main-title">CONSULTA BUS-IND</h1>
        <p class="subtitle">Sistema de Consulta de Bienes Inmuebles</p>
    </div>

    <div class="contenedor_tabla">
        <div class="results-header">
            <h2><i class="fas fa-table"></i> Resultados de la Búsqueda BUS-IND</h2>
        </div>
        
        <div class="results-content">
            <center><p class="inff">INFORMACIÓN HISTÓRICA CON FINES REFERENCIALES</p></center>
            
            <div class="info-section">
                <p class="user-info">USUARIO: <?php echo $_SESSION['nombre']; ?></p>
                <p>Fecha de Consulta: <span class="date-info"><?php echo $dias[date('w')] . " $fecha de $mes del $año"; ?></span></p>
                <p>Hora de Consulta: <span class="time-info"><?php echo Date(" h:i:s a"); ?></span></p>
            </div>

            <div class="table-wrapper">
                <table id="example" class="table table-striped table-hover table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Nro</th>
                            <th>Oficina</th>
                            <th>F.Mec</th>
                            <th>F.Elec</th>
                            <th>UBIGEO</th>
                            <th>Nombres</th>
                            <th>Descripción</th>
                            <th>Cód.Cat</th>
                            <th>Tomo</th>
                            <th>Folio</th>
                            <th>Depto</th>
                            <th>Prov</th>
                            <th>Dist</th>
                            <th>Área</th>
                            <th>Med</th>
                            <th>T.Predio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $contador = 0;
                        $registros = array();
                        while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
                            $contador++;
                            $registros[] = $row;
                            
                            echo "<tr onclick='mostrarDetalle($contador)' data-registro='$contador'>";
                            echo "<td><strong>$contador</strong></td>";
                            echo "<td>{$row['OFICINA']}</td>";
                            echo "<td><span class='badge-ficha'>{$row['FICANT']}</span></td>";
                            echo "<td><span class='badge-ficha electronica'>{$row['FICHA']}</span></td>";
                            echo "<td>{$row['UBICACION']}</td>";
                            echo "<td><strong>{$row['NOMBRES']}</strong></td>";
                            echo "<td>" . (!empty($row['DESCRIP']) ? $row['DESCRIP'] : '<em style="color: #999;">SIN DATOS</em>') . "</td>";
                            echo "<td><code>{$row['COD_CATAST']}</code></td>";
                            echo "<td>{$row['TOMO1']}</td>";
                            echo "<td>{$row['FOLIO1']}</td>";
                            echo "<td>{$row['DEPARTAMEN']}</td>";
                            echo "<td>{$row['PROVINCIA']}</td>";
                            echo "<td>{$row['DISTRITO']}</td>";
                            echo "<td><span class='badge-area'>" . number_format((float)$row['AREA'], 2, '.', '') . "</span></td>";
                            echo "<td>{$row['MEDIDA']}</td>";
                            echo "<td>{$row['TPREDIO']}</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="action-buttons">
            <a onclick="window.location.href='../vista/bus_ind.php'" class="btn btn-outline-danger me-2">
                <i class="fas fa-arrow-left me-1"></i>Atrás
            </a>
            <button onclick="imprimirTabla()" class="btn btn-outline-primary">
                <i class="fas fa-print me-1"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<!-- Modal mejorado -->
<div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="true">
<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="detalleModalLabel">
                <i class="fas fa-info-circle me-2"></i>Detalles Completos del Registro
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="detalleModalBody">
            <!-- Contenido dinámico -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
                <i class="fas fa-times me-1"></i>Cerrar
            </button>
        </div>
    </div>
</div>
</div>

<?php
}
}
?>

<!-- Scripts -->
<script src="../assets/estilos/js/jquery.js"></script>
<script src="../assets/estilos/js/Datatables/jquery.dataTables.min.js"></script>
<script src="../assets/estilos/js/Datatables/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Datos de los registros para el modal
var registrosData = <?php echo json_encode($registros ?? []); ?>;

$(document).ready(function() {
    // APLICAR ESTILOS INMEDIATAMENTE ANTES DE INICIALIZAR DATATABLES
    $('.contenedor_tabla').css({
        'opacity': '1',
        'visibility': 'visible'
    });
    
    $('#example').css({
        'opacity': '1',
        'visibility': 'visible'
    });

    // Inicializar la tabla CON CONFIGURACIÓN MEJORADA
    var table = $('#example').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "scrollX": false,
        "autoWidth": false,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        "columnDefs": [
            { "width": "40px", "targets": 0 },
            { "width": "60px", "targets": 1 },
            { "width": "80px", "targets": 2 },
            { "width": "80px", "targets": 3 },
            { "width": "80px", "targets": 4 },
            { "width": "160px", "targets": 5 },
            { "width": "140px", "targets": 6 },
            { "width": "80px", "targets": 7 },
            { "width": "50px", "targets": 8 },
            { "width": "50px", "targets": 9 },
            { "width": "80px", "targets": 10 },
            { "width": "80px", "targets": 11 },
            { "width": "80px", "targets": 12 },
            { "width": "70px", "targets": 13 },
            { "width": "50px", "targets": 14 },
            { "width": "80px", "targets": 15 }
        ],
        "initComplete": function() {
            // ASEGURAR QUE TODO SEA VISIBLE AL COMPLETAR
            $('.contenedor_tabla, #example, .dataTables_wrapper').css({
                'opacity': '1',
                'visibility': 'visible'
            });
        },
        "drawCallback": function() {
            // APLICAR ESTILOS EN CADA REDIBUJADO
            $('#example').css({
                'opacity': '1',
                'visibility': 'visible'
            });
        }
    });
});

// FUNCIÓN PARA CALCULAR FIN
function calcularFIN(ubigeo, ficha) {
    var ultimosTresUbigeo = ubigeo.slice(-3);
    var fichaNumeros = ficha.replace(/^E0*/g, '');
    var ultimosCincoFicha = fichaNumeros.slice(-5).padStart(5, '0');
    return ultimosTresUbigeo + ultimosCincoFicha;
}

// FUNCIÓN PARA CONSULTAR PARTIDA VIA AJAX
function consultarPartida(fin, callback) {
    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: {
            ajax: 'consultar_partida',
            fin: fin
        },
        dataType: 'json',
        success: function(response) {
            callback(response);
        },
        error: function() {
            callback({
                encontrado: false,
                nu_part: 'ERROR EN CONSULTA'
            });
        }
    });
}

// FUNCIÓN MODAL REORGANIZADA CON FIN Y PARTIDA
function mostrarDetalle(numeroRegistro) {
    var registro = registrosData[numeroRegistro - 1];
    if (!registro) return;

    var modalBody = document.getElementById('detalleModalBody');
    var fin = calcularFIN(registro.UBICACION, registro.FICHA);

    mostrarModalConLoading(registro, fin);
    consultarPartida(fin, function(resultadoPartida) {
        actualizarModalConPartida(registro, fin, resultadoPartida);
    });
}

function mostrarModalConLoading(registro, fin) {
    var modalBody = document.getElementById('detalleModalBody');
    var html = '';

    var area_completa = '';
    if (registro.AREA && registro.MEDIDA) {
        var area_valor = parseFloat(registro.AREA).toFixed(2);
        var medida = registro.MEDIDA.toLowerCase().trim();
        
        if (medida.includes('hectarea') || medida.includes('ha')) {
            area_completa = area_valor + ' Hectáreas (' + registro.MEDIDA + ')';
        } else {
            area_completa = area_valor + ' Metros Cuadrados (' + registro.MEDIDA + ')';
        }
    } else if (registro.AREA) {
        area_completa = parseFloat(registro.AREA).toFixed(2) + ' Metros Cuadrados';
    }

    // 1. PRIMERA FILA: Oficina y Tipo de Predio (2 columnas)
    html += '<div class="details-grid">';
    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-building"></i> Oficina</div>';
    html += '<div class="detail-value highlight">' + (registro.OFICINA || 'N/A') + '</div>';
    html += '</div>';

    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-home"></i> Tipo de Predio</div>';
    html += '<div class="detail-value highlight">' + (registro.TPREDIO || 'N/A') + '</div>';
    html += '</div>';
    html += '</div>';

    // 2. SEGUNDA FILA: Ficha Mecánica y Ficha Electrónica (2 columnas)
    html += '<div class="details-grid">';
    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-cog"></i> Ficha Mecánica</div>';
    html += '<div class="detail-value highlight">' + (registro.FICANT || 'N/A') + '</div>';
    html += '</div>';

    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-desktop"></i> Ficha Electrónica</div>';
    html += '<div class="detail-value highlight">' + (registro.FICHA || 'N/A') + '</div>';
    html += '</div>';
    html += '</div>';

    // 3. TERCERA FILA: FIN y Número de Partida (2 columnas)
    html += '<div class="details-grid">';
    html += '<div class="detail-item fin-item">';
    html += '<div class="detail-label"><i class="fas fa-calculator"></i> NEF</div>';
    html += '<div class="detail-value">' + fin + '</div>';
    //html += '<div class="fin-calculation">UBIGEO últimos 3: ' + registro.UBICACION.slice(-3) + ' + Ficha últimos 5: ' + registro.FICHA.replace(/^E0*/g, '').slice(-5).padStart(5, '0') + '</div>';
    html += '</div>';

    html += '<div class="detail-item partida-item" id="partida-container">';
    html += '<div class="detail-label"><i class="fas fa-book"></i> Posible N° Partida</div>';
    html += '<div class="detail-value">Consultando...</div>';
    //html += '<div class="oracle-info">Consulta Oracle: orlcdba.ta_part_rgst_inmb</div>';
    html += '</div>';
    html += '</div>';

    // 4. CUARTA FILA: Ubigeo, Código Catastral y Área (3 columnas)
    html += '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem;">';
    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-map-marker-alt"></i> Ubigeo</div>';
    html += '<div class="detail-value">' + (registro.UBICACION || 'N/A') + '</div>';
    html += '</div>';

    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-barcode"></i> Código Catastral</div>';
    html += '<div class="detail-value">' + (registro.COD_CATAST || 'N/A') + '</div>';
    html += '</div>';

    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-ruler"></i> Área Completa</div>';
    html += '<div class="detail-value highlight">' + (area_completa || 'N/A') + '</div>';
    html += '</div>';
    html += '</div>';

    // 5. QUINTA FILA: Tomo y Folio (2 columnas)
    html += '<div class="details-grid">';
    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-book"></i> Tomo</div>';
    html += '<div class="detail-value">' + (registro.TOMO1 || 'N/A') + '</div>';
    html += '</div>';

    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-file"></i> Folio</div>';
    html += '<div class="detail-value">' + (registro.FOLIO1 || 'N/A') + '</div>';
    html += '</div>';
    html += '</div>';

    // 6. SEXTA FILA: Nombres (ancho completo)
    html += '<div class="details-grid full-width">';
    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-user"></i> Nombres Completos</div>';
    html += '<div class="detail-value highlight">' + (registro.NOMBRES || 'N/A') + '</div>';
    html += '</div>';
    html += '</div>';

    // 7. SÉPTIMA FILA: Descripción (ancho completo)
    html += '<div class="details-grid full-width">';
    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-info-circle"></i> Descripción</div>';
    html += '<div class="detail-value">' + (registro.DESCRIP || 'SIN DATOS') + '</div>';
    html += '</div>';
    html += '</div>';

    // 8. OCTAVA FILA: Departamento, Provincia y Distrito (3 columnas)
    html += '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem;">';
    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-map"></i> Departamento</div>';
    html += '<div class="detail-value">' + (registro.DEPARTAMEN || 'N/A') + '</div>';
    html += '</div>';

    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-map"></i> Provincia</div>';
    html += '<div class="detail-value">' + (registro.PROVINCIA || 'N/A') + '</div>';
    html += '</div>';

    html += '<div class="detail-item">';
    html += '<div class="detail-label"><i class="fas fa-map"></i> Distrito</div>';
    html += '<div class="detail-value">' + (registro.DISTRITO || 'N/A') + '</div>';
    html += '</div>';
    html += '</div>';

    modalBody.innerHTML = html;

    var modal = new bootstrap.Modal(document.getElementById('detalleModal'));
    modal.show();
}

function actualizarModalConPartida(registro, fin, resultadoPartida) {
    var partidaContainer = document.getElementById('partida-container');
    if (partidaContainer) {
        var clasePartida = resultadoPartida.encontrado ? 'encontrada' : 'no-encontrada';
        partidaContainer.className = 'detail-item partida-item ' + clasePartida;
        
        var valorPartida = partidaContainer.querySelector('.detail-value');
        if (valorPartida) {
            valorPartida.textContent = resultadoPartida.nu_part;
        }
    }
}

// FUNCIÓN DE IMPRESIÓN
function imprimirTabla() {
    var iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.width = '0px';
    iframe.style.height = '0px';
    iframe.style.border = 'none';
    document.body.appendChild(iframe);

    var contenidoImpresion = document.querySelector('.contenedor_tabla').innerHTML;
    var fechaConsulta = "<?php echo $dias[date('w')] . ' ' . $fecha . ' de ' . $mes . ' del ' . $año; ?>";
    var horaConsulta = "<?php echo Date('h:i:s a'); ?>";
    var usuario = "<?php echo $_SESSION['nombre']; ?>";

    var doc = iframe.contentWindow.document;
    doc.open();
    doc.write('<html><head><title>Imprimir Reporte BUS-IND</title>');
    doc.write('<style>');
    doc.write('body { font-family: Arial, sans-serif; margin: 0; padding: 15px; position: relative; font-size: 12px; }');
        
    doc.write('.print-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #505F6F; padding-bottom: 10px; }');
    doc.write('.print-header h1 { margin: 0; font-size: 18px; color: #505F6F; }');
    doc.write('.print-header h2 { margin: 5px 0; font-size: 14px; color: #1AA6A4; }');
    doc.write('.print-info { display: flex; justify-content: space-between; margin: 10px 0; font-size: 11px; }');
    doc.write('.print-info div { font-weight: bold; }');
    
    doc.write('.contenedor_tabla { width: 100%; margin: 0; }');
    
    doc.write('table { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 10px; }');
    doc.write('th, td { padding: 4px 3px; text-align: center; border: 1px solid #333; word-wrap: break-word; vertical-align: middle; }');
    doc.write('th { background-color: #505F6F; color: white; font-weight: bold; font-size: 9px; }');
    doc.write('tr:nth-child(even) { background-color: #f9f9f9; }');
    
    doc.write('th:nth-child(1), td:nth-child(1) { width: 3%; }');
    doc.write('th:nth-child(2), td:nth-child(2) { width: 4%; }');
    doc.write('th:nth-child(3), td:nth-child(3) { width: 6%; }');
    doc.write('th:nth-child(4), td:nth-child(4) { width: 6%; }');
    doc.write('th:nth-child(5), td:nth-child(5) { width: 10%; }');
    doc.write('th:nth-child(6), td:nth-child(6) { width: 12%; }');
    doc.write('th:nth-child(7), td:nth-child(7) { width: 14%; }');
    doc.write('th:nth-child(8), td:nth-child(8) { width: 7%; }');
    doc.write('th:nth-child(9), td:nth-child(9) { width: 3%; }');
    doc.write('th:nth-child(10), td:nth-child(10) { width: 3%; }');
    doc.write('th:nth-child(11), td:nth-child(11) { width: 6%; }');
    doc.write('th:nth-child(12), td:nth-child(12) { width: 6%; }');
    doc.write('th:nth-child(13), td:nth-child(13) { width: 9%; }');
    doc.write('th:nth-child(14), td:nth-child(14) { width: 5%; }');
    doc.write('th:nth-child(15), td:nth-child(15) { width: 5%; }');
    doc.write('th:nth-child(16), td:nth-child(16) { width: 7%; }');

    doc.write('.watermark {');
    doc.write('    position: fixed;');
    doc.write('    top: 50%;');
    doc.write('    left: 50%;');
    doc.write('    transform: translate(-50%, -50%) rotate(-30deg);');
    doc.write('    font-size: 2.5rem;');
    doc.write('    color: rgba(0, 0, 0, 0.08);');
    doc.write('    z-index: -1;');
    doc.write('    pointer-events: none;');
    doc.write('    font-weight: bold;');
    doc.write('    text-align: center;');
    doc.write('    width: 100%;');
    doc.write('    white-space: nowrap;');
    doc.write('}');

    doc.write('@media print {');
    doc.write('    @page { size: A4 landscape; margin: 10mm; }');
    doc.write('    body { font-size: 11px; }');
    doc.write('    table { font-size: 9px; }');
    doc.write('    th, td { padding: 3px 2px; }');
    doc.write('    .consulta, .inff, .text-end, .action-buttons, .dataTables_info, .dataTables_paginate, .dataTables_length, .dataTables_filter, .results-header, .info-section { display: none; }');
    doc.write('}');

    doc.write('</style></head><body>');

    doc.write('<div class="print-header">');
    doc.write('<h1>SUPERINTENDENCIA NACIONAL DE LOS REGISTROS PÚBLICOS - SUNARP</h1>');
    doc.write('<h2>Resultados de Búsqueda BUS-IND</h2>');
    doc.write('<div class="print-info">');
    doc.write('<div>USUARIO: ' + usuario + '</div>');
    doc.write('<div>FECHA: ' + fechaConsulta + '</div>');
    doc.write('<div>HORA: ' + horaConsulta + '</div>');
    doc.write('</div>');
    doc.write('<p style="margin: 5px 0; font-size: 10px; color: #8EBC45; font-weight: bold;">INFORMACIÓN HISTÓRICA CON FINES REFERENCIALES</p>');
    doc.write('</div>');

    doc.write('<div class="watermark">INFORMACIÓN HISTÓRICA CON <br>FINES REFERENCIALES</div>');
    
    doc.write(contenidoImpresion);
    doc.write('</body></html>');
    doc.close();

    iframe.contentWindow.focus();
    iframe.contentWindow.print();

    setTimeout(function() {
        document.body.removeChild(iframe);
    }, 1000);
}
</script>

</body>
</html>

<?php

?>
