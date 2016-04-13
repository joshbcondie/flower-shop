<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/11/2016
 * Time: 10:00 PM
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/flower-shop/config/settings.php');

$json = file_get_contents('php://input');
$event = json_decode($json);


error_log('[shop/api.index.php]::$_REQUEST: ' . print_r($_REQUEST, true));
error_log('[shop/api.index.php]::$event: ' . print_r($event, true));

$eventName = $event->_name;

echo call_user_func("process_$eventName", $event);

function process_bid_available($event) {
    Delivery::makeBid($event->delivery_id, $event->driver_name, $event->estimated_time);
}

function process_delivery_complete($event) {
    
}
