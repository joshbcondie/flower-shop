<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/9/2016
 * Time: 10:29 AM
 */
//error_log('[api.php]::$_REQUEST: ' . print_r($_REQUEST, true));
//
//function getRealPOST() {
//    $pairs = explode("&", file_get_contents("php://input"));
//    $vars = array();
//    foreach ($pairs as $pair) {
//        $nv = explode("=", $pair);
//        $name = urldecode($nv[0]);
//        $value = urldecode($nv[1]);
//        $vars[$name] = $value;
//    }
//    return $vars;
//}
//
//error_log('[api.php]::$vars: ' . print_r(getRealPOST(), true));

require_once($_SERVER['DOCUMENT_ROOT'] . '/flower-shop/config/settings.php');

// Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {

    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];

}

try {

    $API = new ShopAPI($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
    echo $API->processAPI();

} catch (Exception $e) {

    echo json_encode(Array('error' => $e->getMessage()));

}