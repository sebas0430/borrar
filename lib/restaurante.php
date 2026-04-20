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
--GA--    $scr_menu = "navegarA(\'\');";
--GA--    $retorno = "<button onClick=\"$scr_menu\">Menu del programa</button>";
--GA--    return $retorno;
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
--GA--    $scr_menu = "navegarA(\'desplegar_menu\');";
--GA--    $scr_pedido = "navegarA(\'realizar_pedidos\');";
--GA--    $scr_mesas = "navegarA(\'gestionar_mesas\');";
--GA--    $retorno = "<h1>Opciones del programa</h1>"
--GA--             . "<div class=\'MENU_OPCIONES\'>"
--GA--             . "<button onClick=\"$scr_menu\">Desplegar Menu</button>"
--GA--             . "<button onClick=\"$scr_pedido\">Realizar Pedido</button>"
--GA--             . "<button onClick=\"$scr_mesas\">Gestión de Mesas</button>"
--GA--             . "<button onClick=\"navegarA(\'gestionar_menu\')\">Gestión de Menú</button>"
--GA--             . "<button onClick=\"navegarA(\'gestionar_reservaciones\')\">Manejo de Reservaciones (Maitre)</button>"
--GA--             . "<button style=\'background:#dc3545;\' onClick=\"navegarA(\'pantallas_operacion\', {rol: \'cocina\'})\">Monitor de Cocina</button>"
--GA--             . "<button style=\'background:#007bff;\' onClick=\"navegarA(\'pantallas_operacion\', {rol: \'mesero\'})\">Avisos de Entrega</button>"
--GA--             . "<button style=\'background:#6f42c1; color:white;\' onClick=\"navegarA(\'reportes_estadisticas\')\">Reportes Administrativos</button>"
--GA--             . "</div>"
--GA--             ;
--GA--    return $retorno;
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

--GA--/*------------------------------------------------------------------*/
--GA--/**
--GA-- * @brief Gestiona el flujo de pedidos, listando mesas o procesando la comanda.
--GA-- * @param resource $conn Recurso de la conexión a la base de datos.
--GA-- * @return string HTML interactivo con las mesas u opciones del Point of Sale.
--GA-- * @pre Debe venir el token si hay modificación.
--GA-- * @post Interactúa con pedidos, creando uno si es primera vez para la mesa, e inserta órdenes.
--GA-- */
--GA--function fn_realizar_pedidos($conn)
--GA--/*--------------------------------------------------------------------*/
--GA--{
--GA--    $retorno = fn_boton_menu_principal()."<br /><h2>Panel de Meseros (POS)</h2>";
--GA--
--GA--    if (isset($_REQUEST['mesa_id']))
--GA--        $accion = "pedido_mesa";
--GA--    else
--GA--        $accion = "listar_mesas";
--GA--
--GA--    switch ($accion) {
--GA--    case 'listar_mesas':
--GA--        $sentencia = "
--GA--            SELECT MES.id, MES.sillas, HOR.inicio, USR.nombre as nombre_usr
--GA--            FROM mesas AS MES
--GA--            LEFT JOIN horarios      AS HOR ON MES.id = HOR.mesa_id
--GA--            LEFT JOIN reservaciones AS RES ON RES.id = HOR.reservacion_id
--GA--            LEFT JOIN usuarios      AS USR ON USR.id = RES.cliente_id
--GA--            WHERE HOR.inicio::date = CURRENT_DATE
--GA--            ORDER BY MES.id
--GA--            ";
--GA--        $resultado = procesar_query($sentencia, $conn);
--GA-- 
--GA--        $botones_mesas = "";
--GA--        foreach ($resultado->datos as $mesa) {
--GA--            if ($mesa['nombre_usr']) {
--GA--                $script = "navegarA(\'realizar_pedidos\', {mesa_id: ".$mesa['id']."});";
--GA--                $botones_mesas.= "<button style=\'background:#28a745; color:white; margin:5px;\' onClick=\"$script\">Mesa ".$mesa['id']." - ".$mesa['nombre_usr']."</button>";
--GA--            } else {
--GA--                $botones_mesas.= "<button disabled style=\'margin:5px;\'>Mesa ".$mesa['id']." (Sin reserva hoy)</button>";
--GA--            }
--GA--        }
--GA--        $retorno .= "<div class=\'MENU_OPCIONES\'>".$botones_mesas."</div>";
--GA--        break;
--GA--        
--GA--    case 'pedido_mesa':
--GA--        $mesa_id = fn_sanitizar($_REQUEST['mesa_id']);
--GA--        
--GA--        $sql_res = "SELECT RES.cliente_id, USR.nombre FROM horarios HOR "
--GA--                 . "JOIN reservaciones RES ON HOR.reservacion_id = RES.id "
--GA--                 . "JOIN usuarios USR ON RES.cliente_id = USR.id "
--GA--                 . "WHERE HOR.mesa_id = $mesa_id AND HOR.inicio::date = CURRENT_DATE LIMIT 1";
--GA--        $res = procesar_query($sql_res, $conn);
--GA--        if ($res->cantidad == 0) return $retorno . "<p>Sin clientes asigandos en esta mesa.</p>";
--GA--        
--GA--        $cliente_id = intval($res->datos[0]['cliente_id']);
--GA--        $nom_cli = $res->datos[0]['nombre'];
--GA--        $mesero_rol = procesar_query("SELECT usuario_id FROM actuaciones JOIN roles ON roles.id = actuaciones.rol_id WHERE roles.nombre = \'mesero\' LIMIT 1", $conn);
--GA--        $mesero_id_defecto = ($mesero_rol->cantidad > 0) ? intval($mesero_rol->datos[0]['usuario_id']) : 1; 
--GA--        
--GA--        $sql_pedido = "SELECT id FROM pedidos WHERE cliente_id = $cliente_id AND solicitado::date = CURRENT_DATE";
--GA--        $res_ped = procesar_query($sql_pedido, $conn);
--GA--        if ($res_ped->cantidad > 0) {
--GA--            $pedido_id = intval($res_ped->datos[0]['id']);
--GA--        } else {
--GA--            $sql_ins_ped = "INSERT INTO pedidos (cliente_id, mesero_id, solicitado) VALUES ($cliente_id, $mesero_id_defecto, NOW()) RETURNING id";
--GA--            $res_ins = procesar_query($sql_ins_ped, $conn);
--GA--            $pedido_id = intval($res_ins->datos[0]['id']);
--GA--        }
--GA--        
--GA--        $sub_accion = isset($_REQUEST['sub_accion']) ? fn_sanitizar($_REQUEST['sub_accion']) : '';
--GA--        if ($sub_accion == 'agregar_plato') {
--GA--            fn_validar_token($_REQUEST['token']);
--GA--            $plato_id = fn_sanitizar($_REQUEST['plato_id']);
--GA--            $sql_ord = "INSERT INTO ordenes (plato_id, pedido_id, estado, cantidad) VALUES ($plato_id, $pedido_id, 0, 1)";
--GA--            pg_query($conn, $sql_ord);
--GA--        }
--GA--        
--GA--        $retorno .= "<h3>Mesa $mesa_id | Cliente: $nom_cli</h3>";
--GA--        
--GA--        $platos = procesar_query("SELECT id, nombre, precio FROM platos", $conn);
--GA--        $retorno .= "<div class=\'SECCION\'><h4>Añadir a la cuenta:</h4>";
--GA--        foreach($platos->datos as $p) {
--GA--            $scr = "navegarA(\'realizar_pedidos\', {mesa_id:$mesa_id, sub_accion:\'agregar_plato\', plato_id:".$p['id']."})";
--GA--            $retorno .= "<button onClick=\"$scr\"> + ".$p['nombre']." ($".$p['precio'].")</button> ";
--GA--        }
--GA--        $retorno .= "</div>";
--GA--        
--GA--        $comanda = procesar_query("SELECT PLA.nombre, ORD.cantidad, ORD.estado FROM ordenes ORD JOIN platos PLA ON ORD.plato_id = PLA.id WHERE ORD.pedido_id = $pedido_id", $conn);
--GA--        $retorno .= "<div class=\'SECCION\'><h4>Comanda Actual de la Mesa:</h4><ul>";
--GA--        if ($comanda->cantidad == 0) $retorno .= "<li>Sin pedidos aún.</li>";
--GA--        foreach($comanda->datos as $c) {
--GA--            $estado = ($c['estado'] == 0) ? \'En Cocina (0)\' : (($c['estado'] == 1) ? \'Listo (1)\' : \'Entregado (2)\');
--GA--            $retorno .= "<li class=\'PLATO\'>".$c['cantidad']."x <span class=\'NOMBRE\'>".$c['nombre']."</span> - $estado</li>";
--GA--        }
--GA--        $retorno .= "</ul></div>";
--GA--        break;
--GA--    }
--GA--    return $retorno;
--GA--}
--GA--/*------------------------------------------------------------------*/
--GA--/**
--GA-- * @brief Panel administrativo para gestionar mesas y aforo total (CRUD).
--GA-- * @param resource $conn Recurso de la conexión activa a Postgres.
--GA-- * @return string Fragmento HTML con la información y formularios.
--GA-- * @pre Debe estar autenticado y se valida el token en las modificaciones (POST).
--GA-- * @post Añade, elimina o modifica registros en tabla "mesas".
--GA-- */
--GA--function fn_gestionar_mesas($conn)
--GA--/*--------------------------------------------------------------------*/
--GA--{
--GA--    $retorno = fn_boton_menu_principal()."<br /><h2>Gestión de Mesas</h2>";
--GA--    $accion = isset($_REQUEST['accion']) ? fn_sanitizar($_REQUEST['accion']) : '';
--GA--    
--GA--    if ($accion !== '') {
--GA--        $token_in = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
--GA--        fn_validar_token($token_in);
--GA--        
--GA--        if ($accion == 'crear') {
--GA--            $sillas = fn_sanitizar($_REQUEST['sillas']);
--GA--            $sql = "INSERT INTO mesas (sillas) VALUES ($sillas)";
--GA--            pg_query($conn, $sql);
--GA--        } elseif ($accion == 'eliminar') {
--GA--            $id = fn_sanitizar($_REQUEST['id']);
--GA--            $sql = "DELETE FROM mesas WHERE id = $id";
--GA--            pg_query($conn, $sql);
--GA--        } elseif ($accion == 'actualizar') {
--GA--            $id = fn_sanitizar($_REQUEST['id']);
--GA--            $sillas = fn_sanitizar($_REQUEST['sillas']);
--GA--            $sql = "UPDATE mesas SET sillas = $sillas WHERE id = $id";
--GA--            pg_query($conn, $sql);
--GA--        }
--GA--    }
--GA--    
--GA--    $func_crear = "navegarA(\'gestionar_mesas\', {accion: \'crear\', sillas: document.getElementById(\'num_sillas\').value})";
--GA--    $retorno .= "<div class=\'SECCION\'><label>Añadir Mesa - Sillas: <input type=\'number\' id=\'num_sillas\' min=\'1\'></label>";
--GA--    $retorno .= "<button onClick=\"$func_crear\">Crear</button></div>";
--GA--    
--GA--    $sentencia = "SELECT id, sillas FROM mesas ORDER BY id";
--GA--    $resultado = procesar_query($sentencia, $conn);
--GA--    
--GA--    $total_sillas = 0;
--GA--    $retorno .= "<ul>";
--GA--    foreach ($resultado->datos as $mesa) {
--GA--        $total_sillas += $mesa['sillas'];
--GA--        $script_del = "navegarA(\'gestionar_mesas\', {accion: \'eliminar\', id: ".$mesa['id']."})";
--GA--        $script_upd = "navegarA(\'gestionar_mesas\', {accion: \'actualizar\', id: ".$mesa['id'].", sillas: document.getElementById(\'sillas_".$mesa['id']."\').value})";
--GA--        
--GA--        $retorno .= "<li class=\'PLATO\'>";
--GA--        $retorno .= "<span class=\'NOMBRE\'>Mesa #".$mesa['id']."</span>";
--GA--        $retorno .= "<span><input type=\'number\' id=\'sillas_".$mesa['id']."\' value=\'".$mesa['sillas']."\' style=\'width:50px;\'> sillas </span>";
--GA--        $retorno .= "<button onClick=\"$script_upd\">Actualizar</button>";
--GA--        $retorno .= "<button onClick=\"$script_del\">Eliminar</button>";
--GA--        $retorno .= "</li>";
--GA--    }
--GA--    $retorno .= "</ul>";
--GA--    $retorno .= "<div class=\'SECCION\'><h3>Cupo Total Actual: $total_sillas personas</h3></div>";
--GA--    
--GA--    return $retorno;
--GA--}
--GA--
--GA--/*------------------------------------------------------------------*/
--GA--/**
--GA-- * @brief Panel administrativo para gestionar la carta del restaurante (CRUD de menú).
--GA-- * @param resource $conn Recurso de la conexión activa a Postgres.
--GA-- * @return string Fragmento HTML con la información y formularios.
--GA-- * @pre Validación del token de seguridad en operaciones POST.
--GA-- * @post Añade, elimina o modifica registros en tabla "platos".
--GA-- */
--GA--function fn_gestionar_menu($conn)
--GA--/*--------------------------------------------------------------------*/
--GA--{
--GA--    $retorno = fn_boton_menu_principal()."<br /><h2>Gestión del Menú</h2>";
--GA--    $accion = isset($_REQUEST['accion']) ? fn_sanitizar($_REQUEST['accion']) : '';
--GA--    
--GA--    if ($accion !== '') {
--GA--        $token_in = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
--GA--        fn_validar_token($token_in);
--GA--        
--GA--        if ($accion == 'crear') {
--GA--            $nombre = fn_sanitizar($_REQUEST['nombre']);
--GA--            $descripcion = fn_sanitizar($_REQUEST['descripcion']);
--GA--            $precio = fn_sanitizar($_REQUEST['precio']);
--GA--            $tipo_id = fn_sanitizar($_REQUEST['tipo_id']);
--GA--            $sql = "INSERT INTO platos (nombre, descripcion, precio, tipo_id) VALUES ('$nombre', '$descripcion', $precio, $tipo_id)";
--GA--            pg_query($conn, $sql);
--GA--        } elseif ($accion == 'eliminar') {
--GA--            $id = fn_sanitizar($_REQUEST['id']);
--GA--            $sql = "DELETE FROM platos WHERE id = $id";
--GA--            pg_query($conn, $sql);
--GA--        }
--GA--    }
--GA--    
--GA--    // Formularios
--GA--    $sentencia_tipos = "SELECT id, nombre FROM tipos ORDER BY nombre";
--GA--    $resultado_tipos = procesar_query($sentencia_tipos, $conn);
--GA--    $opciones_tipos = "";
--GA--    foreach($resultado_tipos->datos as $tipo) {
--GA--        $opciones_tipos .= "<option value=\'".$tipo['id']."\'>".$tipo['nombre']."</option>";
--GA--    }
--GA--    
--GA--    $func_crear = "navegarA(\'gestionar_menu\', {accion: \'crear\', nombre: document.getElementById(\'nuevo_nombre\').value, descripcion: document.getElementById(\'nueva_desc\').value, precio: document.getElementById(\'nuevo_precio\').value, tipo_id: document.getElementById(\'nuevo_tipo\').value})";
--GA--    $retorno .= "<div class=\'SECCION\'><h3>Crear Nuevo Plato</h3>"
--GA--              . "<input type=\'text\' id=\'nuevo_nombre\' placeholder=\'Nombre del plato\'> "
--GA--              . "<input type=\'text\' id=\'nueva_desc\' placeholder=\'Descripción breve\'> "
--GA--              . "$<input type=\'number\' id=\'nuevo_precio\' placeholder=\'Precio (ej: 15.50)\' step=\'0.01\'> "
--GA--              . "<select id=\'nuevo_tipo\'>$opciones_tipos</select> "
--GA--              . "<button onClick=\"$func_crear\">Agregar a Carta</button></div>";
--GA--    
--GA--    $sentencia = "SELECT platos.id, platos.nombre, platos.precio, platos.descripcion, tipos.nombre as tipo "
--GA--               . "FROM platos LEFT JOIN tipos ON platos.tipo_id = tipos.id ORDER BY tipos.nombre, platos.nombre";
--GA--    $resultado = procesar_query($sentencia, $conn);
--GA--    
--GA--    $retorno .= "<ul>";
--GA--    foreach ($resultado->datos as $plato) {
--GA--        $script_del = "navegarA(\'gestionar_menu\', {accion: \'eliminar\', id: ".$plato['id']."})";
--GA--        
--GA--        $retorno .= "<li class=\'PLATO\'>";
--GA--        $retorno .= "<span class=\'NOMBRE\'> ".$plato['nombre']." (".$plato['tipo'].")</span> ";
--GA--        $retorno .= "<span>$".$plato['precio']."</span> - <span class=\'DESCRIPCION\'>".$plato['descripcion']."</span> ";
--GA--        $retorno .= "<button onClick=\"$script_del\">Quitar de menú</button>";
--GA--        $retorno .= "</li>";
--GA--    }
--GA--    $retorno .= "</ul>";
--GA--    
--GA--    return $retorno;
--GA--}
--GA--
--GA--/*------------------------------------------------------------------*/
--GA--/**
--GA-- * @brief Interfaz para que el Maitre asigne sillas y organice la agenda sin sobre-cupo.
--GA-- * @param resource $conn Recurso de la conexión activa a Postgres.
--GA-- * @return string Fragmento HTML con las validaciones de reserva.
--GA-- * @pre Validación del token. Las mesas y usuarios (clientes) deben existir.
--GA-- * @post Genera un registro en reservaciones y su asociado en horarios validando el lapso.
--GA-- */
--GA--function fn_gestionar_reservaciones($conn)
--GA--/*--------------------------------------------------------------------*/
--GA--{
--GA--    $retorno = fn_boton_menu_principal()."<br /><h2>Gestión de Reservaciones (Maitre)</h2>";
--GA--    $accion = isset($_REQUEST['accion']) ? fn_sanitizar($_REQUEST['accion']) : '';
--GA--
--GA--    if ($accion !== '') {
--GA--        $token_in = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
--GA--        fn_validar_token($token_in);
--GA--        
--GA--        if ($accion == 'crear') {
--GA--            $cliente_id = fn_sanitizar($_REQUEST['cliente_id']);
--GA--            $mesa_id = fn_sanitizar($_REQUEST['mesa_id']);
--GA--            $inicio = fn_sanitizar($_REQUEST['inicio']); // Formato: YYYY-MM-DD HH:MM
--GA--            $cantidad = fn_sanitizar($_REQUEST['cantidad']);
--GA--            
--GA--            // REGLA: No cruzar reservaciones en la misma mesa (Margen de 2 horas)
--GA--            $sql_cruce = "SELECT count(*) AS cruce FROM horarios WHERE mesa_id = $mesa_id AND "
--GA--                       . "((inicio <= \'$inicio\' AND (inicio + interval \'1 hours 59 minutes\') >= \'$inicio\') OR "
--GA--                       . "(inicio >= \'$inicio\' AND inicio <= (\'$inicio\'::timestamp + interval \'1 hours 59 minutes\')))";
--GA--            $res_cruce = procesar_query($sql_cruce, $conn);
--GA--            
--GA--            if ($res_cruce->datos[0]['cruce'] > 0) {
--GA--                $retorno .= "<div style=\'color:red\'><strong>Error:</strong> La mesa $mesa_id ya se encuentra ocupada o reservada en el bloque horario seleccionado (2 horas de tolerancia).</div>";
--GA--            } else {
--GA--                // REGLA: No superar el cupo total del restaurante en ese horario general
--GA--                $sql_agregado = "SELECT sum(R.cantidad) AS ocupados FROM horarios H "
--GA--                              . "JOIN reservaciones R ON H.reservacion_id = R.id "
--GA--                              . "WHERE ((H.inicio <= \'$inicio\' AND (H.inicio + interval \'1 hours 59 minutes\') >= \'$inicio\') OR "
--GA--                              . "(H.inicio >= \'$inicio\' AND H.inicio <= (\'$inicio\'::timestamp + interval \'1 hours 59 minutes\')))";
--GA--                $res_agregado = procesar_query($sql_agregado, $conn);
--GA--                $uso_actual = intval($res_agregado->datos[0]['ocupados']);
--GA--                
--GA--                $sql_capacidad = "SELECT sum(sillas) as maximo FROM mesas";
--GA--                $res_capacidad = procesar_query($sql_capacidad, $conn);
--GA--                $limite = intval($res_capacidad->datos[0]['maximo']);
--GA--                
--GA--                if (($uso_actual + $cantidad) > $limite) {
--GA--                    $retorno .= "<div style=\'color:red\'><strong>Error Capacidad General:</strong> Restaurante lleno. La cantidad sobrepasa el cupo total disponible ($limite máximo, reservando $uso_actual).</div>";
--GA--                } else {
--GA--                    // Éxito: Crear en Reservaciones
--GA--                    $sql_res = "INSERT INTO reservaciones (cliente_id, cantidad, estado) VALUES ($cliente_id, $cantidad, 0) RETURNING id";
--GA--                    $id_insert = procesar_query($sql_res, $conn);
--GA--                    if ($id_insert->cantidad > 0) {
--GA--                        $reservacion_id = $id_insert->datos[0]['id'];
--GA--                        $sql_hor = "INSERT INTO horarios (mesa_id, reservacion_id, inicio, duracion) VALUES ($mesa_id, $reservacion_id, \'$inicio\', \'01:59:00\')";
--GA--                        pg_query($conn, $sql_hor);
--GA--                        $retorno .= "<div style=\'color:green\'>Reserva confirmada exitosamente.</div>";
--GA--                    }
--GA--                }
--GA--            }
--GA--        } elseif ($accion == 'eliminar') {
--GA--            $res_id = fn_sanitizar($_REQUEST['res_id']);
--GA--            pg_query($conn, "DELETE FROM horarios WHERE reservacion_id = $res_id");
--GA--            pg_query($conn, "DELETE FROM reservaciones WHERE id = $res_id");
--GA--            $retorno .= "<div style=\'color:orange\'>Reserva anulada.</div>";
--GA--        }
--GA--    }
--GA--
--GA--    // Constructores de Formulario
--GA--    $res_mesas = procesar_query("SELECT id, sillas FROM mesas ORDER BY id", $conn);
--GA--    $opt_mesas = ""; foreach($res_mesas->datos as $m) $opt_mesas .= "<option value=\'".$m['id']."\'>Mesa ".$m['id']." (".$m['sillas']." Pax)</option>";
--GA--    
--GA--    // Usuarios que tengan rol de cliente o directamente listarlos. Asumimos todos para el demo
--GA--    $res_cli = procesar_query("SELECT id, nombre FROM usuarios ORDER BY nombre", $conn);
--GA--    $opt_cli = ""; foreach($res_cli->datos as $c) $opt_cli .= "<option value=\'".$c['id']."\'>".$c['nombre']."</option>";
--GA--    
--GA--    $func_crear = "navegarA(\'gestionar_reservaciones\', {accion:\'crear\', cliente_id:document.getElementById(\'sel_cli\').value, mesa_id:document.getElementById(\'sel_mesa\').value, inicio:document.getElementById(\'in_fecha\').value, cantidad:document.getElementById(\'in_cant\').value})";
--GA--    $retorno .= "<h3>Nueva Reservación:</h3><div class=\'SECCION\'>"
--GA--              . "Cliente: <select id=\'sel_cli\'>$opt_cli</select> "
--GA--              . "Mesa: <select id=\'sel_mesa\'>$opt_mesas</select> "
--GA--              . "Fecha y Hora: <input type=\'datetime-local\' id=\'in_fecha\'> "
--GA--              . "Personas: <input type=\'number\' id=\'in_cant\' min=\'1\' value=\'2\'> "
--GA--              . "<button onClick=\"$func_crear\">Agendar</button></div>";
--GA--    
--GA--    // Listado de Reservas Existentes de las próximas horas/días
--GA--    $sql_horarios = "SELECT R.id as res_id, H.inicio, M.id as mesa, U.nombre as cliente, R.cantidad "
--GA--                  . "FROM reservaciones R JOIN horarios H ON R.id = H.reservacion_id "
--GA--                  . "JOIN mesas M ON H.mesa_id = M.id JOIN usuarios U ON R.cliente_id = U.id "
--GA--                  . "ORDER BY H.inicio DESC";
--GA--    $listado = procesar_query($sql_horarios, $conn);
--GA--    
--GA--    $retorno .= "<ul>";
--GA--    foreach ($listado->datos as $res) {
--GA--        $script_del = "navegarA(\'gestionar_reservaciones\', {accion: \'eliminar\', res_id: ".$res['res_id']."})";
--GA--        $retorno .= "<li class=\'PLATO\'>"
--GA--                  . "<span class=\'NOMBRE\'>El ".$res['inicio']."</span> - "
--GA--                  . "<span> Mesa ".$res['mesa']." | Cliente: ".$res['cliente']." (".$res['cantidad']." Personas)</span> "
--GA--                  . "<button onClick=\"$script_del\">Cancelar Reserva</button></li>";
--GA--    }
--GA--    $retorno .= "</ul>";
--GA--
--GA--    return $retorno;
--GA--}
--GA--
--GA--/*------------------------------------------------------------------*/
--GA--/**
--GA-- * @brief Centraliza los escritorios dinámicos de Cocina (preparar) y Mesero (entregar).
--GA-- * @param resource $conn Recurso a la BDD.
--GA-- * @return string Interfaz auto-refrescantable (polling).
--GA-- * @pre Implementación del Principio DRY en un solo controlador de estados.
--GA-- * @post Ejecuta sentencias UPDATE con Token CSRF.
--GA-- */
--GA--function fn_pantallas_operacion($conn)
--GA--/*--------------------------------------------------------------------*/
--GA--{
--GA--    $rol = isset($_REQUEST['rol']) ? fn_sanitizar($_REQUEST['rol']) : \'cocina\';
--GA--    $titulo = ($rol == \'cocina\') ? \'Monitor de Despachos en Cocina\' : \'Centro de Entregas (Mesero)\';
--GA--    $retorno = fn_boton_menu_principal()."<br /><h2>$titulo</h2>";
--GA--    
--GA--    // Script temporal para auto-refresco embebido (Llama a programa.js)
--GA--    $retorno .= "<script>setTimeout(() => { if(typeof fn_refrescar_automatico === \'function\'){ fn_refrescar_automatico(\'pantallas_operacion&rol=$rol\', 4000); } }, 500);</script>";
--GA--    
--GA--    $accion = isset($_REQUEST['accion']) ? fn_sanitizar($_REQUEST['accion']) : \'\';
--GA--    if ($accion == \'marcar_estado\') {
--GA--        fn_validar_token($_REQUEST['token']);
--GA--        $orden_id = intval(fn_sanitizar($_REQUEST['orden_id']));
--GA--        $nuevo_estado = intval(fn_sanitizar($_REQUEST['nuevo_estado']));
--GA--        
--GA--        if ($nuevo_estado == 2) {
--GA--            // Si es mesero quien lo completó, marca Entregado (2) mas un Timestamp por las politicas BDD.
--GA--            pg_query($conn, "UPDATE ordenes SET estado = 2, entregado = CURRENT_TIMESTAMP WHERE id = $orden_id");
--GA--        } else {
--GA--            // Si es cocina, marca Listo (1)
--GA--            pg_query($conn, "UPDATE ordenes SET estado = $nuevo_estado WHERE id = $orden_id");
--GA--        }
--GA--    }
--GA--    
--GA--    // Condicional de Listado
--GA--    // Cocina quiere ver cosas en ESTADO 0 (En preparacion)
--GA--    // Mesero quiere ver cosas en ESTADO 1 (Listas para repartir a mesa)
--GA--    $filtro_estado = ($rol == \'cocina\') ? 0 : 1;
--GA--    $boton_txt = ($rol == \'cocina\') ? \'¡Plato Listo!\' : \'Entregar en Mesa\';
--GA--    $accion_estado = ($rol == \'cocina\') ? 1 : 2;
--GA--    
--GA--    $sql = "SELECT ORD.id, ORD.cantidad, PLA.nombre as plato, M.id as mesa, U.nombre as cliente, ORD.estado "
--GA--         . "FROM ordenes ORD "
--GA--         . "JOIN platos PLA ON ORD.plato_id = PLA.id "
--GA--         . "JOIN pedidos PED ON ORD.pedido_id = PED.id "
--GA--         . "JOIN reservaciones R ON PED.cliente_id = R.cliente_id "
--GA--         . "JOIN horarios H ON R.id = H.reservacion_id "
--GA--         . "JOIN mesas M ON H.mesa_id = M.id "
--GA--         . "JOIN usuarios U ON PED.cliente_id = U.id "
--GA--         . "WHERE ORD.estado = $filtro_estado AND H.inicio::date = CURRENT_DATE "
--GA--         . "ORDER BY PED.solicitado ASC LIMIT 30";
--GA--         
--GA--    $listado = procesar_query($sql, $conn);
--GA--    
--GA--    if ($listado->cantidad == 0) {
--GA--        $retorno .= "<div class=\'SECCION\'><center><h4><i>Esperando alertas en vivo...</i></h4></center></div>";
--GA--    } else {
--GA--        $retorno .= "<div class=\'SECCION_OPCIONES\'><ul>";
--GA--        foreach ($listado->datos as $row) {
--GA--            $params_js = "{rol:\'$rol\', accion:\'marcar_estado\', orden_id:".$row['id'].", nuevo_estado:$accion_estado}";
--GA--            $script = "navegarA(\'pantallas_operacion\', $params_js)";
--GA--            
--GA--            $retorno .= "<li class=\'PLATO\'>";
--GA--            $retorno .= "<h4>Mesa ".$row['mesa']." (".$row['cliente'].") | <span style=\'color:darkorange\'>".$row['cantidad']."x ".$row['plato']."</span></h4>";
--GA--            $retorno .= "<button style=\'padding:10px; font-weight:bold;\' onClick=\"$script\">✓ $boton_txt</button>";
--GA--            $retorno .= "</li>";
--GA--        }
--GA--        $retorno .= "</ul></div>";
--GA--    }
--GA--    
--GA--    return $retorno;
--GA--}
--GA--
--GA--/*------------------------------------------------------------------*/
--GA--/**
--GA-- * @brief Dashboard interactivo para consultar funciones PL/pgSQL anidadas en BDD.
--GA-- * @param resource $conn Recurso a la BDD.
--GA-- * @return string Interfaz con estadísticas operativas del restaurante.
--GA-- * @pre Debe existir conexión.
--GA-- * @post Devuelve código HTML plano sin recarga consumiendo procedimientos predefinidos.
--GA-- */
--GA--function fn_reportes_estadisticas($conn)
--GA--/*--------------------------------------------------------------------*/
--GA--{
--GA--    $retorno = fn_boton_menu_principal()."<br /><h2>Reportes y Estadísticas (Dashboard)</h2>";
--GA--    
--GA--    $fecha_inicio = isset($_REQUEST['fecha_inicio']) ? fn_sanitizar($_REQUEST['fecha_inicio']) : date('Y-m-d 00:00:00');
--GA--    $fecha_fin = isset($_REQUEST['fecha_fin']) ? fn_sanitizar($_REQUEST['fecha_fin']) : date('Y-m-d 23:59:59');
--GA--    
--GA--    $func_buscar = "navegarA(\'reportes_estadisticas\', {fecha_inicio: document.getElementById(\'fecha_inicio\').value.replace(\'T\', \' \'), fecha_fin: document.getElementById(\'fecha_fin\').value.replace(\'T\', \' \')})";
--GA--    
--GA--    $retorno .= "<div class=\'SECCION\'><h3>Filtro Paramétrico de Fechas</h3>"
--GA--              . "Desde: <input type=\'datetime-local\' id=\'fecha_inicio\' value=\'".str_replace(\' \', \'T\', $fecha_inicio)."\'> "
--GA--              . "Hasta: <input type=\'datetime-local\' id=\'fecha_fin\' value=\'".str_replace(\' \', \'T\', $fecha_fin)."\'> "
--GA--              . "<button onClick=\"$func_buscar\">Generar Reportes Analíticos</button></div>";
--GA--              
--GA--    // Consulta 1: Reporte Ventas por Categoría (Motor PL/pgSQL)
--GA--    $sql_ventas = "SELECT * FROM fn_reporte_ventas_tipos_plato(\'$fecha_inicio\'::timestamp, \'$fecha_fin\'::timestamp)";
--GA--    $res_ventas = procesar_query($sql_ventas, $conn);
--GA--    $retorno .= "<div class=\'SECCION\'><h3>Rentabilidad y Ventas por Categoría</h3><ul>";
--GA--    if ($res_ventas->cantidad == 0) {
--GA--        $retorno .= "<li>No hay movimientos completados en este rago de fechas.</li>";
--GA--    } else {
--GA--        foreach($res_ventas->datos as $v) {
--GA--            $retorno .= "<li class=\'PLATO\'><span class=\'NOMBRE\'>".$v['tipo_plato']."</span> - Cantidad Total: ".$v['cantidad_vendida']." platos vendidos | Ingresos Brutos: <b>$".$v['monto_total']."</b> (".$v['porcentaje_contribucion']."% del volumen de impacto)</li>";
--GA--        }
--GA--    }
--GA--    $retorno .= "</ul></div>";
--GA--    
--GA--    // Consulta 2: Tiempos y Desempeño Meseros (Motor PL/pgSQL)
--GA--    $sql_tiempos = "SELECT * FROM fn_calcular_tiempos_entrega(NULL, \'$fecha_inicio\'::timestamp, \'$fecha_fin\'::timestamp)";
--GA--    $res_tiempos = procesar_query($sql_tiempos, $conn);
--GA--    $retorno .= "<div class=\'SECCION\'><h3>Algoritmo de Desempeño y Puntualidad - Meseros</h3><ul>";
--GA--    if ($res_tiempos->cantidad == 0) {
--GA--        $retorno .= "<li>No existen métricas de entregas exitosas (estado = 2) en esta franja.</li>";
--GA--    } else {
--GA--        foreach($res_tiempos->datos as $t) {
--GA--            $retorno .= "<li class=\'PLATO\'><span class=\'NOMBRE\'>Mesero: ".$t['mesero_nombre']."</span> - Pedidos Atendidos: ".$t['pedidos_atendidos']." | Tiempo Promedio Preparación-Mesa: <b>".$t['tiempo_promedio_entrega']."</b> | Ratiode Entregas UltraRápidas (<30 min): <b>".$t['eficiencia']."%</b></li>";
--GA--        }
--GA--    }
--GA--    $retorno .= "</ul></div>";
--GA--    
--GA--    return $retorno;
--GA--}
--GA--//------------------------------------------------------------
--GA--?>
