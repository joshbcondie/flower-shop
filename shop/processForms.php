<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/11/2016
 * Time: 9:50 AM
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/flower-shop/config/settings.php');


$form = $_REQUEST['form'];

//error_log('[processForm.php]::$form: ' . print_r($form, true));

echo $form();

function register_deliver()
{
//    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//        return "POST " . print_r($_POST, true);
//    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
//        return "GET " . print_r($_GET, true);
//    }

    $esl = $_REQUEST['ESL'];

    $driverId = Driver::addESL($esl);
    
    echo("Success! ESL = $esl, id = $driverId");
}

function create_delivery_request()
{
//    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//        return "POST " . print_r($_POST, true);
//    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
//        return "GET " . print_r($_GET, true);
//    }

    $order = $_REQUEST['order'];
    $address = $_REQUEST['address'];
    $latitude = $_REQUEST['latitude'];
    $longitude = $_REQUEST['longitude'];

    Delivery::createDeliveryRequest($order, $address, $latitude, $longitude);
    
    header('Location: manageDeliveries.php');
}

function accept_bid()
{
    $bidId = $_REQUEST['bid_id'];

    Delivery::acceptBid($bidId);

    header('Location: manageDeliveries.php');
}