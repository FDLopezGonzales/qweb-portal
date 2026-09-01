<?php
// Incluir el archivo de seguridad para verificar la autenticación
include("../control/seguridad.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/img/Logo2.png">
    <title>SUNARP - Dashboard Principal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sunarp-gray: #505F6F;
            --sunarp-green: #8EBC45;
            --sunarp-red: #FF3E19;
            --sunarp-orange: #FF8C42;
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
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        /* Top Bar with Logout */
        .top-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2rem;
        }

        .logout-button {
            background: linear-gradient(135deg, var(--sunarp-red), #ff6b47);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 8px 25px rgba(255, 62, 25, 0.3);
        }

        .logout-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(255, 62, 25, 0.4);
        }

        /* Header Section */
        .header-section {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .logo-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            padding: 2rem;
            display: inline-block;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .logo-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.15);
        }

        .logo-container img {
            width: 120px;
            height: auto;
            filter: brightness(0) invert(1);
            transition: all 0.3s ease;
        }

        .main-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.02em;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.2rem;
            font-weight: 400;
            margin-bottom: 2rem;
        }

        .user-welcome {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 1rem 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .user-welcome:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .user-welcome i {
            font-size: 1.2rem;
            color: var(--sunarp-yellow);
        }

        /* Applications Grid */
        .apps-section {
            margin-bottom: 4rem;
        }

        .section-title {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .apps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .app-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .app-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .app-card:hover::before {
            left: 100%;
        }

        .app-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
        }

        .app-card.devoluciones {
            background: linear-gradient(135deg, rgba(255, 140, 66, 0.1), rgba(255, 255, 255, 0.95));
            border-left: 4px solid #FF8C42;
        }

        .app-card.vehiculos {
            background: linear-gradient(135deg, rgba(26, 166, 164, 0.1), rgba(255, 255, 255, 0.95));
            border-left: 4px solid var(--sunarp-turquesa);
        }

        .app-card.busind {
            background: linear-gradient(135deg, rgba(142, 188, 69, 0.1), rgba(255, 255, 255, 0.95));
            border-left: 4px solid var(--sunarp-green);
        }

        .app-card.titulos {
            background: linear-gradient(135deg, rgba(26, 166, 164, 0.1), rgba(255, 255, 255, 0.95));
            border-left: 4px solid var(--sunarp-turquesa);
        }

        .app-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .app-icon::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: inherit;
            border-radius: inherit;
            filter: blur(10px);
            opacity: 0.3;
            z-index: -1;
        }

        .devoluciones .app-icon {
            background: linear-gradient(135deg, #FF8C42, #ff7a33);
        }

        .vehiculos .app-icon {
            background: linear-gradient(135deg, var(--sunarp-turquesa), #4fd1c7);
        }

        .busind .app-icon {
            background: linear-gradient(135deg, var(--sunarp-green), #a8d46f);
        }

        .titulos .app-icon {
            background: linear-gradient(135deg, var(--sunarp-turquesa), #4fd1c7);
        }

        .app-card.prop {
            background: linear-gradient(135deg, rgba(241, 164, 0, 0.1), rgba(255, 255, 255, 0.95));
            border-left: 4px solid var(--sunarp-yellow);
        }

        .prop .app-icon {
            background: linear-gradient(135deg, var(--sunarp-yellow), #f5b942);
        }

        .app-card:hover .app-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .app-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--sunarp-gray);
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }

        .app-description {
            font-size: 0.95rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .app-button {
            background: linear-gradient(135deg, var(--sunarp-turquesa), var(--sunarp-green));
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(26, 166, 164, 0.3);
        }

        .app-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 166, 164, 0.4);
        }

        /* Stats Section - Moved Below Apps */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--sunarp-yellow);
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.95;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .main-title {
                font-size: 2rem;
            }

            .apps-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .app-card {
                padding: 1.5rem;
            }

            .stats-section {
                grid-template-columns: repeat(2, 1fr);
            }

            .top-bar {
                margin-bottom: 1rem;
            }

            .logout-button {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .main-title {
                font-size: 1.8rem;
            }

            .subtitle {
                font-size: 1rem;
            }

            .user-welcome {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
            }

            .stats-section {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 1.5rem 1rem;
            }

            .stat-number {
                font-size: 2rem;
            }
        }

        /* Loading Animation */
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

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(26, 166, 164, 0.2);
            border-top: 4px solid var(--sunarp-turquesa);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="dashboard-container">
        <!-- Top Bar with Logout -->
        <div class="top-bar">
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
            <h1 class="main-title">SUNARP</h1>
            <p class="subtitle">Zona Registral N° XI - Ica</p>
            <div class="user-welcome">
                <i class="fas fa-user-circle"></i>
                <span>Bienvenido, <?php echo $_SESSION['nombre']; ?></span>
            </div>
        </div>

        <!-- Applications Section -->
        <div class="apps-section">
            <h2 class="section-title">
                <i class="fas fa-th-large" style="margin-right: 0.5rem;"></i>
                Aplicaciones Disponibles
            </h2>
            
            <div class="apps-grid">
                <!-- Devoluciones App -->
                <div class="app-card devoluciones" onclick="navigateToApp('reporte01.php')">
                    <div class="app-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3 class="app-title">Pendiente de Devoluciones</h3>
                    <p class="app-description">
                        Consulta rápida de devoluciones pendientes por DNI. 
                    </p>
                    <button class="app-button">
                        <i class="fas fa-search" style="margin-right: 0.5rem;"></i>
                        Consultar
                    </button>
                </div>

                <!-- Vehículos App -->
                <div class="app-card vehiculos" onclick="navigateToApp('reporte03.php')">
                    <div class="app-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3 class="app-title">Consulta Vehicular Nacional</h3>
                    <p class="app-description">
                        Búsqueda avanzada por serie o motor a nivel nacional. 
                        Información completa de vehículos registrados.
                    </p>
                    <button class="app-button">
                        <i class="fas fa-search" style="margin-right: 0.5rem;"></i>
                        Consultar
                    </button>
                </div>

                <!-- BUS-IND App -->
                <div class="app-card busind" onclick="navigateToApp('bus_ind.php')">
                    <div class="app-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="app-title">Sistema BUS-IND</h3>
                    <p class="app-description">
                        Acceso al sistema especializado BUS-IND para consultas 
                        y reportes detallados. <br>(ICA - CHINCHA - PISCO - NAZCA)
                    </p>
                    <button class="app-button">
                        <i class="fas fa-search" style="margin-right: 0.5rem;"></i>
                        Consultar
                    </button>
                </div>

                <!-- Títulos Pendientes de Firma Digital App -->
                <div class="app-card titulos" onclick="navigateToApp('reporte_titulos_pendientes.php')">
                    <div class="app-icon">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <h3 class="app-title">Títulos Pendientes de Firma Digital</h3>
                    <p class="app-description">
                        Consulta de títulos pendientes de firma digital.
                        Filtrar por oficina, tipo, registrador y año.
                    </p>
                    <button class="app-button">
                        <i class="fas fa-search" style="margin-right: 0.5rem;"></i>
                        Consultar
                    </button>
                </div>

                <!-- PROP: Indice de Partidas Registrales App -->
                <div class="app-card prop" onclick="navigateToApp('prop.php')">
                    <div class="app-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3 class="app-title">Indice de Partidas (PROP)</h3>
                    <p class="app-description">
                        Consulta de partidas registrales del sistema PROP.
                        <br>(PISCO - ICA)
                    </p>
                    <button class="app-button">
                        <i class="fas fa-search" style="margin-right: 0.5rem;"></i>
                        Consultar
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Section - Now Below Apps -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number">5</div>
                <div class="stat-label">Aplicaciones</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Disponibilidad</div>
            </div>
            <!-- <div class="stat-card">
                <div class="stat-number">100%</div>
                <div class="stat-label">Seguridad</div>
            </div> -->
            <div class="stat-card">
                <div class="stat-number">XI</div>
                <div class="stat-label">Zona Registral</div>
            </div>
        </div>
    </div>

    <script>
        // Navigation function with loading animation
        function navigateToApp(url) {
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('active');

            setTimeout(() => {
                window.location.href = url;
            }, 800);
        }

        // Al volver con el boton "atras" del navegador (bfcache), ocultar el overlay
        // para que no quede "cargando" pegado.
        window.addEventListener('pageshow', function (e) {
            const ov = document.getElementById('loadingOverlay');
            if (ov) ov.classList.remove('active');
        });

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

        // Add click effects
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
    </script>
</body>
</html>
