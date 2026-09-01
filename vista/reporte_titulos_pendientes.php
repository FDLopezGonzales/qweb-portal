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
    <title>SUNARP - Títulos Pendientes de Firma Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
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

        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, rgba(26, 166, 164, 0.8) 0%, rgba(142, 188, 69, 0.8) 100%);
        }

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

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
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
        }

        .back-button {
            background: linear-gradient(135deg, var(--sunarp-gray), #3d4a57);
            color: white;
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
            box-shadow: 0 6px 20px rgba(255, 62, 25, 0.3);
        }

        .logout-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 62, 25, 0.4);
            color: white;
            text-decoration: none;
        }

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

        .filters-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .filters-header {
            background: linear-gradient(135deg, var(--sunarp-turquesa) 0%, #1d9896 100%);
            color: white;
            padding: 1.2rem 2rem;
        }

        .filters-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters-content {
            padding: 2rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin: 0;
        }

        .form-group label {
            color: var(--sunarp-gray);
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            border: 2px solid rgba(80, 95, 111, 0.2);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--sunarp-turquesa);
            box-shadow: 0 0 0 0.2rem rgba(26, 166, 164, 0.25);
            outline: none;
        }

        .btn-filters {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--sunarp-turquesa) 0%, #1d9896 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2.5rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 166, 164, 0.3);
            cursor: pointer;
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 166, 164, 0.4);
        }

        .btn-secondary {
            background: var(--sunarp-gray);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            color: white;
        }

        .btn-secondary:hover {
            background: #3d4a57;
            transform: translateY(-2px);
        }

        .table-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .table-header {
            background: linear-gradient(135deg, var(--sunarp-green) 0%, #7ab82f 100%);
            color: white;
            padding: 1.2rem 2rem;
        }

        .table-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-content {
            padding: 1.5rem;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: rgba(26, 166, 164, 0.1);
            border-bottom: 2px solid rgba(26, 166, 164, 0.3);
        }

        th {
            padding: 1rem;
            text-align: left;
            color: var(--sunarp-gray);
            font-weight: 600;
            white-space: nowrap;
        }

        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: rgba(26, 166, 164, 0.05);
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: var(--sunarp-gray);
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .no-data p {
            font-size: 1.1rem;
            margin: 0;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .main-title {
                font-size: 1.6rem;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }

            .btn-filters {
                flex-direction: column;
            }

            .btn-primary, .btn-secondary {
                width: 100%;
            }

            .top-bar {
                justify-content: center;
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
            <p>Cargando títulos pendientes...</p>
            <small style="color: #999; display: block; margin-top: 0.5rem;">Por favor espere, esto puede tomar algunos segundos</small>
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
            <h1 class="main-title">TÍTULOS PENDIENTES DE FIRMA DIGITAL</h1>
            <p class="subtitle">Visualización y Filtrado de Documentos</p>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-header">
                <h3><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
            </div>
            
            <div class="filters-content">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="filtroOficina"><i class="fas fa-building"></i> Oficina</label>
                        <select class="form-select" id="filtroOficina">
                            <option value="">Todas las Oficinas</option>
                            <option value="ICA">ICA</option>
                            <option value="CHINCHA">CHINCHA</option>
                            <option value="PISCO">PISCO</option>
                            <option value="NASCA">NAZCA</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="filtroAño"><i class="fas fa-calendar"></i> Año</label>
                        <select class="form-select" id="filtroAño">
                            <option value="">Todos los Años</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="filtroTipo"><i class="fas fa-user"></i> Registrador</label>
                        <select class="form-select" id="filtroTipo">
                            <option value="">Todos los Registradores</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="filtroRegistrador"><i class="fas fa-list"></i> Tipo de Registro</label>
                        <select class="form-select" id="filtroRegistrador">
                            <option value="">Todos los Tipos</option>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="busqueda"><i class="fas fa-search"></i> Buscar por Registrador o Título</label>
                        <input type="text" class="form-control" id="busqueda" placeholder="Ingrese registrador o número de título...">
                    </div>
                </div>

                <div class="btn-filters">
                    <button class="btn-primary" id="btnAplicarFiltros">
                        <i class="fas fa-check"></i> Aplicar Filtros
                    </button>
                    <button class="btn-secondary" id="btnLimpiarFiltros">
                        <i class="fas fa-redo"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-section">
            <div class="table-header">
                <h3><i class="fas fa-table"></i> Resultados</h3>
            </div>
            
            <div class="table-content">
                <div id="contenidoTabla">
                    <div class="no-data">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Cargando datos... Por favor espere</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let datosOriginales = [];

        // Cargar datos al abrir la página
        document.addEventListener('DOMContentLoaded', function() {
            cargarDatos();
        });

        function cargarDatos(intento) {
            intento = intento || 1;
            document.getElementById('loadingOverlay').classList.add('active');

            fetch('../modelo/obtener_titulos_pendientes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'cargar_datos'
                })
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                document.getElementById('loadingOverlay').classList.remove('active');

                if (data.exito) {
                    datosOriginales = data.datos;
                    poblarFiltros(data.datos);
                    mostrarTabla(data.datos);
                } else if (intento < 3) {
                    // La primera consulta puede tardar/fallar; reintentar automaticamente
                    setTimeout(function() { cargarDatos(intento + 1); }, 1500);
                } else {
                    mostrarError(data.mensaje);
                }
            })
            .catch(error => {
                if (intento < 3) {
                    setTimeout(function() { cargarDatos(intento + 1); }, 1500);
                } else {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    mostrarError('Error al cargar los datos: ' + error.message);
                }
            });
        }

        // Al volver con "atras" (bfcache), quitar el overlay para que no quede pegado.
        window.addEventListener('pageshow', function (e) {
            var ov = document.getElementById('loadingOverlay');
            if (ov) ov.classList.remove('active');
        });

        function poblarFiltros(datos) {
            const años = new Set();
            const tipos = new Set();
            const registradores = new Set();

            datos.forEach(row => {
                if (row.AA_TITU) años.add(row.AA_TITU);
                if (row.REGI) tipos.add(row.REGI);
                if (row.REGISTRO) registradores.add(row.REGISTRO);
            });

            const selectAño = document.getElementById('filtroAño');
            Array.from(años).sort().reverse().forEach(año => {
                const option = document.createElement('option');
                option.value = año;
                option.textContent = año;
                selectAño.appendChild(option);
            });

            const selectTipo = document.getElementById('filtroTipo');
            Array.from(tipos).sort().forEach(tipo => {
                const option = document.createElement('option');
                option.value = tipo;
                option.textContent = tipo;
                selectTipo.appendChild(option);
            });

            const selectRegistrador = document.getElementById('filtroRegistrador');
            Array.from(registradores).sort().forEach(registrador => {
                const option = document.createElement('option');
                option.value = registrador;
                option.textContent = registrador;
                selectRegistrador.appendChild(option);
            });
        }

        function mostrarTabla(datos) {
            const contenido = document.getElementById('contenidoTabla');

            if (datos.length === 0) {
                contenido.innerHTML = `
                    <div class="no-data">
                        <i class="fas fa-inbox"></i>
                        <p>No se encontraron registros con los filtros especificados</p>
                    </div>
                `;
                return;
            }

            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>Oficina</th>
                            <th>Registro</th>
                            <th>Registrador</th>
                            <th>Horas</th>
                            <th>Año</th>
                            <th>Número de Título</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            datos.forEach(row => {
                html += `
                    <tr>
                        <td>${row.OFICINA || ''}</td>
                        <td>${row.REGISTRO || ''}</td>
                        <td>${row.REGI || ''}</td>
                        <td>${parseFloat(row.HORAS).toFixed(2) || '0'}</td>
                        <td>${row.AA_TITU || ''}</td>
                        <td><strong>${row.NU_TITU || ''}</strong></td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
                <div style="text-align: center; color: var(--sunarp-gray); margin-top: 1rem;">
                    <small>Total de registros: <strong>${datos.length}</strong></small>
                </div>
            `;

            contenido.innerHTML = html;
        }

        function aplicarFiltros() {
            const oficina = document.getElementById('filtroOficina').value;
            const año = document.getElementById('filtroAño').value;
            const tipo = document.getElementById('filtroTipo').value;
            const registrador = document.getElementById('filtroRegistrador').value;
            const busqueda = document.getElementById('busqueda').value.toLowerCase();

            let datosFiltrados = datosOriginales.filter(row => {
                const cumpleOficina = !oficina || row.OFICINA === oficina;
                const cumpleAño = !año || row.AA_TITU === año;
                const cumpleTipo = !tipo || row.REGI === tipo;
                const cumpleRegistrador = !registrador || row.REGISTRO === registrador;
                const cumpleBusqueda = !busqueda || 
                    (row.REGISTRO && row.REGISTRO.toLowerCase().includes(busqueda)) ||
                    (row.REGI && row.REGI.toLowerCase().includes(busqueda)) ||
                    (row.NU_TITU && row.NU_TITU.toLowerCase().includes(busqueda));

                return cumpleOficina && cumpleAño && cumpleTipo && cumpleRegistrador && cumpleBusqueda;
            });

            mostrarTabla(datosFiltrados);
        }

        function limpiarFiltros() {
            document.getElementById('filtroOficina').value = '';
            document.getElementById('filtroAño').value = '';
            document.getElementById('filtroTipo').value = '';
            document.getElementById('filtroRegistrador').value = '';
            document.getElementById('busqueda').value = '';
            mostrarTabla(datosOriginales);
        }

        function mostrarError(mensaje) {
            const contenido = document.getElementById('contenidoTabla');
            contenido.innerHTML = `
                <div class="no-data" style="color: var(--sunarp-red);">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>${mensaje}</p>
                </div>
            `;
        }

        // Event Listeners
        document.getElementById('btnAplicarFiltros').addEventListener('click', aplicarFiltros);
        document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);

        // Aplicar filtros al escribir en la búsqueda
        document.getElementById('busqueda').addEventListener('keyup', aplicarFiltros);

        // Aplicar filtros al cambiar selecciones
        ['filtroOficina', 'filtroAño', 'filtroTipo', 'filtroRegistrador'].forEach(id => {
            document.getElementById(id).addEventListener('change', aplicarFiltros);
        });
    </script>
</body>
</html>
