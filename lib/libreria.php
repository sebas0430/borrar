<?php

/*------------------------------------------------------------------*/
/**
 * @brief Establece una conexión con una base de datos PostgreSQL.
 * @param string $anfitrion Dirección del servidor de base de datos (host).
 * @param string $nombre_bd Nombre de la base de datos.
 * @param string $usuario Nombre del usuario de la base de datos.
 * @return resource Recurso de la conexión establecida.
 * @pre El servicio de PostgreSQL debe estar activo y accesible.
 * @post Si la conexión falla, el script termina su ejecución.
 */
function pg_conectar($host, $dbname, $user)
/*--------------------------------------------------------------------*/
{
    $conn = pg_connect("host=$host dbname=$dbname user=$user");
    if (!$conn)
        die("Error de conexión: ");//.pg_last_error());

    return $conn;
}

/*------------------------------------------------------------------*/
/**
 * @brief Formatea una variable para su visualización en depuración.
 * @param mixed $variable La variable, arreglo u objeto a inspeccionar.
 * @return string Cadena con la estructura de la variable dentro de etiquetas <pre>.
 * @pre Ninguna.
 * @post Devuelve el volcado de la variable como texto HTML.
 */
function Mostrar($variable)
/*--------------------------------------------------------------------*/
{
    $retorno = "<pre>".var_export($variable, true)."</pre>";
    return $retorno;
}

/*------------------------------------------------------------------*/
/**
 * @brief Ejecuta una sentencia SQL y devuelve los resultados en un objeto.
 * @param string $sentencia Consulta SQL a ejecutar.
 * @param resource $conexion Recurso de la conexión activa.
 * @return object Objeto que contiene la 'cantidad' de registros y los 'datos'.
 * @pre La conexión debe estar abierta y la sentencia ser válida.
 * @post Se libera la memoria de la consulta implícitamente al retornar.
 */
function procesar_query($sentencia, $conexion)
/*--------------------------------------------------------------------*/
{
    $retorno = array();
    $respuesta = pg_query($conexion, $sentencia);
    $Qregistros = pg_num_rows($respuesta);
    for ($i = 0; $i < $Qregistros; $i ++) {
        // $fila = pg_fetch_row($respuesta, $i);
        $retorno[] = pg_fetch_array($respuesta, $i, PGSQL_ASSOC);
    }
    return (object) array('cantidad' => $Qregistros, 'datos' => $retorno);
}
--GA--/*------------------------------------------------------------------*/
--GA--/**
--GA-- * @brief Sanitiza un valor de entrada para evitar ataques XSS o inyecciones.
--GA-- * @param mixed $valor Valor a sanitizar (entero o cadena).
--GA-- * @return mixed Valor sanitizado.
--GA-- * @pre Ninguna.
--GA-- * @post Devuelve el texto sin etiquetas peligrosas o el numérico puro.
--GA-- */
--GA--function fn_sanitizar($valor)
--GA--/*--------------------------------------------------------------------*/
--GA--{
--GA--    $retorno = null;
--GA--    if (is_numeric($valor)) {
--GA--        $retorno = (int) $valor;
--GA--    } else {
--GA--        $retorno = htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
--GA--    }
--GA--    return $retorno;
--GA--}
--GA--
--GA--/*------------------------------------------------------------------*/
--GA--/**
--GA-- * @brief Activa la sesión y recupera el token único (creándolo si no existe).
--GA-- * @return string Token de 64 caracteres.
--GA-- * @pre Ninguna.
--GA-- * @post Genera un token nuevo en variable de sesión si no existe.
--GA-- */
--GA--function fn_cargar_token_activo()
--GA--/*--------------------------------------------------------------------*/
--GA--{
--GA--    $retorno = "";
--GA--    if (session_status() === PHP_SESSION_NONE) {
--GA--        session_start();
--GA--    }
--GA--    if (empty($_SESSION['app_token'])) {
--GA--        $_SESSION['app_token'] = bin2hex(random_bytes(32));
--GA--    }
--GA--    $retorno = $_SESSION['app_token'];
--GA--    return $retorno;
--GA--}
--GA--
--GA--/*------------------------------------------------------------------*/
--GA--/**
--GA-- * @brief Verifica la validez de un token entregado por $_REQUEST.
--GA-- * @param string $token_recibido El token capturado.
--GA-- * @return bool Validez de la comparación.
--GA-- * @pre La sesión debe estar iniciada.
--GA-- * @post Retorna true o mata la ejecución con HTTP 403 si falla.
--GA-- */
--GA--function fn_validar_token($token_recibido)
--GA--/*--------------------------------------------------------------------*/
--GA--{
--GA--    $retorno = false;
--GA--    $token_real = fn_cargar_token_activo();
--GA--    if ($token_recibido === $token_real) {
--GA--        $retorno = true;
--GA--    } else {
--GA--        http_response_code(403);
--GA--        die(json_encode(["error" => "Token de seguridad invalido. Acceso denegado."]));
--GA--    }
--GA--    return $retorno;
--GA--}
--GA--
--GA--?>
