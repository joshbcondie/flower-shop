<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/11/2016
 * Time: 9:50 AM
 */

$form = $_REQUEST['form'];

error_log('[processForm.php]::$form: ' . print_r($form, true));

echo $form();

function register_deliver()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        return "POST " . print_r($_POST, true);
    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        return "GET " . print_r($_GET, true);
    }
}

function create_delivery_request()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        return "POST " . print_r($_POST, true);
    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        return "GET " . print_r($_GET, true);
    }
}