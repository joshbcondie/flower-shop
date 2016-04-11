<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/9/2016
 * Time: 10:29 AM
 */

require_once("config/settings.php");

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