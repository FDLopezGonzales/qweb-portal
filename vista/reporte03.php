<?php
session_start();
include("../control/seguridad.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/img/Logo2.png">
    <title>SUNARP - Consulta Vehicular</title>
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
            border: 1px solid rgba(26, 166, 164, 0.2);
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(26, 166, 164, 0.2);
            border-top: 4px solid var(--sunarp-turquesa);
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

        /* Main Container */
        .main-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
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
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            color: var(--sunarp-gray);
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
            font-size: 1rem;
        }

        .form-control {
            border: 2px solid rgba(80, 95, 111, 0.2);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--sunarp-turquesa);
            box-shadow: 0 0 0 0.2rem rgba(26, 166, 164, 0.25);
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--sunarp-turquesa) 0%, var(--sunarp-green) 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 166, 164, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 166, 164, 0.4);
            background: linear-gradient(135deg, var(--sunarp-green) 0%, var(--sunarp-turquesa) 100%);
        }

        .btn-secondary {
            background: var(--sunarp-gray);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #3d4a57;
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .form-content {
                padding: 1.5rem;
            }
            
            .btn-primary {
                width: 100%;
                margin-bottom: 1rem;
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
            <h1 class="main-title">CONSULTA VEHICULAR</h1>
            <p class="subtitle">Búsqueda por Serie o Motor Nacional</p>
        </div>

        <!-- Form Section -->
        <div class="form-section">
            <div class="form-header">
                <h3><i class="fas fa-car"></i> Consulta por Serie/Motor</h3>
            </div>
            
            <div class="form-content">
                <form method="POST" action="../modelo/reporte_motor.php" id="consultaForm">
                    <div class="form-group">
                        <label for="SCHASIS"><i class="fas fa-barcode"></i> Número de Serie o Motor:</label>
                        <input type="text" class="form-control" name="SCHASIS" id="SCHASIS" 
                               placeholder="Ingrese el número de serie o motor del vehículo" required>
                        <small class="form-text text-muted">Ingrese el número de serie, motor o VIN del vehículo</small>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Consultar Vehículo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Validación del campo serie/motor (SCHASIS)
    document.getElementById('SCHASIS').addEventListener('input', function(e) {
        // Convertir a mayúsculas
        this.value = this.value.toUpperCase();
        
        // Cambiar color del borde si tiene contenido
        if (this.value.length > 0) {
            this.style.borderColor = 'var(--sunarp-turquesa)';
            this.style.borderWidth = '2px';
        } else {
            this.style.borderColor = 'rgba(80, 95, 111, 0.2)';
            this.style.borderWidth = '2px';
        }
    });

    // Validación y loading antes de enviar
    document.getElementById('consultaForm').addEventListener('submit', function(e) {
        const serie = document.getElementById('SCHASIS').value.trim();
        if (serie.length < 3) {
            e.preventDefault();
            alert('Por favor, ingrese un número de serie o motor válido (mínimo 3 caracteres).');
            return false;
        }
        
        // Mostrar loading
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.classList.add('active');
    });

    // Navigation function with loading animation
    function navigateToApp(url) {
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.classList.add('active');
        
        setTimeout(() => {
            window.location.href = url;
        }, 800); // Espera 800 ms antes de cambiar de página, asegurando que el "loading" sea visible
    }

    // Add entrance animations
    document.addEventListener('DOMContentLoaded', function() {
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
