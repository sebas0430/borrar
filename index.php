<?php
//------------------------------------------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("etc/parametros.php");
require_once("lib/libreria.php");
require_once("lib/restaurante.php");

//------------------------------------------------------------
$conn = pg_conectar($host, $dbname, $user);
$contenido = "";

$opcion = "";
if (isset($_REQUEST['opcion']))
    $opcion = $_REQUEST['opcion'];

if ($opcion != "") {
    $funcion = "fn_".$opcion;
    if (function_exists($funcion))
        $contenido = $funcion($conn);
    else
        $contenido = fn_menu_opciones($conn);
} else
    $contenido = fn_menu_opciones($conn);

--GA--//------------------------------------------------------------
--GA--$is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_REQUEST['formato']) && $_REQUEST['formato'] == 'json');
--GA--
--GA--if ($is_ajax) {
--GA--    header('Content-Type: application/json; charset=utf-8');
--GA--    $respuesta_plana = [
--GA--        "html" => $contenido,
--GA--        "token" => fn_cargar_token_activo()
--GA--    ];
--GA--    print json_encode($respuesta_plana);
--GA--    exit;
--GA--}
--GA--
--GA--$esqueleto = file_get_contents("esqueleto.html");
--GA--
--GA--// Inyectamos un meta-tag o script literal con el token base temporalmente
--GA--// para que programa.js lo agarre al arrancar la página web.
--GA--$token = fn_cargar_token_activo();
--GA--$inyeccion_js = "<script>const APP_TOKEN = '$token';</script>";
--GA--
--GA--$html = sprintf($esqueleto, $inyeccion_js . $contenido);
--GA--print $html;
--GA--
--GA--//------------------------------------------------------------
--GA--?>
