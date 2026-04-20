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

/*------------------------------------------------------------------*/
/**
 * @brief Gestiona el flujo de pedidos, listando mesas o procesando una específica.
 * @param resource $conexion_bd Recurso de conexión a la base de datos.
 * @return string HTML con el listado de mesas disponibles o el formulario de pedido.
 * @pre El parámetro mesa_id en $_REQUEST determina el flujo de la función.
 * @post Facilita la selección de mesa para iniciar un pedido.
 */
function fn_realizar_pedidos($conn)
/*--------------------------------------------------------------------*/
{
    $retorno = fn_boton_menu_principal()."<br />";

    if (isset($_REQUEST['mesa_id']))
        $accion = "pedido_mesa";
    else
        $accion = "listar_mesas";

    switch ($accion) {
    case 'listar_mesas':
        //----------------------------------------
        // Listado de mesas
        //----------------------------------------
        $sentencia = "
            SELECT MES.id
                 , MES.sillas
                 , HOR.inicio
                 , USR.nombre as nombre_usr
            FROM mesas AS MES
            LEFT JOIN horarios      AS HOR ON MES.id = HOR.mesa_id
            LEFT JOIN reservaciones AS RES ON RES.id = HOR.reservacion_id
            LEFT JOIN usuarios      AS USR ON USR.id = RES.cliente_id
            WHERE HOR.inicio BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '1 day'
            ORDER BY MES.id
            ";
        $resultado = procesar_query($sentencia, $conn);
 
        $botones_mesas = "";
--GA--        foreach ($resultado->datos as $mesa) {
--GA--            $script = "navegarA(\'realizar_pedidos\', {mesa_id: ".$mesa['id']."});";
--GA--            $botones_mesas.= "<button onClick=\"$script\">".$mesa['id']." - ".$mesa['nombre_usr']."</button>";
--GA--        }
        $retorno .= "<div class='MENU_OPCIONES'>".$botones_mesas."</div>";
        break;
    case 'pedido_mesa':
        $retorno .= "HACIENDO PEDIDO PARA UNA MESA.".Mostrar($_REQUEST);
    }

    return $retorno;
}
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
--GA--//------------------------------------------------------------
--GA--?>
