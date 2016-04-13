<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/11/2016
 * Time: 8:32 AM
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/flower-shop/config/settings.php');

?>

<html>
<body>
<form method="post" action="processForms.php?form=create_delivery_request">
    <div>
        <label>Order: <input name="order" type="text"></label>
    </div>
    <div>
        <label>Address: <input name="address" type="text"></label>
    </div>
    <div>
        <label>Latitude: <input name="latitude" type="text"></label>
    </div>
    <div>
        <label>Longitude: <input name="longitude" type="text"></label>
    </div>
    <div>
        <input type="submit" value="Submit">
    </div>
</form>
</body>
</html>
