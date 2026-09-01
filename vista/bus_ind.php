<?php 
session_start(); 
setlocale(LC_ALL, 'esp'); 
include("../control/seguridad.php"); 

// Manejar la limpieza del formulario via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'clear_form') {
    unset($_SESSION['busind_form_data']);
    echo json_encode(['success' => true]);
    exit;
}

// Recuperar los datos del formulario si existen en la sesión
$form_data = isset($_SESSION['busind_form_data']) ? $_SESSION['busind_form_data'] : array();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/img/Logo2.png">
    <title>SUNARP - Consulta BUS-IND</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --sunarp-gray: #505F6F;
            --sunarp-green: #8EBC45;
            --sunarp-red: #FF3E19;
            --sunarp-yellow: #F1A400;
            --sunarp-turquesa: #1AA6A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, rgba(26, 166, 164, 0.8) 0%, rgba(142, 188, 69, 0.8) 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, rgba(26, 166, 164, 0.8) 0%, rgba(142, 188, 69, 0.8) 100%);
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }

        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        /* Top Bar with Logout */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0rem;
            gap: 25rem;
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

        /* Form Section */
        .form-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-header {
            background: linear-gradient(135deg, var(--sunarp-turquesa) 0%, var(--sunarp-green) 100%);
            color: white;
            padding: 1.2rem 2rem;
            margin: 0;
        }

        .form-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-content {
            padding: 1.5rem 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            color: var(--sunarp-gray);
            font-weight: 600;
            margin-bottom: 0.4rem;
            display: block;
            font-size: 0.9rem;
        }

        .form-control {
            border: 2px solid rgba(80, 95, 111, 0.2);
            border-radius: 8px;
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            height: auto;
        }

        .form-control:focus {
            border-color: var(--sunarp-green);
            box-shadow: 0 0 0 0.2rem rgba(142, 188, 69, 0.25);
            outline: none;
        }

        .ficha-section {
            background: rgba(142, 188, 69, 0.08);
            border: 2px solid rgba(142, 188, 69, 0.2);
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1.2rem;
        }

        .ficha-section h5 {
            color: var(--sunarp-green);
            font-weight: 600;
            margin-bottom: 0.8rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--sunarp-green) 0%, #7da83a 100%);
            border: none;
            border-radius: 25px;
            padding: 0.7rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(142, 188, 69, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(142, 188, 69, 0.4);
            background: linear-gradient(135deg, #7da83a 0%, var(--sunarp-green) 100%);
        }

        .btn-secondary {
            background: var(--sunarp-gray);
            border: none;
            border-radius: 25px;
            padding: 0.7rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #3d4a57;
            transform: translateY(-2px);
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--sunarp-red) 0%, #e6351a 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }

        .modal-title {
            font-weight: 600;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border-top: none;
            padding: 1rem 2rem 2rem;
        }

        .modal-footer .btn-danger {
            background: var(--sunarp-red);
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(142, 188, 69, 0.2);
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(142, 188, 69, 0.2);
            border-top: 4px solid var(--sunarp-green);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-content p {
            margin: 0;
            color: var(--sunarp-gray);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Highlight for fields with data */
        .form-control.has-data {
            border-color: var(--sunarp-turquesa);
            background-color: rgba(26, 166, 164, 0.05);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .form-content {
                padding: 1rem;
            }
            
            .ficha-section {
                padding: 1rem;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .top-bar {
                flex-direction: column;
                gap: 1rem;
            }

            .main-title {
                font-size: 1.6rem;
            }
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Procesando consulta...</p>
        </div>
    </div>

    <div class="main-container">
        <!-- Top Bar -->
        <div class="top-bar">
            <a href="main.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                <span>Volver al Dashboard</span>
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

        <!-- Form Section -->
        <div class="form-section">
            <div class="form-header">
                <h3><i class="fas fa-search"></i> Formulario de Búsqueda</h3>
            </div>
            
            <div class="form-content">
                <form id="consultaForm" method="POST" action="../modelo/reporte_busind.php">
                    
                    <!-- Sección de Fichas (Prioridad) -->
                    <div class="ficha-section">
                        <h5><i class="fas fa-file-alt"></i> Números de Ficha</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pic_ficant">N° de Ficha Mecánica:</label>
                                    <input type="text" class="form-control" name="pic_ficant" id="pic_ficant" 
                                           placeholder="Ingrese la Ficha Mecánica"
                                           value="<?php echo isset($form_data['pic_ficant']) ? htmlspecialchars($form_data['pic_ficant']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pic_ficha">N° de Ficha Electrónica:</label>
                                    <input type="text" class="form-control" name="pic_ficha" id="pic_ficha" 
                                           placeholder="Ingrese la Ficha Electrónica"
                                           value="<?php echo isset($form_data['pic_ficha']) ? htmlspecialchars($form_data['pic_ficha']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Primera fila: Nombres y Apellidos -->
                    <div class="row gy-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pic_nombres"><i class="fas fa-user"></i> Nombres:</label>
                                <input type="text" class="form-control" name="pic_nombres" id="pic_nombres" 
                                       placeholder="Ingrese los nombres" minlength="1"
                                       value="<?php echo isset($form_data['pic_nombres']) ? htmlspecialchars($form_data['pic_nombres']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pic_apellido"><i class="fas fa-user"></i> Apellidos:</label>
                                <input type="text" class="form-control" name="pic_apellido" id="pic_apellido" 
                                       placeholder="Ingrese los apellidos" minlength="1"
                                       value="<?php echo isset($form_data['pic_apellido']) ? htmlspecialchars($form_data['pic_apellido']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Tercera fila: Tomo, Folio y Código Catastral -->
                    <div class="row gy-3 mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pic_tomo1"><i class="fas fa-book"></i> Tomo:</label>
                                <input type="text" class="form-control" name="pic_tomo1" id="pic_tomo1" 
                                       placeholder="Número de tomo" minlength="1"
                                       value="<?php echo isset($form_data['pic_tomo1']) ? htmlspecialchars($form_data['pic_tomo1']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pic_folio1"><i class="fas fa-file"></i> Folio:</label>
                                <input type="text" class="form-control" name="pic_folio1" id="pic_folio1" 
                                       placeholder="Número de folio" minlength="1"
                                       value="<?php echo isset($form_data['pic_folio1']) ? htmlspecialchars($form_data['pic_folio1']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pic_cod_catast"><i class="fas fa-barcode"></i> Código Catastral:</label>
                                <input type="text" class="form-control" name="pic_cod_catast" id="pic_cod_catast" 
                                       placeholder="Ingrese el código catastral" minlength="1"
                                       value="<?php echo isset($form_data['pic_cod_catast']) ? htmlspecialchars($form_data['pic_cod_catast']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Cuarta fila: Departamento, Provincia y Distrito -->
                    <div class="row gy-3 mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pic_departamen"><i class="fas fa-map-marker-alt"></i> Departamento:</label>
                                <input type="text" class="form-control" name="pic_departamen" id="pic_departamen" 
                                       placeholder="Ingrese el departamento" minlength="1"
                                       value="<?php echo isset($form_data['pic_departamen']) ? htmlspecialchars($form_data['pic_departamen']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pic_provincia"><i class="fas fa-map-marker-alt"></i> Provincia:</label>
                                <input type="text" class="form-control" name="pic_provincia" id="pic_provincia" 
                                       placeholder="Ingrese la provincia" minlength="1"
                                       value="<?php echo isset($form_data['pic_provincia']) ? htmlspecialchars($form_data['pic_provincia']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pic_distrito"><i class="fas fa-map-marker-alt"></i> Distrito:</label>
                                <input type="text" class="form-control" name="pic_distrito" id="pic_distrito" 
                                       placeholder="Ingrese el distrito" minlength="1"
                                       value="<?php echo isset($form_data['pic_distrito']) ? htmlspecialchars($form_data['pic_distrito']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Quinta fila: Descripción (ancho completo) -->
                    <div class="row gy-3 mb-3">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="pic_descrip"><i class="fas fa-info-circle"></i> Descripción:</label>
                                <input type="text" class="form-control" name="pic_descrip" id="pic_descrip" 
                                       placeholder="Ingrese la descripción del predio" minlength="1"
                                       value="<?php echo isset($form_data['pic_descrip']) ? htmlspecialchars($form_data['pic_descrip']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="text-center mt-4">
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <button type="submit" class="btn btn-primary" name="B1">
                                <i class="fas fa-search me-2"></i>Consultar
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">
                                <i class="fas fa-eraser me-2"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de advertencia -->
    <div id="alertModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>¡Atención!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: var(--sunarp-yellow); margin-bottom: 1rem;"></i>
                        <p style="font-size: 16px; color: #333; margin: 0;">
                            Por favor, agrega al menos un parámetro para realizar la búsqueda.
                        </p>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Función para limpiar el formulario COMPLETAMENTE
    function limpiarFormulario() {
        // Mostrar loading mientras se limpia
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.classList.add('active');
        
        // Hacer petición AJAX para limpiar los datos de la sesión
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                action: 'clear_form'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Limpiar el formulario visualmente
                    document.getElementById('consultaForm').reset();
                    
                    // Remover la clase has-data de todos los campos
                    document.querySelectorAll('.form-control').forEach(function(input) {
                        input.classList.remove('has-data');
                        input.style.borderColor = 'rgba(80, 95, 111, 0.2)';
                        input.style.backgroundColor = '';
                        input.value = ''; // Asegurar que el valor esté vacío
                    });
                    
                    // Ocultar loading
                    loadingOverlay.classList.remove('active');
                    
                    // Mostrar mensaje de éxito
                    const toast = document.createElement('div');
                    toast.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                    toast.style.zIndex = '9999';
                    toast.innerHTML = '<i class="fas fa-check-circle me-2"></i>Formulario limpiado completamente';
                    document.body.appendChild(toast);
                    
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                } else {
                    loadingOverlay.classList.remove('active');
                    alert('Error al limpiar el formulario');
                }
            },
            error: function() {
                loadingOverlay.classList.remove('active');
                // Limpiar al menos visualmente si hay error en AJAX
                document.getElementById('consultaForm').reset();
                document.querySelectorAll('.form-control').forEach(function(input) {
                    input.classList.remove('has-data');
                    input.style.borderColor = 'rgba(80, 95, 111, 0.2)';
                    input.style.backgroundColor = '';
                    input.value = '';
                });
                
                const toast = document.createElement('div');
                toast.className = 'alert alert-warning position-fixed top-0 end-0 m-3';
                toast.style.zIndex = '9999';
                toast.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Formulario limpiado (solo visualmente)';
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }
        });
    }

    // Función para destacar campos con datos al cargar la página
    function highlightFieldsWithData() {
        document.querySelectorAll('input[type="text"]').forEach(function(input) {
            if (input.value.trim().length > 0) {
                input.classList.add('has-data');
            }
        });
    }

    // Validación en tiempo real para todos los campos
    document.querySelectorAll('input[type="text"]').forEach(function(input) {
        input.addEventListener('input', function(e) {
            // Convertir a mayúsculas
            this.value = this.value.toUpperCase();
            
            // Cambiar color del borde si tiene contenido válido (al menos 1 carácter)
            if (this.value.trim().length >= 1) {
                this.classList.add('has-data');
                this.style.borderColor = 'var(--sunarp-turquesa)';
                this.style.borderWidth = '2px';
            } else {
                this.classList.remove('has-data');
                this.style.borderColor = 'rgba(80, 95, 111, 0.2)';
                this.style.borderWidth = '2px';
                this.style.backgroundColor = '';
            }
        });
    });

    // Validación mejorada antes de enviar el formulario
    document.getElementById('consultaForm').addEventListener('submit', function(event) {
        var campos = document.querySelectorAll('input[type="text"]');
        var valid = false;
        var camposConDatos = [];

        // Verificar que al menos un campo tenga al menos 1 carácter
        for (var i = 0; i < campos.length; i++) {
            campos[i].value = campos[i].value.trim().toUpperCase();
            
            if (campos[i].value.length >= 1) {
                valid = true;
                camposConDatos.push({
                    name: campos[i].name,
                    value: campos[i].value,
                    length: campos[i].value.length
                });
            }
        }

        if (!valid) {
            event.preventDefault();
            var alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
            alertModal.show();
            
            // Resaltar campos vacíos
            campos.forEach(campo => {
                if (campo.value.trim().length === 0) {
                    campo.style.borderColor = 'var(--sunarp-red)';
                    campo.style.borderWidth = '2px';
                    campo.style.animation = 'shake 0.5s ease-in-out';
                }
            });
            
        } else {
            // Mostrar loading
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('active');
            console.log('Búsqueda iniciada con los campos:', camposConDatos);
        }
    });

    // Navigation function with loading animation
    function navigateToApp(url) {
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.classList.add('active');
        
        setTimeout(() => {
            window.location.href = url;
        }, 800);
    }

    // Add entrance animations
    document.addEventListener('DOMContentLoaded', function() {
        // Destacar campos que ya tienen datos
        highlightFieldsWithData();
        
        const cards = document.querySelectorAll('.app-card');
        const stats = document.querySelectorAll('.stat-card');
        
        // Animate cards
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 200);
        });

        // Animate stats
        stats.forEach((stat, index) => {
            stat.style.opacity = '0';
            stat.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                stat.style.transition = 'all 0.4s ease';
                stat.style.opacity = '1';
                stat.style.transform = 'translateY(0)';
            }, (cards.length * 200) + (index * 100));
        });
    });

    // Add click effects (ripple)
    document.querySelectorAll('.app-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Create ripple effect
            const ripple = document.createElement('div');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(26, 166, 164, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            `;
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Add ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(2);
                opacity: 0;
            }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(style);

    // Ocultar loading cuando la página ha cargado completamente
    window.addEventListener('load', function() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.classList.remove('active');
    });

    // Evitar la desaparición del overlay si el usuario intenta salir de la página
    window.addEventListener('beforeunload', function() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.classList.add('active');
    });
</script>

</body>
</html>