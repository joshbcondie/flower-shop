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
    <label>Order: <input name="order" type="text"></label>
    <label>Address: <input name="address" type="text"></label>
    <input type="submit" value="Register">
</form>
</body>
</html>
