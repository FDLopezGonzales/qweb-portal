<?php
session_start();
include("../control/seguridad.php");
date_default_timezone_set('America/Lima');

$SCHASIS = isset($_POST['SCHASIS']) ? $_POST['SCHASIS'] : NULL;
$FECHA_FIN = isset($_POST['FECHA_FIN']) ? $_POST['FECHA_FIN'] : NULL;

if ($FECHA_FIN or $SCHASIS) {
    $dias = array("Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado");
    $meses = array(1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril", 5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto", 9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre");
    
    $fecha = date('j');
    $mes = $meses[date('n')];
    $año = date('Y');

    $conn = @oci_connect($_SESSION['usuario'], $_SESSION['password'], $_SESSION['db'], 'AL32UTF8');
    $query = ("begin zrxi.pkg_adm_soporte.set_view_query('nu_docu','$SCHASIS'); end;");
    $stid = oci_parse($conn, $query);
    oci_execute($stid);
    $query = ("select * from ZRXI.VW_WEB_DEVOLUCIONES_zonal");
    $stid = oci_parse($conn, $query);
    oci_execute($stid);
?>

<head>
    <link rel="icon" type="image/png" href="../assets/img/Logo2.png">
    <title>Resultados del DNI: <?php echo $SCHASIS ?></title>
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

        body {
            background: linear-gradient(135deg, rgba(26, 166, 164, 0.8) 0%, rgba(142, 188, 69, 0.8) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 15px;
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

        /* Top Bar with Logout */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 25rem; /* Agregar esta línea para separar los botones */
        }

        .back-button {
            background: linear-gradient(135deg, var(--sunarp-gray), #3d4a57);
            color: white;
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
            color: white;
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
            margin-bottom: 2rem;
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

        .page-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            padding: 0 15px;
            overflow-x: auto;
        }

        .contenedor_tabla {
            background: white;
            padding: 0;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 1rem auto;
            width: 95%;
            max-width: 1200px;
            overflow: visible;
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
            padding: 1.5rem 1.5rem 0 1.5rem;
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

        .table-wrapper {
            width: 100%;
            overflow: visible;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin: 1rem 0 0 0;
        }

        #example {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin: 0;
            font-size: 0.85rem; /* Texto un poco más grande */
            table-layout: fixed;
        }

        /* Anchos específicos para 9 columnas - Tabla ajustada */
        #example th:nth-child(1), #example td:nth-child(1) { width: 8%; }  /* Nro */
        #example th:nth-child(2), #example td:nth-child(2) { width: 12%; } /* Oficina */
        #example th:nth-child(3), #example td:nth-child(3) { width: 8%; }  /* Año */
        #example th:nth-child(4), #example td:nth-child(4) { width: 18%; } /* Número de Título */
        #example th:nth-child(5), #example td:nth-child(5) { width: 12%; } /* Monto */
        #example th:nth-child(6), #example td:nth-child(6) { width: 15%; } /* Fecha Creación */
        #example th:nth-child(7), #example td:nth-child(7) { width: 15%; } /* Presentante */
        #example th:nth-child(8), #example td:nth-child(8) { width: 8%; } /* Estado */
        #example th:nth-child(9), #example td:nth-child(9) { width: 14%; } /* Oficina Presentada */

        #example thead th {
            background: linear-gradient(135deg, var(--sunarp-gray) 0%, #3d4a57 100%);
            color: white;
            font-weight: 600;
            padding: 0.8rem 0.5rem;
            border: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        #example tbody td {
            padding: 0.7rem 0.5rem; /* Padding más generoso */
            border: 1px solid rgba(80, 95, 111, 0.1);
            vertical-align: middle;
            font-size: 0.8rem; /* Texto más legible */
            text-align: center;
        }

        #example tbody tr {
            transition: all 0.2s ease;
        }

        #example tbody tr:hover {
            background: linear-gradient(135deg, rgba(142, 188, 69, 0.08) 0%, rgba(26, 166, 164, 0.05) 100%);
        }

        #example tbody tr:nth-child(even) {
            background: rgba(248, 249, 250, 0.6);
        }

        .action-buttons {
            padding: 0.5rem 1.5rem 1.5rem 1.5rem;
            margin: 0;
            text-align: right;
        }

        .btn-outline-danger {
            border: 2px solid var(--sunarp-red);
            color: var(--sunarp-red);
            background: transparent;
            border-radius: 20px;
            padding: 0.4rem 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .btn-outline-danger:hover {
            background: var(--sunarp-red);
            color: white;
            transform: translateY(-1px);
        }

        .btn-outline-primary {
            border: 2px solid var(--sunarp-turquesa);
            color: var(--sunarp-turquesa);
            background: transparent;
            border-radius: 20px;
            padding: 0.4rem 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-outline-primary:hover {
            background: var(--sunarp-turquesa);
            color: white;
            transform: translateY(-1px);
        }

        .dataTables_wrapper {
            padding: 0;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid rgba(80, 95, 111, 0.2);
            border-radius: 6px;
            padding: 0.4rem 0.6rem;
            font-size: 0.85rem;
        }

        .dataTables_wrapper .dataTables_length select:focus,
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--sunarp-green);
            outline: none;
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin: 1rem 1.5rem;
        }

        @media (max-width: 768px) {
            .contenedor_tabla {
                max-width: 95vw;
                min-width: auto;
            }
            
            #example {
                font-size: 0.7rem;
            }
            
            #example th, #example td {
                padding: 0.4rem 0.2rem;
                font-size: 0.65rem;
            }
        }

        a[href="../vista/reporte01.php"]:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(80, 95, 111, 0.4) !important;
            color: white !important;
            text-decoration: none !important;
        }

        a[href="../control/logout.php"]:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(255, 62, 25, 0.4) !important;
            color: white !important;
            text-decoration: none !important;
        }
    </style>
</head>

<div class="page-wrapper">
    <!-- Top Bar -->
    <div class="top-bar">
        <a href="../vista/reporte01.php" class="back-button">
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
        <h1 class="main-title">CONSULTA POR DNI</h1>
        <p class="subtitle">Sistema de Consulta de Devoluciones por Documento Nacional de Identidad</p>
    </div>

    <div class="contenedor_tabla">
        <div class="results-header">
            <h2><i class="fas fa-file-invoice-dollar"></i> Resultados del DNI: <?php echo $SCHASIS ?></h2>
        </div>
        
        <div class="results-content">
            <div class="info-section">
                <p class="user-info">USUARIO: <?php echo $_SESSION['nombre']; ?></p>
                <p>Fecha de Consulta: <span class="date-info"><?php echo $dias[date('w')] . " $fecha de $mes del $año"; ?></span></p>
                <p>Hora de Consulta: <span class="time-info"><?php echo date("h:i:s a"); ?></span></p>
            </div>

            <div class="table-wrapper">
                <table id="example" class="table table-striped table-hover table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Nro</th>
                            <th>Oficina</th>
                            <th>Año</th>
                            <th>Número de Título Devolución</th>
                            <th>Monto Devolución</th>
                            <th>Fecha de Creación</th>
                            <th>Presentante</th>
                            <th>Estado</th>
                            <th>Oficina Presentada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $contador = 0;
                        while ($myrow = oci_fetch_array($stid, OCI_ASSOC)) {
                            $contador++;
                            echo "<tr>";
                            echo "<td><strong>$contador</strong></td>";
                            echo "<td>{$myrow['OFICINA']}</td>";
                            echo "<td>{$myrow['AA_TITU_DEVO']}</td>";
                            echo "<td>{$myrow['NU_TITU_DEVO']}</td>";
                            echo "<td><strong style='color: var(--sunarp-green);'>S/. " . number_format($myrow['MO_DEVO'], 2) . "</strong></td>";
                            echo "<td>{$myrow['TS_USUA_CREA']}</td>";
                            echo "<td>{$myrow['PRESENTANTE']}</td>";
                            echo "<td><span style='color: var(--sunarp-turquesa); font-weight: 600;'>{$myrow['ESTADO']}</span></td>";
                            echo "<td>{$myrow['OFIC_PRESENTA']}</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="action-buttons">
            
            <button class="btn-outline-primary" onclick="imprimirTabla()">
                <i class="fas fa-print me-1"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<?php } ?>

<script src="../assets/estilos/js/jquery.js"></script>
<script src="../assets/estilos/js/Datatables/jquery.dataTables.min.js"></script>
<script src="../assets/estilos/js/Datatables/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#example').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "scrollX": false,
        "autoWidth": false,
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]]
    });
});

function imprimirTabla() {
    var iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.width = '0px';
    iframe.style.height = '0px';
    iframe.style.border = 'none';
    document.body.appendChild(iframe);

    var contenidoImpresion = document.querySelector('.contenedor_tabla').innerHTML;
    var fechaConsulta = "<?php echo $dias[date('w')] . ' ' . $fecha . ' de ' . $mes . ' del ' . $año; ?>";
    var horaConsulta = "<?php echo date('h:i:s a'); ?>";
    var usuario = "<?php echo $_SESSION['nombre']; ?>";

    var doc = iframe.contentWindow.document;
    doc.open();
    doc.write('<html><head><title>Imprimir Reporte DNI</title>');
    doc.write('<style>');
    doc.write('body { font-family: Arial, sans-serif; margin: 0; padding: 15px; font-size: 12px; }');
    doc.write('.print-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #505F6F; padding-bottom: 10px; }');
    doc.write('.print-header h1 { margin: 0; font-size: 18px; color: #505F6F; }');
    doc.write('.print-header h2 { margin: 5px 0; font-size: 14px; color: #1AA6A4; }');
    doc.write('.print-info { display: flex; justify-content: space-between; margin: 10px 0; font-size: 11px; }');
    doc.write('.print-info div { font-weight: bold; }');
    doc.write('table { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 10px; }');
    doc.write('th, td { padding: 4px 3px; text-align: center; border: 1px solid #333; word-wrap: break-word; vertical-align: middle; }');
    doc.write('th { background-color: #505F6F; color: white; font-weight: bold; font-size: 9px; }');
    doc.write('tr:nth-child(even) { background-color: #f9f9f9; }');
    doc.write('@media print {');
    doc.write('    @page { size: A4 landscape; margin: 10mm; }');
    doc.write('    .results-header, .info-section, .action-buttons, .dataTables_info, .dataTables_paginate, .dataTables_length, .dataTables_filter { display: none; }');
    doc.write('}');
    doc.write('</style></head><body>');

    doc.write('<div class="print-header">');
    doc.write('<h1>SUPERINTENDENCIA NACIONAL DE LOS REGISTROS PÚBLICOS - SUNARP</h1>');
    doc.write('<h2>Resultados de Consulta por DNI: <?php echo $SCHASIS; ?></h2>');
    doc.write('<div class="print-info">');
    doc.write('<div>USUARIO: ' + usuario + '</div>');
    doc.write('<div>FECHA: ' + fechaConsulta + '</div>');
    doc.write('<div>HORA: ' + horaConsulta + '</div>');
    doc.write('</div>');
    doc.write('</div>');

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
