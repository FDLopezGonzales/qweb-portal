<?php
error_reporting(0);
include_once("../control/config.php");
$serv = str_replace(".", ".", $_SERVER['REMOTE_ADDR']);
$datosEnvio = "$fechah&" . session_name() . "=" . trim(session_id()) . "&usuario=$usuario&swLog=1";

// Verificar si hay un mensaje de error
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<?php
include_once("conexion.php");
session_start();
if (isset($_SESSION['usuario'])) {
    // Destruir la sesión si está activa
    session_unset();  // Elimina todas las variables de sesión
    session_destroy();  // Destruye la sesión
}

function formatearFechaPermiso($fecha) {
    if (!$fecha) {
        return $fecha;
    }

    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return $fecha;
    }

    return date('d/m/Y', $timestamp);
}

function normalizarFilaRestriccion($row) {
    $row = array_change_key_case($row, CASE_UPPER);

    if (isset($row['FECHAPERMISODESDE'])) {
        $row['FECHAPERMISODESDE'] = formatearFechaPermiso($row['FECHAPERMISODESDE']);
    }

    if (isset($row['FECHAPERMISOHASTA'])) {
        $row['FECHAPERMISOHASTA'] = formatearFechaPermiso($row['FECHAPERMISOHASTA']);
    }

    return $row;
}

function obtenerRestriccionesActivas($conn, $dni) {
    $sql = "
        SELECT
            \"NroDocumento\" NRODOCUMENTO,
            \"Nombres\" NOMBRES,
            \"NombrePermiso\" TIPO_AUSENCIA,
            \"Desc_Aprobacion_Negacion\" DETALLE,
            \"FechaPermisoDesde\" FECHAPERMISODESDE,
            \"FechaPermisoHasta\" FECHAPERMISOHASTA
        FROM \"dbo\".\"Vw_ZRXI_Permiso_Sancion_Vacac\"@DBL_SQLSERVERXI
        WHERE \"NroDocumento\" = :pic_dni
          AND \"Estado\" = 2
          AND TRUNC(SYSDATE) BETWEEN TRUNC(\"FechaPermisoDesde\") AND TRUNC(\"FechaPermisoHasta\")
        ORDER BY \"FechaPermisoDesde\" DESC
    ";

    $stmt = oci_parse($conn, $sql);
    if (!$stmt) {
        $error = oci_error($conn);
        error_log("[recuperar_clave] Error preparando consulta de restricciones: " . $error['message']);
        return [];
    }

    oci_bind_by_name($stmt, ":pic_dni", $dni, max(strlen($dni), 8));

    if (!@oci_execute($stmt)) {
        $error = oci_error($stmt);
        error_log("[recuperar_clave] Error consultando restricciones activas: " . $error['message']);
        oci_free_statement($stmt);
        return [];
    }

    $results = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $results[] = normalizarFilaRestriccion($row);
    }

    oci_free_statement($stmt);
    return $results;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dni = $_POST['dni'];
    $oficina = $_POST['oficina'];

    // Conexión a la base de datos según la oficina seleccionada para los demás datos
    $conn = conectarBaseDeDatos($oficina);

    $result_cursor = oci_new_cursor($conn);
    $result_param = null;

    $sql_validar = "
    BEGIN
        zrxi.pkg_qweb.sp_get_usuario_valido(
            pic_dni => :pic_dni,
            poc_result => :poc_result,
            poc_cursor => :poc_cursor
        );
    END;
    ";

    $stmt_validar = oci_parse($conn, $sql_validar);
    oci_bind_by_name($stmt_validar, ":pic_dni", $dni);
    oci_bind_by_name($stmt_validar, ":poc_result", $result_param, 10);
    oci_bind_by_name($stmt_validar, ":poc_cursor", $result_cursor, -1, OCI_B_CURSOR);

    if (!oci_execute($stmt_validar)) {
        $error = oci_error($stmt_validar);
        echo "<div class='alert-modern'><i class='fas fa-exclamation-triangle'></i> Error en la consulta: " . htmlspecialchars($error['message']) . "</div>";
        oci_free_statement($stmt_validar);
        oci_close($conn);
        exit;
    }

    error_log("[v0] Result parameter value: " . var_export($result_param, true));

    if ($result_param == 1) {
        // User is valid - proceed with normal password change flow
        $cursor = oci_new_cursor($conn);

        $sql = "
        BEGIN
                zrxi.pkg_qweb.sp_get_list_usuariosbd(
                pic_dni => :pic_dni,
                poc_cursor => :poc_cursor
                );
        END;
        ";
     
        // Preparar la consulta
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":pic_dni", $dni);
        oci_bind_by_name($stmt, ":poc_cursor", $cursor, -1, OCI_B_CURSOR);

        // Ejecutar el procedimiento
        oci_execute($stmt);

        // Abrir el cursor para leer los datos devueltos
        oci_execute($cursor);

        // Recuperar los resultados del cursor
        $results = [];
        while ($row = oci_fetch_assoc($cursor)) {
            $results[] = $row;
        }

        // Liberar recursos
        oci_free_statement($stmt);
        oci_free_statement($cursor);

        // Si se encontraron resultados, devolver la tabla con los datos
        if (!empty($results)) {
            echo "<div class='results-container'>";
            echo "<h5 class='results-title'><i class='fas fa-check-circle'></i> Resultados Encontrados</h5>";
            echo "<div class='table-responsive'>";
            echo "<table class='modern-table'>
                    <thead>
                        <tr>
                            <th><i class='fas fa-id-card'></i> DNI</th>
                            <th><i class='fas fa-user'></i> NOMBRE</th>
                            <th><i class='fas fa-user-circle'></i> Usuario</th>
                            <th><i class='fas fa-building'></i> Oficina</th>                        
                            <th><i class='fas fa-cogs'></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>";

            // Recorrer los resultados y mostrarlos en la tabla
            foreach ($results as $row) {
                echo "<tr class='table-row-hover'>
                        <td><span class='dni-badge'>" . htmlspecialchars($row['DNI']) . "</span></td>
                        <td><strong>" . htmlspecialchars($row['NOMBRES']) . "</strong></td>
                        <td><span class='user-badge'>" . htmlspecialchars($row['USER_BD']) . "</span></td>
                        <td><span class='office-badge'>" . htmlspecialchars($row['OFICINA']) . "</span></td>
                        <td>
                            <!-- Formulario para enviar los datos al archivo cambio_clave.php -->
                            <form action='vista/cambio_clave.php' method='POST' style='margin: 0;'>
                                <input type='hidden' name='dni' value='" . htmlspecialchars($row['DNI']) . "'>
                                <input type='hidden' name='usuario' value='" . htmlspecialchars($row['USER_BD']) . "'>
                                <input type='hidden' name='oficina' value='" . htmlspecialchars($row['OFICINA']) . "'>
                                <input type='hidden' name='email' value='" . htmlspecialchars($row['EMAIL']) . "'>
                                <input type='hidden' name='nombres' value='" . htmlspecialchars($row['NOMBRES']) . "'>
                                <button type='submit' class='btn-change-password'>
                                    <i class='fas fa-key'></i> Cambiar Contraseña
                                </button>
                            </form>
                        </td>
                      </tr>";
            }
            echo "</tbody></table>";
            echo "</div>";
            echo "</div>";
        } else {
            echo "<div class='no-results'>
                    <i class='fas fa-exclamation-triangle'></i>
                    <h5>No se encontraron resultados</h5>
                    <p>No se encontraron usuarios para el DNI proporcionado en la oficina seleccionada.</p>
                  </div>";
        }

        // Liberar recursos
        oci_free_statement($stmt);
        oci_close($conn);

    } else if ($result_param == 0) {
        $sql_invalid = "
        BEGIN
            zrxi.pkg_qweb.sp_get_usuario_valido(
            pic_dni => :pic_dni,
            poc_result => :poc_result,
            poc_cursor => :poc_cursor
            );
        END;
        ";

        $stmt_invalid = oci_parse($conn, $sql_invalid);
        oci_bind_by_name($stmt_invalid, ":pic_dni", $dni);
        oci_bind_by_name($stmt_invalid, ":poc_result", $result_param, 10);
        oci_bind_by_name($stmt_invalid, ":poc_cursor", $result_cursor, -1, OCI_B_CURSOR);

        if (!oci_execute($stmt_invalid)) {
            $error = oci_error($stmt_invalid);
            echo "<div class='alert-error-container'>";
            echo "<div class='alert-error-header'>";
            echo "<i class='fas fa-exclamation-circle'></i>";
            echo "<h4>Error al procesar la solicitud</h4>";
            echo "</div>";
            echo "<div class='alert-error-message'>";
            echo htmlspecialchars($error['message']);
            echo "</div>";
            echo "</div>";
            oci_free_statement($stmt_invalid);
        } else {
            $invalid_results = [];

            if (@oci_execute($result_cursor)) {
                while ($row = @oci_fetch_assoc($result_cursor)) {
                    $invalid_results[] = normalizarFilaRestriccion($row);
                }

                $cursor_error = oci_error($result_cursor);
                if ($cursor_error) {
                    error_log("[recuperar_clave] Error leyendo cursor de restricciones: " . $cursor_error['message']);
                }
            } else {
                $cursor_error = oci_error($result_cursor);
                error_log("[recuperar_clave] Error ejecutando cursor de restricciones: " . $cursor_error['message']);
            }

            if (empty($invalid_results)) {
                $invalid_results = obtenerRestriccionesActivas($conn, $dni);
            }

            if (!empty($invalid_results)) {
                echo "<div class='alert-error-container' style='background: linear-gradient(135deg, #fef2f2 0%, #fde8e8 100%); border: 2px solid #dc2626;'>";
                echo "<div class='alert-error-header'>";
                echo "<i class='fas fa-ban' style='color: #dc2626;'></i>";
                echo "<h4 style='color: #dc2626;'>No puedes cambiar tu contraseña</h4>";
                echo "</div>";
                
                echo "<div class='alert-error-message' style='color: #7f1d1d; font-size: 0.95rem;'>";
                echo "<p><strong><i class='fas fa-info-circle'></i> Razón:</strong> Actualmente tienes restricciones que impiden el cambio de contraseña:</p>";
                echo "</div>";
                
                echo "<div class='error-details-container' style='background: white; border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(220, 38, 38, 0.15);'>";
                
                foreach ($invalid_results as $row) {
                    echo "<div class='detail-row' style='display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem; background: #f9fafb; border-radius: 10px; border-left: 4px solid #dc2626; margin-bottom: 1rem;'>";
                    
                    echo "<div style='display: flex; align-items: center; gap: 0.5rem;'>";
                    echo "<i class='fas fa-id-card' style='color: #dc2626; font-size: 1.1rem;'></i>";
                    echo "<span style='font-weight: 600; color: #374151;'>Documento:</span>";
                    echo "<span style='background: #dc2626; color: white; padding: 0.25rem 0.75rem; border-radius: 6px; font-weight: 600;'>" . htmlspecialchars($row['NRODOCUMENTO']) . "</span>";
                    echo "</div>";
                    
                    echo "<div style='display: flex; align-items: center; gap: 0.5rem;'>";
                    echo "<i class='fas fa-user' style='color: #7c3aed; font-size: 1rem;'></i>";
                    echo "<span style='font-weight: 600; color: #374151;'>Nombre:</span>";
                    echo "<span style='color: #6b7280;'>" . htmlspecialchars($row['NOMBRES']) . "</span>";
                    echo "</div>";
                    
                    echo "<div style='display: flex; align-items: center; gap: 0.5rem;'>";
                    echo "<i class='fas fa-exclamation-triangle' style='color: #f59e0b; font-size: 1rem;'></i>";
                    echo "<span style='font-weight: 600; color: #374151;'>Tipo de Restricción:</span>";
                    echo "<span style='background: #fca5a5; padding: 0.25rem 0.75rem; border-radius: 6px; color: #7f1d1d; font-weight: 500;'>" . htmlspecialchars($row['TIPO_AUSENCIA']) . "</span>";
                    echo "</div>";
                    
                    echo "<div style='display: flex; align-items: flex-start; gap: 0.5rem;'>";
                    echo "<i class='fas fa-file-alt' style='color: #059669; font-size: 1rem; margin-top: 0.25rem;'></i>";
                    echo "<span style='font-weight: 600; color: #374151;'>Detalles:</span>";
                    echo "<span style='color: #6b7280;'>" . htmlspecialchars($row['DETALLE']) . "</span>";
                    echo "</div>";
                    
                    if (isset($row['FECHAPERMISODESDE']) && $row['FECHAPERMISODESDE']) {
                        echo "<div style='display: flex; align-items: center; gap: 0.5rem;'>";
                        echo "<i class='fas fa-calendar-check' style='color: #0891b2; font-size: 1rem;'></i>";
                        echo "<span style='font-weight: 600; color: #374151;'>Válida desde:</span>";
                        echo "<span style='color: #6b7280;'>" . htmlspecialchars($row['FECHAPERMISODESDE']) . "</span>";
                        echo "</div>";
                    }
                    
                    if (isset($row['FECHAPERMISOHASTA']) && $row['FECHAPERMISOHASTA']) {
                        echo "<div style='display: flex; align-items: center; gap: 0.5rem;'>";
                        echo "<i class='fas fa-calendar-times' style='color: #dc2626; font-size: 1rem;'></i>";
                        echo "<span style='font-weight: 600; color: #374151;'>Válida hasta:</span>";
                        echo "<span style='color: #6b7280;'>" . htmlspecialchars($row['FECHAPERMISOHASTA']) . "</span>";
                        echo "</div>";
                    }
                    
                    echo "</div>";
                }
                
                echo "</div>";
                
                echo "<div style='margin-top: 1.5rem; padding: 1rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; border-radius: 8px; border: 1px solid rgba(245, 158, 11, 0.2);'>";
                echo "<p style='color: #92400e; margin: 0; display: flex; align-items: center; gap: 0.5rem;'>";
                echo "<i class='fas fa-lightbulb'></i>";
                echo "<strong>Próximos pasos:</strong> Por favor, ponte en contacto con Centro de Servicios o el departamento de RRHH para resolver esta restricción y poder cambiar tu contraseña.";
                echo "</p>";
                echo "</div>";
                
                echo "</div>";
            } else {
                echo "<div class='alert-error-container' style='background: #fef3c7; border: 2px solid #f59e0b;'>";
                echo "<div class='alert-error-header'>";
                echo "<i class='fas fa-exclamation-circle' style='color: #f59e0b;'></i>";
                echo "<h4 style='color: #92400e;'>Usuario no válido</h4>";
                echo "</div>";
                echo "<div class='alert-error-message' style='color: #92400e;'>";
                echo "El usuario no es válido para cambiar contraseña. No se encontraron detalles adicionales. Por favor, contacta con administración.";
                echo "</div>";
                echo "</div>";
            }

            oci_free_statement($stmt_invalid);
        }
    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/img/Logo2.png">
    <title>SUNARP - Servicio de Consultas Web</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" type="text/css" href="assets/estilos/css/Bootstrap/bootstrap.min.css" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Colores oficiales SUNARP */
            --sunarp-verde: #8EC33F;      /* Verde Sunarp - RGB: 142 195 63 */
            --sunarp-turquesa: #00A5A5;   /* Turquesa Sunarp - RGB: 0 165 165 */
            --sunarp-amarillo: #FFAF00;   /* Amarillo Sunarp - RGB: 255 175 0 */
            --sunarp-rojo: #E93219;       /* Rojo Sunarp - RGB: 233 50 25 */
            --sunarp-gris: #50605B;       /* Gris Sunarp - RGB: 80 96 91 */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
            display: flex; /* Added to make flexbox work with footer */
            flex-direction: column; /* Stack children vertically */
            min-height: 100vh; /* Ensure body takes at least full viewport height */
        }

        .main-container {
            min-height: 100vh;
            display: flex;
            flex: 1; /* Allow main content to grow and push footer down */
        }

        /* LADO IZQUIERDO - VERDE SUNARP */
        .green-side {
            flex: 1;
            background: linear-gradient(135deg, rgba(26, 166, 164, 0.8) 0%, rgba(142, 188, 69, 0.8) 100%);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .green-side::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .welcome-section {
            position: relative;
            z-index: 2;
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
            color: white;
        }

        .main-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 0 4px 8px rgba(0,0,0,0.2);
            line-height: 1.2;
        }

        .subtitle {
            font-size: 1.1rem;
            margin-bottom: 3rem;
            opacity: 0.95;
            font-weight: 400;
        }

        .illustration-container {
            margin: 3rem 0;
            position: relative;
            display: flex;
            justify-content: center;
        }

        .illustration-box {
            width: 300px;
            height: 200px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sunarp-logo-illustration {
            width: 380px;
            height: auto;
            position: relative;
            z-index: 2;
        }

        .floating-icons {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 3;
        }

        .floating-icon {
            position: absolute;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            animation: bounce 2s ease-in-out infinite;
        }

        .floating-icon:nth-child(1) {
            top: -20px;
            left: -20px;
            background: var(--sunarp-amarillo);
            animation-delay: 0s;
        }

        .floating-icon:nth-child(2) {
            top: -10px;
            right: -30px;
            background: var(--sunarp-turquesa);
            animation-delay: 0.5s;
        }

        .floating-icon:nth-child(3) {
            bottom: -15px;
            left: 20px;
            background: var(--sunarp-rojo);
            animation-delay: 1s;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .recovery-section {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2rem;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .recovery-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .systems-text {
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .btn-recover {
            background: linear-gradient(135deg, var(--sunarp-turquesa) 0%, #008A8A 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 165, 165, 0.4);
        }

        .btn-recover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 165, 165, 0.6);
        }

        /* LADO DERECHO - LOGIN */
        .login-side {
            flex: 1;
            background: white;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .login-container {
            max-width: 500px;
            margin: 0 auto;
            width: 100%;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-container {
            display: inline-block;
            position: relative;
        }

        .logo-img {
            width: 250px;
            height: auto;
            margin: 0 auto 1rem;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .login-title {
            text-align: center;
            font-size: 1.3rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 2rem;
            line-height: 1.4;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-control-modern {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-control-modern:focus {
            outline: none;
            border-color: var(--sunarp-verde);
            background: white;
            box-shadow: 0 0 0 3px rgba(142, 195, 63, 0.1);
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .select-modern {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            background: #f9fafb;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .select-modern:focus {
            outline: none;
            border-color: var(--sunarp-verde);
            background: white;
            box-shadow: 0 0 0 3px rgba(142, 195, 63, 0.1);
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .checkbox-modern {
            width: 18px;
            height: 18px;
            accent-color: var(--sunarp-verde);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--sunarp-verde) 0%, #7AB82F 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(142, 195, 63, 0.4);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(142, 195, 63, 0.6);
        }

        .forgot-password {
            text-align: center;
            margin-top: 1.5rem;
        }

        .forgot-link {
            color: var(--sunarp-turquesa);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #008A8A;
            text-decoration: underline;
        }

        .alert-modern {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: none;
            background: #fef2f2;
            color: var(--sunarp-rojo);
            border-left: 4px solid var(--sunarp-rojo);
        }

        /* MODAL STYLES */

        .modal-dialog {
            margin: 1rem auto;
            max-width: none;
            display: flex;
            align-items: center;
            min-height: calc(50vh - 2rem);
            width: 1200px;
        }

        .modal-dialog .modal-content {
            margin: auto;
        }
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 25px 80px rgba(0,0,0,0.2);
        }

        .modal-header {
            border-bottom: 1px solid #e5e7eb;
            padding: 1.5rem 2rem;
            border-radius: 20px 20px 0 0;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--sunarp-verde);
        }

        .modal-body {
            padding: 2rem;
        }

        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            text-align: center;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(142, 188, 69, 0.2);
            border-top: 4px solid #8EBC45;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: #6b7280;
            font-size: 1.1rem;
        }

        /* RESULTS STYLES */
        .results-container {
            margin-top: 1rem;
        }

        .results-title {
            color: var(--sunarp-verde);
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .modern-table thead {
            background: linear-gradient(135deg, var(--sunarp-verde) 0%, #7AB82F 100%);
            color: white;
        }

        .modern-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .modern-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .table-row-hover:hover {
            background: #f9fafb;
        }

        .dni-badge, .user-badge, .office-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .dni-badge {
            background: rgba(0, 165, 165, 0.1);
            color: var(--sunarp-turquesa);
        }

        .user-badge {
            background: rgba(80, 96, 91, 0.1);
            color: var(--sunarp-gris);
        }

        .office-badge {
            background: rgba(255, 175, 0, 0.1);
            color: var(--sunarp-amarillo);
        }

        .btn-change-password {
            background: linear-gradient(135deg, var(--sunarp-amarillo) 0%, #E6A000 100%);
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            color: white;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-change-password:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 175, 0, 0.4);
        }

        .no-results {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }

        .no-results i {
            font-size: 3rem;
            color: var(--sunarp-amarillo);
            margin-bottom: 1rem;
        }

        .no-results h5 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }

        /* Added comprehensive styling for error details display */
        .alert-error-container {
            background: linear-gradient(135deg, #fef2f2 0%, #fde8e8 100%);
            border: 2px solid var(--sunarp-rojo);
            border-radius: 16px;
            padding: 2rem;
            margin-top: 1.5rem;
        }

        .alert-error-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(233, 50, 25, 0.2);
        }

        .alert-error-header i {
            font-size: 1.8rem;
            color: var(--sunarp-rojo);
        }

        .alert-error-header h4 {
            margin: 0;
            color: var(--sunarp-rojo);
            font-size: 1.3rem;
            font-weight: 700;
        }

        .alert-error-message {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .error-details-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(220, 38, 38, 0.15);
        }

        .error-detail-item {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .detail-row {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 10px;
            border-left: 4px solid #dc2626;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .detail-row:hover {
            background: #f3f4f6;
            transform: translateX(4px);
        }

        .detail-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--sunarp-gris);
            min-width: 150px;
            font-size: 0.9rem;
        }

        .detail-label i {
            color: var(--sunarp-verde);
            font-size: 1.1rem;
        }

        .detail-value {
            flex: 1;
            color: #374151;
            font-weight: 500;
            word-break: break-word;
            padding: 0.5rem 0;
        }

        .no-details-message {
            text-align: center;
            color: #9ca3af;
            padding: 2rem;
            font-style: italic;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .green-side, .login-side {
                flex: none;
            }
            
            .main-title {
                font-size: 2rem;
            }
            
            .login-card {
                padding: 1.5rem;
            }
            
            .modal-dialog {
                margin: 0.5rem auto;
                max-width: calc(50vw - 1rem);
                min-height: calc(50vh - 1rem);
            }
        }

        /* Footer styles */
        .footer {
            background: linear-gradient(135deg, var(--sunarp-gris) 0%, #3a4540 100%);
            color: white;
            padding: 1.5rem 2rem;
            text-align: center;
            border-top: 3px solid var(--sunarp-verde);
            margin-top: auto; /* Push footer to the bottom */
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .footer-icon {
            font-size: 1.2rem;
            color: var(--sunarp-verde);
        }

        .footer-text {
            font-size: 0.95rem;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .footer-separator {
            width: 1px;
            height: 20px;
            background: rgba(255, 255, 255, 0.3);
            margin: 0 0.75rem;
        }

        .footer-copyright {
            font-size: 0.85rem;
            opacity: 0.9;
            color: rgba(255, 255, 255, 0.9);
        }

        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                gap: 0.5rem;
            }

            .footer-separator {
                display: none;
            }

            .footer {
                padding: 1rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="green-side">
            <div class="welcome-section">
                <h1 class="main-title">MÓDULO DE CONSULTAS DE APOYO PARA EL SISTEMA REGISTRAL Y DATA HISTÓRICA</h1>
                
                <p class="subtitle">Las opciones de consulta disponibles estarán de acuerdo a los perfiles de usuario asignados en los Sistemas Registrales.</p>

                <div class="illustration-container">
                    <div class="illustration-box">
                        <img src="assets/img/qweb.png" alt="SUNARP" class="sunarp-logo-illustration">
                        <div class="floating-icons">
                            <div class="floating-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="floating-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="floating-icon">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="recovery-section">
                    <h3 class="recovery-title">OPCIÓN DE RECUPERACIÓN DE CONTRASEÑAS:</h3>
                    <p class="systems-text">(SIR, SIR_RPV, SPR, CONSULTA REGISTRAL, SARP, SPRN, SGTD, SCUNAC, SIGESAR)</p>
                    
                    <button class="btn-recover" data-toggle="modal" data-target="#forgotPasswordModal">
                        <i class="fas fa-key"></i> RECUPERAR CONTRASEÑA
                    </button>
                </div>
            </div>
        </div>
        
        <!-- LADO DERECHO - LOGIN -->
        <div class="login-side">
            <div class="login-container">
                <div class="logo-section">
                    <div class="logo-container">
                        <img src="assets/img/Logo2.png" alt="SUNARP Logo" class="logo-img">
                    </div>
                </div>

                <div class="login-card">
                    <h4 class="login-title">Ingrese su Usuario y Contraseña para acceder al módulo.</h4>
                    
                    <?php if ($error): ?>
                        <div class="alert-modern">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="control/validar_usuario.php" method="post" name="form01" id="form01">
                        <div class="form-group">
                            <input type="text" class="form-control-modern" name="usuario" id="usuario" placeholder="👤 Usuario" required>
                        </div>
                        
                        <div class="form-group">
                            <div class="password-container">
                                <input type="password" class="form-control-modern" id="password" name="password" placeholder="🔒 Contraseña" required>
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-building"></i> Seleccione la oficina
                            </label>
                            <select class="select-modern" name="db" required>
                                <?php include('control/oficinas.php'); ?>
                                <?php foreach ($oficinas as $nombre => $conexion): ?>
                                    <option value="<?php echo htmlspecialchars($conexion); ?>"><?php echo htmlspecialchars($nombre); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="checkbox-container">
                            <input type="checkbox" class="checkbox-modern" id="rememberMe">
                            <label for="rememberMe" style="color: #6b7280;">Recordar sesión</label>
                        </div>
                        
                        <button type="submit" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i> Ingresar
                        </button>
                    </form>

                    <div class="forgot-password">
                        <a href="#" class="forgot-link" data-toggle="modal" data-target="#forgotPasswordModal">
                            <i class="fas fa-question-circle"></i> Recuperar mi contraseña
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL RECUPERAR CONTRASEÑA -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" role="dialog" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">
                        <i class="fas fa-key"></i> Recuperar Contraseña
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; color: #6b7280;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="dniForm">
                        <div class="form-group">
                            <label for="dni" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-id-card"></i> Número de DNI
                            </label>
                            <input type="text" class="form-control-modern" id="dni" name="dni" placeholder="Ingrese su DNI (8 dígitos)" required
                                   maxlength="8" pattern="\d{8}" 
                                   oninput="this.value = this.value.replace(/\s/g, '').replace(/\D/g, '');">
                        </div>
                        
                        <div class="form-group">
                            <label for="oficina" style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-building"></i> Oficina Registral
                            </label>
                            <select class="select-modern" id="oficina" name="oficina" required>
                                <option value="ICA">ICA</option>
                                <option value="CHINCHA">CHINCHA</option>
                                <option value="PISCO">PISCO</option>
                                <option value="NASCA">NASCA</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-login" id="consultarBtn" style="margin-top: 1rem;">
                            <i class="fas fa-search"></i> Consultar Usuario
                        </button>
                    </form>

                    <div id="loading" class="loading-container" style="display: none;">
                        <img src="assets/img/rel.gif" alt="Cargando..." class="loading-spinner" />
                        <div class="loading-text">
                            <i class="fas fa-spinner fa-spin"></i> Consultando información...
                        </div>
                    </div>

                    <div id="resultados"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer with copyright and branding -->
    <footer class="footer">
        <div class="footer-content">
            <i class="fas fa-cogs footer-icon"></i>
            <span class="footer-text">Query & Workflow Engine for Business</span>
            <span class="footer-separator"></span>
            <span class="footer-copyright">&copy; 2025 SUNARP. Todos los derechos reservados.</span>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/estilos/js/jquery-3.6.0.min.js"></script>
    <script src="assets/estilos/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // AJAX para el formulario de recuperación
        $('#dniForm').on('submit', function(e) {
            e.preventDefault();

            var consultarBtn = $('#consultarBtn');
            var oficinaSelect = $('#oficina');
            var dniInput = $('#dni');

            // Desactivar controles
            consultarBtn.prop('disabled', true);
            oficinaSelect.prop('disabled', true);
            dniInput.prop('disabled', true);
            
            // Cambiar texto del botón
            consultarBtn.html('<i class="fas fa-spinner fa-spin"></i> Consultando...');

            // Mostrar loading
            $('#loading').show();
            $('#resultados').hide();

            var dni = $('#dni').val();
            var oficina = $('#oficina').val();

            $.ajax({
                url: '',
                method: 'POST',
                data: {dni: dni, oficina: oficina },
                success: function(response) {
                    $('#resultados').html(response).show();
                    $('#loading').hide();
                    
                    // Eliminar el contenedor solo dentro del modal
                    $('#forgotPasswordModal .main-container').remove();  // Elimina el contenedor principal
                    $('#forgotPasswordModal .green-side').remove();      // Elimina la sección verde
                    $('#forgotPasswordModal .login-side').remove();     // Elimina la sección de login
                    
                    // Rehabilitar controles después de 3 segundos
                    setTimeout(function() {
                        consultarBtn.prop('disabled', false);
                        oficinaSelect.prop('disabled', false);
                        dniInput.prop('disabled', false);
                        consultarBtn.html('<i class="fas fa-search"></i> Nueva Consulta');
                    }, 3000);
                },
                error: function() {
                    $('#loading').hide();
                    $('#resultados').html('<div class="no-results"><i class="fas fa-exclamation-triangle"></i><h5>Error en la consulta</h5><p>Ocurrió un error al procesar la solicitud. Intente nuevamente.</p></div>').show();
                    
                    // Rehabilitar controles
                    setTimeout(function() {
                        consultarBtn.prop('disabled', false);
                        oficinaSelect.prop('disabled', false);
                        dniInput.prop('disabled', false);
                        consultarBtn.html('<i class="fas fa-search"></i> Consultar Usuario');
                    }, 2000);
                }
            });
        });

        // Limpiar modal al cerrarlo
        $('#forgotPasswordModal').on('hidden.bs.modal', function () {
            $('#dniForm')[0].reset();
            $('#resultados').empty();
            $('#loading').hide();
            $('#consultarBtn').prop('disabled', false).html('<i class="fas fa-search"></i> Consultar Usuario');
            $('#oficina, #dni').prop('disabled', false);
        });
    </script>
</body>
</html>
