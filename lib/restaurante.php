<?php
/*------------------------------------------------------------------*/
/**
 * @brief Genera el código HTML de un botón para regresar al inicio.
 * @return string Cadena de texto con el botón HTML.
 * @pre Ninguna.
 * @post El botón redirige a la opción raíz mediante JavaScript.
 */
function fn_boton_menu_principal()
/*--------------------------------------------------------------------*/
{
    $scr_menu = "navegarA('');";
    $retorno = "<button onClick=\"$scr_menu\">Menu del programa</button>";
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Despliega los botones principales de navegación del programa.
 * @param resource $conexion_bd Recurso de conexión a la base de datos.
 * @return string Fragmento HTML con el menú de navegación.
 * @pre El sistema debe estar cargado y la conexión debe ser válida.
 * @post Permite navegar hacia el menú de platos o el sistema de pedidos.
 */
function fn_menu_opciones($conn)
/*--------------------------------------------------------------------*/
{
    $rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
    $nombre = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Invitado';

    $scr_menu = "navegarA('desplegar_menu');";
    $scr_pedido = "navegarA('realizar_pedidos');";
    $scr_mesas = "navegarA('gestionar_mesas');";

    // Notificación 1: platos listos (estado=1) para el mesero
    $res_listos = procesar_query("SELECT COUNT(*) AS total FROM ordenes WHERE estado = 1", $conn);
    $platos_listos = intval($res_listos->datos[0]['total']);
    $badge_mesero = ($platos_listos > 0) ? " <span style='background:#dc3545;color:white;border-radius:50%;padding:2px 7px;font-size:0.8em;'>$platos_listos</span>" : "";

    // Notificación 2: reservaciones próximas (en los próximos 30 min) para el maitre
    $sql_prox = "SELECT COUNT(*) AS total FROM horarios WHERE inicio BETWEEN NOW() AND NOW() + INTERVAL '30 minutes'";
    $res_prox = procesar_query($sql_prox, $conn);
    $reservas_prox = intval($res_prox->datos[0]['total']);
    $alerta_maitre = "";
    if ($reservas_prox > 0 && ($rol == 'administrador' || $rol == 'maitre')) {
        $alerta_maitre = "<div style='background:#fff3cd;border:1px solid #ffc107;padding:8px 14px;border-radius:6px;margin-bottom:10px;'>"
                       . "⚠️ <b>$reservas_prox reservación(es)</b> próxima(s) a comenzar (en los próximos 30 minutos). "
                       . "<button onClick=\"navegarA('gestionar_reservaciones')\">Ver Reservaciones</button></div>";
    }

    $retorno = "<div style='display:flex; justify-content:space-between; align-items:center;'>"
             . "<span>Bienvenido, <b>$nombre</b> ($rol)</span>"
             . "<a href='index.php?opcion=logout' style='color:#dc3545; font-weight:bold; text-decoration:none;'>Cerrar Sesión</a>"
             . "</div>"
             . "<h1>Opciones del programa</h1>"
             . $alerta_maitre
             . "<div class='MENU_OPCIONES'>";

    // Reglas de visibilidad por rol
    $isAdmin = ($rol == 'administrador');
    $isMaitre = ($rol == 'maitre');
    $isMesero = ($rol == 'mesero' || $rol == 'barman');
    $isCocinero = ($rol == 'cocinero');

    if ($isAdmin || $isMaitre || $isMesero || $isCocinero) {
        $retorno .= "<button onClick=\"$scr_menu\">Desplegar Menu</button>";
    }
    
    if ($isAdmin || $isMesero) {
        $retorno .= "<button onClick=\"$scr_pedido\">Realizar Pedido</button>";
    }

    if ($isAdmin) {
        $retorno .= "<button onClick=\"$scr_mesas\">Gestión de Mesas</button>";
        $retorno .= "<button onClick=\"navegarA('gestionar_menu')\">Gestión de Menú</button>";
        $retorno .= "<button style='background:#fd7e14; color:white;' onClick=\"navegarA('gestionar_empleados')\">Gestión de Empleados</button>";
    }

    if ($isAdmin || $isMaitre) {
        $retorno .= "<button onClick=\"navegarA('gestionar_reservaciones')\">Manejo de Reservaciones (Maitre)</button>";
        $retorno .= "<button style='background:#20c997; color:white;' onClick=\"navegarA('historial_cliente')\">Historial de Clientes</button>";
    }

    if ($isAdmin || $isCocinero) {
        $retorno .= "<button style='background:#dc3545;' onClick=\"navegarA('pantallas_operacion', {rol: 'cocina'})\">Monitor de Cocina</button>";
    }

    if ($isAdmin || $isMesero) {
        $retorno .= "<button style='background:#007bff;' onClick=\"navegarA('pantallas_operacion', {rol: 'mesero'})\">Avisos de Entrega$badge_mesero</button>";
    }

    if ($isAdmin) {
        $retorno .= "<button style='background:#6f42c1; color:white;' onClick=\"navegarA('reportes_estadisticas')\">Reportes Administrativos</button>";
    }

    $retorno .= "</div>";
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Consulta y visualiza la carta de platos agrupados por tipo.
 * @param resource $conexion_bd Recurso de conexión a la base de datos.
 * @return string Lista HTML (ul) con las secciones y platos del restaurante.
 * @pre Debe existir la tabla platos y tipos con datos íntegros.
 * @post Genera la visualización estructurada del menú.
 */
function fn_desplegar_menu($conn)
/*--------------------------------------------------------------------*/
{
    $sentencia = "
    SELECT platos.id
         , platos.nombre
         , tipos.nombre AS tipo
         , descripcion
         , precio 
    FROM platos
    LEFT JOIN tipos ON platos.tipo_id=tipos.id
        ORDER BY tipos.id, platos.nombre
        ;";
    $resultado = procesar_query($sentencia, $conn);
    $retorno = fn_boton_menu_principal()."<br />";
    $tipo = "<div class='SECCION'>";
    foreach ($resultado->datos AS $plato) {
    if ($tipo != $plato['tipo']) {
        $retorno.= "</div><div class='SECCION'><h2>".$plato['tipo']."</h2>";
        $tipo = $plato['tipo'];
    }
        $retorno.= "<div class='PLATO'>"
                 .     "<span class='NOMBRE'>".$plato['nombre']."</span>"
                 .     "<span class='DESCRIPCION'>".$plato['descripcion']."</span>"
                 .     "<span class='PRECIO'>$".$plato['precio']."</span>"
                 . "</div>";
    }
    $retorno .= "</div>";
    $retorno = "<ul>$retorno</ul>";
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Gestiona el flujo de pedidos, listando mesas o procesando la comanda.
 * @param resource $conn Recurso de la conexión a la base de datos.
 * @return string HTML interactivo con las mesas u opciones del Point of Sale.
 * @pre Debe venir el token si hay modificación.
 * @post Interactúa con pedidos, creando uno si es primera vez para la mesa, e inserta órdenes.
 */
function fn_realizar_pedidos($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal()."<br /><h2>Panel de Meseros (POS)</h2>";

    if (isset($_REQUEST['mesa_id']))
        $accion = "pedido_mesa";
    else
        $accion = "listar_mesas";

    switch ($accion) {
    case 'listar_mesas':
        $sentencia = "
            SELECT MES.id, MES.sillas, HOR.inicio, USR.nombre as nombre_usr
            FROM mesas AS MES
            LEFT JOIN (
                SELECT mesa_id, inicio, reservacion_id FROM horarios WHERE inicio::date = CURRENT_DATE
            ) AS HOR ON MES.id = HOR.mesa_id
            LEFT JOIN reservaciones AS RES ON RES.id = HOR.reservacion_id
            LEFT JOIN usuarios      AS USR ON USR.id = RES.cliente_id
            ORDER BY MES.id
            ";
        $resultado = procesar_query($sentencia, $conn);
 
        $botones_mesas = "";
        foreach ($resultado->datos as $mesa) {
            $nombre_cli = $mesa['nombre_usr'] ? $mesa['nombre_usr'] : "Mesa Libre";
            $color = $mesa['nombre_usr'] ? "#28a745" : "#6c757d";
            $script = "navegarA('realizar_pedidos', {mesa_id: ".$mesa['id']."});";
            $botones_mesas.= "<button style='background:$color; color:white; margin:5px;' onClick=\"$script\">Mesa ".$mesa['id']." ($nombre_cli)</button>";
        }
        $retorno .= "<div class='MENU_OPCIONES'>".$botones_mesas."</div>";
        break;
        
    case 'pedido_mesa':
        $mesa_id = fn_sanitizar($_REQUEST['mesa_id']);
        $sql_res = "SELECT RES.cliente_id, USR.nombre FROM horarios HOR "
                 . "JOIN reservaciones RES ON HOR.reservacion_id = RES.id "
                 . "JOIN usuarios USR ON RES.cliente_id = USR.id "
                 . "WHERE HOR.mesa_id = $mesa_id AND HOR.inicio::date = CURRENT_DATE LIMIT 1";
        $res = procesar_query($sql_res, $conn);
        
        if ($res->cantidad == 0) {
            // No hay reserva: permitir elegir cliente para abrir la mesa
            $sel_cli = isset($_REQUEST['cliente_vincular']) ? intval($_REQUEST['cliente_vincular']) : 0;
            if ($sel_cli > 0) {
                // Crear reserva inmediata para hoy
                $sql_r = "INSERT INTO reservaciones (cliente_id, cantidad, estado) VALUES ($sel_cli, 1, 0) RETURNING id";
                $ins_r = procesar_query($sql_r, $conn);
                $rid = $ins_r->datos[0]['id'];
                pg_query($conn, "INSERT INTO horarios (mesa_id, reservacion_id, inicio, duracion) VALUES ($mesa_id, $rid, CURRENT_TIMESTAMP, '02:00:00')");
                // Recargar para entrar en el flujo normal
                return "<script>navegarA('realizar_pedidos', {mesa_id:$mesa_id})</script>";
            }

            $users = procesar_query("SELECT id, nombre FROM usuarios ORDER BY nombre", $conn);
            $opts = ""; foreach($users->datos as $u) $opts .= "<option value='".$u['id']."'>".$u['nombre']."</option>";
            $retorno .= "<div class='SECCION'><h3>Mesa $mesa_id Libre</h3>"
                      . "<p>Para abrir pedido selecciona un cliente:</p>"
                      . "Cliente: <select id='vinc_cli'>$opts</select> "
                      . "<button onClick=\"navegarA('realizar_pedidos', {mesa_id:$mesa_id, cliente_vincular: document.getElementById('vinc_cli').value})\">Abrir Mesa</button></div>";
            return $retorno;
        }
        
        $cliente_id = intval($res->datos[0]['cliente_id']);
        $nom_cli = $res->datos[0]['nombre'];
        $mesero_id_defecto = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 1; 
        
        $sql_pedido = "SELECT id FROM pedidos WHERE cliente_id = $cliente_id AND solicitado::date = CURRENT_DATE";
        $res_ped = procesar_query($sql_pedido, $conn);
        if ($res_ped->cantidad > 0) {
            $pedido_id = intval($res_ped->datos[0]['id']);
        } else {
            $sql_ins_ped = "INSERT INTO pedidos (cliente_id, mesero_id, solicitado) VALUES ($cliente_id, $mesero_id_defecto, NOW()) RETURNING id";
            $res_ins = procesar_query($sql_ins_ped, $conn);
            $pedido_id = intval($res_ins->datos[0]['id']);
        }
        
        $sub_accion = isset($_REQUEST['sub_accion']) ? fn_sanitizar($_REQUEST['sub_accion']) : '';
        if ($sub_accion == 'agregar_plato') {
            fn_validar_token($_REQUEST['token']);
            $plato_id = fn_sanitizar($_REQUEST['plato_id']);
            $sql_ord = "INSERT INTO ordenes (plato_id, pedido_id, estado, cantidad) VALUES ($plato_id, $pedido_id, 0, 1)";
            pg_query($conn, $sql_ord);
        }
        
        $retorno .= "<h3>Mesa $mesa_id | Cliente: $nom_cli</h3>";
        
        $platos = procesar_query("SELECT id, nombre, precio FROM platos", $conn);
        $retorno .= "<div class='SECCION'><h4>Añadir a la cuenta:</h4>";
        foreach($platos->datos as $p) {
            $scr = "navegarA('realizar_pedidos', {mesa_id:$mesa_id, sub_accion:'agregar_plato', plato_id:".$p['id']."})";
            $retorno .= "<button onClick=\"$scr\"> + ".$p['nombre']." ($".$p['precio'].")</button> ";
        }
        $retorno .= "</div>";
        
        $comanda = procesar_query("SELECT PLA.nombre, ORD.cantidad, ORD.estado FROM ordenes ORD JOIN platos PLA ON ORD.plato_id = PLA.id WHERE ORD.pedido_id = $pedido_id", $conn);
        $retorno .= "<div class='SECCION'><h4>Comanda Actual de la Mesa:</h4><ul>";
        if ($comanda->cantidad == 0) $retorno .= "<li>Sin pedidos aún.</li>";
        foreach($comanda->datos as $c) {
            $estado = ($c['estado'] == 0) ? 'En Cocina (0)' : (($c['estado'] == 1) ? 'Listo (1)' : 'Entregado (2)');
            $retorno .= "<li class='PLATO'>".$c['cantidad']."x <span class='NOMBRE'>".$c['nombre']."</span> - $estado</li>";
        }
        $retorno .= "</ul></div>";
        break;
    }
    return $retorno;
}
/*------------------------------------------------------------------*/
/**
 * @brief Panel administrativo para gestionar mesas y aforo total (CRUD).
 * @param resource $conn Recurso de la conexión activa a Postgres.
 * @return string Fragmento HTML con la información y formularios.
 * @pre Debe estar autenticado y se valida el token en las modificaciones (POST).
 * @post Añade, elimina o modifica registros en tabla "mesas".
 */
function fn_gestionar_mesas($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal()."<br /><h2>Gestión de Mesas</h2>";
    $accion = isset($_REQUEST['accion']) ? fn_sanitizar($_REQUEST['accion']) : '';
    
    if ($accion !== '') {
        $token_in = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
        fn_validar_token($token_in);
        
        if ($accion == 'crear') {
            $sillas = fn_sanitizar($_REQUEST['sillas']);
            $sql = "INSERT INTO mesas (sillas) VALUES ($sillas)";
            pg_query($conn, $sql);
        } elseif ($accion == 'eliminar') {
            $id = fn_sanitizar($_REQUEST['id']);
            $sql = "DELETE FROM mesas WHERE id = $id";
            pg_query($conn, $sql);
        } elseif ($accion == 'actualizar') {
            $id = fn_sanitizar($_REQUEST['id']);
            $sillas = fn_sanitizar($_REQUEST['sillas']);
            $sql = "UPDATE mesas SET sillas = $sillas WHERE id = $id";
            pg_query($conn, $sql);
        }
    }
    
    $func_crear = "navegarA('gestionar_mesas', {accion: 'crear', sillas: document.getElementById('num_sillas').value})";
    $retorno .= "<div class='SECCION'><label>Añadir Mesa - Sillas: <input type='number' id='num_sillas' min='1'></label>";
    $retorno .= "<button onClick=\"$func_crear\">Crear</button></div>";
    
    $sentencia = "SELECT id, sillas FROM mesas ORDER BY id";
    $resultado = procesar_query($sentencia, $conn);
    
    $total_sillas = 0;
    $retorno .= "<ul>";
    foreach ($resultado->datos as $mesa) {
        $total_sillas += $mesa['sillas'];
        $script_del = "navegarA('gestionar_mesas', {accion: 'eliminar', id: ".$mesa['id']."})";
        $script_upd = "navegarA('gestionar_mesas', {accion: 'actualizar', id: ".$mesa['id'].", sillas: document.getElementById('sillas_".$mesa['id']."').value})";
        
        $retorno .= "<li class='PLATO'>";
        $retorno .= "<span class='NOMBRE'>Mesa #".$mesa['id']."</span>";
        $retorno .= "<span><input type='number' id='sillas_".$mesa['id']."' value='".$mesa['sillas']."' style='width:50px;'> sillas </span>";
        $retorno .= "<button onClick=\"$script_upd\">Actualizar</button>";
        $retorno .= "<button onClick=\"$script_del\">Eliminar</button>";
        $retorno .= "</li>";
    }
    $retorno .= "</ul>";
    $retorno .= "<div class='SECCION'><h3>Cupo Total Actual: $total_sillas personas</h3></div>";
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Panel administrativo para gestionar la carta del restaurante (CRUD de menú).
 * @param resource $conn Recurso de la conexión activa a Postgres.
 * @return string Fragmento HTML con la información y formularios.
 * @pre Validación del token de seguridad en operaciones POST.
 * @post Añade, elimina o modifica registros en tabla "platos".
 */
function fn_gestionar_menu($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal()."<br /><h2>Gestión del Menú</h2>";
    $accion = isset($_REQUEST['accion']) ? fn_sanitizar($_REQUEST['accion']) : '';
    
    if ($accion !== '') {
        $token_in = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
        fn_validar_token($token_in);
        
        if ($accion == 'crear') {
            $nombre = fn_sanitizar($_REQUEST['nombre']);
            $descripcion = fn_sanitizar($_REQUEST['descripcion']);
            $precio = fn_sanitizar($_REQUEST['precio']);
            $tipo_id = fn_sanitizar($_REQUEST['tipo_id']);
            $tiempo = fn_sanitizar($_REQUEST['tiempo']);
            $sql = "INSERT INTO platos (nombre, descripcion, precio, tipo_id, tiempo) VALUES ('$nombre', '$descripcion', $precio, $tipo_id, '$tiempo minutes'::interval)";
            pg_query($conn, $sql);
        } elseif ($accion == 'actualizar') {
            $id = fn_sanitizar($_REQUEST['id']);
            $nombre = fn_sanitizar($_REQUEST['nombre']);
            $descripcion = fn_sanitizar($_REQUEST['descripcion']);
            $precio = fn_sanitizar($_REQUEST['precio']);
            $tipo_id = fn_sanitizar($_REQUEST['tipo_id']);
            $tiempo = fn_sanitizar($_REQUEST['tiempo']);
            $sql = "UPDATE platos SET nombre='$nombre', descripcion='$descripcion', precio=$precio, tipo_id=$tipo_id, tiempo='$tiempo minutes'::interval WHERE id=$id";
            pg_query($conn, $sql);
        } elseif ($accion == 'eliminar') {
            $id = fn_sanitizar($_REQUEST['id']);
            $sql = "DELETE FROM platos WHERE id = $id";
            pg_query($conn, $sql);
        }
    }
    
    $filtro_tipo = isset($_REQUEST['filtro_tipo']) ? fn_sanitizar($_REQUEST['filtro_tipo']) : '';
    
    // Formularios
    $sentencia_tipos = "SELECT id, nombre FROM tipos ORDER BY nombre";
    $resultado_tipos = procesar_query($sentencia_tipos, $conn);
    $opciones_tipos = "";
    $opciones_filtro = "<option value=''>Todos los tipos</option>";
    foreach($resultado_tipos->datos as $tipo) {
        $opciones_tipos .= "<option value='".$tipo['id']."'>".$tipo['nombre']."</option>";
        
        $sel = ($filtro_tipo == $tipo['id']) ? "selected" : "";
        $opciones_filtro .= "<option value='".$tipo['id']."' $sel>".$tipo['nombre']."</option>";
    }
    
    // Sección de Filtro
    $func_filtrar = "navegarA('gestionar_menu', {filtro_tipo: document.getElementById('filtro_tipo_id').value})";
    $retorno .= "<div class='SECCION'><h3>Filtrar por Tipo</h3>"
              . "<select id='filtro_tipo_id'>$opciones_filtro</select> "
              . "<button onClick=\"$func_filtrar\">Filtrar Menú</button></div>";
    
    $func_crear = "navegarA('gestionar_menu', {accion: 'crear', filtro_tipo: '$filtro_tipo', nombre: document.getElementById('nuevo_nombre').value, descripcion: document.getElementById('nueva_desc').value, precio: document.getElementById('nuevo_precio').value, tipo_id: document.getElementById('nuevo_tipo').value, tiempo: document.getElementById('nuevo_tiempo').value})";
    $retorno .= "<div class='SECCION'><h3>Crear Nuevo Plato</h3>"
              . "<input type='text' id='nuevo_nombre' placeholder='Nombre del plato'> "
              . "<input type='text' id='nueva_desc' placeholder='Descripción breve'> "
              . "$<input type='number' id='nuevo_precio' placeholder='Precio (ej: 15.50)' step='0.01'> "
              . "<input type='number' id='nuevo_tiempo' placeholder='Tiempo prep. (min)' min='1' style='width:120px;'> min "
              . "<select id='nuevo_tipo'>$opciones_tipos</select> "
              . "<button onClick=\"$func_crear\">Agregar a Carta</button></div>";
    
    $where = "";
    if ($filtro_tipo != "") {
        $where = " WHERE platos.tipo_id = $filtro_tipo ";
    }
    
    $sentencia = "SELECT platos.id, platos.nombre, platos.precio, platos.descripcion, tipos.nombre as tipo, tipos.id as tipo_id, "
               . "EXTRACT(EPOCH FROM platos.tiempo)/60 AS tiempo_min "
               . "FROM platos LEFT JOIN tipos ON platos.tipo_id = tipos.id $where ORDER BY tipos.nombre, platos.nombre";
    $resultado = procesar_query($sentencia, $conn);
    
    $retorno .= "<ul>";
    foreach ($resultado->datos as $plato) {
        $pid = $plato['id'];
        $tiempo_val = intval($plato['tiempo_min']);
        $script_del = "navegarA('gestionar_menu', {accion: 'eliminar', filtro_tipo: '$filtro_tipo', id: $pid})";
        $script_upd = "navegarA('gestionar_menu', {accion: 'actualizar', filtro_tipo: '$filtro_tipo', id: $pid, nombre: document.getElementById('ed_nom_$pid').value, descripcion: document.getElementById('ed_desc_$pid').value, precio: document.getElementById('ed_prec_$pid').value, tipo_id: document.getElementById('ed_tipo_$pid').value, tiempo: document.getElementById('ed_tiempo_$pid').value})";
        
        // Build type options for this row with current type selected
        $opts_row = "";
        foreach ($resultado_tipos->datos as $t) {
            $sel = ($t['id'] == $plato['tipo_id']) ? 'selected' : '';
            $opts_row .= "<option value='".$t['id']."' $sel>".$t['nombre']."</option>";
        }

        $retorno .= "<li class='PLATO' style='margin-bottom:8px;'>";
        $retorno .= "<input type='text' id='ed_nom_$pid' value='".htmlspecialchars($plato['nombre'])."' style='width:130px;'> ";
        $retorno .= "<input type='text' id='ed_desc_$pid' value='".htmlspecialchars($plato['descripcion'])."' style='width:160px;'> ";
        $retorno .= "$<input type='number' id='ed_prec_$pid' value='".$plato['precio']."' step='0.01' style='width:70px;'> ";
        $retorno .= "<input type='number' id='ed_tiempo_$pid' value='$tiempo_val' min='1' style='width:60px;'> min ";
        $retorno .= "<select id='ed_tipo_$pid'>$opts_row</select> ";
        $retorno .= "<button onClick=\"$script_upd\">Guardar</button> ";
        $retorno .= "<button onClick=\"$script_del\">Quitar de menú</button>";
        $retorno .= "</li>";
    }
    $retorno .= "</ul>";
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Interfaz para que el Maitre asigne sillas y organice la agenda sin sobre-cupo.
 * @param resource $conn Recurso de la conexión activa a Postgres.
 * @return string Fragmento HTML con las validaciones de reserva.
 * @pre Validación del token. Las mesas y usuarios (clientes) deben existir.
 * @post Genera un registro en reservaciones y su asociado en horarios validando el lapso.
 */
function fn_gestionar_reservaciones($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal()."<br /><h2>Gestión de Reservaciones (Maitre)</h2>";
    $accion = isset($_REQUEST['accion']) ? fn_sanitizar($_REQUEST['accion']) : '';

    if ($accion !== '') {
        $token_in = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
        fn_validar_token($token_in);
        
        if ($accion == 'crear') {
            $cliente_id = fn_sanitizar($_REQUEST['cliente_id']);
            $mesa_id = fn_sanitizar($_REQUEST['mesa_id']);
            $inicio = fn_sanitizar($_REQUEST['inicio']); // Formato: YYYY-MM-DD HH:MM
            $cantidad = fn_sanitizar($_REQUEST['cantidad']);
            
            // REGLA: No cruzar reservaciones en la misma mesa (Margen de 2 horas)
            $sql_cruce = "SELECT count(*) AS cruce FROM horarios WHERE mesa_id = $mesa_id AND "
                       . "((inicio <= '$inicio' AND (inicio + interval '1 hours 59 minutes') >= '$inicio') OR "
                       . "(inicio >= '$inicio' AND inicio <= ('$inicio'::timestamp + interval '1 hours 59 minutes')))";
            $res_cruce = procesar_query($sql_cruce, $conn);
            
            if ($res_cruce->datos[0]['cruce'] > 0) {
                $retorno .= "<div style='color:red'><strong>Error:</strong> La mesa $mesa_id ya se encuentra ocupada o reservada en el bloque horario seleccionado (2 horas de tolerancia).</div>";
            } else {
                // REGLA: No superar el cupo total del restaurante en ese horario general
                $sql_agregado = "SELECT sum(R.cantidad) AS ocupados FROM horarios H "
                              . "JOIN reservaciones R ON H.reservacion_id = R.id "
                              . "WHERE ((H.inicio <= '$inicio' AND (H.inicio + interval '1 hours 59 minutes') >= '$inicio') OR "
                              . "(H.inicio >= '$inicio' AND H.inicio <= ('$inicio'::timestamp + interval '1 hours 59 minutes')))";
                $res_agregado = procesar_query($sql_agregado, $conn);
                $uso_actual = intval($res_agregado->datos[0]['ocupados']);
                
                $sql_capacidad = "SELECT sum(sillas) as maximo FROM mesas";
                $res_capacidad = procesar_query($sql_capacidad, $conn);
                $limite = intval($res_capacidad->datos[0]['maximo']);
                
                if (($uso_actual + $cantidad) > $limite) {
                    $retorno .= "<div style='color:red'><strong>Error Capacidad General:</strong> Restaurante lleno. La cantidad sobrepasa el cupo total disponible ($limite máximo, reservando $uso_actual).</div>";
                } else {
                    // Éxito: Crear en Reservaciones
                    $sql_res = "INSERT INTO reservaciones (cliente_id, cantidad, estado) VALUES ($cliente_id, $cantidad, 0) RETURNING id";
                    $id_insert = procesar_query($sql_res, $conn);
                    if ($id_insert->cantidad > 0) {
                        $reservacion_id = $id_insert->datos[0]['id'];
                        $sql_hor = "INSERT INTO horarios (mesa_id, reservacion_id, inicio, duracion) VALUES ($mesa_id, $reservacion_id, '$inicio', '01:59:00')";
                        pg_query($conn, $sql_hor);
                        $retorno .= "<div style='color:green'>Reserva confirmada exitosamente.</div>";
                    }
                }
            }
        } elseif ($accion == 'eliminar') {
            $res_id = fn_sanitizar($_REQUEST['res_id']);
            pg_query($conn, "DELETE FROM horarios WHERE reservacion_id = $res_id");
            pg_query($conn, "DELETE FROM reservaciones WHERE id = $res_id");
            $retorno .= "<div style='color:orange'>Reserva anulada.</div>";
        }
    }

    // Constructores de Formulario
    $res_mesas = procesar_query("SELECT id, sillas FROM mesas ORDER BY id", $conn);
    $opt_mesas = ""; foreach($res_mesas->datos as $m) $opt_mesas .= "<option value='".$m['id']."'>Mesa ".$m['id']." (".$m['sillas']." Pax)</option>";
    
    // Usuarios que tengan rol de cliente o directamente listarlos. Asumimos todos para el demo
    $res_cli = procesar_query("SELECT id, nombre FROM usuarios ORDER BY nombre", $conn);
    $opt_cli = ""; foreach($res_cli->datos as $c) $opt_cli .= "<option value='".$c['id']."'>".$c['nombre']."</option>";
    
    $func_crear = "navegarA('gestionar_reservaciones', {accion:'crear', cliente_id:document.getElementById('sel_cli').value, mesa_id:document.getElementById('sel_mesa').value, inicio:document.getElementById('in_fecha').value, cantidad:document.getElementById('in_cant').value})";
    $retorno .= "<h3>Nueva Reservación:</h3><div class='SECCION'>"
              . "Cliente: <select id='sel_cli'>$opt_cli</select> "
              . "Mesa: <select id='sel_mesa'>$opt_mesas</select> "
              . "Fecha y Hora: <input type='datetime-local' id='in_fecha'> "
              . "Personas: <input type='number' id='in_cant' min='1' value='2'> "
              . "<button onClick=\"$func_crear\">Agendar</button></div>";
    
    // Listado de Reservas Existentes de las próximas horas/días
    $sql_horarios = "SELECT R.id as res_id, H.inicio, M.id as mesa, U.nombre as cliente, R.cantidad "
                  . "FROM reservaciones R JOIN horarios H ON R.id = H.reservacion_id "
                  . "JOIN mesas M ON H.mesa_id = M.id JOIN usuarios U ON R.cliente_id = U.id "
                  . "ORDER BY H.inicio DESC";
    $listado = procesar_query($sql_horarios, $conn);
    
    $retorno .= "<ul>";
    foreach ($listado->datos as $res) {
        $script_del = "navegarA('gestionar_reservaciones', {accion: 'eliminar', res_id: ".$res['res_id']."})";
        $retorno .= "<li class='PLATO'>"
                  . "<span class='NOMBRE'>El ".$res['inicio']."</span> - "
                  . "<span> Mesa ".$res['mesa']." | Cliente: ".$res['cliente']." (".$res['cantidad']." Personas)</span> "
                  . "<button onClick=\"$script_del\">Cancelar Reserva</button></li>";
    }
    $retorno .= "</ul>";

    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Centraliza los escritorios dinámicos de Cocina (preparar) y Mesero (entregar).
 * @param resource $conn Recurso a la BDD.
 * @return string Interfaz auto-refrescantable (polling).
 * @pre Implementación del Principio DRY en un solo controlador de estados.
 * @post Ejecuta sentencias UPDATE con Token CSRF.
 */
function fn_pantallas_operacion($conn)
/*--------------------------------------------------------------------*/
{
    $rol = isset($_REQUEST['rol']) ? fn_sanitizar($_REQUEST['rol']) : 'cocina';
    $titulo = ($rol == 'cocina') ? 'Monitor de Despachos en Cocina' : 'Centro de Entregas (Mesero)';
    $retorno = fn_boton_menu_principal()."<br /><h2>$titulo</h2>";
    
    // Script temporal para auto-refresco embebido (Llama a programa.js)
    $retorno .= "<script>setTimeout(() => { if(typeof fn_refrescar_automatico === 'function'){ fn_refrescar_automatico('pantallas_operacion&rol=$rol', 4000); } }, 500);</script>";
    
    $accion = isset($_REQUEST['accion']) ? fn_sanitizar($_REQUEST['accion']) : '';
    if ($accion == 'marcar_estado') {
        fn_validar_token($_REQUEST['token']);
        $orden_id = intval(fn_sanitizar($_REQUEST['orden_id']));
        $nuevo_estado = intval(fn_sanitizar($_REQUEST['nuevo_estado']));
        
        if ($nuevo_estado == 2) {
            // Si es mesero quien lo completó, marca Entregado (2) mas un Timestamp por las politicas BDD.
            pg_query($conn, "UPDATE ordenes SET estado = 2, entregado = CURRENT_TIMESTAMP WHERE id = $orden_id");
        } else {
            // Si es cocina, marca Listo (1)
            pg_query($conn, "UPDATE ordenes SET estado = $nuevo_estado WHERE id = $orden_id");
        }
    }
    
    // Condicional de Listado
    // Cocina quiere ver cosas en ESTADO 0 (En preparacion)
    // Mesero quiere ver cosas en ESTADO 1 (Listas para repartir a mesa)
    $filtro_estado = ($rol == 'cocina') ? 0 : 1;
    $boton_txt = ($rol == 'cocina') ? '¡Plato Listo!' : 'Entregar en Mesa';
    $accion_estado = ($rol == 'cocina') ? 1 : 2;
    
    $sql = "SELECT ORD.id, ORD.cantidad, PLA.nombre as plato, M.id as mesa, U.nombre as cliente, ORD.estado "
         . "FROM ordenes ORD "
         . "JOIN platos PLA ON ORD.plato_id = PLA.id "
         . "JOIN pedidos PED ON ORD.pedido_id = PED.id "
         . "JOIN usuarios U ON PED.cliente_id = U.id "
         . "LEFT JOIN reservaciones R ON PED.cliente_id = R.cliente_id "
         . "LEFT JOIN horarios H ON R.id = H.reservacion_id "
         . "LEFT JOIN mesas M ON H.mesa_id = M.id "
         . "WHERE ORD.estado = $filtro_estado "
         . "ORDER BY PED.solicitado ASC LIMIT 30";
         
    $listado = procesar_query($sql, $conn);
    
    if ($listado->cantidad == 0) {
        $retorno .= "<div class='SECCION'><center><h4><i>Esperando alertas en vivo...</i></h4></center></div>";
    } else {
        $retorno .= "<div class='SECCION_OPCIONES'><ul>";
        foreach ($listado->datos as $row) {
            $params_js = "{rol:'$rol', accion:'marcar_estado', orden_id:".$row['id'].", nuevo_estado:$accion_estado}";
            $script = "navegarA('pantallas_operacion', $params_js)";
            
            $retorno .= "<li class='PLATO'>";
            $retorno .= "<h4>Mesa ".$row['mesa']." (".$row['cliente'].") | <span style='color:darkorange'>".$row['cantidad']."x ".$row['plato']."</span></h4>";
            $retorno .= "<button style='padding:10px; font-weight:bold;' onClick=\"$script\">✓ $boton_txt</button>";
            $retorno .= "</li>";
        }
        $retorno .= "</ul></div>";
    }
    
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Dashboard interactivo para consultar funciones PL/pgSQL anidadas en BDD.
 * @param resource $conn Recurso a la BDD.
 * @return string Interfaz con estadísticas operativas del restaurante.
 * @pre Debe existir conexión.
 * @post Devuelve código HTML plano sin recarga consumiendo procedimientos predefinidos.
 */
function fn_reportes_estadisticas($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal()."<br /><h2>Reportes y Estadísticas (Dashboard)</h2>";
    
    $fecha_inicio = isset($_REQUEST['fecha_inicio']) ? fn_sanitizar($_REQUEST['fecha_inicio']) : date('Y-m-d 00:00:00');
    $fecha_fin = isset($_REQUEST['fecha_fin']) ? fn_sanitizar($_REQUEST['fecha_fin']) : date('Y-m-d 23:59:59');
    
    $func_buscar = "navegarA('reportes_estadisticas', {fecha_inicio: document.getElementById('fecha_inicio').value.replace('T', ' '), fecha_fin: document.getElementById('fecha_fin').value.replace('T', ' ')})";
    
    $retorno .= "<div class='SECCION'><h3>Filtro Paramétrico de Fechas</h3>"
              . "Desde: <input type='datetime-local' id='fecha_inicio' value='".str_replace(' ', 'T', $fecha_inicio)."'> "
              . "Hasta: <input type='datetime-local' id='fecha_fin' value='".str_replace(' ', 'T', $fecha_fin)."'> "
              . "<button onClick=\"$func_buscar\">Generar Reportes Analíticos</button></div>";
              
    // Consulta 1: Reporte Ventas por Categoría (Motor PL/pgSQL)
    $sql_ventas = "SELECT * FROM fn_reporte_ventas_tipos_plato('$fecha_inicio'::timestamp, '$fecha_fin'::timestamp)";
    $res_ventas = procesar_query($sql_ventas, $conn);
    $retorno .= "<div class='SECCION'><h3>Rentabilidad y Ventas por Categoría</h3><ul>";
    if ($res_ventas->cantidad == 0) {
        $retorno .= "<li>No hay movimientos completados en este rago de fechas.</li>";
    } else {
        foreach($res_ventas->datos as $v) {
            $retorno .= "<li class='PLATO'><span class='NOMBRE'>".$v['tipo_plato']."</span> - Cantidad Total: ".$v['cantidad_vendida']." platos vendidos | Ingresos Brutos: <b>$".$v['monto_total']."</b> (".$v['porcentaje_contribucion']."% del volumen de impacto)</li>";
        }
    }
    $retorno .= "</ul></div>";
    
    // Consulta 2: Tiempos y Desempeño Meseros (Motor PL/pgSQL)
    $sql_tiempos = "SELECT * FROM fn_calcular_tiempos_entrega(NULL, '$fecha_inicio'::timestamp, '$fecha_fin'::timestamp)";
    $res_tiempos = procesar_query($sql_tiempos, $conn);
    $retorno .= "<div class='SECCION'><h3>Algoritmo de Desempeño y Puntualidad - Meseros</h3><ul>";
    if ($res_tiempos->cantidad == 0) {
        $retorno .= "<li>No existen métricas de entregas exitosas (estado = 2) en esta franja.</li>";
    } else {
        foreach($res_tiempos->datos as $t) {
            $retorno .= "<li class='PLATO'><span class='NOMBRE'>Mesero: ".$t['mesero_nombre']."</span> - Pedidos Atendidos: ".$t['pedidos_atendidos']." | Tiempo Promedio Preparación-Mesa: <b>".$t['tiempo_promedio_entrega']."</b> | Ratio de Entregas UltraRápidas (&lt;30 min): <b>".$t['eficiencia']."%</b></li>";
        }
    }
    $retorno .= "</ul></div>";

    // Consulta 3: Reporte de Reservaciones por Período
    $sql_reservaciones = "SELECT DATE(H.inicio) as fecha, COUNT(R.id) as total_reservas, SUM(R.cantidad) as total_personas "
                       . "FROM reservaciones R JOIN horarios H ON R.id = H.reservacion_id "
                       . "WHERE H.inicio >= '$fecha_inicio'::timestamp AND H.inicio <= '$fecha_fin'::timestamp "
                       . "GROUP BY DATE(H.inicio) ORDER BY fecha DESC";
    $res_reservaciones = procesar_query($sql_reservaciones, $conn);
    $retorno .= "<div class='SECCION'><h3>Reporte de Reservaciones por Período</h3><ul>";
    if ($res_reservaciones->cantidad == 0) {
        $retorno .= "<li>No hay reservaciones en este período.</li>";
    } else {
        $total_res = 0;
        $total_pax = 0;
        foreach ($res_reservaciones->datos as $r) {
            $total_res += $r['total_reservas'];
            $total_pax += $r['total_personas'];
            $retorno .= "<li class='PLATO'><span class='NOMBRE'>".$r['fecha']."</span> — "
                      . "<b>".$r['total_reservas']."</b> reserva(s) | "
                      . "<b>".$r['total_personas']."</b> persona(s)</li>";
        }
        $retorno .= "<li style='margin-top:6px;font-weight:bold;'>TOTAL: $total_res reservaciones | $total_pax personas</li>";
    }
    $retorno .= "</ul></div>";

    // Consulta 4: Platos más solicitados en el período
    $sql_populares = "SELECT PLA.nombre, TIP.nombre as tipo, COUNT(ORD.id) as veces, SUM(ORD.cantidad) as unidades "
                   . "FROM ordenes ORD "
                   . "JOIN platos PLA ON ORD.plato_id = PLA.id "
                   . "JOIN tipos TIP ON PLA.tipo_id = TIP.id "
                   . "JOIN pedidos PED ON ORD.pedido_id = PED.id "
                   . "WHERE PED.solicitado >= '$fecha_inicio'::timestamp AND PED.solicitado <= '$fecha_fin'::timestamp "
                   . "GROUP BY PLA.nombre, TIP.nombre ORDER BY unidades DESC LIMIT 10";
    $res_populares = procesar_query($sql_populares, $conn);
    $retorno .= "<div class='SECCION'><h3>Top 10 Platos Más Solicitados</h3><ul>";
    if ($res_populares->cantidad == 0) {
        $retorno .= "<li>No hay pedidos registrados en este período.</li>";
    } else {
        $rank = 1;
        foreach ($res_populares->datos as $p) {
            $retorno .= "<li class='PLATO'><span class='NOMBRE'>#$rank — ".$p['nombre']." <small>(".$p['tipo'].")</small></span> — "
                      . "<b>".$p['unidades']."</b> unidades pedidas en <b>".$p['veces']."</b> orden(es)</li>";
            $rank++;
        }
    }
    $retorno .= "</ul></div>";
    
    return $retorno;
}
//------------------------------------------------------------
/*------------------------------------------------------------------*/
/**
 * @brief CRUD de empleados: registra usuarios con roles (maitre, mesero, cocinero, etc.).
 * @param resource $conn Conexión activa a PostgreSQL.
 * @return string HTML con el formulario y la lista de empleados.
 */
function fn_gestionar_empleados($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal()."<br /><h2>Gestión de Empleados</h2>";
    $accion  = isset($_REQUEST['accion']) ? fn_sanitizar($_REQUEST['accion']) : '';

    if ($accion !== '') {
        $token_in = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
        fn_validar_token($token_in);

        if ($accion == 'crear') {
            $nombre  = fn_sanitizar($_REQUEST['nombre']);
            $clave   = hash('sha256', $_REQUEST['clave']);
            $rol_id  = fn_sanitizar($_REQUEST['rol_id']);
            // Insertar usuario
            $sql_u = "INSERT INTO usuarios (nombre, clave, fecha_clave) VALUES ('$nombre', decode('$clave','hex'), NOW()) RETURNING id";
            $res_u = procesar_query($sql_u, $conn);
            if ($res_u->cantidad > 0) {
                $uid = $res_u->datos[0]['id'];
                pg_query($conn, "INSERT INTO actuaciones (usuario_id, rol_id) VALUES ($uid, $rol_id)");
                $retorno .= "<div style='color:green'>Empleado '$nombre' registrado exitosamente.</div>";
            }
        } elseif ($accion == 'eliminar') {
            $uid = fn_sanitizar($_REQUEST['uid']);
            pg_query($conn, "DELETE FROM actuaciones WHERE usuario_id = $uid");
            pg_query($conn, "DELETE FROM usuarios WHERE id = $uid");
            $retorno .= "<div style='color:orange'>Empleado eliminado.</div>";
        }
    }

    // Formulario de creación
    $res_roles = procesar_query("SELECT id, nombre FROM roles ORDER BY nombre", $conn);
    $opt_roles = "";
    foreach ($res_roles->datos as $r) {
        $opt_roles .= "<option value='".$r['id']."'>".ucfirst($r['nombre'])."</option>";
    }
    $func_crear = "navegarA('gestionar_empleados', {accion:'crear', nombre: document.getElementById('emp_nombre').value, clave: document.getElementById('emp_clave').value, rol_id: document.getElementById('emp_rol').value})";
    $retorno .= "<div class='SECCION'><h3>Registrar Nuevo Empleado</h3>"
              . "Nombre: <input type='text' id='emp_nombre' placeholder='Nombre completo'> "
              . "Clave: <input type='password' id='emp_clave' placeholder='Contraseña'> "
              . "Rol: <select id='emp_rol'>$opt_roles</select> "
              . "<button onClick=\"$func_crear\">Registrar</button></div>";

    // Lista de empleados con su(s) rol(es)
    $sql_lista = "SELECT U.id, U.nombre, STRING_AGG(R.nombre, ', ') as roles, U.fecha_clave "
               . "FROM usuarios U "
               . "LEFT JOIN actuaciones A ON A.usuario_id = U.id "
               . "LEFT JOIN roles R ON R.id = A.rol_id "
               . "GROUP BY U.id, U.nombre, U.fecha_clave ORDER BY U.nombre";
    $lista = procesar_query($sql_lista, $conn);

    $retorno .= "<h3>Empleados Registrados</h3><ul>";
    foreach ($lista->datos as $emp) {
        $script_del = "navegarA('gestionar_empleados', {accion:'eliminar', uid:".$emp['id']."})";
        $roles_txt  = $emp['roles'] ?: '<em>Sin rol asignado</em>';
        $retorno .= "<li class='PLATO'>"
                  . "<span class='NOMBRE'>".$emp['nombre']."</span> "
                  . "— <span style='color:#6c757d'>".htmlspecialchars($roles_txt)."</span> "
                  . " | Clave actualizada: ".substr($emp['fecha_clave'], 0, 10)
                  . " <button onClick=\"$script_del\" style='background:#dc3545;'>Eliminar</button>"
                  . "</li>";
    }
    $retorno .= "</ul>";

    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Historial de reservaciones y pedidos de un cliente.
 * @param resource $conn Conexión activa a PostgreSQL.
 * @return string HTML con el historial.
 */
function fn_historial_cliente($conn)
/*--------------------------------------------------------------------*/
{
    $retorno   = fn_boton_menu_principal()."<br /><h2>Historial de Clientes</h2>";
    $cliente_id = isset($_REQUEST['cliente_id']) ? fn_sanitizar($_REQUEST['cliente_id']) : '';

    // Selector de cliente
    $res_cli = procesar_query("SELECT id, nombre FROM usuarios ORDER BY nombre", $conn);
    $opt_cli = "<option value=''>-- Selecciona un cliente --</option>";
    foreach ($res_cli->datos as $c) {
        $sel = ($cliente_id == $c['id']) ? 'selected' : '';
        $opt_cli .= "<option value='".$c['id']."' $sel>".$c['nombre']."</option>";
    }
    $func_buscar = "navegarA('historial_cliente', {cliente_id: document.getElementById('sel_cli_hist').value})";
    $retorno .= "<div class='SECCION'>"
              . "Cliente: <select id='sel_cli_hist'>$opt_cli</select> "
              . "<button onClick=\"$func_buscar\">Ver Historial</button></div>";

    if ($cliente_id == '') return $retorno;

    // Nombre del cliente
    $res_nom = procesar_query("SELECT nombre FROM usuarios WHERE id = $cliente_id", $conn);
    $nom_cli = ($res_nom->cantidad > 0) ? $res_nom->datos[0]['nombre'] : "Cliente #$cliente_id";
    $retorno .= "<h3>Historial de: $nom_cli</h3>";

    // Historial de reservaciones
    $sql_res = "SELECT H.inicio, M.id as mesa, R.cantidad, R.estado "
             . "FROM reservaciones R "
             . "JOIN horarios H ON R.id = H.reservacion_id "
             . "JOIN mesas M ON H.mesa_id = M.id "
             . "WHERE R.cliente_id = $cliente_id ORDER BY H.inicio DESC";
    $res_hist = procesar_query($sql_res, $conn);
    $retorno .= "<div class='SECCION'><h4>Reservaciones (".$res_hist->cantidad.")</h4><ul>";
    if ($res_hist->cantidad == 0) {
        $retorno .= "<li>Sin reservaciones registradas.</li>";
    } else {
        foreach ($res_hist->datos as $r) {
            $estado_txt = ($r['estado'] == 0) ? 'Vigente' : 'Cancelada';
            $retorno .= "<li class='PLATO'>"
                      . "<span class='NOMBRE'>".$r['inicio']."</span> "
                      . "— Mesa ".$r['mesa']." | ".$r['cantidad']." personas | <b>$estado_txt</b>"
                      . "</li>";
        }
    }
    $retorno .= "</ul></div>";

    // Historial de pedidos
    $sql_ped = "SELECT PED.solicitado, COUNT(ORD.id) as items, SUM(ORD.cantidad) as unidades "
             . "FROM pedidos PED "
             . "JOIN ordenes ORD ON ORD.pedido_id = PED.id "
             . "WHERE PED.cliente_id = $cliente_id "
             . "GROUP BY PED.id, PED.solicitado ORDER BY PED.solicitado DESC";
    $res_ped = procesar_query($sql_ped, $conn);
    $retorno .= "<div class='SECCION'><h4>Pedidos (".$res_ped->cantidad.")</h4><ul>";
    if ($res_ped->cantidad == 0) {
        $retorno .= "<li>Sin pedidos registrados.</li>";
    } else {
        foreach ($res_ped->datos as $p) {
            $retorno .= "<li class='PLATO'>"
                      . "<span class='NOMBRE'>".$p['solicitado']."</span> "
                      . "— ".$p['items']." plato(s) diferentes | ".$p['unidades']." unidades totales"
                      . "</li>";
        }
    }
    $retorno .= "</ul></div>";

    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Muestra el formulario de inicio de sesión.
 */
function fn_login_form()
/*--------------------------------------------------------------------*/
{
    return "<div class='SECCION' style='max-width:400px; margin:50px auto;'>"
         . "<h2>Iniciar Sesión</h2>"
         . "Usuario: <input type='text' id='log_usuario' style='width:100%'><br><br>"
         . "Clave: <input type='password' id='log_clave' style='width:100%'><br><br>"
         . "<button onClick=\"navegarA('login', {usuario: document.getElementById('log_usuario').value, clave: document.getElementById('log_clave').value})\">Entrar</button>"
         . "</div>";
}

/*------------------------------------------------------------------*/
/**
 * @brief Procesa el inicio de sesión.
 */
function fn_login($conn)
/*--------------------------------------------------------------------*/
{
    $usuario = trim(pg_escape_string($conn, $_REQUEST['usuario']));
    $clave_raw = trim($_REQUEST['clave']);
    $clave   = hash('sha256', $clave_raw);

    // Buscamos ignorando mayúsculas/minúsculas para mejor UX
    $sql = "SELECT id, nombre, encode(clave, 'hex') as clave_hex FROM usuarios WHERE LOWER(nombre) = LOWER('$usuario')";
    $res = procesar_query($sql, $conn);

    if ($res->cantidad > 0) {
        // depuracion temporal
        $hash_bd = $res->datos[0]['clave_hex'];
        if ($hash_bd === $clave) {
            $_SESSION['usuario_id'] = $res->datos[0]['id'];
            $_SESSION['nombre'] = $res->datos[0]['nombre'];
            
            // Obtener el primer rol asociado
            $sql_rol = "SELECT R.nombre FROM actuaciones A JOIN roles R ON A.rol_id = R.id WHERE A.usuario_id = " . $_SESSION['usuario_id'] . " LIMIT 1";
            $res_rol = procesar_query($sql_rol, $conn);
            $_SESSION['rol'] = ($res_rol->cantidad > 0) ? $res_rol->datos[0]['nombre'] : 'ninguno';

            // En vez de script (que innerHTML no ejecuta), devolvemos el menú de una vez
            return fn_menu_opciones($conn);
        } else {
            // error_log("Login fallido para $usuario. BD: $hash_bd vs Input: $clave");
        }
    }

    return "<div style='color:red; font-weight:bold;'>Error: Usuario o clave incorrectos. Intenta de nuevo.</div>" . fn_login_form();
}

?>
