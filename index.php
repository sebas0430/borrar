<?php
//------------------------------------------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("etc/parametros.php");
require_once("lib/libreria.php");
require_once("lib/restaurante.php");

session_start();

//------------------------------------------------------------
$conn = pg_conectar($host, $dbname, $user, $pass);
$contenido = "";

$opcion = isset($_REQUEST['opcion']) ? $_REQUEST['opcion'] : '';

// if not logged in, only fn_login is allowed
if (!isset($_SESSION['usuario_id']) && $opcion !== 'login') {
    $opcion = 'login_form';
}

if ($opcion == 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

if ($opcion == 'login_form') {
    $contenido = fn_login_form();
} else if ($opcion != "") {
    $funcion = "fn_".$opcion;
    if (function_exists($funcion))
        $contenido = $funcion($conn);
    else
        $contenido = fn_menu_opciones($conn);
} else {
    $contenido = fn_menu_opciones($conn);
}

//------------------------------------------------------------
$is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_REQUEST['formato']) && $_REQUEST['formato'] == 'json');

if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    $respuesta_plana = [
        "html" => $contenido,
        "token" => fn_cargar_token_activo()
    ];
    print json_encode($respuesta_plana);
    exit;
}

$esqueleto = file_get_contents("esqueleto.html");

// Inyectamos un meta-tag o script literal con el token base temporalmente
// para que programa.js lo agarre al arrancar la página web.
$token = fn_cargar_token_activo();
$inyeccion_js = "<script>const APP_TOKEN = '$token';</script>";

$html = sprintf($esqueleto, $inyeccion_js . $contenido);
print $html;

//------------------------------------------------------------
?>
