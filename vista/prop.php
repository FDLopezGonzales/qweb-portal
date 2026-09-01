<?php
// vista/prop.php  -  Modulo PROP: indice de partidas registrales (Pisco + Ica)
include("../control/seguridad.php");

$CATALOGO = [
    "ASOCI" => "Asociaciones",
    "CCNAT" => "Comunidades Campesinas/Nativas",
    "COMER" => "Comerciantes",
    "COMIT" => "Comites",
    "COOPE" => "Cooperativas",
    "EDPUB" => "Empresas del Estado / Public.",
    "EPSOC" => "Empresas de Propiedad Social",
    "FUNDA" => "Fundaciones",
    "INTES" => "Sucesiones Intestadas",
    "MANDA" => "Mandatos y Poderes",
    "MERCA" => "Registro Mercantil",
    "PAGRI" => "Predios Agricolas",
    "PERSO" => "Personas Juridicas",
    "PINDU" => "Predios Urbanos / Ind.",
    "PROP1" => "Propiedad Inmueble (Titulares)",
    "PROP2" => "Propiedad Inmueble (Predios)",
    "SOCIE" => "Sociedades",
    "TESTA" => "Testamentos",
];
$presentes = [];
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/../data/prop.sqlite');
    foreach ($pdo->query("SELECT registro, COUNT(*) c FROM partidas GROUP BY registro") as $r) {
        $presentes[$r['registro']] = (int)$r['c'];
    }
    $totalReg = (int)$pdo->query("SELECT COUNT(*) FROM partidas")->fetchColumn();
} catch (Exception $e) {
    $totalReg = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/img/Logo2.png">
    <title>SUNARP - Indice de Partidas (PROP)</title>
    <link rel="stylesheet" type="text/css" href="../assets/estilos/css/Bootstrap/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="../assets/estilos/css/Datatables/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --sunarp-gray:#505F6F; --sunarp-green:#8EBC45; --sunarp-red:#FF3E19;
            --sunarp-yellow:#F1A400; --sunarp-turquesa:#1AA6A4;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
            background:linear-gradient(135deg,rgba(26,166,164,.8) 0%,rgba(142,188,69,.8) 100%);
            min-height:100vh; padding:15px;
        }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
        .back-button,.logout-button {
            color:#fff; border:none; border-radius:50px; padding:.6rem 1.5rem; font-weight:600;
            font-size:.9rem; cursor:pointer; text-decoration:none; display:inline-flex;
            align-items:center; gap:.5rem; transition:all .3s ease;
        }
        .back-button { background:linear-gradient(135deg,var(--sunarp-gray),#3d4a57); box-shadow:0 6px 20px rgba(80,95,111,.3); }
        .logout-button { background:linear-gradient(135deg,var(--sunarp-red),#ff6b47); box-shadow:0 6px 20px rgba(255,62,25,.3); }
        .back-button:hover,.logout-button:hover { transform:translateY(-2px); color:#fff; text-decoration:none; }
        .header-section { text-align:center; margin-bottom:1.5rem; }
        .logo-container {
            background:rgba(255,255,255,.15); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,.2);
            border-radius:25px; padding:1.2rem; display:inline-block; margin-bottom:1rem;
        }
        .logo-container img { width:80px; height:auto; filter:brightness(0) invert(1); }
        .main-title { color:#fff; font-size:2rem; font-weight:800; text-shadow:0 4px 20px rgba(0,0,0,.3); }
        .subtitle { color:rgba(255,255,255,.9); font-size:1rem; }
        .contenedor_tabla {
            background:#fff; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,.1);
            margin:1rem auto; width:97%; max-width:1300px;
        }
        .results-header {
            background:linear-gradient(135deg,var(--sunarp-turquesa) 0%,var(--sunarp-green) 100%);
            color:#fff; padding:1rem 2rem; border-radius:15px 15px 0 0;
        }
        .results-header h2 { margin:0; font-weight:600; font-size:1.3rem; display:flex; align-items:center; gap:.5rem; }

        /* ----- Panel de busqueda por campos ----- */
        .buscador { padding:1.2rem 1.5rem 0; }
        .buscador .fila { display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end; }
        .campo { display:flex; flex-direction:column; gap:.3rem; }
        .campo label { font-size:.75rem; font-weight:700; color:var(--sunarp-gray); text-transform:uppercase; letter-spacing:.3px; }
        .campo input, .campo select {
            border:2px solid rgba(80,95,111,.25); border-radius:8px; padding:.5rem .7rem; font-size:.92rem; background:#fff;
        }
        .campo input:focus, .campo select:focus { border-color:var(--sunarp-green); outline:none; box-shadow:0 0 0 3px rgba(142,188,69,.15); }
        .campo.chico input { width:95px; }
        .campo.nombre input { width:320px; max-width:60vw; }
        .campo select { min-width:180px; }

        /* Desplegable propio (abre siempre hacia abajo, con scroll) */
        .dd { position:relative; }
        .dd-toggle {
            display:flex; align-items:center; justify-content:space-between; gap:.6rem;
            background:#fff; border:2px solid rgba(80,95,111,.25); border-radius:8px;
            padding:.5rem .7rem; font-size:.92rem; cursor:pointer; color:#243; width:100%; text-align:left;
        }
        .dd-toggle:hover { border-color:var(--sunarp-green); }
        .dd.open .dd-toggle { border-color:var(--sunarp-green); box-shadow:0 0 0 3px rgba(142,188,69,.15); }
        .dd-toggle .chev { transition:transform .2s ease; color:var(--sunarp-gray); }
        .dd.open .dd-toggle .chev { transform:rotate(180deg); }
        .dd.oficina { min-width:130px; }
        .dd.registro { min-width:250px; }
        .dd-menu {
            display:none; position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff;
            border:1px solid rgba(80,95,111,.2); border-radius:8px; box-shadow:0 14px 34px rgba(0,0,0,.2);
            max-height:340px; overflow:auto; z-index:200;
        }
        .dd.open .dd-menu { display:block; }
        .dd-opt { padding:.5rem .85rem; font-size:.9rem; cursor:pointer; white-space:nowrap; }
        .dd-opt:hover { background:rgba(26,166,164,.12); }
        .dd-opt.sel { background:var(--sunarp-turquesa); color:#fff; }
        .dd-opt.oculto { display:none; }
        .dd-search {
            position:sticky; top:0; width:100%; border:none; border-bottom:1px solid rgba(80,95,111,.2);
            padding:.55rem .85rem; font-size:.9rem; outline:none; background:#f7f9fb;
        }
        .dd-search:focus { background:#fff; }
        .btns { display:flex; gap:.6rem; }
        .btn-buscar, .btn-limpiar {
            border:none; border-radius:50px; padding:.55rem 1.4rem; font-weight:700; font-size:.9rem; cursor:pointer;
            display:inline-flex; align-items:center; gap:.5rem; transition:all .25s ease;
        }
        .btn-buscar { background:linear-gradient(135deg,var(--sunarp-turquesa),var(--sunarp-green)); color:#fff; box-shadow:0 4px 15px rgba(26,166,164,.3); }
        .btn-buscar:hover { transform:translateY(-2px); }
        .btn-limpiar { background:#eef1f4; color:var(--sunarp-gray); }
        .btn-limpiar:hover { background:#e2e7ec; }
        .ayuda { font-size:.8rem; color:#7a8794; margin-top:.6rem; }
        .ayuda i { color:var(--sunarp-turquesa); }

        .results-content { padding:1rem 1.5rem 1.5rem; }
        table#tabla { width:100%; font-size:.85rem; table-layout:fixed; word-wrap:break-word; }
        table#tabla th:nth-child(1), table#tabla td:nth-child(1){ width:7%; }   /* Oficina */
        table#tabla th:nth-child(2), table#tabla td:nth-child(2){ width:13%; }  /* Registro */
        table#tabla th:nth-child(3), table#tabla td:nth-child(3){ width:6%; }   /* Tomo */
        table#tabla th:nth-child(4), table#tabla td:nth-child(4){ width:6%; }   /* Folio */
        table#tabla th:nth-child(5), table#tabla td:nth-child(5){ width:8%; }   /* Ficha */
        table#tabla th:nth-child(6), table#tabla td:nth-child(6){ width:30%; }  /* Nombre */
        table#tabla th:nth-child(7), table#tabla td:nth-child(7){ width:30%; }  /* Detalle */
        table#tabla thead th {
            background:linear-gradient(135deg,var(--sunarp-gray) 0%,#3d4a57 100%); color:#fff;
            font-weight:600; text-transform:uppercase; font-size:.72rem; letter-spacing:.3px; text-align:left;
        }
        table#tabla tbody td { vertical-align:middle; }
        table#tabla tbody tr { cursor:pointer; }
        table#tabla tbody tr:hover { background:linear-gradient(135deg,rgba(142,188,69,.12),rgba(26,166,164,.08)); }

        /* Selector "Mostrar N registros": una sola flecha (evita el choque con Bootstrap) */
        .dataTables_wrapper .dataTables_length select {
            padding:.4rem 2rem .4rem .7rem; min-width:80px; border:2px solid rgba(80,95,111,.25);
            border-radius:6px; background-color:#fff;
            -webkit-appearance:none; -moz-appearance:none; appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23505F6F'%3E%3Cpath d='M4 6l4 4 4-4z'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right .6rem center; background-size:.85rem;
        }
        .dataTables_wrapper .dataTables_length select:focus { border-color:var(--sunarp-green); outline:none; }
        .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { margin-top:1rem; }
        mark { background:#ffe27a; padding:0 1px; border-radius:2px; }
        .of-badge { padding:.15rem .5rem; border-radius:6px; font-size:.7rem; font-weight:700; color:#fff; }
        .of-ICA { background:var(--sunarp-turquesa); }
        .of-PISCO { background:var(--sunarp-green); }

        /* ----- Modal detalle ----- */
        .modal-fondo {
            display:none; position:fixed; inset:0; background:rgba(30,40,50,.55); z-index:1000;
            align-items:flex-start; justify-content:center; padding:4vh 15px; overflow:auto;
        }
        .modal-fondo.activo { display:flex; }
        .modal-caja { background:#fff; border-radius:16px; width:100%; max-width:720px; box-shadow:0 25px 70px rgba(0,0,0,.3); overflow:hidden; }
        .modal-cab { background:linear-gradient(135deg,var(--sunarp-turquesa),var(--sunarp-green)); color:#fff; padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center; }
        .modal-cab h3 { margin:0; font-size:1.15rem; font-weight:700; }
        .modal-cerrar { background:transparent; border:none; color:#fff; font-size:1.5rem; cursor:pointer; line-height:1; }
        .modal-cuerpo { padding:1.3rem 1.5rem; }
        .kv { display:grid; grid-template-columns:130px 1fr; gap:.4rem 1rem; margin-bottom:.4rem; }
        .kv .k { font-weight:700; color:var(--sunarp-gray); font-size:.85rem; text-transform:uppercase; letter-spacing:.3px; }
        .kv .v { color:#243; font-size:.95rem; }
        .seccion-enlace { margin-top:1.2rem; border-top:2px dashed rgba(26,166,164,.3); padding-top:1rem; }
        .seccion-enlace h4 { color:var(--sunarp-turquesa); font-size:1rem; margin-bottom:.6rem; display:flex; align-items:center; gap:.5rem; }
        .enlace-item { background:rgba(26,166,164,.06); border:1px solid rgba(26,166,164,.18); border-radius:10px; padding:.7rem .9rem; margin-bottom:.6rem; }
        .enlace-item .ubic { font-weight:700; color:#243; }
        .enlace-item .sub { font-size:.85rem; color:#5a6b78; margin-top:.2rem; }
        .cargando { color:#7a8794; font-style:italic; }
    </style>
</head>
<body>
    <div class="top-bar">
        <a href="main.php" class="back-button"><i class="fas fa-arrow-left"></i><span>Volver al Inicio</span></a>
        <a href="../control/logout.php" class="logout-button"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesion</span></a>
    </div>

    <div class="header-section">
        <div class="logo-container"><img src="../assets/img/Logo2.png" alt="SUNARP"></div>
        <h1 class="main-title">Indice de Partidas Registrales</h1>
        <p class="subtitle">Sistema PROP &mdash; Oficinas de Pisco e Ica</p>
    </div>

    <div class="contenedor_tabla">
        <div class="results-header">
            <h2><i class="fas fa-database"></i> Consulta de Partidas</h2>
        </div>

        <!-- Buscador por campos independientes -->
        <div class="buscador">
            <div class="fila">
                <div class="campo">
                    <label>Oficina</label>
                    <div class="dd oficina" id="ddOficina" data-value="">
                        <button type="button" class="dd-toggle"><span class="dd-label">Todas</span><i class="fas fa-chevron-down chev"></i></button>
                        <div class="dd-menu">
                            <div class="dd-opt sel" data-value="">Todas</div>
                            <div class="dd-opt" data-value="ICA">Ica</div>
                            <div class="dd-opt" data-value="PISCO">Pisco</div>
                        </div>
                    </div>
                </div>
                <div class="campo">
                    <label>Registro</label>
                    <div class="dd registro" id="ddRegistro" data-value="">
                        <button type="button" class="dd-toggle"><span class="dd-label">Todos</span><i class="fas fa-chevron-down chev"></i></button>
                        <div class="dd-menu">
                            <input type="text" class="dd-search" placeholder="Escriba para filtrar..." autocomplete="off">
                            <div class="dd-opt sel" data-value="">Todos</div>
                            <?php foreach ($CATALOGO as $code => $label): if (!empty($presentes[$code])): ?>
                                <div class="dd-opt" data-value="<?php echo $code; ?>"><?php echo $label; ?></div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="campo nombre">
                    <label for="fNombre">Nombre / Razon social</label>
                    <input type="text" id="fNombre" placeholder="Escriba un nombre y presione Enter" autocomplete="off">
                </div>
                <div class="campo chico">
                    <label for="fTomo">Tomo</label>
                    <input type="text" id="fTomo" placeholder="Ej. 19" autocomplete="off">
                </div>
                <div class="campo chico">
                    <label for="fFolio">Folio</label>
                    <input type="text" id="fFolio" placeholder="Ej. 3" autocomplete="off">
                </div>
                <div class="campo chico">
                    <label for="fFicha">Ficha</label>
                    <input type="text" id="fFicha" placeholder="Ej. 1024" autocomplete="off">
                </div>
                <div class="btns">
                    <button class="btn-buscar" id="btnBuscar"><i class="fas fa-search"></i> Buscar</button>
                    <button class="btn-limpiar" id="btnLimpiar"><i class="fas fa-eraser"></i> Limpiar</button>
                </div>
            </div>
        </div>

        <div class="results-content">
            <table id="tabla" class="table table-striped table-hover table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Oficina</th><th>Registro</th><th>Tomo</th><th>Folio</th>
                        <th>Ficha</th><th>Nombre</th><th>Detalle</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal de detalle -->
    <div class="modal-fondo" id="modal">
        <div class="modal-caja">
            <div class="modal-cab">
                <h3><i class="fas fa-file-lines"></i> Detalle de la partida</h3>
                <button class="modal-cerrar" id="modalCerrar">&times;</button>
            </div>
            <div class="modal-cuerpo" id="modalCuerpo"></div>
        </div>
    </div>

    <script src="../assets/estilos/js/jquery.js"></script>
    <script src="../assets/estilos/js/Datatables/jquery.dataTables.min.js"></script>
    <script src="../assets/estilos/js/Datatables/dataTables.bootstrap5.min.js"></script>
    <script>
    var LBL = <?php echo json_encode($CATALOGO, JSON_UNESCAPED_UNICODE); ?>;
    // Etiquetas legibles de los campos de "detalle"
    var FLAB = {
        APO:'Apoderado', FUNDO:'Fundo / Predio', ASUNTO:'Asunto',
        DPTO:'Departamento', PRO:'Provincia', DIS:'Distrito',
        TPRE:'Tipo de predio', UBICAC:'Ubicacion', AREA:'Area', UMED:'Unidad',
        UCAT:'Unidad catastral', FAB:'Fabrica', ANOT:'Anotacion', GRAV:'Gravamen',
        TIT:'Titulo', FEC:'Fecha', NUM:'N. de orden', TIPO:'Tipo', EST:'Estado'
    };
    function esc(s){ return (s==null?'':String(s)).replace(/[&<>]/g,function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;'}[m]; }); }
    var termino = '';   // texto efectivamente buscado (para resaltar)
    function resaltar(texto, term){
        var t = (texto==null?'':String(texto));
        if(!term) return esc(t);
        var tU = t.toUpperCase(), q = term.toUpperCase(), out='', i=0, idx;
        while((idx = tU.indexOf(q, i)) !== -1){
            out += esc(t.slice(i, idx)) + '<mark>' + esc(t.slice(idx, idx+q.length)) + '</mark>';
            i = idx + q.length;
        }
        return out + esc(t.slice(i));
    }
    function fmtNum(x){ var n=parseFloat(x); if(isNaN(n)) return x; return (String(n).indexOf('.')<0)?String(n):String(n).replace(/\.?0+$/,''); }
    // Convierte el JSON "extra" en pares {k: etiqueta, v: valor legible}
    function pares(extraStr){
        var o={}; try{ o=JSON.parse(extraStr||'{}'); }catch(e){ o={}; }
        var res=[];
        Object.keys(o).forEach(function(k){
            var v=o[k]; if(v==='' || v==null) return;
            if(k==='UMED') return;                 // se muestra junto al Area
            var val=v;
            if(k==='TPRE')      val=(v==='U'?'Urbano':(v==='R'?'Rustico':v));
            else if(k==='GRAV') val=(v==='S'?'Si':(v==='N'?'No':v));
            else if(k==='AREA') val=fmtNum(v)+(o.UMED?' '+o.UMED:'');
            res.push({k:(FLAB[k]||k), v:val});
        });
        return res;
    }
    function detalleCompacto(extraStr){
        return pares(extraStr).map(function(x){ return '<b>'+esc(x.k)+':</b> '+resaltar(x.v, termino); }).join(' &middot; ');
    }

    $(function(){
        var tabla = $('#tabla').DataTable({
            processing:true,
            serverSide:true,
            ordering:false,
            searching:false,               // usamos nuestros propios campos
            autoWidth:false,               // respeta los anchos definidos por CSS
            deferLoading:0,                // NO carga datos hasta que el usuario busque
            dom:'lrtip',                   // sin la caja de busqueda global
            ajax:{
                url:'../modelo/prop_datos.php',
                data:function(d){
                    d.oficina  = ddVal('ddOficina');
                    d.registro = ddVal('ddRegistro');
                    d.tomo     = $('#fTomo').val().trim();
                    d.folio    = $('#fFolio').val().trim();
                    d.ficha    = $('#fFicha').val().trim();
                    d.nombre   = $('#fNombre').val().trim();
                },
                dataSrc:function(json){
                    if (json && json.sessionExpired) {
                        window.location.replace('../index.php');
                        return [];
                    }
                    return json.data || [];
                }
            },
            columns:[
                {data:'oficina', render:function(v){ return '<span class="of-badge of-'+esc(v)+'">'+esc(v)+'</span>'; }},
                {data:'registro', render:function(v){ return esc(LBL[v]||v); }},
                {data:'tomo'}, {data:'folio'}, {data:'ficha'},
                {data:'nombre', render:function(v){ return resaltar(v, termino); }},
                {data:'extra', orderable:false, render:function(v){ return detalleCompacto(v); }}
            ],
            pageLength:25,
            lengthMenu:[[10,25,50,100],[10,25,50,100]],
            language:{
                url:'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                emptyTable:'Ingrese un criterio de busqueda y presione Buscar.',
                zeroRecords:'Sin resultados para la busqueda.'
            }
        });

        function buscar(){ termino = $('#fNombre').val().trim(); tabla.ajax.reload(); }

        // ---- Desplegables propios (abren hacia abajo; NO disparan la busqueda) ----
        function ddVal(id){ return $('#'+id).attr('data-value') || ''; }
        function ddReset(id, etiqueta){
            var dd = $('#'+id);
            dd.attr('data-value', '');
            dd.find('.dd-label').text(etiqueta);
            dd.find('.dd-opt').removeClass('sel').first().addClass('sel');
        }
        $('.dd-toggle').on('click', function(e){
            e.stopPropagation();
            var dd = $(this).closest('.dd');
            $('.dd').not(dd).removeClass('open');
            dd.toggleClass('open');
            if (dd.hasClass('open')) {
                var s = dd.find('.dd-search');
                if (s.length) { s.val('').trigger('input'); setTimeout(function(){ s.focus(); }, 30); }
            }
        });
        // Escribir dentro del desplegable para filtrar las opciones
        $('.dd-search').on('click', function(e){ e.stopPropagation(); });
        $('.dd-search').on('input', function(){
            var q = $(this).val().trim().toUpperCase();
            $(this).closest('.dd-menu').find('.dd-opt').each(function(){
                $(this).toggleClass('oculto', q !== '' && $(this).text().toUpperCase().indexOf(q) === -1);
            });
        });
        $('.dd-opt').on('click', function(){
            var dd = $(this).closest('.dd');
            dd.attr('data-value', $(this).attr('data-value'));
            dd.find('.dd-label').text($(this).text());
            dd.find('.dd-opt').removeClass('sel');
            $(this).addClass('sel');
            dd.removeClass('open');   // solo actualiza el valor; se filtra al pulsar Buscar
        });
        $(document).on('click', function(){ $('.dd').removeClass('open'); });

        $('#btnBuscar').on('click', buscar);
        $('#btnLimpiar').on('click', function(){
            $('#fTomo,#fFolio,#fFicha,#fNombre').val('');
            ddReset('ddOficina', 'Todas');
            ddReset('ddRegistro', 'Todos');
            buscar();
        });
        $('#fTomo,#fFolio,#fFicha,#fNombre').on('keydown', function(e){
            if (e.key === 'Enter') { e.preventDefault(); buscar(); }
        });

        // Clic en una fila -> detalle
        $('#tabla tbody').on('click', 'tr', function(){
            var d = tabla.row(this).data();
            if (d) abrirDetalle(d);
        });

        function abrirDetalle(d){
            var html = '<div class="kv"><div class="k">Oficina</div><div class="v">'+esc(d.oficina)+'</div>'
                     + '<div class="k">Registro</div><div class="v">'+esc(LBL[d.registro]||d.registro)+'</div>'
                     + '<div class="k">Tomo</div><div class="v">'+esc(d.tomo||'-')+'</div>'
                     + '<div class="k">Folio</div><div class="v">'+esc(d.folio||'-')+'</div>'
                     + '<div class="k">Ficha</div><div class="v">'+esc(d.ficha||'-')+'</div>'
                     + '<div class="k">Nombre</div><div class="v">'+(d.nombre?resaltar(d.nombre,termino):'-')+'</div>';
            pares(d.extra).forEach(function(x){
                html += '<div class="k">'+esc(x.k)+'</div><div class="v">'+resaltar(x.v,termino)+'</div>';
            });
            html += '</div>';

            // Propiedad Inmueble: enlazar Titular <-> Predio
            if (d.registro === 'PROP1' || d.registro === 'PROP2') {
                var titulo = (d.registro === 'PROP1')
                    ? '<i class="fas fa-map-location-dot"></i> Ubicacion del predio'
                    : '<i class="fas fa-user"></i> Titular(es) del predio';
                html += '<div class="seccion-enlace"><h4>'+titulo+'</h4>'
                      + '<div id="enlaceCont" class="cargando">Buscando...</div></div>';
            }
            $('#modalCuerpo').html(html);
            $('#modal').addClass('activo');

            if (d.registro === 'PROP1' || d.registro === 'PROP2') {
                $.getJSON('../modelo/prop_datos.php', {
                    modo:'enlace', oficina:d.oficina, registro:d.registro,
                    tomo:d.tomo, folio:d.folio, ficha:d.ficha
                }).done(function(res){
                    if (res && res.sessionExpired) { window.location.replace('../index.php'); return; }
                    var cont = $('#enlaceCont');
                    if (!res.data || !res.data.length) { cont.removeClass('cargando').text('Sin informacion enlazada para esta partida.'); return; }
                    var out = '';
                    res.data.forEach(function(r){
                        var det = detalleCompacto(r.extra);
                        out += '<div class="enlace-item"><div class="ubic">'+esc(r.nombre||'(sin dato)')+'</div>'
                             + (det ? '<div class="sub">'+det+'</div>' : '')
                             + '<div class="sub">Tomo '+esc(r.tomo||'-')+' &middot; Folio '+esc(r.folio||'-')+' &middot; Ficha '+esc(r.ficha||'-')+'</div></div>';
                    });
                    cont.removeClass('cargando').html(out);
                }).fail(function(){ $('#enlaceCont').removeClass('cargando').text('No se pudo cargar la informacion enlazada.'); });
            }
        }

        $('#modalCerrar').on('click', function(){ $('#modal').removeClass('activo'); });
        $('#modal').on('click', function(e){ if (e.target === this) $(this).removeClass('activo'); });
        $(document).on('keydown', function(e){ if (e.key === 'Escape') $('#modal').removeClass('activo'); });
    });
    </script>
</body>
</html>
