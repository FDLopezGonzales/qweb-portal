<?php
require_once __DIR__ . '/../control/config_env.php';
$correoSoporte = qweb_config('QWEB_CORREO_SOPORTE', 'soporte@ejemplo.gob.pe');
include_once("conexion.php");

if (empty($_POST['dni']) && empty($_POST['usuario']) && empty($_POST['oficina']) && empty($_POST['correo'])) {
    // Si no se reciben los datos, cerrar la sesión y redirigir al index.php
    session_unset();  // Elimina todas las variables de sesión
    session_destroy();  // Destruye la sesión
    header("Location: ../index.php");  // Redirigir al index
    exit();  // Detener el script para que no ejecute más código
}

// Función para ocultar parte del correo (mover al inicio para que esté disponible en todo el código)
function ocultarCorreo($correo) {
    $partes = explode('@', $correo);
    $nombre = $partes[0];
    $dominio = $partes[1];

    //
    $partes = explode('@', $correo);
    $nombre = $partes[0];
    $dominio = $partes[1];

    // Ocultar parte del nombre y del dominio (por ejemplo, dejando solo los primeros 3 caracteres del nombre y 3 del dominio)
    $nombreOcultado = substr($nombre, 0, 5) . str_repeat('*', strlen($nombre) - 5);

    return $nombreOcultado . '@' . $dominio;
}

function getCustomErrorMessage($oracleError) {
    $errorMessages = [
        'ORA-20001' => 'La contraseña no puede ser igual al nombre de usuario',
        'ORA-20002' => 'La nueva contraseña no puede ser igual a la anterior',
        'ORA-20003' => 'La contraseña debe tener al menos 6 caracteres',
        'ORA-20004' => 'La contraseña debe contener al menos: 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial',
        'ORA-28007' => 'No puede reutilizar una contraseña anterior. Por favor, elija una contraseña diferente'
    ];
    
    // Extract all ORA error codes from the message
    if (preg_match_all('/ORA-(\d+)/', $oracleError, $matches)) {
        foreach ($matches[1] as $errorNumber) {
            $errorCode = 'ORA-' . $errorNumber;
            // Check if it's one of our known validation errors
            if (isset($errorMessages[$errorCode])) {
                return [
                    'code' => $errorCode,
                    'message' => $errorMessages[$errorCode],
                    'isKnownError' => true
                ];
            }
        }
    }
    
    return [
        'code' => 'UNKNOWN',
        'message' => 'Ha ocurrido un error inesperado. Contacte a soporte.',
        'isKnownError' => false
    ];
}

function hasExceededMaxAttempts() {
    if (!isset($_SESSION['password_attempts'])) {
        $_SESSION['password_attempts'] = 0;
    }
    return $_SESSION['password_attempts'] >= 5;
}

function incrementPasswordAttempts() {
    if (!isset($_SESSION['password_attempts'])) {
        $_SESSION['password_attempts'] = 0;
    }
    $_SESSION['password_attempts']++;
}

function resetPasswordAttempts() {
    $_SESSION['password_attempts'] = 0;
}

function renderPageWithStyles($content) {
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/img/Logo2.png">
    <title>SUNARP - Cambio de Contraseña</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Colores oficiales SUNARP */
            --sunarp-verde: #8EC33F;      /* Verde Sunarp */
            --sunarp-turquesa: #00A5A5;   /* Turquesa Sunarp */
            --sunarp-amarillo: #FFAF00;   /* Amarillo Sunarp */
            --sunarp-rojo: #E93219;       /* Rojo Sunarp */
            --sunarp-gris: #50605B;       /* Gris Sunarp */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: \'Inter\', sans-serif;
            background: linear-gradient(135deg, rgba(26, 166, 164, 0.8) 0%, rgba(142, 188, 69, 0.8) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: \'\';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .main-container {
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 2;
        }

        .form-card {
            background: white;
            border-radius: 25px;
            padding: 2.5rem;
            box-shadow: 0 25px 80px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .success-card {
            border-top: 5px solid var(--sunarp-verde);
        }

        .error-card {
            border-top: 5px solid var(--sunarp-rojo);
        }

        .header-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-container {
            margin-bottom: 1.5rem;
        }

        .logo-img {
            width: 80px;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--sunarp-gris);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .success-title {
            color: var(--sunarp-verde);
        }

        .error-title {
            color: var(--sunarp-rojo);
        }

        .form-subtitle {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.5;
        }

        .modern-form {
            margin: 2rem 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--sunarp-gris);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control-modern {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
            font-family: inherit;
        }

        .form-control-modern:focus {
            outline: none;
            border-color: var(--sunarp-verde);
            background: white;
            box-shadow: 0 0 0 3px rgba(142, 195, 63, 0.1);
        }

        .form-control-modern.error {
            border-color: var(--sunarp-rojo);
            background: #fef2f2;
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
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--sunarp-verde);
        }

        .btn-primary-modern {
            width: 100%;
            padding: 15px 20px;
            background: linear-gradient(135deg, var(--sunarp-verde) 0%, #7AB82F 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(142, 195, 63, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: inherit;
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(142, 195, 63, 0.6);
        }

        .btn-primary-modern:active {
            transform: translateY(0);
        }

        /* Alertas */
        .info-alert, .success-alert, .error-alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .info-alert {
            background: rgba(0, 165, 165, 0.1);
            border: 1px solid rgba(0, 165, 165, 0.2);
            color: var(--sunarp-turquesa);
        }

        .success-alert {
            background: rgba(142, 195, 63, 0.1);
            border: 1px solid rgba(142, 195, 63, 0.2);
            color: var(--sunarp-verde);
        }

        .success-alert.large {
            padding: 20px;
            font-size: 1rem;
        }

        .error-alert {
            background: rgba(233, 50, 25, 0.1);
            border: 1px solid rgba(233, 50, 25, 0.2);
            color: var(--sunarp-rojo);
        }

        .error-alert.large {
            padding: 20px;
            font-size: 1rem;
        }

        .email-masked {
            font-family: \'Courier New\', monospace;
            background: rgba(0, 165, 165, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
        }

        .user-highlight {
            background: rgba(142, 195, 63, 0.2);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            color: var(--sunarp-verde);
        }

        .help-text {
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .button-group {
            margin: 1.5rem 0;
        }

        /* Loading */
        #loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
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

        #loading p {
            color: var(--sunarp-gris);
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .form-card {
                padding: 2rem 1.5rem;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
            
            .logo-img {
                width: 60px;
            }
        }

        @media (max-width: 480px) {
            .form-card {
                padding: 1.5rem 1rem;
            }
            
            .form-title {
                font-size: 1.3rem;
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div id="loading" style="display: none;">
        <div class="loading-spinner"></div>
        <p>Procesando solicitud...</p>
    </div>

    ' . $content . '

    <script>
        function togglePassword(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordField.type === \'password\') {
                passwordField.type = \'text\';
                toggleIcon.classList.remove(\'fa-eye\');
                toggleIcon.classList.add(\'fa-eye-slash\');
            } else {
                passwordField.type = \'password\';
                toggleIcon.classList.remove(\'fa-eye-slash\');
                toggleIcon.classList.add(\'fa-eye\');
            }
        }

        window.addEventListener(\'load\', function () {
            document.getElementById(\'loading\').style.display = \'none\';
        });

        document.addEventListener(\'DOMContentLoaded\', function() {
            const firstInput = document.querySelector(\'input[type="text"], input[type="password"]\');
            if (firstInput) {
                firstInput.focus();
            }
        });

        document.addEventListener(\'DOMContentLoaded\', function() {
            const codigoInput = document.getElementById(\'codigo\');
            if (codigoInput) {
                codigoInput.addEventListener(\'input\', function(e) {
                    // Remove spaces and convert to uppercase
                    this.value = this.value.replace(/\\s/g, \'\').toUpperCase();
                });
            }
        });
    </script>
</body>
</html>';
}

// Inicializar variables para evitar la regeneración de código
session_start();
if (!isset($_SESSION['codigo_generado'])) {
    $_SESSION['codigo_generado'] = null; // No hay código generado inicialmente
}

echo "<script>document.getElementById('loading').style.display = 'flex';</script>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibir valores desde el formulario
    $dni = $_POST['dni'] ?? null;
    $usuario = $_POST['usuario'] ?? null;
    $oficina = $_POST['oficina'] ?? null;  // La oficina debe venir desde el formulario de login
    $correo = $_POST['email'] ?? $_POST['correo'] ?? null; // Puede venir como 'email' o 'correo'
    $codigoIngresado = $_POST['codigo'] ?? null;
    $nuevaContraseña = $_POST['nueva_contraseña'] ?? null;
    $confirmarContraseña = $_POST['confirmar_contraseña'] ?? null;
    $nombres = $_POST['nombres'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Guardar los valores en la sesión para que se mantengan disponibles
    $_SESSION['dni'] = $dni;
    $_SESSION['usuario'] = $usuario;
    $_SESSION['oficina'] = $oficina;
    $_SESSION['email'] = $correo;
    $_SESSION['nombres'] = $nombres;
    $_SESSION['ip'] = $ip;

    // Generar correo oculto (disponible para todas las partes del código)
    $correoOcultado = '';
    if ($correo) {
        $correoOcultado = ocultarCorreo($correo);
    } elseif (isset($_SESSION['email']) && $_SESSION['email']) {
        $correoOcultado = ocultarCorreo($_SESSION['email']);
    }

    // Conexión a la base de datos de ICA para generar y obtener el código de verificación
    $connIca = conectarBaseDeDatos('ICA');

    if (isset($dni) && $_SESSION['codigo_generado'] === null) {
        // <CHANGE> Conectar a la BD de la oficina del usuario en lugar de 'ICA'
        $conn = conectarBaseDeDatos($oficina);

        // Generar el código de seguridad si aún no está generado
        $sqlGenerateCode = "BEGIN
                                zrxi.pkg_qweb.sp_gen_codigoseguridad(pic_dni => :pic_dni,
                                                                     pic_ipadress => :pic_ipadress);
                             END;";
        $stmtGenerateCode = oci_parse($conn, $sqlGenerateCode);
        oci_bind_by_name($stmtGenerateCode, ":pic_dni", $dni);
        oci_bind_by_name($stmtGenerateCode, ":pic_ipadress", $ip);
        oci_execute($stmtGenerateCode);

        // Obtener el código generado con el DNI
        $sqlGetCode = "BEGIN
                          :result := zrxi.pkg_qweb.sf_get_codigoseguridad(pic_dni => :pic_dni);
                       END;";
        $stmtGetCode = oci_parse($conn, $sqlGetCode);
        oci_bind_by_name($stmtGetCode, ":pic_dni", $dni);
        oci_bind_by_name($stmtGetCode, ":result", $codeGenerated, 32);  // Definir el tamaño del parámetro
        oci_execute($stmtGetCode);

        $_SESSION['codigo_generado'] = $codeGenerated; // Guardamos el código en la sesión

        // Enviar el código al correo
        $subject = "QWEB: Solicitud de cambio de clave - " . $usuario;
        $recipient = $correo;
        $user = $usuario;
        $nomapellido = $nombres;
        $audit = $ip;

        $sqlSendMail = "
                        BEGIN
                            zrxi.pkg_qweb.sp_sendmail_msg_body(
                                pic_subject => :pic_subject,
                                pic_recipient => :pic_recipient,
                                pic_user => :pic_user,
                                pic_codigo => :pic_codigo,
                                pic_nomapellido => :pic_nomapellido,  -- Nombre completo
                                pic_audit => :pic_audit  -- Dirección IP
                            );
                        END;
                    ";
        $stmtSendMail = oci_parse($conn, $sqlSendMail);
        oci_bind_by_name($stmtSendMail, ":pic_subject", $subject);
        oci_bind_by_name($stmtSendMail, ":pic_recipient", $recipient);
        oci_bind_by_name($stmtSendMail, ":pic_user", $user);
        oci_bind_by_name($stmtSendMail, ":pic_codigo", $codeGenerated);
        oci_bind_by_name($stmtSendMail, ":pic_nomapellido", $nomapellido);  // Pasar el nombre completo
        oci_bind_by_name($stmtSendMail, ":pic_audit", $audit);
        oci_execute($stmtSendMail);

        // Liberar recursos
        oci_free_statement($stmtGenerateCode);
        oci_free_statement($stmtGetCode);
        oci_free_statement($stmtSendMail);
        oci_close($conn);

        echo "<script>document.getElementById('loading').style.display = 'none';</script>";

        resetPasswordAttempts();

        // Mostrar el formulario para ingresar el código
        $codeVerificationContent = "
            <div class='main-container'>
                <div class='form-card'>
                    <div class='header-section'>
                        <div class='logo-container'>
                            <img src='../assets/img/Logo2.png' alt='SUNARP Logo' class='logo-img'>
                        </div>
                        <h2 class='form-title'>
                            <i class='fas fa-shield-alt'></i>
                            Verificación de Código
                        </h2>
                        <p class='form-subtitle'>Ingrese el código de verificación enviado a su correo electrónico</p>
                    </div>
                    
                    <div class='info-alert'>
                        <i class='fas fa-envelope'></i>
                        <div>
                            <strong>Código enviado a:</strong><br>
                            <span class='email-masked'>$correoOcultado</span>
                        </div>
                    </div>
                    
                    <form method='POST' class='modern-form'>
                        <div class='form-group'>
                            <label for='codigo' class='form-label'>
                                <i class='fas fa-key'></i> Código de Verificación
                            </label>
                            <input type='text' name='codigo' id='codigo' class='form-control-modern' 
                                   placeholder='Ingrese el código de 6 dígitos' required maxlength='6' 
                                   pattern='[^ ]*' style='text-transform:uppercase;'>
                        </div>
                        
                        <!-- Campos ocultos -->
                        <input type='hidden' name='dni' value='" . htmlspecialchars($dni) . "'>
                        <input type='hidden' name='usuario' value='" . htmlspecialchars($usuario) . "'>
                        <input type='hidden' name='oficina' value='" . htmlspecialchars($oficina) . "'>
                        <input type='hidden' name='correo' value='" . htmlspecialchars($correo) . "'>
                        
                        <button type='submit' class='btn-primary-modern'>
                            <i class='fas fa-check-circle'></i>
                            Verificar Código
                        </button>
                    </form>
                    
                    <div class='help-text'>
                        <i class='fas fa-info-circle'></i>
                        Si no recibió el código, revise su carpeta de spam o correo no deseado.
                    </div>
                </div>
            </div>
        ";
        renderPageWithStyles($codeVerificationContent);

    } else if ($codigoIngresado) {
        // Verificar si el código ingresado es correcto
        if ($codigoIngresado === $_SESSION['codigo_generado']) {
            // Recuperar los valores desde $_POST (ya que se pasan como campos ocultos)
            $dni = $_POST['dni'];
            $usuario = $_POST['usuario'];
            $oficina = $_POST['oficina'];
            $correo = $_POST['correo'];
            $ip = $_SESSION['ip'];
            $nombres = $_SESSION['nombres'];

            // Ahora proceder con el formulario para cambiar la contraseña
            $newPasswordFormContent = "
                <div class='main-container'>
                    <div class='form-card'>
                        <div class='header-section'>
                            <div class='logo-container'>
                                <img src='../assets/img/Logo2.png' alt='SUNARP Logo' class='logo-img'>
                            </div>
                            <h2 class='form-title'>
                                <i class='fas fa-lock'></i>
                                Nueva Contraseña
                            </h2>
                            <p class='form-subtitle'>Ingrese su nueva contraseña para el usuario: <strong>$usuario</strong></p>
                        </div>
                        
                        <div class='success-alert'>
                            <i class='fas fa-check-circle'></i>
                            <span>Código verificado correctamente</span>
                        </div>
                        
                        <form method='POST' class='modern-form'>
                            <div class='form-group'>
                                <label for='nueva_contraseña' class='form-label'>
                                    <i class='fas fa-key'></i> Nueva Contraseña
                                </label>
                                <div class='password-container'>
                                    <input type='password' name='nueva_contraseña' id='nueva_contraseña' 
                                           class='form-control-modern' placeholder='Ingrese su nueva contraseña' required>
                                    <button type='button' class='password-toggle' onclick='togglePassword(\"nueva_contraseña\", \"toggleIcon1\")'>
                                        <i class='fas fa-eye' id='toggleIcon1'></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class='form-group'>
                                <label for='confirmar_contraseña' class='form-label'>
                                    <i class='fas fa-lock'></i> Confirmar Contraseña
                                </label>
                                <div class='password-container'>
                                    <input type='password' name='confirmar_contraseña' id='confirmar_contraseña' 
                                           class='form-control-modern' placeholder='Confirme su nueva contraseña' required>
                                    <button type='button' class='password-toggle' onclick='togglePassword(\"confirmar_contraseña\", \"toggleIcon2\")'>
                                        <i class='fas fa-eye' id='toggleIcon2'></i>
                                    </button>
                                </div>
                            </div>

                            <input type='hidden' name='usuario' value='" . htmlspecialchars($usuario) . "'>
                            <input type='hidden' name='oficina' value='" . htmlspecialchars($oficina) . "'>

                            <button type='submit' class='btn-primary-modern'>
                                <i class='fas fa-save'></i>
                                Cambiar Contraseña
                            </button>
                        </form>
                        
                        <div class='help-text'>
                            <i class='fas fa-info-circle'></i>
                            La contraseña debe ser segura y fácil de recordar para usted.
                        </div>
                    </div>
                </div>
            ";
            renderPageWithStyles($newPasswordFormContent);
        } else {
            // AQUÍ ESTÁ LA CORRECCIÓN: Regenerar el correo oculto para el mensaje de error
            // Obtener el correo desde $_POST o $_SESSION
            $correoParaOcultar = $_POST['correo'] ?? $_SESSION['email'] ?? '';
            $correoOcultado = $correoParaOcultar ? ocultarCorreo($correoParaOcultar) : 'correo@dominio.com';
            
            // Show the form again to enter the code
            $codeVerificationErrorContent = "
                <div class='main-container'>
                    <div class='form-card'>
                        <div class='header-section'>
                            <div class='logo-container'>
                                <img src='../assets/img/Logo2.png' alt='SUNARP Logo' class='logo-img'>
                            </div>
                            <h2 class='form-title'>
                                <i class='fas fa-shield-alt'></i>
                                Verificación de Código
                            </h2>
                            <p class='form-subtitle'>Ingrese el código de verificación enviado a su correo electrónico</p>
                        </div>
                        
                        <div class='error-alert'>
                            <i class='fas fa-exclamation-triangle'></i>
                            <span>Código incorrecto. Por favor, intente de nuevo.</span>
                        </div>
                        
                        <div class='info-alert'>
                            <i class='fas fa-envelope'></i>
                            <div>
                                <strong>Código enviado a:</strong><br>
                                <span class='email-masked'>$correoOcultado</span>
                            </div>
                        </div>
                        
                        <form method='POST' class='modern-form'>
                            <div class='form-group'>
                                <label for='codigo' class='form-label'>
                                    <i class='fas fa-key'></i> Código de Verificación
                                </label>
                                <input type='text' name='codigo' id='codigo' class='form-control-modern error' 
                                       value='$codigoIngresado' placeholder='Ingrese el código de 6 dígitos' 
                                       required maxlength='6' pattern='[^ ]*' style='text-transform:uppercase;'>
                            </div>
                            
                            <input type='hidden' name='dni' value='" . htmlspecialchars($dni) . "'>
                            <input type='hidden' name='usuario' value='" . htmlspecialchars($usuario) . "'>
                            <input type='hidden' name='oficina' value='" . htmlspecialchars($oficina) . "'>
                            <input type='hidden' name='correo' value='" . htmlspecialchars($correoParaOcultar) . "'>
                            
                            <button type='submit' class='btn-primary-modern'>
                                <i class='fas fa-check-circle'></i>
                                Verificar Código
                            </button>
                        </form>
                        
                        <div class='help-text'>
                            <i class='fas fa-info-circle'></i>
                            Si no recibió el código, revise su carpeta de spam o correo no deseado.
                        </div>
                    </div>
                </div>
            ";
            renderPageWithStyles($codeVerificationErrorContent);
        }
    }

    if ($nuevaContraseña && $confirmarContraseña) {
        if ($nuevaContraseña === $confirmarContraseña) {
            // Check if user has exceeded maximum attempts
            if (hasExceededMaxAttempts()) {
                $maxAttemptsContent = "
                    <div class='main-container'>
                        <div class='form-card error-card'>
                            <div class='header-section'>
                                <div class='logo-container'>
                                    <img src='../assets/img/Logo2.png' alt='SUNARP Logo' class='logo-img'>
                                </div>
                                <h2 class='form-title error-title'>
                                    <i class='fas fa-ban'></i>
                                    Acceso Bloqueado
                                </h2>
                                <p class='form-subtitle'>Se ha alcanzado el límite máximo de intentos</p>
                            </div>
                            
                            <div class='error-alert large'>
                                <i class='fas fa-shield-alt'></i>
                                <div>
                                    <strong>Máximo de intentos alcanzado</strong><br>
                                    Ha excedido el número máximo de intentos permitidos (5). Por motivos de seguridad, 
                                    el proceso ha sido bloqueado temporalmente.
                                </div>
                            </div>
                            
                            <div class='info-alert'>
                                <i class='fas fa-clock'></i>
                                <div>
                                    <strong>¿Qué hacer ahora?</strong><br>
                                    Puede intentar nuevamente o contactar con soporte técnico si necesita ayuda inmediata.
                                </div>
                            </div>
                            
                            <div class='button-group'>
                                <form action='../index.php' method='GET' style='margin: 0;'>
                                    <button type='submit' class='btn-primary-modern'>
                                        <i class='fas fa-home'></i>
                                        Volver al Inicio
                                    </button>
                                </form>
                            </div>
                            
                            <div class='help-text'>
                                <i class='fas fa-envelope'></i>
                                ¿Necesita ayuda? Contacte: <strong><?= htmlspecialchars($correoSoporte) ?></strong>
                            </div>
                        </div>
                    </div>";
                
                renderPageWithStyles($maxAttemptsContent);
                
                // Clear session data
                session_unset();
                session_destroy();
                exit();
            }

            // Recuperar los valores desde la sesión
            $dni = $_SESSION['dni'];
            $usuario = $_SESSION['usuario'];
            $oficina = $_SESSION['oficina'];
            $correo = $_SESSION['correo'];
            $ip = $_SESSION['ip'];
            $nombres = $_SESSION['nombres'];

            // Conectar a la base de datos según la oficina del usuario
            $conn = conectarBaseDeDatos($oficina);

            try {
                // Procedimiento para cambiar la contraseña
                $sqlChangePassword = "BEGIN
                                          zrxi.pkg_qweb.sp_recover_passwd(pic_userbd => :pic_userbd,
                                                                  pic_pass => :pic_pass,
                                                                  pin_flag => 0);
                                     END;";
                $stmtChangePassword = oci_parse($conn, $sqlChangePassword);
                oci_bind_by_name($stmtChangePassword, ":pic_userbd", $usuario);
                oci_bind_by_name($stmtChangePassword, ":pic_pass", $nuevaContraseña);
                
                $executeResult = @oci_execute($stmtChangePassword);
                
                if (!$executeResult) {
                    $error = oci_error($stmtChangePassword);
                    $errorMessage = $error['message'] ?? 'Error desconocido';
                    
                    // Log the full error for debugging
                    error_log("Oracle Error Details: " . print_r($error, true));
                    
                    throw new Exception($errorMessage);
                }
                
                // Si la ejecución fue exitosa, confirmar la transacción
                oci_commit($conn);

                // Reset password attempts on success
                resetPasswordAttempts();

                // Liberar recursos y cerrar la conexión
                oci_free_statement($stmtChangePassword);
                oci_close($conn);

                // Mostrar mensaje de éxito
                $successContent = "
                    <div class='main-container'>
                        <div class='form-card success-card'>
                            <div class='header-section'>
                                <div class='logo-container'>
                                    <img src='../assets/img/Logo2.png' alt='SUNARP Logo' class='logo-img'>
                                </div>
                                <h2 class='form-title success-title'>
                                    <i class='fas fa-check-circle'></i>
                                    ¡Contraseña Cambiada!
                                </h2>
                                <p class='form-subtitle'>Su contraseña se ha actualizado exitosamente</p>
                            </div>
                            
                            <div class='success-alert large'>
                                <i class='fas fa-shield-check'></i>
                                <div>
                                    <strong>¡Proceso completado exitosamente!</strong><br>
                                    La contraseña para el usuario <span class='user-highlight'>$usuario</span> ha sido cambiada correctamente.
                                </div>
                            </div>
                            
                            <div class='info-alert'>
                                <i class='fas fa-lightbulb'></i>
                                <div>
                                    <strong>Recomendación:</strong><br>
                                    Recuerda que esta información es confidencial. No la compartas con nadie.
                                </div>
                            </div>
                            
                            <form action='../index.php' method='GET'>
                                <button type='submit' class='btn-primary-modern'>
                                    <i class='fas fa-sign-in-alt'></i>
                                    Iniciar Sesión
                                </button>
                            </form>
                        </div>
                    </div>";
                renderPageWithStyles($successContent);
                    
            } catch (Exception $e) {
                incrementPasswordAttempts();
                
                $errorMessage = $e->getMessage();
                
                error_log("Password change error for user $usuario: " . $errorMessage);
                
                $errorInfo = getCustomErrorMessage($errorMessage);
                
                $isSystemError = (
                    strpos($errorMessage, 'Dynamic Performance Tables not accessible') !== false || 
                    strpos($errorMessage, 'ORA-00942') !== false ||
                    strpos($errorMessage, 'ORA-12154') !== false ||
                    strpos($errorMessage, 'ORA-12541') !== false ||
                    strpos($errorMessage, 'ORA-01017') !== false ||
                    strpos($errorMessage, 'ORA-12170') !== false
                );

                if ($isSystemError) {
                    // System/connection errors - don't allow retry
                    $systemErrorContent = "
                        <div class='main-container'>
                            <div class='form-card'>
                                <div class='header-section'>
                                    <div class='logo-container'>
                                        <img src='../assets/img/Logo2.png' alt='SUNARP Logo' class='logo-img'>
                                    </div>
                                    <h2 class='form-title'>
                                        <i class='fas fa-exclamation-triangle'></i>
                                        Error en el Sistema
                                    </h2>
                                </div>
                                
                                <div class='error-alert'>
                                    <i class='fas fa-times-circle'></i>
                                    <div>
                                        <strong>No se pudo cambiar la contraseña del usuario:</strong> <span class='user-highlight'>$usuario</span><br>
                                        Por favor, contacte con: <strong><?= htmlspecialchars($correoSoporte) ?></strong>
                                    </div>
                                </div>
                                
                                <form action='../index.php' method='GET'>
                                    <button type='submit' class='btn-primary-modern'>
                                        <i class='fas fa-home'></i>
                                        Volver al Inicio
                                    </button>
                                </form>
                            </div>
                        </div>";
                    renderPageWithStyles($systemErrorContent);
                        
                } else if ($errorInfo['isKnownError']) {
                    $attemptsLeft = 5 - $_SESSION['password_attempts'];
                    
                    $validationErrorContent = "
                        <div class='main-container'>
                            <div class='form-card'>
                                <div class='header-section'>
                                    <div class='logo-container'>
                                        <img src='../assets/img/Logo2.png' alt='SUNARP Logo' class='logo-img'>
                                    </div>
                                    <h2 class='form-title'>
                                        <i class='fas fa-exclamation-triangle'></i>
                                        Error de Validación
                                    </h2>
                                    <p class='form-subtitle'>Ingrese su nueva contraseña para el usuario: <strong>$usuario</strong></p>
                                </div>
                                
                                <div class='error-alert'>
                                    <i class='fas fa-exclamation-triangle'></i>
                                    <div>
                                        <strong>Error:</strong> {$errorInfo['message']}<br>
                                        <small>Intentos restantes: <strong>$attemptsLeft</strong></small>
                                    </div>
                                </div>
                                
                                <form method='POST' class='modern-form'>
                                    <div class='form-group'>
                                        <label for='nueva_contraseña' class='form-label'>
                                            <i class='fas fa-key'></i> Nueva Contraseña
                                        </label>
                                        <div class='password-container'>
                                            <input type='password' name='nueva_contraseña' id='nueva_contraseña' 
                                                   class='form-control-modern error' 
                                                   placeholder='Ingrese su nueva contraseña' required>
                                            <button type='button' class='password-toggle' onclick='togglePassword(\"nueva_contraseña\", \"toggleIcon1\")'>
                                                <i class='fas fa-eye' id='toggleIcon1'></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class='form-group'>
                                        <label for='confirmar_contraseña' class='form-label'>
                                            <i class='fas fa-lock'></i> Confirmar Contraseña
                                        </label>
                                        <div class='password-container'>
                                            <input type='password' name='confirmar_contraseña' id='confirmar_contraseña' 
                                                   class='form-control-modern error' 
                                                   placeholder='Confirme su nueva contraseña' required>
                                            <button type='button' class='password-toggle' onclick='togglePassword(\"confirmar_contraseña\", \"toggleIcon2\")'>
                                                <i class='fas fa-eye' id='toggleIcon2'></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <input type='hidden' name='usuario' value='" . htmlspecialchars($usuario) . "'>
                                    <input type='hidden' name='oficina' value='" . htmlspecialchars($oficina) . "'>

                                    <button type='submit' class='btn-primary-modern'>
                                        <i class='fas fa-redo'></i>
                                        Intentar de Nuevo
                                    </button>
                                </form>
                                
                                <div class='help-text'>
                                    <i class='fas fa-info-circle'></i>
                                    Asegúrese de cumplir con todos los requisitos de la contraseña.
                                </div>
                            </div>
                        </div>";
                    renderPageWithStyles($validationErrorContent);
                        
                } else {
                    $unexpectedErrorContent = "
                        <div class='main-container'>
                            <div class='form-card'>
                                <div class='header-section'>
                                    <div class='logo-container'>
                                        <img src='../assets/img/Logo2.png' alt='SUNARP Logo' class='logo-img'>
                                    </div>
                                    <h2 class='form-title'>
                                        <i class='fas fa-exclamation-triangle'></i>
                                        Error Inesperado
                                    </h2>
                                </div>
                                
                                <div class='error-alert'>
                                    <i class='fas fa-times-circle'></i>
                                    <div>
                                        <strong>Ha ocurrido un error inesperado. Contacte a soporte.</strong><br>
                                        Por favor, contacte con: <strong><?= htmlspecialchars($correoSoporte) ?></strong>
                                    </div>
                                </div>
                                
                                <form action='../index.php' method='GET'>
                                    <button type='submit' class='btn-primary-modern'>
                                        <i class='fas fa-home'></i>
                                        Volver al Inicio
                                    </button>
                                </form>
                            </div>
                        </div>";
                    renderPageWithStyles($unexpectedErrorContent);
                }

                if (isset($stmtChangePassword)) {
                    oci_free_statement($stmtChangePassword);
                }
                if (isset($conn)) {
                    oci_close($conn);
                }
            }

        } else {
            incrementPasswordAttempts();
            
            // Check if user has exceeded maximum attempts after password mismatch
            if (hasExceededMaxAttempts()) {
                $maxAttemptsContent = "
                    <div class='main-container'>
                        <div class='form-card error-card'>
                            <div class='header-section'>
                                <div class='logo-container'>
                                    <img src='../assets/img/Logo2.png' alt='SUNARP Logo' class='logo-img'>
                                </div>
                                <h2 class='form-title error-title'>
                                    <i class='fas fa-ban'></i>
                                    Acceso Bloqueado
                                </h2>
                                <p class='form-subtitle'>Se ha alcanzado el límite máximo de intentos</p>
                            </div>
                            
                            <div class='error-alert large'>
                                <i class='fas fa-shield-alt'></i>
                                <div>
                                    <strong>Máximo de intentos alcanzado</strong><br>
                                    Ha excedido el número máximo de intentos permitidos (5). Por motivos de seguridad, 
                                    el proceso ha sido bloqueado temporalmente.
                                </div>
                            </div>
                            
                            <div class='info-alert'>
                                <i class='fas fa-clock'></i>
                                <div>
                                    <strong>¿Qué hacer ahora?</strong><br>
                                    Puede intentar nuevamente más tarde o contactar con soporte técnico si necesita ayuda inmediata.
                                </div>
                            </div>
                            
                            <div class='button-group'>
                                <form action='../index.php' method='GET' style='margin: 0;'>
                                    <button type='submit' class='btn-primary-modern'>
                                        <i class='fas fa-home'></i>
                                        Volver al Inicio
                                    </button>
                                </form>
                            </div>
                            
                            <div class='help-text'>
                                <i class='fas fa-envelope'></i>
                                ¿Necesita ayuda? Contacte: <strong><?= htmlspecialchars($correoSoporte) ?></strong>
                            </div>
                        </div>
                    </div>";
                
                renderPageWithStyles($maxAttemptsContent);
                
                // Clear session data
                session_unset();
                session_destroy();
                exit();
            }
            
            $attemptsLeft = 5 - $_SESSION['password_attempts'];
            
            // Mostrar nuevamente el formulario con las contraseñas ingresadas
            $passwordMismatchContent = "
                <div class='main-container'>
                    <div class='form-card'>
                        <div class='header-section'>
                            <div class='logo-container'>
                                <img src='../assets/img/Logo2.png' alt='SUNARP Logo' class='logo-img'>
                            </div>
                            <h2 class='form-title'>
                                <i class='fas fa-lock'></i>
                                Nueva Contraseña
                            </h2>
                            <p class='form-subtitle'>Ingrese su nueva contraseña para el usuario: <strong>$usuario</strong></p>
                        </div>
                        
                        <div class='error-alert'>
                            <i class='fas fa-exclamation-triangle'></i>
                            <div>
                                <strong>Las contraseñas no coinciden. Por favor, intente de nuevo.</strong><br>
                                <small>Intentos restantes: <strong>$attemptsLeft</strong></small>
                            </div>
                        </div>
                        
                        <form method='POST' class='modern-form'>
                            <div class='form-group'>
                                <label for='nueva_contraseña' class='form-label'>
                                    <i class='fas fa-key'></i> Nueva Contraseña
                                </label>
                                <div class='password-container'>
                                    <input type='password' name='nueva_contraseña' id='nueva_contraseña' 
                                           class='form-control-modern error' value='$nuevaContraseña' 
                                           placeholder='Ingrese su nueva contraseña' required>
                                    <button type='button' class='password-toggle' onclick='togglePassword(\"nueva_contraseña\", \"toggleIcon1\")'>
                                        <i class='fas fa-eye' id='toggleIcon1'></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class='form-group'>
                                <label for='confirmar_contraseña' class='form-label'>
                                    <i class='fas fa-lock'></i> Confirmar Contraseña
                                </label>
                                <div class='password-container'>
                                    <input type='password' name='confirmar_contraseña' id='confirmar_contraseña' 
                                           class='form-control-modern error' value='$confirmarContraseña' 
                                           placeholder='Confirme su nueva contraseña' required>
                                    <button type='button' class='password-toggle' onclick='togglePassword(\"confirmar_contraseña\", \"toggleIcon2\")'>
                                        <i class='fas fa-eye' id='toggleIcon2'></i>
                                    </button>
                                </div>
                            </div>
                            
                            <input type='hidden' name='usuario' value='" . htmlspecialchars($usuario) . "'>
                            <input type='hidden' name='oficina' value='" . htmlspecialchars($oficina) . "'>

                            <button type='submit' class='btn-primary-modern'>
                                <i class='fas fa-redo'></i>
                                Intentar de Nuevo
                            </button>
                        </form>
                        
                        <div class='help-text'>
                            <i class='fas fa-info-circle'></i>
                            Asegúrese de que ambas contraseñas sean idénticas.
                        </div>
                    </div>
                </div>
            ";
            renderPageWithStyles($passwordMismatchContent);
        }
    }
}

?>
