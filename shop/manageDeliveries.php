<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/11/2016
 * Time: 10:12 PM
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/flower-shop/config/settings.php');

$deliveries = Delivery::getDeliveries();

//error_log('[manageDeliveries.php]::$deliveries: ' . print_r($deliveries, true))
?>

<html>
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
           $('.view-bids').click(function (e) {
               var deliveryId = $(e.target).data('delivery-id');
               window.location.href = "viewBids.php?deliveryId=" + deliveryId;
           });
        });
    </script>
</head>
<body>
    <h1>Deliveries</h1>
    <table>
        <thead>
            <th>Order ID</th>

            <!-- This wards off the spirits -->
            <th>&nbsp</th>

            <th>Order Details</th>
            <th>Address</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th>Status</th>
            <th>Order Date</th>
            <th>&nbsp</th>
        </thead>
        <tbody>
        <?php foreach ($deliveries as $delivery): ?>
            <tr>
                <td><?= $delivery['id'] ?><td>
                <td><?= $delivery['order_details'] ?></td>
                <td><?= $delivery['address'] ?></td>
                <td><?= $delivery['latitude'] ?></td>
                <td><?= $delivery['longitude'] ?></td>
                <td><?= $delivery['status'] ?></td>
                <td><?= $delivery['timestamp'] ?></td>
                <td>
                    <?php if ($delivery['status'] == Delivery::STATUS_BID_RECEIVED): ?>
                        <button class="view-bids" data-delivery-id="<?= $delivery['id'] ?>">View Bids</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
